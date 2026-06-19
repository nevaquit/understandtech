# Core patches

Optional Moodle core patches applied during deploy when `apply-patches.sh` is executable.

| Patch | Purpose |
|-------|---------|
| `mod-page-skip-filters-cert-courses.patch` | Skip `format_text` filters on SEC701/NET009/APLUS lesson HTML — prevents intermittent "Error reading from database" on large `mod_page` bodies when filter MUC/DB lookups fail |

Applied automatically by `apply-patches.sh` during deploy.
