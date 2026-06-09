// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Course index drawer fallback when core Templates.replaceNode fails (broken YUI).
 *
 * Moodle's placeholder component renders the course index via Templates.replaceNode,
 * which depends on Y.NodeList. When YUI is unavailable the drawer stays on skeleton
 * placeholders — this module injects the index with native DOM and can fetch course
 * state directly via core_courseformat_get_state when the reactive editor is slow.
 *
 * @module theme_understandtech/courseindex_fallback
 */
define([
    'core/config',
    'core_courseformat/courseeditor',
    'core/templates',
    'core/log',
], function(Config, courseeditor, Templates, log) {
    'use strict';

    const POLL_NATIVE_MS = 250;
    const POLL_NATIVE_ATTEMPTS = 20;
    const POLL_INJECT_ATTEMPTS = 4;
    const POLL_INJECT_DELAY_MS = 2000;

    /**
     * Whether the drawer already contains a hydrated course index tree.
     *
     * @return {boolean}
     */
    const hasCourseIndexContent = () => {
        return !!document.querySelector(
            '#courseindex .courseindex-section, #courseindex [data-for="section"]',
        );
    };

    /**
     * Whether skeleton placeholders are still the only drawer content.
     *
     * @return {boolean}
     */
    const stillShowingPlaceholders = () => {
        if (hasCourseIndexContent()) {
            return false;
        }
        return !!document.querySelector(
            '#course-index-placeholder, #courseindex-content .placeholders, #courseindex .placeholders',
        );
    };

    /**
     * Resolve course id from init arg, M.cfg, or body data attribute.
     *
     * @param {number|null} courseId Optional course id passed from PHP.
     * @return {number|null}
     */
    const resolveCourseId = (courseId) => {
        const parsed = parseInt(courseId, 10);
        if (Number.isFinite(parsed) && parsed > 1) {
            return parsed;
        }
        const cfgId = parseInt(Config.courseId || window.M?.cfg?.courseId, 10);
        if (Number.isFinite(cfgId) && cfgId > 1) {
            return cfgId;
        }
        const bodyId = parseInt(document.body.getAttribute('data-course-id'), 10);
        if (Number.isFinite(bodyId) && bodyId > 1) {
            return bodyId;
        }
        return null;
    };

    /**
     * Wait until native hydration completes or attempts are exhausted.
     *
     * @param {number} attempts Maximum poll attempts.
     * @param {number} delayMs Delay between attempts in milliseconds.
     * @return {Promise<boolean>} True when hydrated content is present.
     */
    const waitForNativeHydration = (attempts, delayMs) => {
        return new Promise((resolve) => {
            let remaining = attempts;
            const poll = () => {
                if (hasCourseIndexContent()) {
                    resolve(true);
                    return;
                }
                remaining -= 1;
                if (remaining <= 0) {
                    resolve(false);
                    return;
                }
                window.setTimeout(poll, delayMs);
            };
            poll();
        });
    };

    /**
     * Load course state from the reactive editor or webservice fallback.
     *
     * @param {number} courseId Course id.
     * @return {Promise<{editor: object, state: object}|null>}
     */
    const loadEditorState = async (courseId) => {
        const editor = courseeditor.getCourseEditor(courseId);
        if (!editor) {
            return null;
        }

        let state = editor.state;
        if (stateHasSections(state)) {
            return {editor, state};
        }

        if (typeof editor.getServerCourseState === 'function') {
            try {
                state = await editor.getServerCourseState();
                if (stateHasSections(state)) {
                    return {editor, state};
                }
            } catch (error) {
                log.warn('theme_understandtech/courseindex_fallback: getServerCourseState failed: ' + error);
            }
        }

        return null;
    };

    /**
     * Whether a course state object contains section/module data.
     *
     * @param {object|null} state Course editor state.
     * @return {boolean}
     */
    const stateHasSections = (state) => {
        if (!state) {
            return false;
        }
        if (Array.isArray(state.section) && state.section.length > 0) {
            return true;
        }
        if (state.section && typeof state.section === 'object' && Object.keys(state.section).length > 0) {
            return true;
        }
        if (state.course && state.course.id) {
            return true;
        }
        return false;
    };

    /**
     * Insert rendered course index HTML without Templates.replaceNode (no YUI).
     *
     * @param {HTMLElement} target Node to replace (usually the loading placeholder).
     * @param {string} html Rendered Mustache HTML.
     * @param {string} js Template JS payload from renderForPromise.
     * @return {Promise<void>}
     */
    const insertCourseIndexHtml = (target, html, js) => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        const newRoot = wrapper.firstElementChild;

        if (!newRoot) {
            return Promise.reject(new Error('Empty course index template output'));
        }

        target.replaceWith(newRoot);

        if (!js) {
            return Promise.resolve();
        }

        if (typeof Templates.runTemplateJS === 'function') {
            return Templates.runTemplateJS(js);
        }

        return Promise.resolve();
    };

    /**
     * Fetch and inject the course index when the native placeholder path failed.
     *
     * @param {number} courseId Course id.
     * @return {Promise<boolean>} True when injection succeeded.
     */
    const injectCourseIndex = async (courseId) => {
        if (hasCourseIndexContent()) {
            return true;
        }

        const placeholder = document.getElementById('course-index-placeholder');
        const content = document.getElementById('courseindex-content');
        const target = placeholder || content;
        if (!target) {
            return false;
        }

        const loaded = await loadEditorState(courseId);
        if (!loaded) {
            log.warn('theme_understandtech/courseindex_fallback: course state unavailable for course ' + courseId);
            return false;
        }

        try {
            const data = loaded.editor.getExporter().course(loaded.state);
            const {html, js} = await Templates.renderForPromise(
                'core_courseformat/local/courseindex/courseindex',
                data,
            );
            await insertCourseIndexHtml(target, html, js);
            log.debug('theme_understandtech/courseindex_fallback: injected course index via DOM fallback');
            return hasCourseIndexContent();
        } catch (error) {
            log.error('theme_understandtech/courseindex_fallback: ' + error);
            return false;
        }
    };

    return {
        /**
         * Initialise the fallback hydrator on course / incourse pages.
         *
         * Polls for native hydration, then injects via webservice-backed state if skeletons remain.
         *
         * @param {number|null} courseId Optional course id from PHP.
         */
        init: function(courseId) {
            const resolvedCourseId = resolveCourseId(courseId);
            if (!resolvedCourseId) {
                log.warn('theme_understandtech/courseindex_fallback: missing course id');
                return;
            }

            const run = async () => {
                const hydrated = await waitForNativeHydration(POLL_NATIVE_ATTEMPTS, POLL_NATIVE_MS);
                if (hydrated) {
                    return;
                }

                for (let attempt = 0; attempt < POLL_INJECT_ATTEMPTS; attempt += 1) {
                    if (!stillShowingPlaceholders()) {
                        return;
                    }
                    const ok = await injectCourseIndex(resolvedCourseId);
                    if (ok) {
                        return;
                    }
                    if (attempt < POLL_INJECT_ATTEMPTS - 1) {
                        await new Promise((resolve) => {
                            window.setTimeout(resolve, POLL_INJECT_DELAY_MS);
                        });
                    }
                }
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    run();
                });
            } else {
                run();
            }
        },
    };
});
