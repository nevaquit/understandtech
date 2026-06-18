#!/usr/bin/env php
<?php
/**
 * Build practice-exam-{1,2,3}.gift from SY0-701 objective banks with peN_qNNN tags.
 *
 * Usage:
 *   php scripts/build-practice-exams-gift.php 1
 *   php scripts/build-practice-exams-gift.php 2
 *   php scripts/build-practice-exams-gift.php 3
 *   php scripts/build-practice-exams-gift.php all
 *
 * @package    understandtech
 */

$repopath = dirname(__DIR__);
$sources = [
    $repopath . '/content/security-plus/sy0-701-quiz.gift',
    $repopath . '/content/security-plus/sy0-701-quiz-extra.gift',
    $repopath . '/content/security-plus/sy0-701-quiz-launch.gift',
];
$targets = [1 => 11, 2 => 20, 3 => 16, 4 => 25, 5 => 18];
$targettotal = array_sum($targets);

$arg = $argv[1] ?? 'all';
$exams = [];
if ($arg === 'all') {
    $exams = [1, 2, 3];
} else {
    $n = (int) $arg;
    if ($n < 1 || $n > 3) {
        fwrite(STDERR, "usage: php build-practice-exams-gift.php [1|2|3|all]\n");
        exit(1);
    }
    $exams = [$n];
}

/**
 * @param string $content
 * @return array<int,array<int,string>> domain => question blocks
 */
function parse_gift_by_domain(string $content): array {
    $domains = [1 => [], 2 => [], 3 => [], 4 => [], 5 => []];
    if (!preg_match_all('/^::sy701_(\d)_\d+[^:]*::.*?^(?=::|\Z)/ms', $content, $matches, PREG_SET_ORDER)) {
        return $domains;
    }
    foreach ($matches as $match) {
        $domain = (int) $match[1];
        if (!isset($domains[$domain])) {
            continue;
        }
        $domains[$domain][] = trim($match[0]);
    }
    return $domains;
}

/**
 * @param array<int,array<int,string>> $domains
 * @param int[] $domaintargets
 * @param int $targettotal
 * @param int $seed
 * @return string[]
 */
function select_exam_questions(array $domains, array $domaintargets, int $targettotal, int $seed): array {
    mt_srand($seed);
    $selected = [];
    $unused = [];

    foreach ($domaintargets as $domain => $need) {
        $pool = $domains[$domain] ?? [];
        shuffle($pool);
        $take = array_slice($pool, 0, min($need, count($pool)));
        $selected = array_merge($selected, $take);
        if (count($pool) > count($take)) {
            $unused = array_merge($unused, array_slice($pool, count($take)));
        }
    }

    $seen = [];
    $unique = [];
    foreach ($selected as $block) {
        if (isset($seen[$block])) {
            continue;
        }
        $seen[$block] = true;
        $unique[] = $block;
    }
    $selected = $unique;

    shuffle($unused);
    foreach ($unused as $block) {
        if (count($selected) >= $targettotal) {
            break;
        }
        if (!isset($seen[$block])) {
            $seen[$block] = true;
            $selected[] = $block;
        }
    }

    $fallback = $domains[4] ?? [];
    while (count($selected) < $targettotal && $fallback !== []) {
        $block = $fallback[array_rand($fallback)];
        $selected[] = $block;
    }

    return array_slice($selected, 0, $targettotal);
}

$content = '';
foreach ($sources as $path) {
    if (!is_readable($path)) {
        continue;
    }
    $content .= "\n" . file_get_contents($path);
}
if ($content === '') {
    fwrite(STDERR, "error=no_gift_sources_readable\n");
    exit(1);
}

$domains = parse_gift_by_domain($content);

foreach ($exams as $examnum) {
    $seed = 70100 + ($examnum * 997);
    $selected = select_exam_questions($domains, $targets, $targettotal, $seed);
    $prefix = 'pe' . $examnum . '_q';
    $outpath = $repopath . '/content/security-plus/practice-exam-' . $examnum . '.gift';
    $out = '';
    $num = 1;
    foreach ($selected as $block) {
        if (preg_match('/^::[^:]*::/m', $block, $head)) {
            $title = trim($head[0], ':');
            $body = preg_replace('/^::[^:]*::/m', '', $block, 1);
            $out .= '::' . $prefix . str_pad((string) $num, 3, '0', STR_PAD_LEFT) . ' ' . $title . '::' . $body . "\n\n";
        } else {
            $out .= '::' . $prefix . str_pad((string) $num, 3, '0', STR_PAD_LEFT) . "::\n" . $block . "\n\n";
        }
        $num++;
    }
    file_put_contents($outpath, trim($out) . "\n");
    echo "wrote {$outpath} exam={$examnum} questions=" . count($selected) . "\n";
}
