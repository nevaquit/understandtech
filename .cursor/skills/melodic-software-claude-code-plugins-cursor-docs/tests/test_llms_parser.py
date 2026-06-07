#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Tests for llms_parser.py - llms.txt format parsing."""

import sys
from pathlib import Path

# Add scripts directory to path
scripts_dir = Path(__file__).resolve().parents[1] / "scripts"
sys.path.insert(0, str(scripts_dir))

import pytest
from core.llms_parser import (
    LlmsParser,
    LlmsFullParser,
    LlmsEntry,
    LlmsFullPage,
    parse_llms_txt,
    url_to_local_path,
)


class TestLlmsParser:
    """Tests for standard llms.txt parsing."""

    def test_parse_standard_format(self):
        """Test parsing standard markdown link format."""
        content = """# Documentation

## Getting Started
- [Introduction](https://example.com/docs/intro.md): Welcome guide
- [Quickstart](https://example.com/docs/quickstart.md): Get started fast

## Features
- [Feature A](https://example.com/docs/feature-a.md)
"""
        parser = LlmsParser()
        entries = parser.parse_to_list(content)

        assert len(entries) == 3  # 3 links in the content
        assert entries[0].title == "Introduction"
        assert entries[0].url == "https://example.com/docs/intro.md"
        assert entries[0].description == "Welcome guide"
        assert entries[0].section == "Getting Started"

        assert entries[2].title == "Feature A"
        assert entries[2].section == "Features"
        assert entries[2].description is None

    def test_parse_plain_url_format(self):
        """Test parsing cursor.com plain URL format."""
        content = """# Cursor Docs

## Core
- https://cursor.com/docs/agent/overview.md
- https://cursor.com/docs/tab/overview.md

## CLI
- https://cursor.com/docs/cli/installation.md
"""
        parser = LlmsParser()
        entries = parser.parse_to_list(content)

        assert len(entries) == 3
        assert entries[0].url == "https://cursor.com/docs/agent/overview.md"
        assert entries[0].title == "Agent Overview"  # Derived from URL
        assert entries[0].section == "Core"

        assert entries[2].title == "Cli Installation"
        assert entries[2].section == "CLI"

    def test_parse_header_with_link(self):
        """Test parsing header with embedded link."""
        content = """# [Architecture Overview](https://example.com/docs/arch.md)

Some content here.

# [API Reference](https://example.com/docs/api.md)
"""
        parser = LlmsParser()
        entries = parser.parse_to_list(content)

        assert len(entries) == 2
        assert entries[0].title == "Architecture Overview"
        assert entries[1].title == "API Reference"

    def test_parse_relative_urls(self):
        """Test resolving relative URLs with base_url."""
        content = """# Docs
- [Tab](/docs/tab.md)
- [Agent](/docs/agent.md)
"""
        parser = LlmsParser(base_url="https://cursor.com")
        entries = parser.parse_to_list(content)

        assert len(entries) == 2
        assert entries[0].url == "https://cursor.com/docs/tab.md"
        assert entries[1].url == "https://cursor.com/docs/agent.md"

    def test_deduplication(self):
        """Test duplicate URL removal."""
        content = """# Docs
- [Tab](https://cursor.com/docs/tab.md)
- [Tab Overview](https://cursor.com/docs/tab.md)
- [Agent](https://cursor.com/docs/agent.md)
"""
        parser = LlmsParser()
        entries = parser.parse_to_list(content)

        assert len(entries) == 2  # Deduplicated

    def test_extract_urls(self):
        """Test URL extraction convenience method."""
        content = """# Docs
- [A](https://example.com/a.md)
- [B](https://example.com/b.md)
"""
        parser = LlmsParser()
        urls = parser.extract_urls(content)

        assert urls == [
            "https://example.com/a.md",
            "https://example.com/b.md",
        ]

    def test_extract_urls_by_section(self):
        """Test URL extraction grouped by section."""
        content = """# Docs

## Section A
- [A1](https://example.com/a1.md)

## Section B
- [B1](https://example.com/b1.md)
- [B2](https://example.com/b2.md)
"""
        parser = LlmsParser()
        sections = parser.extract_urls_by_section(content)

        assert "Section A" in sections
        assert "Section B" in sections
        assert len(sections["Section A"]) == 1
        assert len(sections["Section B"]) == 2

    def test_derive_title_from_url(self):
        """Test title derivation from URL path."""
        parser = LlmsParser()

        assert parser._derive_title_from_url(
            "https://cursor.com/docs/tab/overview.md"
        ) == "Tab Overview"
        assert parser._derive_title_from_url(
            "https://cursor.com/docs/get-started/quickstart.md"
        ) == "Get Started Quickstart"
        assert parser._derive_title_from_url(
            "https://example.com/some_feature.md"
        ) == "Some Feature"


class TestLlmsFullParser:
    """Tests for llms-full.txt content parsing."""

    def test_parse_full_format(self):
        """Test parsing llms-full.txt format."""
        content = """# Introduction
Source: https://example.com/docs/intro.md

Welcome to the documentation.

This is the intro page.

# API Reference
Source: https://example.com/docs/api.md

API documentation here.

## Endpoints
- GET /users
- POST /users
"""
        parser = LlmsFullParser()
        pages = parser.parse_to_list(content)

        assert len(pages) == 2
        assert pages[0].title == "Introduction"
        assert pages[0].source_url == "https://example.com/docs/intro.md"
        assert "Welcome to the documentation" in pages[0].content

        assert pages[1].title == "API Reference"
        assert "API documentation here" in pages[1].content

    def test_count_pages(self):
        """Test page counting."""
        content = """# Page 1
Source: https://example.com/1.md

Content 1

# Page 2
Source: https://example.com/2.md

Content 2

# Page 3
Source: https://example.com/3.md

Content 3
"""
        parser = LlmsFullParser()
        count = parser.count_pages(content)

        assert count == 3

    def test_get_page_by_url(self):
        """Test finding specific page by URL."""
        content = """# First
Source: https://example.com/first.md

First content

# Second
Source: https://example.com/second.md

Second content
"""
        parser = LlmsFullParser()
        page = parser.get_page_by_url(content, "https://example.com/second.md")

        assert page is not None
        assert page.title == "Second"
        assert "Second content" in page.content

        # Test not found
        not_found = parser.get_page_by_url(content, "https://example.com/missing.md")
        assert not_found is None


class TestConvenienceFunctions:
    """Tests for module-level convenience functions."""

    def test_parse_llms_txt(self):
        """Test parse_llms_txt convenience function."""
        content = """# Docs
- [A](https://example.com/a.md)
- [B](https://example.com/b.md)
"""
        urls = parse_llms_txt(content)

        assert len(urls) == 2
        assert "https://example.com/a.md" in urls

    def test_parse_llms_txt_with_base_url(self):
        """Test parse_llms_txt with base_url for relative paths."""
        content = """# Docs
- [Tab](/docs/tab.md)
"""
        urls = parse_llms_txt(content, base_url="https://cursor.com")

        assert urls == ["https://cursor.com/docs/tab.md"]

    def test_url_to_local_path(self):
        """Test URL to local path conversion."""
        from pathlib import Path

        path = url_to_local_path(
            "https://cursor.com/docs/agent/overview.md",
            Path("/canonical")
        )

        assert str(path).replace("\\", "/") == "/canonical/docs/agent/overview.md"

    def test_url_to_local_path_adds_md_extension(self):
        """Test that .md extension is added if missing."""
        path = url_to_local_path(
            "https://cursor.com/docs/agent/overview",
            "/canonical"
        )

        assert str(path).endswith(".md")


class TestEdgeCases:
    """Tests for edge cases and error handling."""

    def test_empty_content(self):
        """Test parsing empty content."""
        parser = LlmsParser()
        entries = parser.parse_to_list("")

        assert entries == []

    def test_content_without_links(self):
        """Test parsing content with no links."""
        content = """# Documentation

This is just text without any links.

## Section
More text.
"""
        parser = LlmsParser()
        entries = parser.parse_to_list(content)

        assert entries == []

    def test_malformed_links(self):
        """Test handling of malformed links."""
        content = """# Docs
- [Missing URL]()
- [](https://example.com/empty-title.md)
- Not a link at all
"""
        parser = LlmsParser()
        entries = parser.parse_to_list(content)

        # Should gracefully handle and extract valid links only
        assert len(entries) == 0  # All are malformed

    def test_inline_links_extraction(self):
        """Test extraction of inline markdown links."""
        content = """# Documentation

Check out [Tab completion](/docs/tab.md) and [Agent mode](/docs/agent.md).
"""
        parser = LlmsParser(base_url="https://cursor.com")
        entries = parser.parse_to_list(content)

        assert len(entries) == 2
        assert entries[0].title == "Tab completion"
        assert entries[0].url == "https://cursor.com/docs/tab.md"


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
