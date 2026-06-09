import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const lessonsDir = path.join(__dirname, '../content/security-plus/lessons');

function extractDiagramBlock(html, start) {
  let depth = 0;
  let pos = start;
  while (pos < html.length) {
    const open = html.slice(pos).match(/^<div\b[^>]*>/i);
    if (open) {
      depth++;
      pos += open[0].length;
      continue;
    }
    const close = html.slice(pos).match(/^<\/div>/i);
    if (close) {
      depth--;
      pos += close[0].length;
      if (depth === 0) return { block: html.slice(start, pos), end: pos };
      continue;
    }
    pos++;
  }
  return null;
}

function stripTags(s) {
  return s.replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim();
}

const files = fs
  .readdirSync(lessonsDir)
  .filter((f) => f.startsWith('sy701_') && f.endsWith('.html'))
  .sort();

console.log(
  'Lesson'.padEnd(14) +
    'VR'.padStart(4) +
    'SVG'.padStart(4) +
    'Inf'.padStart(4) +
    '  Issues'
);
console.log('-'.repeat(72));

let totalIssues = 0;

for (const file of files) {
  const html = fs.readFileSync(path.join(lessonsDir, file), 'utf8');
  const issues = [];

  const vrMatches = [...html.matchAll(/<h4 class="ut-visual-representation">Visual Representation:\s*([^<]*)<\/h4>/gi)];
  const vr = vrMatches.length;
  const svg = (html.match(/ut-svg-figure/g) || []).length;
  const infog = (html.match(/ut-infographic/g) || []).length;

  if (html.includes('illustrates this concept')) issues.push('generic-intro');
  if (vr > 0 && svg < vr) issues.push(`svg<${vr}`);
  if (/<div class="ut-lesson-diagram[^"]*">\s*<\/div>/.test(html)) issues.push('empty-diagram');
  if ((html.match(/<h4>Visual Representation:/gi) || []).length > 0) issues.push('missing-vr-class');

  for (const m of vrMatches) {
    const title = m[1];
    if (/^[\uFE0F\s]/.test(title) || /^️/.test(title)) issues.push(`mojibake-vr:${title.slice(0, 20)}`);
    const after = html.slice(m.index + m[0].length, m.index + m[0].length + 800);
    if (!/<div class="ut-lesson-diagram/.test(after)) {
      issues.push('no-adjacent-diagram');
      continue;
    }
    const gap = after.match(/^(\s*(?:<p[^>]*>[\s\S]*?<\/p>\s*)?)(<div class="ut-lesson-diagram)/);
    if (!gap) {
      issues.push('orphaned-gap');
    } else {
      const between = gap[1];
      if (/<ul|<ol|<h[1-6]|<div class="ut-lesson-diagram/.test(between.replace(/<p[^>]*>[\s\S]*?<\/p>/gi, ''))) {
        issues.push('content-between-vr-diagram');
      }
    }
  }

  const diagramRe = /<div class="ut-lesson-diagram[^"]*">/g;
  let dm;
  while ((dm = diagramRe.exec(html)) !== null) {
    const extracted = extractDiagramBlock(html, dm.index);
    if (!extracted) continue;
    const body = stripTags(extracted.block);
    if (body.length < 20) issues.push('thin-diagram');
    if (!extracted.block.includes('ut-svg-figure') && !extracted.block.includes('concept-grid') &&
        !extracted.block.includes('flow-diagram') && !extracted.block.includes('controls-matrix') &&
        !extracted.block.includes('threat-actors') && !extracted.block.includes('cia-triad') &&
        !extracted.block.includes('malware-grid')) {
      issues.push('no-visual-content');
    }
    if (!extracted.block.includes('ut-infographic')) issues.push('missing-ut-infographic');
    const titleM = extracted.block.match(/<div class="diagram-title">([^<]*)<\/div>/i);
    if (titleM && /^[\uFE0F\s]/.test(titleM[1])) issues.push(`mojibake-title:${titleM[1].slice(0, 20)}`);
  }

  const status = issues.length === 0 ? 'ok' : issues.join('; ');
  if (issues.length) totalIssues += issues.length;
  console.log(
    file.padEnd(14) + String(vr).padStart(4) + String(svg).padStart(4) + String(infog).padStart(4) + '  ' + status
  );
}

console.log('-'.repeat(72));
console.log(`audit_complete files=${files.length} issue_count=${totalIssues}`);
