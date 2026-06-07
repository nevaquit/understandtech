<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_aigrading';
$plugin->version   = 2026060800;
$plugin->requires  = 2024100700;
$plugin->maturity  = MATURITY_BETA;
$plugin->release   = '1.0.0';
$plugin->dependencies = [
    'local_aitutor' => 2026060803,
];
