<?php
/**
 * Diagnose a Moodle page module by course-module id.
 *
 * Run on VM: sudo -u www-data php /opt/understandtech-plugins/scripts/diagnose-page-cmid.php 4
 *
 * @package    understandtech
 */

define('CLI_SCRIPT', true);

$cmid = (int) ($argv[1] ?? 0);
if ($cmid <= 0) {
    fwrite(STDERR, "Usage: php diagnose-page-cmid.php <cmid>\n");
    exit(1);
}

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');
require_once($CFG->dirroot . '/mod/page/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

global $DB;

echo "=== diagnose cmid={$cmid} ===\n";

try {
    $cm = get_coursemodule_from_id('page', $cmid, 0, false, MUST_EXIST);
    echo "cm_ok instance={$cm->instance} course={$cm->course} section={$cm->section}\n";
} catch (Throwable $e) {
    echo 'cm_error=' . $e->getMessage() . "\n";
    exit(1);
}

try {
    $page = $DB->get_record('page', ['id' => $cm->instance], '*', MUST_EXIST);
    echo 'page_name=' . $page->name . "\n";
    echo 'content_len=' . strlen((string) $page->content) . "\n";
    echo 'contentformat=' . $page->contentformat . "\n";
    echo 'displayoptions=' . $page->displayoptions . "\n";
    if (!mb_check_encoding((string) $page->content, 'UTF-8')) {
        echo "content_utf8=INVALID\n";
    } else {
        echo "content_utf8=ok\n";
    }
} catch (Throwable $e) {
    echo 'page_error=' . $e->getMessage() . "\n";
}

try {
    $context = context_module::instance($cmid);
    echo 'context_ok id=' . $context->id . "\n";
} catch (Throwable $e) {
    echo 'context_error=' . $e->getMessage() . "\n";
}

try {
    $course = get_course($cm->course);
    require_capability('mod/page:view', context_module::instance($cmid));
    echo "capability_ok\n";
} catch (Throwable $e) {
    echo 'capability_note=' . $e->getMessage() . "\n";
}

// Simulate view.php data load path.
try {
    $page = $DB->get_record('page', ['id' => $cm->instance], '*', MUST_EXIST);
    $options = page_get_displayoptions($page);
    echo 'displayoptions_parsed=' . json_encode($options) . "\n";
} catch (Throwable $e) {
    echo 'displayoptions_error=' . $e->getMessage() . "\n";
}

try {
    $fs = get_file_storage();
    $context = context_module::instance($cmid);
    $files = $fs->get_area_files($context->id, 'mod_page', 'content', 0, 'id', false);
    echo 'content_files=' . count($files) . "\n";
} catch (Throwable $e) {
    echo 'files_error=' . $e->getMessage() . "\n";
}

try {
    $context = context_module::instance($cmid);
    $page = $DB->get_record('page', ['id' => $cm->instance], '*', MUST_EXIST);
    $options = new stdClass();
    $options->noclean = true;
    $options->overflowdiv = true;
    $options->context = $context;
    $options->filter = false;
    $formatted = format_text($page->content, $page->contentformat, $options);
    echo 'format_text_no_filter_len=' . strlen($formatted) . "\n";
    echo "format_text_no_filter_ok\n";
} catch (Throwable $e) {
    echo 'format_text_no_filter_error=' . $e->getMessage() . "\n";
}

try {
    $context = context_module::instance($cmid);
    $page = $DB->get_record('page', ['id' => $cm->instance], '*', MUST_EXIST);
    $options = new stdClass();
    $options->noclean = true;
    $options->overflowdiv = true;
    $options->context = $context;
    $formatted = format_text($page->content, $page->contentformat, $options);
    echo 'format_text_with_filters_len=' . strlen($formatted) . "\n";
    echo "format_text_with_filters_ok\n";
} catch (Throwable $e) {
    echo 'format_text_with_filters_error=' . $e->getMessage() . "\n";
}

$activefilters = filter_get_active_in_context(context_module::instance($cmid));
echo 'active_filters=' . implode(',', array_keys($activefilters)) . "\n";

require_once($CFG->libdir . '/filterlib.php');
$filterstates = [];
foreach ($DB->get_records('filter_active') as $row) {
    $filterstates[$row->filter] = (int) $row->active;
    filter_set_global_state($row->filter, TEXTFILTER_DISABLED);
}
filter_manager::reset_caches();

foreach ($filterstates as $filtername => $state) {
    if ($state !== TEXTFILTER_ON) {
        continue;
    }
    filter_set_global_state($filtername, TEXTFILTER_ON);
    filter_manager::reset_caches();
    try {
        $context = context_module::instance($cmid);
        $page = $DB->get_record('page', ['id' => $cm->instance], '*', MUST_EXIST);
        $options = new stdClass();
        $options->noclean = true;
        $options->overflowdiv = true;
        $options->context = $context;
        $filtered = format_text($page->content, $page->contentformat, $options);
        echo "filter_ok name={$filtername} len=" . strlen($filtered) . "\n";
    } catch (Throwable $e) {
        echo "filter_fail name={$filtername} err=" . $e->getMessage() . "\n";
    }
    filter_set_global_state($filtername, TEXTFILTER_DISABLED);
    filter_manager::reset_caches();
}

foreach ($filterstates as $filtername => $state) {
    filter_set_global_state($filtername, $state);
}
filter_manager::reset_caches();

$enabled = array_keys(array_filter($filterstates, static fn(int $s): bool => $s === TEXTFILTER_ON));
$chain = [];
foreach ($enabled as $name) {
    $chain[] = $name;
    foreach ($filterstates as $filtername => $state) {
        filter_set_global_state($filtername, TEXTFILTER_DISABLED);
    }
    foreach ($chain as $on) {
        filter_set_global_state($on, TEXTFILTER_ON);
    }
    filter_manager::reset_caches();
    try {
        $context = context_module::instance($cmid);
        $page = $DB->get_record('page', ['id' => $cm->instance], '*', MUST_EXIST);
        $options = new stdClass();
        $options->noclean = true;
        $options->overflowdiv = true;
        $options->context = $context;
        $filtered = format_text($page->content, $page->contentformat, $options);
        echo 'chain_ok filters=' . implode('+', $chain) . ' len=' . strlen($filtered) . "\n";
    } catch (Throwable $e) {
        echo 'chain_fail filters=' . implode('+', $chain) . ' err=' . $e->getMessage() . "\n";
        break;
    }
}

$enabled = array_keys(array_filter($filterstates, static fn(int $s): bool => $s === TEXTFILTER_ON));
foreach ($enabled as $skip) {
    foreach ($filterstates as $filtername => $state) {
        filter_set_global_state($filtername, TEXTFILTER_DISABLED);
    }
    foreach ($enabled as $name) {
        if ($name !== $skip) {
            filter_set_global_state($name, TEXTFILTER_ON);
        }
    }
    filter_manager::reset_caches();
    try {
        $context = context_module::instance($cmid);
        $page = $DB->get_record('page', ['id' => $cm->instance], '*', MUST_EXIST);
        $options = new stdClass();
        $options->noclean = true;
        $options->overflowdiv = true;
        $options->context = $context;
        $filtered = format_text($page->content, $page->contentformat, $options);
        echo "combo_without_{$skip}_ok len=" . strlen($filtered) . "\n";
    } catch (Throwable $e) {
        echo "combo_without_{$skip}_fail err=" . $e->getMessage() . "\n";
    }
}

foreach ($filterstates as $filtername => $state) {
    filter_set_global_state($filtername, $state);
}
filter_manager::reset_caches();

echo "=== done ===\n";
