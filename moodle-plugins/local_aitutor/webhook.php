<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

$rawbody = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_MOODLE_SIGNATURE'] ?? '';

if (!\local_aitutor\api::receive_transcript_webhook($rawbody, $signature)) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

http_response_code(204);
