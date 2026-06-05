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
- Skool-style layout toggle

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
| `core/toast` | Notification styling |
| `block_xp/main` | Leaderboard widget (requires block_xp) |

## Customization

Adjust `scss/preset/default.scss` for layout tokens and `scss/post.scss` for widget styling. Purge theme caches after changes (**Site administration → Development → Purge all caches**).
