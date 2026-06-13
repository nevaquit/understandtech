<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'AI Tutor';
$string['task_reindex_courses'] = 'Reindex course content for AI tutor RAG';
$string['sidebar_title'] = 'AI Tutor';
$string['workerurl'] = 'AI Worker URL';
$string['workerurl_desc'] = 'Cloudflare Worker endpoint for tutor SSE connections.';
$string['tokenexpiry'] = 'JWT expiry (seconds)';
$string['tokenexpiry_desc'] = 'Short-lived token lifetime (default 300).';
$string['jwtsharedsecret'] = 'Worker shared secret (fallback)';
$string['jwtsharedsecret_desc'] = 'Prefer AITUTOR_WORKER_SHARED_SECRET in /etc/moodle/env from Key Vault.';
$string['missingsecret'] = 'AI Tutor is not configured (missing worker shared secret).';
$string['unavailable'] = 'AI Tutor is temporarily unavailable. Please try again later.';
$string['input_label'] = 'Question for the AI tutor';
$string['input_placeholder'] = 'Ask the tutor…';
$string['send'] = 'Send';
$string['toggle_panel'] = 'Toggle tutor panel';
$string['loading'] = 'Thinking…';
$string['output_label'] = 'Tutor response';
$string['error_generic'] = 'Something went wrong. Please try again.';
$string['history_label'] = 'Conversation history';
$string['new_conversation'] = 'New conversation';
$string['welcome_hint'] = 'Ask a concept question — the tutor guides you without giving quiz or lab answers.';
$string['invalidconversation'] = 'You cannot access that conversation.';
$string['enablesidebar'] = 'Enable course sidebar';
$string['enablesidebar_desc'] = 'Show the AI Tutor sidebar on course and activity pages for users with the use capability.';
$string['index_heading'] = 'AI Tutor';
$string['index_intro'] = 'Open a certification course to chat with the Socratic tutor. All LLM requests go through the Cloudflare Worker — Moodle never calls Anthropic or OpenAI directly.';
$string['index_opencourse'] = 'Open course';
$string['nocourses'] = 'Enrol in a certification course to use the AI tutor sidebar on lesson pages.';
$string['privacy:metadata:conversations'] = 'AI tutor conversation sessions.';
$string['privacy:metadata:conversations:userid'] = 'The user who chatted with the tutor.';
$string['privacy:metadata:conversations:courseid'] = 'The course context for the conversation.';
$string['privacy:metadata:conversations:timecreated'] = 'When the conversation started.';
$string['privacy:metadata:conversations:timemodified'] = 'When the conversation was last updated.';
$string['privacy:metadata:messages'] = 'Individual tutor chat messages.';
$string['privacy:metadata:messages:role'] = 'Whether the message was from the user or assistant.';
$string['privacy:metadata:messages:content'] = 'Message text (audit transcript).';
$string['privacy:metadata:messages:timecreated'] = 'When the message was recorded.';
$string['privacy:metadata:aitutor'] = 'Stores AI tutor conversation transcripts for audit.';
