<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_aitutor_get_jwt' => [
        'classname' => 'local_aitutor\external\get_jwt',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Generate short-lived tutor JWT',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/aitutor:use',
    ],
    'local_aitutor_get_conversations' => [
        'classname' => 'local_aitutor\external\get_conversations',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'List recent tutor conversations',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/aitutor:use',
    ],
];
