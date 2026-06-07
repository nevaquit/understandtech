#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
refresh_index.py - One-shot orchestration to refresh the cursor-docs index

Adapted for Cursor Documentation from openai-ecosystem codex-cli-docs skill.

Runs the full, typical pipeline in the foreground so agents don't need to
chain multiple background commands:

1. Rebuild index from filesystem
2. Extract keywords / metadata for all documents
3. Generate a summary report

This script is designed specifically so tools like Claude Code can run a
single, short-lived command (no background job, no polling loops) and rely
on the final "REFRESH_INDEX_DONE" sentinel line.

IMPORTANT: This script only prints plain ASCII to avoid Windows console
encoding issues. Do not wrap it in additional environment prefixes; just run:

    python refresh_index.py
"""

import sys
from pathlib import Path
sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
import bootstrap; scripts_dir = bootstrap.scripts_dir

import argparse
import subprocess
from datetime import datetime

from utils.script_utils import format_duration, EXIT_SUCCESS
from utils.logging_utils import get_or_setup_logger

logger = get_or_setup_logger(__file__, log_category="index")

# Import path_config for base_dir
try:
    from utils.path_config import get_base_dir
except ImportError:
    def get_base_dir(start=None):
        from utils.common_paths import find_repo_root
        repo_root = find_repo_root(start)
        return repo_root / "plugins" / "cursor-ecosystem" / "skills" / "cursor-docs" / "canonical"

def run_step(description: str, cmd: list) -> bool:
    """Run a subprocess step with simple logging (ASCII-only)."""
    print()
    print("=" * 80)
    print(f">>> {description}")
    print(f"    Command: {' '.join(cmd)}")
    print("=" * 80)
    start = datetime.now()
    logger.debug(f"Starting step: {description}")

    try:
        # Let subprocess inherit stdout/stderr to avoid encoding issues
        result = subprocess.run(
            cmd,
            check=False,
            text=True,
        )
    except KeyboardInterrupt:
        print("\n[ERROR] Aborted by user (KeyboardInterrupt)")
        logger.warning(f"Step aborted by user: {description}")
        return False

    duration = (datetime.now() - start).total_seconds()
    status = "OK" if result.returncode == 0 else "FAIL"
    print()
    print(f"[{status}] Finished: {description} (exit code {result.returncode}, {format_duration(duration)})")

    # Log structured metrics for step
    logger.info(f"Step completed: {description} [{status}]")
    logger.track_metric(f"step_{description.lower().replace(' ', '_')}_duration", duration)
    logger.track_metric(f"step_{description.lower().replace(' ', '_')}_exit_code", result.returncode)

    return result.returncode == 0

# Individual step functions for modular execution
def step_rebuild_index(scripts_dir: Path) -> bool:
    """Step 1: Rebuild index from filesystem"""
    return run_step(
        "Rebuild index from filesystem",
        [sys.executable, str(scripts_dir / "management" / "rebuild_index.py")],
    )

def step_extract_keywords(scripts_dir: Path) -> bool:
    """Step 2: Extract keywords and metadata for all documents"""
    return run_step(
        "Extract keywords and metadata for all documents",
        [
            sys.executable,
            str(scripts_dir / "management" / "manage_index.py"),
            "extract-keywords",
        ],
    )

def step_generate_report(scripts_dir: Path) -> bool:
    """Step 3: Generate index metadata report"""
    return run_step(
        "Generate index metadata report",
        [sys.executable, str(scripts_dir / "management" / "generate_report.py")],
    )

def main() -> int:
    """Main entry point for refresh_index orchestration."""
    parser = argparse.ArgumentParser(
        description='Refresh cursor-docs index (rebuild, extract keywords)',
        formatter_class=argparse.RawDescriptionHelpFormatter
    )
    parser.add_argument(
        '--step',
        choices=['rebuild-index', 'extract-keywords', 'generate-report'],
        help='Run a specific step only (modular execution)'
    )
    parser.add_argument(
        '--clear-cache',
        action='store_true',
        help='Clear all caches before refresh (inverted index + LLMS)'
    )
    args = parser.parse_args()

    # Print dev/prod mode banner for visibility
    from utils.dev_mode import print_mode_banner
    print_mode_banner(logger)

    # scripts_dir already set by setup_python_path() at top of file
    start_time = datetime.now()
    base_dir = get_base_dir()

    # Clear cache if requested
    if args.clear_cache:
        try:
            from utils.cache_manager import CacheManager
            cm = CacheManager(base_dir)
            result = cm.clear_all()
            if result['inverted_index'] or result['llms_cache']:
                print("Cache cleared:")
                if result['inverted_index']:
                    print("  - Inverted index cache cleared")
                if result['llms_cache']:
                    print("  - LLMS/scraper cache cleared")
            else:
                print("Cache was already empty")
            print()
        except ImportError:
            print("Warning: CacheManager not available, skipping cache clear")
        except Exception as e:
            print(f"Warning: Failed to clear cache: {e}")

    # Log script start with parameters
    logger.start({
        'step': args.step,
        'base_dir': str(base_dir),
    })

    # Print basic Python environment summary for observability
    print("=" * 80)
    print("Cursor Docs - refresh_index.py")
    print(f"Python version : {sys.version.split()[0]}")
    print(f"Python exe     : {sys.executable}")
    print(f"Scripts folder : {scripts_dir}")
    if args.step:
        print(f"Running step   : {args.step}")
    print("=" * 80)

    # Handle single-step execution
    if args.step:
        step_ok = False
        if args.step == 'rebuild-index':
            step_ok = step_rebuild_index(scripts_dir)
        elif args.step == 'extract-keywords':
            step_ok = step_extract_keywords(scripts_dir)
        elif args.step == 'generate-report':
            step_ok = step_generate_report(scripts_dir)

        if not step_ok:
            print(f"Step '{args.step}' failed. See output above.")
            logger.end(exit_code=1, summary={'step': args.step, 'status': 'failed'})
            return 1

        print("\nStep execution complete.")
        print("REFRESH_INDEX_DONE")
        logger.end(exit_code=EXIT_SUCCESS, summary={'step': args.step, 'status': 'ok'})
        return EXIT_SUCCESS

    # Full workflow execution
    # 1) Rebuild index from filesystem
    step1_ok = step_rebuild_index(scripts_dir)
    if not step1_ok:
        print("Index rebuild failed. See output above.")
        logger.end(exit_code=1, summary={'failed_step': 'rebuild_index'})
        return 1

    # 2) Extract keywords / metadata (foreground, no background jobs)
    step2_ok = step_extract_keywords(scripts_dir)
    if not step2_ok:
        print("Keyword/metadata extraction failed. See output above.")
        logger.end(exit_code=1, summary={'failed_step': 'extract_keywords'})
        return 1

    # 3) Generate summary report
    step3_ok = step_generate_report(scripts_dir)
    if not step3_ok:
        print("Report generation failed. See output above.")
        logger.end(exit_code=1, summary={'failed_step': 'generate_report'})
        return 1

    # Calculate total duration
    total_duration = (datetime.now() - start_time).total_seconds()

    print()
    print("Index refresh complete.")
    print("  - Index rebuilt from filesystem")
    print("  - Keywords / metadata extracted")
    print("  - Summary report generated")
    print()
    print(f"Total duration: {format_duration(total_duration)}")
    print()
    print("Expected runtime: ~10-20 seconds for typical documentation sets.")
    print("This command is designed to be run in the foreground (no background job needed).")
    print()

    # Sentinel line for tools/agents to detect completion reliably
    print("REFRESH_INDEX_DONE")

    # Track total metrics
    logger.track_metric('total_duration_seconds', total_duration)

    logger.end(exit_code=EXIT_SUCCESS, summary={
        'status': 'success',
        'total_duration': format_duration(total_duration),
        'steps_completed': 3,
    })
    return EXIT_SUCCESS

if __name__ == "__main__":
    sys.exit(main())
