#!/usr/bin/env node
/**
 * Extract SY0-701 objective supplement HTML from CompTIA Ebooks (Exam Cram handouts).
 *
 * Sources (default under OneDrive Ebooks):
 *   Security Plus SY0-701 Domain N Handout.pdf
 *
 * Usage:
 *   node scripts/extract-security-plus-ebooks.mjs [ebooks-root]
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { PDFParse } from 'pdf-parse';
import { sanitizeEbookHtml, sanitizeEbookText } from './lib/sanitize-ebook-text.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, '..');
const outDir = path.join(repoRoot, 'content', 'security-plus', 'supplements');

const defaultRoot = path.join(
  process.env.USERPROFILE || '',
  'OneDrive',
  'Ebooks',
  'Comptia',
  'Security +'
);

const ebooksRoot = path.resolve(process.argv[2] || defaultRoot);

const domainFiles = [
  { domain: 1, file: 'Security Plus SY0-701 Domain 1 Handout.pdf', objectives: 4 },
  { domain: 2, file: 'Security Plus SY0-701 Domain 2 HANDOUT.pdf', objectives: 5 },
  { domain: 3, file: 'Security Plus SY0-701 Domain 3 Handout.pdf', objectives: 4 },
  { domain: 4, file: 'Security Plus SY0-701 Domain 4 Handout.pdf', objectives: 9 },
  { domain: 5, file: 'Security Plus SY0-701 Domain 5 Handout.pdf', objectives: 6 },
];

/** @param {string} text */
function inlineFormat(text) {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
}

/** @param {string} raw */
function textToHtml(raw) {
  const lines = raw.replace(/\r\n/g, '\n').split('\n');
  const out = [];
  let para = [];
  let inUl = false;
  const ulIndents = [];

  const flushPara = () => {
    if (!para.length) {
      return;
    }
    const text = para.join(' ').replace(/\s+/g, ' ').trim();
    if (text) {
      out.push(`<p>${inlineFormat(text)}</p>`);
    }
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

  for (const rawLine of lines) {
    const line = rawLine.trim();
    if (!line || /^-- \d+ of \d+ --$/.test(line)) {
      flushPara();
      closeAllLists();
      continue;
    }
    if (/^Links in video description$/i.test(line)) {
      continue;
    }
    if (/^SECURITY\+/i.test(line) && line.length < 40) {
      continue;
    }

    const bullet = rawLine.match(/^(\s*)(?:•|–|-|\u2022)\s+(.+)$/);
    if (bullet) {
      flushPara();
      const indent = bullet[1].replace(/\t/g, '  ').length;
      closeListsAbove(indent);
      if (!ulIndents.length || ulIndents[ulIndents.length - 1] < indent) {
        out.push('<ul>');
        ulIndents.push(indent);
      }
      out.push(`<li>${inlineFormat(bullet[2].trim())}</li>`);
      continue;
    }

    closeAllLists();
    para.push(line);
  }

  flushPara();
  closeAllLists();
  return out.join('\n');
}

/**
 * @param {string} text
 * @param {number} domain
 * @param {number} objectiveCount
 */
function splitObjectives(text, domain, objectiveCount) {
  /** @type {Map<string, string>} */
  const sections = new Map();
  const positions = [];

  for (let o = 1; o <= objectiveCount; o++) {
    const key = `${domain}.${o}`;
    const re = new RegExp(`(?:^|\\n)${domain}\\.${o}\\s+`, 'g');
    const match = re.exec(text);
    if (match) {
      positions.push({ key, index: match.index, start: match.index + match[0].length - `${domain}.${o} `.length });
    }
  }

  positions.sort((a, b) => a.index - b.index);

  for (let i = 0; i < positions.length; i++) {
    const start = positions[i].start;
    const end = i + 1 < positions.length ? positions[i + 1].index : text.length;
    let chunk = text.slice(start, end).trim();
    chunk = chunk.replace(new RegExp(`^${positions[i].key.replace('.', '\\.')}\\s+`), '');
    if (chunk.length > 80) {
      sections.set(positions[i].key, chunk);
    }
  }

  return sections;
}

/** @param {string} body */
function wrapSupplement(body) {
  return [
    '<div class="ut-lesson-supplement">',
    '<h4>Exam focus</h4>',
    body,
    '</div>',
  ].join('\n');
}

if (!fs.existsSync(ebooksRoot)) {
  console.error(`Ebooks root not found: ${ebooksRoot}`);
  process.exit(1);
}

fs.mkdirSync(outDir, { recursive: true });

let written = 0;
for (const spec of domainFiles) {
  const pdfPath = path.join(ebooksRoot, spec.file);
  if (!fs.existsSync(pdfPath)) {
    console.error(`missing=${spec.file}`);
    continue;
  }
  const buffer = fs.readFileSync(pdfPath);
  const parser = new PDFParse({ data: buffer });
  const parsed = await parser.getText();
  await parser.destroy();
  const sections = splitObjectives(parsed.text, spec.domain, spec.objectives);

  for (let o = 1; o <= spec.objectives; o++) {
    const key = `${spec.domain}.${o}`;
    const chunk = sections.get(key);
    if (!chunk) {
      console.error(`no_section key=${key} file=${spec.file}`);
      continue;
    }
    const shortname = `sy701_${key.replace('.', '_')}`;
    const html = sanitizeEbookHtml(wrapSupplement(textToHtml(sanitizeEbookText(chunk))));
    const outfile = path.join(outDir, `${shortname}.html`);
    fs.writeFileSync(outfile, html, 'utf8');
    written++;
    console.log(`wrote ${path.relative(repoRoot, outfile)} (${chunk.length} chars)`);
  }
}

console.log(`supplements_written=${written} source=${ebooksRoot}`);
