# mod_ctfflag

Capture-the-flag style lab activity module for hands-on certification labs.

## Readiness integration (stub)

When flag validation is implemented, call `ctfflag_notify_flag_success($cm, $instance)` to fire `\mod_ctfflag\event\flag_submitted`. The `local_certmaster` observer listens for this event toward future objective mastery recalculation.
