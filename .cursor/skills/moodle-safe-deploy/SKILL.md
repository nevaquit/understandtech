# Moodle Safe Deploy

Prevents production breakage when pushing `moodle-plugins/` changes to understandtech.app.

## Why deploys broke the site

| Failure | Cause | Gate that now catches it |
|---------|-------|--------------------------|
| `Call to undefined method moodle_page::add_body_attributes()` | API from Moodle >4.5 used on 4.5.12 | `validate-moodle-plugin-api.php` blocklist |
| Empty course index / skeleton nav | `Y.NodeList` crash in `core/templates` | `templates_dom_patch.js` + web health |
| Site-wide DB errors | VM permissions / PgBouncer drift | `verify-moodle-web-health.sh` + rollback |

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

## Deploy pipeline (`.github/workflows/deploy.yml`)

1. **Validate** on GitHub-hosted runner (blocks deploy)
2. **Record pre-deploy SHA** on VM (`/tmp/understandtech-pre-deploy-sha`)
3. **Enable maintenance** during sync
4. Rsync plugins → purge caches → upgrade
5. **`verify-moodle-web-health.sh`** — login must have `logintoken`, no Exception/DB error
6. **`smoke-test-deployment.sh`**
7. **Disable maintenance** only if steps 5–6 pass
8. **On failure:** `rollback-deploy-vm.sh` restores pre-deploy SHA

## Manual recovery

```bash
# On VM or via Recover origin DB workflow
sudo bash /opt/understandtech-plugins/scripts/rollback-deploy-vm.sh

# Or full origin recovery
sudo bash /opt/understandtech-plugins/scripts/ensure-origin-healthy-vm.sh
```

## Checklist before pushing theme changes

```
- [ ] php scripts/validate-moodle-plugin-api.php
- [ ] AMD build/*.min.js updated for every amd/src change
- [ ] version.php bumped
- [ ] No new $PAGE->method() without allowlist entry
- [ ] PR / push — wait for Deploy workflow green before telling users to refresh
```
