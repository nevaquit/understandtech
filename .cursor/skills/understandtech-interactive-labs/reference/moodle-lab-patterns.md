# Moodle 4.5 Lab Patterns

Research-backed patterns for building rich interactive labs **within** understandtech.app's Moodle 4.5 LTS deployment. Moodle core is not in this repo — patterns reference custom plugins and standard activity types available on the VM.

## Architecture context

White paper §2.5 defines two integration layers:

1. **Phase 1 — In-Moodle labs:** `mod_ctfflag` for flag submission + `mod_page` for scenario/interactive content
2. **Phase 2+ — External labs:** LTI 1.3 gateway via `local_integrations`, provisioning Sentinel/GNS3/browser VMs

## Pattern 1: `mod_ctfflag` (primary — Phase 1)

**When:** Learner must derive a token from scenario analysis and submit for XP/completion/gradebook.

### Instance configuration

| Field | Purpose | Example |
|-------|---------|---------|
| `name` | Activity title | `Lab 1: SIEM alert triage` |
| `intro` | Scenario HTML (loaded from `content/<track>/labs/`) | No flag values |
| `expected_flag_regex` | PCRE validation | `UT\{[A-Fa-f0-9]{8}\}` |
| `xp_award` | Level Up XP on success | `100` |
| `completion_required` | Activity completion on correct flag | `1` |

### Validation flow

```
view.php → submit_form → flag_validator::matches(flag, regex)
  → ctfflag_submissions (success bit only, no plaintext flag storage)
  → completion + gradebook + ctfflag_award_xp() → local_gamification
  → event\flag_submitted → local_certmaster observer (readiness)
```

### Seed idempotency

`security_plus_upsert_ctfflag()` in `scripts/seed-security-plus-course.php`:

- Finds existing cm by course + activity name
- Updates intro/regex if changed; skips if unchanged
- Uses `add_moduleinfo()` for new instances

Mirror pattern for NET009/APLUS when labs ship.

### Intro HTML loading

```php
function security_plus_load_lab_intro(string $repopath, string $slug, string $fallback): string {
    $path = $repopath . '/content/security-plus/labs/' . $slug . '.html';
    // Return file contents or $fallback
}
```

Lab HTML uses `<div class="ut-lab-content">` root (distinct from lesson `ut-lesson-content`).

## Pattern 2: `mod_page` + embedded interactive HTML/JS

**When:** Drag-and-drop triage, subnet calculator, troubleshooting tree — interactivity without server-side flag logic.

### Structure

1. **Sibling activities** in course section: `mod_page` (interactive) + `mod_ctfflag` (flag from derived result)
2. **Or** embed lightweight JS inside ctfflag intro (keep JS answer-free)

### Safety rules for embedded JS

- No hardcoded flags or answer keys in source
- Sandbox: no `eval()`, no external fetch to non-allowlisted domains
- State stays client-side; flag submission still via `mod_ctfflag`
- Prefer vanilla JS or Moodle AMD module if reused across labs

### Moodle embedding

- `mod_page` content field accepts filtered HTML
- Theme `theme_understandtech` provides brand palette classes
- Test with Moodle HTML cleaner — avoid inline event handlers where filter strips them; use `addEventListener` in `<script>` blocks if allowed, or AMD

## Pattern 3: H5P (optional / future)

Moodle 4.5 ships H5P support (`filter_h5p`, `mod_h5pactivity`). White paper plugin table lists both.

**Status on understandtech.app:** Document as optional. Before authoring:

1. Verify H5P is enabled on VM (`Site administration → Plugins → Filters → H5P`)
2. Confirm content bank permissions for course creators
3. Prefer H5P for branching scenarios with **no** flag secrets in xAPI statements

**Phase 1 default:** Use static HTML + ctfflag instead of H5P to reduce admin dependency.

**Future use cases:**

- Interactive video with in-stream questions (concept reinforcement, not flag validation)
- Branching scenario (phishing analysis paths)
- Drag-and-drop classification (control types, port/protocol matching)

## Pattern 4: LTI 1.3 external lab (Phase 2+)

`local_integrations` exposes admin settings stub:

- `local_integrations/ltiissuer` — platform issuer URL for tool registration

**Intended flow (not Phase 1):**

```
Moodle course → External tool (LTI 1.3) → Lab gateway
  → Provision learner environment (Sentinel, GNS3, browser VM)
  → Return grade / completion via LTI Assignment and Grade Services
  → Optional: ctfflag for in-platform flag checkpoint after external work
```

Do not implement LTI gateway in content seeds until Phase 2 playbook prompt. Document lab design with `modality: lti-deferred` in gap memo.

## Pattern 5: Virtual lab gateways (Phase 2+)

| Track | Gateway | Moodle integration |
|-------|---------|-------------------|
| SEC701 | Azure Sentinel + Defender tenant | LTI launch + written reflection `mod_assign` |
| NET009 | GNS3 / EVE-NG topology | LTI + topology file download via `mod_resource` |
| APLUS | Browser-based Windows/Linux VM | LTI (Kasm or equivalent) |

Phase 1 ships **simulated** scenarios in HTML that teach the same analysis skills without live tenant access.

## Pattern 6: BigBlueButton live labs (Phase 2+)

`local_integrations/bbburl` setting. Use for cohort synchronous walkthroughs — not a replacement for async ctfflag labs.

## Completion and gradebook

| Requirement | Setting |
|-------------|---------|
| Activity completion | Enable on course module; `completion_required=1` on ctfflag |
| Gradebook | `ctfflag_update_grades()` posts 1.0 on success |
| Portfolio | `block_portfolio` reads completions — verify lab cm IDs appear |
| Readiness | `local_certmaster` listens to `flag_submitted` event |

## Course section placement

| Track | Recommended section | Notes |
|-------|---------------------|-------|
| SEC701 | Section 7+ `Hands-on Labs` | Lab 1 seeded in `seed-security-plus-course.php` |
| NET009 | Dedicated labs section | After domain KCs |
| APLUS | Per-core lab section | 220-1101 vs 220-1102 split |

## Moodle APIs checklist (plugin work)

When extending `mod_ctfflag`:

- `$DB` API only — no raw SQL in content scripts
- `moodleform` for forms (`mod_ctfflag_mod_form`, `submit_form`)
- `get_string()` for all user-facing strings
- Events: `\mod_ctfflag\event\flag_submitted`
- Capabilities: `mod/ctfflag:view`, `mod/ctfflag:submit`

Defer to `/moodle-development` or `moodle-core-php-engineering` for PHP changes.

## References

- [mod_ctfflag README](../../../../moodle-plugins/mod_ctfflag/README.md)
- [practice-exams-and-labs.md](../../understandtech-cert-content/reference/practice-exams-and-labs.md)
- Moodle 4.5 docs: [Activity modules](https://docs.moodle.org/405/en/Activity_modules), [H5P](https://docs.moodle.org/405/en/H5P), [LTI](https://docs.moodle.org/405/en/LTI)
