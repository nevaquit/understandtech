<?php
/**
 * Shared mod_ctfflag seed helpers for certification course scripts.
 *
 * @package    understandtech
 */

defined('CLI_SCRIPT') || die('ctfflag_seed.php must run from CLI scripts');

/**
 * Append shared lab footer disclaimer when present in repo.
 *
 * @param string $repopath Monorepo root on VM
 * @return string HTML fragment (may be empty)
 */
function ut_lab_shared_footer(string $repopath): string {
    $path = $repopath . '/content/_shared/lab-footer.html';
    if (!is_readable($path)) {
        return '';
    }
    $html = file_get_contents($path);
    return ($html !== false && trim($html) !== '') ? $html : '';
}

/**
 * Load lab intro HTML from content/<track>/labs/<slug>.html.
 *
 * @param string $repopath Monorepo root
 * @param string $track e.g. security-plus
 * @param string $slug e.g. lab-1-siem-triage
 * @param string $fallback Fallback HTML when file missing
 * @return string
 */
function ut_load_lab_intro(string $repopath, string $track, string $slug, string $fallback): string {
    $path = $repopath . '/content/' . $track . '/labs/' . $slug . '.html';
    if (is_readable($path)) {
        $html = file_get_contents($path);
        if ($html !== false && trim($html) !== '') {
            return $html . ut_lab_shared_footer($repopath);
        }
    }
    return $fallback;
}

/**
 * Find ctfflag instance by course and activity name.
 *
 * @param int $courseid
 * @param string $name
 * @return stdClass|null
 */
function ut_find_ctfflag(int $courseid, string $name): ?stdClass {
    global $DB;

    $record = $DB->get_record_sql(
        "SELECT cf.*
           FROM {ctfflag} cf
           JOIN {course_modules} cm ON cm.instance = cf.id
           JOIN {modules} m ON m.id = cm.module AND m.name = 'ctfflag'
          WHERE cm.course = :courseid AND cf.name = :name",
        ['courseid' => $courseid, 'name' => $name]
    );
    return $record ?: null;
}

/**
 * Create or update a mod_ctfflag lab activity (idempotent).
 *
 * @param stdClass $course
 * @param int $sectionnum
 * @param string $name
 * @param string $intro HTML intro (must not contain flag answers)
 * @param string $regex Expected flag PCRE
 * @param int $xpaward XP on success
 * @return void
 */
function ut_upsert_ctfflag(
    stdClass $course,
    int $sectionnum,
    string $name,
    string $intro,
    string $regex,
    int $xpaward = 100
): void {
    global $DB, $CFG;

    if (!array_key_exists('ctfflag', core_component::get_plugin_list('mod'))) {
        echo "ctfflag_skip plugin_missing name={$name}\n";
        return;
    }

    require_once($CFG->dirroot . '/mod/ctfflag/lib.php');

    $existing = ut_find_ctfflag((int) $course->id, $name);
    if ($existing) {
        $changed = false;
        if ((string) $existing->intro !== $intro) {
            $existing->intro = $intro;
            $existing->introformat = FORMAT_HTML;
            $changed = true;
        }
        if ((string) $existing->expected_flag_regex !== $regex) {
            $existing->expected_flag_regex = $regex;
            $changed = true;
        }
        if ((int) $existing->xp_award !== $xpaward) {
            $existing->xp_award = $xpaward;
            $changed = true;
        }
        if ($changed) {
            $existing->instance = $existing->id;
            ctfflag_update_instance($existing);
            echo "ctfflag_updated id={$existing->id} name={$name}\n";
        } else {
            echo "ctfflag_unchanged id={$existing->id} name={$name}\n";
        }
        return;
    }

    $module = new stdClass();
    $module->course = $course->id;
    $module->name = $name;
    $module->intro = $intro;
    $module->introformat = FORMAT_HTML;
    $module->expected_flag_regex = $regex;
    $module->xp_award = $xpaward;
    $module->completion_required = 1;
    $module->module = $DB->get_field('modules', 'id', ['name' => 'ctfflag']);
    $module->modulename = 'ctfflag';
    $module->section = $sectionnum;
    $module->visible = 1;
    $module->completion = COMPLETION_TRACKING_AUTOMATIC;
    $module->completionusegrade = 1;

    try {
        $cm = add_moduleinfo($module, $course);
        echo "ctfflag_created id={$cm->instance} name={$name} section={$sectionnum}\n";
    } catch (Throwable $e) {
        echo "ctfflag_create_failed name={$name} error=" . $e->getMessage() . "\n";
    }
}
