# theme_understandtech

Boost child theme for [understandtech.app](https://understandtech.app) — Skool-inspired community learning UI with navy, gold, and teal branding.

## Brand palette (defaults)

| Token | Hex |
|-------|-----|
| Navy | `#0B1F3A` |
| Gold | `#C9A227` |
| Teal | `#1A8A7D` |

Typography: Rajdhani (headings), Source Serif 4 (body), Share Tech Mono (code) via Google Fonts.

## Installation

1. Copy this directory to `{moodleroot}/theme/understandtech/`
2. Visit **Site administration → Notifications** to install the plugin
3. Run `php admin/cli/upgrade.php --non-interactive`
4. Enable at **Site administration → Appearance → Themes → Theme selector**

## Admin settings

**Site administration → Appearance → Themes → UnderstandTech**

- Brand colours (navy, gold, teal)
- Custom logo upload
- **Enable Skool-style layout** — adds `ut-skool-enabled` body class for two-pane lesson grids, dashboard cards, and community feed styling

## Skool-style course layout

Two-pane lesson view (media left, course index right) uses the theme override `core_course/single_activity` wrapped in `.ut-skool-layout`.

1. Enable **Skool-style layout** in theme settings (on by default).
2. For each certification track course, set **Course format** to **Single activity format** (`format_singleactivity`).
   - **Course administration → Edit settings → Format → Single activity format**
   - Choose the primary lesson activity (Page, URL, or custom module).
3. Purge theme caches after changing format or SCSS.

Container queries on `.ut-skool-layout` collapse the grid to a single column below 48rem container width (and below 992px viewport).

## Development

```bash
# From Moodle dirroot (with npm/grunt installed)
npx grunt scss --themes=understandtech
vendor/bin/phpcs --standard=moodle theme/understandtech
```

Enable **Theme designer mode** while iterating on SCSS.

## Screenshots

Placeholder — capture after Moodle install:

- Dashboard with card layout
- Login page (centred card)
- Course lesson two-pane view

## Template overrides

| Template | Purpose |
|----------|---------|
| `core/loginform` | Centred Skool-style login |
| `core_course/single_activity` | Two-pane lesson layout |
| `core/toast` | Skool-style notifications (see below) |
| `block_xp/main` | Leaderboard widget (requires block_xp) |

### `core/notification_popup` → `core/toast` (intentional)

The playbook references a `core/notification_popup` override for Skool-style toasts. Moodle 4.5 surfaces user notifications through **`core/toast`** (Bootstrap 5 toast stack), not a separate popup template. This theme therefore overrides `core/toast` with `.ut-notification-toast` styling instead of adding a redundant `notification_popup` template.

Use `core/toast` for milestone unlocks (e.g. add class `ut-milestone-toast` server-side when surfacing Level Up XP unlocks).

## Gamification (Level Up XP)

When `block_xp` is installed on the production VM, the theme overrides `block_xp/main` with `.ut-leaderboard` styling and milestone toasts via `core/toast` (add class `ut-milestone-toast` server-side when surfacing unlocks).

### XP economy calibration (configure in Level Up XP admin)

| Behavior | Relative XP | Notes |
|----------|-------------|-------|
| Community post | Small, frequent | Skool-like feed activity |
| Peer support (forum answer) | Substantial | Reward helping others |
| Lesson completion | Moderate | Core progression |
| Lab submission | Large | Tie to `mod_ctfflag` when deployed |
| Practice exam pass | Largest | Cert readiness milestones |

Award XP via Moodle **event observers** in local plugins (`db/events.php`), not theme JavaScript. Readiness thresholds should reference `local_certmaster\api::get_user_readiness()`, not raw quiz attempt counts.

## Customization

Adjust `scss/preset/default.scss` for layout tokens and `scss/post.scss` for widget styling. Purge theme caches after changes (**Site administration → Development → Purge all caches**).
