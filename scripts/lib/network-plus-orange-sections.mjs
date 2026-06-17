/**
 * Parse CyberKraft Orange Study Guide text into structured Network+ sections.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { isValidObjective } from './network-plus-objectives.mjs';
import { resolveSourcePath } from './network-plus-objectives.mjs';
import { sanitizeNetworkOrangeText, isOrangeNoiseLine } from './sanitize-network-orange-text.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
export const repoRoot = path.resolve(__dirname, '..', '..');
export const contentDir = path.join(repoRoot, 'content', 'network-plus');
export const sourcesPath = path.join(contentDir, 'sources.json');

/**
 * @param {string} text
 */
export function escapeHtml(text) {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/**
 * @param {string} pdfPath
 * @returns {Promise<string>}
 */
export async function extractPdfText(pdfPath) {
  const { PDFParse } = await import('pdf-parse');
  const buf = fs.readFileSync(pdfPath);
  const parser = new PDFParse({ data: buf });
  const data = await parser.getText();
  return data.text;
}

/**
 * @param {string} text
 * @returns {Map<string, string[]>}
 */
/**
 * @param {string[]} lines
 * @returns {string[]}
 */
export function repairSectionLines(lines) {
  /** @type {string[]} */
  let out = [];
  const topoNames = /^(Star|Mesh|Bus|Ring|Hybrid)\b/;
  let i = 0;
  while (i < lines.length) {
    const line = lines[i];
    if (lines[i] === 'Ste' && lines[i + 1] === 'p') {
      out.push('Step Action Key Activities');
      i += 2;
      if (lines[i] === 'Action Key Activities') {
        i += 1;
      }
      continue;
    }
    if (/^Topology Description Pros Cons$/i.test(line)) {
      out.push(line);
      i += 1;
      /** @type {string} */
      let buf = '';
      while (i < lines.length && !/^(Enterprise Network|•|\d+ EXAM TIP|H PRO-TIP)/.test(lines[i])) {
        if (topoNames.test(lines[i])) {
          if (buf) {
            out.push(buf.trim());
          }
          buf = lines[i];
        } else if (buf) {
          buf += ` ${lines[i]}`;
        } else {
          out.push(lines[i]);
        }
        i += 1;
      }
      if (buf) {
        out.push(buf.trim());
      }
      continue;
    }
    if (/^(\d+)\s+/.test(line) && !/^(\d+)\.(\d+)\s/.test(line) && !/^\d+ EXAM TIP/.test(line)) {
      let buf = line;
      i += 1;
      while (
        i < lines.length &&
        !/^(\d+)\s+/.test(lines[i]) &&
        !/^\d+ EXAM TIP/.test(lines[i]) &&
        !/^H PRO-TIP/.test(lines[i]) &&
        !/^(\d)\.(\d+)\s/.test(lines[i]) &&
        !/^APPENDIX/.test(lines[i])
      ) {
        buf += ` ${lines[i]}`;
        i += 1;
      }
      out.push(buf.trim());
      continue;
    }
    out.push(line);
    i += 1;
  }
  out = out.filter((l) => l !== 'Action Key Activities' && l !== 'Cause' && l !== 'Escalate' && l !== 'Functionality');
  return out.flatMap(explodeMashedWirelessLine);
}

/**
 * Split PDF repair artifacts where EXAM TIP and wireless subsections merge into one line.
 * @param {string} line
 * @returns {string[]}
 */
function explodeMashedWirelessLine(line) {
  if (!/^\d+ EXAM TIP/.test(line) || !line.includes('Wireless Encryption')) {
    return [line];
  }
  /** @type {string[]} */
  const out = [];
  const examMatch = line.match(/^(\d+) EXAM TIP\s+/);
  if (!examMatch) {
    return [line];
  }
  out.push(`${examMatch[1]} EXAM TIP`);
  let rest = line.slice(examMatch[0].length);

  const encIdx = rest.indexOf('Wireless Encryption');
  if (encIdx > 0) {
    out.push(rest.slice(0, encIdx).trim());
    rest = rest.slice(encIdx);
  }

  rest = rest.replace(/^Wireless Encryption\s+Protocol Security Level Notes\s*/, '');
  out.push('Wireless Encryption');
  out.push('Protocol Security Level Notes');

  const authIdx = rest.indexOf('Wireless Authentication');
  const encText = authIdx >= 0 ? rest.slice(0, authIdx).trim() : rest;
  const encRows = encText.split(/\s+(?=WEP\s|WPA2?\s|WPA3\s)/);
  for (const row of encRows) {
    if (row.trim()) {
      out.push(row.trim());
    }
  }

  if (authIdx >= 0) {
    rest = rest.slice(authIdx);
    out.push('Wireless Authentication');
    const apIdx = rest.indexOf('AP Deployment');
    const authText = apIdx >= 0 ? rest.slice('Wireless Authentication'.length, apIdx) : rest.slice('Wireless Authentication'.length);
    for (const bullet of authText.split(/•/).map((b) => b.trim()).filter(Boolean)) {
      out.push(`• ${bullet}`);
    }
    if (apIdx >= 0) {
      out.push('AP Deployment');
      const apText = rest.slice(apIdx + 'AP Deployment'.length);
      for (const bullet of apText.split(/•/).map((b) => b.trim()).filter(Boolean)) {
        out.push(`• ${bullet}`);
      }
    }
  }

  return out;
}

/**
 * @param {string} text
 * @returns {Map<string, string[]>}
 */
export function splitOrangeSections(text) {
  const cleaned = sanitizeNetworkOrangeText(text);
  const lines = cleaned.split('\n');
  /** @type {Map<string, string[]>} */
  const sections = new Map();
  /** @type {string|null} */
  let currentKey = null;
  /** @type {string[]} */
  let currentLines = [];

  const flush = () => {
    if (currentKey && currentLines.length) {
      sections.set(currentKey, repairSectionLines(currentLines));
    }
    currentLines = [];
  };

  for (const raw of lines) {
    const line = raw.trim();
    if (isOrangeNoiseLine(line)) {
      continue;
    }
    if (/^APPENDIX [A-Z]/i.test(line)) {
      flush();
      currentKey = null;
      continue;
    }

    const m = line.match(/^(\d)\.(\d+)\s+(.+)$/);
    if (m) {
      const domain = parseInt(m[1], 10);
      const objective = parseInt(m[2], 10);
      if (isValidObjective(domain, objective)) {
        flush();
        currentKey = `n10009_${domain}_${objective}`;
        currentLines = [];
        continue;
      }
    }

    if (currentKey) {
      currentLines.push(line);
    }
  }
  flush();
  return sections;
}

/**
 * @param {string} text
 * @returns {string}
 */
/**
 * @param {string} text
 * @returns {string}
 */
export function extractAppendixBHtml(text) {
  const cleaned = sanitizeNetworkOrangeText(text);
  const start = cleaned.search(/^APPENDIX B$/m);
  if (start === -1) {
    return '';
  }
  const slice = cleaned.slice(start);
  const end = slice.search(/^APPENDIX C$/m);
  const body = end === -1 ? slice : slice.slice(0, end);
  const lines = body.split('\n').slice(2).filter((l) => !isOrangeNoiseLine(l.trim()) && l.trim());
  const html = orangeLinesToHtml(lines);
  if (!html) {
    return '';
  }
  return [
    '<div class="ut-lesson-supplement">',
    '<h4>Appendix B — Network+ port cheat sheet</h4>',
    html,
    '</div>',
  ].join('\n');
}

const OSI_LAYERS = '(?:Application|Presentation|Session|Transport|Network|Data Link|Physical)';

/** @type {Record<string, {header: string[], testHeader: RegExp, parseRow: (line: string) => string[]|null}>} */
const TABLE_TYPES = {
  osi: {
    header: ['Layer', '#', 'Name', 'PDU', 'Key protocols / devices'],
    testHeader: /^Layer # Name PDU/i,
    parseRow(line) {
      const m = line.match(
        /^(Application|Presentation|Session|Transport|Network|Data Link|Physical)\s+(\d+)\s+(Data Link|Application|Presentation|Session|Transport|Network|Physical)\s+(Frame|Bit|Packet|Segment\/Datagram|Data)\s+(.+)$/
      );
      return m ? [m[1], m[2], m[3], m[4], m[5]] : null;
    },
  },
  port3: {
    header: ['Port', 'Protocol', 'Notes'],
    testHeader: /^Port Protocol Notes$/i,
    parseRow(line) {
      const m = line.match(/^(\d+)\s+(\S+(?:\s*\/\s*\S+)?)\s+(.+)$/);
      return m ? [m[1], m[2], m[3]] : null;
    },
  },
  port4: {
    header: ['Port', 'Protocol', 'Service', 'Notes'],
    testHeader: /^Port Protocol Service Notes$/i,
    parseRow(line) {
      const m = line.match(/^(\d+)\s+(TCP(?:\/UDP)?|UDP(?:\/TCP)?|TCP|UDP)\s+(.+?)\s+(.+)$/i);
      if (m) {
        const serviceNotes = m[3].trim();
        const slashParts = serviceNotes.match(/^(.+?)\s+(.+)$/);
        if (slashParts && /[A-Z]{2,}/.test(slashParts[2])) {
          return [m[1], m[2], slashParts[1], `${slashParts[2]} ${m[4]}`];
        }
        return [m[1], m[2], m[3], m[4]];
      }
      return null;
    },
  },
  device: {
    header: ['Device', 'OSI layer', 'Function'],
    testHeader: /^Device OSI Layer Function$/i,
    parseRow(line) {
      const m = line.match(/^(Hub|Switch|Router|Firewall|IDS|IPS|Load Balancer|Proxy Server|WAP)\s+([\d/]+)\s+(.+)$/i);
      return m ? [m[1], m[2], m[3]] : null;
    },
  },
  cable: {
    header: ['Cable', 'Max speed', 'Max distance', 'Notes'],
    testHeader: /^Cable Max Speed Max Distance Notes$/i,
    parseRow(line) {
      const m = line.match(/^(Cat\d[a-z]?)\s+([\d.]+\s*[GMK]?bps)\s+([\d.]+\s*m)\s+(.+)$/i);
      return m ? [m[1], m[2], m[3], m[4]] : null;
    },
  },
  fiber: {
    header: ['Type', 'Core size', 'Max distance', 'Use case'],
    testHeader: /^Type Core Size Max Distance Use Case$/i,
    parseRow(line) {
      const m = line.match(/^(Single-mode \(SMF\)|Multimode \(MMF\))\s+(.+?)\s+(Up to [\d.]+\s*(?:km|m))\s+(.+)$/i);
      return m ? [m[1], m[2], m[3], m[4]] : null;
    },
  },
  connector: {
    header: ['Connector', 'Common use'],
    testHeader: /^Connector Type Common Use$/i,
    parseRow(line) {
      const m = line.match(/^([A-Z0-9-]+(?:\s+[A-Za-z]+)?)\s+(Fiber|Copper|Coaxial)\s+(.+)$/i);
      if (m) {
        return [`${m[1]} ${m[2]}`, m[3]];
      }
      const m2 = line.match(/^([A-Z0-9-]+(?:\s+[A-Za-z]+)?)\s+(.+)$/);
      return m2 ? [m2[1], m2[2]] : null;
    },
  },
  topology: {
    header: ['Topology', 'Description', 'Pros', 'Cons'],
    testHeader: /^Topology Description Pros Cons$/i,
    parseRow(line) {
      const prosPatterns = [
        'Easy to manage; fault isolation',
        'High redundancy',
        'Simple; inexpensive',
        'Predictable performance',
        'Flexible',
      ];
      const m = line.match(/^(Star|Mesh|Bus|Ring|Hybrid)\s+(.+)$/i);
      if (!m) {
        return null;
      }
      for (const pros of prosPatterns) {
        const idx = m[2].indexOf(pros);
        if (idx !== -1) {
          return [m[1], m[2].slice(0, idx).trim(), pros, m[2].slice(idx + pros.length).trim()];
        }
      }
      return null;
    },
  },
  ipClass: {
    header: ['Class', 'First octet', 'Default mask', 'Private range'],
    testHeader: /^Class First Octet Default Mask Private Range/i,
    parseRow(line) {
      const m = line.match(/^([A-E])\s+([\d-]+|224-239|240-255)\s+(\S+|N\/A)\s+(.+)$/i);
      return m ? [m[1], m[2], m[3], m[4]] : null;
    },
  },
  cidr: {
    header: ['CIDR', 'Subnet mask', 'Network bits', 'Host bits', 'Total addresses', 'Usable hosts'],
    testHeader: /^CIDR Subnet Mask Network Bits Host Bits Total Addresses Usable Hosts$/i,
    parseRow(line) {
      const m = line.match(/^(\/\d+)\s+(\S+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)$/);
      return m ? [m[1], m[2], m[3], m[4], m[5], m[6]] : null;
    },
  },
  troubleshootStep: {
    header: ['Step', 'Action', 'Key activities'],
    testHeader: /^Step Action Key Activities$/i,
    parseRow(line) {
      const m = line.match(
        /^(\d+)\s+(Identify the Problem|Establish a Theory of Probable Cause|Test the Theory|Establish a Plan of Action|Implement the Solution or Escalate|Verify Full System Functionality|Document Everything)\s+(.+)$/i
      );
      return m ? [m[1], m[2], m[3]] : null;
    },
  },
  wifiStandard: {
    header: ['Standard', 'Frequency', 'Max speed', 'Notes'],
    testHeader: /^Standard Frequency Max Speed Key Feature$/i,
    parseRow(line) {
      const m = line.match(
        /^(802\.11[\w()-]+(?:\s*\([^)]+\))?)\s+([\d./\s]+GHz)\s+([\d.]+\s*[GMK]?bps)\s+(.+)$/i
      );
      return m ? [m[1], m[2].replace(/\s+/g, ' ').trim(), m[3], m[4]] : null;
    },
  },
  wirelessEnc: {
    header: ['Protocol', 'Security level', 'Notes'],
    testHeader: /^Protocol Security Level Notes$/i,
    parseRow(line) {
      const m = line.match(/^(WEP|WPA2?|WPA3)\s+(\S+)\s+(.+)$/i);
      return m ? [m[1], m[2], m[3]] : null;
    },
  },
  cloudModel: {
    header: ['Model', 'Description', 'Example'],
    testHeader: /^Model Description Example$/i,
    parseRow(line) {
      const m = line.match(/^(Public Cloud|Private Cloud|Hybrid Cloud|Community Cloud|SaaS|PaaS|IaaS)\s+(.+?)\s+(.+)$/i);
      return m ? [m[1], m[2], m[3]] : null;
    },
  },
  serviceModel: {
    header: ['Model', 'You manage', 'Provider manages', 'Example'],
    testHeader: /^Model You Manage Provider Manages Example$/i,
    parseRow(line) {
      const m = line.match(/^(SaaS|PaaS|IaaS)\s+(.+?)\s+(.+?)\s+(.+)$/i);
      return m ? [m[1], m[2], m[3], m[4]] : null;
    },
  },
};

/**
 * @param {string} line
 * @returns {string|null}
 */
function detectTableType(line) {
  for (const [key, cfg] of Object.entries(TABLE_TYPES)) {
    if (cfg.testHeader.test(line)) {
      return key;
    }
  }
  return null;
}

/**
 * @param {string} line
 * @param {string|null} activeType
 * @returns {string[]|null}
 */
function parseStructuredRow(line, activeType) {
  if (!activeType) {
    return null;
  }
  return TABLE_TYPES[activeType]?.parseRow(line) || null;
}

/**
 * @param {string} line
 */
function isSubheading(line) {
  if (detectTableType(line)) {
    return false;
  }
  if (line.length > 72 || line.length < 4) {
    return false;
  }
  if (/^\d/.test(line) && /\d{2,}/.test(line)) {
    return false;
  }
  if (/^(Model|Port|Layer|Device|Type|Connector|Topology|Acronym|Metric|Site|Tool|Cable)\b/i.test(line)) {
    return false;
  }
  if (line.endsWith('.') && line.split(' ').length > 6) {
    return false;
  }
  if (/[:]$/.test(line)) {
    return true;
  }
  const words = line.split(/\s+/);
  if (words.length <= 6 && /^[A-Z]/.test(line) && !line.includes('  ')) {
    return true;
  }
  return false;
}

/**
 * @param {string[]} lines
 * @returns {string[]}
 */
function mergeWrappedLines(lines) {
  /** @type {string[]} */
  const merged = [];
  for (const raw of lines) {
    const line = raw.trim();
    if (!line || isOrangeNoiseLine(line)) {
      continue;
    }
    if (!merged.length) {
      merged.push(line);
      continue;
    }
    const prev = merged[merged.length - 1];
    const continuePrev =
      /[,;:]$/.test(prev) ||
      (/^[a-z(]/.test(line) && !detectTableType(line));
    if (
      continuePrev &&
      !detectTableType(line) &&
      !/^(\d+ EXAM TIP|H PRO-TIP|[•]|n )/.test(line)
    ) {
      merged[merged.length - 1] = `${prev} ${line}`;
      continue;
    }
    merged.push(line);
  }
  return merged;
}

/**
 * @param {string|null} tableType
 * @param {string[][]} rows
 */
function structuredTableToHtml(tableType, rows) {
  if (!tableType || !rows.length) {
    return '';
  }
  const header = TABLE_TYPES[tableType].header;
  const headHtml = header.map((c) => `<th>${escapeHtml(c)}</th>`).join('');
  const bodyHtml = rows
    .map((cells) => `<tr>${cells.map((c) => `<td>${escapeHtml(c)}</td>`).join('')}</tr>`)
    .join('\n');
  return `<table class="ut-lesson-table">\n<thead><tr>${headHtml}</tr></thead>\n<tbody>\n${bodyHtml}\n</tbody>\n</table>`;
}

/**
 * @param {string[]} lines
 */
/**
 * @param {string[]} lines
 * @param {{ stripCallouts?: boolean }} [options]
 * @returns {string}
 */
export function orangeLinesToHtml(lines, options = {}) {
  const { stripCallouts = false } = options;
  const merged = mergeWrappedLines(lines);
  const out = [];
  /** @type {string[]} */
  let para = [];
  /** @type {string[]} */
  let ul = [];
  /** @type {string|null} */
  let tableType = null;
  /** @type {string[][]} */
  let tableRows = [];
  /** @type {{type: 'exam'|'pro', lines: string[]}|null} */
  let callout = null;

  const flushPara = () => {
    if (para.length) {
      out.push(`<p>${escapeHtml(para.join(' '))}</p>`);
      para = [];
    }
  };
  const flushUl = () => {
    if (ul.length) {
      out.push(`<ul>\n${ul.map((i) => `<li>${escapeHtml(i)}</li>`).join('\n')}\n</ul>`);
      ul = [];
    }
  };
  const flushTable = () => {
    if (tableRows.length) {
      out.push(structuredTableToHtml(tableType, tableRows));
      tableRows = [];
      tableType = null;
    }
  };
  const flushCallout = () => {
    if (!callout) {
      return;
    }
    if (!stripCallouts) {
      const cls = callout.type === 'pro' ? 'key-concepts' : 'highlight-box';
      const label = callout.type === 'pro' ? 'Pro tip' : 'Exam tip';
      out.push(
        `<div class="${cls}"><h4>${label}</h4><p>${escapeHtml(callout.lines.join(' '))}</p></div>`
      );
    }
    callout = null;
  };
  const flushAll = () => {
    flushCallout();
    flushPara();
    flushUl();
    flushTable();
  };

  for (const line of merged) {
    if (/^H PRO-TIP$/.test(line)) {
      flushAll();
      callout = { type: 'pro', lines: [] };
      continue;
    }
    if (/^H PRO-TIP\s+(.+)$/.test(line)) {
      flushAll();
      callout = { type: 'pro', lines: [line.replace(/^H PRO-TIP\s+/, '')] };
      continue;
    }
    if (/^\d+ EXAM TIP$/.test(line)) {
      flushAll();
      callout = { type: 'exam', lines: [] };
      continue;
    }
    const examInline = line.match(/^\d+ EXAM TIP\s+(.+)$/);
    if (examInline) {
      flushAll();
      callout = { type: 'exam', lines: [examInline[1]] };
      continue;
    }
    if (callout) {
      const endsCallout =
        isSubheading(line) ||
        detectTableType(line) ||
        /^802\.11/.test(line) ||
        /^(WEP|WPA2?|WPA3)\s+/.test(line) ||
        /^[•]/.test(line) ||
        /^n /.test(line);
      if (endsCallout) {
        flushCallout();
      } else {
        callout.lines.push(line);
        continue;
      }
    }

    if (line.startsWith('Memory Aid:')) {
      flushAll();
      if (!stripCallouts) {
        out.push(`<p><strong>${escapeHtml(line)}</strong></p>`);
      }
      continue;
    }

    if (/^802\.11/.test(line) && !tableType) {
      flushPara();
      flushUl();
      flushTable();
      tableType = 'wifiStandard';
      const row = parseStructuredRow(line, tableType);
      if (row) {
        tableRows.push(row);
      }
      continue;
    }

    if (/^(WEP|WPA2?|WPA3)\s+/.test(line) && !tableType) {
      flushPara();
      flushUl();
      flushTable();
      tableType = 'wirelessEnc';
      const row = parseStructuredRow(line, tableType);
      if (row) {
        tableRows.push(row);
      }
      continue;
    }

    if (/^[•]/.test(line)) {
      flushPara();
      flushTable();
      ul.push(line.replace(/^[•]\s*/, ''));
      continue;
    }
    if (/^n /.test(line)) {
      flushPara();
      flushTable();
      ul.push(line.replace(/^n /, ''));
      continue;
    }

    const headerType = detectTableType(line);
    if (headerType) {
      flushPara();
      flushUl();
      flushTable();
      tableType = headerType;
      continue;
    }

    const row = tableType ? parseStructuredRow(line, tableType) : null;
    if (row) {
      flushPara();
      flushUl();
      tableRows.push(row);
      continue;
    }

    if (tableRows.length) {
      flushTable();
    }

    if (isSubheading(line)) {
      flushAll();
      out.push(`<h4>${escapeHtml(line)}</h4>`);
      continue;
    }

    flushTable();
    para.push(line);
  }
  flushAll();
  return out.join('\n');
}

/**
 * @param {string} shortname
 * @param {string} title
 * @param {string} body
 * @param {string} extra
 */
/**
 * @param {string} shortname
 * @param {string} title
 * @param {string} body
 */
export function wrapLesson(shortname, title, body) {
  const m = shortname.match(/^n10009_(\d)_(\d+)$/);
  const display = m ? `N10-009 ${m[1]}.${m[2]}` : shortname;
  return [
    '<div class="ut-lesson-content">',
    `<h3>Exam objective ${escapeHtml(display)}</h3>`,
    `<p><strong>${escapeHtml(title)}</strong></p>`,
    '<div class="ut-lesson-body">',
    body,
    '</div>',
    '<h4>Next steps</h4>',
    '<p>Complete the domain knowledge check quiz after this lesson. Use the AI tutor for scenario-based',
    'questions — it guides you Socratically without revealing assessment answers.</p>',
    '</div>',
    '',
  ].join('\n');
}

/**
 * Build PoE × CAT standards fragment for objective 1.5.
 */
/**
 * @returns {string|null}
 */
export function ensurePoeSnippet() {
  const snippetsDir = path.join(contentDir, 'snippets');
  fs.mkdirSync(snippetsDir, { recursive: true });
  const snippetPath = path.join(snippetsDir, 'poe_cat_standards.frag.html');
  const poePath = resolveSourcePath(sourcesPath, 'poe_cat_standards_html');
  if (!fs.existsSync(poePath)) {
    return fs.existsSync(snippetPath) ? snippetPath : null;
  }

  const html = fs.readFileSync(poePath, 'utf8');
  const legendIdx = html.indexOf('<!-- Legend -->');
  const footnotesIdx = html.indexOf('<!-- Footnotes -->');
  let inner = '';
  if (legendIdx !== -1 && footnotesIdx !== -1) {
    inner = html.slice(legendIdx, footnotesIdx);
  } else if (legendIdx !== -1) {
    inner = html.slice(legendIdx);
  } else {
    const bodyMatch = html.match(/<body[^>]*>([\s\S]*)<\/body>/i);
    inner = bodyMatch ? bodyMatch[1] : '';
  }
  inner = inner
    .replace(/<script[\s\S]*?<\/script>/gi, '')
    .replace(/style="[^"]*"/gi, '')
    .replace(/var\(--[^)]+\)/g, '#1A8A7D')
    .trim();

  const frag = [
    '<div class="ut-lesson-supplement">',
    '<h4>PoE × CAT cable standards reference</h4>',
    inner,
    '</div>',
  ].join('\n');
  fs.writeFileSync(snippetPath, frag);
  return snippetPath;
}

/**
 * @param {string} [arg]
 * @returns {Promise<string>}
 */
export async function loadOrangeText(arg) {
  if (arg) {
    const resolved = path.resolve(arg);
    if (resolved.toLowerCase().endsWith('.pdf')) {
      return extractPdfText(resolved);
    }
    return fs.readFileSync(resolved, 'utf8');
  }
  const pdfPath = resolveSourcePath(sourcesPath, 'orange_study_guide_pdf');
  return extractPdfText(pdfPath);
}

/**
 * Collect exam-focus bullet strings from a section.
 * @param {string[]} lines
 * @returns {string[]}
 */
export function extractExamFocusBullets(lines) {
  const merged = mergeWrappedLines(lines);
  /** @type {string[]} */
  const bullets = [];
  /** @type {string[]|null} */
  let callout = null;

  const flushCallout = () => {
    if (callout?.length) {
      bullets.push(callout.join(' '));
      callout = null;
    }
  };

  for (const line of merged) {
    if (/^H PRO-TIP$/.test(line) || /^\d+ EXAM TIP$/.test(line)) {
      flushCallout();
      callout = [];
      continue;
    }
    const examInline = line.match(/^\d+ EXAM TIP\s+(.+)$/);
    const proInline = line.match(/^H PRO-TIP\s+(.+)$/);
    if (examInline) {
      flushCallout();
      bullets.push(examInline[1]);
      continue;
    }
    if (proInline) {
      flushCallout();
      bullets.push(proInline[1]);
      continue;
    }
    if (callout) {
      callout.push(line);
      continue;
    }
    if (line.startsWith('Memory Aid:')) {
      bullets.push(line);
      continue;
    }
    if (/^[•]/.test(line)) {
      bullets.push(line.replace(/^[•]\s*/, ''));
      continue;
    }
    if (/^n /.test(line)) {
      bullets.push(line.replace(/^n /, ''));
      continue;
    }
    if (/^(\d+)\s+(Identify the Problem|Establish a Theory|Test the Theory|Document Everything)/i.test(line)) {
      bullets.push(line.replace(/^\d+\s+/, ''));
    }
  }
  flushCallout();
  return [...new Set(bullets.map((b) => b.replace(/\s+/g, ' ').trim()).filter((b) => b.length > 6))];
}
