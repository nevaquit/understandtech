// This file is part of Moodle - http://moodle.org/

/**
 * Inject server-rendered course index HTML when the drawer still shows placeholders.
 *
 * @module theme_understandtech/courseindex_prerender
 */
define([
    'core/log',
], function(log) {
    'use strict';

    /**
     * Whether real course index sections are already present.
     *
     * @return {boolean}
     */
    const hasCourseIndexContent = () => {
        return !!document.querySelector(
            '#courseindex .courseindex-section, #courseindex [data-for="section"]',
        );
    };

    /**
     * Insert server-rendered HTML from M.cfg.theme_understandtech.
     *
     * @return {boolean}
     */
    const injectPrerender = () => {
        const cfg = (window.M && M.cfg && M.cfg.theme_understandtech) || {};
        const html = cfg.courseindexhtml;
        if (!html || hasCourseIndexContent()) {
            return false;
        }

        const placeholder = document.getElementById('course-index-placeholder');
        const content = document.getElementById('courseindex-content');
        if (!placeholder && !content) {
            return false;
        }

        if (placeholder) {
            placeholder.outerHTML = html.trim();
        } else {
            content.innerHTML = html.trim();
        }

        log.debug('theme_understandtech/courseindex_prerender: injected server HTML');
        return hasCourseIndexContent();
    };

    return {
        /**
         * Apply server prerender immediately on DOM ready.
         */
        init: function() {
            const run = () => {
                injectPrerender();
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', run);
            } else {
                run();
            }
        },
    };
});
