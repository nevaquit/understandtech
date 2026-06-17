/**
 * Shared N10-009 objective metadata for Network+ content scripts.
 */
import fs from 'node:fs';

/** @typedef {{cert: string, domain: string, shortname: string, fullname: string}} ObjectiveRow */

/**
 * @param {string} csvText
 * @returns {ObjectiveRow[]}
 */
export function loadObjectivesFromCsv(csvText) {
  const lines = csvText.trim().split(/\r?\n/).slice(1);
  return lines.map((line) => {
    const m = line.match(/^([^,]+),([^,]+),([^,]+),"(.*)"$/);
    if (!m) {
      throw new Error(`Invalid objectives CSV line: ${line}`);
    }
    return {
      cert: m[1],
      domain: m[2],
      shortname: m[3],
      fullname: m[4],
    };
  });
}

/**
 * @param {string} csvPath
 * @returns {ObjectiveRow[]}
 */
export function loadObjectivesCsvFile(csvPath) {
  return loadObjectivesFromCsv(fs.readFileSync(csvPath, 'utf8'));
}

/** @type {Record<string, string[]>} */
export const DOMAIN_KEYWORDS = {
  network_fundamentals: [
    'osi', 'layer', 'tcp', 'udp', 'port', 'dns', 'dhcp', 'http', 'https', 'subnet', 'cidr', 'vlsm',
    'cloud', 'saas', 'iaas', 'paas', 'fiber', 'copper', 'cat5', 'cat6', 'topology', 'vlan', 'ipv4',
    'ipv6', 'sd-wan', 'sdn', 'vxlan', 'zero trust', 'nat64', 'multicast', 'broadcast', 'anycast',
    'hub', 'switch', 'router', 'proxy', 'cdn', 'api', 'rfc1918', 'apipa',
  ],
  network_impl: [
    'routing', 'ospf', 'eigrp', 'bgp', 'static route', 'nat', 'pat', 'fhrp', 'subinterface',
    '802.1q', 'trunk', 'spanning tree', 'stp', 'svi', 'link aggregation', 'wireless', '802.11',
    'wpa', 'ssid', 'access point', 'channel', 'antenna', 'rack', 'mdf', 'idf', 'ups', 'pdu',
    'router-on-a-stick', 'inter-vlan',
  ],
  network_ops: [
    'snmp', 'syslog', 'siem', 'monitor', 'baseline', 'ipam', 'documentation', 'change management',
    'rpo', 'rto', 'disaster', 'failover', 'hot site', 'cold site', 'dhcp scope', 'dnssec', 'doh',
    'dot', 'ntp', 'vpn', 'split tunnel', 'jump box', 'out-of-band', 'in-band',
  ],
  network_security: [
    'firewall', 'ids', 'ips', '802.1x', 'nac', 'acl', 'encryption', 'pki', 'certificate', 'mfa',
    'radius', 'ldap', 'saml', 'tacacs', 'segmentation', 'dmz', 'screened subnet', 'arp spoof',
    'dns poisoning', 'evil twin', 'rogue', 'ddos', 'vlan hopping', 'mac flood', 'phishing',
    'honeypot', 'cia', 'pci', 'gdpr', 'byod', 'iot', 'scada',
  ],
  network_troubleshoot: [
    'troubleshoot', 'methodology', 'ping', 'traceroute', 'nslookup', 'dig', 'tcpdump', 'netstat',
    'cable tester', 'crc', 'attenuation', 'crosstalk', 'poE', 'poe', 'jitter', 'latency',
    'congestion', 'interference', 'roaming', 'duplicate ip', 'default gateway', 'root bridge',
    'show mac', 'show route', 'show interface', 'lldp', 'cdp',
  ],
};

/** @type {Record<string, number>} */
export const OBJECTIVE_COUNTS = { 1: 8, 2: 4, 3: 5, 4: 3, 5: 5 };

/**
 * @param {number} domain
 * @param {number} objective
 * @returns {boolean}
 */
export function isValidObjective(domain, objective) {
  return OBJECTIVE_COUNTS[domain] !== undefined && objective >= 1 && objective <= OBJECTIVE_COUNTS[domain];
}

/**
 * @param {string} sourcesJsonPath
 * @returns {string}
 */
export function resolveSourcePath(sourcesJsonPath, key) {
  const sources = JSON.parse(fs.readFileSync(sourcesJsonPath, 'utf8'));
  const rel = sources.n10_009?.[key];
  if (!rel) {
    throw new Error(`Missing sources.n10_009.${key}`);
  }
  return rel.replace(/%USERPROFILE%/g, process.env.USERPROFILE || '');
}

/**
 * @param {string} text
 * @param {string} domainShort
 * @param {ObjectiveRow[]} objectives
 * @returns {ObjectiveRow}
 */
export function mapObjectiveByKeywords(text, domainShort, objectives) {
  const pool = objectives.filter((o) => o.domain === domainShort);
  const hay = text.toLowerCase();
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
    const numMatch = obj.shortname.match(/^n10009_(\d)_(\d+)$/);
    if (numMatch) {
      const tag = `${numMatch[1]}.${numMatch[2]}`;
      if (hay.includes(tag)) {
        score += 5;
      }
    }
    if (score > bestScore) {
      bestScore = score;
      best = obj;
    }
  }
  return best;
}

/** @type {Record<string, string>} */
export const PRACTICE_DOMAIN_MAP = {
  'NETWORKING CONCEPTS': 'network_fundamentals',
  'NETWORK FUNDAMENTALS': 'network_fundamentals',
  'NETWORK IMPLEMENTATIONS': 'network_impl',
  'NETWORK OPERATIONS': 'network_ops',
  'NETWORK SECURITY': 'network_security',
  'NETWORK TROUBLESHOOTING': 'network_troubleshoot',
};
