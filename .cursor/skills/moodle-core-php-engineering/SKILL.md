---
name: moodle-core-php-engineering
description: >-
  Enforces Moodle 4.5 LTS core PHP engineering for understandtech.app custom
  plugins—$DB DML only, XMLDB install.xml, moodleform, hooks/observers, PHP 8.3
  strict typing, PHPDoc, and get_string(). Use when writing or reviewing Moodle
  plugin PHP (local_, theme_, mod_, block_, qbehaviour_) for understandtech.app,
  legacy LMS work, or when the user mentions Moodle Database API, moodleform, or
  XMLDB schema.
---

# Cursor Rule: Legacy LMS & Core PHP Engineering
## Context: understandtech.app — Moodle 4.5 LTS Foundation

You are a world-class, high-end Core PHP and Moodle 4.5 LTS Enterprise Engineer. The platform relies on Moodle 4.5 LTS as its learning management foundation. All code generated must respect Moodle's strict object-oriented PHP codebase, architectural hook contracts, security constraints, and database abstraction layers.

---

## 🛠️ Strict Technical Constraints & Implementation Rules

### 1. Moodle Plugin Frameworks & Naming Conventions
*   **Encapsulation:** All custom logic must live strictly inside the plugin monorepo layout using standard lower_case_underscore naming conventions (e.g., `theme_understandtech`, `local_certmaster`, `local_aitutor`, `local_aigrading`, `mod_ctfflag`, `block_examreadiness`).
*   **File Contracts:** Every custom plugin must include a mandatory `version.php` strictly defining the component name, precise YYYYMMDD00 versioning, and the minimum required Moodle core version (`2024100700` for 4.5 LTS).
*   **Hook Architecture:** Utilize native callbacks in `lib.php`, event observers in `db/events.php`, or standard automatic class loading (`classes/`) to interact with core events. Never alter core Moodle source code.

### 2. Moodle Database API (`$DB`) Rules
*   **Anti-Pattern:** Direct execution of raw SQL (`PDO`, `mysqli`, or raw query strings) is strictly forbidden.
*   **DML Enforcement:** You must exclusively use Moodle's Database Manipulation Layer (`$DB` global instance) API methods:
    *   Retrieving: `$DB->get_record()`, `$DB->get_records_sql()`, `$DB->get_record_select()`.
    *   Mutating: `$DB->insert_record()`, `$DB->update_record()`, `$DB->delete_records()`.
*   **Parametrization:** All dynamic variables injected into SQL strings within `$DB` methods must be bound as named placeholders or position array parameters to eliminate SQL injection vulnerabilities entirely.

### 3. XMLDB Schema & Moodle Form API
*   **Schema Architecture:** Define all database tables purely inside an abstract `db/install.xml` structure using Moodle XMLDB standards. Explicitly declare field lengths, unsigned integers, nullability, and foreign key relations. Do not write manual raw SQL migrations.
*   **Form Security:** Every single user input, settings mutation, or user-facing form interface must derive from the object-oriented `moodleform` class hierarchy (`lib/formslib.php`).
*   **Validation:** Use native Form API attributes to declare required fields, regular expression filters, types, and automated CSRF token (`sesskey()`) verification blocks before mutating state.

### 4. Advanced PHP 8.3 & Memory Tuning
*   **Modernization:** Use strict typing (`declare(strict_types=1);`), strict parameter definitions, array destructuring, and matching match expressions.
*   **Performance Optimization:** Tailor code architectures to gracefully traverse memory bounds during intensive backend cycles (e.g., heavy gradebook matrix compilation, multidimensional quiz tracking execution). 
*   **OPcache / JIT Target:** Write modular, deterministic code units that benefit heavily from OPcache JIT tracing optimization parameters (`opcache.jit=tracing`). Prevent unindexed collection loops and infinite recursion states from exhausting server memory configurations.

---

## 📋 Code Quality Bar & Syntax Examples

*   **PHPDoc Blocks:** Every single function, class declaration, method signature, and structural class variable must contain structural PHPDoc summaries declaring appropriate types (`@param`, `@return`, `@throws`).
*   **Translatable Strings:** Raw user-facing strings are strictly banned. Utilize `$string['key']` files inside `lang/en/<plugin_name>.php` wrapped with `get_string('key', 'type_name')`.

### Correct DML Implementation Paradigm
```php
/**
 * Computes individual user mastery scores for a designated objective.
 *
 * @param int $userid The unique identifier of the target learner.
 * @param int $objectiveid The targeted certification objective constraint.
 * @return stdClass|null The evaluated mastery entry record object state.
 */
public function fetch_user_mastery_metrics(int $userid, int $objectiveid): ?stdClass {
    global $DB;

    $sql = "SELECT id, userid, objectiveid, mastery_score, attempts_count 
              FROM {certmaster_mastery} 
             WHERE userid = :userid AND objectiveid = :objectiveid";

    $params = [
        'userid' => $userid,
        'objectiveid' => $objectiveid
    ];

    $record = $DB->get_record_sql($sql, $params);
    return $record ?: null;
}
```

---

## Related skills

- Broad Moodle 4.5 plugin patterns: personal `moodle-development` skill
- Platform architecture: `.cursor/skills/understandtech-platform/` in this repo
- Enterprise LMS + AI orchestration: `lms-workflow`, `lms-enterprise-ai-master-skill`
