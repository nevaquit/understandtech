<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'mod_ctfflag\task\purge_failed_submissions',
        'blocking' => 0,
        'minute' => '15',
        'hour' => '3',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
