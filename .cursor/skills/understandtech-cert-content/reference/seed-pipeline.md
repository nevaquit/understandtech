# Seed Pipeline

## Core scripts

| Script | Purpose |
|--------|---------|
| `scripts/seed-security-plus-course.php` | SEC701 course, pages, GIFT, domain quizzes |
| `scripts/seed-network-plus-course.php` | NET009 |
| `scripts/seed-comptia-a-plus-course.php` | APLUS |
| `scripts/seed-*-course-vm.sh` | VM wrapper (env, course id) |
| `scripts/lib/moodle-cert-course-filters.php` | Disable page filters post-seed |
| `scripts/lib/moodle-cert-quiz-dedup.php` | Bank dedupe + KC rebuild |
| `scripts/cleanup-cert-knowledge-checks.php` | CLI: `SEC701\|NET009\|APLUS\|all` |
| `scripts/fix-*-course-filters.php` | Per-track filter fix |
| `scripts/verify-cert-course-pages.sh` | Lesson render smoke (3 tracks) |
| `scripts/verify-moodle-web-health.sh` | Strict authenticated health |

## Extract / generate (local dev)

| Script | Output |
|--------|--------|
| `scripts/extract-security-plus-lessons.mjs` | `content/security-plus/lessons/` |
| `scripts/extract-security-plus-diagrams.mjs` | `content/security-plus/diagrams/` |
| `scripts/extract-security-plus-course-notes.mjs` | `content/security-plus/course-notes/` |
| `scripts/extract-security-plus-ebooks.mjs` | `content/security-plus/supplements/` |
| `scripts/generate-security-plus-quiz-gift.mjs` | `sy0-701-quiz-extra.gift` |
| `scripts/extract-network-plus-*.mjs` | `content/network-plus/` |
| `scripts/generate-network-plus-quiz-gift.mjs` | NET009 GIFT expansion |
| `scripts/build-network-plus-quiz-from-practice-bank.cjs` | From CyberKraft PDF bank |

## GitHub Actions

| Workflow | Trigger |
|----------|---------|
| `.github/workflows/seed-sec701.yml` | `workflow_dispatch` → staging/production |
| `.github/workflows/seed-net009.yml` | same |
| `.github/workflows/seed-aplus.yml` | same |

Typical flow after seed:

1. `fix-*-course-filters-vm.sh`
2. `restart-php-fpm-vm.sh`
3. `post-deploy-stabilize-vm.sh`
4. `verify-moodle-web-health.sh`
5. `verify-cert-course-pages.sh` (recover workflow)

## Seed script behaviour (idempotent)

1. Import certmaster objectives from CSV (skip existing)
2. Ensure course in **Certifications** category (`SEC701`, `NET009`, `APLUS`)
3. Create domain sections + named section labels
4. Upsert `mod_page` per objective (CyberKraft HTML or fallback stub)
5. Import GIFT → question category per course
6. Dedupe bank → link questions to objectives
7. Sync **Domain N Knowledge Check** quizzes (curated 1 Q / objective)
8. Ensure manual enrol instance

## Environment variables (VM)

| Variable | Use |
|----------|-----|
| `PLUGINS_REPO_DIR` | `/opt/understandtech-plugins` |
| `SEC701_COURSE_ID` | Override course id (staging=2, prod=3) |
| `SKIP_CLEANUP` | Skip duplicate cleanup when set |

## Post-deploy integration

`scripts/post-deploy-stabilize-vm.sh` also runs:

- `cleanup-cert-knowledge-checks.php all`
- `seed-study-plan-block.php`
- Filter disable + PHP-FPM recycle

## Adding new activity types to seed

1. Add helper functions mirroring `security_plus_sync_quiz()` / `security_plus_upsert_page()`
2. Bump no Moodle plugin version unless plugin PHP changes
3. Extend `-vm.sh` wrapper if new env vars needed
4. Add verify step to `verify-cert-course-pages.sh` or track-specific check
5. Wire into `seed-*.yml` workflow after seed step
