# Technical Details

## Table of Contents

- [Encapsulation Boundary](#encapsulation-boundary)
- [Dependencies](#dependencies)
- [Keyword Extraction Strategy](#keyword-extraction-strategy)
- [File Structure](#file-structure)
- [References Folder Structure](#references-folder-structure)

---

## Encapsulation Boundary

The cursor-docs skill maintains a clear boundary between its **public interface** (what external skills interact with) and **private implementation** (internal storage and logic).

### Public Interface (Accessible to External Skills)

**What external skills can access:**

- **SKILL.md** - Usage documentation and guidance (how to use the skill)
- **references/** - Technical guides, workflows, and best practices (how the skill works)
- **Public API** - Programmatic access via functions:
  - `find_document()` - Natural language search
  - `resolve_doc_id()` - Convert doc_id to content
  - `get_docs_by_tag()` - Tag-based filtering
  - `search_by_keywords()` - Keyword search
  - `get_document_section()` - Section extraction
  - `refresh_index()` - Index refresh

**How external skills invoke the skill:**

- Natural language: "Find documentation about agent mode"
- Programmatic: `from cursor_docs_api import find_document`
- File paths: Never - external skills use API, not file paths

### Private Implementation (Internal to Skill)

**What is NOT exposed to external skills:**

- **canonical/** - Official documentation storage (internal database)
  - `cursor-com/` - Scraped docs from cursor.com
  - `index.yaml` - Metadata index (accessed via API only)

- **scripts/** - Implementation scripts (use via API commands, never accessed directly)
  - Core scripts (scraping, discovery, resolution)
  - Management scripts (index maintenance, cleanup)
  - Maintenance scripts (drift detection, cache clearing)

- **Internal APIs** - Not exposed:
  - `index_manager.py` - Low-level index operations
  - `scrape_docs.py` - Scraping implementation
  - `doc_resolver.py` - Resolution logic

### Why This Boundary Matters

**Encapsulation benefits:**

1. **Implementation hiding** - Internal structure can change without breaking external skills
2. **Consistent access** - All external skills use same API regardless of internal changes
3. **Maintainability** - Updates to canonical storage don't require changes to external skills
4. **Resilience** - Doc references survive file moves/renames (via alias resolution)
5. **Clean API** - External skills never need to know file paths, structure, or implementation details

### Example: Discovery Without Knowing Implementation

**External skill:**

```python
docs = find_document("agent mode configuration")
# Returns results, never needs to know:
# - Where canonical/ directory is located
# - How index.yaml is structured
# - How document metadata is extracted
# - How file paths are resolved
```

**Internal (hidden from external skills):**

- Reads index.yaml
- Matches against keywords and tags
- Ranks by relevance
- Resolves file paths
- Loads content if needed

External skill only sees the result, never the implementation details.

---

## Dependencies

### Required Dependencies

| Dependency | Version | Purpose |
| --- | --- | --- |
| PyYAML | >=6.0 | YAML parsing for index.yaml |
| requests | >=2.28 | HTTP requests for scraping |
| beautifulsoup4 | >=4.11 | HTML parsing |
| markdownify | >=0.11 | HTML to Markdown conversion |
| filelock | >=3.0 | File locking for concurrent access |

### Optional Dependencies

| Dependency | Version | Purpose |
| --- | --- | --- |
| spacy | >=3.7 | Keyword extraction (auto-installed) |
| en_core_web_sm | N/A | spaCy English model |
| yake | >=0.4.8 | Alternative keyword extraction |

### Python Version

- **Recommended:** Python 3.13 (required for spaCy operations)
- **Minimum:** Python 3.11

---

## Keyword Extraction Strategy

### Automatic Keyword Extraction

Keywords are extracted automatically during index refresh using spaCy NLP:

1. **Noun chunks** - Multi-word noun phrases (e.g., "agent mode", "context management")
2. **Named entities** - Proper nouns and technical terms
3. **Filtering** - Remove stopwords, short words, generic terms

### Manual Keyword Override

For documents requiring specific keywords, add to frontmatter:

```yaml
---
keywords:
  - agent
  - mcp
  - context
  - codebase indexing
---
```

---

## File Structure

```text
cursor-docs/
  SKILL.md                    # Primary documentation (public)
  cursor_docs_api.py          # Public API
  requirements.txt            # Python dependencies
  references/                  # Technical documentation (public)
    technical-details.md
    workflows.md
  canonical/                   # Documentation storage (private)
    cursor-com/               # Scraped from cursor.com
    index.yaml                # Metadata index
    index.json                # JSON mirror
  scripts/                     # Implementation (private)
    core/                     # Core operations
    management/               # Index management
    maintenance/              # Cleanup and drift
    utils/                    # Shared utilities
  config/                      # Configuration
    defaults.yaml             # Default settings
    filtering.yaml            # Content filtering
    tag_detection.yaml        # Tag configuration
  .cache/                      # Cache storage (inverted index)
  logs/                        # Log files
```

---

## References Folder Structure

The `references/` folder contains technical documentation organized by topic:

```text
references/
  technical-details.md        # This file - architecture and internals
  workflows.md                # Common operational workflows
  sources.json                # Documentation sources configuration
```

---

## Source: llms.txt

Cursor documentation uses **llms.txt** format:

- **URL:** `https://cursor.com/llms.txt`
- **Format:** Markdown links `[Title](URL)`
- **Parsing:** Extract URLs via regex pattern `\[([^\]]+)\]\(([^)]+)\)`

### llms.txt Characteristics

| Aspect | Description |
| --- | --- |
| Format | Markdown links |
| URLs | Direct .md files (can fetch markdown directly) |
| Categories | Inferred from URL path |
| Titles | Included in link text |
| Expected Count | ~90+ documents |

---

## Index Structure

### index.yaml Format

```yaml
cursor-com-docs-tab-overview:
  url: https://cursor.com/docs/tab/overview.md
  title: "Tab Overview"
  description: "Cursor Tab autocomplete features"
  path: cursor-com/docs/tab/overview.md
  domain: cursor.com
  category: core
  keywords:
    - tab
    - autocomplete
    - code completion
  tags:
    - tab
    - ide
  content_hash: sha256:abc123...
  last_fetched: 2025-12-16T10:30:00Z
```

### doc_id Generation

doc_id is generated from URL:

```text
https://cursor.com/docs/tab/overview.md
                    ↓
cursor-com-docs-tab-overview
```

Rules:

- Replace `.` with `-` (domain separator)
- Replace `/` with `-` (path separator)
- Lowercase all characters
- Remove trailing `-`
- Remove `.md` extension
