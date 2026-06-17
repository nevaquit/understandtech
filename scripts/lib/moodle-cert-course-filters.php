<?php
/**
 * Shared Moodle text-filter disable logic for certification course lesson pages.
 *
 * Prevents filter/MUC "Error reading from database" on large mod/page HTML.
 * Requires Moodle bootstrap (config.php loaded).
 *
 * @package    understandtech
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filterlib.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Collect globally and context-active filter names to disable locally.
 *
 * @param \context[] $contexts
 * @return string[]
 */
function ut_cert_course_filter_names(array $contexts): array {
    global $DB;

    $filternames = [];
    foreach ($DB->get_records('filter_active') as $row) {
        if ((int) $row->active === TEXTFILTER_DISABLED) {
            continue;
        }
        $filternames[$row->filter] = true;
    }
    foreach ($contexts as $context) {
        foreach (array_keys(filter_get_active_in_context($context)) as $filtername) {
            $filternames[$filtername] = true;
        }
    }

    return array_keys($filternames);
}

/**
 * Disable all active text filters for a course and its modules.
 *
 * @param stdClass $course Moodle course record
 * @param bool $purgeall When true, purge all Moodle caches after rebuild
 * @return array{contexts: int, filter_disables: int}
 */
function ut_disable_cert_course_text_filters(stdClass $course, bool $purgeall = true): array {
    $modinfo = get_fast_modinfo($course);
    $contexts = [context_course::instance((int) $course->id)];
    foreach ($modinfo->get_cms() as $cm) {
        if ($cm->deletioninprogress) {
            continue;
        }
        $contexts[] = context_module::instance($cm->id);
    }

    $filternames = ut_cert_course_filter_names($contexts);
    $disabled = 0;
    foreach ($contexts as $context) {
        foreach ($filternames as $filtername) {
            filter_set_local_state($filtername, $context->id, TEXTFILTER_OFF);
            $disabled++;
        }
    }

    filter_manager::reset_caches();
    rebuild_course_cache((int) $course->id, true);
    if ($purgeall && function_exists('purge_all_caches')) {
        purge_all_caches();
    }

    return [
        'contexts' => count($contexts),
        'filter_disables' => $disabled,
    ];
}

/**
 * Disable filters for a course resolved by shortname.
 *
 * @param string $shortname Course shortname (SEC701, NET009, APLUS)
 * @param bool $purgeall
 * @return array{contexts: int, filter_disables: int, courseid: int}
 */
function ut_disable_cert_course_text_filters_by_shortname(string $shortname, bool $purgeall = true): array {
    global $DB;

    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $stats = ut_disable_cert_course_text_filters($course, $purgeall);
    $stats['courseid'] = (int) $course->id;
    return $stats;
}
