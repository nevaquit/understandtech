#!/usr/bin/env node
/**
 * Build CompTIA Network+ N10-009 objectives CSV, lesson HTML, and quiz GIFT.
 *
 * Sources: CompTIA N10-009 exam objectives v4.0 + CyberKraft Orange study materials.
 *
 * Usage: node scripts/build-network-plus-content.mjs
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, '..');
const contentDir = path.join(repoRoot, 'content', 'network-plus');
const lessonsDir = path.join(contentDir, 'lessons');
const csvPath = path.join(contentDir, 'n10-009-objectives.csv');
const giftPath = path.join(contentDir, 'n10-009-quiz.gift');
const manifestPath = path.join(contentDir, 'n10-009-quiz.manifest.json');

const CERT = 'network_plus_n10_009';

/** @type {Record<string, string>} */
const DOMAIN_MAP = {
  '1': 'network_fundamentals',
  '2': 'network_impl',
  '3': 'network_ops',
  '4': 'network_security',
  '5': 'network_troubleshoot',
};

/**
 * Official N10-009 objectives with study bullets for lessons and quiz generation.
 * @type {Array<{domain: string, num: string, title: string, bullets: string[], quizFacts: string[]}>}
 */
const OBJECTIVES = [
  {
    domain: '1', num: '1.1',
    title: 'Explain concepts related to the Open Systems Interconnection (OSI) reference model',
    bullets: [
      'Layer 1 Physical — bits on the wire; cables, hubs, NICs',
      'Layer 2 Data link — frames, MAC addresses, switches',
      'Layer 3 Network — packets, IP addressing, routers',
      'Layer 4 Transport — TCP vs UDP, ports, reliability',
      'Layer 5 Session — dialog control between applications',
      'Layer 6 Presentation — encryption, compression, encoding',
      'Layer 7 Application — HTTP, DNS, SMTP and user-facing protocols',
    ],
    quizFacts: ['Layer 3 handles routing and logical addressing', 'TCP operates at Layer 4', 'MAC addresses are Layer 2'],
  },
  {
    domain: '1', num: '1.2',
    title: 'Compare and contrast networking appliances, applications, and functions',
    bullets: [
      'Physical/virtual appliances: router, switch, firewall, IDS/IPS, load balancer, proxy, NAS, SAN',
      'Wireless: access point (AP) and wireless controller',
      'Applications: CDN for distributed content delivery',
      'Functions: VPN, QoS, TTL',
    ],
    quizFacts: ['A load balancer distributes traffic across servers', 'IDS detects; IPS can block inline', 'CDN caches content closer to users'],
  },
  {
    domain: '1', num: '1.3',
    title: 'Summarize cloud concepts and connectivity options',
    bullets: [
      'NFV, VPC, network security groups and security lists',
      'Cloud gateways: internet gateway, NAT gateway',
      'Connectivity: VPN, Direct Connect',
      'Deployment models: public, private, hybrid',
      'Service models: SaaS, IaaS, PaaS',
      'Scalability, elasticity, multitenancy',
    ],
    quizFacts: ['IaaS provides virtualized compute and network resources', 'Hybrid cloud combines on-premises and public cloud', 'NAT gateway enables outbound internet from private subnets'],
  },
  {
    domain: '1', num: '1.4',
    title: 'Explain common networking ports, protocols, services, and traffic types',
    bullets: [
      'Know ports: FTP 20/21, SSH/SFTP 22, DNS 53, DHCP 67/68, HTTP 80, HTTPS 443, RDP 3389',
      'Protocols: ICMP, TCP, UDP, GRE, IPsec (AH, ESP, IKE)',
      'Traffic types: unicast, multicast, anycast, broadcast',
    ],
    quizFacts: ['DNS uses UDP/TCP port 53', 'HTTPS uses port 443', 'ICMP is used by ping and traceroute'],
  },
  {
    domain: '1', num: '1.5',
    title: 'Compare and contrast transmission media and transceivers',
    bullets: [
      'Wireless: 802.11, cellular, satellite',
      'Wired: 802.3, single-mode vs multimode fiber, DAC/twinax, coax, plenum vs non-plenum',
      'Transceivers: SFP, QSFP; connectors SC, LC, ST, MPO, RJ45, BNC',
      'See PoE × CAT standards reference in this lesson for cable categories and power budgets',
    ],
    quizFacts: ['Single-mode fiber supports longer distances than multimode', 'RJ45 is used for twisted-pair Ethernet', 'Plenum-rated cable is required in air-handling spaces'],
  },
  {
    domain: '1', num: '1.6',
    title: 'Compare and contrast network topologies, architectures, and types',
    bullets: [
      'Topologies: mesh, hybrid, star/hub-spoke, spine-leaf, point-to-point',
      'Three-tier: core, distribution, access; collapsed core',
      'Traffic flows: north-south (client-server) vs east-west (server-server)',
      'Public vs private addressing: APIPA, RFC1918, loopback',
    ],
    quizFacts: ['Spine-leaf is common in modern data centers', 'RFC1918 defines private IPv4 ranges', 'East-west traffic dominates in virtualized environments'],
  },
  {
    domain: '1', num: '1.7',
    title: 'Given a scenario, use appropriate IPv4 network addressing',
    bullets: [
      'Subnetting with VLSM and CIDR notation',
      'IPv4 classes A–E (historical context)',
      'Calculate network, broadcast, and host counts for a given prefix',
    ],
    quizFacts: ['/24 provides 254 usable host addresses', 'CIDR replaces classful addressing', 'VLSM allows different subnet sizes within one network'],
  },
  {
    domain: '1', num: '1.8',
    title: 'Summarize evolving use cases for modern network environments',
    bullets: [
      'SDN and SD-WAN: centralized policy, zero-touch provisioning, transport agnostic',
      'VXLAN for data center interconnect and Layer 2 encapsulation',
      'Zero trust: policy-based auth, least privilege',
      'SASE/SSE, infrastructure as code, IPv6 (tunneling, dual stack, NAT64)',
    ],
    quizFacts: ['SD-WAN abstracts WAN transport from routing policy', 'Zero trust assumes no implicit trust by location', 'NAT64 helps IPv6-only clients reach IPv4 resources'],
  },
  {
    domain: '2', num: '2.1',
    title: 'Explain characteristics of routing technologies',
    bullets: [
      'Static vs dynamic routing (BGP, EIGRP, OSPF)',
      'Route selection: administrative distance, prefix length, metric',
      'NAT, PAT, FHRP, virtual IP, subinterfaces',
    ],
    quizFacts: ['Lower administrative distance is preferred', 'PAT maps many internal hosts to one public IP', 'OSPF is a link-state IGP'],
  },
  {
    domain: '2', num: '2.2',
    title: 'Given a scenario, configure switching technologies and features',
    bullets: [
      'VLANs, VLAN database, SVI, native VLAN, voice VLAN',
      '802.1Q tagging, link aggregation, speed/duplex',
      'Spanning tree, MTU and jumbo frames',
    ],
    quizFacts: ['802.1Q adds a 4-byte tag to Ethernet frames', 'Native VLAN carries untagged traffic on a trunk', 'STP prevents Layer 2 loops'],
  },
  {
    domain: '2', num: '2.3',
    title: 'Given a scenario, select and configure wireless devices and technologies',
    bullets: [
      'Channels, channel width, non-overlapping channels, 802.11h regulatory impacts',
      '2.4 GHz, 5 GHz, 6 GHz; band steering',
      'SSID/BSSID/ESSID; mesh, ad hoc, infrastructure modes',
      'WPA2/WPA3, guest networks, captive portals, PSK vs Enterprise, antenna types',
    ],
    quizFacts: ['WPA3 improves security over WPA2', 'Enterprise Wi-Fi uses 802.1X authentication', 'Directional antennas focus signal in one area'],
  },
  {
    domain: '2', num: '2.4',
    title: 'Explain important factors of physical installations',
    bullets: [
      'IDF/MDF placement, rack size, port exhaust/intake, patch panels, lockable racks',
      'Power: UPS, PDU, load, voltage',
      'Environmental: humidity, fire suppression, temperature',
    ],
    quizFacts: ['MDF is the main distribution frame for a building', 'UPS provides battery backup during outages', 'Proper cooling prevents thermal shutdown of gear'],
  },
  {
    domain: '3', num: '3.1',
    title: 'Explain the purpose of organizational processes and procedures',
    bullets: [
      'Documentation: logical/physical diagrams, rack/cable maps, IPAM, SLAs, wireless surveys',
      'Lifecycle: EOL/EOS, patches, firmware, decommissioning',
      'Change management and configuration baselines',
    ],
    quizFacts: ['IPAM tracks IP allocation and prevents conflicts', 'Golden configuration is the approved baseline', 'Change management reduces outage risk from unapproved changes'],
  },
  {
    domain: '3', num: '3.2',
    title: 'Given a scenario, use network monitoring technologies',
    bullets: [
      'SNMP traps, MIB, v2c/v3, community strings, authentication',
      'Flow data, packet capture, baseline metrics, syslog/SIEM',
      'Port mirroring, API integration, discovery and traffic analysis',
    ],
    quizFacts: ['SNMPv3 adds encryption and authentication', 'SIEM aggregates logs for correlation', 'Port mirroring copies traffic to an analysis tool'],
  },
  {
    domain: '3', num: '3.3',
    title: 'Explain disaster recovery (DR) concepts',
    bullets: [
      'Metrics: RPO, RTO, MTTR, MTBF',
      'Sites: cold, warm, hot',
      'High availability: active-active vs active-passive',
      'Testing: tabletop exercises, validation tests',
    ],
    quizFacts: ['RTO is maximum acceptable downtime', 'RPO is maximum acceptable data loss window', 'Hot site can take over quickly with minimal data loss'],
  },
  {
    domain: '3', num: '3.4',
    title: 'Given a scenario, implement IPv4 and IPv6 network services',
    bullets: [
      'DHCP scopes, reservations, lease time, relay/IP helper, exclusions',
      'SLAAC for IPv6',
      'DNS record types: A, AAAA, CNAME, MX, TXT, NS, PTR; DNSSEC, DoH, DoT',
      'NTP, PTP, NTS for time synchronization',
    ],
    quizFacts: ['DHCP relay forwards requests across subnets', 'AAAA records map hostnames to IPv6', 'DNSSEC adds cryptographic validation to DNS'],
  },
  {
    domain: '3', num: '3.5',
    title: 'Compare and contrast network access and management methods',
    bullets: [
      'Site-to-site vs client-to-site VPN; clientless; split vs full tunnel',
      'SSH, GUI, API, console access; jump box/host',
      'In-band vs out-of-band management',
    ],
    quizFacts: ['Out-of-band management works when production network is down', 'Split tunnel sends only corporate traffic through VPN', 'Jump hosts reduce direct exposure of core devices'],
  },
  {
    domain: '4', num: '4.1',
    title: 'Explain the importance of basic network security concepts',
    bullets: [
      'Encryption in transit and at rest; PKI and certificates',
      'IAM: MFA, SSO, RADIUS, LDAP, SAML, TACACS+, least privilege, RBAC',
      'Physical security, honeypots, CIA triad, compliance (PCI DSS, GDPR)',
      'Segmentation for IoT/IIoT, SCADA/ICS/OT, guest, BYOD',
    ],
    quizFacts: ['CIA triad: confidentiality, integrity, availability', 'MFA requires two or more verification factors', 'Network segmentation limits lateral movement'],
  },
  {
    domain: '4', num: '4.2',
    title: 'Summarize various types of attacks and their impact to the network',
    bullets: [
      'DoS/DDoS, VLAN hopping, MAC flooding, ARP/DNS poisoning and spoofing',
      'Rogue DHCP/AP, evil twin, on-path attacks',
      'Social engineering: phishing, dumpster diving, shoulder surfing, tailgating',
      'Malware impacts on availability and data integrity',
    ],
    quizFacts: ['ARP spoofing maps attacker MAC to a legitimate IP', 'Evil twin mimics a legitimate wireless SSID', 'On-path attacks intercept traffic between two parties'],
  },
  {
    domain: '4', num: '4.3',
    title: 'Given a scenario, apply network security features, defense techniques, and solutions',
    bullets: [
      'Device hardening: disable unused ports/services, change defaults',
      'NAC: port security, 802.1X, MAC filtering',
      'ACLs, URL/content filtering, trusted vs untrusted zones, screened subnet',
    ],
    quizFacts: ['802.1X controls port access based on identity', 'Screened subnet (DMZ) isolates public-facing services', 'ACLs filter traffic by source, destination, and port'],
  },
  {
    domain: '5', num: '5.1',
    title: 'Explain the troubleshooting methodology',
    bullets: [
      'Identify: gather info, question users, duplicate problem',
      'Theory of probable cause; test theory; plan and implement fix',
      'Verify functionality; document findings throughout',
      'Approaches: top-down/bottom-up OSI, divide and conquer',
    ],
    quizFacts: ['Document at every troubleshooting step', 'Divide and conquer isolates the fault domain quickly', 'Escalate when theory fails after reasonable testing'],
  },
  {
    domain: '5', num: '5.2',
    title: 'Given a scenario, troubleshoot common cabling and physical interface issues',
    bullets: [
      'Cable: wrong category, SM vs MM fiber, STP vs UTP, crosstalk, attenuation, bad termination',
      'Interface counters: CRC, runts, giants, drops; port status errors',
      'PoE budget and standard mismatches; transceiver mismatch/signal strength',
    ],
    quizFacts: ['CRC errors often indicate physical layer problems', 'Attenuation is signal loss over distance', 'PoE budget must cover total device draw on the switch'],
  },
  {
    domain: '5', num: '5.3',
    title: 'Given a scenario, troubleshoot common issues with network services',
    bullets: [
      'Switching: STP loops, root bridge, VLAN misassignment, ACLs',
      'Routing: routing table, default routes',
      'Addressing: pool exhaustion, wrong gateway, duplicate IP, wrong mask',
    ],
    quizFacts: ['Duplicate IP addresses cause intermittent connectivity', 'Wrong default gateway breaks off-subnet traffic', 'STP loops can saturate a broadcast domain'],
  },
  {
    domain: '5', num: '5.4',
    title: 'Given a scenario, troubleshoot common performance issues',
    bullets: [
      'Congestion, bottlenecking, bandwidth/throughput, latency, packet loss, jitter',
      'Wireless: interference, channel overlap, coverage gaps, roaming misconfiguration',
    ],
    quizFacts: ['Jitter affects real-time traffic like VoIP', 'Channel overlap causes wireless interference', 'Bottlenecking limits end-to-end throughput'],
  },
  {
    domain: '5', num: '5.5',
    title: 'Given a scenario, use the appropriate tool or protocol to solve networking issues',
    bullets: [
      'Software: protocol analyzer, ping, traceroute, nslookup, tcpdump, dig, netstat, ipconfig/ifconfig, arp, Nmap',
      'Discovery: LLDP/CDP; speed testers',
      'Hardware: toner, cable tester, taps, Wi-Fi analyzer, visual fault locator',
      'Device commands: show mac-address-table, route, interface, arp, vlan, power',
    ],
    quizFacts: ['ping tests ICMP reachability', 'traceroute shows hop-by-hop path', 'Cable testers verify wire map and continuity'],
  },
];

/**
 * @param {string} text
 */
function escapeHtml(text) {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/**
 * @param {string} domain
 * @param {string} num
 */
function objectiveShortname(domain, num) {
  const parts = num.split('.');
  return `n10009_${domain}_${parts[1]}`;
}

/**
 * @param {string} shortname
 */
function displayCode(shortname) {
  const m = shortname.match(/^n10009_(\d)_(\d+)$/);
  return m ? `N10-009 ${m[1]}.${m[2]}` : shortname;
}

/**
 * @param {typeof OBJECTIVES[0]} obj
 * @param {string} shortname
 */
function lessonHtml(obj, shortname) {
  const code = displayCode(shortname);
  const bullets = obj.bullets.map((b) => `<li>${escapeHtml(b)}</li>`).join('\n');
  let extra = '';
  const snippetPath = path.join(contentDir, 'snippets', 'poe_cat_standards.frag.html');
  if (obj.num === '1.5' && fs.existsSync(snippetPath)) {
    extra = `\n${fs.readFileSync(snippetPath, 'utf8')}\n`;
  }
  return `<div class="ut-lesson-content">
<h3>Exam objective ${escapeHtml(code)}</h3>
<p><strong>${escapeHtml(obj.title)}</strong></p>
<p>This lesson aligns with the CompTIA Network+ N10-009 exam blueprint (Version 4.0). Focus on
how the concept appears in enterprise and cloud networking scenarios.</p>
<h4>Key topics</h4>
<ul>
${bullets}
</ul>
${extra}
<h4>Study approach</h4>
<ul>
<li>Relate the objective to OSI layers, defense-in-depth, and documentation where applicable.</li>
<li>Practice explaining trade-offs (performance vs security, cost, and availability).</li>
<li>Complete the domain knowledge check quiz after this lesson.</li>
</ul>
<h4>Next steps</h4>
<p>Use the AI tutor for scenario-based questions about this topic. The tutor will guide you
Socratically without revealing assessment answers.</p>
</div>
`;
}

/**
 * @param {string} text
 */
function giftEscape(text) {
  return text.replace(/[{}~#=:]/g, ' ').replace(/\s+/g, ' ').trim();
}

/**
 * @param {string} shortname
 * @param {typeof OBJECTIVES[0]} obj
 * @param {number} idx
 * @param {string} fact
 */
function giftQuestion(shortname, obj, idx, fact) {
  const tag = `${shortname}_q${idx}`;
  const wrong = obj.quizFacts.filter((f) => f !== fact).slice(0, 3);
  while (wrong.length < 3) {
    wrong.push(`Unrelated concept for ${obj.num}`);
  }
  const options = [fact, ...wrong].sort(() => Math.random() - 0.5);
  const correctIdx = options.indexOf(fact);
  const letters = ['=', '~', '~', '~'];
  letters[correctIdx] = '=';
  const body = options.map((o, i) => `${letters[i]}${giftEscape(o)}`).join('\n');
  return `::${tag} [${shortname}] ${giftEscape(obj.title)} ::\n${giftEscape(`Which statement best relates to ${obj.num}?`)}\n{\n${body}\n}\n`;
}

fs.mkdirSync(lessonsDir, { recursive: true });

const csvLines = ['cert_shortname,domain_shortname,objective_shortname,objective_fullname'];
const giftBlocks = [];
const manifest = { generated: new Date().toISOString(), questions: 0, byObjective: {} };

for (const obj of OBJECTIVES) {
  const shortname = objectiveShortname(obj.domain, obj.num);
  const domainShort = DOMAIN_MAP[obj.domain];
  csvLines.push(
    `${CERT},${domainShort},${shortname},"${obj.title.replace(/"/g, '""')}"`
  );
  fs.writeFileSync(path.join(lessonsDir, `${shortname}.html`), lessonHtml(obj, shortname));

  obj.quizFacts.forEach((fact, idx) => {
    giftBlocks.push(giftQuestion(shortname, obj, idx + 1, fact));
    manifest.byObjective[shortname] = (manifest.byObjective[shortname] || 0) + 1;
    manifest.questions += 1;
  });
}

fs.writeFileSync(csvPath, `${csvLines.join('\n')}\n`);
fs.writeFileSync(giftPath, `${giftBlocks.join('\n')}\n`);
fs.writeFileSync(manifestPath, `${JSON.stringify(manifest, null, 2)}\n`);

console.log(`objectives=${OBJECTIVES.length}`);
console.log(`lessons_dir=${lessonsDir}`);
console.log(`csv=${csvPath}`);
console.log(`gift_questions=${manifest.questions}`);
