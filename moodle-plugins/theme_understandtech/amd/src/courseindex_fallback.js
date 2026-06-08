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
 * which depends on Y.NodeList. When YUI is unavailable the drawer stays empty while
 * our CSS hides the aria-hidden skeleton — this module injects the index with native DOM.
 *
 * @module theme_understandtech/courseindex_fallback
 */
define([
    'core_courseformat/courseeditor',
    'core/templates',
    'core/log',
], function(courseeditor, Templates, log) {
    'use strict';

    /**
     * Whether the drawer already contains a hydrated course index tree.
     *
     * @return {boolean}
     */
    const hasCourseIndexContent = () => {
        return !!document.querySelector('#courseindex .courseindex-section, #courseindex [data-for="section"]');
    };

    /**
     * Wait for the reactive course editor state to become available.
     *
     * @param {number} attempts Maximum poll attempts.
     * @param {number} delayMs Delay between attempts in milliseconds.
     * @return {Promise<object|null>}
     */
    const waitForEditorState = (attempts, delayMs) => {
        return new Promise((resolve) => {
            let remaining = attempts;
            const poll = () => {
                const editor = courseeditor.getCurrentCourseEditor();
                if (editor && editor.state) {
                    resolve(editor);
                    return;
                }
                remaining -= 1;
                if (remaining <= 0) {
                    resolve(null);
                    return;
                }
                window.setTimeout(poll, delayMs);
            };
            poll();
        });
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
     * @return {Promise<boolean>} True when injection succeeded.
     */
    const injectCourseIndex = async () => {
        if (hasCourseIndexContent()) {
            return true;
        }

        const placeholder = document.getElementById('course-index-placeholder');
        const content = document.getElementById('courseindex-content');
        const target = placeholder || content;
        if (!target) {
            return false;
        }

        const editor = await waitForEditorState(40, 250);
        if (!editor) {
            log.warn('theme_understandtech/courseindex_fallback: course editor state unavailable');
            return false;
        }

        try {
            const data = editor.getExporter().course(editor.state);
            const {html, js} = await Templates.renderForPromise(
                'core_courseformat/local/courseindex/courseindex',
                data,
            );
            await insertCourseIndexHtml(target, html, js);
            log.debug('theme_understandtech/courseindex_fallback: injected course index via DOM fallback');
            return true;
        } catch (error) {
            log.error('theme_understandtech/courseindex_fallback: ' + error);
            return false;
        }
    };

    return {
        /**
         * Initialise the fallback hydrator on course / incourse pages.
         *
         * Polls briefly for native hydration, then injects server-rendered index if still empty.
         */
        init: function() {
            const run = async () => {
                // Allow Moodle's native placeholder component to hydrate first.
                await waitForEditorState(12, 250);
                if (hasCourseIndexContent()) {
                    return;
                }
                await injectCourseIndex();
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
