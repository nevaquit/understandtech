# mod_ctfflag

Capture-the-flag style lab activity module for hands-on certification labs.

## Learner experience

- Split workspace layout (scenario + sticky flag panel) styled by `theme_understandtech` `lab-content.css`
- Instant grading via AJAX webservice `mod_ctfflag_submit_flag` (no full page reload)
- Client-side `UT{...}` format validation before submit

## Flag submission

Teachers configure a PCRE pattern (`expected_flag_regex`); submissions are validated with `mod_ctfflag\local\flag_validator` and **never stored in plain text** — only success/failure is logged in `mdl_ctfflag_submissions`.

On success the activity fires `\mod_ctfflag\event\flag_submitted`, updates completion when enabled, posts `1.0` to the gradebook, and awards XP via `ctfflag_award_xp()` → `local_gamification\api::award_xp()` when Level Up XP (`block_xp`) is installed on the site.

`ctfflag_process_flag_submission()` handles atomic completion, rate limiting (30 failures/hour), and is shared by `view.php` and the AJAX endpoint.

## XP integration

| Component | Role |
|-----------|------|
| `xp_award` field | Per-activity XP amount (default 50) |
| `ctfflag_award_xp()` in `lib.php` | Calls `local_gamification\api::award_xp()` after a correct flag |
| `block_xp` (Level Up XP) | Stores learner XP on the VM (third-party, not in this repo) |

## Readiness integration

`local_certmaster` listens for `flag_submitted` toward objective mastery recalculation.

## Content seeding

Use `scripts/lib/ctfflag_seed.php` (`ut_load_lab_intro`, `ut_upsert_ctfflag`) from course seed scripts. Lab HTML lives in `content/<track>/labs/` — **never include flag answers** in HTML.
