#!/usr/bin/env node
/**
 * Extract visual diagram HTML fragments from CyberKraft Orange study guide HTML.
 *
 * Usage:
 *   node scripts/extract-security-plus-diagrams.mjs [path-to-html]
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, '..');
const outDir = path.join(repoRoot, 'content', 'security-plus', 'diagrams');
const mapPath = path.join(repoRoot, 'content', 'security-plus', 'diagram-section-map.json');

const defaultSource = path.join(
  process.env.USERPROFILE || '',
  'OneDrive',
  'Documents',
  '_2026',
  'CyberKraft Training',
  'CompTIA Security +',
  'security_plus_corrected Final.html'
);

const sourcePath = path.resolve(process.argv[2] || defaultSource);
const sectionMap = JSON.parse(fs.readFileSync(mapPath, 'utf8'));

/** @param {string} html */
function extractSectionHtml(html, sectionKey) {
  const escaped = sectionKey.replace('.', '\\.');
  const re = new RegExp(
    `<h3>${escaped}[^<]*</h3>([\\s\\S]*?)(?=<h3>\\d+\\.\\d+|<h2[^>]*>|</body>|$)`,
    'i'
  );
  const match = html.match(re);
  return match ? match[1] : '';
}

/** @param {string} sectionHtml */
function pullDiagramBlocks(sectionHtml) {
  const blocks = [];
  const patterns = [
    /<div class="visual-diagram">([\s\S]*?)<\/div>\s*(?=<div class="key-concepts">|<h3>|<h2>|$)/gi,
    /<div class="key-concepts">([\s\S]*?)<\/div>\s*(?=<div class="visual-diagram">|<h3>|<h2>|$)/gi,
  ];
  for (const pattern of patterns) {
    for (const m of sectionHtml.matchAll(pattern)) {
      blocks.push(m[1].trim());
    }
  }
  return blocks;
}

/** @param {string} inner */
function wrapDiagramBlock(inner) {
  const cleaned = inner
    .replace(/<style[\s\S]*?<\/style>/gi, '')
    .replace(/\n{3,}/g, '\n')
    .trim();
  if (!cleaned) {
    return '';
  }
  return `<div class="ut-lesson-diagram">\n${cleaned}\n</div>`;
}

if (!fs.existsSync(sourcePath)) {
  console.error(`Source not found: ${sourcePath}`);
  process.exit(1);
}

const html = fs.readFileSync(sourcePath, 'utf8');
fs.mkdirSync(outDir, { recursive: true });

let written = 0;
for (const [objective, sections] of Object.entries(sectionMap)) {
  const parts = [];
  for (const sectionKey of sections) {
    const sectionHtml = extractSectionHtml(html, sectionKey);
    const blocks = pullDiagramBlocks(sectionHtml);
    parts.push(...blocks);
  }
  if (!parts.length) {
    continue;
  }
  const outfile = path.join(outDir, `${objective}.html`);
  const wrapped = parts.map((part) => wrapDiagramBlock(part)).filter(Boolean).join('\n');
  fs.writeFileSync(outfile, wrapped, 'utf8');
  written++;
  console.log(`wrote ${path.relative(repoRoot, outfile)} blocks=${parts.length}`);
}

console.log(`diagrams_written=${written}`);
