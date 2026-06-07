<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace local_aitutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Course content ingestion for RAG embeddings.
 */
class ingest {

    /** @var string[] Module types excluded from indexing. */
    private const EXCLUDED_MODS = ['quiz', 'ctfflag', 'assign', 'lesson'];

    /**
     * Index all eligible content in a course.
     *
     * @param int $courseid
     * @return int Number of chunks upserted.
     */
    public static function index_course(int $courseid): int {
        global $DB;

        $modinfo = get_fast_modinfo($courseid);
        $count = 0;

        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->uservisible || in_array($cm->modname, self::EXCLUDED_MODS, true)) {
                continue;
            }

            $text = self::extract_module_text($cm);
            if ($text === '') {
                continue;
            }

            $chunks = self::chunk_text($text);
            foreach ($chunks as $chunk) {
                if (self::upsert_chunk($courseid, $cm->context->id, $cm->modname, $chunk)) {
                    $count++;
                }
            }
        }

        self::embed_pending_chunks($courseid);

        return $count;
    }

    /**
     * @param \cm_info $cm
     * @return string
     */
    protected static function extract_module_text(\cm_info $cm): string {
        global $DB;

        switch ($cm->modname) {
            case 'page':
                $page = $DB->get_record('page', ['id' => $cm->instance], 'content', MUST_EXIST);
                return trim(strip_tags($page->content));
            case 'label':
                $label = $DB->get_record('label', ['id' => $cm->instance], 'intro', MUST_EXIST);
                return trim(strip_tags($label->intro));
            case 'book':
                $chapters = $DB->get_records('book_chapters', ['bookid' => $cm->instance], 'pagenum ASC', 'content');
                return trim(strip_tags(implode("\n", array_map(static fn($c) => $c->content, $chapters))));
            default:
                return '';
        }
    }

    /**
     * @param string $text
     * @return string[]
     */
    protected static function chunk_text(string $text): array {
        $paragraphs = preg_split('/\n{2,}/', $text) ?: [];
        $chunks = [];
        $buffer = '';

        foreach ($paragraphs as $para) {
            $para = trim($para);
            if ($para === '') {
                continue;
            }
            if (strlen($buffer) + strlen($para) > 900) {
                if ($buffer !== '') {
                    $chunks[] = $buffer;
                }
                $buffer = $para;
            } else {
                $buffer = $buffer === '' ? $para : $buffer . "\n\n" . $para;
            }
        }

        if ($buffer !== '') {
            $chunks[] = $buffer;
        }

        return $chunks;
    }

    /**
     * @param int $courseid
     * @param int $contextid
     * @param string $sourcetype
     * @param string $content
     * @return bool
     */
    protected static function upsert_chunk(int $courseid, int $contextid, string $sourcetype, string $content): bool {
        global $DB;

        $hash = hash('sha256', $courseid . ':' . $contextid . ':' . $content);
        $now = time();
        $existing = $DB->get_record('aitutor_embeddings', ['courseid' => $courseid, 'chunk_hash' => $hash]);

        if ($existing) {
            return false;
        }

        $DB->insert_record('aitutor_embeddings', (object) [
            'courseid' => $courseid,
            'contextid' => $contextid,
            'chunk_hash' => $hash,
            'content_text' => $content,
            'source_type' => $sourcetype,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        return true;
    }

    /**
     * Request embeddings from Worker /embed for chunks missing vectors.
     *
     * @param int $courseid
     * @return void
     */
    public static function embed_pending_chunks(int $courseid): void {
        global $DB, $USER;

        $pending = $DB->get_records_select(
            'aitutor_embeddings',
            'courseid = :courseid AND (embeddingjson IS NULL OR embeddingjson = :empty)',
            ['courseid' => $courseid, 'empty' => ''],
            'id ASC',
            '*',
            0,
            32
        );

        if (!$pending) {
            return;
        }

        $texts = array_map(static fn($r) => $r->content_text, $pending);
        $context = \context_course::instance($courseid);
        $jwt = api::generate_tutor_jwt($USER->id ?? 2, $context);

        $embedurl = preg_replace('#/tutor/?$#', '/embed', rtrim((string) get_config('local_aitutor', 'workerurl'), '/'));
        if (!$embedurl) {
            $embedurl = 'https://ai.understandtech.app/embed';
        }

        $curl = new \curl();
        $curl->setHeader(['Content-Type: application/json', 'Authorization: Bearer ' . $jwt]);
        $raw = $curl->post($embedurl, json_encode(['texts' => $texts]));
        $response = json_decode($raw ?: '{}');

        if (empty($response->embeddings) || !is_array($response->embeddings)) {
            return;
        }

        $i = 0;
        foreach ($pending as $record) {
            if (!isset($response->embeddings[$i])) {
                break;
            }
            $vector = $response->embeddings[$i];
            $record->embeddingjson = json_encode($vector);
            $record->timemodified = time();
            $DB->update_record('aitutor_embeddings', $record);

            if (rag_context::pgvector_available()) {
                $literal = '[' . implode(',', array_map('floatval', $vector)) . ']';
                $DB->execute(
                    'UPDATE {aitutor_embeddings} SET embedding = :vec::vector WHERE id = :id',
                    ['vec' => $literal, 'id' => $record->id]
                );
            }
            $i++;
        }
    }
}
