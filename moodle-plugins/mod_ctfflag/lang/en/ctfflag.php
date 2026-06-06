<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'CTF lab flag';
$string['modulenameplural'] = 'CTF lab flags';
$string['pluginname'] = 'CTF lab flag';
$string['pluginadministration'] = 'CTF lab flag administration';
$string['ctfflagname'] = 'Activity name';
$string['intro'] = 'Lab instructions';
$string['expectedflagregex'] = 'Expected flag pattern';
$string['expectedflagregex_help'] = 'PCRE pattern used to validate submissions (for example UT\\{[A-Za-z0-9_\\-]+\\}). Flag values are never stored in plain text.';
$string['xpaward'] = 'XP award';
$string['xpaward_help'] = 'XP points awarded on success when Level Up XP is configured. Stored for future gamification integration.';
$string['completionrequired'] = 'Require successful flag for completion';
$string['flagvalue'] = 'Flag value';
$string['submitflag'] = 'Submit flag';
$string['flagsuccess'] = 'Correct flag — lab complete!';
$string['flagincorrect'] = 'That flag does not match. Review the lab instructions and try again.';
$string['alreadycompleted'] = 'You have already captured this flag.';
$string['submitnotallowed'] = 'You can view this lab but cannot submit a flag.';
$string['noinstances'] = 'No CTF flag activities in this course yet.';
$string['eventflag_submitted'] = 'CTF flag submitted';
$string['privacy:metadata:ctfflag_submissions'] = 'Records whether a learner submitted a correct flag for an activity.';
$string['privacy:metadata:ctfflag_submissions:userid'] = 'The user who submitted the flag.';
$string['privacy:metadata:ctfflag_submissions:success'] = 'Whether the submission matched the expected pattern.';
$string['privacy:metadata:ctfflag_submissions:timecreated'] = 'When the submission was recorded.';
$string['privacy:metadata:nullprovider'] = 'The CTF lab flag module does not expose additional personal data beyond submission records.';
