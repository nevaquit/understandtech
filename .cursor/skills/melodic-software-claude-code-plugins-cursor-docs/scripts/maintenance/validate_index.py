#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
validate_index.py - Validate index.yaml integrity and detect drift

Adapted for Cursor Documentation from openai-ecosystem codex-cli-docs skill.

Checks the index for:
- Missing required fields
- Invalid metadata
- Orphaned files (in filesystem but not in index)
- Missing files (in index but not in filesystem)
- Hash mismatches (content changed)

Usage:
    python validate_index.py [--fix] [--verbose]
"""

import sys
from pathlib import Path
sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

import argparse
import hashlib

from utils.script_utils import configure_utf8_output, ensure_yaml_installed
from utils.cli_utils import add_base_dir_argument, resolve_base_dir_from_args
from utils.logging_utils import get_or_setup_logger

configure_utf8_output()
yaml = ensure_yaml_installed()

logger = get_or_setup_logger(__file__, log_category="index")

try:
    from management.index_manager import IndexManager
except ImportError:
    print("Error: Could not import index_manager")
    sys.exit(1)


def strip_frontmatter(content: str) -> str:
    """Strip YAML frontmatter from content."""
    if content.startswith('---'):
        parts = content.split('---', 2)
        if len(parts) >= 3:
            return parts[2].strip()
    return content


def calculate_hash(content: str) -> str:
    """Calculate SHA-256 hash of content body."""
    body = strip_frontmatter(content)
    hash_obj = hashlib.sha256(body.encode('utf-8'))
    return f"sha256:{hash_obj.hexdigest()}"


def validate_index(base_dir: Path, verbose: bool = False, fix: bool = False) -> dict:
    """
    Validate index integrity.

    Args:
        base_dir: Base directory for canonical storage
        verbose: Print detailed progress
        fix: Attempt to fix issues (not implemented yet)

    Returns:
        Dictionary with validation results
    """
    manager = IndexManager(base_dir)
    index = manager.load_all()

    results = {
        'total_entries': len(index),
        'valid_entries': 0,
        'issues': [],
        'missing_files': [],
        'orphaned_files': [],
        'hash_mismatches': [],
        'missing_fields': [],
    }

    # Required fields
    required_fields = ['path', 'hash', 'last_fetched']

    # Track indexed paths
    indexed_paths = set()

    # Validate each entry
    for doc_id, metadata in index.items():
        issues_found = False

        # Check required fields
        for field in required_fields:
            if field not in metadata:
                results['missing_fields'].append((doc_id, field))
                issues_found = True

        # Check file exists
        path_str = metadata.get('path')
        if path_str:
            indexed_paths.add(path_str)
            file_path = base_dir / path_str
            if not file_path.exists():
                results['missing_files'].append((doc_id, path_str))
                issues_found = True
            else:
                # Check hash if we have one
                expected_hash = metadata.get('hash')
                if expected_hash:
                    try:
                        content = file_path.read_text(encoding='utf-8')
                        actual_hash = calculate_hash(content)
                        if expected_hash != actual_hash:
                            results['hash_mismatches'].append((doc_id, expected_hash, actual_hash))
                            issues_found = True
                    except Exception as e:
                        results['issues'].append(f"Error reading {path_str}: {e}")
                        issues_found = True

        if not issues_found:
            results['valid_entries'] += 1

    # Find orphaned files (in filesystem but not in index)
    for md_file in base_dir.rglob('*.md'):
        if md_file.name == 'README.md':
            continue
        try:
            rel_path = str(md_file.relative_to(base_dir)).replace('\\', '/')
            if rel_path not in indexed_paths:
                results['orphaned_files'].append(rel_path)
        except ValueError:
            pass

    return results


def print_results(results: dict, verbose: bool = False):
    """Print validation results."""
    print("Index Validation Report")
    print("=" * 60)
    print(f"Total entries: {results['total_entries']}")
    print(f"Valid entries: {results['valid_entries']}")
    print()

    has_issues = False

    # Missing files
    if results['missing_files']:
        has_issues = True
        print(f"Missing Files ({len(results['missing_files'])}):")
        for doc_id, path in results['missing_files'][:20]:
            print(f"  - {doc_id}: {path}")
        if len(results['missing_files']) > 20:
            print(f"  ... and {len(results['missing_files']) - 20} more")
        print()

    # Hash mismatches
    if results['hash_mismatches']:
        has_issues = True
        print(f"Hash Mismatches ({len(results['hash_mismatches'])}):")
        for doc_id, expected, actual in results['hash_mismatches'][:10]:
            print(f"  - {doc_id}")
            if verbose:
                print(f"    Expected: {expected[:40]}...")
                print(f"    Actual:   {actual[:40]}...")
        if len(results['hash_mismatches']) > 10:
            print(f"  ... and {len(results['hash_mismatches']) - 10} more")
        print()

    # Missing fields
    if results['missing_fields']:
        has_issues = True
        print(f"Missing Required Fields ({len(results['missing_fields'])}):")
        for doc_id, field in results['missing_fields'][:10]:
            print(f"  - {doc_id}: missing '{field}'")
        if len(results['missing_fields']) > 10:
            print(f"  ... and {len(results['missing_fields']) - 10} more")
        print()

    # Orphaned files
    if results['orphaned_files']:
        has_issues = True
        print(f"Orphaned Files ({len(results['orphaned_files'])}):")
        for path in results['orphaned_files'][:10]:
            print(f"  - {path}")
        if len(results['orphaned_files']) > 10:
            print(f"  ... and {len(results['orphaned_files']) - 10} more")
        print()

    # General issues
    if results['issues']:
        has_issues = True
        print(f"Other Issues ({len(results['issues'])}):")
        for issue in results['issues'][:10]:
            print(f"  - {issue}")
        print()

    print("=" * 60)
    if has_issues:
        print("Validation completed with issues.")
        print("\nTo fix:")
        print("  - Missing files: Run rebuild_index.py to clean up")
        print("  - Hash mismatches: Run rebuild_index.py to update hashes")
        print("  - Orphaned files: Run rebuild_index.py to add to index")
    else:
        print("Validation completed successfully - no issues found.")


def main() -> None:
    """Main entry point."""
    parser = argparse.ArgumentParser(
        description='Validate index.yaml integrity',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  # Run validation
  python validate_index.py

  # Verbose output
  python validate_index.py --verbose

  # JSON output for scripting
  python validate_index.py --json
        """
    )

    add_base_dir_argument(parser)
    parser.add_argument('--verbose', '-v', action='store_true',
                       help='Print detailed output')
    parser.add_argument('--fix', action='store_true',
                       help='Attempt to fix issues (not implemented)')
    parser.add_argument('--json', action='store_true',
                       help='Output results as JSON')

    args = parser.parse_args()

    logger.start({'verbose': args.verbose})

    try:
        base_dir = resolve_base_dir_from_args(args)

        if not base_dir.exists():
            print(f"Error: Directory does not exist: {base_dir}")
            sys.exit(1)

        with logger.time_operation('validate_index'):
            results = validate_index(base_dir, verbose=args.verbose, fix=args.fix)

        if args.json:
            import json
            print(json.dumps(results, indent=2))
        else:
            print_results(results, verbose=args.verbose)

        # Exit with error code if there are issues
        has_critical_issues = (
            len(results['missing_files']) > 0 or
            len(results['missing_fields']) > 0
        )

        exit_code = 1 if has_critical_issues else 0
        logger.end(exit_code=exit_code, summary={
            'total': results['total_entries'],
            'valid': results['valid_entries'],
            'issues': len(results['issues'])
        })

        sys.exit(exit_code)

    except Exception as e:
        logger.log_error("Fatal error in validate_index", error=e)
        sys.exit(1)


if __name__ == '__main__':
    main()
