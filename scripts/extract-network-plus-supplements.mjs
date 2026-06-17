#!/usr/bin/env node
/**
 * Extract Network+ supplements (Exam focus) from Orange Study Guide sections.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { loadObjectivesCsvFile } from './lib/network-plus-objectives.mjs';
import {
  contentDir,
  escapeHtml,
  extractExamFocusBullets,
  loadOrangeText,
  splitOrangeSections,
  extractAppendixBHtml,
  ensurePoeSnippet,
} from './lib/network-plus-orange-sections.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, '..');
const outDir = path.join(contentDir, 'supplements');
const csvPath = path.join(contentDir, 'n10-009-objectives.csv');

async function main() {
  const raw = await loadOrangeText(process.argv[2]);
  const sections = splitOrangeSections(raw);
  const objectives = loadObjectivesCsvFile(csvPath);
  const appendixB = extractAppendixBHtml(raw);
  ensurePoeSnippet();

  fs.mkdirSync(outDir, { recursive: true });
  let written = 0;

  for (const obj of objectives) {
    const lines = sections.get(obj.shortname) || [];
    const bullets = extractExamFocusBullets(lines);
    if (bullets.length < 1) {
      continue;
    }
    const items = bullets.slice(0, 24).map((b) => `<li>${escapeHtml(b)}</li>`).join('\n');
    let extra = '';
    if (obj.shortname === 'n10009_1_4' && appendixB) {
      extra = `\n${appendixB.replace('ut-lesson-supplement', 'ut-lesson-supplement ut-appendix-b')}\n`;
    }
    if (obj.shortname === 'n10009_1_5') {
      const poePath = path.join(contentDir, 'snippets', 'poe_cat_standards.frag.html');
      if (fs.existsSync(poePath)) {
        extra += `\n${fs.readFileSync(poePath, 'utf8')}\n`;
      }
    }
    const html = [
      '<div class="ut-lesson-supplement">',
      '<h4>Exam focus</h4>',
      `<p>${escapeHtml(obj.fullname)}</p>`,
      `<ul>\n${items}\n</ul>`,
      extra,
      '</div>',
      '',
    ].join('\n');
    fs.writeFileSync(path.join(outDir, `${obj.shortname}.html`), html);
    written += 1;
  }

  console.log(`supplements_written=${written}`);
  console.log(`supplements_dir=${outDir}`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
