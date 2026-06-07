<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_aitutor_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026060803) {
        $table = new xmldb_table('aitutor_embeddings');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, null, null);
            $table->add_field('chunk_hash', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
            $table->add_field('content_text', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $table->add_field('source_type', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);
            $table->add_field('embeddingjson', XMLDB_TYPE_TEXT, null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('course_chunk', XMLDB_KEY_UNIQUE, ['courseid', 'chunk_hash']);
            $table->add_index('course_idx', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
            $dbman->create_table($table);
        }

        if ($DB->get_dbfamily() === 'postgres') {
            $pgvector = $DB->get_record_sql(
                "SELECT 1 FROM pg_extension WHERE extname = 'vector'",
                []
            );
            if (!$pgvector) {
                try {
                    $DB->execute("CREATE EXTENSION IF NOT EXISTS vector");
                } catch (\Throwable $e) {
                    debugging('pgvector extension not available: ' . $e->getMessage(), DEBUG_DEVELOPER);
                }
            }

            $columncheck = $DB->get_record_sql(
                "SELECT 1 FROM information_schema.columns
                  WHERE table_name = 'mdl_aitutor_embeddings' AND column_name = 'embedding'",
                []
            );
            if (!$columncheck) {
                try {
                    $DB->execute("ALTER TABLE {aitutor_embeddings} ADD COLUMN embedding vector(1536)");
                    $DB->execute(
                        "CREATE INDEX IF NOT EXISTS mdl_aitutoremb_embed_idx
                         ON {aitutor_embeddings} USING hnsw (embedding vector_cosine_ops)"
                    );
                } catch (\Throwable $e) {
                    debugging('pgvector column skipped: ' . $e->getMessage(), DEBUG_DEVELOPER);
                }
            }
        }

        upgrade_plugin_savepoint(true, 2026060803, 'local', 'aitutor');
    }

    return true;
}
