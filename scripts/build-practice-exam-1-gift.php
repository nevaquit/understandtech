#!/usr/bin/env php
<?php
/**
 * Build practice-exam-1.gift from SY0-701 objective banks with pe1_qNNN tags.
 *
 * Run on VM or dev host with PHP CLI:
 *   php scripts/build-practice-exam-1-gift.php
 *
 * Output: content/security-plus/practice-exam-1.gift
 *
 * @package    understandtech
 */

$repopath = dirname(__DIR__);
$sources = [
    $repopath . '/content/security-plus/sy0-701-quiz.gift',
    $repopath . '/content/security-plus/sy0-701-quiz-extra.gift',
];
$outpath = $repopath . '/content/security-plus/practice-exam-1.gift';
$targets = [1 => 11, 2 => 20, 3 => 16, 4 => 25, 5 => 18];
$targettotal = array_sum($targets);

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

$content = '';
foreach ($sources as $path) {
    if (!is_readable($path)) {
        fwrite(STDERR, "missing source: {$path}\n");
        exit(1);
    }
    $content .= "\n" . file_get_contents($path);
}

$domains = parse_gift_by_domain($content);
$selected = [];
$unused = [];

foreach ($targets as $domain => $need) {
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

$selected = array_slice($selected, 0, $targettotal);
$out = '';
$num = 1;
foreach ($selected as $block) {
    if (preg_match('/^::[^:]*::/m', $block, $head)) {
        $title = trim($head[0], ':');
        $body = preg_replace('/^::[^:]*::/m', '', $block, 1);
        $out .= '::pe1_q' . str_pad((string) $num, 3, '0', STR_PAD_LEFT) . ' ' . $title . '::' . $body . "\n\n";
    } else {
        $out .= '::pe1_q' . str_pad((string) $num, 3, '0', STR_PAD_LEFT) . "::\n" . $block . "\n\n";
    }
    $num++;
}

file_put_contents($outpath, trim($out) . "\n");
echo "wrote {$outpath} questions=" . count($selected) . "\n";
