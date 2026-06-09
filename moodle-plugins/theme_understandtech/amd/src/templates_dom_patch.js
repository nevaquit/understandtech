// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Permanent fix for Moodle core/templates domReplace YUI failures.
 *
 * core/templates calls `new Y.NodeList(...)` before destroying replaced nodes. When the
 * global YUI node stack is not ready, that throws "Y.NodeList is not a constructor" and
 * every Templates.replaceNode / replaceNodeContents call fails — leaving course index,
 * My courses, and other placeholder UIs stuck on skeleton loaders.
 *
 * This module patches core/templates once at load time to use Y.all() when available, or
 * skip YUI teardown and perform a jQuery-native DOM swap (matching Moodle's own logic).
 *
 * @module theme_understandtech/templates_dom_patch
 */
define([
    'jquery',
    'core/templates',
    'core/log',
    'core_filters/events',
], function($, Templates, log, filterEvents) {
    'use strict';

    if (Templates.__utDomPatchApplied) {
        return {};
    }

    /**
     * Whether YUI NodeList can be constructed safely.
     *
     * @return {boolean}
     */
    const canConstructYuiNodeList = () => {
        if (typeof Y === 'undefined' || !Y.NodeList) {
            return false;
        }
        try {
            new Y.NodeList([]);
            return true;
        } catch (error) {
            return false;
        }
    };

    /**
     * Destroy YUI listeners on DOM nodes before replacement.
     *
     * @param {HTMLElement[]|Array} nodes Raw DOM nodes.
     * @return {void}
     */
    const safeYuiDestroy = (nodes) => {
        if (!nodes || !nodes.length) {
            return;
        }
        try {
            if (typeof Y !== 'undefined' && typeof Y.all === 'function') {
                Y.all(nodes).destroy(true);
                return;
            }
            if (canConstructYuiNodeList()) {
                new Y.NodeList(nodes).destroy(true);
            }
        } catch (error) {
            log.debug('theme_understandtech/templates_dom_patch: skipped YUI destroy');
        }
    };

    /**
     * DOM replacement mirroring core/templates domReplace without broken Y.NodeList.
     *
     * @param {JQuery|HTMLElement|string} element Target element.
     * @param {string} newHTML Rendered template HTML.
     * @param {string} newJS Template JS payload.
     * @param {boolean} replaceChildNodes Replace children only vs whole node.
     * @return {HTMLElement[]}
     */
    const domReplaceSafe = (element, newHTML, newJS, replaceChildNodes) => {
        const replaceNode = $(element);
        if (!replaceNode.length) {
            return [];
        }

        const newNodes = $(newHTML);
        if (replaceChildNodes) {
            safeYuiDestroy(replaceNode.children().get());
            replaceNode.empty();
            replaceNode.append(newNodes);
        } else {
            safeYuiDestroy(replaceNode.get());
            replaceNode.replaceWith(newNodes);
        }

        if (newJS && typeof Templates.runTemplateJS === 'function') {
            Templates.runTemplateJS(newJS);
        }

        if (filterEvents && typeof filterEvents.notifyFilterContentUpdated === 'function') {
            filterEvents.notifyFilterContentUpdated(newNodes);
        }

        return newNodes.get();
    };

    /**
     * Wrap a Templates DOM method with a YUI-safe implementation.
     *
     * @param {string} methodName Templates method to patch.
     * @param {boolean} replaceChildNodes Whether to replace child nodes only.
     * @return {void}
     */
    const patchMethod = (methodName, replaceChildNodes) => {
        const original = Templates[methodName];
        if (typeof original !== 'function') {
            return;
        }

        Templates[methodName] = function(element, newHTML, newJS) {
            if (!canConstructYuiNodeList()) {
                return domReplaceSafe(element, newHTML, newJS, replaceChildNodes);
            }

            try {
                return original.call(Templates, element, newHTML, newJS);
            } catch (error) {
                const message = String(error);
                if (message.includes('NodeList') || message.includes('Y.')) {
                    log.debug('theme_understandtech/templates_dom_patch: fallback for ' + methodName);
                    return domReplaceSafe(element, newHTML, newJS, replaceChildNodes);
                }
                throw error;
            }
        };
    };

    // Preload YUI node so native Moodle path works when possible.
    if (typeof Y !== 'undefined' && typeof Y.use === 'function') {
        Y.use('node', function() {});
    }

    patchMethod('replaceNode', false);
    patchMethod('replaceNodeContents', true);

    Templates.__utDomPatchApplied = true;
    log.debug('theme_understandtech/templates_dom_patch: applied');

    return {};
});
