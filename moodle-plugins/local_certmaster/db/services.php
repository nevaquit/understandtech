<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_certmaster_get_stream_iframe_url' => [
        'classname' => 'local_certmaster\external\get_stream_iframe_url',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Refresh signed Cloudflare Stream iframe URL',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/certmaster:viewstream',
    ],
    'local_certmaster_get_user_readiness' => [
        'classname' => 'local_certmaster\external\get_user_readiness',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return user certification readiness for radar chart refresh',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/certmaster:viewmastery',
    ],
];
