<?php
/**
 * Compare default vs noclean format_text on lesson diagram HTML (run on VM).
 *
 * @copyright 2026 AI Tech Pros, Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

$sample = <<<'HTML'
<div class="ut-lesson-diagram ut-infographic">
<div class="diagram-title">Enterprise Mitigation Techniques</div>
<figure class="ut-svg-figure"><svg class="ut-mitigation-hub" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="40" fill="#0B1F3A"/></svg></figure>
<div class="cia-triad"><div class="cia-element"><h4>Segmentation</h4><p>Limit spread</p></div></div>
</div>
HTML;

chdir('/var/www/moodle');
require('/var/www/moodle/config.php');

$context = context_system::instance();
$default = format_text($sample, FORMAT_HTML, ['context' => $context]);
$trusted = format_text($sample, FORMAT_HTML, ['context' => $context, 'noclean' => true]);

echo 'default_len=' . strlen($default) . "\n";
echo 'noclean_len=' . strlen($trusted) . "\n";
echo 'default_has_svg=' . (strpos($default, '<svg') !== false ? 'yes' : 'no') . "\n";
echo 'default_has_cia=' . (strpos($default, 'cia-element') !== false ? 'yes' : 'no') . "\n";
echo 'noclean_has_svg=' . (strpos($trusted, '<svg') !== false ? 'yes' : 'no') . "\n";
echo "test_lesson_purify_complete=1\n";
