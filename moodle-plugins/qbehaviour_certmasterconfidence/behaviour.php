<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/behaviour/behaviourbase.php');

/**
 * CertMaster confidence rating behaviour.
 */
class qbehaviour_certmasterconfidence extends question_behaviour_with_save {

    #[\Override]
    public static function is_compatible_question(question_definition $question): bool {
        return $question->get_type_name() !== 'description';
    }

    #[\Override]
    public function is_compatible_behaviour(): bool {
        return true;
    }

    #[\Override]
    public function get_expected_data(): array {
        return ['confidence' => PARAM_ALPHA];
    }

    #[\Override]
    public function process_submit(question_attempt_pending_step $pendingstep) {
        $confidence = $pendingstep->get_submitted_data()['confidence'] ?? null;
        if (!$confidence) {
            return question_attempt::KEEP;
        }
        $pendingstep->set_qt_var('confidence', $confidence);
        return question_attempt::KEEP;
    }

    #[\Override]
    public function summarise_action(question_attempt_step $step): string {
        $confidence = $step->get_qt_var('confidence');
        if ($confidence) {
            return get_string('confidence_recorded', 'qbehaviour_certmasterconfidence', $confidence);
        }
        return '';
    }

    #[\Override]
    public function question_summary_finished(question_attempt $qa): void {
        $confidence = $qa->get_last_qt_var('confidence');
        if (!$confidence) {
            return;
        }
        $fraction = $qa->get_fraction();
        $iscorrect = $fraction !== null && $fraction > 0.99;
        \local_certmaster\api::record_confidence($qa->get_usage_id(), $qa->get_slot(), $confidence, $iscorrect);
    }
}
