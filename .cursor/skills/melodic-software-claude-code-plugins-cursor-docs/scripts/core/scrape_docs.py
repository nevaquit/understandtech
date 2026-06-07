#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
scrape_docs.py - Fetch documentation from llms.txt and URLs

Adapted for Cursor Documentation from openai-ecosystem codex-cli-docs skill.

Automates documentation scraping from:
- llms.txt files (Cursor documentation index)
- Individual URLs

Updates index.yaml with metadata tracking (source URL, hash, fetch date).

Usage:
    # Scrape from llms.txt
    python scrape_docs.py --llms-txt https://cursor.com/llms.txt

    # Scrape specific URL
    python scrape_docs.py --url https://cursor.com/docs/overview.md \\
                          --output cursor-com/docs/overview.md

Dependencies:
    pip install requests beautifulsoup4 markdownify pyyaml
"""

import sys
from pathlib import Path
sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

import argparse
import hashlib
import json
import os
import re
import subprocess
import threading
import time
import xml.etree.ElementTree as ET
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import datetime, timedelta, timezone
from urllib.parse import urlparse

from utils.script_utils import configure_utf8_output, format_duration, HTTP_STATUS_RATE_LIMITED
from utils.path_config import get_base_dir, get_index_path
from utils.config_helpers import (
    get_scraper_user_agent,
    get_index_lock_retry_delay,
    get_index_lock_retry_backoff,
    get_scraping_rate_limit,
    get_scraping_header_rate_limit,
    get_scraping_max_workers,
    get_scraping_progress_lock_timeout,
    get_scraping_index_lock_timeout,
    get_http_timeout,
    get_http_max_retries,
    get_http_initial_retry_delay,
    get_http_markdown_request_timeout,
    get_validation_timeout,
    get_scraping_progress_interval,
    get_scraping_progress_url_interval,
    get_url_exclusion_patterns
)
from utils.http_utils import fetch_with_retry
configure_utf8_output()

# Lock retry delays (loaded from config via config_helpers)
LOCK_RETRY_DELAY = get_index_lock_retry_delay()  # Delay between lock acquisition attempts
LOCK_RETRY_BACKOFF = get_index_lock_retry_backoff()  # Delay after failed lock acquisition

# Ensure unbuffered output for real-time streaming
if sys.stdout.isatty():
    # If running in terminal, use line buffering
    sys.stdout.reconfigure(line_buffering=True)
else:
    # If piped (e.g., from subprocess), force unbuffered
    sys.stdout.reconfigure(line_buffering=True)

# Thread-safe print helper for parallel processing (threading imported above)
_print_lock = threading.Lock()

def safe_print(*args, **kwargs):
    """Thread-safe print that flushes immediately for real-time output"""
    with _print_lock:
        print(*args, **kwargs, flush=True)

from utils.logging_utils import get_or_setup_logger

# Get source name from environment (set by scrape_all_sources.py for parallel worker identification)
_source_name = os.environ.get('CURSOR_DOCS_SOURCE_NAME', '')
_log_prefix = f"[{_source_name}] " if _source_name else ""

logger = get_or_setup_logger(__file__, log_category="scrape")

from utils.script_utils import ensure_yaml_installed

try:
    import requests
    from bs4 import BeautifulSoup
    from markdownify import markdownify as md
except ImportError as e:
    print(f"Missing dependency: {e}")
    print("Install with: pip install requests beautifulsoup4 markdownify")
    sys.exit(1)

yaml = ensure_yaml_installed()


class RateLimiter:
    """Thread-safe rate limiter for controlling request frequency"""

    def __init__(self, delay: float):
        """
        Initialize rate limiter

        Args:
            delay: Minimum delay between requests in seconds
        """
        self.delay = delay
        self.lock = threading.Lock()
        self.last_request_time = 0.0

    def wait(self):
        """Wait if necessary to maintain rate limit"""
        with self.lock:
            current_time = time.time()
            time_since_last = current_time - self.last_request_time
            if time_since_last < self.delay:
                sleep_time = self.delay - time_since_last
                time.sleep(sleep_time)
            self.last_request_time = time.time()

# Import index_manager for large file support
try:
    from management.index_manager import IndexManager
except ImportError:
    # Fallback if index_manager not available
    IndexManager = None

# Import metadata extractor
try:
    from management.extract_metadata import MetadataExtractor
except ImportError:
    MetadataExtractor = None


class DocScraper:
    """Documentation scraper with llms.txt and URL support"""

    def __init__(self, base_output_dir: Path | None = None, rate_limit: float | None = None,
                 header_rate_limit: float | None = None, trust_existing: bool = False,
                 skip_age_days: int = 0, max_workers: int | None = None, try_markdown: bool = True):
        """
        Initialize scraper

        Args:
            base_output_dir: Base directory for canonical storage. If None, uses config default.
            rate_limit: Delay between requests in seconds. If None, uses config default.
            header_rate_limit: Delay between HEAD requests in seconds. If None, uses config default.
            trust_existing: If True, skip hash check when HTTP headers unavailable (default: False)
            skip_age_days: Skip files fetched within this many days if hash matches (default: 0 = today only)
            max_workers: Maximum parallel workers for URL processing. If None, uses config default.
            try_markdown: If True, try fetching .md URLs before HTML conversion (default: True)
        """
        # Use config defaults if not provided
        self.base_output_dir = base_output_dir if base_output_dir else get_base_dir()
        self.rate_limit = rate_limit if rate_limit is not None else get_scraping_rate_limit()
        self.header_rate_limit = header_rate_limit if header_rate_limit is not None else get_scraping_header_rate_limit()
        self.trust_existing = trust_existing
        self.skip_age_days = skip_age_days
        self.max_workers = max_workers if max_workers is not None else get_scraping_max_workers()
        self.try_markdown = try_markdown
        self.index_path = get_index_path(self.base_output_dir)
        self.progress_file = self.base_output_dir / ".scrape_progress.json"

        # Thread-safe rate limiters (use instance values, not parameters)
        self.rate_limiter = RateLimiter(self.rate_limit)
        self.header_rate_limiter = RateLimiter(self.header_rate_limit)

        # Use thread-local sessions for thread safety
        self._session_local = threading.local()
        self.session_headers = {
            'User-Agent': get_scraper_user_agent()
        }

        # Initialize index manager if available
        if IndexManager:
            self.index_manager = IndexManager(base_output_dir)
        else:
            self.index_manager = None

        # Track 404 URLs for drift detection
        self.url_404s: set[str] = set()

        # Track skip reasons for observability (thread-safe counters)
        self._skip_lock = threading.Lock()
        self.skip_reasons: dict[str, int] = {
            'http_headers_unchanged': 0,
            'trust_existing_no_headers': 0,
            'content_hash_unchanged': 0,
            'fetched_within_age': 0,
            'fetched_today': 0,
            'resume_already_scraped': 0,
        }

    @property
    def session(self):
        """Get thread-local session"""
        if not hasattr(self._session_local, 'session'):
            self._session_local.session = requests.Session()
            self._session_local.session.headers.update(self.session_headers)
        return self._session_local.session

    def _track_skip(self, reason: str) -> None:
        """Thread-safe skip reason tracking for observability."""
        with self._skip_lock:
            if reason in self.skip_reasons:
                self.skip_reasons[reason] += 1
            else:
                self.skip_reasons[reason] = 1

    def get_skip_summary(self) -> str:
        """Get formatted summary of skip reasons for logging."""
        with self._skip_lock:
            active_reasons = {k: v for k, v in self.skip_reasons.items() if v > 0}
            if not active_reasons:
                return "No skips"
            parts = [f"{k}={v}" for k, v in active_reasons.items()]
            return f"SKIP REASONS: {', '.join(parts)}"

    def filter_excluded_urls(self, urls: list[str]) -> list[str]:
        """Filter out URLs matching exclusion patterns from config."""
        exclusion_patterns = get_url_exclusion_patterns()
        if not exclusion_patterns:
            return urls

        compiled_patterns = [re.compile(pattern) for pattern in exclusion_patterns]

        filtered_urls = []
        excluded_count = 0
        for url in urls:
            excluded = False
            for pattern in compiled_patterns:
                if pattern.search(url):
                    excluded = True
                    excluded_count += 1
                    break
            if not excluded:
                filtered_urls.append(url)

        if excluded_count > 0:
            print(f"  Excluded {excluded_count} URLs matching exclusion patterns")

        return filtered_urls

    def load_progress(self) -> set[str]:
        """Load already-scraped URLs from progress file (parallel-safe with locking)"""
        if not self.progress_file.exists():
            return set()

        lock_file = self.progress_file.parent / '.progress.lock'
        start_time = time.time()
        timeout = get_scraping_progress_lock_timeout()

        # Acquire lock
        while time.time() - start_time < timeout:
            try:
                fd = os.open(str(lock_file), os.O_CREAT | os.O_EXCL | os.O_WRONLY)
                os.close(fd)
                break
            except OSError:
                time.sleep(LOCK_RETRY_DELAY)
                continue
        else:
            print(f"  Warning: Could not acquire progress lock, retrying...")
            return set()

        try:
            try:
                with open(self.progress_file, 'r', encoding='utf-8') as f:
                    return set(json.load(f))
            except Exception as e:
                print(f"  Warning: Error loading progress: {e}")
                return set()
        finally:
            try:
                if lock_file.exists():
                    lock_file.unlink()
            except Exception:
                pass

    def save_progress(self, url: str):
        """Save successfully scraped URL (parallel-safe with locking)"""
        lock_file = self.progress_file.parent / '.progress.lock'
        start_time = time.time()
        timeout = get_scraping_progress_lock_timeout()

        while time.time() - start_time < timeout:
            try:
                fd = os.open(str(lock_file), os.O_CREAT | os.O_EXCL | os.O_WRONLY)
                os.close(fd)
                break
            except OSError:
                time.sleep(LOCK_RETRY_DELAY)
                continue
        else:
            print(f"  Warning: Could not acquire progress lock, skipping save...")
            return

        try:
            progress = self.load_progress()
            progress.add(url)
            try:
                with open(self.progress_file, 'w', encoding='utf-8') as f:
                    json.dump(list(progress), f, indent=2)
            except Exception as e:
                print(f"  Warning: Error saving progress: {e}")
        finally:
            try:
                if lock_file.exists():
                    lock_file.unlink()
            except Exception:
                pass

    def clear_progress(self):
        """Clear progress file"""
        if self.progress_file.exists():
            try:
                self.progress_file.unlink()
            except Exception:
                pass

    def fetch_url(self, url: str, max_retries: int | None = None, base_delay: float | None = None) -> tuple[str | None, str | None]:
        """Fetch content from URL with retry logic and exponential backoff"""
        if max_retries is None:
            max_retries = get_http_max_retries()
        if base_delay is None:
            base_delay = get_http_initial_retry_delay()

        for attempt in range(max_retries):
            try:
                if attempt == 0:
                    print(f"  Fetching: {url}")
                else:
                    print(f"  Fetching: {url} (attempt {attempt + 1}/{max_retries})")

                self.rate_limiter.wait()
                http_timeout = get_http_timeout()
                response = self.session.get(url, timeout=http_timeout)
                response.raise_for_status()
                return (response.text, response.url)

            except requests.HTTPError as e:
                status_code = e.response.status_code if e.response else None

                if status_code == 404:
                    print(f"  404 Not Found: {url}")
                    self.url_404s.add(url)
                    return (None, None)

                elif status_code == HTTP_STATUS_RATE_LIMITED:
                    if attempt < max_retries - 1:
                        retry_after = int(e.response.headers.get('Retry-After', base_delay * (2 ** attempt)))
                        wait_time = max(retry_after, base_delay * (2 ** attempt))
                        print(f"  Rate limited (429), retrying in {wait_time}s...")
                        time.sleep(wait_time)
                        continue
                    else:
                        print(f"  Rate limit exceeded after {max_retries} attempts: {url}")
                        return (None, None)

                elif status_code and status_code >= 500:
                    if attempt < max_retries - 1:
                        wait_time = base_delay * (2 ** attempt)
                        print(f"  Server error {status_code} (attempt {attempt + 1}/{max_retries}), retrying in {wait_time}s...")
                        time.sleep(wait_time)
                        continue
                    else:
                        print(f"  Server error {status_code} after {max_retries} attempts: {url}")
                        return (None, None)

                else:
                    print(f"  HTTP {status_code}: {url}")
                    return (None, None)

            except requests.ConnectionError:
                if attempt < max_retries - 1:
                    wait_time = base_delay * (2 ** attempt)
                    print(f"  Connection error (attempt {attempt + 1}/{max_retries}), retrying in {wait_time}s...")
                    time.sleep(wait_time)
                    continue
                else:
                    print(f"  Connection error after {max_retries} attempts: {url}")
                    return (None, None)

            except requests.Timeout:
                if attempt < max_retries - 1:
                    wait_time = base_delay * (2 ** attempt)
                    print(f"  Timeout (attempt {attempt + 1}/{max_retries}), retrying in {wait_time}s...")
                    time.sleep(wait_time)
                    continue
                else:
                    print(f"  Timeout after {max_retries} attempts: {url}")
                    return (None, None)

            except requests.RequestException:
                if attempt < max_retries - 1:
                    wait_time = base_delay * (2 ** attempt)
                    print(f"  Request error (attempt {attempt + 1}/{max_retries}), retrying in {wait_time}s...")
                    time.sleep(wait_time)
                    continue
                else:
                    print(f"  Request error after {max_retries} attempts: {url}")
                    return (None, None)

        return (None, None)

    def try_fetch_markdown(self, url: str) -> tuple[str | None, str | None, str | None]:
        """Try to fetch clean markdown from URL with retry logic"""
        if not self.try_markdown:
            return (None, None, None)

        try:
            if url.endswith('.md'):
                markdown_url = url
                final_url = url.removesuffix('.md')
                print(f"  Trying markdown URL (direct): {markdown_url}")
            else:
                head_timeout = get_http_timeout()
                self.rate_limiter.wait()
                head_response = self.session.head(url, timeout=head_timeout, allow_redirects=True)
                final_url = head_response.url

                if final_url != url:
                    print(f"  Redirected: {url} -> {final_url}")

                if final_url.endswith('/'):
                    final_url = final_url[:-1]

                if final_url.endswith('.md'):
                    markdown_url = final_url
                else:
                    markdown_url = f"{final_url}.md"

                print(f"  Trying markdown URL: {markdown_url}")

            markdown_timeout = get_http_markdown_request_timeout()
            max_retries = get_http_max_retries()
            initial_delay = get_http_initial_retry_delay()

            response = fetch_with_retry(
                markdown_url,
                max_retries=max_retries,
                initial_delay=initial_delay,
                timeout=markdown_timeout,
                session=self.session
            )

            time.sleep(self.rate_limiter.delay)

            content = response.text

            content_stripped = content.strip()
            if content_stripped.startswith('#') or content_stripped.startswith('---'):
                print(f"  Successfully fetched clean markdown from {markdown_url}")
                return (content, "markdown", final_url)
            else:
                print(f"  URL returned content but doesn't appear to be markdown")
                return (None, None, None)

        except requests.HTTPError as e:
            status_code = e.response.status_code if e.response else None
            failed_url = e.request.url if e.request else "unknown"

            if status_code == 404:
                if 'markdown_url' in locals() and failed_url == markdown_url:
                    self.url_404s.add(markdown_url)
                    print(f"  Markdown URL 404: {markdown_url}")
                else:
                    print(f"  Base URL 404: {url}")
            else:
                status_str = str(status_code) if status_code else 'HTTP error'
                print(f"  Markdown URL not available ({status_str}), will try HTML conversion")
            return (None, None, None)
        except (requests.ConnectionError, requests.Timeout, requests.RequestException) as e:
            error_type = type(e).__name__
            print(f"  Markdown URL not available ({error_type}), will try HTML conversion")
            return (None, None, None)

    def parse_llms_txt(self, llms_txt_url: str) -> list[str]:
        """Parse llms.txt and extract documentation URLs"""
        print(f"Parsing llms.txt: {llms_txt_url}")
        content, _ = self.fetch_url(llms_txt_url)
        if not content:
            return []

        parsed = urlparse(llms_txt_url)
        base_url = f"{parsed.scheme}://{parsed.netloc}"

        from llms_parser import LlmsParser
        parser = LlmsParser(base_url=base_url)
        urls = parser.extract_urls(content)

        print(f"  Found {len(urls)} documentation URLs")

        urls = self.filter_excluded_urls(urls)

        return urls

    def html_to_markdown(self, html_content: str, source_url: str | None = None) -> str:
        """Convert HTML content to markdown"""
        soup = BeautifulSoup(html_content, 'html.parser')

        for element in soup(['script', 'style', 'nav', 'header', 'footer']):
            element.decompose()

        markdown = md(str(soup), heading_style="ATX")

        markdown = re.sub(r'\n\n\n+', '\n\n', markdown)

        return markdown.strip()

    def normalize_content(self, content: str) -> str:
        """Normalize scraped content: decode HTML entities, straighten curly quotes"""
        import html as html_module
        content = html_module.unescape(content)
        content = content.replace('\u2018', "'")
        content = content.replace('\u2019', "'")
        content = content.replace('\u201c', '"')
        content = content.replace('\u201d', '"')
        return content

    def calculate_hash(self, content: str) -> str:
        """Calculate SHA-256 hash of content"""
        hash_obj = hashlib.sha256(content.encode('utf-8'))
        return f"sha256:{hash_obj.hexdigest()}"

    def normalize_etag(self, etag: str | None) -> str | None:
        """Normalize ETag for comparison"""
        if etag is None:
            return None

        if etag == '':
            return ''

        if etag.startswith('W/'):
            etag = etag[2:]

        etag = etag.strip('"').strip("'")

        return etag

    def should_skip_url(self, url: str, output_path: Path, use_http_headers: bool = True,
                        verbose: bool = False) -> bool:
        """Check if URL should be skipped (already exists with matching hash and source URL)"""
        if not output_path.exists():
            return False

        try:
            content = output_path.read_text(encoding='utf-8')
            if not content.startswith('---'):
                return False

            frontmatter_end = content.find('---', 3)
            if frontmatter_end == -1:
                return False

            frontmatter_text = content[3:frontmatter_end].strip()
            frontmatter = yaml.safe_load(frontmatter_text)

            if frontmatter.get('source_url') != url:
                return False

            existing_hash = frontmatter.get('content_hash')
            if existing_hash:
                if self.trust_existing:
                    if verbose:
                        print(f"  Skipping (trust existing): {url}")
                    self._track_skip('trust_existing_no_headers')
                    return True

            last_fetched = frontmatter.get('last_fetched')
            if last_fetched:
                try:
                    fetch_date = datetime.fromisoformat(last_fetched).date()
                    today = datetime.now(timezone.utc).date()
                    days_ago = (today - fetch_date).days

                    if days_ago <= self.skip_age_days:
                        if existing_hash and self.trust_existing:
                            if verbose:
                                print(f"  Skipping (fetched {days_ago} days ago, trust existing): {url}")
                            self._track_skip('fetched_within_age')
                            return True
                        elif days_ago == 0:
                            if verbose:
                                print(f"  Skipping (fetched today): {url}")
                            self._track_skip('fetched_today')
                            return True
                except (ValueError, TypeError):
                    pass

            return False
        except Exception as e:
            if verbose:
                print(f"  Warning: Error checking skip status: {e}, will re-scrape")
            return False

    def add_frontmatter(self, content: str, url: str, source_type: str,
                        sitemap_url: str | None = None, fetch_method: str | None = None) -> str:
        """Add YAML frontmatter to content"""
        content_hash = self.calculate_hash(content)

        frontmatter = {
            'source_url': url,
            'source_type': source_type,
            'content_hash': content_hash
        }

        if sitemap_url:
            frontmatter['sitemap_url'] = sitemap_url

        if fetch_method:
            frontmatter['fetch_method'] = fetch_method

        yaml_frontmatter = yaml.dump(frontmatter, default_flow_style=False, sort_keys=False)

        return f"---\n{yaml_frontmatter}---\n\n{content}"

    def scrape_url(self, url: str, output_path: Path, source_type: str,
                   sitemap_url: str | None = None, skip_existing: bool = False,
                   max_retries: int | None = None, max_content_age_days: int | None = None) -> bool:
        """Scrape single URL and save with frontmatter"""
        url_start_time = time.time()

        if max_retries is None:
            max_retries = get_http_max_retries()

        if skip_existing:
            if self.should_skip_url(url, output_path, use_http_headers=True, verbose=True):
                return True

        markdown, fetch_method, final_url = self.try_fetch_markdown(url)

        if final_url:
            if final_url != url:
                url = final_url
                try:
                    new_output_subdir = self.auto_detect_output_dir(url)
                    new_output_dir = self.base_output_dir / new_output_subdir
                    new_relative_path = self.url_to_filename(url, base_pattern=None)
                    output_path = new_output_dir / new_relative_path
                    output_path.parent.mkdir(parents=True, exist_ok=True)
                    print(f"  Updated output path due to redirect: {output_path}")
                except Exception as e:
                    print(f"  Warning: Failed to update output path for redirect {url}: {e}")

        markdown_url = f"{url}.md" if not url.endswith('.md') else url
        if markdown is None and (url in self.url_404s or markdown_url in self.url_404s):
            return False

        if markdown is None:
            html_content, final_html_url = self.fetch_url(url)

            if not html_content:
                print(f"  Failed to fetch HTML from {url}")
                return False

            if final_html_url and final_html_url != url:
                url = final_html_url
                try:
                    new_output_subdir = self.auto_detect_output_dir(url)
                    new_output_dir = self.base_output_dir / new_output_subdir
                    new_relative_path = self.url_to_filename(url, base_pattern=None)
                    output_path = new_output_dir / new_relative_path
                    output_path.parent.mkdir(parents=True, exist_ok=True)
                    print(f"  Updated output path due to HTML redirect: {output_path}")
                except Exception as e:
                    print(f"  Warning: Failed to update output path for HTML redirect {url}: {e}")

            markdown = self.html_to_markdown(html_content, url)
            fetch_method = "html"

        markdown = self.normalize_content(markdown)

        if output_path.exists():
            try:
                existing_content = output_path.read_text(encoding='utf-8')
                if existing_content.startswith('---'):
                    fm_end = existing_content.find('---', 3)
                    if fm_end != -1:
                        existing_fm = yaml.safe_load(existing_content[3:fm_end])
                        existing_hash = existing_fm.get('content_hash')
                        if existing_hash:
                            new_hash = self.calculate_hash(markdown)
                            if existing_hash == new_hash:
                                print(f"  Skipping (content unchanged, metadata-only diff): {url}")
                                self._track_skip('content_unchanged_pre_write')
                                return True
            except Exception:
                pass

        final_content = self.add_frontmatter(markdown, url, source_type, sitemap_url, fetch_method)

        output_path.parent.mkdir(parents=True, exist_ok=True)

        if not final_content.endswith('\n'):
            final_content += '\n'

        with open(output_path, 'w', encoding='utf-8', newline='\n') as f:
            f.write(final_content)
        safe_print(f"  Saved: {output_path}")

        try:
            relative_to_base = output_path.relative_to(self.base_output_dir)
            path_normalized = str(relative_to_base).replace('\\', '/')
        except ValueError:
            path_normalized = str(output_path).replace('\\', '/')

        metadata = {
            'path': path_normalized,
            'url': url,
            'hash': self.calculate_hash(final_content),
            'last_fetched': datetime.now(timezone.utc).strftime('%Y-%m-%d'),
            'source_type': source_type,
            'sitemap_url': sitemap_url
        }

        if MetadataExtractor:
            try:
                extractor = MetadataExtractor(output_path, url)
                extracted = extractor.extract_all()
                metadata.update(extracted)
            except Exception:
                pass

        url_duration_ms = (time.time() - url_start_time) * 1000
        logger.debug(f"URL scraped in {url_duration_ms:.0f}ms: {url}")
        return metadata

    def normalize_domain(self, url: str) -> str:
        """Extract and normalize domain name for use as folder name"""
        parsed = urlparse(url)
        domain = parsed.netloc
        if domain.startswith('www.'):
            domain = domain[4:]
        return domain.replace('.', '-')

    def auto_detect_output_dir(self, url: str, url_filter: str | None = None) -> str:
        """Auto-detect output directory based on URL domain"""
        from urllib.parse import urlparse
        from utils.config_helpers import get_output_dir_mapping

        parsed = urlparse(url)
        domain = parsed.netloc

        return get_output_dir_mapping(domain)

    def url_to_filename(self, url: str, base_pattern: str = None) -> str:
        """Convert URL to relative filepath preserving directory structure"""
        parsed = urlparse(url)
        path = parsed.path.strip('/')

        if base_pattern:
            base_pattern = base_pattern.strip('/')
            if path.startswith(base_pattern):
                path = path[len(base_pattern):].strip('/')

        path = re.sub(r'^[a-z]{2}(-[A-Z]{2})?/', '', path)

        if not path.endswith('.md'):
            path += '.md'

        return path

    def update_index(self, doc_id: str, metadata: dict) -> None:
        """Update index.yaml with document metadata (parallel-safe with file locking)"""
        if self.index_manager:
            if not self.index_manager.update_entry(doc_id, metadata):
                print(f"  Warning: Failed to update index entry: {doc_id}")
        else:
            lock_file = self.index_path.parent / '.index.lock'
            start_time = time.time()
            timeout = get_scraping_index_lock_timeout()

            while time.time() - start_time < timeout:
                try:
                    fd = os.open(str(lock_file), os.O_CREAT | os.O_EXCL | os.O_WRONLY)
                    os.close(fd)
                    break
                except OSError:
                    time.sleep(LOCK_RETRY_DELAY)
                    continue
            else:
                print(f"  Warning: Could not acquire index lock")
                return

            try:
                if self.index_path.exists():
                    with open(self.index_path, 'r', encoding='utf-8') as f:
                        index = yaml.safe_load(f) or {}
                else:
                    index = {}

                index[doc_id] = metadata

                with open(self.index_path, 'w', encoding='utf-8') as f:
                    yaml.dump(index, f, default_flow_style=False, sort_keys=False, allow_unicode=True)
            finally:
                try:
                    if lock_file.exists():
                        lock_file.unlink()
                except Exception:
                    pass

    def scrape_from_llms_txt(self, llms_txt_url: str, output_subdir: str | None = None,
                             limit: int | None = None, max_age_days: int | None = None,
                             skip_existing: bool = False, max_retries: int | None = None,
                             auto_validate: bool = False, expected_count: int | None = None,
                             resume: bool = False, skip_urls: list[str] | None = None) -> int:
        """Scrape multiple URLs from llms.txt index"""
        if max_retries is None:
            max_retries = get_http_max_retries()

        urls = self.parse_llms_txt(llms_txt_url)

        if skip_urls:
            original_count = len(urls)
            skip_urls_set = set(skip_urls)
            original_urls_set = set(urls)
            urls = [u for u in urls if u not in skip_urls_set]
            skipped_known_bad = original_count - len(urls)
            if skipped_known_bad > 0:
                logger.info(f"Skipped {skipped_known_bad} known-bad URL(s) from expected_errors config")
                for skipped_url in skip_urls_set:
                    if skipped_url in original_urls_set:
                        logger.debug(f"  Skipped known-bad URL: {skipped_url}")
                        self._track_skip('expected_error_404')

        if resume:
            progress = self.load_progress()
            urls_to_scrape = [u for u in urls if u not in progress]
            skipped_count = len(urls) - len(urls_to_scrape)
            if skipped_count > 0:
                print(f"  Resuming: {skipped_count} URLs already scraped, {len(urls_to_scrape)} remaining")
                for _ in range(skipped_count):
                    self._track_skip('resume_already_scraped')
            urls = urls_to_scrape
        else:
            self.clear_progress()

        if limit:
            urls = urls[:limit]
            print(f"  Limiting to first {limit} URLs")

        if not output_subdir:
            output_subdir = self.auto_detect_output_dir(llms_txt_url)
            print(f"  Auto-detected output directory: {output_subdir}")

        output_dir = self.base_output_dir / output_subdir
        success_count = 0
        skipped_count = 0
        failed_count = 0
        failed_urls = []
        url_timings: list[float] = []
        scrape_start_time = time.time()
        last_progress_time = time.time()
        progress_interval = get_scraping_progress_interval()
        progress_url_interval = get_scraping_progress_url_interval()

        for i, url in enumerate(urls, 1):
            url_start = time.time()
            print(f"\n[{i}/{len(urls)}] Processing: {url}")

            relative_path = self.url_to_filename(url, base_pattern=None)
            output_path = output_dir / relative_path

            was_skipped = False
            if skip_existing and self.should_skip_url(url, output_path):
                was_skipped = True
                skipped_count += 1

            scrape_result = self.scrape_url(url, output_path, source_type='llms-txt', sitemap_url=llms_txt_url,
                             skip_existing=skip_existing, max_retries=max_retries,
                             max_content_age_days=max_age_days)
            if scrape_result:
                success_count += 1

                if resume:
                    self.save_progress(url)

                if not was_skipped and output_path.exists():
                    if isinstance(scrape_result, dict):
                        metadata = scrape_result
                        path_str = metadata['path']
                        doc_id_suffix = path_str.replace('.md', '').replace('/', '-')
                        doc_id = doc_id_suffix
                        self.update_index(doc_id, metadata)
                    else:
                        doc_id_suffix = relative_path.replace('.md', '').replace('/', '-')
                        doc_id = f"{output_subdir.replace('/', '-')}-{doc_id_suffix}"
                        try:
                            content = output_path.read_text(encoding='utf-8')
                            frontmatter = {}
                            if content.startswith('---'):
                                end = content.find('---', 3)
                                if end > 0:
                                    frontmatter = yaml.safe_load(content[3:end])
                            path_str = str(output_path.relative_to(self.base_output_dir))
                            metadata = {
                                'source_url': url,
                                'content_hash': frontmatter.get('content_hash', ''),
                                'last_fetched': frontmatter.get('last_fetched', ''),
                                'path': path_str,
                                'doc_id': doc_id
                            }
                            self.update_index(doc_id, metadata)
                        except Exception as e:
                            logger.warning(f"Could not extract metadata for index: {e}")
            else:
                failed_count += 1
                failed_urls.append(url)

            url_time = time.time() - url_start
            url_timings.append(url_time)

            current_time = time.time()
            if current_time - last_progress_time >= progress_interval or i % progress_url_interval == 0:
                elapsed = current_time - scrape_start_time
                rate = i / elapsed if elapsed > 0 else 0
                remaining = len(urls) - i
                eta = remaining / rate if rate > 0 else 0
                print(f"  Progress: {i}/{len(urls)} ({i/len(urls)*100:.1f}%) | "
                      f"Rate: {rate:.1f} URL/s | ETA: {format_duration(eta)}")
                last_progress_time = current_time

        total_scrape_time = time.time() - scrape_start_time
        throughput = len(urls) / total_scrape_time if total_scrape_time > 0 else 0
        avg_time_ms = (sum(url_timings) / len(url_timings) * 1000) if url_timings else 0
        max_time_ms = max(url_timings) * 1000 if url_timings else 0
        min_time_ms = min(url_timings) * 1000 if url_timings else 0

        print(f"\n{'='*60}")
        print(f"Scraping Summary for {len(urls)} URLs:")
        print(f"{'='*60}")
        new_updated = max(0, success_count - skipped_count)
        print(f"  New/Updated:      {new_updated}")
        print(f"  Skipped (hash):   {skipped_count}")
        if failed_count > 0:
            print(f"  Failed:           {failed_count}")
        print(f"  Total processed:  {len(urls)}")
        skip_summary = self.get_skip_summary()
        if skip_summary != "No skips":
            logger.info(skip_summary)
        print(f"{'='*60}")
        print(f"  Total time:       {total_scrape_time:.1f}s")
        print(f"  Throughput:       {throughput:.2f} URLs/sec")
        print(f"  Avg per URL:      {avg_time_ms:.0f}ms")
        if len(url_timings) > 1:
            print(f"  Slowest URL:      {max_time_ms:.0f}ms")
            print(f"  Fastest URL:      {min_time_ms:.0f}ms")
        print(f"{'='*60}")

        if failed_urls:
            print(f"\nFailed URLs ({len(failed_urls)}):")
            for failed_url in failed_urls:
                print(f"  - {failed_url}")

        return success_count


def main():
    """Main entry point"""
    parser = argparse.ArgumentParser(
        description='Scrape Cursor documentation from llms.txt',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  # Scrape from llms.txt (auto-detects output directory from domain)
  python scrape_docs.py --llms-txt https://cursor.com/llms.txt
  # Output: cursor-com/...

  # Scrape single URL
  python scrape_docs.py --url https://cursor.com/docs/overview.md \\
                        --output cursor-com/docs/overview.md

  # Test with limit
  python scrape_docs.py --llms-txt https://cursor.com/llms.txt \\
                        --limit 5
        """
    )

    # Input source (mutually exclusive)
    source_group = parser.add_mutually_exclusive_group(required=True)
    source_group.add_argument('--llms-txt', help='URL to llms.txt file (markdown link index)')
    source_group.add_argument('--url', help='Single URL to scrape')

    # Output
    parser.add_argument('--output',
                       help='Output path (auto-detected from URL if not provided; required for --url)')

    # Options
    parser.add_argument('--limit', type=int, help='Limit number of URLs to scrape (for testing)')

    # Get defaults from config
    from utils.cli_utils import add_base_dir_argument, resolve_base_dir_from_args
    default_rate_limit = get_scraping_rate_limit()
    default_header_rate_limit = get_scraping_header_rate_limit()
    default_max_workers = get_scraping_max_workers()

    add_base_dir_argument(parser)
    parser.add_argument('--rate-limit', type=float, default=default_rate_limit,
                       help=f'Delay between requests in seconds (default: {default_rate_limit}, from config)')
    parser.add_argument('--header-rate-limit', type=float, default=default_header_rate_limit,
                       help=f'Delay between HEAD requests in seconds (default: {default_header_rate_limit}, from config)')
    parser.add_argument('--max-workers', type=int, default=default_max_workers,
                       help=f'Maximum parallel workers for URL processing (default: {default_max_workers}, from config)')
    parser.add_argument('--skip-existing', action='store_true',
                       help='Skip URLs that already exist with matching hash and source URL (idempotent mode)')
    parser.add_argument('--trust-existing', action='store_true',
                       help='Skip hash check when HTTP headers unavailable (faster, but less accurate)')
    parser.add_argument('--no-try-markdown', action='store_true',
                       help='Skip trying .md URLs (go straight to HTML conversion)')
    parser.add_argument('--skip-age-days', type=int, default=0,
                       help='Skip files fetched within this many days if hash matches (default: 0 = today only)')
    default_max_retries = get_http_max_retries()
    parser.add_argument('--max-retries', type=int, default=default_max_retries,
                       help=f'Maximum retry attempts for transient failures (default: {default_max_retries}, from config)')
    parser.add_argument('--resume', action='store_true',
                       help='Resume from last successful URL (uses .scrape_progress.json)')
    parser.add_argument('--skip-urls', type=str, nargs='*', default=[],
                       help='URLs to skip (e.g., known 404s from expected_errors in sources.json)')

    args = parser.parse_args()

    # Print dev/prod mode banner for visibility
    if not _source_name:
        from utils.dev_mode import print_mode_banner
        from utils.path_config import get_base_dir
        print_mode_banner(logger)
        logger.info(f"Canonical dir: {get_base_dir()}")

    start_context = {
        'source': 'llms_txt' if args.llms_txt else 'url',
        'base_dir': args.base_dir,
        'limit': args.limit,
        'skip_existing': args.skip_existing
    }
    if _source_name:
        start_context['source_name'] = _source_name
    logger.start(start_context)

    exit_code = 0
    try:
        if args.url and not args.output:
            parser.error("--url requires --output to be specified")

        base_dir = resolve_base_dir_from_args(args)

        base_dir.mkdir(parents=True, exist_ok=True)

        print(f"Using base directory: {base_dir}")
        print(f"   (Absolute path: {base_dir.absolute()})")

        scraper = DocScraper(
            base_dir,
            rate_limit=args.rate_limit if args.rate_limit != default_rate_limit else None,
            header_rate_limit=args.header_rate_limit if args.header_rate_limit != default_header_rate_limit else None,
            max_workers=args.max_workers if args.max_workers != default_max_workers else None,
            trust_existing=args.trust_existing,
            skip_age_days=args.skip_age_days,
            try_markdown=not args.no_try_markdown
        )

        if args.llms_txt:
            success_count = scraper.scrape_from_llms_txt(
                args.llms_txt,
                args.output,
                limit=args.limit,
                skip_existing=args.skip_existing,
                max_retries=args.max_retries,
                resume=args.resume,
                skip_urls=args.skip_urls if args.skip_urls else None
            )
        elif args.url:
            output_path = base_dir / args.output
            result = scraper.scrape_url(args.url, output_path, source_type='manual',
                                        skip_existing=args.skip_existing,
                                        max_retries=args.max_retries)

            if isinstance(result, dict):
                success = True
                metadata = result
                path_str = metadata['path']
                doc_id = path_str.replace('.md', '').replace('/', '-')
                scraper.update_index(doc_id, metadata)
            elif result is True:
                success = True
            else:
                success = False

            success_count = 1 if success else 0

        print(f"\n{'='*60}")
        print(f"Scraping complete: {success_count} document(s) processed")

        if args.llms_txt:
            output_subdir = args.output or scraper.auto_detect_output_dir(args.llms_txt)
            output_dir = base_dir / output_subdir
            if output_dir.exists():
                md_files = list(output_dir.glob("**/*.md"))
                total_size = sum(f.stat().st_size for f in md_files)
                size_mb = total_size / 1024 / 1024
                print(f"Files: {len(md_files)}")
                print(f"Size: {size_mb:.2f} MB")
                logger.track_metric('total_files', len(md_files))
                logger.track_metric('total_size_mb', size_mb)

        duration_seconds = logger.performance_metrics.get('duration_seconds', 0)
        if duration_seconds > 0:
            print(f"Total duration: {format_duration(duration_seconds)}")

        logger.track_metric('success_count', success_count)

        summary = {
            'success_count': success_count,
            'source': 'llms_txt' if args.llms_txt else 'url'
        }
        if _source_name:
            summary['source_name'] = _source_name

        logger.end(exit_code=exit_code, summary=summary)

    except SystemExit:
        raise
    except Exception as e:
        logger.log_error("Fatal error in scrape_docs", error=e)
        exit_code = 1
        logger.end(exit_code=exit_code)
        sys.exit(exit_code)


if __name__ == '__main__':
    main()
