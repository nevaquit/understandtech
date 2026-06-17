#!/usr/bin/env node
/**
 * Verify Network+ lessons have no duplicated body/depth/supplement overlap.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { loadObjectivesCsvFile } from './lib/network-plus-objectives.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, '..');
const lessonsDir = path.join(repoRoot, 'content', 'network-plus', 'lessons');
const csvPath = path.join(repoRoot, 'content', 'network-plus', 'n10-009-objectives.csv');

/**
 * @param {string} html
 * @param {string} className
 */
function extractBlock(html, className) {
  const re = new RegExp(`<div class="${className}[^"]*">([\\s\\S]*?)</div>`, 'i');
  const m = html.match(re);
  return m ? m[1].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim() : '';
}

/**
 * @param {string} a
 * @param {string} b
 */
function overlapRatio(a, b) {
  if (!a || !b) {
    return 0;
  }
  const wordsA = a.split(' ').filter((w) => w.length > 4);
  const wordsB = new Set(b.split(' ').filter((w) => w.length > 4));
  if (!wordsA.length) {
    return 0;
  }
  const shared = wordsA.filter((w) => wordsB.has(w)).length;
  return shared / wordsA.length;
}

const objectives = loadObjectivesCsvFile(csvPath);
let issues = 0;

for (const obj of objectives) {
  const html = fs.readFileSync(path.join(lessonsDir, `${obj.shortname}.html`), 'utf8');
  const body = extractBlock(html, 'ut-lesson-body');
  const supplement = extractBlock(html, 'ut-lesson-supplement');
  const depth = extractBlock(html, 'ut-lesson-depth');
  const bodyCore = body.replace(supplement, '').replace(depth, '');

  const supOverlap = overlapRatio(supplement, bodyCore);
  const depthOverlap = overlapRatio(depth, bodyCore);

  if (supOverlap > 0.45) {
    console.log(`WARN ${obj.shortname} supplement overlaps body ${(supOverlap * 100).toFixed(0)}%`);
    issues += 1;
  }
  if (depthOverlap > 0.45) {
    console.log(`WARN ${obj.shortname} depth overlaps body ${(depthOverlap * 100).toFixed(0)}%`);
    issues += 1;
  }
  if (html.includes('highlight-box') && html.indexOf('highlight-box') < html.indexOf('ut-lesson-supplement')) {
    console.log(`WARN ${obj.shortname} body still contains exam callout boxes`);
    issues += 1;
  }
}

console.log(issues ? `dedup_issues=${issues}` : 'dedup_ok=25');
