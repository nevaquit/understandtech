<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace local_integrations\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * GDPR self-service data export bundle.
 */
class exporter {

    /**
     * @param int $userid
     * @return array<string, mixed>
     */
    public static function export_user(int $userid): array {
        global $DB;

        $user = \core_user::get_user($userid, '*', MUST_EXIST);
        $bundle = [
            'exported_at' => userdate(time()),
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
            ],
            'conversations' => [],
            'mastery' => [],
            'lab_submissions' => [],
        ];

        if ($DB->get_manager()->table_exists('aitutor_conversations')) {
            $bundle['conversations'] = array_values($DB->get_records('aitutor_conversations', ['userid' => $userid]));
        }

        if ($DB->get_manager()->table_exists('certmaster_mastery')) {
            $bundle['mastery'] = array_values($DB->get_records('certmaster_mastery', ['userid' => $userid]));
        }

        if ($DB->get_manager()->table_exists('ctfflag_submissions')) {
            $bundle['lab_submissions'] = array_values($DB->get_records('ctfflag_submissions', ['userid' => $userid]));
        }

        return $bundle;
    }
}
