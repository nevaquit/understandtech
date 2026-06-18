#!/usr/bin/env node
/**
 * Build practice-exam-{1,2,3}.gift from A+ objective bank (aplus-quiz.gift).
 * Combined Core 1 + Core 2 blueprint weights on 90 questions per exam.
 *
 * Usage: node scripts/build-aplus-practice-exams-gift.mjs [1|2|3|all]
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, '..');
const contentDir = path.join(repoRoot, 'content', 'a-plus');

const sources = ['aplus-quiz.gift'];

/** Domain => PE slot count (90 total, normalized across 200% blueprint). */
const targets = {
  1: 6,
  2: 10,
  3: 11,
  4: 5,
  5: 13,
  6: 14,
  7: 11,
  8: 10,
  9: 10,
};
const targetTotal = Object.values(targets).reduce((a, b) => a + b, 0);

/** @param {string} content */
function parseGiftByDomain(content) {
  /** @type {Record<number, string[]>} */
  const domains = { 1: [], 2: [], 3: [], 4: [], 5: [], 6: [], 7: [], 8: [], 9: [] };
  const re = /^::ap110[12]_(\d)_[^:]*::.*?^(?=::|\Z)/gms;
  let m;
  while ((m = re.exec(content)) !== null) {
    let domain = parseInt(m[1], 10);
    const block = m[0].trim();
    if (block.startsWith('::ap1102_')) {
      domain += 5;
    }
    if (domains[domain]) {
      domains[domain].push(block);
    }
  }
  return domains;
}

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
    if (selected.length >= targetTotal) {
      break;
    }
    if (!seen.has(block)) {
      seen.add(block);
      selected.push(block);
    }
  }

  const fallback = domains[5] || [];
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
  const seed = 1101 + examnum * 1102;
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
  fs.writeFileSync(outPath, out, 'utf8');
  console.log(`practice_exam_${examnum}_questions=${selected.length} path=${path.relative(repoRoot, outPath)}`);
}
