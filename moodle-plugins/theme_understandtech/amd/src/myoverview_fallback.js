// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * My courses / block_myoverview fallback when core Templates.replaceNodeContents fails (broken YUI).
 *
 * block_myoverview/view hydrates course cards via Templates.replaceNodeContents, which depends on
 * Y.NodeList. When YUI is unavailable the skeleton placeholders never swap for real cards.
 *
 * @module theme_understandtech/myoverview_fallback
 */
define([
    'block_myoverview/repository',
    'core/templates',
    'core/log',
], function(Repository, Templates, log) {
    'use strict';

    const TEMPLATES = {
        card: 'block_myoverview/view-cards',
        list: 'block_myoverview/view-list',
        summary: 'block_myoverview/view-summary',
        nocourses: 'core_course/no-courses',
    };

    const DEFAULT_LIMIT = 12;

    /**
     * Whether the courses region already shows hydrated course cards.
     *
     * @param {HTMLElement} coursesView The courses-view region element.
     * @return {boolean}
     */
    const hasCourseOverviewContent = (coursesView) => {
        if (!coursesView) {
            return false;
        }
        if (coursesView.querySelector('[data-region="paged-content-container"]')) {
            return true;
        }
        return !!coursesView.querySelector(
            '.coursename a, .dashboard-card a, a[href*="course/view.php"]',
        );
    };

    /**
     * Whether skeleton placeholders are still visible.
     *
     * @param {HTMLElement} coursesView The courses-view region element.
     * @return {boolean}
     */
    const stillShowingPlaceholders = (coursesView) => {
        return !!coursesView.querySelector('.placeholders, .placeholder-tool');
    };

    /**
     * Read filter preferences from the courses-view data attributes.
     *
     * @param {HTMLElement} coursesView The courses-view region element.
     * @return {{display: string, grouping: string, sort: string, displaycategories: string, customfieldname: string, customfieldvalue: string}}
     */
    const getFilterValues = (coursesView) => {
        return {
            display: coursesView.getAttribute('data-display') || 'card',
            grouping: coursesView.getAttribute('data-grouping') || 'all',
            sort: coursesView.getAttribute('data-sort') || 'fullname',
            displaycategories: coursesView.getAttribute('data-displaycategories') || 'off',
            customfieldname: coursesView.getAttribute('data-customfieldname') || '',
            customfieldvalue: coursesView.getAttribute('data-customfieldvalue') || '',
        };
    };

    /**
     * Replace children of a node without Templates.replaceNodeContents (no YUI).
     *
     * @param {HTMLElement} target Container whose children will be replaced.
     * @param {string} html Rendered Mustache HTML.
     * @param {string} js Template JS payload from renderForPromise.
     * @return {Promise<void>}
     */
    const insertHtml = (target, html, js) => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();

        while (target.firstChild) {
            target.removeChild(target.firstChild);
        }
        while (wrapper.firstChild) {
            target.appendChild(wrapper.firstChild);
        }

        if (!js) {
            return Promise.resolve();
        }

        if (typeof Templates.runTemplateJS === 'function') {
            return Templates.runTemplateJS(js);
        }

        return Promise.resolve();
    };

    /**
     * Render enrolled courses (or empty state) into the courses-view region.
     *
     * @param {HTMLElement} coursesView The courses-view region element.
     * @param {object|null} coursesData Webservice response with a courses array.
     * @return {Promise<void>}
     */
    const renderCourses = async (coursesView, coursesData) => {
        const filters = getFilterValues(coursesView);
        let template = TEMPLATES.card;
        if (filters.display === 'list') {
            template = TEMPLATES.list;
        } else if (filters.display === 'summary') {
            template = TEMPLATES.summary;
        }

        const courses = coursesData && Array.isArray(coursesData.courses)
            ? coursesData.courses
            : (coursesData && coursesData.courses ? Object.values(coursesData.courses) : []);

        if (!courses.length) {
            const {html, js} = await Templates.renderForPromise(TEMPLATES.nocourses, {
                nocoursesimg: coursesView.getAttribute('data-nocoursesimg'),
                newcourseurl: coursesView.getAttribute('data-newcourseurl'),
            });
            await insertHtml(coursesView, html, js);
            return;
        }

        const payload = courses.map((course) => {
            course.showcoursecategory = filters.displaycategories === 'on';
            return course;
        });

        const {html, js} = await Templates.renderForPromise(template, {courses: payload});
        await insertHtml(coursesView, html, js);
    };

    /**
     * Fetch and inject course cards when native block_myoverview hydration failed.
     *
     * @param {HTMLElement} blockRoot The myoverview block root element.
     * @return {Promise<boolean>} True when injection succeeded.
     */
    const injectMyOverview = async (blockRoot) => {
        const coursesView = blockRoot.querySelector('[data-region="courses-view"]');
        if (!coursesView) {
            return false;
        }

        if (hasCourseOverviewContent(coursesView) && !stillShowingPlaceholders(coursesView)) {
            return true;
        }

        const filters = getFilterValues(coursesView);
        const paging = parseInt(coursesView.getAttribute('data-paging') || '', 10);
        const limit = Number.isFinite(paging) && paging > 0 ? paging : DEFAULT_LIMIT;

        try {
            const params = {
                offset: 0,
                limit: limit,
                classification: filters.grouping,
                sort: filters.sort,
                customfieldname: filters.customfieldname,
                customfieldvalue: filters.customfieldvalue,
                requiredfields: filters.display === 'summary'
                    ? Repository.SUMMARY_REQUIRED_FIELDS
                    : Repository.CARDLIST_REQUIRED_FIELDS,
            };

            const coursesData = await Repository.getEnrolledCoursesByTimeline(params);
            await renderCourses(coursesView, coursesData);
            log.debug('theme_understandtech/myoverview_fallback: injected courses via DOM fallback');
            return true;
        } catch (error) {
            log.error('theme_understandtech/myoverview_fallback: ' + error);
            return false;
        }
    };

    /**
     * Poll until native hydration completes or attempts are exhausted.
     *
     * @param {HTMLElement} blockRoot The myoverview block root element.
     * @param {number} attempts Maximum poll attempts.
     * @param {number} delayMs Delay between attempts in milliseconds.
     * @return {Promise<boolean>} True when hydrated content is present.
     */
    const waitForNativeHydration = (blockRoot, attempts, delayMs) => {
        return new Promise((resolve) => {
            let remaining = attempts;
            const poll = () => {
                const coursesView = blockRoot.querySelector('[data-region="courses-view"]');
                if (coursesView && hasCourseOverviewContent(coursesView) && !stillShowingPlaceholders(coursesView)) {
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

    return {
        /**
         * Initialise the fallback hydrator on My courses / dashboard pages.
         *
         * Polls briefly for native hydration, then injects course cards if skeletons remain.
         */
        init: function() {
            const run = async () => {
                const blocks = document.querySelectorAll('[data-region="myoverview"]');
                for (const block of blocks) {
                    const hydrated = await waitForNativeHydration(block, 16, 250);
                    if (!hydrated) {
                        await injectMyOverview(block);
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
