<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\mod_ctfflag\event\flag_submitted',
        'callback' => 'local_certmaster\observer::flag_submitted',
        'internal' => false,
    ],
];
