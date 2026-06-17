#!/usr/bin/env node
/**
 * Generate extra N10-009 GIFT questions (2 MCQs per objective) from Orange Study Guide bullets.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { loadObjectivesCsvFile } from './lib/network-plus-objectives.mjs';
import {
  contentDir,
  extractExamFocusBullets,
  loadOrangeText,
  splitOrangeSections,
} from './lib/network-plus-orange-sections.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const csvPath = path.join(contentDir, 'n10-009-objectives.csv');
const outGift = path.join(contentDir, 'n10-009-quiz-extra.gift');

/** @param {string} text */
function giftEscape(text) {
  return text
    .replace(/\\/g, '\\\\')
    .replace(/[#=:{}~]/g, (m) => '\\' + m)
    .replace(/\s+/g, ' ')
    .trim();
}

/**
 * @param {string} shortname
 * @param {string} title
 * @param {string[]} bullets
 * @param {number} idx
 */
function makeQuestion(shortname, title, bullets, idx) {
  if (bullets.length < 2) {
    return null;
  }
  const correct = bullets[idx % bullets.length];
  const pool = bullets.filter((b) => b !== correct);
  const distractors = [];
  for (let i = 0; i < 3; i++) {
    distractors.push(pool[i % pool.length] || 'Unrelated networking concept');
  }
  const tag = `${shortname}_sg${idx + 1}`;
  const display = shortname.replace(/^n10009_(\d)_(\d+)$/, '$1.$2');
  const stem = giftEscape(
    `For N10-009 objective ${display}, which statement best applies to: ${title}?`
  );
  const options = [correct, ...distractors].map((o, i) => {
    const prefix = o === correct ? '=' : '~';
    return `${prefix}${giftEscape(o)}`;
  });
  return [
    `::${tag} [${shortname}] ${giftEscape(title.slice(0, 60))} ::`,
    stem,
    '{',
    options.join('\n'),
    '}',
    '',
  ].join('\n');
}

async function main() {
  const raw = await loadOrangeText(process.argv[2]);
  const sections = splitOrangeSections(raw);
  const objectives = loadObjectivesCsvFile(csvPath);
  /** @type {string[]} */
  const generated = [];

  for (const obj of objectives) {
    const lines = sections.get(obj.shortname) || [];
    const bullets = extractExamFocusBullets(lines);
  if (bullets.length < 2) {
    continue;
  }
    for (let i = 0; i < 2; i++) {
      const q = makeQuestion(obj.shortname, obj.fullname, bullets, i);
      if (q) {
        generated.push(q);
      }
    }
  }

  fs.writeFileSync(outGift, generated.length ? `${generated.join('\n')}\n` : '');
  console.log(`extra_questions=${generated.length}`);
  console.log(`out=${outGift}`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
