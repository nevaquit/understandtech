// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Quiz question flag fallback when core YUI M.core_question_flags.init does not run.
 *
 * Moodle registers flag toggles via Y.use('core_question_flags'). When the YUI stack
 * fails to boot, the raw checkbox+label is left in place: local checkbox toggles but
 * no AJAX persists the flag and the icon/text never update. This module transforms
 * the DOM like core flags.js and binds fetch handlers when YUI init is unavailable.
 *
 * @module theme_understandtech/quiz_flag_fallback
 */
define([
    'core/log',
], function(log) {
    'use strict';

    const EDITABLE_SELECTOR = 'div.questionflag.editable';
    const WAIT_MS = 400;

    /**
     * Whether any editable flag still has the pre-init checkbox markup.
     *
     * @return {boolean}
     */
    const needsFallback = () => {
        return !!document.querySelector(EDITABLE_SELECTOR + ' input[type=checkbox]');
    };

    /**
     * Parse action URL and flag attributes from Moodle's inline init script.
     *
     * @return {{actionurl: string, flagattributes: Object[]}|null}
     */
    const parseFlagConfig = () => {
        const scripts = document.querySelectorAll('script');
        for (let i = 0; i < scripts.length; i++) {
            const text = scripts[i].textContent;
            if (!text.includes('M.core_question_flags.init')) {
                continue;
            }
            const match = text.match(
                /M\.core_question_flags\.init\(Y,\s*"([^"]+)",\s*(\[[\s\S]*?\])\)/,
            );
            if (!match) {
                continue;
            }
            try {
                const flagattributes = JSON.parse(match[2].replace(/\\\//g, '/'));
                return {
                    actionurl: match[1].replace(/\\\//g, '/'),
                    flagattributes: flagattributes,
                };
            } catch (error) {
                log.debug('theme_understandtech/quiz_flag_fallback: JSON parse failed');
            }
        }

        const flagdiv = document.querySelector(EDITABLE_SELECTOR);
        if (!flagdiv) {
            return null;
        }

        const labelImg = flagdiv.querySelector('label .questionflagimage');
        const unflaggedSrc = labelImg ? labelImg.getAttribute('src') : '';
        const flaggedSrc = unflaggedSrc.replace(/unflagged/g, 'flagged');
        const labelText = flagdiv.querySelector('label span')?.textContent?.trim() || 'Flag question';

        return {
            actionurl: (window.M?.cfg?.wwwroot || '') + '/question/toggleflag.php',
            flagattributes: [
                {
                    src: unflaggedSrc,
                    title: labelText,
                    alt: labelText,
                    text: labelText,
                },
                {
                    src: flaggedSrc,
                    title: 'Remove flag',
                    alt: 'Remove flag',
                    text: 'Remove flag',
                },
            ],
        };
    };

    /**
     * Update toggle button content to match flag value (mirrors core update_flag).
     *
     * @param {HTMLInputElement} input Hidden value input.
     * @param {HTMLElement} toggle Toggle anchor.
     * @param {Object[]} flagattributes Flag state metadata from core.
     * @return {void}
     */
    const updateToggle = (input, toggle, flagattributes) => {
        const value = parseInt(input.value, 10) || 0;
        const attrs = flagattributes[value] || flagattributes[0];
        toggle.innerHTML = '<img class="questionflagimage" src="' + attrs.src + '" alt="" />' + attrs.text;
        toggle.setAttribute('aria-pressed', value ? 'true' : 'false');
        toggle.setAttribute('aria-label', attrs.alt);
        if (attrs.title && attrs.title !== attrs.text) {
            toggle.setAttribute('title', attrs.title);
        } else {
            toggle.removeAttribute('title');
        }
    };

    /**
     * Transform checkbox+label into hidden input + a.aabtn (mirrors core init).
     *
     * @param {HTMLElement} flagdiv Root flag container.
     * @param {Object[]} flagattributes Flag state metadata from core.
     * @return {void}
     */
    const transformFlag = (flagdiv, flagattributes) => {
        const checkbox = flagdiv.querySelector('input[type=checkbox]');
        if (!checkbox) {
            return;
        }

        const input = document.createElement('input');
        input.type = 'hidden';
        input.className = 'questionflagvalue';
        input.id = checkbox.id;
        input.name = checkbox.name;
        input.value = checkbox.checked ? '1' : '0';

        const toggle = document.createElement('a');
        toggle.href = '#';
        toggle.tabIndex = 0;
        toggle.className = 'aabtn';
        toggle.setAttribute('role', 'button');
        updateToggle(input, toggle, flagattributes);

        checkbox.remove();
        const label = flagdiv.querySelector('label');
        if (label) {
            label.remove();
        }

        flagdiv.appendChild(input);
        flagdiv.appendChild(toggle);
    };

    /**
     * Toggle flag state and POST to Moodle (mirrors core process).
     *
     * @param {HTMLElement} flagdiv Root flag container.
     * @param {{actionurl: string, flagattributes: Object[]}} config Flag config.
     * @return {void}
     */
    const processFlag = (flagdiv, config) => {
        const input = flagdiv.querySelector('input.questionflagvalue');
        const toggle = flagdiv.querySelector('[aria-pressed]');
        const postField = flagdiv.querySelector('input.questionflagpostdata');
        if (!input || !toggle || !postField) {
            return;
        }

        input.value = String(1 - (parseInt(input.value, 10) || 0));
        updateToggle(input, toggle, config.flagattributes);

        const postdata = postField.value + input.value;
        fetch(config.actionurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: postdata,
            credentials: 'same-origin',
        }).catch((error) => {
            log.debug('theme_understandtech/quiz_flag_fallback: POST failed ' + error);
        });
    };

    /**
     * Bind click/keyboard handlers on transformed flag containers.
     *
     * @param {{actionurl: string, flagattributes: Object[]}} config Flag config.
     * @return {void}
     */
    const bindHandlers = (config) => {
        document.querySelectorAll('.questionflag.ut-flag-ready').forEach((flagdiv) => {
            if (flagdiv.dataset.utFlagBound === '1') {
                return;
            }
            flagdiv.dataset.utFlagBound = '1';

            flagdiv.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                processFlag(flagdiv, config);
            });

            flagdiv.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    processFlag(flagdiv, config);
                }
            });
        });
    };

    /**
     * Attempt core YUI init; return true when delegate handlers were registered.
     *
     * @param {{actionurl: string, flagattributes: Object[]}} config Flag config.
     * @return {boolean}
     */
    const tryCoreInit = (config) => {
        if (typeof Y === 'undefined' || typeof Y.use !== 'function') {
            return false;
        }
        if (typeof M === 'undefined' || !M.core_question_flags || typeof M.core_question_flags.init !== 'function') {
            return false;
        }
        try {
            M.core_question_flags.init(Y, config.actionurl, config.flagattributes);
            return true;
        } catch (error) {
            log.debug('theme_understandtech/quiz_flag_fallback: core init failed');
            return false;
        }
    };

    /**
     * Transform flags and bind fetch handlers when core YUI init did not run.
     *
     * @param {{actionurl: string, flagattributes: Object[]}} config Flag config.
     * @return {void}
     */
    const applyFallback = (config) => {
        document.querySelectorAll(EDITABLE_SELECTOR).forEach((flagdiv) => {
            transformFlag(flagdiv, config.flagattributes);
            flagdiv.classList.remove('editable');
            flagdiv.classList.add('ut-flag-ready');
        });

        bindHandlers(config);
        log.debug('theme_understandtech/quiz_flag_fallback: applied');
    };

    /**
     * Initialize flag fallback when core YUI init did not transform the DOM.
     *
     * @return {void}
     */
    const run = () => {
        if (!needsFallback()) {
            return;
        }

        const config = parseFlagConfig();
        if (!config || !config.actionurl) {
            log.debug('theme_understandtech/quiz_flag_fallback: missing config');
            return;
        }

        // Let core init transform editable checkboxes first; it no-ops once we remove them.
        tryCoreInit(config);
        if (!needsFallback()) {
            return;
        }

        applyFallback(config);
    };

    return {
        /**
         * Entry point — delay briefly so a late YUI boot can still run core init first.
         *
         * @return {void}
         */
        init: function() {
            if (!document.querySelector(EDITABLE_SELECTOR)) {
                return;
            }

            const schedule = () => {
                window.setTimeout(run, WAIT_MS);
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', schedule);
            } else {
                schedule();
            }
        },
    };
});
