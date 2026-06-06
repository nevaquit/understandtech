# mod_ctfflag

Capture-the-flag style lab activity module for hands-on certification labs.

## Flag submission

Learners submit flags on `view.php`. Teachers configure a PCRE pattern (`expected_flag_regex`); submissions are validated with `mod_ctfflag\local\flag_validator` and **never stored in plain text** — only success/failure is logged in `mdl_ctfflag_submissions`.

On success the activity fires `\mod_ctfflag\event\flag_submitted`, updates completion when enabled, and posts `1.0` to the gradebook.

## Readiness integration

`local_certmaster` listens for `flag_submitted` toward future objective mastery recalculation.
