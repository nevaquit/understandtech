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

const brandCss = `
.ut-lesson-diagram { margin: 1.25rem 0; padding: 1rem; border: 1px solid #1A8A7D; border-radius: 8px; background: #f8fbfa; }
.ut-lesson-diagram .diagram-title { font-weight: 700; color: #0B1F3A; margin-bottom: 0.75rem; }
.ut-lesson-diagram .controls-matrix, .ut-lesson-diagram .concept-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.75rem; }
.ut-lesson-diagram .control-card, .ut-lesson-diagram .concept-item { background: #fff; border-left: 4px solid #C9A227; padding: 0.75rem; border-radius: 4px; }
.ut-lesson-diagram .flow-diagram { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: stretch; }
.ut-lesson-diagram .flow-step { flex: 1 1 140px; background: #0B1F3A; color: #fff; padding: 0.6rem; border-radius: 6px; text-align: center; font-size: 0.9rem; }
.ut-lesson-diagram .flow-arrow { align-self: center; color: #C9A227; font-weight: bold; }
.ut-lesson-diagram ul { margin: 0.35rem 0 0.35rem 1.1rem; }
.ut-lesson-diagram h4 { color: #0B1F3A; margin: 0.5rem 0; }
`.trim();

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
function wrapFragment(inner) {
  return `<div class="ut-lesson-diagram">\n<style>${brandCss}</style>\n${inner}\n</div>`;
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
  fs.writeFileSync(outfile, wrapFragment(parts.join('\n')), 'utf8');
  written++;
  console.log(`wrote ${path.relative(repoRoot, outfile)} blocks=${parts.length}`);
}

console.log(`diagrams_written=${written}`);
