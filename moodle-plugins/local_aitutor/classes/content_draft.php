<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace local_aitutor;

defined('MOODLE_INTERNAL') || die();

/**
 * CRUD for instructor-reviewed AI content drafts.
 */
class content_draft {

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_REJECTED = 'rejected';

    /**
     * @param int $courseid
     * @param int|null $cmid
     * @param int $userid
     * @param string $drafttype
     * @param string $sourceexcerpt
     * @param array $draftjson
     * @param string $provider
     * @param string $promptversion
     * @return int Draft record id.
     */
    public static function create(
        int $courseid,
        ?int $cmid,
        int $userid,
        string $drafttype,
        string $sourceexcerpt,
        array $draftjson,
        string $provider,
        string $promptversion
    ): int {
        global $DB;

        $now = time();
        return (int) $DB->insert_record('aitutor_content_drafts', (object) [
            'courseid' => $courseid,
            'cmid' => $cmid,
            'userid' => $userid,
            'draft_type' => $drafttype,
            'source_excerpt' => $sourceexcerpt,
            'draft_json' => json_encode($draftjson, JSON_UNESCAPED_UNICODE),
            'status' => self::STATUS_DRAFT,
            'provider' => $provider,
            'prompt_version' => $promptversion,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    /**
     * @param int $draftid
     * @return \stdClass|null
     */
    public static function get(int $draftid): ?\stdClass {
        global $DB;

        $record = $DB->get_record('aitutor_content_drafts', ['id' => $draftid]);
        if (!$record) {
            return null;
        }

        $record->draft_data = json_decode($record->draft_json, true) ?: [];
        return $record;
    }

    /**
     * @param int $courseid
     * @param string|null $status Filter by status or null for all.
     * @return array<int, \stdClass>
     */
    public static function list_for_course(int $courseid, ?string $status = self::STATUS_DRAFT): array {
        global $DB;

        $params = ['courseid' => $courseid];
        $statussql = '';
        if ($status !== null) {
            $statussql = ' AND status = :status';
            $params['status'] = $status;
        }

        return $DB->get_records_sql(
            "SELECT * FROM {aitutor_content_drafts}
              WHERE courseid = :courseid{$statussql}
           ORDER BY timemodified DESC",
            $params
        );
    }

    /**
     * @param int $draftid
     * @param string $status
     * @return bool
     */
    public static function update_status(int $draftid, string $status): bool {
        global $DB;

        self::validate_status($status);

        return $DB->set_field('aitutor_content_drafts', 'status', $status, ['id' => $draftid])
            && $DB->set_field('aitutor_content_drafts', 'timemodified', time(), ['id' => $draftid]);
    }

    /**
     * @param int $draftid
     * @return bool
     */
    public static function delete(int $draftid): bool {
        global $DB;

        return (bool) $DB->delete_records('aitutor_content_drafts', ['id' => $draftid]);
    }

    /**
     * @param string $status
     * @return void
     */
    protected static function validate_status(string $status): void {
        $allowed = [self::STATUS_DRAFT, self::STATUS_PUBLISHED, self::STATUS_REJECTED];
        if (!in_array($status, $allowed, true)) {
            throw new \invalid_parameter_exception('Invalid draft status');
        }
    }
}
