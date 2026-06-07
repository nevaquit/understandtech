# Workflows

## Table of Contents

- [Scraping Cursor Documentation](#scraping-cursor-documentation)
- [Refreshing the Index](#refreshing-the-index)
- [Validating the Index](#validating-the-index)
- [Cache Management Workflow](#cache-management-workflow)
- [Development Mode Workflow](#development-mode-workflow)

---

## Scraping Cursor Documentation

**Scenario:** Scrape or update Cursor documentation from cursor.com.

### Using the Slash Command

```bash
/cursor-ecosystem:docs-ops scrape
```

### Using the Script Directly

**Linux/macOS:**

```bash
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/core/scrape_docs.py
```

**Windows (PowerShell):**

```powershell
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/core/scrape_docs.py
```

### Validation After Scraping

Always validate after scraping:

```bash
# Validate index integrity
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/maintenance/validate_index.py

# Check document count
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/management/manage_index.py count
```

---

## Refreshing the Index

**Scenario:** Rebuild the index after scraping or when metadata needs updating.

### Using the Slash Command

```bash
/cursor-ecosystem:docs-ops refresh
```

### Using the Script Directly

```bash
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/management/refresh_index.py
```

### What Refresh Does

1. **Scans canonical/** - Finds all markdown files
2. **Extracts metadata** - Parses frontmatter
3. **Generates keywords** - Uses spaCy NLP (if available)
4. **Updates index.yaml** - Writes consolidated index
5. **Syncs index.json** - Creates JSON mirror
6. **Generates report** - Summary of index state

### Using the Public API

```python
from cursor_docs_api import refresh_index

result = refresh_index()
if result['success']:
    print("Refreshed successfully")
else:
    print(f"Errors: {result['error']}")
```

### Individual Refresh Steps

```bash
# Run only rebuild step
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/management/refresh_index.py --step rebuild-index

# Run only keyword extraction step
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/management/refresh_index.py --step extract-keywords

# Run only report generation step
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/management/refresh_index.py --step generate-report
```

---

## Validating the Index

**Scenario:** Check index integrity and detect drift.

### Using the Slash Command

```bash
/cursor-ecosystem:docs-ops validate
```

### Using the Script Directly

```bash
# Basic validation
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/maintenance/validate_index.py

# Verbose output
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/maintenance/validate_index.py --verbose

# JSON output for scripting
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/maintenance/validate_index.py --json
```

### What Validation Checks

1. **Missing files** - Files referenced in index but not on filesystem
2. **Orphaned files** - Files on filesystem but not in index
3. **Hash mismatches** - Content changed since last index
4. **Missing fields** - Required metadata fields (path, hash, last_fetched)

---

## Cache Management Workflow

**Scenario:** Clear caches to force fresh data.

### View Cache Status

```bash
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/maintenance/clear_cache.py --info
```

### Clear All Caches

```bash
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/maintenance/clear_cache.py
```

### Clear Specific Cache

```bash
# Clear only inverted index cache
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/maintenance/clear_cache.py --inverted

# Clear only LLMS/scraper cache
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/maintenance/clear_cache.py --llms
```

### JSON Output for Scripting

```bash
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/maintenance/clear_cache.py --info --json
```

---

## Development Mode Workflow

**Scenario:** Testing changes to the skill before committing.

### Enable Development Mode

Set the environment variable to use local plugin code:

```bash
# Linux/macOS
export CURSOR_DOCS_DEV_ROOT=/path/to/claude-code-plugins

# Windows PowerShell
$env:CURSOR_DOCS_DEV_ROOT = "D:\repos\gh\melodic\claude-code-plugins"
```

### Run Scripts in Dev Mode

```bash
# Scrape using local code
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/core/scrape_docs.py
```

### Verify Dev Mode Active

Scripts will show dev mode indicator in output:

```text
[DEV MODE] Using local plugin: D:\repos\gh\melodic\claude-code-plugins
```

### Disable Development Mode

```bash
# Linux/macOS
unset CURSOR_DOCS_DEV_ROOT

# Windows PowerShell
Remove-Item Env:CURSOR_DOCS_DEV_ROOT
```

---

## Index Management Operations

### List Documents

```bash
# List all documents
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/management/manage_index.py list

# List with pagination
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/management/manage_index.py list --limit 20

# List in JSON format
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/management/manage_index.py list --json
```

### Get Document Details

```bash
# Get specific document metadata
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/management/manage_index.py get cursor-com-docs-tab-overview
```

### Count Documents

```bash
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/management/manage_index.py count
```

### Verify Index

```bash
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/management/manage_index.py verify
```

---

## Search Workflows

### Keyword Search

```bash
# Search by keywords
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/core/find_docs.py search agent mcp

# Limit results
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/core/find_docs.py search agent --limit 5
```

### Natural Language Query

```bash
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/core/find_docs.py query "how to configure agent mode"
```

### Filter by Category

```bash
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/core/find_docs.py category core
```

### Filter by Tag

```bash
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/core/find_docs.py tag cli
```

### Resolve Doc ID

```bash
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/core/find_docs.py resolve cursor-com-docs-tab-overview
```

---

## Full Scrape and Index Workflow

**Scenario:** Complete end-to-end documentation update.

### Step 1: Scrape Documentation

```bash
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/core/scrape_docs.py
```

### Step 2: Refresh Index

```bash
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/management/refresh_index.py
```

### Step 3: Validate

```bash
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/maintenance/validate_index.py
```

### Step 4: Test Search

```bash
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/core/find_docs.py search agent mcp
```

---

## Rebuild Index from Filesystem

**Scenario:** Regenerate index when files are added/removed manually.

```bash
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/management/rebuild_index.py
```

### Dry Run Mode

```bash
python plugins/cursor-ecosystem/skills/cursor-docs/scripts/management/rebuild_index.py --dry-run
```
