<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_ctfflag\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event fired when a learner submits a correct CTF flag (stub contract).
 */
class flag_submitted extends \core\event\base {

    /**
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'ctfflag';
    }

    /**
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventflag_submitted', 'mod_ctfflag');
    }

    /**
     * @return string
     */
    public function get_description(): string {
        return "The user with id '{$this->userid}' submitted a correct flag for ctfflag activity " .
            "with id '{$this->objectid}'.";
    }

    /**
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        $cmid = (int) ($this->other['cmid'] ?? 0);
        return new \moodle_url('/mod/ctfflag/view.php', ['id' => $cmid]);
    }

    /**
     * @return array
     */
    protected function validate_data(): void {
        parent::validate_data();
        if (!isset($this->objectid)) {
            throw new \coding_exception('The \'objectid\' value must be set.');
        }
    }
}
