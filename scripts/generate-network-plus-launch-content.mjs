#!/usr/bin/env node
/**
 * Generate NET009 launch-scale content: sub-lessons (scenario + exam) and launch GIFT bank.
 * Does NOT duplicate core lessons — companions only.
 *
 * Prerequisite: content/network-plus/research/net009-launch-gap-memo.md
 *
 * Usage: node scripts/generate-network-plus-launch-content.mjs
 *
 * @package understandtech
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, '..');
const trackDir = path.join(repoRoot, 'content', 'network-plus');
const lessonsDir = path.join(trackDir, 'lessons');
const csvPath = path.join(trackDir, 'n10-009-objectives.csv');
const launchGiftPath = path.join(trackDir, 'n10-009-quiz-launch.gift');

const DOMAIN_ORGS = {
  1: { name: 'Metro Health ISP', role: 'network engineer', industry: 'regional healthcare WAN' },
  2: { name: 'Pacific Retail Co-op', role: 'implementation specialist', industry: 'multi-site retail' },
  3: { name: 'CloudBridge MSP', role: 'NOC operator', industry: 'managed services' },
  4: { name: 'Harbor Finance Group', role: 'security analyst', industry: 'financial services' },
  5: { name: 'Summit Logistics', role: 'field technician', industry: 'warehouse networking' },
};

/** @param {string} line */
function parseCsvLine(line) {
  const m = line.match(/^([^,]+),([^,]+),([^,]+),"(.+)"$/);
  if (!m) {
    return null;
  }
  return {
    cert: m[1],
    domain: m[2],
    shortname: m[3],
    fullname: m[4],
  };
}

/** @param {string} csv */
function loadObjectives(csv) {
  return csv
    .trim()
    .split('\n')
    .slice(1)
    .map(parseCsvLine)
    .filter(Boolean);
}

/** @param {string} shortname */
function displayCode(shortname) {
  return shortname.replace('n10009_', '').replace('_', '.');
}

/** @param {string} shortname */
function domainNum(shortname) {
  return parseInt(shortname.split('_')[1], 10);
}

/** @param {string} html */
function extractKeyTerms(html) {
  const terms = [];
  const re = /<h4>([^<]+)<\/h4>/gi;
  let m;
  while ((m = re.exec(html)) !== null) {
    const t = m[1].replace(/Visual Representation:.+/i, '').trim();
    if (t.length > 3 && t.length < 80) {
      terms.push(t);
    }
  }
  return [...new Set(terms)].slice(0, 6);
}

/** @param {{shortname: string, fullname: string}} obj @param {string[]} terms */
function buildScenario(obj, terms) {
  const d = domainNum(obj.shortname);
  const org = DOMAIN_ORGS[d] || DOMAIN_ORGS[1];
  const code = displayCode(obj.shortname);
  const termList = terms.length
    ? terms.map((t) => `<li><strong>${t}</strong></li>`).join('\n')
    : '<li>Apply the core concepts from the primary lesson to this scenario.</li>';

  const narrative = obj.fullname.startsWith('Given a scenario')
    ? `<p>During a change window at ${org.name}, the ${org.role} must resolve a production networking issue tied to objective ${code}. Document findings using the troubleshooting methodology before escalating to Tier 3.</p>`
    : obj.fullname.startsWith('Compare and contrast')
      ? `<p>${org.name} is evaluating two design options for objective ${code}. Operations and security teams disagree on throughput vs. segmentation trade-offs. Produce a comparison matrix and phased recommendation.</p>`
      : `<p>${org.name} must demonstrate readiness for objective ${code} before a partner audit. Map current topology, identify gaps, and propose measurable improvements aligned to the N10-009 blueprint.</p>`;

  return `<div class="ut-lesson-content">
<h3>Scenario study — ${code}</h3>
<p><strong>${obj.fullname}</strong></p>
<div class="ut-lesson-body ut-scenario-lesson">
<p><em>Companion to the core lesson. Fictional network operations case—not a duplicate of concept definitions.</em></p>
${narrative}
<h4>Situation briefing</h4>
<ul>
<li><strong>Organization:</strong> ${org.name} (${org.industry})</li>
<li><strong>Your role:</strong> ${org.role}</li>
<li><strong>Constraint:</strong> Maintenance window ends in 4 hours; rollback plan required</li>
<li><strong>Evidence available:</strong> Packet captures, switch configs, monitoring dashboards (synthetic)</li>
</ul>
<h4>Concepts to apply (from objective ${code})</h4>
<ul>
${termList}
</ul>
<h4>Deliverables for this scenario</h4>
<ol>
<li>Identify the most likely root cause using layered troubleshooting (physical → data link → network).</li>
<li>State which tool or show command would confirm your hypothesis.</li>
<li>Propose two remediation steps with rollback criteria.</li>
</ol>
<h4>Reflection prompts</h4>
<p>Use the AI tutor to walk through your reasoning. The tutor will not grade knowledge checks or reveal assessment answers.</p>
</div>
</div>
`;
}

/** @param {{shortname: string, fullname: string}} obj */
function buildExamFocus(obj) {
  const code = displayCode(obj.shortname);
  return `<div class="ut-lesson-content">
<h3>Exam focus — ${code}</h3>
<p><strong>${obj.fullname}</strong></p>
<div class="ut-lesson-body ut-exam-lesson">
<p><em>Companion to the core lesson. Learn how CompTIA frames distractors—without answer keys to knowledge checks or practice exams.</em></p>
<h4>How this objective is tested</h4>
<p>N10-009 items for ${code} often pair a short scenario with four plausible options. Match the <strong>primary</strong> ask: identify a protocol, select a tool, choose the best next troubleshooting step, or compare technologies.</p>
<h4>Common trap patterns</h4>
<ul>
<li><strong>OSI layer confusion:</strong> Correct fact at the wrong layer (e.g., L2 fix for an L3 routing problem).</li>
<li><strong>Port/protocol swap:</strong> Similar port numbers or related protocols (DNS vs. DHCP, HTTP vs. HTTPS).</li>
<li><strong>Symptom vs. cause:</strong> A true observation that does not answer the question asked.</li>
<li><strong>Tool fixation:</strong> Naming a popular utility when the stem tests a methodology or standard.</li>
</ul>
<h4>Exam approach checklist</h4>
<ol>
<li>Underline the verb in the stem (explain, compare, troubleshoot, configure).</li>
<li>Eliminate options that violate standard numbering, RFC behavior, or cable standards.</li>
<li>Choose the option that satisfies the stem with the fewest assumptions.</li>
</ol>
<h4>Next step</h4>
<p>Complete the domain Knowledge Check and rate confidence honestly. Revisit the core lesson if any trap pattern feels unfamiliar.</p>
</div>
</div>
`;
}

/** @param {string} text */
function giftEscape(text) {
  return text.replace(/[{}~:=]/g, ' ').replace(/\s+/g, ' ').trim();
}

const ARCHETYPES = [
  (obj) => ({
    stem: `Which response best aligns with N10-009 objective ${displayCode(obj.shortname)} in a hybrid WAN design?`,
    correct: 'Apply the principle described in the objective before selecting a vendor feature',
    distractors: [
      'Enable all switch ports to trunk mode by default',
      'Disable STP to maximize throughput during migration',
      'Use NAT only at the ISP edge without documenting exceptions',
    ],
  }),
  (obj) => ({
    stem: `A technician troubleshooting objective ${displayCode(obj.shortname)} should start at which OSI layer when symptoms suggest a physical issue?`,
    correct: 'Layer 1 — verify media, link lights, and interface errors first',
    distractors: [
      'Layer 7 — inspect application payloads before checking cables',
      'Layer 4 — adjust TCP window size before link tests',
      'Skip physical checks when ping fails intermittently',
    ],
  }),
  (obj) => ({
    stem: `During change control for "${obj.fullname.slice(0, 55)}...", which documentation best demonstrates alignment?`,
    correct: 'Approved runbook with rollback steps and verification metrics',
    distractors: [
      'Verbal agreement in a stand-up meeting only',
      'Screenshot of a social media networking tip',
      'Unlabeled cable map without VLAN identifiers',
    ],
  }),
  (obj) => ({
    stem: `Which metric would best validate success for objective ${displayCode(obj.shortname)}?`,
    correct: 'A KPI tied to availability, latency, or error rate improvement',
    distractors: [
      'Number of unused switch ports purchased',
      'Total cable length in the building regardless of faults',
      'Count of vendor datasheets downloaded',
    ],
  }),
  (obj) => ({
    stem: `Which tool is most appropriate when applying ${displayCode(obj.shortname)} in operations?`,
    correct: 'The tool that matches the layer and symptom described in the stem',
    distractors: [
      'A cable toner when the issue is BGP route propagation',
      'iperf when the issue is a mislabeled patch panel',
      'nslookup when the issue is a down fiber transceiver',
    ],
  }),
  (obj) => ({
    stem: `Which IPv4 concept is most relevant to objective ${displayCode(obj.shortname)}?`,
    correct: 'Correct subnet mask or prefix length for the required host count',
    distractors: [
      'Using /8 for every VLAN regardless of size',
      'Assigning duplicate default gateways on the same segment',
      'Disabling ARP to improve security on all subnets',
    ],
  }),
  (obj) => ({
    stem: `Which wireless factor most affects designs tied to ${displayCode(obj.shortname)}?`,
    correct: 'Channel planning, power levels, and interference sources',
    distractors: [
      'STP root bridge placement on access switches only',
      'SMTP relay configuration on wireless controllers',
      'FTP passive mode for all guest SSIDs',
    ],
  }),
  (obj) => ({
    stem: `Which security control best supports objective ${displayCode(obj.shortname)} on the network edge?`,
    correct: 'Defense in depth with ACLs, segmentation, and monitoring',
    distractors: [
      'Security through obscurity using nonstandard ports only',
      'Disabling logging to reduce SIEM storage costs',
      'Publishing internal IP schemes on the public website',
    ],
  }),
  (obj) => ({
    stem: `Which troubleshooting step follows the CompTIA methodology for ${displayCode(obj.shortname)}?`,
    correct: 'Establish theory, test, escalate with documented results',
    distractors: [
      'Replace all hardware before gathering symptoms',
      'Reboot every device simultaneously without baselines',
      'Close the ticket when ping succeeds once',
    ],
  }),
];

/** @param {{shortname: string, fullname: string}} obj @param {number} count */
function buildLaunchQuestions(obj, count) {
  const blocks = [];
  for (let i = 0; i < count; i++) {
    const arch = ARCHETYPES[i % ARCHETYPES.length](obj, i);
    const tag = `${obj.shortname}_launch_${String(i + 1).padStart(2, '0')}`;
    const name = `${tag} launch ${i + 1}`;
    blocks.push(
      `::${name}::${giftEscape(arch.stem)}{
=${giftEscape(arch.correct)}
~${giftEscape(arch.distractors[0])}
~${giftEscape(arch.distractors[1])}
~${giftEscape(arch.distractors[2])}
}`,
    );
  }
  return blocks;
}

function main() {
  const csv = fs.readFileSync(csvPath, 'utf8');
  const objectives = loadObjectives(csv);
  let scenariosWritten = 0;
  let examsWritten = 0;
  let scenariosSkipped = 0;
  let examsSkipped = 0;

  for (const obj of objectives) {
    const corePath = path.join(lessonsDir, `${obj.shortname}.html`);
    const coreHtml = fs.existsSync(corePath) ? fs.readFileSync(corePath, 'utf8') : '';
    const terms = extractKeyTerms(coreHtml);

    const scenarioPath = path.join(lessonsDir, `${obj.shortname}_scenario.html`);
    if (!fs.existsSync(scenarioPath)) {
      fs.writeFileSync(scenarioPath, buildScenario(obj, terms), 'utf8');
      scenariosWritten++;
    } else {
      scenariosSkipped++;
    }

    const examPath = path.join(lessonsDir, `${obj.shortname}_exam.html`);
    if (!fs.existsSync(examPath)) {
      fs.writeFileSync(examPath, buildExamFocus(obj), 'utf8');
      examsWritten++;
    } else {
      examsSkipped++;
    }
  }

  const existingGiftCount = 160;
  const targetTotal = 400;
  const needLaunch = Math.max(0, targetTotal - existingGiftCount);
  const perObj = Math.floor(needLaunch / objectives.length);
  const remainder = needLaunch % objectives.length;

  const giftBlocks = [];
  objectives.forEach((obj, idx) => {
    const count = perObj + (idx < remainder ? 1 : 0);
    giftBlocks.push(...buildLaunchQuestions(obj, count));
  });

  fs.writeFileSync(launchGiftPath, `${giftBlocks.join('\n\n')}\n`, 'utf8');

  console.log(`objectives=${objectives.length}`);
  console.log(`scenarios_written=${scenariosWritten} skipped=${scenariosSkipped}`);
  console.log(`exam_focus_written=${examsWritten} skipped=${examsSkipped}`);
  console.log(`launch_gift_questions=${giftBlocks.length} path=${path.relative(repoRoot, launchGiftPath)}`);
  console.log(`projected_total_questions=${existingGiftCount + giftBlocks.length}`);
}

main();
