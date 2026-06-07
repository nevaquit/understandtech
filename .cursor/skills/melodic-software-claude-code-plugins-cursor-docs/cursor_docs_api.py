#!/usr/bin/env python3
"""
Public API for cursor-docs skill.

Adapted for Cursor Documentation from openai-ecosystem codex-cli-docs skill.

Provides a clean, stable API for external tools to interact with the
Cursor documentation management system. This API abstracts away
implementation details and provides simple functions for common operations.

Usage:
    from cursor_docs_api import find_document, resolve_doc_id, get_docs_by_tag

    # Find documents by query
    docs = find_document("agent mode configuration")

    # Resolve doc_id to metadata
    doc = resolve_doc_id("cursor-getting-started")

    # Get docs by tag
    docs = get_docs_by_tag("cli")
"""

import sys
from pathlib import Path
from typing import Any

# Add scripts directory to path
_scripts_dir = Path(__file__).parent / 'scripts'
if str(_scripts_dir) not in sys.path:
    sys.path.insert(0, str(_scripts_dir))

from scripts.management.index_manager import IndexManager
from scripts.core.doc_resolver import DocResolver
from scripts.utils.path_config import get_base_dir


class CursorDocsAPI:
    """
    Public API for cursor-docs skill.

    Provides high-level functions for Cursor documentation operations.
    All functions are designed to be simple, stable, and easy to use.
    """

    def __init__(self, base_dir: Path | None = None):
        """
        Initialize API instance.

        Args:
            base_dir: Base directory for references. If None, uses config default.
        """
        if base_dir:
            self.base_dir = Path(base_dir)
        else:
            self.base_dir = get_base_dir()
        self.index_manager = IndexManager(self.base_dir)
        self.doc_resolver = DocResolver(self.base_dir)

    def find_document(self, query: str, limit: int = 10) -> list[dict[str, Any]]:
        """
        Find documents by natural language query.

        Args:
            query: Natural language search query (e.g., "how to use agent mode")
            limit: Maximum number of results to return (default: 10)

        Returns:
            List of document dictionaries with keys:
            - doc_id: Document identifier
            - url: Source URL
            - title: Document title
            - description: Document description
            - keywords: List of keywords
            - tags: List of tags
            - relevance_score: Relevance score (0-1)

        Example:
            >>> api = CursorDocsAPI()
            >>> docs = api.find_document("agent mode configuration")
            >>> print(docs[0]['title'])
        """
        try:
            results = self.doc_resolver.search_by_natural_language(query, limit=limit)
            return [
                {
                    'doc_id': doc_id,
                    'url': metadata.get('url'),
                    'title': metadata.get('title'),
                    'description': metadata.get('description'),
                    'keywords': metadata.get('keywords', []),
                    'tags': metadata.get('tags', []),
                    'relevance_score': 1.0,
                }
                for doc_id, metadata in results
            ]
        except Exception:
            return []

    def resolve_doc_id(self, doc_id: str) -> dict[str, Any] | None:
        """
        Resolve doc_id to file path and metadata.

        Args:
            doc_id: Document identifier (e.g., "cursor-getting-started")

        Returns:
            Dictionary with keys:
            - doc_id: Document identifier
            - url: Source URL
            - title: Document title
            - description: Document description
            - metadata: Full metadata dictionary

        Returns None if doc_id not found.

        Example:
            >>> api = CursorDocsAPI()
            >>> doc = api.resolve_doc_id("cursor-getting-started")
            >>> print(doc['title'])
        """
        try:
            entry = self.index_manager.get_entry(doc_id)
            if entry:
                return {
                    'doc_id': doc_id,
                    'url': entry.get('url'),
                    'title': entry.get('title'),
                    'description': entry.get('description'),
                    'metadata': entry,
                }

            path = self.doc_resolver.resolve_doc_id(doc_id)
            if path:
                return {
                    'doc_id': doc_id,
                    'url': None,
                    'title': None,
                    'description': None,
                    'metadata': {},
                }
        except Exception:
            pass
        return None

    def get_docs_by_tag(self, tag: str, limit: int = 100) -> list[dict[str, Any]]:
        """
        Get all documents with a specific tag.

        Args:
            tag: Tag to filter by (e.g., "cli", "agent", "tab", "mcp")
            limit: Maximum number of results to return (default: 100)

        Returns:
            List of document dictionaries with doc_id, url, title, description, tags

        Example:
            >>> api = CursorDocsAPI()
            >>> docs = api.get_docs_by_tag("cli")
            >>> print(len(docs))
        """
        try:
            results = self.doc_resolver.search_by_tag(tag, limit=limit)
            return [
                {
                    'doc_id': doc_id,
                    'url': metadata.get('url'),
                    'title': metadata.get('title'),
                    'description': metadata.get('description'),
                    'tags': metadata.get('tags', []),
                }
                for doc_id, metadata in results
            ]
        except Exception:
            return []

    def get_docs_by_category(self, category: str, limit: int = 100) -> list[dict[str, Any]]:
        """
        Get all documents in a specific category.

        Args:
            category: Category to filter by (e.g., "get-started", "core", "context")
            limit: Maximum number of results to return (default: 100)

        Returns:
            List of document dictionaries with doc_id, url, title, description, category

        Example:
            >>> api = CursorDocsAPI()
            >>> docs = api.get_docs_by_category("core")
            >>> print(len(docs))
        """
        try:
            results = self.doc_resolver.search_by_category(category, limit=limit)
            return [
                {
                    'doc_id': doc_id,
                    'url': metadata.get('url'),
                    'title': metadata.get('title'),
                    'description': metadata.get('description'),
                    'category': metadata.get('category'),
                }
                for doc_id, metadata in results
            ]
        except Exception:
            return []

    def search_by_keywords(self, keywords: list[str], limit: int = 25) -> list[dict[str, Any]]:
        """
        Search documents by keywords.

        Args:
            keywords: List of keywords to search for
            limit: Maximum number of results to return (default: 25)

        Returns:
            List of document dictionaries with relevance scores

        Example:
            >>> api = CursorDocsAPI()
            >>> docs = api.search_by_keywords(["agent", "mcp"])
            >>> print(docs[0]['title'])
        """
        try:
            results = self.doc_resolver.search_by_keywords(keywords, limit=limit)
            return [
                {
                    'doc_id': doc_id,
                    'url': metadata.get('url'),
                    'title': metadata.get('title'),
                    'description': metadata.get('description'),
                    'keywords': metadata.get('keywords', []),
                    'relevance_score': score,
                }
                for doc_id, metadata, score in results
            ]
        except Exception:
            return []

    def get_document_section(self, doc_id: str, section_heading: str) -> dict[str, Any] | None:
        """
        Extract a specific section from a document.

        Args:
            doc_id: Document identifier
            section_heading: Heading text to extract (e.g., "Installation")

        Returns:
            Dictionary with keys:
            - doc_id: Document identifier
            - section: Section heading
            - content: Section content (markdown)

        Returns None if document or section not found.

        Example:
            >>> api = CursorDocsAPI()
            >>> section = api.get_document_section("cursor-overview", "Installation")
            >>> print(section['content'])
        """
        try:
            path = self.doc_resolver.resolve_doc_id(doc_id)
            if not path or not path.exists():
                return None

            content = path.read_text(encoding='utf-8')

            # Simple section extraction
            import re
            pattern = rf'^(#{1,3})\s+{re.escape(section_heading)}\s*$'
            match = re.search(pattern, content, re.MULTILINE | re.IGNORECASE)

            if not match:
                return None

            start = match.end()
            level = len(match.group(1))

            # Find next heading at same or higher level
            next_pattern = rf'^#{{{1},{level}}}\s+'
            next_match = re.search(next_pattern, content[start:], re.MULTILINE)

            if next_match:
                section_content = content[start:start + next_match.start()].strip()
            else:
                section_content = content[start:].strip()

            return {
                'doc_id': doc_id,
                'section': section_heading,
                'content': section_content,
            }
        except Exception:
            return None

    def refresh_index(self, check_drift: bool = False) -> dict[str, Any]:
        """
        Refresh the index from filesystem.

        Args:
            check_drift: If True, check for content drift

        Returns:
            Dictionary with refresh results

        Example:
            >>> api = CursorDocsAPI()
            >>> result = api.refresh_index()
            >>> print(result['total_entries'])
        """
        try:
            import subprocess
            scripts_dir = Path(__file__).parent / 'scripts'
            result = subprocess.run(
                [sys.executable, str(scripts_dir / 'management' / 'refresh_index.py')],
                capture_output=True,
                text=True,
            )
            return {
                'success': result.returncode == 0,
                'output': result.stdout,
                'error': result.stderr if result.returncode != 0 else None,
            }
        except Exception as e:
            return {
                'success': False,
                'output': '',
                'error': str(e),
            }


# Module-level convenience functions
_default_api: CursorDocsAPI | None = None


def _get_api() -> CursorDocsAPI:
    """Get or create default API instance."""
    global _default_api
    if _default_api is None:
        _default_api = CursorDocsAPI()
    return _default_api


def find_document(query: str, limit: int = 10) -> list[dict[str, Any]]:
    """Find documents by natural language query."""
    return _get_api().find_document(query, limit)


def resolve_doc_id(doc_id: str) -> dict[str, Any] | None:
    """Resolve doc_id to file path and metadata."""
    return _get_api().resolve_doc_id(doc_id)


def get_docs_by_tag(tag: str, limit: int = 100) -> list[dict[str, Any]]:
    """Get all documents with a specific tag."""
    return _get_api().get_docs_by_tag(tag, limit)


def get_docs_by_category(category: str, limit: int = 100) -> list[dict[str, Any]]:
    """Get all documents in a specific category."""
    return _get_api().get_docs_by_category(category, limit)


def search_by_keywords(keywords: list[str], limit: int = 25) -> list[dict[str, Any]]:
    """Search documents by keywords."""
    return _get_api().search_by_keywords(keywords, limit)


def get_document_section(doc_id: str, section_heading: str) -> dict[str, Any] | None:
    """Extract a specific section from a document."""
    return _get_api().get_document_section(doc_id, section_heading)


def refresh_index(check_drift: bool = False) -> dict[str, Any]:
    """Refresh the index from filesystem."""
    return _get_api().refresh_index(check_drift)


if __name__ == '__main__':
    # Simple CLI for testing
    import argparse

    parser = argparse.ArgumentParser(description='Cursor Docs API')
    parser.add_argument('command', choices=['find', 'resolve', 'tag', 'category', 'search', 'refresh'])
    parser.add_argument('args', nargs='*')

    args = parser.parse_args()

    api = CursorDocsAPI()

    if args.command == 'find':
        query = ' '.join(args.args) if args.args else 'getting started'
        results = api.find_document(query)
        print(f"Found {len(results)} documents:")
        for doc in results[:5]:
            print(f"  - {doc['doc_id']}: {doc['title']}")

    elif args.command == 'resolve':
        doc_id = args.args[0] if args.args else 'cursor-overview'
        result = api.resolve_doc_id(doc_id)
        if result:
            print(f"Resolved {doc_id}:")
            print(f"  Title: {result['title']}")
            print(f"  URL: {result['url']}")
        else:
            print(f"Not found: {doc_id}")

    elif args.command == 'tag':
        tag = args.args[0] if args.args else 'cli'
        results = api.get_docs_by_tag(tag)
        print(f"Found {len(results)} documents with tag '{tag}':")
        for doc in results[:5]:
            print(f"  - {doc['doc_id']}: {doc['title']}")

    elif args.command == 'category':
        category = args.args[0] if args.args else 'core'
        results = api.get_docs_by_category(category)
        print(f"Found {len(results)} documents in category '{category}':")
        for doc in results[:5]:
            print(f"  - {doc['doc_id']}: {doc['title']}")

    elif args.command == 'search':
        keywords = args.args if args.args else ['agent', 'mcp']
        results = api.search_by_keywords(keywords)
        print(f"Found {len(results)} documents for keywords {keywords}:")
        for doc in results[:5]:
            print(f"  - {doc['doc_id']}: {doc['title']} (score: {doc.get('relevance_score', 'N/A')})")

    elif args.command == 'refresh':
        result = api.refresh_index()
        if result['success']:
            print("Index refreshed successfully")
        else:
            print(f"Refresh failed: {result['error']}")
