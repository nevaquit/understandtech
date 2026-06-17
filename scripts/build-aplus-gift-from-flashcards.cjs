'use strict';

/**
 * Build content/a-plus/aplus-quiz.gift from CyberKraft CertMaster flashcard PDF export.
 *
 * Usage:
 *   node scripts/build-aplus-gift-from-flashcards.cjs [path-to-extracted.txt]
 *
 * If no txt path is given, extracts from the PDF path in content/a-plus/sources.json.
 */

const fs = require('fs');
const path = require('path');

const REPO = path.resolve(__dirname, '..');
const CSV_PATH = path.join(REPO, 'content/a-plus/aplus-objectives.csv');
const OUT_PATH = path.join(REPO, 'content/a-plus/aplus-quiz.gift');
const MANIFEST_PATH = path.join(REPO, 'content/a-plus/aplus-quiz.manifest.json');

const DOMAIN_KEYWORDS = {
  mobile_devices: [
    'mobile', 'laptop', 'tablet', 'smartphone', 'battery', 'bluetooth', 'nfc', 'gps', 'sim',
    'cellular', 'portable', 'iphone', 'ipad', 'android', 'touchscreen', 'biometric', 'dock',
  ],
  networking: [
    'network', 'tcp', 'udp', 'port', 'ethernet', 'wifi', 'wlan', 'router', 'switch', 'dns',
    'dhcp', ' ip', 'vpn', 'firewall', 'nat', 'vlan', 'soho', 'fiber', 'twisted', 'rj-45',
    'wireless', 'ssid', '802.11', 'ping', 'traceroute', 'nslookup', 'netstat',
  ],
  hardware: [
    'motherboard', 'cpu', 'ram', 'memory', 'disk', 'drive', 'sata', 'nvme', 'display', 'hdmi',
    'vga', 'dvi', 'printer', 'psu', 'power supply', 'pcie', 'pci', 'usb', 'cooling', 'heat',
    'raid', 'ssd', 'hdd', 'connector', 'cable', 'slot', 'socket', 'fan', 'thermal', 'gpu',
    'video card', 'sound card', 'nic', 'molex', 'atx', 'form factor', 'optical', 'projector',
  ],
  virtualization: [
    'virtual', 'hypervisor', 'cloud', 'saas', 'iaas', 'paas', 'azure', 'aws', 'vm', 'container',
    'sync', 'elastic', 'shared', 'hybrid',
  ],
  hw_net_troubleshooting: [
    'troubleshoot', 'problem', 'fault', 'diagnose', 'methodology', 'symptom', 'root cause',
  ],
  operating_systems: [
    'windows', 'linux', 'macos', 'command', 'registry', 'filesystem', ' os', 'powershell',
    'cmd', 'bash', 'terminal', 'bitlocker', 'uac', 'defender', 'update', 'driver', 'services.msc',
  ],
  security: [
    'security', 'malware', 'virus', 'encryption', 'authentication', 'firewall', 'permission',
    'password', 'bios', 'phishing', 'social engineering', 'hash', 'certificate', 'wpa', 'wep',
    '802.1x', 'mfa', 'smart card', 'disposal', 'shred', 'degauss',
  ],
  software_troubleshooting: [
    'troubleshoot', 'slow', 'boot', 'blue screen', 'application', 'restore', 'safe mode',
    'event viewer', 'msconfig', 'sfc', 'chkdsk',
  ],
  operational_procedures: [
    'documentation', 'policy', 'safety', 'backup', 'disposal', 'professional', 'script',
    'remote', 'change management', 'environmental', 'esd', 'msds', 'license', 'privacy',
    'communication', 'ticket', 'sla', 'chain of custody',
  ],
};

/**
 * @param {string} text
 * @returns {string}
 */
function escapeGift(text) {
  return text
    .replace(/\\/g, '\\\\')
    .replace(/[#=:{}~]/g, (m) => '\\' + m)
    .replace(/\s+/g, ' ')
    .trim();
}

/**
 * @param {string} text
 * @param {number} max
 * @returns {string}
 */
function clip(text, max = 180) {
  const clean = text.replace(/\s+/g, ' ').trim();
  if (clean.length <= max) {
    return clean;
  }
  return clean.slice(0, max - 1).trim() + '…';
}

/**
 * @param {string} line
 * @returns {{num: number, term: string, definition: string}|null}
 */
function parseCardLine(line) {
  const tabIdx = line.indexOf('\t');
  if (tabIdx === -1) {
    return null;
  }
  const left = line.slice(0, tabIdx).trim();
  let right = line.slice(tabIdx + 1).trim();
  const m = left.match(/^(\d+)\s+(.+)$/);
  if (!m) {
    return null;
  }
  const num = parseInt(m[1], 10);
  let term = m[2].trim();
  // PDF export often repeats the term at the start of the definition side.
  if (right.toLowerCase().startsWith(term.toLowerCase())) {
    right = right.slice(term.length).trim();
  }
  if (!term || !right) {
    return null;
  }
  return { num, term, definition: right };
}

/**
 * @param {string} raw
 * @returns {{num: number, term: string, definition: string}[]}
 */
function parseFlashcards(raw) {
  const cards = [];
  let current = null;

  for (const line of raw.split(/\r?\n/)) {
    const trimmed = line.trim();
    if (!trimmed) {
      continue;
    }
    if (/^--\s+\d+\s+of\s+\d+\s+--$/.test(trimmed)) {
      continue;
    }
    if (/^Side 1/i.test(trimmed) || /^Set Type/i.test(trimmed) || /^Default\s+\d+/i.test(trimmed)) {
      continue;
    }
    if (/^CertMaster/i.test(trimmed) || /^220-1101/i.test(trimmed)) {
      continue;
    }
    if (/^\(Image ©/i.test(trimmed)) {
      continue;
    }

    const parsed = parseCardLine(trimmed);
    if (parsed) {
      if (current) {
        cards.push(current);
      }
      current = parsed;
      continue;
    }

    if (current) {
      current.definition += ' ' + trimmed;
    }
  }
  if (current) {
    cards.push(current);
  }

  return cards.map((c) => ({
    ...c,
    term: c.term.replace(/\s+/g, ' ').trim(),
    definition: c.definition.replace(/\s+/g, ' ').trim(),
  }));
}

/**
 * @param {string} csv
 * @returns {{shortname: string, fullname: string, domain: string}[]}
 */
function loadObjectives(csv) {
  const lines = csv.split(/\r?\n/).slice(1);
  const rows = [];
  for (const line of lines) {
    if (!line.trim()) {
      continue;
    }
    const parts = [];
    let cur = '';
    let inQuotes = false;
    for (let i = 0; i < line.length; i++) {
      const ch = line[i];
      if (ch === '"') {
        inQuotes = !inQuotes;
        continue;
      }
      if (ch === ',' && !inQuotes) {
        parts.push(cur);
        cur = '';
        continue;
      }
      cur += ch;
    }
    parts.push(cur);
    if (parts.length < 4) {
      continue;
    }
    rows.push({
      domain: parts[1],
      shortname: parts[2],
      fullname: parts[3],
    });
  }
  return rows;
}

/**
 * @param {{term: string, definition: string}} card
 * @param {{shortname: string, fullname: string, domain: string}[]} objectives
 * @returns {{shortname: string, fullname: string, domain: string}}
 */
function mapObjective(card, objectives) {
  const text = `${card.term} ${card.definition}`.toLowerCase();
  let best = objectives[0];
  let bestScore = -1;
  for (const obj of objectives) {
    let score = 0;
    const objText = `${obj.fullname} ${obj.domain}`.toLowerCase();
    for (const word of text.split(/\W+/)) {
      if (word.length < 4) {
        continue;
      }
      if (objText.includes(word)) {
        score += 2;
      }
    }
    for (const kw of DOMAIN_KEYWORDS[obj.domain] || []) {
      if (text.includes(kw)) {
        score += 1;
      }
    }
    if (score > bestScore) {
      bestScore = score;
      best = obj;
    }
  }
  return best;
}

/**
 * @param {string} pdfPath
 * @returns {Promise<string>}
 */
async function extractPdfText(pdfPath) {
  const { PDFParse } = require('pdf-parse');
  const buf = fs.readFileSync(pdfPath);
  const parser = new PDFParse({ data: buf });
  const data = await parser.getText();
  return data.text;
}

async function main() {
  const txtArg = process.argv[2];
  let raw;
  if (txtArg) {
    raw = fs.readFileSync(path.resolve(txtArg), 'utf8');
  } else {
    const sources = JSON.parse(fs.readFileSync(path.join(REPO, 'content/a-plus/sources.json'), 'utf8'));
    const pdfRel = sources.external_sources?.flashcards_pdf;
    if (!pdfRel) {
      throw new Error('flashcards_pdf missing from content/a-plus/sources.json');
    }
    const pdfPath = pdfRel.replace(/%USERPROFILE%/g, process.env.USERPROFILE || '');
    raw = await extractPdfText(pdfPath);
  }

  const cards = parseFlashcards(raw);
  if (cards.length < 100) {
    throw new Error(`Expected hundreds of flashcards, parsed only ${cards.length}`);
  }

  const objectives = loadObjectives(fs.readFileSync(CSV_PATH, 'utf8'));
  const byDomain = new Map();
  for (const obj of objectives) {
    if (!byDomain.has(obj.domain)) {
      byDomain.set(obj.domain, []);
    }
    byDomain.get(obj.domain).push(obj);
  }

  const mapped = cards.map((card) => ({
    card,
    objective: mapObjective(card, objectives),
  }));

  const giftLines = [
    '// Generated from CyberKraft CertMaster flashcards — do not hand-edit; rebuild via:',
    '// node scripts/build-aplus-gift-from-flashcards.cjs',
    '',
  ];

  mapped.forEach(({ card, objective }, idx) => {
    const domainPool = mapped
      .filter((m) => m.objective.domain === objective.domain && m.card.num !== card.num)
      .map((m) => clip(m.card.definition, 120));
    const distractors = [];
    for (let i = 0; i < domainPool.length && distractors.length < 3; i++) {
      const pick = domainPool[(idx + i * 17) % domainPool.length];
      if (pick && pick !== clip(card.definition, 120) && !distractors.includes(pick)) {
        distractors.push(pick);
      }
    }
    while (distractors.length < 3) {
      distractors.push('None of the listed options apply to this CompTIA A+ topic.');
    }

    const qname = `${objective.shortname}_fc${String(card.num).padStart(4, '0')}`;
    const prompt = `Which statement best describes ${card.term}?`;
    const correct = clip(card.definition, 220);

    giftLines.push(`::${escapeGift(qname)} ${escapeGift(clip(card.term, 40))}::${escapeGift(prompt)}{`);
    giftLines.push(`=${escapeGift(correct)}`);
    for (const d of distractors.slice(0, 3)) {
      giftLines.push(`~${escapeGift(d)}`);
    }
    giftLines.push('}');
    giftLines.push('');
  });

  fs.writeFileSync(OUT_PATH, giftLines.join('\n'), 'utf8');

  const perObjective = {};
  for (const { objective } of mapped) {
    perObjective[objective.shortname] = (perObjective[objective.shortname] || 0) + 1;
  }

  const manifest = {
    generated_at: new Date().toISOString(),
    source: 'CyberKraft CertMaster flashcards PDF',
    cards_parsed: cards.length,
    questions_written: mapped.length,
    gift_path: 'content/a-plus/aplus-quiz.gift',
    per_objective_min: Math.min(...Object.values(perObjective)),
    per_objective_max: Math.max(...Object.values(perObjective)),
    per_objective_avg: Math.round(mapped.length / objectives.length),
  };
  fs.writeFileSync(MANIFEST_PATH, JSON.stringify(manifest, null, 2) + '\n', 'utf8');

  console.log(JSON.stringify(manifest, null, 2));
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
