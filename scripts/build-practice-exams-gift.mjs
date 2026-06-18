#!/usr/bin/env node
/**
 * Build practice-exam-{1,2,3}.gift from SY0-701 banks (includes launch pool).
 *
 * Usage: node scripts/build-practice-exams-gift.mjs [1|2|3|all]
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, '..');
const contentDir = path.join(repoRoot, 'content', 'security-plus');

const sources = [
  'sy0-701-quiz.gift',
  'sy0-701-quiz-extra.gift',
  'sy0-701-quiz-launch.gift',
];
const targets = { 1: 11, 2: 20, 3: 16, 4: 25, 5: 18 };
const targetTotal = Object.values(targets).reduce((a, b) => a + b, 0);

/** @param {string} content */
function parseGiftByDomain(content) {
  const domains = { 1: [], 2: [], 3: [], 4: [], 5: [] };
  const re = /^::sy701_(\d)_\d+[^:]*::.*?^(?=::|\Z)/gms;
  let m;
  while ((m = re.exec(content)) !== null) {
    const domain = parseInt(m[1], 10);
    if (domains[domain]) {
      domains[domain].push(m[0].trim());
    }
  }
  return domains;
}

/** Seeded shuffle */
function seededRandom(seed) {
  let s = seed;
  return () => {
    s = (s * 1103515245 + 12345) & 0x7fffffff;
    return s / 0x7fffffff;
  };
}

function shuffle(arr, rand) {
  const a = [...arr];
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(rand() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a;
}

function selectExamQuestions(domains, seed) {
  const rand = seededRandom(seed);
  const selected = [];
  const seen = new Set();
  const unused = [];

  for (const [domain, need] of Object.entries(targets)) {
    const pool = shuffle(domains[domain] || [], rand);
    const take = pool.slice(0, Math.min(need, pool.length));
    for (const block of take) {
      if (!seen.has(block)) {
        seen.add(block);
        selected.push(block);
      }
    }
    unused.push(...pool.slice(take.length));
  }

  for (const block of shuffle(unused, rand)) {
    if (selected.length >= targetTotal) break;
    if (!seen.has(block)) {
      seen.add(block);
      selected.push(block);
    }
  }

  const fallback = domains[4] || [];
  while (selected.length < targetTotal && fallback.length) {
    const block = fallback[Math.floor(rand() * fallback.length)];
    selected.push(block);
  }

  return selected.slice(0, targetTotal);
}

const arg = process.argv[2] || 'all';
const exams = arg === 'all' ? [1, 2, 3] : [parseInt(arg, 10)];

let content = '';
for (const file of sources) {
  const p = path.join(contentDir, file);
  if (fs.existsSync(p)) {
    content += `\n${fs.readFileSync(p, 'utf8')}`;
  }
}

const domains = parseGiftByDomain(content);

for (const examnum of exams) {
  const seed = 70100 + examnum * 997;
  const selected = selectExamQuestions(domains, seed);
  const prefix = `pe${examnum}_q`;
  let out = '';
  selected.forEach((block, idx) => {
    const num = String(idx + 1).padStart(3, '0');
    const head = block.match(/^::[^:]*::/m);
    if (head) {
      const title = head[0].replace(/^::|::$/g, '');
      const body = block.replace(/^::[^:]*::/m, '');
      out += `::${prefix}${num} ${title}::${body}\n\n`;
    } else {
      out += `::${prefix}${num}::\n${block}\n\n`;
    }
  });
  const outPath = path.join(contentDir, `practice-exam-${examnum}.gift`);
  fs.writeFileSync(outPath, out.trim() + '\n', 'utf8');
  console.log(`wrote ${path.relative(repoRoot, outPath)} exam=${examnum} questions=${selected.length}`);
}
