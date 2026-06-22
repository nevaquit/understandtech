// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Instant AJAX flag submission for mod_ctfflag labs.
 *
 * @module mod_ctfflag/lab_workspace
 */
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    'use strict';

    const FLAG_FORMAT = /^UT\{[A-Za-z0-9_\-]+\}$/;

    /**
     * @param {Object} config
     * @return {void}
     */
    const init = (config) => {
        if (config.completed) {
            return;
        }

        const form = document.querySelector('.ut-ctfflag-form form.mform');
        const feedback = document.getElementById('ut-lab-feedback');
        const input = form ? form.querySelector('#id_flagvalue, [name="flagvalue"]') : null;
        const submitBtn = form ? form.querySelector('#id_submitbutton, button[type="submit"]') : null;

        if (!form || !input || !feedback) {
            return;
        }

        form.addEventListener('submit', (event) => {
            event.preventDefault();

            const value = (input.value || '').trim();
            if (!value) {
                feedback.textContent = config.formatInvalid || '';
                feedback.className = 'ut-lab-feedback ut-lab-feedback--error';
                return;
            }

            if (!FLAG_FORMAT.test(value)) {
                feedback.textContent = config.formatInvalid || '';
                feedback.className = 'ut-lab-feedback ut-lab-feedback--error';
                input.focus();
                return;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
            }
            feedback.textContent = config.submitting || '';
            feedback.className = 'ut-lab-feedback ut-lab-feedback--pending';

            Ajax.call([{
                methodname: 'mod_ctfflag_submit_flag',
                args: {
                    cmid: config.cmid,
                    flagvalue: value,
                },
            }])[0].then((result) => {
                feedback.textContent = result.message;
                if (result.success) {
                    feedback.className = 'ut-lab-feedback ut-lab-feedback--success';
                    input.disabled = true;
                    form.classList.add('ut-ctfflag-complete');
                    const workspace = document.querySelector('.ut-lab-workspace');
                    if (workspace) {
                        workspace.classList.add('ut-lab-workspace--complete');
                    }
                    if (submitBtn) {
                        submitBtn.disabled = true;
                    }
                } else {
                    feedback.className = 'ut-lab-feedback ut-lab-feedback--error';
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                    input.focus();
                }
                return result;
            }).catch((error) => {
                Notification.exception(error);
                feedback.className = 'ut-lab-feedback ut-lab-feedback--error';
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            });
        });
    };

    return {
        init: init,
    };
});
