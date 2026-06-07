/* ═══════════════════════════════════════════════════════════════════
   UnderstandTech — Main JavaScript
   Handles: nav toggle, FAQ accordion, scroll animations,
            counter animation, active nav state
   ═══════════════════════════════════════════════════════════════════ */

'use strict';

/* ─── Mobile Nav Toggle ──────────────────────────────────────────── */
function initNav() {
  const toggle = document.querySelector('.ut-nav-toggle');
  const links  = document.querySelector('.ut-nav-links');
  if (!toggle || !links) return;

  toggle.addEventListener('click', () => {
    const open = links.classList.toggle('open');
    toggle.setAttribute('aria-expanded', String(open));
    toggle.querySelector('.icon-open').style.display  = open ? 'none'  : '';
    toggle.querySelector('.icon-close').style.display = open ? ''      : 'none';
  });

  // Close on outside click
  document.addEventListener('click', e => {
    if (!toggle.contains(e.target) && !links.contains(e.target)) {
      links.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.querySelector('.icon-open').style.display  = '';
      toggle.querySelector('.icon-close').style.display = 'none';
    }
  });

  // Close on Escape
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && links.classList.contains('open')) {
      links.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.focus();
    }
  });
}

/* ─── Active Nav Link ────────────────────────────────────────────── */
function setActiveNav() {
  const current = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.ut-nav-links a').forEach(a => {
    const href = a.getAttribute('href');
    if (href === current || (current === '' && href === 'index.html')) {
      a.setAttribute('aria-current', 'page');
    }
  });
}

/* ─── FAQ Accordion ──────────────────────────────────────────────── */
function initFAQ() {
  document.querySelectorAll('.ut-faq-trigger').forEach(trigger => {
    trigger.addEventListener('click', () => {
      const item = trigger.closest('.ut-faq-item');
      const isOpen = item.classList.contains('open');

      // Close all
      document.querySelectorAll('.ut-faq-item.open').forEach(i => {
        i.classList.remove('open');
        i.querySelector('.ut-faq-trigger').setAttribute('aria-expanded', 'false');
      });

      // Open clicked (if was closed)
      if (!isOpen) {
        item.classList.add('open');
        trigger.setAttribute('aria-expanded', 'true');
      }
    });
  });
}

/* ─── Scroll-Reveal Animations ───────────────────────────────────── */
function initReveal() {
  if (!('IntersectionObserver' in window)) return;

  const style = document.createElement('style');
  style.textContent = `
    .reveal { opacity: 0; transform: translateY(24px); transition: opacity 0.6s cubic-bezier(0,0,0.2,1), transform 0.6s cubic-bezier(0,0,0.2,1); }
    .reveal.visible { opacity: 1; transform: none; }
    .reveal-delay-1 { transition-delay: 0.1s; }
    .reveal-delay-2 { transition-delay: 0.2s; }
    .reveal-delay-3 { transition-delay: 0.3s; }
    .reveal-delay-4 { transition-delay: 0.4s; }
    @media (prefers-reduced-motion: reduce) {
      .reveal { opacity: 1; transform: none; transition: none; }
    }
  `;
  document.head.appendChild(style);

  const obs = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('visible');
        obs.unobserve(e.target);
      }
    });
  }, { threshold: 0.05, rootMargin: '0px 0px -20px 0px' });

  document.querySelectorAll('.reveal').forEach(el => obs.observe(el));

  // Immediately reveal elements already in viewport on load
  requestAnimationFrame(() => {
    document.querySelectorAll('.reveal').forEach(el => {
      const rect = el.getBoundingClientRect();
      if (rect.top < window.innerHeight) {
        el.classList.add('visible');
        obs.unobserve(el);
      }
    });
  });
}

/* ─── Animated Counters ──────────────────────────────────────────── */
function animateCounter(el) {
  const target = parseFloat(el.dataset.target);
  const suffix = el.dataset.suffix || '';
  const prefix = el.dataset.prefix || '';
  const duration = 1800;
  const start = performance.now();

  function step(now) {
    const progress = Math.min((now - start) / duration, 1);
    // Ease-out cubic
    const eased = 1 - Math.pow(1 - progress, 3);
    const value = target * eased;
    el.textContent = prefix + (Number.isInteger(target) ? Math.round(value) : value.toFixed(1)) + suffix;
    if (progress < 1) requestAnimationFrame(step);
  }

  requestAnimationFrame(step);
}

function initCounters() {
  if (!('IntersectionObserver' in window)) return;

  const obs = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        animateCounter(e.target);
        obs.unobserve(e.target);
      }
    });
  }, { threshold: 0.2, rootMargin: '0px 0px -10px 0px' });

  document.querySelectorAll('[data-target]').forEach(el => obs.observe(el));
}

/* ─── Sticky Nav Shadow on Scroll ────────────────────────────────── */
function initNavScroll() {
  const nav = document.querySelector('.ut-nav');
  if (!nav) return;

  const handler = () => {
    nav.style.boxShadow = window.scrollY > 10
      ? '0 4px 24px rgba(11,31,58,0.25)'
      : '';
  };

  window.addEventListener('scroll', handler, { passive: true });
}

/* ─── XP Bar Animation ───────────────────────────────────────────── */
function initXPBars() {
  if (!('IntersectionObserver' in window)) return;

  const obs = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        const fill = e.target.querySelector('.ut-xp-fill');
        if (fill) {
          const target = fill.dataset.width || '0%';
          setTimeout(() => { fill.style.width = target; }, 200);
        }
        obs.unobserve(e.target);
      }
    });
  }, { threshold: 0.3 });

  document.querySelectorAll('.ut-xp-bar').forEach(el => obs.observe(el));
}

/* ─── Boot ───────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  initNav();
  setActiveNav();
  initFAQ();
  initReveal();
  initCounters();
  initNavScroll();
  initXPBars();
});
