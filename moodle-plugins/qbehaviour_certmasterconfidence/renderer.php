<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for CertMaster confidence rating controls.
 */
class qbehaviour_certmasterconfidence_renderer extends qbehaviour_renderer {

    /**
     * Confidence level keys in display order.
     *
     * @return string[]
     */
    protected function confidence_levels(): array {
        return [
            \local_certmaster\api::CONFIDENCE_GUESSING,
            \local_certmaster\api::CONFIDENCE_UNSURE,
            \local_certmaster\api::CONFIDENCE_CONFIDENT,
            \local_certmaster\api::CONFIDENCE_CERTAIN,
        ];
    }

    /**
     * Render radio group for confidence selection.
     *
     * @param string $controlname Field name for the behaviour control.
     * @param string|null $selected Currently selected confidence level.
     * @param bool $readonly Whether inputs are disabled.
     * @return string HTML fragment.
     */
    protected function confidence_choices(string $controlname, ?string $selected, bool $readonly): string {
        $attributes = [
            'type' => 'radio',
            'name' => $controlname,
        ];
        if ($readonly) {
            $attributes['disabled'] = 'disabled';
        }

        $choices = '';
        foreach ($this->confidence_levels() as $level) {
            $id = $controlname . '_' . $level;
            $attributes['id'] = $id;
            $attributes['value'] = $level;
            if ($selected === $level) {
                $attributes['checked'] = 'checked';
            } else {
                unset($attributes['checked']);
            }

            $label = get_string('confidence_' . $level, 'qbehaviour_certmasterconfidence');
            $choices .= html_writer::tag(
                'label',
                html_writer::empty_tag('input', $attributes) .
                html_writer::tag('span', $label, ['class' => 'ut-confidence-label']),
                ['class' => 'ut-confidence-option', 'for' => $id]
            );
        }

        return html_writer::tag('div', $choices, ['class' => 'ut-confidence-options', 'role' => 'radiogroup']);
    }

    /**
     * Output confidence controls in the question formulation area.
     *
     * @param question_attempt $qa Question attempt.
     * @param question_display_options $options Display options.
     * @return string HTML fragment.
     */
    public function controls(question_attempt $qa, question_display_options $options): string {
        if ($qa->get_state()->is_finished()) {
            return '';
        }

        $controlname = $qa->get_behaviour_field_name('confidence');
        $selected = $qa->get_last_behaviour_var('confidence');

        $a = (object) [
            'help' => $this->output->help_icon('confidencehelp', 'qbehaviour_certmasterconfidence'),
            'choices' => $this->confidence_choices($controlname, $selected, $options->readonly),
        ];

        return html_writer::tag(
            'div',
            html_writer::tag('p', get_string('howconfident', 'qbehaviour_certmasterconfidence', $a), ['class' => 'ut-confidence-prompt']) .
            $a->choices,
            ['class' => 'ut-confidence-rating']
        );
    }

    /**
     * Show recorded confidence after the attempt is graded.
     *
     * @param question_attempt $qa Question attempt.
     * @param question_display_options $options Display options.
     * @return string HTML fragment.
     */
    public function feedback(question_attempt $qa, question_display_options $options): string {
        if (!$options->feedback) {
            return '';
        }

        $confidence = $qa->get_last_behaviour_var('confidence');
        if (!$confidence) {
            return '';
        }

        $label = get_string('confidence_' . $confidence, 'qbehaviour_certmasterconfidence');
        return html_writer::tag(
            'p',
            get_string('confidence_recorded', 'qbehaviour_certmasterconfidence', $label),
            ['class' => 'ut-confidence-feedback']
        );
    }
}
