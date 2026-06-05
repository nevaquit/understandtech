<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'AI Tutor';
$string['sidebar_title'] = 'AI Tutor';
$string['workerurl'] = 'AI Worker URL';
$string['workerurl_desc'] = 'Cloudflare Worker endpoint for tutor SSE connections.';
$string['tokenexpiry'] = 'JWT expiry (seconds)';
$string['tokenexpiry_desc'] = 'Short-lived token lifetime (default 300).';
$string['jwtsharedsecret'] = 'Worker shared secret (fallback)';
$string['jwtsharedsecret_desc'] = 'Prefer AITUTOR_WORKER_SHARED_SECRET in /etc/moodle/env from Key Vault.';
$string['missingsecret'] = 'AI Tutor is not configured (missing worker shared secret).';
$string['unavailable'] = 'AI Tutor is temporarily unavailable. Please try again later.';
$string['privacy:metadata:aitutor'] = 'Stores AI tutor conversation transcripts for audit.';
