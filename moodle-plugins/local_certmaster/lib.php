<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Render a Cloudflare Stream iframe player with server-signed JWT and AMD refresh before expiry.
 *
 * @param string $videoid Stream video UID (never echoed in page HTML; passed to AMD via js_call_amd).
 * @param int|null $courseid Course id for JWT refresh authorization (defaults to current page course).
 * @return string HTML fragment.
 */
function local_certmaster_render_stream_player(string $videoid, ?int $courseid = null): string {
    global $PAGE, $OUTPUT, $USER;

    if ($courseid === null) {
        $courseid = (int) ($PAGE->course->id ?? 0);
    }

    if (!empty($USER->id)) {
        \local_certmaster\stream_access::assert_user_can_sign((int) $USER->id, $videoid, $courseid);
    }

    $iframesrc = \local_certmaster\stream_helper::sign_iframe_url($videoid);
    $expiresat = time() + \local_certmaster\stream_helper::JWT_EXPIRY_SECONDS;

    $PAGE->requires->js_call_amd('local_certmaster/stream_player', 'init', [$videoid, $courseid]);

    return $OUTPUT->render_from_template('local_certmaster/stream_player', [
        'iframesrc' => $iframesrc,
        'expiresat' => $expiresat,
    ]);
}
