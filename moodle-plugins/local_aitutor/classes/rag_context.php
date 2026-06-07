<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace local_aitutor;

defined('MOODLE_INTERNAL') || die();

/**
 * RAG context provider — course-scoped retrieval with pgvector or keyword fallback.
 */
class rag_context {

    /** @var string[] Patterns redacted before returning chunks to the Worker. */
    private const REDACT_PATTERNS = [
        '/UT\{[A-Za-z0-9_\-]+\}/',
        '/flag\s*[:=]\s*\S+/i',
        '/correct\s+answer\s*[:=]/i',
    ];

    /**
     * Retrieve top-k course context chunks for tutor grounding.
     *
     * @param int $courseid Moodle course id (must be positive).
     * @param string $query Learner question (normalized server-side).
     * @param int $limit Maximum chunks to return.
     * @param array|null $embedding Optional query embedding from Worker (1536-dim).
     * @return array<int, array{content: string, source_type: string}>
     */
    public static function retrieve(int $courseid, string $query, int $limit = 5, ?array $embedding = null): array {
        global $DB;

        if ($courseid <= 0) {
            throw new \invalid_parameter_exception('Invalid course id');
        }

        $query = trim($query);
        if ($query === '') {
            return [];
        }

        if ($embedding !== null && self::pgvector_available()) {
            $chunks = self::retrieve_vector($courseid, $embedding, $limit);
            if ($chunks !== []) {
                return self::redact_chunks($chunks);
            }
        }

        $chunks = self::retrieve_keyword($courseid, $query, $limit);
        return self::redact_chunks($chunks);
    }

    /**
     * @param int $courseid
     * @param array $embedding
     * @param int $limit
     * @return array<int, array{content: string, source_type: string}>
     */
    protected static function retrieve_vector(int $courseid, array $embedding, int $limit): array {
        global $DB;

        $vectorliteral = '[' . implode(',', array_map('floatval', $embedding)) . ']';
        $sql = "SELECT content_text, source_type
                  FROM {aitutor_embeddings}
                 WHERE courseid = :courseid
                   AND embedding IS NOT NULL
              ORDER BY embedding <=> :vec::vector
                 LIMIT :lim";

        $records = $DB->get_records_sql($sql, [
            'courseid' => $courseid,
            'vec' => $vectorliteral,
            'lim' => $limit,
        ]);

        return self::map_records($records);
    }

    /**
     * @param int $courseid
     * @param string $query
     * @param int $limit
     * @return array<int, array{content: string, source_type: string}>
     */
    protected static function retrieve_keyword(int $courseid, string $query, int $limit): array {
        global $DB;

        $terms = preg_split('/\s+/', strtolower($query), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $terms = array_slice(array_unique($terms), 0, 5);
        if ($terms === []) {
            return [];
        }

        $conditions = [];
        $params = ['courseid' => $courseid];
        foreach ($terms as $i => $term) {
            if (strlen($term) < 3) {
                continue;
            }
            $key = 't' . $i;
            $conditions[] = $DB->sql_like('content_text', ':' . $key, false, false);
            $params[$key] = '%' . $DB->sql_like_escape($term) . '%';
        }

        if ($conditions === []) {
            $records = $DB->get_records('aitutor_embeddings', ['courseid' => $courseid], 'timemodified DESC', 'content_text, source_type', 0, $limit);
            return self::map_records($records);
        }

        $where = 'courseid = :courseid AND (' . implode(' OR ', $conditions) . ')';
        $records = $DB->get_records_select('aitutor_embeddings', $where, $params, 'timemodified DESC', 'content_text, source_type', 0, $limit);
        return self::map_records($records);
    }

    /**
     * @param array $records
     * @return array<int, array{content: string, source_type: string}>
     */
    protected static function map_records(array $records): array {
        $chunks = [];
        foreach ($records as $record) {
            $chunks[] = [
                'content' => (string) $record->content_text,
                'source_type' => (string) $record->source_type,
            ];
        }
        return $chunks;
    }

    /**
     * @param array<int, array{content: string, source_type: string}> $chunks
     * @return array<int, array{content: string, source_type: string}>
     */
    protected static function redact_chunks(array $chunks): array {
        $out = [];
        foreach ($chunks as $chunk) {
            $content = $chunk['content'];
            foreach (self::REDACT_PATTERNS as $pattern) {
                $content = preg_replace($pattern, '[redacted]', $content) ?? $content;
            }
            if (trim($content) !== '') {
                $out[] = [
                    'content' => $content,
                    'source_type' => $chunk['source_type'],
                ];
            }
        }
        return $out;
    }

    /**
     * @return bool
     */
    public static function pgvector_available(): bool {
        global $DB;

        if ($DB->get_dbfamily() !== 'postgres') {
            return false;
        }

        try {
            $ext = $DB->get_record_sql("SELECT 1 AS ok FROM pg_extension WHERE extname = 'vector'", []);
            $col = $DB->get_record_sql(
                "SELECT 1 AS ok FROM information_schema.columns
                  WHERE table_name = 'mdl_aitutor_embeddings' AND column_name = 'embedding'",
                []
            );
            return (bool) $ext && (bool) $col;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
