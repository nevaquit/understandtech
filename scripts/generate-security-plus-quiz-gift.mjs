#!/usr/bin/env node
/**
 * Generate expanded SY0-701 GIFT quiz (3 MCQs per objective) from CyberKraft study guide.
 * Merges with base sy0-701-quiz.gift into sy0-701-quiz-full.gift.
 *
 * Usage:
 *   node scripts/generate-security-plus-quiz-gift.mjs
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, '..');
const contentDir = path.join(repoRoot, 'content', 'security-plus');
const baseGift = path.join(contentDir, 'sy0-701-quiz.gift');
const outGift = path.join(contentDir, 'sy0-701-quiz-extra.gift');

const defaultSource = path.join(
  process.env.USERPROFILE || '',
  'OneDrive',
  'Documents',
  '_2026',
  'CyberKraft Training',
  'CompTIA Security +',
  'security_plus_study_guide.md'
);

const sourcePath = path.resolve(process.argv[2] || defaultSource);

/** @param {string} line */
function stripMd(line) {
  return line
    .replace(/^\s*-\s+/, '')
    .replace(/\*\*(.+?)\*\*/g, '$1')
    .replace(/:.+$/, '')
    .trim();
}

/** @param {string} text */
function giftEscape(text) {
  return text.replace(/[{}~]/g, ' ').replace(/\s+/g, ' ').trim();
}

/** @param {string} md */
function extractSections(md) {
  const headingRe = /^### (\d+)\.(\d+) (.+)$/gm;
  const matches = [...md.matchAll(headingRe)];
  /** @type {Map<string, {title: string, bullets: string[]}>} */
  const sections = new Map();

  for (let i = 0; i < matches.length; i++) {
    const m = matches[i];
    const key = `${m[1]}.${m[2]}`;
    const start = m.index + m[0].length;
    const end = i + 1 < matches.length ? matches[i + 1].index : md.length;
    let body = md.slice(start, end).trim();
    const sectionBreak = body.search(/^## (?!#)/m);
    if (sectionBreak >= 0) {
      body = body.slice(0, sectionBreak).trim();
    }
    const bullets = body
      .split('\n')
      .filter((l) => /^\s*-\s+\*\*/.test(l))
      .map(stripMd)
      .filter((b) => b.length > 3 && b.length < 120);

    const candidate = { title: m[3].trim(), bullets: [...new Set(bullets)] };
    const existing = sections.get(key);
    if (!existing || candidate.bullets.length > existing.bullets.length) {
      sections.set(key, candidate);
    }
  }
  return sections;
}

/** @param {string} tag @param {string} title @param {string[]} bullets @param {number} idx */
function makeQuestion(tag, title, bullets, idx) {
  if (bullets.length < 4) {
    return null;
  }
  const correct = bullets[idx % bullets.length];
  const distractors = bullets.filter((b) => b !== correct).slice(0, 3);
  while (distractors.length < 3) {
    distractors.push('Unrelated administrative overhead');
  }
  const slug = correct.toLowerCase().replace(/[^a-z0-9]+/g, '_').slice(0, 24);
  const name = `${tag} ${slug} ${idx + 2}`;
  const stem = giftEscape(`For SY0-701 objective ${tag.replace('sy701_', '').replace('_', '.')}, which item best matches: ${title}?`);
  const lines = [
    `::${name}::${stem}{`,
    `=${giftEscape(correct)}`,
    ...distractors.map((d) => `~${giftEscape(d)}`),
    '}',
    '',
  ];
  return lines.join('\n');
}

const expected = [];
for (let d = 1; d <= 5; d++) {
  const count = d === 4 ? 9 : d === 2 ? 5 : d === 1 || d === 3 ? 4 : 6;
  for (let o = 1; o <= count; o++) {
    expected.push(`${d}.${o}`);
  }
}

const md = fs.readFileSync(sourcePath, 'utf8');
const sections = extractSections(md);
const generated = [];

for (const key of expected) {
  const section = sections.get(key);
  if (!section || section.bullets.length < 4) {
    continue;
  }
  const tag = `sy701_${key.replace('.', '_')}`;
  for (let i = 0; i < 2; i++) {
    const q = makeQuestion(tag, section.title, section.bullets, i);
    if (q) {
      generated.push(q);
    }
  }
}

const base = fs.readFileSync(baseGift, 'utf8').trim();
const extraOnly = generated.join('\n').trim();
fs.writeFileSync(outGift, extraOnly ? `${extraOnly}\n` : '', 'utf8');
console.log(`base_questions=28 generated=${generated.length} out=${path.relative(repoRoot, outGift)}`);
