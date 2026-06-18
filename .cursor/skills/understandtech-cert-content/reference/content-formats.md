# Content Formats

## Directory layout (per track)

```
content/<track>/
├── <exam>-objectives.csv      # certmaster objective import
├── sources.json               # CyberKraft / ebook paths (SEC701)
├── lessons/<code>.html        # mod_page body (required for seed)
├── diagrams/<code>.html       # optional SVG/HTML figures
├── course-notes/<code>.html   # optional instructor notes (not seeded by default)
├── supplements/<code>.html    # optional enrichment
├── snippets/                  # reusable HTML fragments
├── <exam>-quiz.gift           # 1 MCQ per objective (required)
├── <exam>-quiz-extra.gift     # expansion bank
├── <exam>-quiz.manifest.json  # optional build metadata (NET009, APLUS)
└── practice-exam-*.gift       # (planned) full-length exams
```

## Objectives CSV

```csv
cert_shortname,domain_shortname,objective_shortname,objective_fullname
security_plus_sy0_701,general_concepts,sy701_1_1,"Compare and contrast various types of security controls"
```

Imported by seed script via `local_certmaster` CSV importer. Domain shortnames must match seed script `$domainsection` map.

## Lesson HTML

**Required wrapper:**

```html
<div class="ut-lesson-content">
<h3>Exam objective 1.1</h3>
<p><strong>Official objective title</strong></p>
<div class="ut-lesson-body">
  <!-- prose, lists, tables -->
</div>
</div>
```

**Diagrams:** place in `diagrams/<code>.html` using classes `ut-lesson-diagram`, `ut-svg-figure`. Seed loaders merge into lesson when present.

**Stream video embed** (PHP filtered page or seed-time injection):

```php
require_once($CFG->dirroot . '/local/certmaster/lib.php');
$videoid = 'STREAM_UID_FROM_DASHBOARD';
echo local_certmaster_render_stream_player($videoid);
```

Videoid string must appear in stored page content for JWT refresh authorization.

## GIFT question bank

**Objective-linked MCQ** (required for Knowledge Checks):

```gift
::sy701_2_3 Vulnerability types::A buffer overflow is best categorized as which type?{
=Application
~Physical
~Policy
~Environmental
}
```

Rules:

- Objective tag in name: `sy701_X_Y`, `n10009_X_Y`, or `ap1101_X_Y` / `ap1102_X_Y`
- Exactly one `=` correct answer; `~` distractors
- Escape `{`, `}`, `~` in text (generator scripts handle this)
- Avoid duplicate question names in bank (dedup lib removes by name)

**Practice exam questions** (separate namespace):

```gift
::pe1_q001 Mixed domain sample::Question stem here?{
=Correct
~Wrong
~Wrong
~Wrong
}
```

Do **not** use `sy701_*` tags on practice exam-only questions unless also mapped to objectives intentionally.

## Question → objective linking

Seed scripts call `security_plus_link_questions_to_objectives()` (or track equivalent) which parses objective tags from question **names** and writes `certmaster_question_objective`.

Knowledge Check curation: `ut_curate_knowledge_check_questions()` picks **one question per objective** (prefers `-practice-bank` over `-sg` suffixes).

## Sub-lesson naming (expansion)

When adding pages beyond blueprint objectives:

| Pattern | Page title example |
|---------|-------------------|
| `sy701_1_1` | `SY0-701 1.1: Compare and contrast...` |
| `sy701_1_1_scenario` | `SY0-701 1.1 Scenario: Security controls in practice` |
| `sy701_1_1_exam` | `SY0-701 1.1 Exam focus: Control categories` |

Extend seed script loop or add secondary manifest JSON listing extra pages per section.
