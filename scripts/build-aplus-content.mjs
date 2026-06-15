#!/usr/bin/env node
/**
 * Parse CompTIA A+ study guide markdown into objectives CSV and lesson HTML.
 *
 * Usage: node scripts/build-aplus-content.mjs
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, '..');
const srcPath = path.join(repoRoot, 'content/a-plus/CompTIA_A+_Study_Guide_Final.md');
const lessonsDir = path.join(repoRoot, 'content/a-plus/lessons');
const csvPath = path.join(repoRoot, 'content/a-plus/aplus-objectives.csv');

const CERT = 'comptia_a_plus';

const domainMapC1 = {
  '1.0': 'mobile_devices',
  '2.0': 'networking',
  '3.0': 'hardware',
  '4.0': 'virtualization',
  '5.0': 'hw_net_troubleshooting',
};
const domainMapC2 = {
  '1.0': 'operating_systems',
  '2.0': 'security',
  '3.0': 'software_troubleshooting',
  '4.0': 'operational_procedures',
};

function escapeHtml(text) {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function inlineMd(text) {
  return escapeHtml(text).replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
}

function mdBlockToHtml(block) {
  const lines = block.split('\n');
  const out = [];
  let i = 0;

  while (i < lines.length) {
    const line = lines[i];

    if (line.startsWith('|')) {
      const tableLines = [];
      while (i < lines.length && lines[i].startsWith('|')) {
        tableLines.push(lines[i]);
        i += 1;
      }
      if (tableLines.length >= 2) {
        const rows = tableLines.filter((r) => !/^\|[\s\-:|]+\|$/.test(r));
        out.push('<table class="ut-lesson-table"><tbody>');
        rows.forEach((row, idx) => {
          const cells = row.split('|').slice(1, -1).map((c) => c.trim());
          const tag = idx === 0 ? 'th' : 'td';
          out.push('<tr>' + cells.map((c) => `<${tag}>${inlineMd(c)}</${tag}>`).join('') + '</tr>');
        });
        out.push('</tbody></table>');
      }
      continue;
    }

    if (line.startsWith('> ')) {
      const quote = [];
      while (i < lines.length && lines[i].startsWith('> ')) {
        quote.push(lines[i].slice(2));
        i += 1;
      }
      out.push(`<blockquote><p>${inlineMd(quote.join(' '))}</p></blockquote>`);
      continue;
    }

    if (/^[-*] /.test(line)) {
      out.push('<ul>');
      while (i < lines.length && /^[-*] /.test(lines[i])) {
        out.push(`<li>${inlineMd(lines[i].replace(/^[-*] /, ''))}</li>`);
        i += 1;
      }
      out.push('</ul>');
      continue;
    }

    if (/^\d+\. /.test(line)) {
      out.push('<ol>');
      while (i < lines.length && /^\d+\. /.test(lines[i])) {
        out.push(`<li>${inlineMd(lines[i].replace(/^\d+\. /, ''))}</li>`);
        i += 1;
      }
      out.push('</ol>');
      continue;
    }

    if (line.startsWith('### ')) {
      out.push(`<h4>${inlineMd(line.slice(4))}</h4>`);
      i += 1;
      continue;
    }

    if (line.trim() === '') {
      i += 1;
      continue;
    }

    out.push(`<p>${inlineMd(line)}</p>`);
    i += 1;
  }

  return out.join('\n');
}

function objectiveShortname(core, domainKey, title) {
  const prefix = core === 1 ? 'ap1101' : 'ap1102';
  const domainNum = domainKey.split('.')[0];
  const numbered = title.match(/^(\d+(?:\.\d+)+)/);
  if (numbered) {
    const parts = numbered[1].split(/[./]/).slice(1);
    return `${prefix}_${domainNum}_${parts.join('_')}`;
  }
  const slug = title
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_|_$/g, '')
    .slice(0, 40);
  return `${prefix}_${domainNum}_${slug || 'overview'}`;
}

function displayCode(shortname) {
  const m = shortname.match(/^ap110[12]_(\d+)_(\d+(?:_\d+)*|overview|[a-z_]+)$/);
  if (!m) {
    return shortname;
  }
  const exam = shortname.startsWith('ap1101_') ? '220-1101' : '220-1102';
  const obj = `${m[1]}.${m[2].replace(/_/g, '.')}`;
  return `${exam} ${obj}`;
}

function wrapLesson(shortname, title, bodyHtml) {
  const code = displayCode(shortname);
  return `<div class="ut-lesson-content">
<h3>Exam objective ${escapeHtml(code)}</h3>
<p><strong>${escapeHtml(title)}</strong></p>
<div class="ut-lesson-body">
${bodyHtml}
</div>
<h4>Next steps</h4>
<p>Complete the domain knowledge check when available, rate your confidence honestly, and use the AI tutor for scenario-based practice.</p>
</div>
`;
}

function parseStudyGuide(markdown) {
  const lines = markdown.split(/\r?\n/);
  let core = 1;
  let domainKey = null;
  let domainShort = null;
  let current = null;
  const objectives = [];

  const flush = () => {
    if (!current) {
      return;
    }
    const body = mdBlockToHtml(current.body.join('\n').trim());
    objectives.push({
      ...current,
      html: wrapLesson(current.shortname, current.title, body),
    });
    current = null;
  };

  for (const line of lines) {
    if (/^## CORE 2/i.test(line)) {
      flush();
      core = 2;
      domainKey = null;
      domainShort = null;
      continue;
    }

    const domainMatch = line.match(/^## Domain (\d+\.\d+):/);
    if (domainMatch) {
      flush();
      domainKey = domainMatch[1];
      const map = core === 1 ? domainMapC1 : domainMapC2;
      domainShort = map[domainKey] ?? null;
      continue;
    }

    if (line.startsWith('### ') && domainShort) {
      flush();
      const title = line.slice(4).trim();
      const shortname = objectiveShortname(core, domainKey, title);
      current = {
        core,
        domainShort,
        title,
        shortname,
        body: [],
      };
      continue;
    }

    if (current) {
      current.body.push(line);
    }
  }

  flush();
  return objectives;
}

if (!fs.existsSync(srcPath)) {
  console.error(`Missing source: ${srcPath}`);
  process.exit(1);
}

fs.mkdirSync(lessonsDir, { recursive: true });
const objectives = parseStudyGuide(fs.readFileSync(srcPath, 'utf8'));

const csvRows = ['cert_shortname,domain_shortname,objective_shortname,objective_fullname'];
for (const obj of objectives) {
  const fullname = obj.title.replace(/"/g, '""');
  csvRows.push(`${CERT},${obj.domainShort},${obj.shortname},"${fullname}"`);
  fs.writeFileSync(path.join(lessonsDir, `${obj.shortname}.html`), obj.html, 'utf8');
}

fs.writeFileSync(csvPath, csvRows.join('\n') + '\n', 'utf8');
console.log(`objectives=${objectives.length}`);
console.log(`lessons_dir=${lessonsDir}`);
console.log(`csv=${csvPath}`);
