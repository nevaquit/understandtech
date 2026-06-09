import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const lessonsDir = path.join(__dirname, '../content/security-plus/lessons');

function stripBrokenPrefix(text) {
  return text
    .replace(/^[\uFE0F\uFFFD\s]+/, '')
    .replace(/^(?:\uFFFD|\?){1,4}\s*/, '')
    .replace(/^[\uD800-\uDBFF][\uDC00-\uDFFF]\uFE0F?\s*/, (m) => {
      // Keep valid emoji at start of diagram-title; only strip lone variation selectors.
      return m.length <= 2 ? '' : m;
    });
}

for (const file of fs.readdirSync(lessonsDir).filter((f) => f.startsWith('sy701_') && f.endsWith('.html'))) {
  const fp = path.join(lessonsDir, file);
  let html = fs.readFileSync(fp, 'utf8');
  const original = html;

  html = html.replace(
    /(<h4 class="ut-visual-representation">Visual Representation:\s*)([^<]*)(<\/h4>)/gi,
    (_, pre, title, post) => `${pre}${stripBrokenPrefix(title.trim())}${post}`
  );

  html = html.replace(
    /(<div class="diagram-title">)([^<]*)(<\/div>)/gi,
    (_, pre, title, post) => {
      const cleaned = stripBrokenPrefix(title.trim());
      // Fix known broken emoji sequences to proper UTF-8 emoji where title matches.
      const emojiMap = {
        'Security Governance Framework': '🏛️ Security Governance Framework',
        'Zero Trust Architecture (NIST SP 800-207)': '🛡️ Zero Trust Architecture (NIST SP 800-207)',
      };
      const mapped = emojiMap[cleaned] || cleaned;
      return `${pre}${mapped}${post}`;
    }
  );

  html = html.replace(
    /(<p>The following diagram illustrates <strong>)([^<]*)(<\/strong> with a branded visual summary\.<\/p>)/gi,
    (_, pre, title, post) => `${pre}${stripBrokenPrefix(title.trim())}${post}`
  );

  if (html !== original) {
    fs.writeFileSync(fp, html);
    console.log(`fixed ${file}`);
  }
}

console.log('fix_vr_mojibake_complete=1');
