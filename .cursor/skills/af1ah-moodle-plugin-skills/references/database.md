# Database Layer

> XMLDB schema, `$DB` API, upgrade.php, transactions, and best practices.

## install.xml — Schema Definition

Use the XMLDB editor at `/admin/tool/xmldb/` to generate. Never hand-write.

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<XMLDB PATH="local/myplugin/db" VERSION="20260301">
  <TABLES>
    <TABLE NAME="local_myplugin_items" COMMENT="Plugin items">
      <FIELDS>
        <FIELD NAME="id"                TYPE="int"  LENGTH="10" NOTNULL="true" SEQUENCE="true"/>
        <FIELD NAME="courseid"          TYPE="int"  LENGTH="10" NOTNULL="true" DEFAULT="0"/>
        <FIELD NAME="userid"            TYPE="int"  LENGTH="10" NOTNULL="true" DEFAULT="0"/>
        <FIELD NAME="name"              TYPE="char" LENGTH="255" NOTNULL="true"/>
        <FIELD NAME="description"       TYPE="text" NOTNULL="false"/>
        <FIELD NAME="descriptionformat" TYPE="int"  LENGTH="2"  NOTNULL="true" DEFAULT="1"/>
        <FIELD NAME="status"            TYPE="int"  LENGTH="2"  NOTNULL="true" DEFAULT="0"/>
        <FIELD NAME="sortorder"         TYPE="int"  LENGTH="10" NOTNULL="true" DEFAULT="0"/>
        <FIELD NAME="timecreated"       TYPE="int"  LENGTH="10" NOTNULL="true" DEFAULT="0"/>
        <FIELD NAME="timemodified"      TYPE="int"  LENGTH="10" NOTNULL="true" DEFAULT="0"/>
      </FIELDS>
      <KEYS>
        <KEY NAME="primary"   TYPE="primary" FIELDS="id"/>
        <KEY NAME="fk_course" TYPE="foreign" FIELDS="courseid" REFTABLE="course" REFFIELDS="id"/>
        <KEY NAME="fk_user"   TYPE="foreign" FIELDS="userid"   REFTABLE="user"   REFFIELDS="id"/>
      </KEYS>
      <INDEXES>
        <INDEX NAME="idx_courseid_status" UNIQUE="false" FIELDS="courseid, status"/>
        <INDEX NAME="idx_userid"          UNIQUE="false" FIELDS="userid"/>
      </INDEXES>
    </TABLE>
  </TABLES>
</XMLDB>
```

### Schema Rules

- Always include `id` (auto-sequence), `timecreated`, `timemodified`
- For rich text: add both `fieldname TEXT` **and** `fieldnameformat INT(2)` (default 1 = FORMAT_HTML)
- Index every column used in WHERE, JOIN, or ORDER BY
- Foreign keys: define in XMLDB even though MySQL/MariaDB doesn't enforce all FK types — it documents relationships
- Table naming: `{plugintype}_{pluginname}_{entity}` (e.g. `local_myplugin_items`)
- Field names: lowercase, no underscores unless separating logical parts

## upgrade.php — Schema Migrations

```php
<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_myplugin_upgrade(int $oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026020100) {
        // Add a new field.
        $table = new xmldb_table('local_myplugin_items');
        $field = new xmldb_field('priority', XMLDB_TYPE_INTEGER, '4', null,
                                  XMLDB_NOTNULL, null, '0', 'status');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add a new index.
        $index = new xmldb_index('idx_priority', XMLDB_INDEX_NOTUNIQUE, ['priority']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2026020100, 'local', 'myplugin');
    }

    if ($oldversion < 2026030100) {
        // Add a new table.
        $table = new xmldb_table('local_myplugin_logs');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('itemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('action', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_item', XMLDB_KEY_FOREIGN, ['itemid'], 'local_myplugin_items', ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026030100, 'local', 'myplugin');
    }

    return true;
}
```

### Upgrade Rules

- Guard every block with `if ($oldversion < YYYYMMDDXX)`
- Always check existence before adding (field_exists, table_exists, index_exists)
- Always call `upgrade_plugin_savepoint()` at the end of each block
- Keep `install.xml` in sync — it must represent the final schema
- Never delete user data without a confirmation step / admin notification

## $DB API — Complete Reference

### Reading Data

```php
global $DB;

// Single record by conditions.
$item = $DB->get_record('local_myplugin_items', ['id' => $id], '*', MUST_EXIST);

// Multiple records.
$items = $DB->get_records('local_myplugin_items', ['courseid' => $courseid], 'timecreated DESC');

// Single field value.
$name = $DB->get_field('local_myplugin_items', 'name', ['id' => $id], MUST_EXIST);

// Count.
$count = $DB->count_records('local_myplugin_items', ['courseid' => $courseid]);

// Check existence.
$exists = $DB->record_exists('local_myplugin_items', ['id' => $id, 'status' => 1]);

// Complex SQL — ALWAYS named placeholders.
$sql = "SELECT i.*, u.firstname, u.lastname
          FROM {local_myplugin_items} i
          JOIN {user} u ON u.id = i.userid
         WHERE i.courseid = :courseid
           AND i.status = :status
      ORDER BY i.timecreated DESC";
$records = $DB->get_records_sql($sql, ['courseid' => $courseid, 'status' => 1]);

// Paginated.
$records = $DB->get_records_sql($sql, $params, $offset, $limit);

// IN clause.
[$insql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'id');
$records = $DB->get_records_sql(
    "SELECT * FROM {local_myplugin_items} WHERE id $insql",
    $params
);

// Recordset (for large result sets — iterate, don't load all into memory).
$rs = $DB->get_recordset('local_myplugin_items', ['courseid' => $courseid]);
foreach ($rs as $record) {
    // Process one at a time.
}
$rs->close(); // MUST close recordsets.
```

### Writing Data

```php
// Insert — returns new ID.
$id = $DB->insert_record('local_myplugin_items', (object) [
    'courseid'     => $courseid,
    'userid'       => $USER->id,
    'name'         => $name,
    'status'       => 0,
    'timecreated'  => time(),
    'timemodified' => time(),
]);

// Update — object MUST have 'id'.
$DB->update_record('local_myplugin_items', (object) [
    'id'           => $id,
    'name'         => $newname,
    'timemodified' => time(),
]);

// Update single field.
$DB->set_field('local_myplugin_items', 'status', 1, ['id' => $id]);

// Delete.
$DB->delete_records('local_myplugin_items', ['courseid' => $courseid]);

// Delete with SQL.
$DB->delete_records_select('local_myplugin_items', 'status = :s AND timecreated < :t', [
    's' => 0,
    't' => time() - (DAYSECS * 90),
]);
```

### Transactions

```php
$transaction = $DB->start_delegated_transaction();
try {
    $DB->insert_record('local_myplugin_items', $record1);
    $DB->insert_record('local_myplugin_logs', $log);
    $transaction->allow_commit();
} catch (\Exception $e) {
    $transaction->rollback($e); // Re-throws automatically.
}
```

### SQL Coding Style

- Use double quotes for SQL strings
- Use UPPERCASE for SQL keywords (`SELECT`, `FROM`, `WHERE`, `JOIN`, `ORDER BY`, `GROUP BY`, `HAVING`)
- Right-align SQL clause keywords for readability
- Always use `{tablename}` — prefix is added automatically
- Named placeholders only (`:param`) — NEVER concatenate user input
- Use `JOIN` instead of `INNER JOIN`; never use right joins
- Use `<>` for not-equal comparison, not `!=`
- Always use `AS` keyword for column aliases
- Never use `AS` keyword for table aliases

```php
// Correct SQL formatting:
$sql = "SELECT i.id,
               i.name,
               u.firstname AS author_first,
               COUNT(l.id) AS logcount
          FROM {local_myplugin_items} i
          JOIN {user} u ON u.id = i.userid
     LEFT JOIN {local_myplugin_logs} l ON l.itemid = i.id
         WHERE i.courseid = :courseid
           AND i.status <> :deleted
      GROUP BY i.id, i.name, u.firstname
        HAVING COUNT(l.id) > :minlogs
      ORDER BY i.sortorder ASC, i.timecreated DESC";
```

### Placeholder Reference

| Syntax | Style | When to use |
|--------|-------|-------------|
| `:named` | Named | **Preferred** — always use with >1 parameter |
| `?` | Positional | Acceptable for single-parameter simple queries |
| `$1` | Dollar | Available but rarely used |

### NEVER Do

- String concatenation in SQL (`"WHERE id = " . $id`)
- Raw mysqli / PDO calls
- Table names without `{}`
- `$DB->execute()` for SELECT queries (use `get_records_sql`)
