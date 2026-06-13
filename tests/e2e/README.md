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
| `E2E_COURSE_PATH` | For tutor tests | e.g. `/course/view.php?id=3` (SEC701) |
| `E2E_QUIZ_PATH` | For quiz flag / confidence | Quiz view URL on staging |
| `E2E_QUIZ_ATTEMPT_PATH` | Optional quiz flag shortcut | In-progress attempt URL |
| `E2E_CTFFLAG_PATH` | For lab flag tests | e.g. `/mod/ctfflag/view.php?id=5` |
| `E2E_CTFFLAG_VALID_FLAG` | Lab success + XP tests | GitHub secret — never commit |
| `E2E_LEADERBOARD_PATH` | XP leaderboard check | Defaults to `/my/` |

## Run

```bash
npm test
# Windows: install chromium only, single worker to avoid login rate limits
npx playwright install chromium
npx playwright test --project=chromium --workers=1
npm run test:headed   # visible browser
npm run report        # open HTML report after a run
```

Tests without credentials skip authenticated flows; the invalid-login test always runs.

## Specs

| File | Coverage |
|------|----------|
| `auth.spec.ts` | Login, logout, invalid creds, session persistence |
| `course-navigation.spec.ts` | Dashboard, optional course page |
| `frontpage.spec.ts` | Guest marketing home + logged-in members hub |
| `ai-tutor.spec.ts` | Sidebar visibility, streaming, Socratic refusal, worker outage |

| `payment-flow.spec.ts` | Stripe checkout — **deferred**; excluded from `chromium`; use `--project=chromium-stripe` with `STRIPE_TEST=1` |
| `video-playback.spec.ts` | Stream signed JWT player — set `E2E_STREAM_COURSE_PATH` |
| `quiz-confidence.spec.ts` | CertMaster confidence UI — set `E2E_QUIZ_PATH` |
| `quiz-flag.spec.ts` | Quiz question flag toggle — set `E2E_QUIZ_PATH` or `E2E_QUIZ_ATTEMPT_PATH` |
| `lab-flag.spec.ts` | CTF flag submission + optional XP — set `E2E_CTFFLAG_PATH` |

## CI

Run manually from a machine with network access to production/staging and secrets in env. A GitHub Actions workflow can be added when `STAGING_TEST_USER_*` secrets are configured.
