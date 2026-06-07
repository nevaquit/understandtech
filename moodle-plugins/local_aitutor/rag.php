<?php
// This file is part of Moodle - http://moodle.org/

define('NO_DEBUG_DISPLAY', true);

require(__DIR__ . '/../../config.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!preg_match('/^Bearer\s+(.+)$/i', $auth, $matches)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$secret = \local_aitutor\api::get_worker_secret();
if ($secret === '') {
    http_response_code(503);
    echo json_encode(['error' => 'Worker secret not configured']);
    exit;
}

$claims = \local_aitutor\jwt_helper::decode($matches[1], $secret);
if (!$claims || ($claims['aud'] ?? '') !== 'ai-worker') {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

$body = json_decode(file_get_contents('php://input') ?: '{}', true);
$query = trim((string) ($body['query'] ?? ''));
$courseid = (int) ($claims['context']['courseid'] ?? 0);
$embedding = $body['embedding'] ?? null;

if ($courseid <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing course context in JWT']);
    exit;
}

try {
    $chunks = \local_aitutor\rag_context::retrieve(
        $courseid,
        $query,
        5,
        is_array($embedding) ? $embedding : null
    );
    echo json_encode(['chunks' => $chunks]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Retrieval failed']);
}
