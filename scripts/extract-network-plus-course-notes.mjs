#!/usr/bin/env node
/**
 * Extract Network+ course notes (Detailed coverage) from Orange Study Guide sections.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { loadObjectivesCsvFile } from './lib/network-plus-objectives.mjs';
import {
  contentDir,
  escapeHtml,
  loadOrangeText,
  orangeLinesToHtml,
  splitOrangeSections,
} from './lib/network-plus-orange-sections.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const csvPath = path.join(contentDir, 'n10-009-objectives.csv');
const outDir = path.join(contentDir, 'course-notes');

async function main() {
  const raw = await loadOrangeText(process.argv[2]);
  const sections = splitOrangeSections(raw);
  const objectives = loadObjectivesCsvFile(csvPath);

  fs.mkdirSync(outDir, { recursive: true });
  let written = 0;

  for (const obj of objectives) {
    const lines = sections.get(obj.shortname);
    if (!lines?.length) {
      continue;
    }
    const body = orangeLinesToHtml(lines);
    if (!body) {
      continue;
    }
    const html = [
      '<div class="ut-lesson-depth">',
      '<h4>Detailed coverage</h4>',
      `<p><strong>${escapeHtml(obj.fullname)}</strong></p>`,
      body,
      '</div>',
      '',
    ].join('\n');
    fs.writeFileSync(path.join(outDir, `${obj.shortname}.html`), html);
    written += 1;
  }

  console.log(`course_notes_written=${written}`);
  console.log(`course_notes_dir=${outDir}`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
