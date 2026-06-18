# Lab Types by Track

When labs apply per domain/objective for SEC701, NET009, and APLUS. Use with the decision matrix in `SKILL.md`.

**Legend:** ✅ Phase 1 lab target · 🔶 Phase 1 scenario-only (ctfflag + HTML) · ⏳ Phase 2+ (LTI/live env) · ➖ Lesson/KC sufficient

## SEC701 — Security+ SY0-701

**Phase 1 target:** 3 `mod_ctfflag` labs (white paper + launch-targets)

| Lab | Domain | Objectives | Modality | Pattern |
|-----|--------|------------|----------|---------|
| Lab 1: SIEM alert triage | security_operations (4) | sy701_4_4, sy701_4_9 | 🔶 ctfflag | Parse alert, derive IOC prefix flag |
| Lab 2: Phishing analysis | threats_vulns (2) | sy701_2_2, sy701_2_4 | 🔶 ctfflag | Email headers, campaign ID flag |
| Lab 3: Firewall rule review | security_architecture (3) | sy701_3_2 | 🔶 ctfflag | Rule set ID from policy table |

### Domain lab relevance (SEC701)

| Domain | Weight | Lab relevance | Rationale |
|--------|--------|---------------|-----------|
| general_concepts (1) | 11% | ➖ | Conceptual; KC + `_scenario` sub-lessons |
| threats_vulns (2) | 22% | ✅ Lab 2 | Malicious activity indicators need artifact analysis |
| security_architecture (3) | 25% | ✅ Lab 3 | "Apply security principles" — config review |
| security_operations (4) | 28% | ✅ Lab 1 | SIEM triage is core SecOps job skill |
| program_management (5) | 14% | ➖ | Governance/risk — case studies in lessons |

### Phase 2+ expansion (SEC701)

| Lab type | Environment | Portfolio output |
|----------|-------------|------------------|
| KQL threat hunt | Azure Sentinel tenant | Hunt report PDF |
| Incident response timeline | Sentinel + Defender | IR documentation |
| Vulnerability prioritization | Scan results (synthetic) | Remediation plan |

## NET009 — Network+ N10-009

**Phase 2 target:** GNS3/EVE-NG + browser simulator (white paper §2.5). Phase 1: scenario HTML labs where matrix justifies.

| Lab (proposed) | Domain | Objectives | Phase | Pattern |
|----------------|--------|------------|-------|---------|
| Subnetting challenge | network_fundamentals (1) | n10009_1_7 | 🔶 | Interactive calculator + ctfflag |
| PCAP analysis | network_ops (3) | n10009_3_2 | 🔶 | Sanitized capture excerpt |
| VLAN/switch config review | network_impl (2) | n10009_2_2 | 🔶 | Config snippet + flag |
| Firewall ACL review | network_security (4) | n10009_4_3 | 🔶 | Rule table analysis |
| Troubleshooting ticket | network_troubleshoot (5) | n10009_5_1–5_5 | 🔶 | Decision tree + root cause flag |

### Domain lab relevance (NET009)

| Domain | Lab relevance | Rationale |
|--------|---------------|-----------|
| network_fundamentals (1) | ✅ Subnetting | Procedural addressing — hands-on justified |
| network_impl (2) | ✅ Config review | "Configure switching" — Phase 1 simulated |
| network_ops (3) | ✅ Monitoring/PCAP | Tool output interpretation |
| network_security (4) | ✅ ACL review | Defense technique application |
| network_troubleshoot (5) | ✅ Ticket triage | Methodology application |

### Phase 2+ expansion (NET009)

| Lab type | Environment | Portfolio output |
|----------|-------------|------------------|
| Multi-router OSPF | GNS3/EVE-NG via LTI | Topology diagram + config export |
| Wireless survey | Simulator | Site survey notes |
| IPv6 deployment | Virtual topology | Address plan document |

## APLUS — CompTIA A+ 220-1101/1102

**Phase 2 target:** Browser-based VM (white paper §2.5). Phase 1: hardware ID and troubleshooting scenarios in HTML.

| Lab (proposed) | Exam | Domain | Objectives | Pattern |
|----------------|------|--------|------------|---------|
| Component identification | 1101 | hardware (3) | ap1101_3_1–3_4 | Spec table → part ID flag |
| Mobile device diagnosis | 1101 | mobile_devices (1) | ap1101_1_1, ap1101_1_3 | Symptom → resolution flag |
| SOHO network setup review | 1101 | networking (2) | ap1101_2_6 | Config checklist flag |
| OS troubleshooting tree | 1102 | operating_systems | ap1102_* troubleshoot | Branching scenario |
| Security hygiene audit | 1102 | security (2) | ap1102_2_* | Settings review flag |

### Domain lab relevance (APLUS)

| Domain (1101) | Lab relevance | Rationale |
|---------------|---------------|-----------|
| mobile_devices | ✅ | "Given a scenario" hardware/connectivity |
| networking | 🔶 SOHO review | Simulated config sufficient Phase 1 |
| hardware | ✅ Component ID | Spec-based identification |
| virtualization | ➖ | Conceptual — lessons sufficient |
| hw_net_troubleshooting | ✅ | 6-step methodology application |

| Domain (1102) | Lab relevance | Rationale |
|---------------|---------------|-----------|
| operating_systems | ✅ | Troubleshooting trees |
| security | ✅ | Settings/permission review |
| software_troubleshooting | ✅ | Scenario-based diagnosis |
| operational_procedures | ➖ | Procedures in lessons |

## Cross-track decision quick reference

| Objective language | Typical verdict |
|--------------------|-----------------|
| "Given a scenario, analyze/apply/troubleshoot" | Hands-on lab |
| "Compare and contrast" / "Summarize" | Lesson + KC |
| "Explain the importance of" | Lesson + `_scenario` |
| Requires live vendor tenant | Defer Phase 2 (document in memo) |

## Launch alignment

| Track | Phase 1 labs | Current repo |
|-------|--------------|--------------|
| SEC701 | 3 | 1 HTML (`lab-1-siem-triage.html`), 1 seeded ctfflag |
| NET009 | 0 (Phase 2 parity) | No `content/network-plus/labs/` yet |
| APLUS | 0 (Phase 2 parity) | No `content/a-plus/labs/` yet |

Close SEC701 Labs 2–3 before NET009/APLUS lab expansion unless research memo prioritizes otherwise.
