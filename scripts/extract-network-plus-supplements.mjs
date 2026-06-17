#!/usr/bin/env node
/**
 * Build Network+ supplements from unique enrichment checklists (not Orange callout duplication).
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { loadObjectivesCsvFile } from './lib/network-plus-objectives.mjs';
import {
  contentDir,
  extractAppendixBHtml,
  loadOrangeText,
} from './lib/network-plus-orange-sections.mjs';
import { buildSupplementHtml } from './lib/network-plus-enrichment.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const outDir = path.join(contentDir, 'supplements');
const csvPath = path.join(contentDir, 'n10-009-objectives.csv');

async function main() {
  const raw = await loadOrangeText(process.argv[2]);
  const objectives = loadObjectivesCsvFile(csvPath);
  const appendixB = extractAppendixBHtml(raw);

  fs.mkdirSync(outDir, { recursive: true });
  let written = 0;

  for (const obj of objectives) {
    let html = buildSupplementHtml(obj.shortname, obj.fullname);
    if (!html) {
      continue;
    }
    if (obj.shortname === 'n10009_1_4' && appendixB) {
      html = html.replace(
        '</div>\n',
        `\n${appendixB.replace('ut-lesson-supplement', 'ut-lesson-supplement ut-appendix-b')}\n</div>\n`
      );
    }
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
