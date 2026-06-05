<?php
// This file is part of Moodle - http://moodle.org/

namespace local_certmaster\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for certification mastery data.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    #[\Override]
    public static function get_metadata(\core_privacy\local\metadata\collection $collection): \core_privacy\local\metadata\collection {
        $collection->add_database_table('certmaster_attempt_confidence', [
            'attemptid' => 'privacy:metadata:attemptid',
            'confidence' => 'privacy:metadata:confidence',
            'iscorrect' => 'privacy:metadata:iscorrect',
        ], 'privacy:metadata:attemptconfidence');

        $collection->add_database_table('certmaster_mastery', [
            'userid' => 'privacy:metadata:userid',
            'mastery_score' => 'privacy:metadata:masteryscore',
        ], 'privacy:metadata:mastery');

        return $collection;
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): \core_privacy\local\request\contextlist {
        return new \core_privacy\local\request\contextlist();
    }

    #[\Override]
    public static function export_user_data(\core_privacy\local\request\approved_contextlist $contextlist): void {
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(\context $context): void {
    }

    #[\Override]
    public static function delete_data_for_user(\core_privacy\local\request\approved_contextlist $contextlist): void {
    }
}
