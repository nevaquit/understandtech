#!/usr/bin/env node
/**
 * Generate SEC701 launch-scale content: sub-lessons (scenario + exam) and launch GIFT bank.
 * Does NOT duplicate core lessons — companions only.
 *
 * Prerequisite: content/security-plus/research/sec701-launch-gap-memo.md
 *
 * Usage: node scripts/generate-security-plus-launch-content.mjs
 *
 * @package understandtech
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, '..');
const trackDir = path.join(repoRoot, 'content', 'security-plus');
const lessonsDir = path.join(trackDir, 'lessons');
const csvPath = path.join(trackDir, 'sy0-701-objectives.csv');
const launchGiftPath = path.join(trackDir, 'sy0-701-quiz-launch.gift');

const DOMAIN_ORGS = {
  1: { name: 'Northwind Credit Union', role: 'security architect', industry: 'financial services' },
  2: { name: 'Contoso Manufacturing', role: 'SOC analyst', industry: 'industrial IoT' },
  3: { name: 'Fabrikam Health', role: 'cloud security engineer', industry: 'healthcare SaaS' },
  4: { name: 'Adventure Works', role: 'SecOps lead', industry: 'e-commerce' },
  5: { name: 'Tailspin Toys', role: 'GRC analyst', industry: 'retail' },
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
  return shortname.replace('sy701_', '').replace('_', '.');
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
    ? `<p>During a quarterly review at ${org.name}, leadership asks the ${org.role} to respond to a realistic ${org.industry} incident tied to objective ${code}. Your analysis should connect observable events to the correct security principle—without skipping change-control or evidence preservation steps.</p>`
    : obj.fullname.startsWith('Compare and contrast')
      ? `<p>${org.name} is evaluating two competing approaches for objective ${code}. Stakeholders from IT, risk, and operations each favor a different design. Document trade-offs using the comparison framework from the primary lesson, then recommend a phased rollout.</p>`
      : `<p>${org.name} must demonstrate compliance with objective ${code} before a partner audit. As the ${org.role}, you are asked to map policy gaps, identify missing controls, and propose measurable improvements aligned to the blueprint.</p>`;

  return `<div class="ut-lesson-content">
<h3>Scenario study — ${code}</h3>
<p><strong>${obj.fullname}</strong></p>
<div class="ut-lesson-body ut-scenario-lesson">
<p><em>Companion to the core lesson. This page is a fictional case study—not a duplicate of concept definitions.</em></p>
${narrative}
<h4>Situation briefing</h4>
<ul>
<li><strong>Organization:</strong> ${org.name} (${org.industry})</li>
<li><strong>Your role:</strong> ${org.role}</li>
<li><strong>Constraint:</strong> 48-hour audit window; production changes require CAB approval</li>
<li><strong>Evidence available:</strong> Ticket queue, configuration exports, and interview notes (synthetic)</li>
</ul>
<h4>Concepts to apply (from objective ${code})</h4>
<ul>
${termList}
</ul>
<h4>Deliverables for this scenario</h4>
<ol>
<li>Summarize the primary risk in business terms (availability, integrity, or confidentiality).</li>
<li>Identify which control types (preventive, detective, corrective, deterrent, compensating) apply.</li>
<li>Propose two prioritized actions with owners and success metrics.</li>
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
<p>SY0-701 items for ${code} often pair a short scenario with four plausible options. The correct choice is the one that matches the <strong>primary</strong> ask in the stem (definition vs. best next step vs. control type).</p>
<h4>Common trap patterns</h4>
<ul>
<li><strong>Scope shift:</strong> A true statement about a related control that does not answer the question asked.</li>
<li><strong>Phase confusion:</strong> Mixing preventive, detective, and corrective responses for the same incident phase.</li>
<li><strong>Absolute language:</strong> Options with “always/never” unless the objective explicitly requires a hard rule.</li>
<li><strong>Tool fixation:</strong> Naming a popular product when the question tests a process or principle.</li>
</ul>
<h4>Distractor families to compare (not answers)</h4>
<table class="ut-exam-table">
<thead><tr><th>Family</th><th>When it tempts you</th><th>Recheck</th></tr></thead>
<tbody>
<tr><td>Related but wrong phase</td><td>Incident already detected</td><td>Re-read whether stem asks for containment vs. eradication</td></tr>
<tr><td>Compliance vs. security</td><td>Audit language in stem</td><td>Separate legal requirement from technical control</td></tr>
<tr><td>Speed vs. evidence</td><td>Time pressure in scenario</td><td>Preservation and logging may precede recovery</td></tr>
</tbody>
</table>
<h4>Exam approach checklist</h4>
<ol>
<li>Underline the verb in the stem (identify, recommend, compare, explain).</li>
<li>Eliminate options that violate CIA, least privilege, or separation of duties.</li>
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

/** Question archetypes per objective — original stems, not copies of existing GIFT. */
const ARCHETYPES = [
  (obj, i) => ({
    stem: `Which response best aligns with SY0-701 objective ${displayCode(obj.shortname)} when prioritizing risk in a hybrid environment?`,
    correct: 'Apply the principle described in the objective before selecting a tool',
    distractors: [
      'Deploy the newest commercial product regardless of integration cost',
      'Disable logging temporarily to improve performance',
      'Transfer all accountability to the cloud provider under shared responsibility',
    ],
  }),
  (obj, i) => ({
    stem: `A stakeholder asks how objective ${displayCode(obj.shortname)} relates to defense-in-depth. What is the best explanation?`,
    correct: 'It adds a layer that supports confidentiality, integrity, or availability',
    distractors: [
      'It replaces the need for physical controls entirely',
      'It applies only to on-premises data centers',
      'It eliminates the need for user awareness training',
    ],
  }),
  (obj, i) => ({
    stem: `During an audit finding for "${obj.fullname.slice(0, 60)}...", which evidence best demonstrates alignment?`,
    correct: 'Documented procedure mapped to the objective with measurable outcomes',
    distractors: [
      'Verbal assurance from a single engineer',
      'Marketing brochure describing security features',
      'Unverified screenshot without chain of custody',
    ],
  }),
  (obj, i) => ({
    stem: `Which metric would best validate success for objective ${displayCode(obj.shortname)}?`,
    correct: 'A KPI tied to reduced risk or improved control effectiveness',
    distractors: [
      'Number of security tools purchased this quarter',
      'Total blocked emails regardless of false positives',
      'Count of policy documents without employee attestation',
    ],
  }),
  (obj, i) => ({
    stem: `Which trade-off is most relevant when implementing controls for ${displayCode(obj.shortname)}?`,
    correct: 'Balancing security improvement with usability and operational cost',
    distractors: [
      'Maximizing obscurity as the primary control strategy',
      'Removing multi-factor authentication for executive accounts',
      'Avoiding change management to deploy fixes faster',
    ],
  }),
  (obj, i) => ({
    stem: `Which activity belongs in the corrective phase for issues tied to ${displayCode(obj.shortname)}?`,
    correct: 'Remediate root cause and verify control effectiveness after an event',
    distractors: [
      'Install deterrent signage only',
      'Archive logs without analysis',
      'Disable accounts proactively before investigation',
    ],
  }),
  (obj, i) => ({
    stem: `Which data source best supports decisions required by objective ${displayCode(obj.shortname)}?`,
    correct: 'Authoritative configuration and telemetry correlated to the asset',
    distractors: [
      'Anonymous social media posts',
      'Unauthenticated third-party forum advice',
      'Outdated network diagram with no owner',
    ],
  }),
  (obj, i) => ({
    stem: `Which role is typically accountable for outcomes described in ${displayCode(obj.shortname)}?`,
    correct: 'The role defined in policy with documented responsibilities',
    distractors: [
      'Any user with local administrator rights',
      'External penetration testers permanently',
      'Vendors without contractual security clauses',
    ],
  }),
  (obj, i) => ({
    stem: `Which NIST CSF function most closely maps to ${displayCode(obj.shortname)}?`,
    correct: 'The function that matches identify, protect, detect, respond, or recover context',
    distractors: [
      'Only the recover function for every objective',
      'Functions are not applicable to enterprise security',
      'A single function eliminates the need for governance',
    ],
  }),
  (obj, i) => ({
    stem: `Which scenario best requires applying ${displayCode(obj.shortname)}?`,
    correct: 'A realistic enterprise condition described in the objective verb phrase',
    distractors: [
      'A consumer mobile game outage unrelated to security',
      'Replacing keyboards for aesthetic reasons',
      'Publishing press releases about a product launch',
    ],
  }),
  (obj, i) => ({
    stem: `Which documentation practice supports auditability for ${displayCode(obj.shortname)}?`,
    correct: 'Version-controlled records with approval and retention rules',
    distractors: [
      'Ephemeral chat messages as the sole record',
      'Shared passwords stored in plain text',
      'Ad-hoc changes without rollback plans',
    ],
  }),
  (obj, i) => ({
    stem: `Which communication is appropriate when coordinating ${displayCode(obj.shortname)} across teams?`,
    correct: 'Clear escalation paths defined in incident and change procedures',
    distractors: [
      'Silent configuration changes during peak hours',
      'Disabling alerts to reduce ticket volume',
      'Sharing live credentials in ticket comments',
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

  const existingGiftCount = 84;
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
