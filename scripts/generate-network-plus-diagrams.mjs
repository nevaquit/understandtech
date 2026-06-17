#!/usr/bin/env node
/**
 * Generate Network+ lesson diagram HTML fragments (SEC701-style visual blocks).
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { contentDir } from './lib/network-plus-orange-sections.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const outDir = path.join(contentDir, 'diagrams');

/** @type {Record<string, string>} */
const DIAGRAMS = {
  n10009_1_1: `<h4 class="ut-visual-representation">Visual Representation: OSI Reference Model</h4>
<div class="ut-lesson-diagram ut-infographic">
<div class="diagram-title">Seven-layer OSI stack (Physical → Application)</div>
<div class="flow-diagram">
<div class="flow-step"><strong>7 Application</strong><br>HTTP, DNS, SMTP</div>
<div class="flow-arrow">↑</div>
<div class="flow-step"><strong>6 Presentation</strong><br>SSL/TLS, encoding</div>
<div class="flow-arrow">↑</div>
<div class="flow-step"><strong>5 Session</strong><br>Dialog control</div>
<div class="flow-arrow">↑</div>
<div class="flow-step"><strong>4 Transport</strong><br>TCP, UDP, ports</div>
<div class="flow-arrow">↑</div>
<div class="flow-step"><strong>3 Network</strong><br>IP, routing, routers</div>
<div class="flow-arrow">↑</div>
<div class="flow-step"><strong>2 Data Link</strong><br>Frames, MAC, switches</div>
<div class="flow-arrow">↑</div>
<div class="flow-step"><strong>1 Physical</strong><br>Bits, cables, NICs</div>
</div>
</div>`,

  n10009_1_2: `<h4 class="ut-visual-representation">Visual Representation: Network Appliances by Layer</h4>
<div class="ut-lesson-diagram">
<div class="diagram-title">Common devices and their primary OSI layers</div>
<div class="concept-grid">
<div class="concept-item"><h4>Layer 1–2</h4><p>Hub (L1), Switch (L2), WAP (L1/L2)</p></div>
<div class="concept-item"><h4>Layer 3</h4><p>Router, basic firewall, IDS/IPS</p></div>
<div class="concept-item"><h4>Layer 4–7</h4><p>Load balancer, proxy, NGFW, WAF</p></div>
<div class="concept-item"><h4>Services</h4><p>CDN, VPN concentrator, NAS/SAN</p></div>
</div>
</div>`,

  n10009_1_3: `<h4 class="ut-visual-representation">Visual Representation: Cloud Service Models</h4>
<div class="ut-lesson-diagram">
<div class="diagram-title">SaaS vs PaaS vs IaaS responsibility split</div>
<div class="concept-grid">
<div class="concept-item"><h4>SaaS</h4><p>You manage: data &amp; access. Provider: everything else.</p></div>
<div class="concept-item"><h4>PaaS</h4><p>You manage: apps &amp; data. Provider: runtime, OS, hardware.</p></div>
<div class="concept-item"><h4>IaaS</h4><p>You manage: OS, apps, data. Provider: virtualization &amp; hardware.</p></div>
</div>
</div>`,

  n10009_1_6: `<h4 class="ut-visual-representation">Visual Representation: Enterprise Architectures</h4>
<div class="ut-lesson-diagram">
<div class="diagram-title">Three-tier vs spine-leaf traffic patterns</div>
<div class="concept-grid">
<div class="concept-item"><h4>Three-tier</h4><p>Core → Distribution → Access. Classic campus design.</p></div>
<div class="concept-item"><h4>Spine-leaf</h4><p>Every leaf connects to every spine. Optimized for east-west traffic.</p></div>
<div class="concept-item"><h4>North-south</h4><p>Client ↔ internet / data center edge.</p></div>
<div class="concept-item"><h4>East-west</h4><p>Server ↔ server inside the data center.</p></div>
</div>
</div>`,

  n10009_1_7: `<h4 class="ut-visual-representation">Visual Representation: IPv4 Subnetting</h4>
<div class="ut-lesson-diagram">
<div class="diagram-title">CIDR block sizes (common exam values)</div>
<div class="concept-grid">
<div class="concept-item"><h4>/24</h4><p>254 usable hosts — typical LAN</p></div>
<div class="concept-item"><h4>/26</h4><p>62 usable hosts</p></div>
<div class="concept-item"><h4>/27</h4><p>30 usable hosts</p></div>
<div class="concept-item"><h4>/30</h4><p>2 usable hosts — point-to-point links</p></div>
</div>
<p><strong>Formula:</strong> Usable hosts = 2<sup>host bits</sup> − 2</p>
</div>`,

  n10009_2_1: `<h4 class="ut-visual-representation">Visual Representation: Route Selection</h4>
<div class="ut-lesson-diagram">
<div class="diagram-title">Administrative distance (lower = preferred)</div>
<div class="flow-diagram">
<div class="flow-step"><strong>0</strong> Connected</div>
<div class="flow-step"><strong>1</strong> Static</div>
<div class="flow-step"><strong>20</strong> BGP (eBGP)</div>
<div class="flow-step"><strong>90</strong> EIGRP</div>
<div class="flow-step"><strong>110</strong> OSPF</div>
<div class="flow-step"><strong>120</strong> RIP</div>
</div>
</div>`,

  n10009_2_2: `<h4 class="ut-visual-representation">Visual Representation: VLAN Trunking</h4>
<div class="ut-lesson-diagram">
<div class="diagram-title">802.1Q trunk between switch and router-on-a-stick</div>
<div class="flow-diagram">
<div class="flow-step"><strong>VLAN 10</strong><br>Subinterface .10</div>
<div class="flow-arrow">↔</div>
<div class="flow-step"><strong>Trunk</strong><br>802.1Q tags</div>
<div class="flow-arrow">↔</div>
<div class="flow-step"><strong>VLAN 20</strong><br>Subinterface .20</div>
</div>
</div>`,

  n10009_3_3: `<h4 class="ut-visual-representation">Visual Representation: Disaster Recovery Sites</h4>
<div class="ut-lesson-diagram">
<div class="diagram-title">Cold, warm, and hot recovery sites</div>
<div class="concept-grid">
<div class="concept-item"><h4>Cold site</h4><p>Space only — longest RTO, lowest cost.</p></div>
<div class="concept-item"><h4>Warm site</h4><p>Partial hardware — moderate RTO/RPO.</p></div>
<div class="concept-item"><h4>Hot site</h4><p>Fully operational mirror — fastest failover.</p></div>
</div>
</div>`,

  n10009_4_1: `<h4 class="ut-visual-representation">Visual Representation: Defense in Depth</h4>
<div class="ut-lesson-diagram">
<div class="diagram-title">Layered network security controls</div>
<div class="flow-diagram">
<div class="flow-step"><strong>Perimeter</strong><br>Firewall, WAF</div>
<div class="flow-arrow">→</div>
<div class="flow-step"><strong>Network</strong><br>IDS/IPS, NAC</div>
<div class="flow-arrow">→</div>
<div class="flow-step"><strong>Endpoint</strong><br>802.1X, hardening</div>
<div class="flow-arrow">→</div>
<div class="flow-step"><strong>Data</strong><br>Encryption, PKI</div>
</div>
</div>`,

  n10009_5_1: `<h4 class="ut-visual-representation">Visual Representation: CompTIA Troubleshooting Methodology</h4>
<div class="ut-lesson-diagram ut-infographic">
<div class="diagram-title">Seven-step troubleshooting process</div>
<div class="flow-diagram">
<div class="flow-step"><strong>1</strong> Identify the problem</div>
<div class="flow-step"><strong>2</strong> Establish theory of probable cause</div>
<div class="flow-step"><strong>3</strong> Test the theory</div>
<div class="flow-step"><strong>4</strong> Establish a plan of action</div>
<div class="flow-step"><strong>5</strong> Implement or escalate</div>
<div class="flow-step"><strong>6</strong> Verify full functionality</div>
<div class="flow-step"><strong>7</strong> Document findings</div>
</div>
</div>`,

  n10009_5_5: `<h4 class="ut-visual-representation">Visual Representation: Troubleshooting Tools</h4>
<div class="ut-lesson-diagram">
<div class="diagram-title">Software vs hardware tools</div>
<div class="concept-grid">
<div class="concept-item"><h4>Layer 3 reachability</h4><p>ping, traceroute, pathping</p></div>
<div class="concept-item"><h4>Name resolution</h4><p>nslookup, dig</p></div>
<div class="concept-item"><h4>Capture &amp; analyze</h4><p>tcpdump, Wireshark (protocol analyzer)</p></div>
<div class="concept-item"><h4>Physical layer</h4><p>cable tester, toner, VFL, Wi-Fi analyzer</p></div>
</div>
</div>`,
};

fs.mkdirSync(outDir, { recursive: true });
let written = 0;
for (const [shortname, html] of Object.entries(DIAGRAMS)) {
  fs.writeFileSync(path.join(outDir, `${shortname}.html`), `${html.trim()}\n`);
  written += 1;
}
console.log(`diagrams_written=${written}`);
console.log(`diagrams_dir=${outDir}`);
