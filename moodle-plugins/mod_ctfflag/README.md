# mod_ctfflag

Capture-the-flag style lab activity module for hands-on certification labs.

## Flag submission

Learners submit flags on `view.php`. Teachers configure a PCRE pattern (`expected_flag_regex`); submissions are validated with `mod_ctfflag\local\flag_validator` and **never stored in plain text** — only success/failure is logged in `mdl_ctfflag_submissions`.

On success the activity fires `\mod_ctfflag\event\flag_submitted`, updates completion when enabled, posts `1.0` to the gradebook, and awards XP via `ctfflag_award_xp()` → `local_gamification\api::award_xp()` when Level Up XP (`block_xp`) is installed on the site.

## XP integration

| Component | Role |
|-----------|------|
| `xp_award` field | Per-activity XP amount (default 50) |
| `ctfflag_award_xp()` in `lib.php` | Calls `local_gamification\api::award_xp()` after a correct flag |
| `block_xp` (Level Up XP) | Stores learner XP on the VM (third-party, not in this repo) |

Configure relative XP tiers in Level Up XP admin; lab submissions use the **Large** tier per theme README.

## Readiness integration

`local_certmaster` listens for `flag_submitted` toward future objective mastery recalculation.
