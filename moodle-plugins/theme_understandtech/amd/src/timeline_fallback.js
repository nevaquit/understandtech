// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Timeline block fallback when core Templates.replaceNodeContents fails (broken YUI).
 *
 * block_timeline/event_list hydrates the event list via Templates.replaceNodeContents, which
 * depends on Y.NodeList. When YUI is unavailable the skeleton placeholders never swap for events.
 *
 * @module theme_understandtech/timeline_fallback
 */
define([
    'block_timeline/calendar_events_repository',
    'core/templates',
    'core/user_date',
    'core/log',
], function(CalendarEventsRepository, Templates, UserDate, log) {
    'use strict';

    const SECONDS_IN_DAY = 60 * 60 * 24;
    const DEFAULT_FIRST_LOAD = 5;
    const TEMPLATE_EVENT_LIST = 'block_timeline/event-list-content';

    /**
     * Whether the event list already shows hydrated timeline content.
     *
     * @param {HTMLElement} container The event-list-container element.
     * @return {boolean}
     */
    const hasTimelineEventContent = (container) => {
        if (!container) {
            return false;
        }

        const content = container.querySelector('[data-region="event-list-content"]');
        if (content && content.querySelector('[data-region="event-list-wrapper"]')) {
            return true;
        }

        const emptyMessage = container.querySelector('[data-region="no-events-empty-message"]');
        return !!(emptyMessage && !emptyMessage.classList.contains('hidden'));
    };

    /**
     * Whether skeleton placeholders are still visible.
     *
     * @param {HTMLElement} container The event-list-container element.
     * @return {boolean}
     */
    const stillShowingPlaceholders = (container) => {
        const placeholder = container.querySelector('[data-region="event-list-loading-placeholder"]');
        if (placeholder && !placeholder.classList.contains('hidden')) {
            return true;
        }

        return !!container.querySelector('.bg-pulse-grey.rounded-circle');
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
     * Build Mustache context grouped by day (mirrors block_timeline/event_list).
     *
     * @param {Array} calendarEvents Calendar events from the webservice.
     * @return {{courseview: boolean, eventsbyday: Array}}
     */
    const buildTemplateContext = (calendarEvents) => {
        const eventsByDay = {};
        const templateContext = {
            courseview: false,
            eventsbyday: [],
        };

        calendarEvents.forEach((calendarEvent) => {
            const dayTimestamp = calendarEvent.timeusermidnight;
            if (eventsByDay[dayTimestamp]) {
                eventsByDay[dayTimestamp].push(calendarEvent);
            } else {
                eventsByDay[dayTimestamp] = [calendarEvent];
            }
        });

        Object.keys(eventsByDay).forEach((dayTimestamp) => {
            templateContext.eventsbyday.push({
                dayTimestamp: dayTimestamp,
                events: eventsByDay[dayTimestamp],
            });
        });

        return templateContext;
    };

    /**
     * Read optional search value from the timeline block search field.
     *
     * @param {HTMLElement} container The event-list-container element.
     * @return {string|undefined}
     */
    const getSearchValue = (container) => {
        const timelineBlock = container.closest('[data-region="timeline"]');
        if (!timelineBlock) {
            return undefined;
        }

        const searchInput = timelineBlock.querySelector('[data-action="search"]');
        if (!searchInput || typeof searchInput.value !== 'string') {
            return undefined;
        }

        const value = searchInput.value.trim();
        return value === '' ? undefined : value;
    };

    /**
     * Filter calendar events the same way block_timeline/event_list does.
     *
     * @param {Array} events Raw events from the webservice.
     * @param {number} midnight User midnight unix timestamp.
     * @return {Array}
     */
    const filterCalendarEvents = (events, midnight) => {
        const overdueFilter = document.querySelector("[data-filtername='overdue']");
        const filterByOverdue = !!(overdueFilter && overdueFilter.getAttribute('aria-current'));

        return events.filter((event) => {
            if (event.eventtype === 'open' || event.eventtype === 'opensubmission') {
                const dayTimestamp = UserDate.getUserMidnightForTimestamp(event.timesort, midnight);
                return dayTimestamp > midnight;
            }

            return !filterByOverdue || event.overdue;
        });
    };

    /**
     * Toggle visible regions after render (mirrors event_list hideContent/showContent).
     *
     * @param {HTMLElement} container The event-list-container element.
     * @param {boolean} hasContent Whether events were rendered.
     * @return {void}
     */
    const setContentVisibility = (container, hasContent) => {
        const content = container.querySelector('[data-region="event-list-content"]');
        const emptyMessage = container.querySelector('[data-region="no-events-empty-message"]');
        const loadingPlaceholder = container.querySelector('[data-region="event-list-loading-placeholder"]');

        if (loadingPlaceholder) {
            loadingPlaceholder.classList.add('hidden');
        }

        if (hasContent) {
            if (content) {
                content.classList.remove('hidden');
            }
            if (emptyMessage) {
                emptyMessage.classList.add('hidden');
            }
            return;
        }

        if (content) {
            content.classList.add('hidden');
        }
        if (emptyMessage) {
            emptyMessage.classList.remove('hidden');
        }
    };

    /**
     * Fetch calendar events for the container's filter attributes.
     *
     * @param {HTMLElement} container The event-list-container element.
     * @return {Promise<Array>}
     */
    const fetchCalendarEvents = async (container) => {
        const midnight = parseInt(container.getAttribute('data-midnight') || '', 10);
        const daysOffset = parseInt(container.getAttribute('data-days-offset') || '0', 10);
        const daysLimitAttr = container.getAttribute('data-days-limit');
        let daysLimit;
        if (daysLimitAttr !== null && daysLimitAttr !== '') {
            daysLimit = parseInt(daysLimitAttr, 10);
        }

        const courseIdAttr = container.getAttribute('data-course-id');
        const searchValue = getSearchValue(container);
        const itemLimit = DEFAULT_FIRST_LOAD;
        const startTime = midnight + (daysOffset * SECONDS_IN_DAY);
        const endTime = daysLimit !== undefined ? midnight + (daysLimit * SECONDS_IN_DAY) : false;

        const args = {
            starttime: startTime,
            limit: itemLimit + 1,
        };

        if (endTime) {
            args.endtime = endTime;
        }

        if (searchValue) {
            args.searchvalue = searchValue;
        }

        let result;
        if (courseIdAttr) {
            args.courseid = parseInt(courseIdAttr, 10);
            result = await CalendarEventsRepository.queryByCourse(args);
        } else {
            result = await CalendarEventsRepository.queryByTime(args);
        }

        if (!result || !Array.isArray(result.events) || !result.events.length) {
            return [];
        }

        let calendarEvents = filterCalendarEvents(result.events, midnight);
        if (calendarEvents.length > itemLimit) {
            calendarEvents = calendarEvents.slice(0, itemLimit);
        }

        return calendarEvents;
    };

    /**
     * Render events into the event-list-content region.
     *
     * @param {HTMLElement} container The event-list-container element.
     * @param {Array} calendarEvents Filtered calendar events.
     * @return {Promise<void>}
     */
    const renderEvents = async (container, calendarEvents) => {
        const content = container.querySelector('[data-region="event-list-content"]');
        if (!content) {
            return;
        }

        if (!calendarEvents.length) {
            setContentVisibility(container, false);
            return;
        }

        const templateContext = buildTemplateContext(calendarEvents);
        const {html, js} = await Templates.renderForPromise(TEMPLATE_EVENT_LIST, templateContext);
        await insertHtml(content, html, js);
        setContentVisibility(container, true);
    };

    /**
     * Fetch and inject timeline events when native hydration failed.
     *
     * @param {HTMLElement} container The event-list-container element.
     * @return {Promise<boolean>} True when injection succeeded.
     */
    const injectTimelineEvents = async (container) => {
        if (!container) {
            return false;
        }

        if (hasTimelineEventContent(container) && !stillShowingPlaceholders(container)) {
            return true;
        }

        try {
            const calendarEvents = await fetchCalendarEvents(container);
            await renderEvents(container, calendarEvents);
            log.debug('theme_understandtech/timeline_fallback: injected timeline events via DOM fallback');
            return true;
        } catch (error) {
            log.error('theme_understandtech/timeline_fallback: ' + error);
            return false;
        }
    };

    /**
     * Poll until native hydration completes or attempts are exhausted.
     *
     * @param {HTMLElement} container The event-list-container element.
     * @param {number} attempts Maximum poll attempts.
     * @param {number} delayMs Delay between attempts in milliseconds.
     * @return {Promise<boolean>} True when hydrated content is present.
     */
    const waitForNativeHydration = (container, attempts, delayMs) => {
        return new Promise((resolve) => {
            let remaining = attempts;
            const poll = () => {
                if (hasTimelineEventContent(container) && !stillShowingPlaceholders(container)) {
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
     * Collect timeline event list containers on dashboard / my courses pages.
     *
     * @return {HTMLElement[]}
     */
    const findTimelineContainers = () => {
        const selectors = [
            '[data-region="timeline"] [data-region="event-list-container"]',
            '[data-region="timeline-view"] [data-region="event-list-container"]',
        ];
        const seen = new Set();
        const containers = [];

        selectors.forEach((selector) => {
            document.querySelectorAll(selector).forEach((element) => {
                if (!seen.has(element)) {
                    seen.add(element);
                    containers.push(element);
                }
            });
        });

        return containers;
    };

    return {
        /**
         * Initialise the fallback hydrator on My courses / dashboard pages.
         *
         * Polls briefly for native hydration, then injects timeline events if skeletons remain.
         */
        init: function() {
            const run = async () => {
                const containers = findTimelineContainers();
                for (const container of containers) {
                    const hydrated = await waitForNativeHydration(container, 16, 250);
                    if (!hydrated) {
                        await injectTimelineEvents(container);
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
