# block_examreadiness

Dashboard block showing exam readiness %, domain radar chart, and dangerous misconceptions via `local_certmaster` API.

## Configuration

1. Add block to **My Moodle** or a course page.
2. Open block settings and choose a **Certification** from the CertMaster dropdown (Security+ SY0-701 is seeded on fresh install).
3. Optionally set a custom **Block title**.

The radar chart refreshes automatically via `local_certmaster_get_user_readiness` when the block is visible (e.g. after completing a quiz in another tab).

## Dependencies

- `local_certmaster` (readiness API + `radar_chart` AMD module)
