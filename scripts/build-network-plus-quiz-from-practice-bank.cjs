'use strict';

/**
 * Build content/network-plus/n10-009-quiz.gift from CyberKraft practice exam bank PDF.
 *
 * Usage:
 *   node scripts/build-network-plus-quiz-from-practice-bank.cjs [path-to-extracted.txt]
 */

const fs = require('fs');
const path = require('path');

const REPO = path.resolve(__dirname, '..');
const CSV_PATH = path.join(REPO, 'content/network-plus/n10-009-objectives.csv');
const OUT_PATH = path.join(REPO, 'content/network-plus/n10-009-quiz.gift');
const MANIFEST_PATH = path.join(REPO, 'content/network-plus/n10-009-quiz.manifest.json');
const SOURCES_PATH = path.join(REPO, 'content/network-plus/sources.json');

const DOMAIN_KEYWORDS = {
  network_fundamentals: [
    'osi', 'layer', 'tcp', 'udp', 'port', 'dns', 'dhcp', 'http', 'https', 'subnet', 'cidr', 'vlsm',
    'cloud', 'saas', 'iaas', 'paas', 'fiber', 'copper', 'cat5', 'cat6', 'topology', 'ipv4', 'ipv6',
    'hub', 'switch', 'router', 'proxy', 'cdn', 'multicast', 'broadcast', 'api', 'nat', 'gre', 'ipsec',
  ],
  network_impl: [
    'routing', 'ospf', 'eigrp', 'bgp', 'static route', 'nat', 'pat', 'fhrp', 'subinterface', '802.1q',
    'trunk', 'spanning tree', 'stp', 'svi', 'link aggregation', 'wireless', '802.11', 'wpa', 'ssid',
    'access point', 'channel', 'antenna', 'rack', 'mdf', 'idf', 'ups', 'router-on-a-stick', 'inter-vlan',
  ],
  network_ops: [
    'snmp', 'syslog', 'siem', 'monitor', 'baseline', 'ipam', 'documentation', 'change management',
    'rpo', 'rto', 'disaster', 'failover', 'hot site', 'dhcp scope', 'dnssec', 'doh', 'dot', 'ntp',
    'vpn', 'split tunnel', 'jump box', 'out-of-band', 'in-band',
  ],
  network_security: [
    'firewall', 'ids', 'ips', '802.1x', 'nac', 'acl', 'encryption', 'pki', 'certificate', 'mfa',
    'radius', 'ldap', 'segmentation', 'dmz', 'arp spoof', 'dns poisoning', 'evil twin', 'rogue',
    'ddos', 'vlan hopping', 'mac flood', 'phishing', 'honeypot', 'cia',
  ],
  network_troubleshoot: [
    'troubleshoot', 'methodology', 'ping', 'traceroute', 'nslookup', 'dig', 'tcpdump', 'netstat',
    'cable tester', 'crc', 'attenuation', 'crosstalk', 'poe', 'jitter', 'latency', 'congestion',
    'interference', 'duplicate ip', 'default gateway', 'show mac', 'show route', 'lldp', 'cdp',
  ],
};

const PRACTICE_DOMAIN_MAP = {
  'NETWORKING CONCEPTS': 'network_fundamentals',
  'NETWORK FUNDAMENTALS': 'network_fundamentals',
  'NETWORK IMPLEMENTATIONS': 'network_impl',
  'NETWORK OPERATIONS': 'network_ops',
  'NETWORK SECURITY': 'network_security',
  'NETWORK TROUBLESHOOTING': 'network_troubleshoot',
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
 * @param {string} csvText
 * @returns {{shortname: string, fullname: string, domain: string}[]}
 */
function loadObjectives(csvText) {
  return csvText
    .trim()
    .split(/\r?\n/)
    .slice(1)
    .map((line) => {
      const m = line.match(/^([^,]+),([^,]+),([^,]+),"(.*)"$/);
      if (!m) {
        throw new Error(`Invalid CSV line: ${line}`);
      }
      return { cert: m[1], domain: m[2], shortname: m[3], fullname: m[4] };
    });
}

/**
 * @param {string} raw
 * @returns {string}
 */
function sanitizePracticeText(raw) {
  return raw
    .replace(/\r\n/g, '\n')
    .replace(/[ \t]+\n/g, '\n')
    .split('\n')
    .filter((line) => {
      const t = line.trim();
      if (!t) {
        return true;
      }
      if (/^-- \d+ of \d+ --$/.test(t)) {
        return false;
      }
      if (/N10-009 Network\+ Practice Question Bank/i.test(t)) {
        return false;
      }
      if (/For personal exam preparation/i.test(t)) {
        return false;
      }
      if (/^— \d+ —$/.test(t)) {
        return false;
      }
      return true;
    })
    .join('\n');
}

/**
 * @param {string} block
 * @returns {{qnum: number, domain: string, stem: string, options: Map<string, string>, correct: string, correctText: string}|null}
 */
function parseQuestionBlock(block) {
  const header = block.match(/^Q\s+(\d+)\s+DOMAIN:\s*(.+)$/im);
  if (!header) {
    return null;
  }
  const qnum = parseInt(header[1], 10);
  const domain = header[2].trim().replace(/\s+/g, ' ');

  const correctM = block.match(/CORRECT ANSWER:\s*([A-D])\.\s*(.+)$/im);
  if (!correctM) {
    return null;
  }
  const correct = correctM[1];
  const correctText = correctM[2].trim();

  const lines = block.split('\n');
  const stemLines = [];
  /** @type {Map<string, string>} */
  const options = new Map();
  let phase = 'stem';

  for (let i = 1; i < lines.length; i++) {
    const line = lines[i].trim();
    if (!line) {
      continue;
    }
    if (/^CORRECT ANSWER:/i.test(line)) {
      break;
    }
    if (/^OPTION-BY-OPTION/i.test(line)) {
      break;
    }
    const opt = line.match(/^\d+\s+([A-D])\.\s*(.+)$/);
    if (opt) {
      phase = 'options';
      options.set(opt[1], opt[2].trim());
      continue;
    }
    if (phase === 'stem') {
      stemLines.push(line);
    }
  }

  if (options.size < 2) {
    return null;
  }
  const stem = stemLines.join(' ').replace(/\s+/g, ' ').trim();
  if (!stem) {
    return null;
  }
  return { qnum, domain, stem, options, correct, correctText };
}

/**
 * @param {string} raw
 * @returns {ReturnType<typeof parseQuestionBlock>[]}
 */
function parsePracticeBank(raw) {
  const cleaned = sanitizePracticeText(raw);
  const blocks = cleaned.split(/\n(?=Q\s+\d+\s+DOMAIN:)/i);
  const questions = [];
  for (const block of blocks) {
    const q = parseQuestionBlock(block);
    if (q) {
      questions.push(q);
    }
  }
  return questions;
}

/**
 * @param {{stem: string, domain: string}} question
 * @param {{shortname: string, fullname: string, domain: string}[]} objectives
 * @returns {{shortname: string, fullname: string, domain: string}}
 */
function mapObjective(question, objectives) {
  const domainShort = PRACTICE_DOMAIN_MAP[question.domain.toUpperCase()];
  const pool = domainShort
    ? objectives.filter((o) => o.domain === domainShort)
    : objectives;
  const hay = `${question.stem} ${question.domain}`.toLowerCase();
  let best = pool[0] || objectives[0];
  let bestScore = -1;

  for (const obj of pool) {
    let score = 0;
    const objText = obj.fullname.toLowerCase();
    for (const word of hay.split(/\W+/)) {
      if (word.length < 4) {
        continue;
      }
      if (objText.includes(word)) {
        score += 3;
      }
    }
    for (const kw of DOMAIN_KEYWORDS[obj.domain] || []) {
      if (hay.includes(kw)) {
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

/**
 * @param {ReturnType<typeof parseQuestionBlock>} q
 * @param {{shortname: string}} objective
 * @param {number} idx
 * @returns {string}
 */
function toGift(q, objective, idx) {
  const tag = `${objective.shortname}_pb${q.qnum}`;
  const letters = ['A', 'B', 'C', 'D'];
  const present = letters.filter((l) => q.options.has(l));
  if (present.length < 2) {
    return '';
  }
  const lines = present.map((l) => {
    const prefix = l === q.correct ? '=' : '~';
    return `${prefix}${escapeGift(q.options.get(l) || q.correctText)}`;
  });
  return [
    `::${tag} [${objective.shortname}] ${escapeGift(q.stem.slice(0, 80))} ::`,
    escapeGift(q.stem),
    '{',
    lines.join('\n'),
    '}',
    '',
  ].join('\n');
}

async function main() {
  const txtArg = process.argv[2];
  let raw;
  if (txtArg) {
    raw = fs.readFileSync(path.resolve(txtArg), 'utf8');
  } else {
    const sources = JSON.parse(fs.readFileSync(SOURCES_PATH, 'utf8'));
    const pdfRel = sources.n10_009?.practice_exam_bank_pdf;
    if (!pdfRel) {
      throw new Error('practice_exam_bank_pdf missing from sources.json');
    }
    const pdfPath = pdfRel.replace(/%USERPROFILE%/g, process.env.USERPROFILE || '');
    raw = await extractPdfText(pdfPath);
  }

  const questions = parsePracticeBank(raw);
  if (questions.length < 50) {
    throw new Error(`Expected 50+ solved MCQs, parsed only ${questions.length}`);
  }

  const objectives = loadObjectives(fs.readFileSync(CSV_PATH, 'utf8'));
  const giftLines = [
    '// Generated from CyberKraft N10-009 Practice Exam Bank — rebuild via:',
    '// node scripts/build-network-plus-quiz-from-practice-bank.cjs',
    '',
  ];
  /** @type {Record<string, number>} */
  const byObjective = {};
  let written = 0;

  for (const q of questions) {
    const objective = mapObjective(q, objectives);
    const block = toGift(q, objective, written);
    if (!block) {
      continue;
    }
    giftLines.push(block);
    byObjective[objective.shortname] = (byObjective[objective.shortname] || 0) + 1;
    written += 1;
  }

  const manifest = {
    generated: new Date().toISOString(),
    source: 'N10-009_Network_Plus_Practice_Exam_Bank',
    questions: written,
    byObjective,
  };

  fs.writeFileSync(OUT_PATH, `${giftLines.join('\n')}\n`);
  fs.writeFileSync(MANIFEST_PATH, `${JSON.stringify(manifest, null, 2)}\n`);

  console.log(`gift_questions=${written}`);
  console.log(`parsed_solved=${questions.length}`);
  console.log(`gift=${OUT_PATH}`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
