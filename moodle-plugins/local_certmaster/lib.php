<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Render a Cloudflare Stream iframe player with server-signed JWT and AMD refresh before expiry.
 *
 * @param string $videoid Stream video UID (never echoed in page HTML; passed to AMD via js_call_amd).
 * @return string HTML fragment.
 */
function local_certmaster_render_stream_player(string $videoid): string {
    global $PAGE, $OUTPUT;

    $iframesrc = \local_certmaster\stream_helper::sign_iframe_url($videoid);
    $expiresat = time() + \local_certmaster\stream_helper::JWT_EXPIRY_SECONDS;

    $PAGE->requires->js_call_amd('local_certmaster/stream_player', 'init', [$videoid]);

    return $OUTPUT->render_from_template('local_certmaster/stream_player', [
        'iframesrc' => $iframesrc,
        'expiresat' => $expiresat,
    ]);
}
