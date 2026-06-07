#!/usr/bin/env bash
# scaffold.sh — Generate Moodle plugin boilerplate
#
# Usage:
#   bash scaffold.sh <type> <name> [--moodle-root=/path/to/moodle]
#
# Examples:
#   bash scaffold.sh local myplugin
#   bash scaffold.sh mod myplugin --moodle-root=/var/www/moodle
#   bash scaffold.sh block myplugin
#   bash scaffold.sh report myplugin
#
# Supported types: local, mod, block, report, tool
#
# Copyright 2026 — Released under GNU GPL v3+

set -euo pipefail

# ─── Defaults ────────────────────────────────────────────────────────
AUTHOR="${MOODLE_AUTHOR:-Your Name}"
EMAIL="${MOODLE_EMAIL:-you@example.com}"
MOODLE_REQUIRES="2024042200"  # Moodle 4.4
YEAR=$(date +%Y)
VERSION=$(date +%Y%m%d)00
TODAY=$(date +%Y-%m-%d)

# ─── Parse arguments ─────────────────────────────────────────────────
if [[ $# -lt 2 ]]; then
    echo "Usage: bash scaffold.sh <type> <name> [--moodle-root=/path]"
    echo "Types: local, mod, block, report, tool"
    exit 1
fi

TYPE="$1"
NAME="$2"
MOODLE_ROOT=""

for arg in "${@:3}"; do
    case "$arg" in
        --moodle-root=*) MOODLE_ROOT="${arg#*=}" ;;
        *) echo "Unknown option: $arg"; exit 1 ;;
    esac
done

# ─── Derive paths ────────────────────────────────────────────────────
case "$TYPE" in
    local)  COMPONENT="local_${NAME}"; REL="local/${NAME}" ;;
    mod)    COMPONENT="mod_${NAME}"; REL="mod/${NAME}" ;;
    block)  COMPONENT="block_${NAME}"; REL="blocks/${NAME}" ;;
    report) COMPONENT="report_${NAME}"; REL="report/${NAME}" ;;
    tool)   COMPONENT="tool_${NAME}"; REL="admin/tool/${NAME}" ;;
    *)      echo "Unsupported type: ${TYPE}. Use: local, mod, block, report, tool"; exit 1 ;;
esac

if [[ -n "$MOODLE_ROOT" ]]; then
    ROOT="${MOODLE_ROOT}/${REL}"
else
    ROOT="./${REL}"
fi

if [[ -d "$ROOT" ]]; then
    echo "Error: Directory ${ROOT} already exists."
    exit 1
fi

echo "Creating ${COMPONENT} at ${ROOT}..."

# ─── GPL header ──────────────────────────────────────────────────────
GPL_HEADER="<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>."

# ─── Create directories ──────────────────────────────────────────────
mkdir -p "${ROOT}/classes/output"
mkdir -p "${ROOT}/classes/privacy"
mkdir -p "${ROOT}/db"
mkdir -p "${ROOT}/lang/en"
mkdir -p "${ROOT}/templates"
mkdir -p "${ROOT}/tests/generator"

if [[ "$TYPE" == "mod" ]]; then
    mkdir -p "${ROOT}/backup/moodle2"
    mkdir -p "${ROOT}/pix"
fi

if [[ "$TYPE" == "block" || "$TYPE" == "local" || "$TYPE" == "mod" ]]; then
    mkdir -p "${ROOT}/amd/src/local"
    mkdir -p "${ROOT}/amd/build"
fi

# ─── version.php ─────────────────────────────────────────────────────
cat > "${ROOT}/version.php" << VEOF
${GPL_HEADER}

/**
 * Plugin version information.
 *
 * @package    ${COMPONENT}
 * @copyright  ${YEAR} ${AUTHOR} <${EMAIL}>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

\$plugin->component = '${COMPONENT}';
\$plugin->version   = ${VERSION};
\$plugin->requires  = ${MOODLE_REQUIRES};
\$plugin->maturity  = MATURITY_ALPHA;
\$plugin->release   = '0.1.0';
VEOF

# ─── Language file ───────────────────────────────────────────────────
cat > "${ROOT}/lang/en/${COMPONENT}.php" << LEOF
${GPL_HEADER}

/**
 * Language strings for ${COMPONENT}.
 *
 * @package    ${COMPONENT}
 * @copyright  ${YEAR} ${AUTHOR} <${EMAIL}>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

\$string['pluginname'] = '${NAME}';
LEOF

# ─── db/access.php ───────────────────────────────────────────────────
cat > "${ROOT}/db/access.php" << AEOF
${GPL_HEADER}

/**
 * Capability definitions for ${COMPONENT}.
 *
 * @package    ${COMPONENT}
 * @copyright  ${YEAR} ${AUTHOR} <${EMAIL}>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

\$capabilities = [
    '${TYPE}/${NAME}:view' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'student'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW,
        ],
    ],
    '${TYPE}/${NAME}:manage' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'riskbitmask'  => RISK_SPAM,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW,
        ],
    ],
];
AEOF

# ─── Privacy null provider ───────────────────────────────────────────
NAMESPACE_PATH=$(echo "$COMPONENT" | tr '_' '\\')
cat > "${ROOT}/classes/privacy/provider.php" << PEOF
${GPL_HEADER}

/**
 * Privacy null provider for ${COMPONENT}.
 *
 * @package    ${COMPONENT}
 * @copyright  ${YEAR} ${AUTHOR} <${EMAIL}>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace ${COMPONENT}\\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider — no user data stored.
 */
class provider implements \\core_privacy\\local\\metadata\\null_provider {

    /**
     * Get the reason this plugin stores no data.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
PEOF

# Add privacy string to lang file.
echo "\$string['privacy:metadata'] = '${NAME} does not store personal user data.';" >> "${ROOT}/lang/en/${COMPONENT}.php"

# ─── Type-specific files ─────────────────────────────────────────────
if [[ "$TYPE" == "mod" ]]; then
    # lib.php for mod_
    cat > "${ROOT}/lib.php" << MEOF
${GPL_HEADER}

/**
 * Library functions for ${COMPONENT}.
 *
 * @package    ${COMPONENT}
 * @copyright  ${YEAR} ${AUTHOR} <${EMAIL}>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function ${NAME}_add_instance(\stdClass \$data, \${COMPONENT}_mod_form \$form = null): int {
    global \$DB;
    \$data->timecreated = time();
    \$data->timemodified = time();
    return \$DB->insert_record('${NAME}', \$data);
}

function ${NAME}_update_instance(\stdClass \$data, \${COMPONENT}_mod_form \$form = null): bool {
    global \$DB;
    \$data->timemodified = time();
    \$data->id = \$data->instance;
    return \$DB->update_record('${NAME}', \$data);
}

function ${NAME}_delete_instance(int \$id): bool {
    global \$DB;
    if (!\$DB->record_exists('${NAME}', ['id' => \$id])) {
        return false;
    }
    \$DB->delete_records('${NAME}', ['id' => \$id]);
    return true;
}

function ${NAME}_supports(string \$feature): ?bool {
    return match (\$feature) {
        FEATURE_MOD_INTRO        => true,
        FEATURE_BACKUP_MOODLE2   => true,
        FEATURE_SHOW_DESCRIPTION => true,
        FEATURE_MOD_PURPOSE      => MOD_PURPOSE_OTHER,
        default                  => null,
    };
}
MEOF

    # mod_form.php skeleton
    cat > "${ROOT}/mod_form.php" << FEOF
${GPL_HEADER}

/**
 * Activity settings form for ${COMPONENT}.
 *
 * @package    ${COMPONENT}
 * @copyright  ${YEAR} ${AUTHOR} <${EMAIL}>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(\$CFG->dirroot . '/course/moodleform_mod.php');

class ${COMPONENT}_mod_form extends moodleform_mod {

    public function definition(): void {
        \$mform = \$this->_form;

        \$mform->addElement('header', 'general', get_string('general', 'form'));
        \$mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        \$mform->setType('name', PARAM_TEXT);
        \$mform->addRule('name', null, 'required', null, 'client');

        \$this->standard_intro_elements();
        \$this->standard_coursemodule_elements();
        \$this->add_action_buttons();
    }
}
FEOF

    # view.php skeleton
    cat > "${ROOT}/view.php" << WEOF
${GPL_HEADER}

/**
 * View page for ${COMPONENT}.
 *
 * @package    ${COMPONENT}
 * @copyright  ${YEAR} ${AUTHOR} <${EMAIL}>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

\$id = required_param('id', PARAM_INT);

\$cm      = get_coursemodule_from_id('${NAME}', \$id, 0, false, MUST_EXIST);
\$course  = get_course(\$cm->course);
\$instance = \$DB->get_record('${NAME}', ['id' => \$cm->instance], '*', MUST_EXIST);

require_login(\$course, true, \$cm);
\$context = context_module::instance(\$cm->id);
require_capability('mod/${NAME}:view', \$context);

\$PAGE->set_url('/mod/${NAME}/view.php', ['id' => \$id]);
\$PAGE->set_title(format_string(\$instance->name));
\$PAGE->set_heading(\$course->fullname);

echo \$OUTPUT->header();
echo \$OUTPUT->heading(format_string(\$instance->name));
echo \$OUTPUT->footer();
WEOF

    # index.php skeleton
    cat > "${ROOT}/index.php" << IEOF
${GPL_HEADER}

/**
 * List all instances of ${COMPONENT} in a course.
 *
 * @package    ${COMPONENT}
 * @copyright  ${YEAR} ${AUTHOR} <${EMAIL}>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

\$id = required_param('id', PARAM_INT);
\$course = get_course(\$id);

require_login(\$course);
\$PAGE->set_url('/mod/${NAME}/index.php', ['id' => \$id]);
\$PAGE->set_title(get_string('modulenameplural', '${COMPONENT}'));
\$PAGE->set_heading(\$course->fullname);

echo \$OUTPUT->header();
echo \$OUTPUT->heading(get_string('modulenameplural', '${COMPONENT}'));
echo \$OUTPUT->footer();
IEOF

    # Add mod-specific lang strings
    cat >> "${ROOT}/lang/en/${COMPONENT}.php" << MLEOF
\$string['modulename'] = '${NAME}';
\$string['modulenameplural'] = '${NAME}s';
\$string['${NAME}:addinstance'] = 'Add a new ${NAME}';
\$string['${NAME}:view'] = 'View ${NAME}';
MLEOF
fi

if [[ "$TYPE" == "block" ]]; then
    cat > "${ROOT}/block_${NAME}.php" << BEOF
${GPL_HEADER}

/**
 * Block class for ${COMPONENT}.
 *
 * @package    ${COMPONENT}
 * @copyright  ${YEAR} ${AUTHOR} <${EMAIL}>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_${NAME} extends block_base {

    public function init(): void {
        \$this->title = get_string('pluginname', '${COMPONENT}');
    }

    public function get_content(): \stdClass {
        if (\$this->content !== null) {
            return \$this->content;
        }

        \$this->content = new \stdClass();
        \$this->content->text = '';
        \$this->content->footer = '';

        return \$this->content;
    }

    public function applicable_formats(): array {
        return ['course-view' => true, 'site' => true, 'my' => true];
    }
}
BEOF
fi

# ─── Summary ─────────────────────────────────────────────────────────
echo ""
echo "✓ Created ${COMPONENT} at ${ROOT}"
echo ""
echo "Files created:"
find "${ROOT}" -type f | sort | sed "s|^${ROOT}/|  |"
echo ""
echo "Next steps:"
echo "  1. Edit version.php with your actual version"
echo "  2. Create db/install.xml if you need database tables"
echo "  3. Run: php admin/cli/upgrade.php"
echo "  4. Set MOODLE_AUTHOR and MOODLE_EMAIL env vars for future runs"
