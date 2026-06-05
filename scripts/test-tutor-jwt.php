<?php
// CLI on production VM: verify JWT generation and Worker auth.
// Usage: sudo -u www-data php /tmp/test-tutor-jwt.php [--curl]
define('CLI_SCRIPT', true);

$moodleroot = getenv('MOODLE_ROOT') ?: '/var/www/moodle';
require($moodleroot . '/config.php');

$secretlen = strlen(getenv('AITUTOR_WORKER_SHARED_SECRET') ?: '');
$fallback = (string) get_config('local_aitutor', 'jwtsharedsecret');
echo 'secret_env_len=' . $secretlen . ' fallback_len=' . strlen($fallback) . PHP_EOL;
echo 'workerurl=' . (get_config('local_aitutor', 'workerurl') ?: 'default') . PHP_EOL;

global $DB;
$course = $DB->get_record('course', ['id' => 1]) ?: $DB->get_record_sql('SELECT * FROM {course} WHERE id > 1 ORDER BY id ASC LIMIT 1');
if (!$course) {
    echo "error=no_course\n";
    exit(1);
}

$context = context_course::instance((int) $course->id);
$user = $DB->get_record_sql('SELECT * FROM {user} WHERE deleted = 0 AND id > 1 ORDER BY id ASC LIMIT 1');
if (!$user) {
    echo "error=no_user\n";
    exit(1);
}

$token = \local_aitutor\api::generate_tutor_jwt((int) $user->id, $context);
echo 'jwt_generated=1 len=' . strlen($token) . PHP_EOL;

if (!in_array('--curl', $argv ?? [], true)) {
    exit(0);
}

$workerurl = get_config('local_aitutor', 'workerurl') ?: 'https://ai.understandtech.app/tutor';
$body = json_encode([
    'messages' => [['role' => 'user', 'content' => 'What is Kerberos in one sentence?']],
    'context' => [
        'courseid' => (int) $course->id,
        'activityid' => null,
        'conversation_id' => 'cli-test',
    ],
]);
$ch = curl_init($workerurl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 45,
]);
$response = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo 'worker_http=' . $code . PHP_EOL;
echo substr((string) $response, 0, 800) . PHP_EOL;
