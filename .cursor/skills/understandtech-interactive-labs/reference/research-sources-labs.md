# Research Sources for Labs

Authoritative sources for lab scenario design, interactivity patterns, and platform integration. Cite in gap memos per cert-research discipline.

## Official blueprints (always first)

| Track | Source | Use for labs |
|-------|--------|--------------|
| SEC701 | [CompTIA Security+ SY0-701](https://www.comptia.org/certifications/security) | Domain 2/3/4 procedural objectives |
| NET009 | [CompTIA Network+ N10-009](https://www.comptia.org/certifications/network) | Troubleshooting + implementation objectives |
| APLUS | [CompTIA A+](https://www.comptia.org/certifications/a) | "Given a scenario" hardware/OS objectives |

Repo CSVs: `content/<track>/*-objectives.csv`

## Security operations lab sources (SEC701)

| Source | URL / reference | Lab application |
|--------|-----------------|-----------------|
| MITRE ATT&CK | https://attack.mitre.org/ | Map TTPs in SIEM scenarios (no live exploitation) |
| NIST CSF 2.0 | https://www.nist.gov/cyberframework | Frame IR/detection activities |
| CISA alerts | https://www.cisa.gov/news-events/cybersecurity-advisories | Realistic threat narratives (sanitize) |
| Microsoft Sentinel | https://learn.microsoft.com/en-us/azure/sentinel/ | KQL/log schema reference for synthetic alerts |
| Microsoft Defender | https://learn.microsoft.com/en-us/microsoft-365/security/defender/ | Alert field naming for scenarios |
| SANS Reading Room | https://www.sans.org/reading-room/ | Investigation methodology (cite, don't copy) |

**Phase 1 constraint:** Synthetic logs and fictional IOCs only — no live Sentinel tenant in seeds.

## Network lab sources (NET009)

| Source | URL / reference | Lab application |
|--------|-----------------|-----------------|
| Wireshark sample captures | https://wiki.wireshark.org/SampleCaptures | PCAP excerpt patterns (sanitize/rebuild) |
| RFC 1918 / CIDR | https://www.rfc-editor.org/ | Subnetting lab math |
| IEEE 802.1Q | VLAN tagging scenarios | Config review labs |
| GNS3 | https://www.gns3.com/ | Phase 2 topology design reference |
| EVE-NG | https://www.eve-ng.net/ | Phase 2 alternative |
| Cisco/docarchive | Vendor config syntax | Simulated switch/router configs |

## A+ lab sources (APLUS)

| Source | URL / reference | Lab application |
|--------|-----------------|-----------------|
| Vendor hardware specs | Dell/HP/Lenovo public spec sheets | Component ID scenarios |
| Microsoft Learn (Windows) | https://learn.microsoft.com/en-us/windows/ | Troubleshooting paths |
| Linux man pages | https://man7.org/linux/man-pages/ | CLI symptom scenarios |
| CompTIA troubleshooting methodology | A+ exam objectives | 6-step tree structure |

## Moodle platform sources

| Topic | Reference | Notes |
|-------|-----------|-------|
| Activity modules | https://docs.moodle.org/405/en/Activity_modules | mod_page, mod_ctfflag placement |
| H5P in Moodle | https://docs.moodle.org/405/en/H5P | Optional interactivity |
| LTI 1.3 | https://docs.moodle.org/405/en/LTI | Phase 2 lab gateway |
| Completion API | https://docs.moodle.org/405/en/Activity_completion | ctfflag completion |
| Gradebook API | https://docs.moodle.org/405/en/Gradebook | Lab grades → portfolio |

## understandtech.app repo sources

| Asset | Path | Role |
|-------|------|------|
| mod_ctfflag | `moodle-plugins/mod_ctfflag/` | Flag validation, XP, completion |
| local_integrations | `moodle-plugins/local_integrations/` | LTI/BBB stubs |
| block_portfolio | `moodle-plugins/block_portfolio/` | Lab artifact aggregation |
| local_certmaster | `moodle-plugins/local_certmaster/` | Objective mapping, readiness |
| local_aitutor | `moodle-plugins/local_aitutor/` | Tutor guardrails on lab pages |
| Seed scripts | `scripts/seed-*-course.php` | Lab activity provisioning |
| E2E | `tests/e2e/lab-flag.spec.ts` | Submit flow verification |
| White paper §2.5 | `docs/white-paper.md` | Lab infrastructure by track |

## Vendor sandboxes and ranges (Phase 2+ research)

Document in gap memo — **do not** wire into Phase 1 seeds:

| Provider | Use case | Integration |
|----------|----------|-------------|
| Azure Sentinel free tier / MS Learn | SEC701 live hunts | LTI + Azure AD |
| MITRE Caldera (controlled) | Detection engineering | Self-hosted — policy review required |
| TryHackMe / HackTheBox | CTF inspiration only | **No** copy flags/scenarios; methodology only |
| GNS3 marketplace | NET009 topologies | Export + LTI |
| Kasm Workspaces | A+ browser VMs | LTI (white paper Linux+/A+ pattern) |

**Policy:** No real malware, no offensive tooling instructions, no instructions to attack third-party systems.

## H5P content types (if enabled)

| H5P type | Lab use | Flag handling |
|----------|---------|---------------|
| Branching Scenario | Phishing path, troubleshooting tree | Separate ctfflag for final submission |
| Drag and Drop | Classify controls, ports, cables | No secrets in drop zones |
| Interactive Video | Concept walkthrough | Not for flag validation |
| Documentation Tool | Lab reflection export | Portfolio input |

## Citation template for lab gap memo

```markdown
## Lab research — [TRACK] [Lab name]

| Source | URL | Version/date | Retrieved |
|--------|-----|--------------|-----------|
| CompTIA SY0-701 objectives | https://... | 2024 objectives | YYYY-MM-DD |
| MITRE ATT&CK T1566 | https://attack.mitre.org/techniques/T1566/ | v14 | YYYY-MM-DD |

### Lab relevance
- Objectives: sy701_4_4, sy701_4_9
- Verdict: hands-on justified
- Modality: mod_ctfflag + ut-lab-content HTML
- Phase: 1
- Flag derivation: first 8 hex chars of SHA-256 in scenario (regex only in seed)
```
