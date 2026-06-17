/**
 * Unique Network+ N10-009 enrichment (exam focus checklists + detailed coverage prose).
 * Wording is original — principles align with CompTIA objectives, not copied from source PDFs.
 */

/** @typedef {{title: string, paragraphs?: string[], bullets?: string[]}} DepthSection */
/** @typedef {{examFocus: string[], depth: DepthSection[]}} ObjectiveEnrichment */

/** @type {Record<string, ObjectiveEnrichment>} */
export const ENRICHMENT = {
  n10009_1_1: {
    examFocus: [
      'Identify which layer a protocol, device, or symptom belongs to before choosing a fix.',
      'Remember PDUs: bits, frames, packets, segments/datagrams, and generic data units.',
      'Switches operate at Layer 2; routers at Layer 3; hubs/repeaters at Layer 1.',
      'Firewalls may filter at Layers 3, 4, or 7 depending on capability.',
      'TCP = reliable transport; UDP = fast, connectionless transport.',
    ],
    depth: [
      {
        title: 'Using the model in troubleshooting',
        paragraphs: [
          'Technicians map symptoms to layers so tests stay focused. Physical issues show up as link lights and CRC errors; routing problems appear once packets leave the local subnet.',
        ],
        bullets: [
          'Bottom-up: start at cabling and NIC link state',
          'Top-down: start at the application or DNS name resolution',
          'Divide and conquer: test the middle (default gateway) first in mixed symptoms',
        ],
      },
      {
        title: 'Encapsulation flow',
        paragraphs: [
          'Each layer adds its own header (and sometimes trailer) as data moves down the stack. On receipt, the peer strips headers upward until the application can read the payload.',
        ],
      },
    ],
  },
  n10009_1_2: {
    examFocus: [
      'Know appliance placement: hub vs switch vs router vs firewall vs proxy.',
      'IDS = alert only; IPS = inline detection and block.',
      'Load balancers distribute sessions; reverse proxies protect servers.',
      'CDN caches content closer to users to reduce latency.',
    ],
    depth: [
      {
        title: 'Choosing the right appliance',
        paragraphs: [
          'Placement determines visibility and control. Passive taps and SPAN ports feed monitoring tools without altering traffic; inline devices can enforce policy but become failure points if not redundant.',
        ],
        bullets: [
          'Forward proxy: client-side filtering and caching',
          'Reverse proxy: server-side termination and shielding',
          'WAF: HTTP/S-specific application protection',
        ],
      },
    ],
  },
  n10009_1_3: {
    examFocus: [
      'SaaS = provider runs everything except your data/config.',
      'PaaS = you bring applications; provider supplies runtime.',
      'IaaS = you manage OS and above; provider supplies VMs and network.',
      'Hybrid links on-premises VLANs to cloud VPCs via VPN or dedicated links.',
    ],
    depth: [
      {
        title: 'Cloud connectivity patterns',
        paragraphs: [
          'Private subnets in a VPC reach the internet through a NAT gateway while public subnets use an internet gateway. Site-to-site VPN extends corporate address space into the cloud control plane.',
        ],
      },
    ],
  },
  n10009_1_4: {
    examFocus: [
      'Associate port numbers with service purpose, not just memorization.',
      'DNS uses UDP for queries and TCP for zone transfers.',
      'SSH (22) replaces cleartext Telnet (23) for administration.',
      'Know unicast vs multicast vs broadcast vs anycast use cases.',
    ],
    depth: [
      {
        title: 'Traffic types in modern networks',
        paragraphs: [
          'Unicast is one-to-one delivery. Multicast conserves bandwidth for streaming to groups. Broadcast reaches all hosts on a subnet but is limited in IPv6. Anycast routes to the nearest instance of a service—common for DNS and CDN edges.',
        ],
      },
      {
        title: 'Protocol selection scenarios',
        bullets: [
          'File transfer and web sessions typically rely on TCP',
          'Real-time voice and video tolerate UDP loss with codecs',
          'ICMP supports diagnostics but is often filtered at the edge',
        ],
      },
    ],
  },
  n10009_1_5: {
    examFocus: [
      'Single-mode fiber for distance; multimode for short campus runs.',
      'Cat6a supports 10G at full 100 m; Cat8 is data-center short reach.',
      'PoE budgets must cover total device draw on the switch.',
      'Match transceiver form factor (SFP, QSFP) to switch and fiber type.',
    ],
    depth: [
      {
        title: 'Copper vs fiber decisions',
        paragraphs: [
          'Copper is cost-effective inside buildings; fiber resists EMI and spans longer distances between IDFs or to ISP handoff. Always verify plenum rating in air-handling spaces.',
        ],
      },
    ],
  },
  n10009_1_6: {
    examFocus: [
      'Star = easy management; mesh = maximum redundancy.',
      'Three-tier: core, distribution, access roles.',
      'Spine-leaf favors east-west data-center traffic.',
      'North-south = client/Internet; east-west = server-to-server.',
    ],
    depth: [
      {
        title: 'Architecture trade-offs',
        paragraphs: [
          'Collapsed core designs reduce cost for small sites but concentrate failure domains. Spine-leaf adds predictable latency for virtualization clusters at the expense of more inter-switch links.',
        ],
      },
    ],
  },
  n10009_1_7: {
    examFocus: [
      'Usable hosts = 2^host_bits − 2 for standard subnets.',
      'APIPA 169.254.x.x indicates DHCP failure.',
      'Private ranges: 10/8, 172.16–31/12, 192.168/16.',
      'VLSM allows different mask lengths within one routing domain.',
    ],
    depth: [
      {
        title: 'Subnetting workflow',
        bullets: [
          'Confirm required host count and subnet count',
          'Choose mask; list network/broadcast for each subnet',
          'Reserve gateway and infrastructure addresses first',
          'Document allocations in IPAM to prevent overlap',
        ],
      },
    ],
  },
  n10009_1_8: {
    examFocus: [
      'SD-WAN abstracts WAN paths and applies policy by application.',
      'Zero trust removes implicit LAN trust; verify every session.',
      'VXLAN extends Layer 2 segments across Layer 3 fabrics.',
      'Dual stack runs IPv4 and IPv6 concurrently during migration.',
    ],
    depth: [
      {
        title: 'Modern design drivers',
        paragraphs: [
          'East-west traffic and remote work pushed architectures toward centralized policy, encrypted overlays, and identity-aware access rather than perimeter-only security.',
        ],
      },
    ],
  },
  n10009_2_1: {
    examFocus: [
      'Lower administrative distance wins when protocols disagree.',
      'Static routes for stub networks; dynamic for large topologies.',
      'PAT overloads many internal hosts onto one public IP.',
      'FHRP provides a virtual default gateway for hosts.',
    ],
    depth: [
      {
        title: 'Route selection in practice',
        paragraphs: [
          'When two routes share the same AD, the router compares metric (hop count, cost, or bandwidth). Floating static routes can back up dynamic protocols if configured with higher AD.',
        ],
      },
    ],
  },
  n10009_2_2: {
    examFocus: [
      '802.1Q tags VLAN ID on trunk links; native VLAN carries untagged frames.',
      'STP prevents Layer 2 loops; know root bridge election.',
      'SVI provides Layer 3 gateway for a VLAN on a multilayer switch.',
      'Link aggregation bundles ports for bandwidth and redundancy.',
    ],
    depth: [
      {
        title: 'Inter-VLAN routing options',
        bullets: [
          'Router-on-a-stick: one trunk to a router with subinterfaces',
          'Layer 3 switch SVIs: preferred in modern campus designs',
          'Verify VLAN membership when hosts cannot reach their gateway',
        ],
      },
    ],
  },
  n10009_2_3: {
    examFocus: [
      '2.4 GHz: channels 1, 6, 11 non-overlapping; 5 GHz offers more channels.',
      'WPA3 preferred over WPA2; never deploy WEP.',
      'Enterprise WLAN uses 802.1X with RADIUS; PSK for small sites.',
      'WLC manages lightweight APs; autonomous APs for small deployments.',
    ],
    depth: [
      {
        title: 'RF planning checklist',
        bullets: [
          'Survey coverage and interference sources before AP placement',
          'Match channel width to density (narrower in crowded halls)',
          'Enable band steering to move capable clients to 5/6 GHz',
          'Validate roaming when VoIP or scanners move between APs',
        ],
      },
      {
        title: 'Security posture',
        paragraphs: [
          'Separate guest SSIDs with client isolation and captive portal policies. Management interfaces should not be reachable from untrusted VLANs.',
        ],
      },
    ],
  },
  n10009_2_4: {
    examFocus: [
      'MDF = main building entry; IDF = floor/zone closets.',
      'UPS runtime must exceed graceful shutdown or failover time.',
      'Maintain temperature and humidity within vendor specs.',
      'Label ports and document cable paths for change control.',
    ],
    depth: [
      {
        title: 'Rack and power planning',
        paragraphs: [
          'Calculate PoE and compute load per PDU circuit before energizing racks. Hot/cold aisle containment improves cooling efficiency in dense rows.',
        ],
      },
    ],
  },
  n10009_3_1: {
    examFocus: [
      'Maintain logical and physical diagrams plus cable maps.',
      'Track EOL/EOS for hardware and firmware baselines.',
      'Change management reduces outage risk from untested edits.',
      'Wireless surveys validate coverage before go-live.',
    ],
    depth: [
      {
        title: 'Operational maturity',
        paragraphs: [
          'Golden configurations and version-controlled templates let teams roll back quickly. Pair documentation updates with every approved change ticket.',
        ],
      },
    ],
  },
  n10009_3_2: {
    examFocus: [
      'SNMPv3 adds auth and privacy; avoid SNMPv1 community strings in production.',
      'Syslog centralizes events; SIEM adds correlation and alerting.',
      'Port mirroring copies traffic to analysis tools.',
      'Baselines help spot anomalies versus normal utilization.',
    ],
    depth: [
      {
        title: 'Observability stack',
        bullets: [
          'Flow records (NetFlow/IPFIX) show top talkers',
          'Synthetic probes validate path quality to SaaS targets',
          'APIs integrate cloud and SD-WAN controllers into NMS',
        ],
      },
    ],
  },
  n10009_3_3: {
    examFocus: [
      'RTO = max tolerable downtime; RPO = max tolerable data loss.',
      'Cold site cheapest/slowest; hot site fastest/most expensive.',
      'Active-active spreads load; active-passive waits on standby.',
      'Tabletop exercises validate plans without full failover.',
    ],
    depth: [
      {
        title: 'Measuring resilience',
        paragraphs: [
          'MTBF and MTTR inform hardware refresh and staffing. Test restores regularly—an untested backup is only a hope, not a plan.',
        ],
      },
    ],
  },
  n10009_3_4: {
    examFocus: [
      'DHCP relay forwards requests across subnets.',
      'DNS A/AAAA for IPv4/IPv6; PTR for reverse lookups.',
      'DNSSEC validates integrity; DoH/DoT encrypt DNS transport.',
      'NTP keeps logs and certificates trustworthy across devices.',
    ],
    depth: [
      {
        title: 'Service dependency chain',
        paragraphs: [
          'Clients often fail “by name” while IP still works—check DNS first. DHCP scope exhaustion presents as new devices never getting addresses.',
        ],
      },
    ],
  },
  n10009_3_5: {
    examFocus: [
      'Split tunnel sends only corporate traffic through VPN.',
      'Full tunnel forces all Internet via corporate security stack.',
      'Out-of-band management uses dedicated management VLAN or serial.',
      'Jump hosts reduce direct exposure of core device CLIs.',
    ],
    depth: [
      {
        title: 'Remote access design',
        paragraphs: [
          'Clientless VPN portals suit vendor access; always combine with MFA and device posture checks where policy requires it.',
        ],
      },
    ],
  },
  n10009_4_1: {
    examFocus: [
      'CIA triad guides control selection for confidentiality, integrity, availability.',
      'MFA and SSO reduce password reuse risk.',
      'Segmentation limits lateral movement after compromise.',
      'Certificates and PKI underpin TLS and 802.1X.',
    ],
    depth: [
      {
        title: 'Defense in depth',
        bullets: [
          'Perimeter filtering plus internal micro-segmentation',
          'Least privilege for admin and service accounts',
          'Physical controls complement logical policies',
        ],
      },
    ],
  },
  n10009_4_2: {
    examFocus: [
      'ARP/DNS poisoning redirect traffic; evil twin mimics legitimate SSIDs.',
      'On-path attacks intercept sessions—encrypt sensitive links.',
      'DDoS targets availability; combine upstream scrubbing and rate limits.',
      'Social engineering bypasses technical controls—train users.',
    ],
    depth: [
      {
        title: 'Attack surface awareness',
        paragraphs: [
          'IoT and BYOD expand entry points. Monitor east-west traffic for beaconing and unexpected DNS destinations, not only north-south Internet flows.',
        ],
      },
    ],
  },
  n10009_4_3: {
    examFocus: [
      '802.1X/NAC validates identity before port forward.',
      'ACLs filter by source, destination, port, and direction.',
      'DMZ/screened subnet isolates public servers.',
      'Disable unused services and change default credentials.',
    ],
    depth: [
      {
        title: 'Hardening workflow',
        bullets: [
          'Inventory open ports and remove unneeded listeners',
          'Apply vendor hardening guides and firmware patches',
          'Log denied ACL hits to refine rules without gaps',
        ],
      },
    ],
  },
  n10009_5_1: {
    examFocus: [
      'Follow all seven steps in order; documentation is always last.',
      '“What should they do NEXT?” questions test step sequence.',
      'Establish theory before making changes; plan rollback.',
      'Escalate when scope or access exceeds your role.',
    ],
    depth: [
      {
        title: 'Scenario discipline',
        paragraphs: [
          'Gather symptoms and recent changes before touching configuration. Duplicate the problem in a lab when possible so fixes are verified, not guessed.',
        ],
      },
    ],
  },
  n10009_5_2: {
    examFocus: [
      'CRC errors often indicate bad cables, duplex mismatch, or damaged NICs.',
      'Verify PoE class and total switch budget for powered devices.',
      'SM vs MM fiber mismatch prevents link entirely.',
      'Interface counters (runts, giants) point to MTU or duplex issues.',
    ],
    depth: [
      {
        title: 'Layer 1 verification',
        bullets: [
          'Re-terminate suspect pairs; test with certifier when available',
          'Confirm auto-negotiation result or force consistent speed/duplex',
          'Compare SFP wavelength and fiber type on both ends',
        ],
      },
    ],
  },
  n10009_5_3: {
    examFocus: [
      'Duplicate IP causes intermittent connectivity—check ARP and DHCP logs.',
      'Wrong VLAN or trunk native VLAN breaks subnet reachability.',
      'Missing default gateway affects off-subnet traffic only.',
      'STP loops saturate links with broadcasts—look for flapping MACs.',
    ],
    depth: [
      {
        title: 'Systematic isolation',
        paragraphs: [
          'Ping gateway, then remote subnet, then DNS name. Each failure narrows whether the fault is local L2, routing, or name services.',
        ],
      },
    ],
  },
  n10009_5_4: {
    examFocus: [
      'Distinguish bandwidth (capacity) from throughput (achieved rate).',
      'Latency and jitter impact real-time apps; bufferbloat adds delay.',
      'Wireless: check overlap, interference, and AP placement.',
      'QoS marks delay-sensitive traffic when congestion appears.',
    ],
    depth: [
      {
        title: 'Performance baselines',
        paragraphs: [
          'Capture utilization during peak hours before upgrading circuits. A single oversubscribed uplink can mimic “slow servers” elsewhere in the path.',
        ],
      },
    ],
  },
  n10009_5_5: {
    examFocus: [
      'ping/traceroute for reachability; nslookup/dig for DNS.',
      'tcpdump/Wireshark for packet-level evidence.',
      'Cable certifiers and toners for physical layer.',
      'show route / show interface on network OS for local state.',
    ],
    depth: [
      {
        title: 'Tool selection matrix',
        bullets: [
          'Software tools: fast, repeatable, remote-friendly',
          'Hardware tools: authoritative for cabling and RF surveys',
          'Document command output in tickets for escalation handoffs',
        ],
      },
    ],
  },
};

/**
 * @param {string} shortname
 * @returns {ObjectiveEnrichment|null}
 */
export function getEnrichment(shortname) {
  return ENRICHMENT[shortname] || null;
}

/**
 * @param {string} text
 */
function esc(text) {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/**
 * @param {DepthSection} section
 */
function sectionToHtml(section) {
  const parts = [`<h5>${esc(section.title)}</h5>`];
  if (section.paragraphs?.length) {
    parts.push(...section.paragraphs.map((p) => `<p>${esc(p)}</p>`));
  }
  if (section.bullets?.length) {
    parts.push(`<ul>\n${section.bullets.map((b) => `<li>${esc(b)}</li>`).join('\n')}\n</ul>`);
  }
  return parts.join('\n');
}

/**
 * @param {string} shortname
 * @param {string} title
 * @returns {string}
 */
export function buildSupplementHtml(shortname, title) {
  const data = getEnrichment(shortname);
  if (!data?.examFocus?.length) {
    return '';
  }
  const items = data.examFocus.map((b) => `<li>${esc(b)}</li>`).join('\n');
  return [
    '<div class="ut-lesson-supplement">',
    '<h4>Exam focus</h4>',
    `<p>${esc(title)}</p>`,
    `<ul>\n${items}\n</ul>`,
    '</div>',
    '',
  ].join('\n');
}

/**
 * @param {string} shortname
 * @param {string} title
 * @returns {string}
 */
export function buildDepthHtml(shortname, title) {
  const data = getEnrichment(shortname);
  if (!data?.depth?.length) {
    return '';
  }
  const body = data.depth.map(sectionToHtml).join('\n');
  return [
    '<div class="ut-lesson-depth">',
    '<h4>Detailed coverage</h4>',
    `<p><strong>${esc(title)}</strong></p>`,
    body,
    '</div>',
    '',
  ].join('\n');
}
