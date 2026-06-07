<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\mod_assign\event\assessable_submitted',
        'callback' => 'local_aigrading\observer::assessable_submitted',
        'internal' => false,
    ],
];
