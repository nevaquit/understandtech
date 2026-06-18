# Research Sources

Authoritative sources for certification content research. Prefer primary sources over blogs or brain dumps.

## Official exam blueprints (required)

| Exam | Blueprint / objectives | Notes |
|------|------------------------|-------|
| SY0-701 Security+ | [CompTIA Security+](https://www.comptia.org/certifications/security) | 5 domains; ~90 questions; PBQ + MCQ |
| N10-009 Network+ | [CompTIA Network+](https://www.comptia.org/certifications/network) | 5 domains; networking implementation focus |
| A+ 220-1101 / 1102 | [CompTIA A+](https://www.comptia.org/certifications/a) | Split Core 1 / Core 2; hardware + software |

**Repo source of truth for objective shortnames:** `content/<track>/*-objectives.csv` — always cross-check generated content against CSV rows, not memory.

### Blueprint extraction checklist

- [ ] Domain numbers, names, and **weight percentages**
- [ ] Objective IDs and **verbatim** objective text
- [ ] Exam length, passing score, item types (MCQ, PBQ, performance-based)
- [ ] Retirement / version date of blueprint PDF or web page
- [ ] Mapping: each objective → planned lesson codes + question count

## Framework and vendor supplements

Use by track and domain. Cite edition/version and retrieval date.

### Security+ (SY0-701)

| Source | Use for |
|--------|---------|
| [NIST Cybersecurity Framework (CSF) 2.0](https://www.nist.gov/cyberframework) | Governance, risk, program management domains |
| [NIST SP 800-53 Rev. 5](https://csrc.nist.gov/publications/detail/sp/800-53/rev-5/final) | Control families, security architecture |
| [CISA Cybersecurity Resources](https://www.cisa.gov/topics/cybersecurity-best-practices) | Threat landscape, incident response |
| [MITRE ATT&CK](https://attack.mitre.org/) | Threats, TTPs, detection, SecOps scenarios |
| Vendor docs (Microsoft, AWS, Cisco security guides) | Tool-specific architecture and operations |

### Network+ (N10-009)

| Source | Use for |
|--------|---------|
| IETF RFCs (e.g. TCP/IP, DNS, DHCP) | Protocol behavior |
| IEEE 802 standards summaries | Ethernet, wireless |
| Vendor neutral topology references | Subnetting, VLANs, routing |
| Cloud networking docs (AWS VPC, Azure VNet) | Modern implementation objectives |

### A+ (220-1101 / 220-1102)

| Source | Use for |
|--------|---------|
| Vendor hardware specs (form factors, interfaces) | Core 1 hardware |
| OS vendor documentation (Windows, Linux, macOS) | Core 2 software, troubleshooting |
| CompTIA troubleshooting methodology | Operational procedures |

## Repo gap analysis

Before generating, inventory existing assets:

```bash
# Lessons per track
ls content/security-plus/lessons/*.html | wc -l
ls content/network-plus/lessons/*.html | wc -l
ls content/a-plus/lessons/*.html | wc -l

# Objectives (CSV rows minus header)
tail -n +2 content/security-plus/sy0-701-objectives.csv | wc -l

# GIFT items
rg -c '^::' content/security-plus/sy0-701-quiz.gift content/security-plus/sy0-701-quiz-extra.gift

# Sub-lesson suffix coverage
ls content/security-plus/lessons/*_scenario.html 2>/dev/null | wc -l
ls content/security-plus/lessons/*_exam.html 2>/dev/null | wc -l

# Practice exams
ls content/security-plus/practice-exam-*.gift 2>/dev/null
```

Compare counts to [launch-targets.md](../../understandtech-cert-content/reference/launch-targets.md) and white paper Phase 1–2 targets (`docs/white-paper.md` §5).

### Gap memo template

```markdown
## Gap memo: [TRACK] [DOMAIN or SCOPE]
Date: YYYY-MM-DD
Researcher: [agent/human]

### Targets (white paper / launch-targets)
- Lessons: current X → target Y
- Questions: current X → target Y
- Practice exams: current X → target 3
- Labs: current X → target 3

### Objectives lacking sub-lessons
| Objective | Has _core | Needs _scenario | Needs _exam |
|-----------|-----------|-----------------|-------------|
| sy701_2_3 | yes | yes | yes |

### Objectives under question quota (~14/objective)
| Objective | Current MCQs | Delta needed |
|-----------|--------------|--------------|
| sy701_2_3 | 3 | +11 |

### Planned artifacts this sprint
- [ ] sy701_2_3_scenario.html
- [ ] sy701_2_3_exam.html
- [ ] 11× GIFT in sy0-701-quiz-extra.gift
```

## Citation discipline

Every research memo entry:

```markdown
### Source: [Short title]
- URL: https://...
- Version/edition: e.g. SY0-701 exam objectives v3.0, NIST CSF 2.0
- Retrieved: YYYY-MM-DD
- Relevant objectives: sy701_4_2, sy701_4_3
- Key takeaways: (bullet list for generation)
```

**In generated lesson HTML:** attribute frameworks in prose (“per NIST CSF 2.0…”) — do not paste long copyrighted text. Paraphrase and teach.

## CyberKraft / legacy extraction

When `content/<track>/sources.json` lists CyberKraft or ebook paths, run extract scripts **after** research confirms alignment with current blueprint:

```bash
node scripts/extract-security-plus-lessons.mjs
node scripts/extract-security-plus-diagrams.mjs
node scripts/extract-security-plus-course-notes.mjs
```

Treat extracted HTML as **draft** — verify against SY0-701/N10-009/A+ objectives before committing.
