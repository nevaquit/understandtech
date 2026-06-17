<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'block_studyplan';
$plugin->version   = 2026061600;
$plugin->requires  = 2024100700;
$plugin->dependencies = [
    'local_certmaster' => 2026061601,
];
$plugin->maturity  = MATURITY_BETA;
$plugin->release   = '1.0.0';
