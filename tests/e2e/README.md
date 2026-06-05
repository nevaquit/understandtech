# E2E Tests (Phase 6)

Playwright TypeScript suite for understandtech.app critical paths: login, dashboard, AI tutor sidebar.

## Prerequisites

- Node.js 20+
- Test student account on the target environment

## Setup

```bash
cd tests/e2e
npm install
npx playwright install chromium
cp .env.example .env
# Edit .env with STAGING_URL, credentials, and E2E_COURSE_PATH
```

## Environment variables

| Variable | Required | Description |
|----------|----------|-------------|
| `STAGING_URL` | No | Base URL (default `https://understandtech.app`) |
| `STAGING_TEST_USER_EMAIL` | For auth/tutor | Student login email |
| `STAGING_TEST_USER_PASSWORD` | For auth/tutor | Student login password |
| `E2E_COURSE_PATH` | For tutor tests | e.g. `/course/view.php?id=2` |

## Run

```bash
npm test
npm run test:headed   # visible browser
npm run report        # open HTML report after a run
```

Tests without credentials skip authenticated flows; the invalid-login test always runs.

## Specs

| File | Coverage |
|------|----------|
| `auth.spec.ts` | Login, logout, invalid creds, session persistence |
| `course-navigation.spec.ts` | Dashboard, optional course page |
| `ai-tutor.spec.ts` | Sidebar visibility, streaming, Socratic refusal, worker outage |

Future playbook specs (video, quiz confidence, lab flag, Stripe) belong here once staging content is seeded.

## CI

Run manually from a machine with network access to production/staging and secrets in env. A GitHub Actions workflow can be added when `STAGING_TEST_USER_*` secrets are configured.
