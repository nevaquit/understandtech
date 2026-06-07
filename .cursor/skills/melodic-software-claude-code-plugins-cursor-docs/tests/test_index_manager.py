#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Tests for index_manager.py - Index operations and management."""

import sys
from pathlib import Path
import tempfile
import shutil

# Add scripts directory to path
scripts_dir = Path(__file__).resolve().parents[1] / "scripts"
sys.path.insert(0, str(scripts_dir))

import pytest
import yaml


class TestIndexManagerOperations:
    """Tests for IndexManager class operations."""

    @pytest.fixture
    def temp_canonical_dir(self):
        """Create a temporary canonical directory with test data."""
        temp_dir = tempfile.mkdtemp()
        canonical_dir = Path(temp_dir)

        # Create index.yaml with test data (flat structure, no documents wrapper)
        index_data = {
            "test-doc-1": {
                "title": "Test Document 1",
                "path": "test/doc1.md",
                "hash": "sha256:abc123",
                "url": "https://example.com/doc1.md",
                "last_fetched": "2025-01-01",
                "keywords": ["test", "document"],
                "tags": ["test"],
                "description": "A test document",
            },
            "test-doc-2": {
                "title": "Test Document 2",
                "path": "test/doc2.md",
                "hash": "sha256:def456",
                "url": "https://example.com/doc2.md",
                "last_fetched": "2025-01-01",
                "keywords": ["another", "test"],
                "tags": ["test", "example"],
                "description": "Another test document",
            },
        }

        index_path = canonical_dir / "index.yaml"
        with open(index_path, "w", encoding="utf-8") as f:
            yaml.dump(index_data, f, default_flow_style=False)

        # Create test markdown files
        test_dir = canonical_dir / "test"
        test_dir.mkdir(parents=True)

        (test_dir / "doc1.md").write_text(
            """---
source_url: https://example.com/doc1.md
content_hash: sha256:abc123
---

# Test Document 1

Test content.
""",
            encoding="utf-8",
        )

        (test_dir / "doc2.md").write_text(
            """---
source_url: https://example.com/doc2.md
content_hash: sha256:def456
---

# Test Document 2

More test content.
""",
            encoding="utf-8",
        )

        yield canonical_dir

        # Cleanup
        shutil.rmtree(temp_dir)

    def test_load_all(self, temp_canonical_dir):
        """Test loading index from YAML file."""
        from management.index_manager import IndexManager

        manager = IndexManager(temp_canonical_dir)
        index = manager.load_all()

        assert index is not None
        assert "test-doc-1" in index
        assert "test-doc-2" in index
        assert index["test-doc-1"]["title"] == "Test Document 1"

    def test_get_entry(self, temp_canonical_dir):
        """Test getting a specific entry by doc_id."""
        from management.index_manager import IndexManager

        manager = IndexManager(temp_canonical_dir)
        entry = manager.get_entry("test-doc-1")

        assert entry is not None
        assert entry["title"] == "Test Document 1"
        assert entry["path"] == "test/doc1.md"

    def test_get_entry_not_found(self, temp_canonical_dir):
        """Test getting a non-existent entry."""
        from management.index_manager import IndexManager

        manager = IndexManager(temp_canonical_dir)
        entry = manager.get_entry("nonexistent-doc")

        assert entry is None

    def test_get_entry_count(self, temp_canonical_dir):
        """Test counting index entries."""
        from management.index_manager import IndexManager

        manager = IndexManager(temp_canonical_dir)
        count = manager.get_entry_count()

        assert count == 2

    def test_list_entries(self, temp_canonical_dir):
        """Test listing all entries."""
        from management.index_manager import IndexManager

        manager = IndexManager(temp_canonical_dir)
        entries = list(manager.list_entries())

        assert len(entries) == 2
        doc_ids = [e[0] for e in entries]
        assert "test-doc-1" in doc_ids
        assert "test-doc-2" in doc_ids

    def test_update_entry(self, temp_canonical_dir):
        """Test updating an existing entry."""
        from management.index_manager import IndexManager

        manager = IndexManager(temp_canonical_dir)

        # update_entry replaces the entire entry
        full_entry = {
            "title": "Updated Title",
            "path": "test/doc1.md",
            "hash": "sha256:updated",
            "url": "https://example.com/doc1.md",
            "last_fetched": "2025-01-02",
            "keywords": ["updated", "test"],
            "tags": ["test"],
            "description": "Updated description",
        }

        success = manager.update_entry("test-doc-1", full_entry)
        assert success

        # Verify update
        entry = manager.get_entry("test-doc-1")
        assert entry["title"] == "Updated Title"
        assert "updated" in entry["keywords"]

    def test_remove_entry(self, temp_canonical_dir):
        """Test removing an entry."""
        from management.index_manager import IndexManager

        manager = IndexManager(temp_canonical_dir)

        # Verify entry exists
        assert manager.get_entry("test-doc-1") is not None

        success = manager.remove_entry("test-doc-1")
        assert success

        # Verify removal
        assert manager.get_entry("test-doc-1") is None


class TestIndexValidation:
    """Tests for index validation operations."""

    @pytest.fixture
    def temp_canonical_dir(self):
        """Create a temporary canonical directory."""
        temp_dir = tempfile.mkdtemp()
        canonical_dir = Path(temp_dir)

        # Create minimal index (flat structure)
        index_data = {
            "valid-doc": {
                "title": "Valid Doc",
                "path": "valid.md",
                "hash": "sha256:valid123",
                "url": "https://example.com/valid.md",
                "last_fetched": "2025-01-01",
            },
            "missing-file-doc": {
                "title": "Missing File",
                "path": "missing.md",
                "hash": "sha256:missing",
                "url": "https://example.com/missing.md",
                "last_fetched": "2025-01-01",
            },
        }

        index_path = canonical_dir / "index.yaml"
        with open(index_path, "w", encoding="utf-8") as f:
            yaml.dump(index_data, f)

        # Create only the valid file
        (canonical_dir / "valid.md").write_text("# Valid\nContent", encoding="utf-8")

        yield canonical_dir

        shutil.rmtree(temp_dir)

    def test_verify_index_detects_missing_files(self, temp_canonical_dir):
        """Test that we can detect missing files by checking paths."""
        from management.index_manager import IndexManager

        manager = IndexManager(temp_canonical_dir)

        # IndexManager doesn't have verify_index, but we can manually check
        missing_files = []
        for doc_id, metadata in manager.list_entries():
            path = metadata.get("path")
            if path:
                full_path = temp_canonical_dir / path
                if not full_path.exists():
                    missing_files.append(doc_id)

        assert len(missing_files) == 1
        assert "missing-file-doc" in missing_files


class TestEmptyIndex:
    """Tests for handling empty or new indexes."""

    @pytest.fixture
    def empty_canonical_dir(self):
        """Create a temporary canonical directory with empty index."""
        temp_dir = tempfile.mkdtemp()
        canonical_dir = Path(temp_dir)

        index_data = {}
        index_path = canonical_dir / "index.yaml"
        with open(index_path, "w", encoding="utf-8") as f:
            yaml.dump(index_data, f)

        yield canonical_dir

        shutil.rmtree(temp_dir)

    def test_load_empty_index(self, empty_canonical_dir):
        """Test loading an empty index."""
        from management.index_manager import IndexManager

        manager = IndexManager(empty_canonical_dir)
        index = manager.load_all()

        assert index is not None
        assert len(index) == 0

    def test_count_empty_index(self, empty_canonical_dir):
        """Test counting entries in empty index."""
        from management.index_manager import IndexManager

        manager = IndexManager(empty_canonical_dir)
        count = manager.get_entry_count()

        assert count == 0


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
