<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');

require_login();

redirect(new moodle_url('/calendar/view.php'));
