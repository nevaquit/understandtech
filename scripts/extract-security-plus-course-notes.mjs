#!/usr/bin/env node
/**
 * Extract SY0-701 objective course-note HTML from official lesson PDFs + mapping PDF.
 * Output is unbranded (no vendor/product names in learner-facing HTML).
 *
 * Usage:
 *   node scripts/extract-security-plus-course-notes.mjs [ebooks-root]
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { PDFParse } from 'pdf-parse';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, '..');
const outDir = path.join(repoRoot, 'content', 'security-plus', 'course-notes');

const defaultRoot = path.join(
  process.env.USERPROFILE || '',
  'OneDrive',
  'Ebooks',
  'Comptia',
  'Security +'
);

const ebooksRoot = path.resolve(process.argv[2] || defaultRoot);
const mappingPdf = path.join(ebooksRoot, 'Mapping Course Content to CompTIA Security+ (Exam SY0-701).pdf');
const lessonDir = path.join(ebooksRoot, 'Study Notes for Security+', 'Study Notes');

/** @param {string} text */
function inlineFormat(text) {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
}

/** @param {string} raw */
function cleanLessonText(raw) {
  return raw
    .replace(/\r\n/g, '\n')
    .replace(/Copyright ©[^\n]+\n/g, '')
    .replace(/LICENSED FOR USE ONLY BY:[^\n]+\n/g, '')
    .replace(/-- \d+ of \d+ --/g, '')
    .replace(/CompTIA Security\+ Exam SY0-701/gi, '')
    .replace(/CompTIA\.org/gi, '')
    .replace(/^Lesson \d+\s*$/gm, '')
    .replace(/^Objectives\s*$/gm, '')
    .replace(/^\d{1,2}\s*$/gm, '')
    .replace(/^Lab Activity[\s\S]*?(?=Topic \d+[A-D]|Copyright|$)/gm, '')
    .replace(/^Review Activity[\s\S]*?(?=Topic \d+[A-D]|Copyright|$)/gm, '')
    .replace(/\n{3,}/g, '\n\n')
    .trim();
}

/** @param {string} raw */
function textToHtml(raw) {
  const text = cleanLessonText(raw);
  const lines = text.split('\n');
  const out = [];
  let para = [];
  const ulIndents = [];

  const flushPara = () => {
    if (!para.length) {
      return;
    }
    const joined = para.join(' ').replace(/\s+/g, ' ').trim();
    if (joined && !/^\d{1,2}$/.test(joined)) {
      out.push(`<p>${inlineFormat(joined)}</p>`);
    }
    para = [];
  };

  const closeAllLists = () => {
    while (ulIndents.length) {
      out.push('</ul>');
      ulIndents.pop();
    }
  };

  const closeListsAbove = (indent) => {
    while (ulIndents.length && ulIndents[ulIndents.length - 1] > indent) {
      out.push('</ul>');
      ulIndents.pop();
    }
  };

  for (const rawLine of lines) {
    const trimmed = rawLine.trim();
    if (!trimmed) {
      flushPara();
      closeAllLists();
      continue;
    }

    const h5 = trimmed.match(/^Topic \d+([A-D])\s*(.*)$/i);
    if (h5 && h5[2]) {
      flushPara();
      closeAllLists();
      out.push(`<h5>${inlineFormat(h5[2].trim())}</h5>`);
      continue;
    }

    const bullet = rawLine.match(/^(\s*)(?:•|\u2022|-|\t•)\s*(.+)$/);
    if (bullet) {
      flushPara();
      const indent = bullet[1].replace(/\t/g, '  ').length;
      closeListsAbove(indent);
      if (!ulIndents.length || ulIndents[ulIndents.length - 1] < indent) {
        out.push('<ul>');
        ulIndents.push(indent);
      }
      out.push(`<li>${inlineFormat(bullet[2].trim())}</li>`);
      continue;
    }

    closeAllLists();
    para.push(trimmed);
  }

  flushPara();
  closeAllLists();
  return out.join('\n');
}

/** @param {string} text */
function parseObjectiveMapping(text) {
  /** @type {Record<string, string[]>} */
  const map = {};
  const chunks = text.split(/(?=\d\.\d+\s+)/);
  for (const chunk of chunks) {
    const head = chunk.match(/^(\d)\.(\d+)\s+/);
    if (!head) {
      continue;
    }
    const key = `sy701_${head[1]}_${head[2]}`;
    const refs = [...chunk.matchAll(/Lesson\s+(\d+),\s+Topic\s+([A-D])/gi)].map(
      (m) => `${m[1]}:${m[2].toUpperCase()}`
    );
    map[key] = [...new Set(refs)];
  }
  return map;
}

/** @param {string} text @param {number} lessonNum */
function splitLessonTopics(text, lessonNum) {
  /** @type {Record<string, string>} */
  const topics = {};
  const parts = text.split(new RegExp(`(?=Topic\\s+${lessonNum}[A-D])`, 'i'));
  for (const part of parts) {
    const m = part.match(new RegExp(`^Topic\\s+${lessonNum}([A-D])`, 'i'));
    if (!m) {
      continue;
    }
    const key = `${lessonNum}:${m[1].toUpperCase()}`;
    const body = part.replace(new RegExp(`^Topic\\s+${lessonNum}[A-D]\\s*`, 'i'), '').trim();
    if (body.length > 60) {
      topics[key] = body;
    }
  }
  return topics;
}

/** @param {string} body */
function wrapCourseNotes(body) {
  return [
    '<div class="ut-lesson-depth">',
    '<h4>Detailed coverage</h4>',
    body,
    '</div>',
  ].join('\n');
}

/** @param {string} pdfPath */
async function readPdfText(pdfPath) {
  const parser = new PDFParse({ data: fs.readFileSync(pdfPath) });
  const parsed = await parser.getText();
  await parser.destroy();
  return parsed.text;
}

if (!fs.existsSync(mappingPdf)) {
  console.error(`Mapping PDF not found: ${mappingPdf}`);
  process.exit(1);
}
if (!fs.existsSync(lessonDir)) {
  console.error(`Lesson directory not found: ${lessonDir}`);
  process.exit(1);
}

const mappingText = await readPdfText(mappingPdf);
const objectiveMap = parseObjectiveMapping(mappingText);

/** @type {Record<string, string>} */
const topicCorpus = {};
for (let lesson = 1; lesson <= 16; lesson++) {
  const file = path.join(lessonDir, `sy0-701_Lesson ${String(lesson).padStart(2, '0')}.pdf`);
  if (!fs.existsSync(file)) {
    console.error(`missing=${file}`);
    continue;
  }
  const text = await readPdfText(file);
  Object.assign(topicCorpus, splitLessonTopics(text, lesson));
}

fs.mkdirSync(outDir, { recursive: true });

const expected = [];
for (let d = 1; d <= 5; d++) {
  const count = d === 4 ? 9 : d === 2 ? 5 : d === 1 || d === 3 ? 4 : 6;
  for (let o = 1; o <= count; o++) {
    expected.push(`sy701_${d}_${o}`);
  }
}

let written = 0;
for (const key of expected) {
  const refs = objectiveMap[key] || [];
  const chunks = [];
  for (const ref of refs) {
    const topicText = topicCorpus[ref];
    if (topicText) {
      chunks.push(topicText);
    }
  }
  if (!chunks.length) {
    console.error(`no_course_notes key=${key}`);
    continue;
  }
  const unique = [...new Set(chunks)];
  const html = wrapCourseNotes(textToHtml(unique.join('\n\n')));
  fs.writeFileSync(path.join(outDir, `${key}.html`), html, 'utf8');
  written++;
  console.log(`wrote ${key}.html refs=${refs.length} chunks=${unique.length}`);
}

console.log(`course_notes_written=${written}`);
