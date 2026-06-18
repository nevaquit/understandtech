<?php
/**
 * Practice exam helpers for certification course seed scripts.
 *
 * @package    understandtech
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Blueprint-weighted question counts for a 90-question SY0-701 practice exam.
 *
 * @return array<int,int> Domain number => target count
 */
function ut_practice_exam_domain_targets(): array {
    return [
        1 => 11,
        2 => 20,
        3 => 16,
        4 => 25,
        5 => 18,
    ];
}

/**
 * Select question ids for a full-length practice exam from the objective bank.
 *
 * Uses blueprint weights first, then fills remaining slots without duplication,
 * then allows reuse from the largest domain pool if the bank is still short.
 *
 * @param int $categoryid Question category id
 * @param int $targetcount Target slot count (default 90)
 * @return int[]
 */
function ut_select_practice_exam_questions(int $categoryid, int $targetcount = 90): array {
    if (!function_exists('security_plus_map_questions_by_domain')) {
        return [];
    }

    $domains = security_plus_map_questions_by_domain($categoryid);
    $targets = ut_practice_exam_domain_targets();
    $selected = [];
    $unused = [];

    foreach ($targets as $domainnum => $need) {
        $pool = $domains[$domainnum] ?? [];
        shuffle($pool);
        $take = array_slice($pool, 0, min($need, count($pool)));
        $selected = array_merge($selected, $take);
        if (count($pool) > count($take)) {
            $unused = array_merge($unused, array_slice($pool, count($take)));
        }
    }

    $selected = array_values(array_unique($selected));
    shuffle($unused);

    foreach ($unused as $qid) {
        if (count($selected) >= $targetcount) {
            break;
        }
        if (!in_array($qid, $selected, true)) {
            $selected[] = $qid;
        }
    }

    if (count($selected) < $targetcount) {
        $fallback = $domains[4] ?? [];
        if ($fallback === []) {
            foreach ($domains as $pool) {
                if (count($pool) > count($fallback)) {
                    $fallback = $pool;
                }
            }
        }
        shuffle($fallback);
        $idx = 0;
        while (count($selected) < $targetcount && $fallback !== []) {
            $selected[] = (int) $fallback[$idx % count($fallback)];
            $idx++;
        }
    }

    return array_slice($selected, 0, $targetcount);
}

/**
 * Collect question ids tagged ::peN_q in a category (practice exam bank import).
 *
 * @param int $categoryid
 * @param string $prefix e.g. pe1_q
 * @return int[]
 */
function ut_practice_exam_category_question_ids(int $categoryid, string $prefix): array {
    global $DB;

    $ids = [];
    $seen = [];
    foreach ($DB->get_records_sql(
        "SELECT q.id, q.name
           FROM {question} q
           JOIN {question_versions} qv ON qv.questionid = q.id
           JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
          WHERE qbe.questioncategoryid = :catid
            AND qv.status = :status
            AND q.name LIKE :pattern
       ORDER BY q.name ASC",
        ['catid' => $categoryid, 'status' => 'ready', 'pattern' => $prefix . '%']
    ) as $row) {
        $name = (string) $row->name;
        if (isset($seen[$name])) {
            continue;
        }
        $seen[$name] = true;
        $ids[] = (int) $row->id;
        if (count($ids) >= 90) {
            break;
        }
    }
    return $ids;
}

/**
 * Blueprint-weighted question counts for a 90-question N10-009 practice exam.
 *
 * @return array<int,int> Domain number => target count
 */
function ut_practice_exam_domain_targets_net009(): array {
    return [
        1 => 21,
        2 => 18,
        3 => 17,
        4 => 13,
        5 => 21,
    ];
}

/**
 * Select practice exam question ids from a Network+ objective bank.
 *
 * @param int $categoryid Question category id
 * @param int $targetcount Target slot count (default 90)
 * @return int[]
 */
function ut_select_practice_exam_questions_net009(int $categoryid, int $targetcount = 90): array {
    if (!function_exists('network_plus_map_questions_by_domain')) {
        return [];
    }

    $domains = network_plus_map_questions_by_domain($categoryid);
    $targets = ut_practice_exam_domain_targets_net009();
    $selected = [];
    $unused = [];

    foreach ($targets as $domainnum => $need) {
        $pool = $domains[$domainnum] ?? [];
        shuffle($pool);
        $take = array_slice($pool, 0, min($need, count($pool)));
        $selected = array_merge($selected, $take);
        if (count($pool) > count($take)) {
            $unused = array_merge($unused, array_slice($pool, count($take)));
        }
    }

    $selected = array_values(array_unique($selected));
    shuffle($unused);

    foreach ($unused as $qid) {
        if (count($selected) >= $targetcount) {
            break;
        }
        if (!in_array($qid, $selected, true)) {
            $selected[] = $qid;
        }
    }

    if (count($selected) < $targetcount) {
        $fallback = $domains[5] ?? [];
        if ($fallback === []) {
            foreach ($domains as $pool) {
                if (count($pool) > count($fallback)) {
                    $fallback = $pool;
                }
            }
        }
        shuffle($fallback);
        $idx = 0;
        while (count($selected) < $targetcount && $fallback !== []) {
            $selected[] = (int) $fallback[$idx % count($fallback)];
            $idx++;
        }
    }

    return array_slice($selected, 0, $targetcount);
}
