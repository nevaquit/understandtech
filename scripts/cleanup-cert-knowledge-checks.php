<?php
/**
 * Remove duplicate question bank entries and rebuild knowledge-check quizzes.
 *
 * Usage: sudo -u www-data php cleanup-cert-knowledge-checks.php [NET009|SEC701|APLUS|all]
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');

require_once(__DIR__ . '/lib/moodle-cert-quiz-dedup.php');

/** @var array<string, array{category: string, reconcile: callable}> $specs */
$specs = [
    'NET009' => [
        'category' => 'Network+ N10-009',
        'reconcile' => static function (stdClass $course, stdClass $qcat): void {
            $all = ut_map_questions_by_objective((int) $qcat->id, '/\b(n10009_\d+_\d+)\b/');
            for ($domain = 1; $domain <= 5; $domain++) {
                $num = $domain;
                $qids = ut_curate_knowledge_check_questions(
                    $all,
                    static function (string $obj) use ($num): bool {
                        return (bool) preg_match('/^n10009_' . $num . '_/', $obj);
                    }
                );
                ut_sync_knowledge_check_quiz(
                    $course,
                    $domain,
                    "Domain {$domain} Knowledge Check",
                    $qids,
                    static function (stdClass $c, int $s, string $n, array $ids): void {
                        ut_add_knowledge_check_quiz(
                            $c,
                            $s,
                            $n,
                            $ids,
                            '<p>Network+ N10-009 domain knowledge check — one unique question per objective.</p>'
                        );
                    }
                );
            }
        },
    ],
    'SEC701' => [
        'category' => 'Security+ SY0-701',
        'reconcile' => static function (stdClass $course, stdClass $qcat): void {
            $all = ut_map_questions_by_objective((int) $qcat->id, '/\b(sy701_\d+_\d+)\b/');
            for ($domain = 1; $domain <= 5; $domain++) {
                $num = $domain;
                $qids = ut_curate_knowledge_check_questions(
                    $all,
                    static function (string $obj) use ($num): bool {
                        return (bool) preg_match('/^sy701_' . $num . '_/', $obj);
                    }
                );
                ut_sync_knowledge_check_quiz(
                    $course,
                    $domain,
                    "Domain {$domain} Knowledge Check",
                    $qids,
                    static function (stdClass $c, int $s, string $n, array $ids): void {
                        ut_add_knowledge_check_quiz(
                            $c,
                            $s,
                            $n,
                            $ids,
                            '<p>Security+ SY0-701 domain knowledge check — one unique question per objective.</p>'
                        );
                    }
                );
            }
        },
    ],
    'APLUS' => [
        'category' => 'CompTIA A+ certification',
        'reconcile' => static function (stdClass $course, stdClass $qcat): void {
            $all = ut_map_questions_by_objective((int) $qcat->id, '/\b(ap110[12]_\d+_\d+)\b/');
            for ($section = 1; $section <= 9; $section++) {
                $num = $section;
                $qids = ut_curate_knowledge_check_questions(
                    $all,
                    static function (string $obj) use ($num): bool {
                        if ($num <= 5 && preg_match('/^ap1101_' . $num . '_/', $obj)) {
                            return true;
                        }
                        if ($num > 5 && preg_match('/^ap1102_' . ($num - 5) . '_/', $obj)) {
                            return true;
                        }
                        return false;
                    }
                );
                ut_sync_knowledge_check_quiz(
                    $course,
                    $section,
                    "Domain {$section} Knowledge Check",
                    $qids,
                    static function (stdClass $c, int $s, string $n, array $ids): void {
                        ut_add_knowledge_check_quiz(
                            $c,
                            $s,
                            $n,
                            $ids,
                            '<p>CompTIA A+ domain knowledge check — one unique question per objective.</p>'
                        );
                    }
                );
            }
        },
    ],
];

$target = strtoupper(trim($argv[1] ?? 'all'));
$run = $target === 'ALL' ? array_keys($specs) : [$target];

foreach ($run as $courseshort) {
    if (!isset($specs[$courseshort])) {
        echo "unknown_course={$courseshort}\n";
        continue;
    }

    $course = $DB->get_record('course', ['shortname' => $courseshort]);
    if (!$course) {
        echo "course_skip missing shortname={$courseshort}\n";
        continue;
    }

    $context = context_course::instance((int) $course->id);
    $qcat = $DB->get_record('question_categories', [
        'contextid' => $context->id,
        'name' => $specs[$courseshort]['category'],
    ]);
    if (!$qcat) {
        echo "category_skip course={$courseshort}\n";
        continue;
    }

    echo "=== cleanup knowledge checks course={$courseshort} ===\n";
    ut_dedupe_question_bank_category((int) $qcat->id);
    $specs[$courseshort]['reconcile']($course, $qcat);
}

purge_all_caches();
echo "cleanup_cert_knowledge_checks_complete=1\n";
