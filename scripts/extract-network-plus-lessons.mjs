#!/usr/bin/env node
/**
 * Merge Orange Study Guide body + diagrams + supplements + course notes into lesson HTML.
 *
 * Usage:
 *   node scripts/extract-network-plus-lessons.mjs [path-to-orange.txt-or.pdf]
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { loadObjectivesCsvFile } from './lib/network-plus-objectives.mjs';
import {
  contentDir,
  loadOrangeText,
  orangeLinesToHtml,
  splitOrangeSections,
  wrapLesson,
} from './lib/network-plus-orange-sections.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const lessonsDir = path.join(contentDir, 'lessons');
const diagramDir = path.join(contentDir, 'diagrams');
const supplementDir = path.join(contentDir, 'supplements');
const courseNotesDir = path.join(contentDir, 'course-notes');
const csvPath = path.join(contentDir, 'n10-009-objectives.csv');

/**
 * @param {string} html
 * @param {string} fragment
 */
function mergeBeforeNextSteps(html, fragment) {
  if (!fragment) {
    return html;
  }
  return html.replace('</div>\n<h4>Next steps</h4>', `${fragment}\n</div>\n<h4>Next steps</h4>`);
}

async function main() {
  const raw = await loadOrangeText(process.argv[2]);
  const sections = splitOrangeSections(raw);
  const objectives = loadObjectivesCsvFile(csvPath);

  fs.mkdirSync(lessonsDir, { recursive: true });

  let written = 0;
  for (const obj of objectives) {
    const lines = sections.get(obj.shortname);
    let body = lines ? orangeLinesToHtml(lines) : '';
    if (!body) {
      body = '<p><em>Study guide section pending — review the Orange Study Guide PDF for this objective.</em></p>';
    }

    let html = wrapLesson(obj.shortname, obj.fullname, body);

    const diagramPath = path.join(diagramDir, `${obj.shortname}.html`);
    if (fs.existsSync(diagramPath)) {
      html = mergeBeforeNextSteps(html, fs.readFileSync(diagramPath, 'utf8').trim());
    }
    const supplementPath = path.join(supplementDir, `${obj.shortname}.html`);
    if (fs.existsSync(supplementPath)) {
      html = mergeBeforeNextSteps(html, fs.readFileSync(supplementPath, 'utf8').trim());
    }
    const notesPath = path.join(courseNotesDir, `${obj.shortname}.html`);
    if (fs.existsSync(notesPath)) {
      html = mergeBeforeNextSteps(html, fs.readFileSync(notesPath, 'utf8').trim());
    }

    fs.writeFileSync(path.join(lessonsDir, `${obj.shortname}.html`), html);
    written += 1;
  }

  console.log(`lessons_written=${written}`);
  console.log(`sections_parsed=${sections.size}`);
  console.log(`lessons_dir=${lessonsDir}`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
