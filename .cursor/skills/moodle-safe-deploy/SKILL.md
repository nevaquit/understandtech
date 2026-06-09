# Moodle Safe Deploy

Prevents production breakage when pushing `moodle-plugins/` changes to understandtech.app.

## Why deploys broke the site

| Failure | Cause | Permanent fix |
|---------|-------|---------------|
| `Call to undefined method moodle_page::add_body_attributes()` | API from Moodle >4.5 used on 4.5.12 | `validate-moodle-plugin-api.php` blocklist |
| Empty course index / skeleton nav | `Y.NodeList` crash in `core/templates` | `templates_dom_patch.js` + web health |
| Site-wide DB errors after deploy | Stale PHP-FPM workers after `reload` | **Always `systemctl restart php8.3-fpm`** (never reload) |
| DB errors after deploy | www-data cannot chdir into `/var/www/moodle` | `fix-moodle-dir-permissions-vm.sh` + `fix-moodle-chdir-quick-vm.sh` |
| Dashboard Timeline skeleton loaders | AMD race after cache purge | `timeline_fallback.js` + health retries on `/my/` |
| Course 3 permission error | Users not enrolled after seed/DB recovery | `enroll-sec701-default-users.php` in every deploy/recover |
| Theme drift after partial recovery | Theme not re-synced from monorepo | `sync-theme-understandtech-vm.sh` in stabilize path |
| ~35 min broken window | 5-min cron only | 3-min cron + post-deploy `workflow_run` trigger |
| Rollback left site worse | Maintenance disabled before health passed | Rollback → `ensure-origin-healthy-vm.sh` → health gate |

## CI gates (run before deploy)

```bash
php scripts/validate-moodle-plugin-api.php   # Moodle 4.5 API + AMD build files
php -l moodle-plugins/**/*.php               # syntax (deploy.yml validate job)
```

**Rules when editing theme/plugins:**

1. Never call `$page` / `$PAGE` methods not in `scripts/moodle-45-page-api-allowlist.txt`
2. Never add methods in `scripts/moodle-45-page-api-blocklist.txt`
3. Every `amd/src/*.js` needs a matching `amd/build/*.min.js`
4. On course/incourse pages: use `js_amd_inline`, not `js_call_amd` in `lib.php` / hooks
5. Bump `version.php` on every plugin change

## PHP-FPM rule (CRITICAL)

**Never use `systemctl reload php8.3-fpm` in deploy or recovery paths.**

`reload` keeps old workers alive; they retain stale DB/bootstrap state and cause
`Error reading from database` site-wide after plugin sync or config changes.

Always use `systemctl restart php8.3-fpm`.

Scripts that enforce this: `fix-moodle-chdir-quick-vm.sh`, `fix-moodle-dir-permissions-vm.sh`,
`apply-php-fpm-pool-vm.sh`, `moodle-upgrade-direct-pg.sh`, `post-deploy-stabilize-vm.sh`.

## Deploy pipeline (`.github/workflows/deploy.yml`)

1. **Validate** on GitHub-hosted runner (blocks deploy)
2. **Record pre-deploy SHA** on VM (`/tmp/understandtech-pre-deploy-sha`)
3. **Enable maintenance** during sync
4. Rsync plugins → purge caches → **`restart` PHP-FPM** → upgrade
5. Fix permissions → chdir verify → **`restart` PHP-FPM** again
6. **`post-deploy-stabilize-vm.sh`** — SEC701 enrolment, theme sync, permissions
7. **`verify-moodle-web-health.sh`** — strict, with retries (login, `/my/` timeline, course 3)
8. **`smoke-test-deployment.sh`**
9. **Disable maintenance** only if steps 7–8 pass
10. **On failure:** rollback → `ensure-origin-healthy-vm.sh` → maintenance stays ON if health fails
11. **Origin health check** auto-runs via `workflow_run` after deploy completes

## Recovery scripts (idempotent)

| Script | When |
|--------|------|
| `ensure-origin-healthy-vm.sh` | Full recovery: DB, PHP-FPM, enrol, theme, strict health |
| `post-deploy-stabilize-vm.sh` | After deploy, seed, or partial fix |
| `sync-theme-understandtech-vm.sh` | Theme drift / partial recovery |
| `enroll-sec701-default-users.php` | After seed, DB recovery, or deploy |
| `rollback-deploy-vm.sh` | Plugin rollback only — caller must run ensure-origin-healthy |
| `verify-moodle-web-health.sh` | Deploy gate, origin-health, recover, seed |

## Monitoring (`.github/workflows/origin-health.yml`)

- Cron every **3 minutes**
- Auto-triggers after **Deploy Moodle Plugins** completes
- On failure: runs `ensure-origin-healthy-vm.sh` (DB + PHP-FPM + enrol + theme + health)
- Maintenance disabled **only on success**

## Operator checklist before pushing plugin changes

```
- [ ] php scripts/validate-moodle-plugin-api.php
- [ ] AMD build/*.min.js updated for every amd/src change
- [ ] version.php bumped for every changed plugin
- [ ] No new $PAGE->method() without allowlist entry
- [ ] PR validate job green; merge to main
- [ ] Wait for Deploy workflow green (maintenance → health → smoke)
- [ ] Confirm Origin health check passes within 3 min (auto after deploy)
- [ ] Spot-check: login, /my/ dashboard timeline, course SEC701 (id=3)
```

## Operator checklist after manual VM intervention

```
- [ ] sudo bash /opt/understandtech-plugins/scripts/ensure-origin-healthy-vm.sh
- [ ] Do NOT disable maintenance until verify-moodle-web-health.sh exits 0
- [ ] Never reload PHP-FPM — always restart
- [ ] Re-run enroll-sec701-default-users.php if users report course access errors
```

## Manual recovery

```bash
# Full idempotent recovery (preferred)
sudo bash /opt/understandtech-plugins/scripts/ensure-origin-healthy-vm.sh

# Plugin-only rollback (then run ensure-origin-healthy)
sudo bash /opt/understandtech-plugins/scripts/rollback-deploy-vm.sh
sudo bash /opt/understandtech-plugins/scripts/ensure-origin-healthy-vm.sh

# Or via GitHub Actions: Recover origin DB / Origin health check (workflow_dispatch)
```
