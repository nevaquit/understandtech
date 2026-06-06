// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// UnderstandTech Theme — AMD JavaScript Module
// Handles: nav scroll shadow, mobile menu, scroll-reveal, stat counters,
//          XP bar animation, password toggle, FAQ accordion.

/**
 * @module theme_understandtech/theme
 */
define(['jquery', 'core/log'], function($, log) {
    'use strict';

    // ── Navbar scroll shadow ────────────────────────────────────────────────
    var initNavbarScroll = function() {
        var navbar = document.querySelector('.ut-navbar');
        if (!navbar) return;
        var onScroll = function() {
            navbar.classList.toggle('scrolled', window.scrollY > 20);
        };
        window.addEventListener('scroll', onScroll, {passive: true});
        onScroll();
    };

    // ── Mobile menu toggle ──────────────────────────────────────────────────
    var initMobileMenu = function() {
        var btn = document.getElementById('ut-mobile-toggle');
        var nav = document.getElementById('ut-primary-nav');
        if (!btn || !nav) return;
        btn.addEventListener('click', function() {
            var isOpen = nav.classList.toggle('open');
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
        // Close on outside click
        document.addEventListener('click', function(e) {
            if (!btn.contains(e.target) && !nav.contains(e.target)) {
                nav.classList.remove('open');
                btn.setAttribute('aria-expanded', 'false');
            }
        });
    };

    // ── Scroll-reveal with IntersectionObserver ─────────────────────────────
    var initScrollReveal = function() {
        if (!('IntersectionObserver' in window)) {
            // Fallback: make everything visible immediately
            document.querySelectorAll('.reveal').forEach(function(el) {
                el.style.opacity = '1';
                el.style.transform = 'none';
            });
            return;
        }

        var style = document.createElement('style');
        style.textContent = [
            '.reveal{opacity:0;transform:translateY(24px);transition:opacity 0.55s ease,transform 0.55s ease;}',
            '.reveal.visible{opacity:1;transform:none;}',
            '@media(prefers-reduced-motion:reduce){.reveal{opacity:1;transform:none;transition:none;}}'
        ].join('');
        document.head.appendChild(style);

        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {threshold: 0.1, rootMargin: '0px 0px -40px 0px'});

        // Elements already in viewport get revealed immediately
        requestAnimationFrame(function() {
            document.querySelectorAll('.reveal').forEach(function(el) {
                var rect = el.getBoundingClientRect();
                if (rect.top < window.innerHeight) {
                    el.classList.add('visible');
                } else {
                    observer.observe(el);
                }
            });
        });
    };

    // ── Animated stat counters ──────────────────────────────────────────────
    var initCounters = function() {
        var counters = document.querySelectorAll('[data-counter]');
        if (!counters.length) return;

        var animateCounter = function(el) {
            var target = parseInt(el.getAttribute('data-counter'), 10);
            var suffix = el.getAttribute('data-suffix') || '';
            var duration = 1800;
            var start = null;

            var step = function(timestamp) {
                if (!start) start = timestamp;
                var progress = Math.min((timestamp - start) / duration, 1);
                var eased = 1 - Math.pow(1 - progress, 3); // ease-out-cubic
                var current = Math.floor(eased * target);
                el.textContent = current.toLocaleString() + suffix;
                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    el.textContent = target.toLocaleString() + suffix;
                }
            };
            requestAnimationFrame(step);
        };

        if (!('IntersectionObserver' in window)) {
            counters.forEach(animateCounter);
            return;
        }

        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, {threshold: 0.5});

        counters.forEach(function(el) {
            observer.observe(el);
        });
    };

    // ── XP bar animation ────────────────────────────────────────────────────
    var initXpBars = function() {
        var bars = document.querySelectorAll('.ut-xp-fill, .ut-progress-fill, .ut-cert-score-fill');
        if (!bars.length) return;

        if (!('IntersectionObserver' in window)) return;

        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var el = entry.target;
                    var target = el.style.width;
                    el.style.width = '0';
                    requestAnimationFrame(function() {
                        el.style.transition = 'width 1s cubic-bezier(0.4,0,0.2,1)';
                        el.style.width = target;
                    });
                    observer.unobserve(el);
                }
            });
        }, {threshold: 0.3});

        bars.forEach(function(el) {
            observer.observe(el);
        });
    };

    // ── Password visibility toggle ──────────────────────────────────────────
    var initPasswordToggle = function() {
        var btn = document.getElementById('ut-pwd-toggle');
        var inp = document.getElementById('password');
        if (!btn || !inp) return;
        btn.addEventListener('click', function() {
            var isText = inp.type === 'text';
            inp.type = isText ? 'password' : 'text';
            btn.setAttribute('aria-label', isText ? 'Show password' : 'Hide password');
            btn.style.color = isText ? 'rgba(255,255,255,0.45)' : '#C9A227';
        });
    };

    // ── FAQ accordion ───────────────────────────────────────────────────────
    var initFaqAccordion = function() {
        var items = document.querySelectorAll('.ut-faq-item');
        items.forEach(function(item) {
            var trigger = item.querySelector('.ut-faq-trigger');
            var body = item.querySelector('.ut-faq-body');
            if (!trigger || !body) return;

            trigger.setAttribute('aria-expanded', 'false');
            body.style.display = 'none';

            trigger.addEventListener('click', function() {
                var isOpen = trigger.getAttribute('aria-expanded') === 'true';

                // Close all others
                items.forEach(function(other) {
                    var otherTrigger = other.querySelector('.ut-faq-trigger');
                    var otherBody = other.querySelector('.ut-faq-body');
                    if (otherTrigger && otherBody && other !== item) {
                        otherTrigger.setAttribute('aria-expanded', 'false');
                        otherBody.style.display = 'none';
                        other.classList.remove('open');
                    }
                });

                trigger.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                body.style.display = isOpen ? 'none' : 'block';
                item.classList.toggle('open', !isOpen);
            });
        });
    };

    // ── Pricing toggle (monthly / annual) ──────────────────────────────────
    var initPricingToggle = function() {
        var toggle = document.getElementById('ut-billing-toggle');
        if (!toggle) return;

        toggle.addEventListener('change', function() {
            var isAnnual = toggle.checked;
            document.querySelectorAll('[data-monthly]').forEach(function(el) {
                el.textContent = isAnnual ? el.getAttribute('data-annual') : el.getAttribute('data-monthly');
            });
            document.querySelectorAll('.ut-annual-badge').forEach(function(el) {
                el.style.display = isAnnual ? 'inline-flex' : 'none';
            });
        });
    };

    // ── Public init ─────────────────────────────────────────────────────────
    return {
        init: function() {
            log.debug('theme_understandtech/theme: initialising');
            initNavbarScroll();
            initMobileMenu();
            initScrollReveal();
            initCounters();
            initXpBars();
            initPasswordToggle();
            initFaqAccordion();
            initPricingToggle();
        }
    };
});
