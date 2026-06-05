# local_certmaster

CertMaster-equivalent certification readiness tracking for understandtech.app.

## Features (v1.0)

- Certification framework schema (certifications, domains, objectives)
- Security+ SY0-701 domain seed on install (blueprint weights 11/22/25/28/14%)
- Confidence-aware mastery algorithm per white paper Section 2.4
- `api::get_user_readiness()` for dashboard/radar JSON
- Hourly scheduled mastery recalculation task

## Not yet implemented

- `certmaster_confidence` question behaviour UI
- CSV objective import admin UI
- `amd/src/radar_chart.js` (consumed by `block_examreadiness`)

## Install

Copy to `{moodleroot}/local/certmaster/` and run `php admin/cli/upgrade.php --non-interactive`.
