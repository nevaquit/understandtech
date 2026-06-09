import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import crypto from 'crypto';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const lessonsDir = path.join(__dirname, '../content/security-plus/lessons');
const snippetDir = path.join(__dirname, '../content/security-plus/snippets/svg');

function stripTitlePrefix(title) {
  return title.trim().replace(/^[\p{So}\p{Sk}\p{Emoji}\s]+/gu, '');
}

function loadSvgFrag(name) {
  const p = path.join(snippetDir, name);
  if (!fs.existsSync(p)) return '';
  let frag = fs.readFileSync(p, 'utf8');
  if (frag.includes('{{GRAD_ID}}')) {
    frag = frag.replaceAll('{{GRAD_ID}}', 'utGrad' + crypto.randomBytes(4).toString('hex'));
  }
  return frag;
}

function pickBanner(body) {
  if (body.includes('flow-diagram')) return loadSvgFrag('flow-banner.frag.html');
  if (body.includes('controls-matrix') || body.includes('threat-actors')) {
    return loadSvgFrag('matrix-banner.frag.html');
  }
  if (body.includes('malware-grid') || body.includes('concept-grid') || body.includes('cia-triad')) {
    return loadSvgFrag('grid-banner.frag.html');
  }
  return loadSvgFrag('cycle-banner.frag.html');
}

function addFlowArrows(html) {
  return html.replace(
    /(<div class="flow-diagram">\s*)(.*?)(\s*<\/div>)/gs,
    (m, open, inner, close) => {
      if (inner.includes('flow-arrow')) return m;
      const fixed = inner.replace(
        /(<\/div>)\s*(<div class="flow-step">)/g,
        '$1<div class="flow-arrow" aria-hidden="true">→</div>$2'
      );
      return open + fixed + close;
    }
  );
}

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

for (const file of fs.readdirSync(lessonsDir).filter((f) => f.startsWith('sy701_') && f.endsWith('.html'))) {
  const fp = path.join(lessonsDir, file);
  let html = fs.readFileSync(fp, 'utf8');
  const original = html;

  html = html.replace(
    /<h4>Visual Representation:\s*([^<]*)<\/h4>/gu,
    (_, raw) => {
      const title = stripTitlePrefix(raw);
      return `<h4 class="ut-visual-representation">Visual Representation: ${title}</h4>`;
    }
  );

  html = html.replace(
    /(<h4 class="ut-visual-representation">Visual Representation:\s*([^<]*)<\/h4>\s*)<p>The following diagram illustrates this concept\.<\/p>/gu,
    (_, pre, raw) => {
      const topic = stripTitlePrefix(raw);
      return `${pre}<p>The following diagram illustrates <strong>${topic}</strong> with a branded visual summary.</p>`;
    }
  );

  html = html.replace(
    /<div class="ut-lesson-diagram(\s+ut-infographic)?">\s*\n\s*\n\s*/g,
    '<div class="ut-lesson-diagram$1">\n'
  );

  html = html.replace(/<div class="ut-lesson-diagram">/g, '<div class="ut-lesson-diagram ut-infographic">');

  let offset = 0;
  while (true) {
    const idx = html.indexOf('<div class="ut-lesson-diagram ut-infographic">', offset);
    if (idx === -1) break;
    const extracted = extractDiagramBlock(html, idx);
    if (!extracted) break;
    let { block, end } = extracted;
    if (!block.includes('ut-svg-figure')) {
      const banner = pickBanner(block);
      if (banner) {
        const titleMatch = block.match(/<div class="diagram-title">[^<]*<\/div>/i);
        if (titleMatch) {
          block = block.replace(titleMatch[0], `${titleMatch[0]}\n${banner}\n`);
        } else {
          block = block.replace(
            '<div class="ut-lesson-diagram ut-infographic">',
            `<div class="ut-lesson-diagram ut-infographic">\n${banner}\n`
          );
        }
        html = html.slice(0, idx) + block + html.slice(end);
        end = idx + block.length;
      }
    }
    offset = end;
  }

  html = addFlowArrows(html);

  if (html !== original) {
    fs.writeFileSync(fp, html);
    console.log(`upgraded ${file}`);
  } else {
    console.log(`unchanged ${file}`);
  }
}

console.log('upgrade_all_lesson_visuals_complete=1');
