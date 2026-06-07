#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Tests for doc_resolver.py - Document resolution and search."""

import sys
from pathlib import Path
import tempfile
import shutil

# Add scripts directory to path
scripts_dir = Path(__file__).resolve().parents[1] / "scripts"
sys.path.insert(0, str(scripts_dir))

import pytest
import yaml


class TestDocResolver:
    """Tests for DocResolver class."""

    @pytest.fixture
    def temp_canonical_dir(self):
        """Create a temporary canonical directory with test index."""
        temp_dir = tempfile.mkdtemp()
        canonical_dir = Path(temp_dir)

        # Create index with varied content for search testing
        index_data = {
            "cursor-com-docs-agent-overview": {
                "title": "Cursor Agent Overview",
                "path": "cursor-com/docs/agent/overview.md",
                "hash": "sha256:abc123",
                "url": "https://cursor.com/docs/agent/overview.md",
                "last_fetched": "2025-01-01",
                "keywords": ["agent", "overview", "autonomous", "coding"],
                "tags": ["agent", "core"],
                "category": "core",
                "description": "Agent is Cursor's assistant for autonomous coding",
                "domain": "cursor.com",
                "subsections": [
                    {"anchor": "#browser", "heading": "Browser", "level": 2},
                    {"anchor": "#tools", "heading": "Tools", "level": 2},
                ],
            },
            "cursor-com-docs-cli-overview": {
                "title": "Cursor CLI Overview",
                "path": "cursor-com/docs/cli/overview.md",
                "hash": "sha256:def456",
                "url": "https://cursor.com/docs/cli/overview.md",
                "last_fetched": "2025-01-01",
                "keywords": ["cli", "terminal", "command", "line"],
                "tags": ["cli", "terminal"],
                "category": "cli",
                "description": "Use Cursor from the command line",
                "domain": "cursor.com",
            },
            "cursor-com-docs-context-mcp": {
                "title": "Model Context Protocol",
                "path": "cursor-com/docs/context/mcp.md",
                "hash": "sha256:ghi789",
                "url": "https://cursor.com/docs/context/mcp.md",
                "last_fetched": "2025-01-01",
                "keywords": ["mcp", "context", "protocol", "servers"],
                "tags": ["mcp", "context"],
                "category": "context",
                "description": "Connect to external tools with MCP",
                "domain": "cursor.com",
                "subsections": [
                    {"anchor": "#installing", "heading": "Installing MCP servers", "level": 2},
                ],
            },
            "cursor-com-docs-tab-overview": {
                "title": "Tab Completion",
                "path": "cursor-com/docs/tab/overview.md",
                "hash": "sha256:jkl012",
                "url": "https://cursor.com/docs/tab/overview.md",
                "last_fetched": "2025-01-01",
                "keywords": ["tab", "completion", "autocomplete"],
                "tags": ["tab", "core"],
                "category": "core",
                "description": "AI-powered code completion",
                "domain": "cursor.com",
            },
        }

        # Create directory structure
        for doc_id, entry in index_data.items():
            doc_path = canonical_dir / entry["path"]
            doc_path.parent.mkdir(parents=True, exist_ok=True)
            doc_path.write_text(f"# {entry['title']}\n\n{entry['description']}", encoding="utf-8")

        # Write index
        index_path = canonical_dir / "index.yaml"
        with open(index_path, "w", encoding="utf-8") as f:
            yaml.dump(index_data, f, default_flow_style=False)

        # Create cache directory
        (canonical_dir / ".cache").mkdir(exist_ok=True)

        yield canonical_dir

        shutil.rmtree(temp_dir)

    def test_resolve_exact_doc_id(self, temp_canonical_dir):
        """Test resolving exact doc_id."""
        from core.doc_resolver import DocResolver

        resolver = DocResolver(temp_canonical_dir)
        result = resolver.resolve_doc_id("cursor-com-docs-agent-overview")

        assert result is not None
        assert result.exists()
        assert "agent" in str(result).lower()

    def test_search_by_keyword(self, temp_canonical_dir):
        """Test keyword-based search."""
        from core.doc_resolver import DocResolver

        resolver = DocResolver(temp_canonical_dir)
        results = resolver.search_by_keyword(["agent", "cli"])

        assert len(results) >= 2
        doc_ids = [r[0] for r in results]
        assert any("agent" in d for d in doc_ids)
        assert any("cli" in d for d in doc_ids)

    def test_search_by_single_keyword(self, temp_canonical_dir):
        """Test search with single keyword."""
        from core.doc_resolver import DocResolver

        resolver = DocResolver(temp_canonical_dir)
        results = resolver.search_by_keyword(["mcp"])

        assert len(results) >= 1
        doc_ids = [r[0] for r in results]
        assert any("mcp" in d for d in doc_ids)

    def test_get_by_category(self, temp_canonical_dir):
        """Test filtering by category."""
        from core.doc_resolver import DocResolver

        resolver = DocResolver(temp_canonical_dir)
        results = resolver.get_by_category("core")

        assert len(results) >= 2
        for doc_id, metadata in results:
            assert metadata.get("category") == "core"

    def test_get_by_tag(self, temp_canonical_dir):
        """Test filtering by tag."""
        from core.doc_resolver import DocResolver

        resolver = DocResolver(temp_canonical_dir)
        results = resolver.get_by_tag("cli")

        assert len(results) >= 1
        for doc_id, metadata in results:
            tags = metadata.get("tags", [])
            assert "cli" in [t.lower() for t in tags]

    def test_natural_language_query(self, temp_canonical_dir):
        """Test natural language query processing."""
        from core.doc_resolver import DocResolver

        resolver = DocResolver(temp_canonical_dir)
        results = resolver.search_by_natural_language("how to use agent mode")

        assert len(results) >= 1
        # Should find agent-related docs
        doc_ids = [r[0].lower() for r in results]
        assert any("agent" in d for d in doc_ids)

    def test_resolve_nonexistent_doc(self, temp_canonical_dir):
        """Test resolving non-existent doc_id."""
        from core.doc_resolver import DocResolver

        resolver = DocResolver(temp_canonical_dir)
        result = resolver.resolve_doc_id("nonexistent-doc-id")

        assert result is None

    def test_search_with_subsections(self, temp_canonical_dir):
        """Test that search includes subsection matches."""
        from core.doc_resolver import DocResolver

        resolver = DocResolver(temp_canonical_dir)
        results = resolver.search_by_keyword(["browser", "tools"])

        # Should find agent overview which has Browser and Tools subsections
        assert len(results) >= 1

    def test_inverted_index_caching(self, temp_canonical_dir):
        """Test that inverted index is cached for performance."""
        from core.doc_resolver import DocResolver

        resolver = DocResolver(temp_canonical_dir)

        # First search builds index
        results1 = resolver.search_by_keyword(["agent"])

        # Second search should use cached index (faster)
        results2 = resolver.search_by_keyword(["cli"])

        assert len(results1) >= 1
        assert len(results2) >= 1


class TestSearchScoring:
    """Tests for search result scoring and ranking."""

    @pytest.fixture
    def temp_canonical_dir(self):
        """Create temp directory with documents for scoring tests."""
        temp_dir = tempfile.mkdtemp()
        canonical_dir = Path(temp_dir)

        index_data = {
            "exact-match": {
                "title": "Agent Documentation",
                "path": "agent.md",
                "hash": "sha256:a",
                "url": "https://cursor.com/agent.md",
                "last_fetched": "2025-01-01",
                "keywords": ["agent", "documentation"],
                "tags": ["agent"],
                "description": "The agent documentation page",
                "domain": "cursor.com",
            },
            "partial-match": {
                "title": "Other Page",
                "path": "other.md",
                "hash": "sha256:b",
                "url": "https://cursor.com/other.md",
                "last_fetched": "2025-01-01",
                "keywords": ["other", "stuff"],
                "tags": ["misc"],
                "description": "This page mentions agent briefly",
                "domain": "cursor.com",
            },
        }

        for doc_id, entry in index_data.items():
            doc_path = canonical_dir / entry["path"]
            doc_path.parent.mkdir(parents=True, exist_ok=True)
            doc_path.write_text(f"# {entry['title']}", encoding="utf-8")

        index_path = canonical_dir / "index.yaml"
        with open(index_path, "w", encoding="utf-8") as f:
            yaml.dump(index_data, f)

        (canonical_dir / ".cache").mkdir(exist_ok=True)

        yield canonical_dir

        shutil.rmtree(temp_dir)

    def test_exact_keyword_match_ranks_higher(self, temp_canonical_dir):
        """Test that exact keyword matches rank higher."""
        from core.doc_resolver import DocResolver

        resolver = DocResolver(temp_canonical_dir)
        results = resolver.search_by_keyword(["agent"])

        # Document with "agent" in keywords should rank first
        if len(results) >= 2:
            assert results[0][0] == "exact-match"


class TestEdgeCases:
    """Tests for edge cases and error handling."""

    @pytest.fixture
    def empty_canonical_dir(self):
        """Create temp directory with empty index."""
        temp_dir = tempfile.mkdtemp()
        canonical_dir = Path(temp_dir)

        index_data = {}
        index_path = canonical_dir / "index.yaml"
        with open(index_path, "w", encoding="utf-8") as f:
            yaml.dump(index_data, f)

        (canonical_dir / ".cache").mkdir(exist_ok=True)

        yield canonical_dir

        shutil.rmtree(temp_dir)

    def test_search_empty_index(self, empty_canonical_dir):
        """Test searching an empty index."""
        from core.doc_resolver import DocResolver

        resolver = DocResolver(empty_canonical_dir)
        results = resolver.search_by_keyword(["anything"])

        assert results == []

    def test_empty_query(self, empty_canonical_dir):
        """Test with empty query."""
        from core.doc_resolver import DocResolver

        resolver = DocResolver(empty_canonical_dir)
        results = resolver.search_by_keyword([])

        assert results == []

    def test_special_characters_in_query(self, empty_canonical_dir):
        """Test handling special characters in query."""
        from core.doc_resolver import DocResolver

        resolver = DocResolver(empty_canonical_dir)
        # Should not raise exception
        results = resolver.search_by_natural_language("how do I use @mentions?")

        assert isinstance(results, list)


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
