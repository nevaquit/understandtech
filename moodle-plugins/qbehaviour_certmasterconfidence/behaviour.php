<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/behaviour/deferredfeedback/behaviour.php');

/**
 * CertMaster confidence rating behaviour (deferred feedback + confidence capture).
 */
class qbehaviour_certmasterconfidence extends qbehaviour_deferredfeedback {

    #[\Override]
    public function is_compatible_question(question_definition $question): bool {
        return $question->get_type_name() !== 'description';
    }

    #[\Override]
    public function get_expected_data(): array {
        $expected = parent::get_expected_data();
        if ($this->qa->get_state()->is_active()) {
            $expected['confidence'] = PARAM_ALPHA;
        }
        return $expected;
    }

    #[\Override]
    protected function is_same_response(question_attempt_step $pendingstep): bool {
        return parent::is_same_response($pendingstep) &&
            $this->qa->get_last_behaviour_var('confidence') === $pendingstep->get_behaviour_var('confidence');
    }

    #[\Override]
    protected function is_complete_response(question_attempt_step $pendingstep): bool {
        return parent::is_complete_response($pendingstep) &&
            $pendingstep->has_behaviour_var('confidence');
    }

    #[\Override]
    public function summarise_action(question_attempt_step $step): string {
        $summary = parent::summarise_action($step);
        if ($step->has_behaviour_var('confidence')) {
            $level = $step->get_behaviour_var('confidence');
            $label = get_string('confidence_' . $level, 'qbehaviour_certmasterconfidence');
            $summary .= '. ' . get_string('confidence_recorded', 'qbehaviour_certmasterconfidence', $label);
        }
        return $summary;
    }

    #[\Override]
    public function question_summary_finished(question_attempt $qa): void {
        parent::question_summary_finished($qa);

        $confidence = $qa->get_last_behaviour_var('confidence');
        if (!$confidence) {
            return;
        }

        $fraction = $qa->get_fraction();
        $iscorrect = $fraction !== null && $fraction > 0.99;
        \local_certmaster\api::record_confidence($qa->get_usage_id(), $qa->get_slot(), $confidence, $iscorrect);
    }
}
