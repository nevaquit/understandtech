/* ═══════════════════════════════════════════════════════════════════
   UnderstandTech — Shared Components
   Injects nav and footer into every page.
   Path-aware: works at domain root (understandtech.app) and in
   local preview (served from any subdirectory).
   ═══════════════════════════════════════════════════════════════════ */

'use strict';

/* Detect depth: pages/ = 1 level deep, root = 0 */
const isSubPage = window.location.pathname.includes('/pages/');
const ROOT      = isSubPage ? '../'  : '/';
const PAGES     = isSubPage ? ''     : 'pages/';
const LOGIN_URL = '/learn/login/index.php?wantsurl=%2Flearn%2Fmy%2F';

function buildNav() {
  return `
<a class="skip-link" href="#main-content">Skip to main content</a>
<nav class="ut-nav" aria-label="Main navigation">
  <a href="${ROOT}" class="ut-nav-brand" aria-label="UnderstandTech home">
    <div class="ut-nav-brand-mark" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="#0B1F3A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
        <path d="M2 17l10 5 10-5"/>
        <path d="M2 12l10 5 10-5"/>
      </svg>
    </div>
    <span class="ut-nav-brand-name">Understand<span>Tech</span></span>
  </a>

  <button class="ut-nav-toggle" aria-expanded="false" aria-controls="nav-links" aria-label="Toggle navigation">
    <svg class="icon-open" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
      <line x1="3" y1="6"  x2="21" y2="6"/>
      <line x1="3" y1="12" x2="21" y2="12"/>
      <line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
    <svg class="icon-close" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="display:none">
      <line x1="18" y1="6" x2="6" y2="18"/>
      <line x1="6"  y1="6" x2="18" y2="18"/>
    </svg>
  </button>

  <ul class="ut-nav-links" id="nav-links" role="list">
    <li><a href="${ROOT}">Home</a></li>
    <li><a href="${ROOT}${PAGES}ai-tutor.html">AI Tutor</a></li>
    <li><a href="${ROOT}${PAGES}certification.html">Certification</a></li>
    <li><a href="${ROOT}${PAGES}gamification.html">Gamification</a></li>
    <li><a href="${ROOT}${PAGES}pricing.html">Pricing</a></li>
    <li><a href="${ROOT}${PAGES}about.html">About</a></li>
    <li><a href="${LOGIN_URL}" class="ut-nav-login">Log In</a></li>
    <li><a href="${LOGIN_URL}" class="ut-nav-cta">Start Free Trial</a></li>
  </ul>
</nav>`;
}

function buildFooter() {
  return `
<footer class="ut-footer" role="contentinfo">
  <div class="ut-container">
    <div class="ut-footer-grid">
      <div class="ut-footer-brand">
        <a href="${ROOT}" class="ut-nav-brand" style="display:inline-flex;margin-bottom:1rem;" aria-label="UnderstandTech home">
          <div class="ut-nav-brand-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="#0B1F3A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 2L2 7l10 5 10-5-10-5z"/>
              <path d="M2 17l10 5 10-5"/>
              <path d="M2 12l10 5 10-5"/>
            </svg>
          </div>
          <span class="ut-nav-brand-name">Understand<span>Tech</span></span>
        </a>
        <p>The AI-powered certification platform that turns complex technology into mastery — one adaptive lesson at a time. A Veteran Owned Small Business (VOSB).</p>
      </div>

      <div class="ut-footer-col">
        <h4>Platform</h4>
        <ul>
          <li><a href="${ROOT}${PAGES}ai-tutor.html">AI Tutor</a></li>
          <li><a href="${ROOT}${PAGES}certification.html">Certification</a></li>
          <li><a href="${ROOT}${PAGES}gamification.html">Gamification</a></li>
          <li><a href="${ROOT}${PAGES}pricing.html">Pricing</a></li>
        </ul>
      </div>

      <div class="ut-footer-col">
        <h4>Company</h4>
        <ul>
          <li><a href="${ROOT}${PAGES}about.html">About</a></li>
          <li><a href="${ROOT}${PAGES}about.html#team">Team</a></li>
          <li><a href="mailto:blog@understandtech.app">Blog</a></li>
          <li><a href="mailto:careers@understandtech.app?subject=Career%20Inquiry">Careers</a></li>
        </ul>
      </div>

      <div class="ut-footer-col">
        <h4>Account</h4>
        <ul>
          <li><a href="${LOGIN_URL}">Log In</a></li>
          <li><a href="${LOGIN_URL}">Start Free Trial</a></li>
          <li><a href="mailto:privacy@understandtech.app">Privacy Policy</a></li>
          <li><a href="mailto:legal@understandtech.app">Terms of Service</a></li>
        </ul>
      </div>
    </div>

    <div class="ut-footer-bottom">
      <span>&copy; 2026 AI Tech Pros, Inc. All rights reserved. A Veteran Owned Small Business.</span>
      <div style="display:flex;gap:1.5rem;">
        <a href="mailto:privacy@understandtech.app">Privacy</a>
        <a href="mailto:legal@understandtech.app">Terms</a>
        <a href="mailto:support@understandtech.app">Contact</a>
      </div>
    </div>
  </div>
</footer>`;
}

/* Inject into page */
document.addEventListener('DOMContentLoaded', () => {
  const navTarget = document.getElementById('ut-nav-placeholder');
  if (navTarget) navTarget.outerHTML = buildNav();

  const footerTarget = document.getElementById('ut-footer-placeholder');
  if (footerTarget) footerTarget.outerHTML = buildFooter();
});
