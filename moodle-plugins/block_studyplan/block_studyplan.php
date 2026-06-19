<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Adaptive study coach block — surfaces CertMaster-style daily priorities.
 */
class block_studyplan extends block_base {

    /**
     * @return void
     */
    public function init(): void {
        $this->title = get_string('pluginname', 'block_studyplan');
    }

    #[\Override]
    public function applicable_formats(): array {
        return ['my' => true, 'course-view' => true];
    }

    #[\Override]
    public function get_content() {
        global $USER, $OUTPUT, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $certid = (int) ($this->config->certificationid ?? 0);

        if (!$certid || !class_exists('\local_certmaster\api')) {
            $this->content->text = get_string('noconfig', 'block_studyplan');
            return $this->content;
        }

        $regenerate = optional_param('regeneratestudyplan', 0, PARAM_INT) === $certid;
        if ($regenerate && confirm_sesskey()) {
            \local_certmaster\api::get_user_study_plan($USER->id, $certid, true);
            redirect($PAGE->url);
        }

        $plan = \local_certmaster\api::get_user_study_plan($USER->id, $certid);

        $regenerateurl = (new moodle_url($PAGE->url, [
            'regeneratestudyplan' => $certid,
            'sesskey' => sesskey(),
        ]))->out(false);

        $activities = [];
        foreach ($plan['activities'] as $activity) {
            $type = (string) ($activity['type'] ?? 'lesson_review');
            $typekey = 'activitytype_' . $type;
            $typelabel = get_string_manager()->string_exists($typekey, 'block_studyplan')
                ? get_string($typekey, 'block_studyplan')
                : ucfirst(str_replace('_', ' ', $type));
            $activities[] = [
                'title' => $activity['title'] ?? '',
                'objective' => $activity['objective'] ?? '',
                'reason' => $activity['reason'] ?? '',
                'minutes' => (int) ($activity['minutes'] ?? 25),
                'url' => $activity['url'] ?? '',
                'mastery_score' => $activity['mastery_score'] ?? null,
                'hasurl' => !empty($activity['url']),
                'type' => $type,
                'type_label' => $typelabel,
            ];
        }

        $generated = $plan['generated_at'] > 0
            ? userdate($plan['generated_at'], get_string('strftimedatefullshort'))
            : '';

        $this->content->text = $OUTPUT->render_from_template('block_studyplan/main', [
            'empty' => $plan['empty'],
            'summary' => $plan['summary'],
            'generated' => $generated,
            'activities' => $activities,
            'regenerateurl' => $regenerateurl,
            'certificationid' => $certid,
        ]);

        return $this->content;
    }

    #[\Override]
    public function specialization(): void {
        $this->title = format_string($this->config->title ?? get_string('pluginname', 'block_studyplan'));
    }
}
