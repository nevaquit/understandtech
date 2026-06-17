#!/usr/bin/env node
/**
 * Build Network+ course notes (Detailed coverage) from unique enrichment prose.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { loadObjectivesCsvFile } from './lib/network-plus-objectives.mjs';
import { contentDir, ensurePoeSnippet } from './lib/network-plus-orange-sections.mjs';
import { buildDepthHtml } from './lib/network-plus-enrichment.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const csvPath = path.join(contentDir, 'n10-009-objectives.csv');
const outDir = path.join(contentDir, 'course-notes');

function main() {
  ensurePoeSnippet();
  const objectives = loadObjectivesCsvFile(csvPath);

  fs.mkdirSync(outDir, { recursive: true });
  let written = 0;

  for (const obj of objectives) {
    let html = buildDepthHtml(obj.shortname, obj.fullname);
    if (!html) {
      continue;
    }
    if (obj.shortname === 'n10009_1_5') {
      const poePath = path.join(contentDir, 'snippets', 'poe_cat_standards.frag.html');
      if (fs.existsSync(poePath)) {
        const poe = fs.readFileSync(poePath, 'utf8')
          .replace('ut-lesson-supplement', 'ut-lesson-depth ut-poe-reference');
        html = html.replace('</div>\n', `\n${poe}\n</div>\n`);
      }
    }
    fs.writeFileSync(path.join(outDir, `${obj.shortname}.html`), html);
    written += 1;
  }

  console.log(`course_notes_written=${written}`);
  console.log(`course_notes_dir=${outDir}`);
}

main();
