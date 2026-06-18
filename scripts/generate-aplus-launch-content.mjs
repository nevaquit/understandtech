#!/usr/bin/env node
/**
 * Generate APLUS launch-scale sub-lessons (scenario + exam focus).
 * Does NOT duplicate core lessons or add GIFT — aplus-quiz.gift already exceeds 400 Q.
 *
 * Prerequisite: content/a-plus/research/aplus-launch-gap-memo.md
 *
 * Usage: node scripts/generate-aplus-launch-content.mjs
 *
 * @package understandtech
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, '..');
const trackDir = path.join(repoRoot, 'content', 'a-plus');
const lessonsDir = path.join(trackDir, 'lessons');
const csvPath = path.join(trackDir, 'aplus-objectives.csv');

/** @type {Record<number, {name: string, role: string, industry: string, exam: string}>} */
const DOMAIN_ORGS = {
  1: { name: 'FieldTech Mobile Services', role: 'mobile device technician', industry: 'corporate device fleet', exam: '220-1101' },
  2: { name: 'BrightLink SOHO Support', role: 'network support specialist', industry: 'small business connectivity', exam: '220-1101' },
  3: { name: 'Precision PC Repair Depot', role: 'hardware bench technician', industry: 'component-level repair', exam: '220-1101' },
  4: { name: 'Nimbus Cloud Migration Team', role: 'virtualization analyst', industry: 'hybrid cloud rollout', exam: '220-1101' },
  5: { name: 'Tier-1 Help Desk North', role: 'support technician', industry: 'hardware and network escalation', exam: '220-1101' },
  6: { name: 'Desktop Services Group', role: 'OS support specialist', industry: 'Windows and macOS fleet', exam: '220-1102' },
  7: { name: 'SecureDesk IT Security', role: 'endpoint security analyst', industry: 'workstation hardening', exam: '220-1102' },
  8: { name: 'AppResolve Software Support', role: 'software troubleshooter', industry: 'line-of-business applications', exam: '220-1102' },
  9: { name: 'Compliance Operations Office', role: 'IT operations coordinator', industry: 'documentation and safety', exam: '220-1102' },
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
  const core = shortname.startsWith('ap1101_') ? '220-1101' : '220-1102';
  const rest = shortname.replace(/^ap110[12]_/, '').replace(/_/g, '.');
  return `${core} ${rest}`;
}

/** @param {string} shortname */
function domainNum(shortname) {
  if (shortname.startsWith('ap1101_')) {
    return parseInt(shortname.split('_')[1], 10);
  }
  if (shortname.startsWith('ap1102_')) {
    return 5 + parseInt(shortname.split('_')[1], 10);
  }
  return 1;
}

/** @param {string} html */
function extractKeyTerms(html) {
  const terms = [];
  const re = /<p><strong>([^<]+)<\/strong><\/p>/gi;
  let m;
  while ((m = re.exec(html)) !== null) {
    const t = m[1].trim();
    if (t.length > 3 && t.length < 80 && !t.startsWith('Exam Tip')) {
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
    ? `<p>At ${org.name}, a ticket escalated to you as ${org.role} requires hands-on diagnosis aligned to objective ${code}. Follow the CompTIA troubleshooting methodology before closing the ticket.</p>`
    : obj.fullname.startsWith('Compare and contrast')
      ? `<p>${org.name} must choose between two viable options for objective ${code}. Stakeholders disagree on cost, compatibility, and user impact. Document a comparison and phased recommendation.</p>`
      : `<p>${org.name} is preparing for a readiness review covering objective ${code}. Map current environment state, identify gaps, and propose measurable improvements for the ${org.exam} blueprint.</p>`;

  return `<div class="ut-lesson-content">
<h3>Scenario study — ${code}</h3>
<p><strong>${obj.fullname}</strong></p>
<div class="ut-lesson-body ut-scenario-lesson">
<p><em>Companion to the core lesson. Fictional help-desk / field-service case—not a duplicate of concept definitions.</em></p>
${narrative}
<h4>Situation briefing</h4>
<ul>
<li><strong>Organization:</strong> ${org.name} (${org.industry})</li>
<li><strong>Your role:</strong> ${org.role}</li>
<li><strong>Constraint:</strong> User downtime limited; document rollback steps</li>
<li><strong>Evidence available:</strong> Ticket notes, device specs, logs (synthetic)</li>
</ul>
<h4>Concepts to apply (from objective ${code})</h4>
<ul>
${termList}
</ul>
<h4>Deliverables for this scenario</h4>
<ol>
<li>Identify the most likely root cause using layered troubleshooting (hardware → OS → application).</li>
<li>State which tool or procedure would confirm your hypothesis.</li>
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
  const exam = obj.shortname.startsWith('ap1101_') ? '220-1101' : '220-1102';
  return `<div class="ut-lesson-content">
<h3>Exam focus — ${code}</h3>
<p><strong>${obj.fullname}</strong></p>
<div class="ut-lesson-body ut-exam-lesson">
<p><em>Companion to the core lesson. Learn how CompTIA frames distractors—without answer keys to knowledge checks or practice exams.</em></p>
<h4>How this objective is tested</h4>
<p>${exam} items for ${code} often use scenario stems with four plausible options. Match the <strong>primary</strong> ask: identify hardware, select a tool, choose the best next troubleshooting step, or compare technologies.</p>
<h4>Common trap patterns</h4>
<ul>
<li><strong>Symptom vs. cause:</strong> A true observation that does not answer the question asked.</li>
<li><strong>Hardware vs. software:</strong> Correct fix at the wrong layer (driver issue vs. failed RAM).</li>
<li><strong>Port/cable confusion:</strong> Similar connectors or speeds (USB-C vs. Thunderbolt, SATA vs. NVMe).</li>
<li><strong>Tool fixation:</strong> Naming a popular utility when the stem tests methodology or safety procedure.</li>
</ul>
<h4>Exam approach checklist</h4>
<ol>
<li>Underline the verb in the stem (install, compare, troubleshoot, configure).</li>
<li>Eliminate options that violate safety, ESD, or manufacturer guidance.</li>
<li>Choose the option that satisfies the stem with the fewest assumptions.</li>
</ol>
<h4>Next step</h4>
<p>Complete the domain Knowledge Check and rate confidence honestly. Revisit the core lesson if any trap pattern feels unfamiliar.</p>
</div>
</div>
`;
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

  console.log(`objectives=${objectives.length}`);
  console.log(`scenarios_written=${scenariosWritten} skipped=${scenariosSkipped}`);
  console.log(`exam_focus_written=${examsWritten} skipped=${examsSkipped}`);
  console.log(`sublessons_total=${objectives.length * 2}`);
  console.log(`launch_gift_skipped=1 reason=existing_bank_912_questions`);
}

main();
