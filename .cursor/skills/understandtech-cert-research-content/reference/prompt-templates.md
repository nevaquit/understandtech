# Prompt Templates

Copy-paste prompts for research and generation phases. Replace `[BRACKET]` placeholders.

## 1. Domain research prompt

```markdown
Research CompTIA [EXAM] Domain [N]: [DOMAIN NAME] for understandtech.app course [COURSE].

Requirements:
1. Fetch official objective text for every objective in domain [N] from the current exam blueprint.
2. List domain weight % and how it maps to ~[COUNT] questions in a 90-question practice exam.
3. For Security+: map key topics to MITRE ATT&CK tactics/techniques and NIST CSF 2.0 categories where relevant.
4. For Network+: cite relevant RFCs or IEEE concepts per objective.
5. Compare against repo file content/[TRACK]/lessons/ — list objectives missing _scenario or _exam sub-lessons.
6. Compare GIFT counts per objective in content/[TRACK]/*-quiz*.gift — list objectives under ~14 questions.

Output a gap memo with:
- Source table (URL, version, retrieval date)
- Per-objective: key concepts, common exam traps, recommended sub-lessons
- Question deficit table

Do not write lesson HTML yet. Research only.
```

## 2. Single-objective deep dive

```markdown
Deep research: [OBJECTIVE_SHORTNAME] — "[OBJECTIVE_FULLNAME]"

Sources to consult (cite all):
- CompTIA [EXAM] objectives (current version)
- [FRAMEWORK: NIST/CISA/ATT&CK/RFC as applicable]
- Repo existing lesson: content/[TRACK]/lessons/[CODE].html (summarize gaps)

Deliver:
1. Concept outline (H4 sections for lesson body)
2. One realistic _scenario fictional case (organization name, constraint, decision point)
3. Exam trap notes for _exam sub-lesson (no correct answers to our GIFT items)
4. 5 original MCQ stems with topics for distractors (do not write full GIFT yet)

Constraints: original content; no copied exam items; no flag or answer leakage.
```

## 3. Lesson outline → HTML generation

```markdown
Generate lesson HTML for content/[TRACK]/lessons/[CODE].html using the research memo below.

Format:
- Root: <div class="ut-lesson-content">
- h3: Exam objective X.Y
- strong: verbatim objective title
- div.ut-lesson-body: prose sections from memo
- Use ut-lesson-diagram placeholders if diagrams needed
- Brand accents only: navy #0B1F3A, gold #C9A227, teal #1A8A7D

Type: [_core | _scenario | _exam]

Research memo:
[PASTE MEMO]

Constraints:
- No quiz answers or lab flags
- Attribute frameworks in prose
- Match tone/length of existing sy701_1_1.html
```

## 4. Batch GIFT generation

```markdown
Write [COUNT] original GIFT MCQs for objective [OBJECTIVE_SHORTNAME]: "[OBJECTIVE_FULLNAME]".

File target: content/[TRACK]/[EXAM]-quiz-extra.gift

Format per question:
::[PREFIX]_X_Y [Short title]::[Stem]?{
=[Correct]
~[Distractor]
~[Distractor]
~[Distractor]
}

Rules:
- [PREFIX] = sy701 | n10009 | ap1101 | ap1102
- Plausible distractors from research memo
- Vary difficulty: recall, application, analysis
- No duplicate stems; no copied CompTIA items
- Escape special GIFT characters

Research memo:
[PASTE MEMO]
```

## 5. Practice exam question set

```markdown
Author [90] practice exam questions for [EXAM] Practice Exam [N].

Namespace: ::pe[N]_q001:: through ::pe[N]_q090::

Distribution (SY0-701 example):
- Domain 1: 11 questions
- Domain 2: 20
- Domain 3: 16
- Domain 4: 25
- Domain 5: 18

Requirements:
- Mixed difficulty; scenario-based stems where appropriate
- Do NOT reuse ::sy701_*:: names
- Original content only
- Output single GIFT file content/[TRACK]/practice-exam-[N].gift

After generation, validate count:
rg -c '^::pe[N]_q' practice-exam-[N].gift
```

## 6. Lab scenario intro (no flag)

```markdown
Design mod_ctfflag Lab [N] for SEC701 Domain [D]: [TITLE].

Deliver:
1. Activity name: Lab [N]: [action title]
2. Intro HTML (scenario + numbered steps) — NO flag value
3. expected_flag_regex pattern only (e.g. UT\{[A-F0-9]{8}\})
4. Learning objectives mapped to sy701_X_Y codes
5. Hints that guide investigation without giving the flag

Scenario theme: [e.g. SIEM alert triage, phishing analysis, firewall review]

Constraints:
- Flag derived by learner in lab environment
- No UT{...} string in intro HTML
- Align with white paper Phase 1 Security+ labs
```

## 7. Post-generation review prompt

```markdown
Review these newly generated certification assets for understandtech.app:

Files:
[LIST PATHS]

Check:
1. Objective alignment vs [TRACK]-objectives.csv
2. AI tutor answer/flag leakage
3. GIFT format and naming conventions
4. HTML ut-lesson-content wrapper
5. Originality (not copied exam content)

Output: pass/fail per file with specific line-level fixes.
```
