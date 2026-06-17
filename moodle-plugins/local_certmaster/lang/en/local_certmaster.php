<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'CertMaster readiness tracking';
$string['task_recalculate_mastery'] = 'Recalculate certification mastery scores';
$string['settingsheading'] = 'Certification frameworks';
$string['settingsdesc'] = 'Manage certification frameworks and objective mappings. Security+ SY0-701 is seeded on install.';
$string['manageframeworks'] = 'Import objectives via CSV at /local/certmaster/import.php (managers only).';
$string['importobjectives'] = 'Import objectives (CSV)';
$string['csvfile'] = 'CSV file';
$string['csvhelp'] = 'Columns: cert_shortname, domain_shortname, objective_shortname, objective_fullname';
$string['import'] = 'Import';
$string['importsuccess'] = 'Imported {$a} objectives.';
$string['overallreadiness'] = 'Overall exam readiness: {$a}%';
$string['dangerousmisconceptions'] = 'Dangerous misconceptions';
$string['certtracks'] = 'Certification tracks';
$string['task_generate_study_plans'] = 'Generate adaptive study plans';
$string['streamsettingsheading'] = 'Cloudflare Stream signing';
$string['streamsettingsdesc'] = 'Signing key PEM is loaded from /etc/moodle/cf-stream-signing-key.pem (Azure Key Vault secret cf-stream-signing-key). Configure kid and customer subdomain from the Stream dashboard.';
$string['streamsigningkid'] = 'Stream signing key ID (kid)';
$string['streamsigningkid_desc'] = 'Key ID from Cloudflare Stream → Settings → Signing keys.';
$string['streamcustomersubdomain'] = 'Stream customer subdomain';
$string['streamcustomersubdomain_desc'] = 'e.g. customer-abc123def456 from the Stream embed snippet.';
$string['streamnotconfigured'] = 'Cloudflare Stream signing is not configured. Set kid and customer subdomain in CertMaster settings.';
$string['streamsigningkeymissing'] = 'Stream signing PEM is missing. Deploy cf-stream-signing-key from Key Vault to /etc/moodle/cf-stream-signing-key.pem.';
$string['streamsignfailed'] = 'Failed to sign Stream JWT. Check signing key PEM format.';
$string['streamtestvideoid'] = 'Test Stream video ID';
$string['streamtestvideoid_desc'] = 'Optional video UID for /local/certmaster/player.php preview and E2E. Set after uploading a test clip in the Stream dashboard.';
$string['invalidcertification'] = 'The selected certification does not exist.';
$string['streamtestvideomissing'] = 'No test Stream video ID configured. Set it in CertMaster settings after uploading a test video.';
$string['streamplayer_title'] = 'Course video';
$string['privacy:metadata:attemptid'] = 'Quiz attempt identifier linked to a confidence rating.';
$string['privacy:metadata:confidence'] = 'Learner confidence rating after answering a question.';
$string['privacy:metadata:iscorrect'] = 'Whether the learner answer was correct.';
$string['privacy:metadata:attemptconfidence'] = 'Stores confidence ratings submitted during quiz attempts.';
$string['privacy:metadata:userid'] = 'Learner user id for mastery tracking.';
$string['privacy:metadata:masteryscore'] = 'Calculated mastery score for an objective.';
$string['privacy:metadata:mastery'] = 'Stores per-objective mastery scores.';
$string['studyplansummary'] = 'Focus on weakest objectives weighted by exam blueprint.';
$string['studyplanreasonmisconception'] = 'Confident-incorrect on this objective — review before moving on.';
$string['studyplanreasonlow'] = 'Low mastery on a high-weight domain — start here.';
$string['studyplanreasonbuilding'] = 'Building mastery — reinforce with a focused lesson review.';
$string['studyplanreasonmaintain'] = 'Maintain momentum with a quick review of this objective.';
