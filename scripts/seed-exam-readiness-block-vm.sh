#!/usr/bin/env bash
# Seed exam readiness block on E2E user dashboard (run on production VM).
set -euo pipefail

USERNAME="${E2E_USERNAME:-e2etest}"
CERTID="${E2E_CERTIFICATION_ID:-1}"

sudo -u www-data php <<PHP
<?php
define('CLI_SCRIPT', true);
require('/var/www/moodle/config.php');
require_once(\$CFG->dirroot . '/user/lib.php');
require_once(\$CFG->dirroot . '/my/lib.php');

\$username = getenv('E2E_USERNAME') ?: '${USERNAME}';
\$certid = (int) (getenv('E2E_CERTIFICATION_ID') ?: ${CERTID});

\$user = \$DB->get_record('user', ['username' => \$username, 'deleted' => 0]);
if (!\$user) {
    echo "user_not_found\n";
    exit(1);
}

if (!\$DB->record_exists('certmaster_certifications', ['id' => \$certid])) {
    echo "certification_missing id=\$certid\n";
    exit(1);
}

\$context = context_user::instance((int) \$user->id);
\$pagetype = 'my-index';

\$existing = \$DB->get_records('block_instances', [
    'blockname' => 'examreadiness',
    'parentcontextid' => \$context->id,
    'pagetypepattern' => \$pagetype,
]);
if (\$existing) {
    echo 'block_exists id=' . reset(\$existing)->id . "\n";
    exit(0);
}

\$config = (object) [
    'certificationid' => \$certid,
    'title' => 'Exam readiness',
];
\$instance = (object) [
    'blockname' => 'examreadiness',
    'parentcontextid' => \$context->id,
    'showinsubcontexts' => 0,
    'requiredbytheme' => 0,
    'pagetypepattern' => \$pagetype,
    'subpagepattern' => null,
    'defaultregion' => 'content',
    'defaultweight' => -2,
    'configdata' => base64_encode(serialize(\$config)),
    'timecreated' => time(),
    'timemodified' => time(),
];
\$instance->id = (int) \$DB->insert_record('block_instances', \$instance);
echo "block_created id={\$instance->id} user={\$user->id} cert=\$certid\n";

PHP

sudo -u www-data php /var/www/moodle/admin/cli/purge_caches.php
echo 'caches_purged=1'
