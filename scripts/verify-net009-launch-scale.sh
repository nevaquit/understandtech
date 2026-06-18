#!/usr/bin/env bash
# Verify NET009 launch-scale module counts on the current Moodle VM (staging or production).
set -euo pipefail

if [ -f /tmp/ut-net009-course-id-gha ]; then
  NET009_COURSE_ID="$(tr -d '[:space:]' < /tmp/ut-net009-course-id-gha)"
fi
if [ -z "${NET009_COURSE_ID:-}" ] && [ -f /var/www/moodle/config.php ]; then
  _wwwroot="$(/usr/bin/php -r 'define("CLI_SCRIPT", true); require "/var/www/moodle/config.php"; echo $CFG->wwwroot;' 2>/dev/null || true)"
  if [[ "$_wwwroot" == *staging* ]]; then
    NET009_COURSE_ID=2
  fi
fi
export NET009_COURSE_ID="${NET009_COURSE_ID:-3}"

ut_www_data_php() {
  if [ "$(id -u)" -eq 0 ]; then
    runuser -u www-data -- env NET009_COURSE_ID="${NET009_COURSE_ID}" php "$@"
  else
    sudo -u www-data env NET009_COURSE_ID="${NET009_COURSE_ID}" php "$@"
  fi
}

readarray -t COUNTS < <(ut_www_data_php -r "
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';
global \$DB;

\$course = \$DB->get_record('course', ['shortname' => 'NET009']);
if (!\$course) {
    fwrite(STDERR, \"verify_net009_launch_scale_failed reason=course_missing shortname=NET009\n\");
    exit(1);
}
\$courseid = (int) \$course->id;

\$pages = (int) \$DB->count_records_sql(
    \"SELECT COUNT(*) FROM {page} p
       JOIN {course_modules} cm ON cm.instance = p.id AND cm.deletioninprogress = 0
       JOIN {modules} m ON m.id = cm.module AND m.name = 'page'
      WHERE cm.course = ?\",
    [\$courseid]
);
\$quizzes = (int) \$DB->count_records_sql(
    \"SELECT COUNT(*) FROM {quiz} q
       JOIN {course_modules} cm ON cm.instance = q.id AND cm.deletioninprogress = 0
       JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
      WHERE cm.course = ?\",
    [\$courseid]
);
\$ctfflags = (int) \$DB->count_records_sql(
    \"SELECT COUNT(*) FROM {ctfflag} c
       JOIN {course_modules} cm ON cm.instance = c.id AND cm.deletioninprogress = 0
       JOIN {modules} m ON m.id = cm.module AND m.name = 'ctfflag'
      WHERE cm.course = ?\",
    [\$courseid]
);

\$peids = [];
foreach (['Practice Exam 1', 'Practice Exam 2', 'Practice Exam 3'] as \$name) {
    \$quizid = (int) \$DB->get_field_sql(
        \"SELECT q.id FROM {quiz} q
           JOIN {course_modules} cm ON cm.instance = q.id AND cm.deletioninprogress = 0
           JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
          WHERE cm.course = ? AND q.name = ?\",
        [\$courseid, \$name],
        IGNORE_MISSING
    );
    if (\$quizid > 0) {
        \$peids[] = (int) \$DB->count_records('quiz_slots', ['quizid' => \$quizid]);
    } else {
        \$peids[] = 0;
    }
}

echo \"course_id={\$courseid}\n\";
echo \"pages={\$pages}\n\";
echo \"quizzes={\$quizzes}\n\";
echo \"ctfflags={\$ctfflags}\n\";
echo \"practice_exam_1_slots={\$peids[0]}\n\";
echo \"practice_exam_2_slots={\$peids[1]}\n\";
echo \"practice_exam_3_slots={\$peids[2]}\n\";
")

for line in "${COUNTS[@]}"; do
  echo "$line"
done

pages=0
quizzes=0
ctfflags=0
pe1=0
pe2=0
pe3=0
for line in "${COUNTS[@]}"; do
  case "$line" in
    pages=*) pages="${line#pages=}" ;;
    quizzes=*) quizzes="${line#quizzes=}" ;;
    ctfflags=*) ctfflags="${line#ctfflags=}" ;;
    practice_exam_1_slots=*) pe1="${line#practice_exam_1_slots=}" ;;
    practice_exam_2_slots=*) pe2="${line#practice_exam_2_slots=}" ;;
    practice_exam_3_slots=*) pe3="${line#practice_exam_3_slots=}" ;;
  esac
done

fail=0
if [ "$pages" -lt 75 ]; then
  echo "verify_net009_launch_scale_failed metric=pages got=${pages} want=75"
  fail=1
fi
if [ "$quizzes" -lt 8 ]; then
  echo "verify_net009_launch_scale_failed metric=quizzes got=${quizzes} want=8"
  fail=1
fi
if [ "$ctfflags" -lt 3 ]; then
  echo "verify_net009_launch_scale_failed metric=ctfflags got=${ctfflags} want=3"
  fail=1
fi
for n in 1 2 3; do
  eval "slots=\$pe${n}"
  if [ "$slots" -lt 90 ]; then
    echo "verify_net009_launch_scale_failed metric=practice_exam_${n}_slots got=${slots} want=90"
    fail=1
  fi
done

if [ "$fail" -ne 0 ]; then
  exit 1
fi

echo "verify_net009_launch_scale_ok=1"
