#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
extract_metadata.py - Extract metadata (title, description, keywords, tags) from markdown files

Adapted for Cursor Documentation from openai-ecosystem codex-cli-docs skill.

Extracts metadata from markdown content for indexing:
- Title: First h1 heading or frontmatter title
- Description: First paragraph or excerpt
- Keywords: From frontmatter, headings, or content analysis
- Tags: From frontmatter or auto-categorization
- Category: Auto-detect from path/domain/content
- Domain: Extract from URL

Usage:
    python extract_metadata.py <file_path>
    python extract_metadata.py <file_path> --url <url>
"""

import sys
from pathlib import Path
sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
import bootstrap; config_dir = bootstrap.config_dir

import argparse
import re
from typing import Union
from urllib.parse import urlparse

from utils.script_utils import configure_utf8_output, ensure_yaml_installed

FILE_TOKEN_EXTENSIONS = {
    'md', 'mdx', 'yaml', 'yml', 'json', 'toml', 'ini', 'cfg',
    'ps1', 'sh', 'bat', 'py', 'ts', 'js'
}

# Configure UTF-8 output for Windows console compatibility
configure_utf8_output()

# Import config helpers for configuration access
try:
    from config.config_registry import get_default
except ImportError:
    def get_default(section: str, key: str, default=None):
        return default

from utils.logging_utils import get_or_setup_logger
logger = get_or_setup_logger(__file__, log_category="index")

yaml = ensure_yaml_installed()

# Optional NLP libraries
try:
    import yake
    YAKE_AVAILABLE = True
except ImportError:
    YAKE_AVAILABLE = False


class MetadataExtractor:
    """Extract metadata from markdown files"""

    # Class-level caches (avoid repeating expensive work per document)
    _tag_config = None
    _filter_config = None
    _stop_words_cache: set[str] | None = None

    def __init__(self, file_path: Path, url: str | None = None):
        """
        Initialize extractor

        Args:
            file_path: Path to markdown file
            url: Optional source URL

        Raises:
            FileNotFoundError: If file doesn't exist
            UnicodeDecodeError: If file can't be decoded as UTF-8
        """
        self.file_path = Path(file_path)
        if not self.file_path.exists():
            raise FileNotFoundError(f"File not found: {self.file_path}")

        self.url = url
        try:
            self.content = self.file_path.read_text(encoding='utf-8')
        except UnicodeDecodeError as e:
            raise ValueError(f"Failed to read file as UTF-8: {self.file_path}") from e

        self.frontmatter = self._parse_frontmatter()
        self.body = self._strip_frontmatter()

        # Load configs (cached at class level)
        self._load_configs()

    @classmethod
    def _load_configs(cls):
        """Load YAML config files (cached at class level)"""
        if cls._tag_config is not None and cls._filter_config is not None:
            return

        # Get config directory using centralized utility (already in sys.path)
        from utils.common_paths import get_config_dir
        config_dir = get_config_dir()

        # Load tag detection config
        tag_config_path = config_dir / 'tag_detection.yaml'
        if tag_config_path.exists():
            try:
                with open(tag_config_path, 'r', encoding='utf-8') as f:
                    cls._tag_config = yaml.safe_load(f) or {}
            except Exception as e:
                print(f"Warning: Could not load tag_detection.yaml: {e}")
                cls._tag_config = {}
        else:
            cls._tag_config = {}

        # Load filtering config
        filter_config_path = config_dir / 'filtering.yaml'
        if filter_config_path.exists():
            try:
                with open(filter_config_path, 'r', encoding='utf-8') as f:
                    cls._filter_config = yaml.safe_load(f) or {}
            except Exception as e:
                print(f"Warning: Could not load filtering.yaml: {e}")
                cls._filter_config = {}
        else:
            cls._filter_config = {}

    def _get_stop_words(self) -> set[str]:
        """Get stop words from static list + domain-specific config."""
        if MetadataExtractor._stop_words_cache is not None:
            return set(MetadataExtractor._stop_words_cache)

        # Comprehensive English stop words
        stop_words: set[str] = {
            'a', 'about', 'above', 'after', 'again', 'against', 'all', 'also', 'am', 'an', 'and',
            'any', 'are', 'as', 'at', 'be', 'because', 'been', 'before', 'being', 'below', 'between',
            'both', 'but', 'by', 'can', 'could', 'did', 'do', 'does', 'doing', 'down', 'during',
            'each', 'few', 'for', 'from', 'further', 'had', 'has', 'have', 'having', 'he', 'her',
            'here', 'hers', 'herself', 'him', 'himself', 'his', 'how', 'i', 'if', 'in', 'into', 'is',
            'it', 'its', 'itself', 'just', 'me', 'more', 'most', 'my', 'myself', 'no', 'nor', 'not',
            'now', 'of', 'off', 'on', 'once', 'only', 'or', 'other', 'our', 'ours', 'ourselves', 'out',
            'over', 'own', 'same', 'she', 'should', 'so', 'some', 'such', 'than', 'that', 'the', 'their',
            'theirs', 'them', 'themselves', 'then', 'there', 'these', 'they', 'this', 'those', 'through',
            'to', 'too', 'under', 'until', 'up', 'very', 'was', 'we', 'were', 'what', 'when', 'where',
            'which', 'while', 'who', 'whom', 'why', 'will', 'with', 'would', 'you', 'your', 'yours',
            'yourself', 'yourselves'
        }

        # Add domain-specific stop words from config
        if self._filter_config:
            domain_stop_words = self._filter_config.get('domain_stop_words', [])
            stop_words.update(domain_stop_words)

        # Add common markdown/document terms
        stop_words.update({'md', 'doc', 'docs', 'guide', 'reference', 'api', 'overview', 'intro', 'about', 'using'})

        MetadataExtractor._stop_words_cache = set(stop_words)
        return stop_words

    def _parse_frontmatter(self) -> dict:
        """Parse YAML frontmatter if present"""
        if not self.content.startswith('---'):
            return {}

        try:
            parts = self.content.split('---', 2)
            if len(parts) >= 3:
                frontmatter_text = parts[1].strip()
                if frontmatter_text:
                    return yaml.safe_load(frontmatter_text) or {}
        except Exception:
            pass

        return {}

    def _strip_frontmatter(self) -> str:
        """Remove frontmatter and return body"""
        if self.content.startswith('---'):
            parts = self.content.split('---', 2)
            if len(parts) >= 3:
                return parts[2].strip()
        return self.content.strip()

    def extract_title(self) -> str | None:
        """
        Extract document title

        Priority:
        1. Frontmatter 'title'
        2. First h1 heading
        3. Filename (without extension)
        """
        # Check frontmatter
        if 'title' in self.frontmatter:
            return str(self.frontmatter['title']).strip()

        # Check first h1 heading
        h1_match = re.search(r'^#\s+(.+)$', self.body, re.MULTILINE)
        if h1_match:
            return h1_match.group(1).strip()

        # Fallback to filename
        return self.file_path.stem.replace('-', ' ').replace('_', ' ').title()

    def extract_description(self, max_length: int = 200) -> str | None:
        """
        Extract document description

        Priority:
        1. Frontmatter 'description'
        2. First paragraph (non-heading, non-code)
        """
        # Check frontmatter
        if 'description' in self.frontmatter:
            desc = str(self.frontmatter['description'])
            if desc:
                return desc.strip()[:max_length]

        # Extract first paragraph
        lines = self.body.split('\n')
        paragraph_lines = []

        for line in lines:
            line = line.strip()
            if not line:
                if paragraph_lines:
                    break
                continue
            if line.startswith('#') or line.startswith('```') or line.startswith('---'):
                continue
            if line.startswith('[') and '](' in line:
                continue
            if line.startswith('>'):
                continue

            line = line.lstrip('> ').strip()
            if not line:
                continue

            paragraph_lines.append(line)
            if len(' '.join(paragraph_lines)) > max_length:
                break

        if paragraph_lines:
            desc = ' '.join(paragraph_lines)
            if len(desc) > max_length:
                truncate_pos = desc.rfind(' ', 0, max_length)
                if truncate_pos > 0:
                    desc = desc[:truncate_pos] + '...'
                else:
                    desc = desc[:max_length] + '...'
            return desc.strip()

        return None

    def extract_keywords(self) -> list[str]:
        """
        Extract meaningful keywords from content using multi-source extraction.

        Sources (in priority order):
        1. Frontmatter keywords/tags
        2. Title and description
        3. Heading keywords
        4. YAKE extraction (if available)
        5. Filename keywords
        """
        keywords: set[str] = set()
        stop_words = self._get_stop_words()

        # 1. From frontmatter
        if 'keywords' in self.frontmatter:
            kw = self.frontmatter['keywords']
            if isinstance(kw, list):
                keywords.update(str(k).lower().strip() for k in kw if k)
            elif isinstance(kw, str):
                keywords.update(k.lower().strip() for k in kw.split(',') if k.strip())

        if 'tags' in self.frontmatter:
            tags = self.frontmatter['tags']
            if isinstance(tags, list):
                keywords.update(str(t).lower().strip() for t in tags if t)
            elif isinstance(tags, str):
                keywords.update(t.lower().strip() for t in tags.split(',') if t.strip())

        # 2. From title
        title = self.extract_title()
        if title:
            title_words = [w for w in re.findall(r'\b[a-z]{3,}\b', title.lower()) if w not in stop_words]
            keywords.update(title_words)

        # 3. From headings
        heading_pattern = re.compile(r'^#{1,6}\s+(.+)$', re.MULTILINE)
        for match in heading_pattern.finditer(self.body):
            heading_text = match.group(1).strip()
            heading_text = re.sub(r'\[([^\]]+)\]\([^\)]+\)', r'\1', heading_text)
            heading_text = re.sub(r'`([^`]+)`', r'\1', heading_text)
            words = re.findall(r'\b[a-z]{3,}\b', heading_text.lower())
            keywords.update(w for w in words if w not in stop_words)

        # 4. YAKE extraction if available
        if YAKE_AVAILABLE:
            try:
                yake_text = re.sub(r'```[\s\S]*?```', '', self.body)
                yake_text = re.sub(r'`[^`]+`', '', yake_text)
                yake_text = re.sub(r'\[([^\]]+)\]\([^\)]+\)', r'\1', yake_text)
                yake_text = re.sub(r'[#*_~]', '', yake_text)

                if len(yake_text) >= 50:
                    kw_extractor = yake.KeywordExtractor(
                        lan='en', n=3, dedupLim=0.7, top=15, features=None
                    )
                    yake_keywords = kw_extractor.extract_keywords(yake_text)
                    for score, keyword in yake_keywords:
                        keyword_lower = keyword.lower().strip()
                        if keyword_lower and len(keyword_lower) >= 3 and keyword_lower not in stop_words:
                            keywords.add(keyword_lower)
            except Exception:
                pass

        # 5. From filename
        filename_words = re.findall(r'\b[a-z]{3,}\b', self.file_path.stem.lower())
        keywords.update(w for w in filename_words if w not in stop_words)

        # Clean and limit keywords
        cleaned = sorted(keywords, key=lambda x: (-len(x), x))[:12]
        return cleaned

    def extract_tags(self) -> list[str]:
        """Extract tags for categorization"""
        tags: set[str] = set()

        # From frontmatter
        if 'tags' in self.frontmatter:
            frontmatter_tags = self.frontmatter['tags']
            if isinstance(frontmatter_tags, list):
                tags.update(str(t).lower().strip() for t in frontmatter_tags)
            elif isinstance(frontmatter_tags, str):
                tags.update(t.lower().strip() for t in frontmatter_tags.split(','))

        # Auto-categorize from path
        path_str = self.file_path.as_posix().lower()

        if 'cursor' in path_str:
            tags.add('cursor')
        if 'cli' in path_str:
            tags.add('cli')
        if 'agent' in path_str:
            tags.add('agent')
        if 'tab' in path_str:
            tags.add('tab')
        if 'mcp' in path_str:
            tags.add('mcp')
        if 'context' in path_str:
            tags.add('context')
        if 'config' in path_str or 'configuration' in path_str:
            tags.add('configuration')
        if 'guide' in path_str or 'tutorial' in path_str:
            tags.add('guides')
        if 'reference' in path_str:
            tags.add('reference')
        if 'example' in path_str or 'cookbook' in path_str:
            tags.add('examples')
        if 'install' in path_str or 'setup' in path_str:
            tags.add('installation')
        if 'enterprise' in path_str:
            tags.add('enterprise')
        if 'cloud' in path_str:
            tags.add('cloud-agent')

        # Content-based tags
        body_lower = self.body.lower()
        if 'inline edit' in body_lower:
            tags.add('inline-edit')
        if 'cursor' in body_lower:
            tags.add('cursor')

        if not tags:
            tags.add('reference')

        return sorted(tags)[:6]

    def extract_category(self) -> str | None:
        """Auto-detect category from path/content"""
        if 'category' in self.frontmatter:
            return str(self.frontmatter['category']).lower().strip()

        path_str = self.file_path.as_posix().lower()

        if 'guide' in path_str or 'tutorial' in path_str:
            return 'guides'
        if 'reference' in path_str or 'api' in path_str:
            return 'api'
        if 'example' in path_str or 'cookbook' in path_str:
            return 'cookbook'
        if 'install' in path_str or 'setup' in path_str or 'getting-started' in path_str:
            return 'get-started'
        if 'cli' in path_str:
            return 'cli'
        if 'context' in path_str:
            return 'context'
        if 'integration' in path_str:
            return 'integrations'
        if 'enterprise' in path_str:
            return 'enterprise'

        return None

    def extract_domain(self) -> str | None:
        """Extract domain from URL"""
        if self.url:
            try:
                parsed = urlparse(self.url)
                domain = parsed.netloc
                if domain.startswith('www.'):
                    domain = domain[4:]
                return domain
            except Exception:
                pass

        # Fallback: Infer from path
        path_str = str(self.file_path.as_posix()).lower()
        if 'cursor' in path_str:
            return 'cursor.com'

        return None

    def extract_subsections(self) -> list[dict]:
        """Extract subsections (h2 and h3 headings) with metadata."""
        subsections = []
        heading_pattern = re.compile(r'^(#{2,3})\s+(.+)$', re.MULTILINE)
        matches = list(heading_pattern.finditer(self.body))

        if not matches:
            return []

        stop_words = self._get_stop_words()

        for i, match in enumerate(matches):
            level = len(match.group(1))
            heading_text = match.group(2).strip()

            if level not in [2, 3]:
                continue

            anchor = '#' + re.sub(r'[^\w\s-]', '', heading_text.lower()).strip().replace(' ', '-')

            heading_words = re.findall(r'\b[a-z]{3,}\b', heading_text.lower())
            heading_keywords = [w for w in heading_words if w not in stop_words and len(w) >= 3]

            subsections.append({
                'heading': heading_text,
                'level': level,
                'anchor': anchor,
                'keywords': heading_keywords[:5]
            })

        return subsections

    def extract_all(self) -> dict:
        """Extract all metadata"""
        metadata = {
            'title': self.extract_title(),
            'description': self.extract_description(),
            'keywords': self.extract_keywords(),
            'tags': self.extract_tags(),
            'category': self.extract_category(),
            'domain': self.extract_domain(),
            'subsections': self.extract_subsections(),
        }

        # Remove None values
        return {k: v for k, v in metadata.items() if v is not None and v != []}


def main() -> None:
    """Main entry point"""
    parser = argparse.ArgumentParser(
        description='Extract metadata from markdown file',
        formatter_class=argparse.RawDescriptionHelpFormatter
    )

    parser.add_argument('file_path', help='Path to markdown file')
    parser.add_argument('--url', help='Source URL (for domain extraction)')
    parser.add_argument('--json', action='store_true', help='Output as JSON')

    args = parser.parse_args()

    logger.start({
        'file_path': str(args.file_path),
        'url': args.url,
        'json': args.json
    })

    exit_code = 0
    try:
        file_path = Path(args.file_path)
        if not file_path.exists():
            print(f"File not found: {file_path}")
            exit_code = 1
            raise SystemExit(1)

        with logger.time_operation('extract_metadata'):
            extractor = MetadataExtractor(file_path, args.url)
            metadata = extractor.extract_all()

        if args.json:
            import json
            print(json.dumps(metadata, indent=2))
        else:
            print("Extracted Metadata:")
            for key, value in metadata.items():
                if isinstance(value, list):
                    print(f"  {key}: {', '.join(str(v) for v in value)}")
                else:
                    print(f"  {key}: {value}")

        logger.end(exit_code=exit_code, summary={'fields_extracted': len(metadata)})

    except SystemExit:
        raise
    except Exception as e:
        logger.log_error("Fatal error in extract_metadata", error=e)
        exit_code = 1
        logger.end(exit_code=exit_code)
        sys.exit(exit_code)


if __name__ == '__main__':
    main()
