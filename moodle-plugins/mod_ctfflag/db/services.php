<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_ctfflag_submit_flag' => [
        'classname' => 'mod_ctfflag\external\submit_flag',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Submit a CTF lab flag with instant validation',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/ctfflag:submit',
    ],
];
