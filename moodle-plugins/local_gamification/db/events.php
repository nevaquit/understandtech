<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\mod_quiz\event\attempt_submitted',
        'callback' => 'local_gamification\observer::quiz_attempt_submitted',
        'internal' => false,
    ],
    [
        'eventname' => '\mod_ctfflag\event\flag_submitted',
        'callback' => 'local_gamification\observer::flag_submitted',
        'internal' => false,
    ],
];
