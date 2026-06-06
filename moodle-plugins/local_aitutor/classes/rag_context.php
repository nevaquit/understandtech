<?php
// This file is part of Moodle - http://moodle.org/

namespace local_aitutor;

defined('MOODLE_INTERNAL') || die();

/**
 * RAG context provider — Phase 2 stub (pgvector not yet enabled).
 *
 * Future: course-scoped retrieval from mdl_aitutor_embeddings via pgvector.
 * See docs/rag-phase2.md and .cursor/skills/ai-intelligent-systems/rag-pgvector.md.
 */
class rag_context {

    /**
     * Retrieve top-k course context chunks for tutor grounding.
     *
     * @param int $courseid Moodle course id (must be positive).
     * @param string $query Learner question (normalized server-side in Phase 2).
     * @param int $limit Maximum chunks to return.
     * @return array<int, array{content: string, source_type: string}> Empty until pgvector Phase 2.
     */
    public static function retrieve(int $courseid, string $query, int $limit = 5): array {
        if ($courseid <= 0) {
            throw new \invalid_parameter_exception('Invalid course id');
        }

        // Phase 2: embed $query, SELECT ... WHERE courseid = :courseid ORDER BY embedding <=> :vec LIMIT :limit.
        return [];
    }
}
