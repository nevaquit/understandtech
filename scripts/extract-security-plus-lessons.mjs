#!/usr/bin/env node
/**
 * Extract SY0-701 objective lesson HTML from CyberKraft security_plus_study_guide.md.
 *
 * Usage:
 *   node scripts/extract-security-plus-lessons.mjs [path-to-study-guide.md]
 *
 * Default source:
 *   %USERPROFILE%/OneDrive/Documents/_2026/CyberKraft Training/CompTIA Security +/security_plus_study_guide.md
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, '..');
const outDir = path.join(repoRoot, 'content', 'security-plus', 'lessons');
const diagramDir = path.join(repoRoot, 'content', 'security-plus', 'diagrams');

const defaultSource = path.join(
  process.env.USERPROFILE || '',
  'OneDrive',
  'Documents',
  '_2026',
  'CyberKraft Training',
  'CompTIA Security +',
  'security_plus_study_guide.md'
);

const sourcePath = path.resolve(process.argv[2] || defaultSource);

/** @param {string} md */
function markdownToHtml(md) {
  const lines = md.replace(/\r\n/g, '\n').split('\n');
  const out = [];
  let para = [];
  /** @type {number[]} open list indent levels (spaces) */
  const ulIndents = [];

  const flushPara = () => {
    if (!para.length) {
      return;
    }
    out.push(`<p>${inlineFormat(para.join(' '))}</p>`);
    para = [];
  };

  const closeAllLists = () => {
    while (ulIndents.length) {
      out.push('</ul>');
      ulIndents.pop();
    }
  };

  const closeListsAbove = (indent) => {
    while (ulIndents.length && ulIndents[ulIndents.length - 1] > indent) {
      out.push('</ul>');
      ulIndents.pop();
    }
  };

  for (const raw of lines) {
    const trimmed = raw.trimEnd();

    if (/^## .+/.test(trimmed.trim())) {
      flushPara();
      closeAllLists();
      break;
    }

    if (trimmed.startsWith('![')) {
      flushPara();
      closeAllLists();
      continue;
    }

    if (/^---+$/.test(trimmed.trim())) {
      flushPara();
      closeAllLists();
      out.push('<hr />');
      continue;
    }

    const h5 = trimmed.match(/^##### (.+)$/);
    if (h5) {
      flushPara();
      closeAllLists();
      out.push(`<h5>${inlineFormat(h5[1])}</h5>`);
      continue;
    }

    const h4 = trimmed.match(/^#### (.+)$/);
    if (h4) {
      flushPara();
      closeAllLists();
      out.push(`<h4>${inlineFormat(h4[1])}</h4>`);
      continue;
    }

    const bullet = trimmed.match(/^(\s*)- (.+)$/);
    if (bullet) {
      flushPara();
      const indent = bullet[1].replace(/\t/g, '  ').length;
      closeListsAbove(indent);
      if (!ulIndents.length || ulIndents[ulIndents.length - 1] < indent) {
        out.push('<ul>');
        ulIndents.push(indent);
      }
      out.push(`<li>${inlineFormat(bullet[2])}</li>`);
      continue;
    }

    if (trimmed.trim() === '') {
      flushPara();
      closeAllLists();
      continue;
    }

    closeAllLists();
    para.push(trimmed.trim());
  }

  flushPara();
  closeAllLists();
  return out.join('\n');
}

/** @param {string} text */
function inlineFormat(text) {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    .replace(/`([^`]+)`/g, '<code>$1</code>');
}

/** @param {string} body */
function wrapLesson(code, title, body) {
  const display = code.replace(/^sy701_/, '').replace(/_/g, '.');
  const escTitle = inlineFormat(title);
  return [
    '<div class="ut-lesson-content">',
    `<h3>Exam objective ${display}</h3>`,
    `<p><strong>${escTitle}</strong></p>`,
    '<div class="ut-lesson-body">',
    body,
    '</div>',
    '<h4>Next steps</h4>',
    '<p>Complete the domain knowledge check quiz after this lesson and use the AI tutor for scenario-based practice. '
      + 'The tutor guides you Socratically without revealing assessment answers.</p>',
    '</div>',
  ].join('\n');
}

if (!fs.existsSync(sourcePath)) {
  console.error(`Source not found: ${sourcePath}`);
  process.exit(1);
}

const text = fs.readFileSync(sourcePath, 'utf8');
const headingRe = /^### (\d+)\.(\d+) (.+)$/gm;
const matches = [...text.matchAll(headingRe)];

/** @type {Map<string, {title: string, body: string}>} */
const sections = new Map();

for (let i = 0; i < matches.length; i++) {
  const m = matches[i];
  const key = `${m[1]}.${m[2]}`;
  const start = m.index + m[0].length;
  const end = i + 1 < matches.length ? matches[i + 1].index : text.length;
  let body = text.slice(start, end).trim();
  const sectionBreak = body.search(/^## (?!#)/m);
  if (sectionBreak >= 0) {
    body = body.slice(0, sectionBreak).trim();
  }
  if (body.includes('*empts to stop a security incident')) {
    body = body.split('\n').filter((l) => !l.includes('*empts to stop')).join('\n').trim();
  }
  const candidate = { title: m[3].trim(), body };
  const existing = sections.get(key);
  if (!existing || candidate.body.length > existing.body.length) {
    sections.set(key, candidate);
  }
}

fs.mkdirSync(outDir, { recursive: true });

const expected = [];
for (let d = 1; d <= 5; d++) {
  const count = d === 4 ? 9 : d === 2 ? 5 : d === 1 || d === 3 ? 4 : 6;
  for (let o = 1; o <= count; o++) {
    expected.push(`${d}.${o}`);
  }
}

let written = 0;
for (const key of expected) {
  const section = sections.get(key);
  if (!section) {
    console.error(`Missing section for objective ${key}`);
    process.exit(1);
  }
  const shortname = `sy701_${key.replace('.', '_')}`;
  let html = wrapLesson(shortname, section.title, markdownToHtml(section.body));
  const diagramPath = path.join(diagramDir, `${shortname}.html`);
  if (fs.existsSync(diagramPath)) {
    const diagram = fs.readFileSync(diagramPath, 'utf8').trim();
    html = html.replace(
      '</div>\n<h4>Next steps</h4>',
      `${diagram}\n</div>\n<h4>Next steps</h4>`
    );
  }
  const outfile = path.join(outDir, `${shortname}.html`);
  fs.writeFileSync(outfile, html, 'utf8');
  written++;
  console.log(`wrote ${path.relative(repoRoot, outfile)} (${section.body.length} chars source)`);
}

console.log(`extracted=${written} source=${sourcePath}`);
