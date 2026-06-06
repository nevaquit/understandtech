# local_gamification

Server-side gamification hooks for understandtech.app. XP storage and leaderboards remain in **Level Up XP** (`block_xp`) on the production VM; this plugin wires Moodle events toward that economy.

## Observers

| Event | Handler | Purpose |
|-------|---------|---------|
| `\mod_quiz\event\attempt_submitted` | `observer::quiz_attempt_submitted` | Quiz completion → XP (stub) |

When `block_xp` is **not** installed, observers are no-ops. Configure the XP economy in **Level Up XP admin** when the plugin is present:

| Behavior | Relative XP |
|----------|-------------|
| Community post | Small, frequent |
| Peer support (forum answer) | Substantial |
| Lesson completion | Moderate |
| Lab submission (`mod_ctfflag`) | Large |
| Practice exam pass | Largest |

Readiness-based bonus tiers should reference `local_certmaster\api::get_user_readiness()`, not raw attempt counts.

## Installation

Copy to `{moodleroot}/local/gamification/` and run `php admin/cli/upgrade.php --non-interactive`.
