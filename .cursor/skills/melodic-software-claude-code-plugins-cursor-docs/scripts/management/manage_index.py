#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
manage_index.py - CLI tool for managing index.yaml

Adapted for Cursor Documentation from openai-ecosystem codex-cli-docs skill.

Standalone CLI tool for common index operations that can be used by
Claude Code or manually.

Usage:
    python manage_index.py get <doc_id>
    python manage_index.py update <doc_id> <metadata_json>
    python manage_index.py remove <doc_id>
    python manage_index.py list [--filter <field>=<value>]
    python manage_index.py count
    python manage_index.py verify
    python manage_index.py extract-keywords

Dependencies:
    pip install pyyaml
"""

import sys
from pathlib import Path
sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

import argparse
import json

from utils.cli_utils import add_common_index_args
from utils.metadata_utils import normalize_keywords, normalize_tags
from utils.script_utils import (
    configure_utf8_output,
    resolve_base_dir,
    EXIT_SUCCESS,
    EXIT_INDEX_ERROR,
    EXIT_BAD_ARGS,
    EXIT_NO_RESULTS,
)
from utils.logging_utils import get_or_setup_logger

# Configure UTF-8 output for Windows console compatibility
configure_utf8_output()

# Script logger (structured, with performance tracking)
logger = get_or_setup_logger(__file__, log_category="index")

# Import index manager
try:
    from management.index_manager import IndexManager
except ImportError:
    print("Error: Could not import index_manager")
    print("Make sure index_manager.py is available (management/index_manager.py).")
    sys.exit(EXIT_INDEX_ERROR)

# Import metadata extractor (optional)
try:
    from management.extract_metadata import MetadataExtractor
except ImportError:
    MetadataExtractor = None


def cmd_get(manager: IndexManager, doc_id: str) -> None:
    """Get entry by ID"""
    entry = manager.get_entry(doc_id)
    if entry:
        print(f"Entry: {doc_id}")
        for key, value in entry.items():
            if isinstance(value, list):
                if value:
                    print(f"   {key}: {', '.join(str(v) for v in value)}")
                else:
                    print(f"   {key}: []")
            elif isinstance(value, dict):
                print(f"   {key}:")
                for k, v in value.items():
                    print(f"     {k}: {v}")
            else:
                print(f"   {key}: {value}")
    else:
        print(f"Entry not found: {doc_id}")
        sys.exit(EXIT_NO_RESULTS)


def cmd_update(manager: IndexManager, doc_id: str, metadata_json: str) -> None:
    """Update entry"""
    try:
        metadata = json.loads(metadata_json)
    except json.JSONDecodeError as e:
        print(f"Invalid JSON: {e}")
        sys.exit(EXIT_BAD_ARGS)

    if manager.update_entry(doc_id, metadata):
        print(f"Updated entry: {doc_id}")
    else:
        print(f"Failed to update entry: {doc_id}")
        sys.exit(EXIT_INDEX_ERROR)


def cmd_remove(manager: IndexManager, doc_id: str) -> None:
    """Remove entry"""
    if manager.remove_entry(doc_id):
        print(f"Removed entry: {doc_id}")
    else:
        print(f"Entry not found or failed to remove: {doc_id}")
        sys.exit(1)


def cmd_list(manager: IndexManager, filters: dict[str, str], limit: int | None = None) -> None:
    """List entries with optional filtering"""
    if filters:
        entries = manager.search_entries(**filters)
        print(f"Found {len(entries)} matching entries:")
    else:
        entries = list(manager.list_entries())
        print(f"All entries ({len(entries)}):")

    try:
        count = 0
        for doc_id, metadata in entries:
            if limit is not None and count >= limit:
                break
            print(f"\n{doc_id}:")
            for key, value in metadata.items():
                print(f"  {key}: {value}")
            count += 1
    except BrokenPipeError:
        try:
            sys.stderr.close()
        except Exception:
            pass
        sys.exit(0)


def cmd_count(manager: IndexManager) -> None:
    """Get total entry count"""
    count = manager.get_entry_count()
    print(f"Total entries: {count}")


def cmd_verify(manager: IndexManager, base_dir: Path) -> None:
    """Verify index integrity"""
    print("Verifying index integrity...")

    if not manager.index_path.exists():
        print(f"Index file does not exist: {manager.index_path}")
        sys.exit(1)

    issues = []
    entry_count = 0

    for doc_id, metadata in manager.list_entries():
        entry_count += 1

        if not isinstance(metadata, dict):
            issues.append(f"Invalid metadata for {doc_id}: not a dict")
            continue

        required = ['path', 'hash', 'last_fetched']
        missing = [f for f in required if f not in metadata]
        if missing:
            issues.append(f"Missing fields {missing} for {doc_id}")
            continue

        file_path = base_dir / metadata['path']
        if not file_path.exists():
            issues.append(f"File not found: {metadata['path']} (doc_id: {doc_id})")

    print(f"\nIndex Verification: {manager.index_path}")
    print(f"   Entries: {entry_count}")

    if issues:
        print(f"   Issues found: {len(issues)}")
        for issue in issues[:20]:
            print(f"      - {issue}")
        if len(issues) > 20:
            print(f"      ... and {len(issues) - 20} more")
        sys.exit(1)
    else:
        print(f"   All checks passed")
        print(f"   All files exist")


def cmd_add_keywords(manager: IndexManager, doc_id: str, keywords: list[str]) -> None:
    """Add/update keywords for an entry"""
    entry = manager.get_entry(doc_id)
    if not entry:
        print(f"Entry not found: {doc_id}")
        sys.exit(1)

    existing_keywords = normalize_keywords(entry.get('keywords', []))
    new_keywords = normalize_keywords(keywords)

    all_keywords = set(existing_keywords)
    all_keywords.update(new_keywords)

    entry['keywords'] = sorted(all_keywords)

    if manager.update_entry(doc_id, entry):
        print(f"Updated keywords for {doc_id}: {', '.join(sorted(all_keywords))}")
    else:
        print(f"Failed to update keywords for {doc_id}")
        sys.exit(1)


def cmd_add_tags(manager: IndexManager, doc_id: str, tags: list[str]) -> None:
    """Add/update tags for an entry"""
    entry = manager.get_entry(doc_id)
    if not entry:
        print(f"Entry not found: {doc_id}")
        sys.exit(1)

    existing_tags = normalize_tags(entry.get('tags', []))
    new_tags = normalize_tags(tags)

    all_tags = set(existing_tags)
    all_tags.update(new_tags)

    entry['tags'] = sorted(all_tags)

    if manager.update_entry(doc_id, entry):
        print(f"Updated tags for {doc_id}: {', '.join(sorted(all_tags))}")
    else:
        print(f"Failed to update tags for {doc_id}")
        sys.exit(1)


def cmd_extract_keywords(manager: IndexManager, base_dir: Path, skip_existing: bool = True, verbose: bool = False) -> None:
    """Extract keywords from all documents"""
    if not MetadataExtractor:
        print("Error: extract_metadata module not available")
        sys.exit(1)

    print("Extracting metadata from all documents...")
    print(f"   (Skipping files that already have metadata)" if skip_existing else "")

    total_count = manager.get_entry_count()
    processed = 0
    skipped = 0
    updates = {}
    error_count = 0

    import time
    start_time = time.time()
    progress_interval = 50

    for doc_id, metadata in manager.list_entries():
        processed += 1
        path_str = metadata.get('path')
        if not path_str:
            continue

        file_path = base_dir / path_str
        if not file_path.exists():
            if verbose:
                print(f"  [{processed}/{total_count}] File not found: {doc_id}")
            continue

        if skip_existing:
            has_all_metadata = all(key in metadata for key in ['title', 'description', 'keywords', 'tags', 'category', 'domain'])
            if has_all_metadata:
                skipped += 1
                if not verbose and processed % progress_interval == 0:
                    print(f"  [{processed}/{total_count}] Progress: {skipped} skipped, {len(updates)} queued...")
                continue

        try:
            url = metadata.get('url', '')
            extractor = MetadataExtractor(file_path, url)
            extracted = extractor.extract_all()

            update_dict = {}
            for key in ['title', 'description', 'keywords', 'tags', 'category', 'domain', 'subsections']:
                if key in extracted:
                    existing_value = metadata.get(key)
                    if existing_value is None or not existing_value:
                        update_dict[key] = extracted[key]
                    elif key == 'keywords' and (not existing_value or len(existing_value) < 3):
                        update_dict[key] = extracted[key]

            if update_dict:
                updates[doc_id] = update_dict
                if verbose:
                    print(f"  [{processed}/{total_count}] Queued {doc_id}")
                elif processed % progress_interval == 0:
                    print(f"  [{processed}/{total_count}] Progress: {skipped} skipped, {len(updates)} queued...")
            else:
                skipped += 1
        except Exception as e:
            error_count += 1
            if verbose:
                print(f"  [{processed}/{total_count}] Error processing {doc_id}: {e}")

    if updates:
        print(f"\nApplying {len(updates)} updates in batch...")
        if manager.batch_update_entries(updates):
            updated_count = len(updates)
            print(f"   Successfully updated {updated_count} entries")
        else:
            error_count += len(updates)
            print(f"   Failed to apply batch update")
            updated_count = 0
    else:
        updated_count = 0

    print(f"\nExtraction complete:")
    print(f"   Processed: {processed}/{total_count}")
    print(f"   Updated: {updated_count}")
    print(f"   Skipped: {skipped}")
    print(f"   Errors: {error_count}")


def cmd_validate_metadata(manager: IndexManager, base_dir: Path) -> None:
    """Validate metadata quality after extraction"""
    total_count = manager.get_entry_count()

    stats = {
        'total': total_count,
        'has_title': 0,
        'has_description': 0,
        'has_keywords': 0,
        'has_tags': 0,
        'has_category': 0,
        'has_domain': 0,
        'has_all_metadata': 0,
        'empty_keywords': 0,
        'minimal_keywords': 0,
        'missing_files': 0,
    }

    for doc_id, metadata in manager.list_entries():
        if metadata.get('title'):
            stats['has_title'] += 1
        if metadata.get('description'):
            stats['has_description'] += 1
        if metadata.get('keywords'):
            stats['has_keywords'] += 1
            keywords = metadata.get('keywords', [])
            if not keywords:
                stats['empty_keywords'] += 1
            elif len([k for k in keywords if k and len(str(k)) >= 4]) < 3:
                stats['minimal_keywords'] += 1
        if metadata.get('tags'):
            stats['has_tags'] += 1
        if metadata.get('category'):
            stats['has_category'] += 1
        if metadata.get('domain'):
            stats['has_domain'] += 1

        has_all = all(key in metadata and metadata[key]
                     for key in ['title', 'description', 'keywords', 'tags', 'category', 'domain'])
        if has_all:
            stats['has_all_metadata'] += 1

        path_str = metadata.get('path')
        if path_str:
            file_path = base_dir / path_str
            if not file_path.exists():
                stats['missing_files'] += 1

    print("Metadata Validation Report:")
    print("=" * 60)
    print(f"Total entries: {stats['total']}")
    print()
    if total_count > 0:
        print("Field Coverage:")
        print(f"  Title:       {stats['has_title']}/{stats['total']} ({stats['has_title']*100//total_count}%)")
        print(f"  Description: {stats['has_description']}/{stats['total']} ({stats['has_description']*100//total_count}%)")
        print(f"  Keywords:    {stats['has_keywords']}/{stats['total']} ({stats['has_keywords']*100//total_count}%)")
        print(f"  Tags:        {stats['has_tags']}/{stats['total']} ({stats['has_tags']*100//total_count}%)")
        print(f"  Category:    {stats['has_category']}/{stats['total']} ({stats['has_category']*100//total_count}%)")
        print(f"  Domain:      {stats['has_domain']}/{stats['total']} ({stats['has_domain']*100//total_count}%)")
        print()
        print(f"Complete Metadata: {stats['has_all_metadata']}/{stats['total']} ({stats['has_all_metadata']*100//total_count}%)")

    issues = []
    if stats['empty_keywords'] > 0:
        issues.append(f"Empty keywords: {stats['empty_keywords']}")
    if stats['minimal_keywords'] > 0:
        issues.append(f"Minimal keywords (<3): {stats['minimal_keywords']}")
    if stats['missing_files'] > 0:
        issues.append(f"Missing files: {stats['missing_files']}")

    if issues:
        print("\nQuality Issues:")
        for issue in issues:
            print(f"  - {issue}")
        if stats['missing_files'] > 0:
            print("\nCritical issue: One or more index entries reference missing files.")
            sys.exit(1)
    else:
        print("\nNo quality issues detected")


def main() -> None:
    """Main entry point"""
    parser = argparse.ArgumentParser(
        description='Manage index.yaml',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  python manage_index.py get doc-id
  python manage_index.py update doc-id '{"path": "test.md"}'
  python manage_index.py list
  python manage_index.py count
  python manage_index.py verify
  python manage_index.py extract-keywords
        """
    )

    add_common_index_args(parser, include_json=False)

    subparsers = parser.add_subparsers(dest='command', help='Command to execute')

    get_parser = subparsers.add_parser('get', help='Get entry by ID')
    get_parser.add_argument('doc_id', help='Document ID')

    update_parser = subparsers.add_parser('update', help='Update entry')
    update_parser.add_argument('doc_id', help='Document ID')
    update_parser.add_argument('metadata', help='Metadata as JSON string')

    remove_parser = subparsers.add_parser('remove', help='Remove entry')
    remove_parser.add_argument('doc_id', help='Document ID')

    list_parser = subparsers.add_parser('list', help='List entries')
    list_parser.add_argument('--filter', action='append', metavar='KEY=VALUE',
                             help='Filter by field (can be used multiple times)')
    list_parser.add_argument('--limit', type=int, metavar='N',
                             help='Limit output to first N entries')

    subparsers.add_parser('count', help='Get total entry count')
    subparsers.add_parser('verify', help='Verify index integrity')
    subparsers.add_parser('validate-metadata', help='Validate metadata quality after extraction')

    add_keywords_parser = subparsers.add_parser('add-keywords', help='Add/update keywords for an entry')
    add_keywords_parser.add_argument('doc_id', help='Document ID')
    add_keywords_parser.add_argument('keywords', nargs='+', help='Keywords to add')

    add_tags_parser = subparsers.add_parser('add-tags', help='Add/update tags for an entry')
    add_tags_parser.add_argument('doc_id', help='Document ID')
    add_tags_parser.add_argument('tags', nargs='+', help='Tags to add')

    extract_parser = subparsers.add_parser('extract-keywords', help='Extract keywords from all documents')
    extract_parser.add_argument('--no-skip-existing', action='store_true',
                               help='Re-extract metadata even if already present')
    extract_parser.add_argument('--verbose', '-v', action='store_true',
                               help='Print detailed progress for each file')

    args = parser.parse_args()

    if not args.command:
        parser.print_help()
        sys.exit(1)

    logger.start({
        'command': args.command,
        'base_dir': args.base_dir,
    })

    exit_code = EXIT_SUCCESS
    try:
        base_dir = resolve_base_dir(args.base_dir)
        manager = IndexManager(base_dir)

        if args.command == 'get':
            cmd_get(manager, args.doc_id)
        elif args.command == 'update':
            cmd_update(manager, args.doc_id, args.metadata)
        elif args.command == 'remove':
            cmd_remove(manager, args.doc_id)
        elif args.command == 'list':
            filters = {}
            if args.filter:
                for f in args.filter:
                    if '=' in f:
                        key, value = f.split('=', 1)
                        filters[key] = value
            limit = getattr(args, 'limit', None)
            cmd_list(manager, filters, limit=limit)
        elif args.command == 'count':
            cmd_count(manager)
        elif args.command == 'verify':
            cmd_verify(manager, base_dir)
        elif args.command == 'validate-metadata':
            cmd_validate_metadata(manager, base_dir)
        elif args.command == 'add-keywords':
            cmd_add_keywords(manager, args.doc_id, args.keywords)
        elif args.command == 'add-tags':
            cmd_add_tags(manager, args.doc_id, args.tags)
        elif args.command == 'extract-keywords':
            skip_existing = not getattr(args, 'no_skip_existing', False)
            verbose = getattr(args, 'verbose', False)
            with logger.time_operation('extract_keywords'):
                cmd_extract_keywords(manager, base_dir, skip_existing=skip_existing, verbose=verbose)
        else:
            parser.print_help()
            exit_code = 1

        logger.end(exit_code=exit_code)

    except SystemExit:
        raise
    except Exception as e:
        logger.log_error("Fatal error in manage_index", error=e)
        exit_code = 1
        logger.end(exit_code=exit_code)
        sys.exit(exit_code)


if __name__ == '__main__':
    main()
