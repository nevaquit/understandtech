#!/usr/bin/env node
/**
 * Build CompTIA Network+ N10-009 content artifacts (SEC701-style four-layer pipeline).
 *
 * Usage: node scripts/build-network-plus-content.mjs
 */
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, '..');
const contentDir = path.join(repoRoot, 'content', 'network-plus');
const csvPath = path.join(contentDir, 'n10-009-objectives.csv');

const CERT = 'network_plus_n10_009';

/** @type {Record<string, string>} */
const DOMAIN_MAP = {
  '1': 'network_fundamentals',
  '2': 'network_impl',
  '3': 'network_ops',
  '4': 'network_security',
  '5': 'network_troubleshoot',
};

/** @type {Array<{domain: string, num: string, title: string}>} */
const OBJECTIVES = [
  { domain: '1', num: '1.1', title: 'Explain concepts related to the Open Systems Interconnection (OSI) reference model' },
  { domain: '1', num: '1.2', title: 'Compare and contrast networking appliances, applications, and functions' },
  { domain: '1', num: '1.3', title: 'Summarize cloud concepts and connectivity options' },
  { domain: '1', num: '1.4', title: 'Explain common networking ports, protocols, services, and traffic types' },
  { domain: '1', num: '1.5', title: 'Compare and contrast transmission media and transceivers' },
  { domain: '1', num: '1.6', title: 'Compare and contrast network topologies, architectures, and types' },
  { domain: '1', num: '1.7', title: 'Given a scenario, use appropriate IPv4 network addressing' },
  { domain: '1', num: '1.8', title: 'Summarize evolving use cases for modern network environments' },
  { domain: '2', num: '2.1', title: 'Explain characteristics of routing technologies' },
  { domain: '2', num: '2.2', title: 'Given a scenario, configure switching technologies and features' },
  { domain: '2', num: '2.3', title: 'Given a scenario, select and configure wireless devices and technologies' },
  { domain: '2', num: '2.4', title: 'Explain important factors of physical installations' },
  { domain: '3', num: '3.1', title: 'Explain the purpose of organizational processes and procedures' },
  { domain: '3', num: '3.2', title: 'Given a scenario, use network monitoring technologies' },
  { domain: '3', num: '3.3', title: 'Explain disaster recovery (DR) concepts' },
  { domain: '3', num: '3.4', title: 'Given a scenario, implement IPv4 and IPv6 network services' },
  { domain: '3', num: '3.5', title: 'Compare and contrast network access and management methods' },
  { domain: '4', num: '4.1', title: 'Explain the importance of basic network security concepts' },
  { domain: '4', num: '4.2', title: 'Summarize various types of attacks and their impact to the network' },
  { domain: '4', num: '4.3', title: 'Given a scenario, apply network security features, defense techniques, and solutions' },
  { domain: '5', num: '5.1', title: 'Explain the troubleshooting methodology' },
  { domain: '5', num: '5.2', title: 'Given a scenario, troubleshoot common cabling and physical interface issues' },
  { domain: '5', num: '5.3', title: 'Given a scenario, troubleshoot common issues with network services' },
  { domain: '5', num: '5.4', title: 'Given a scenario, troubleshoot common performance issues' },
  { domain: '5', num: '5.5', title: 'Given a scenario, use the appropriate tool or protocol to solve networking issues' },
];

/**
 * @param {string} domain
 * @param {string} num
 */
function objectiveShortname(domain, num) {
  const parts = num.split('.');
  return `n10009_${domain}_${parts[1]}`;
}

/**
 * @param {string} script
 */
function runNode(script) {
  const result = spawnSync(process.execPath, [path.join(repoRoot, 'scripts', script)], {
    cwd: repoRoot,
    stdio: 'inherit',
    env: process.env,
  });
  if (result.status !== 0) {
    throw new Error(`${script} failed with exit code ${result.status}`);
  }
}

const csvLines = ['cert_shortname,domain_shortname,objective_shortname,objective_fullname'];
for (const obj of OBJECTIVES) {
  const shortname = objectiveShortname(obj.domain, obj.num);
  csvLines.push(
    `${CERT},${DOMAIN_MAP[obj.domain]},${shortname},"${obj.title.replace(/"/g, '""')}"`
  );
}
fs.mkdirSync(contentDir, { recursive: true });
fs.writeFileSync(csvPath, `${csvLines.join('\n')}\n`);
console.log(`objectives_csv=${csvPath} count=${OBJECTIVES.length}`);

runNode('generate-network-plus-diagrams.mjs');
runNode('extract-network-plus-supplements.mjs');
runNode('extract-network-plus-course-notes.mjs');
runNode('extract-network-plus-lessons.mjs');
runNode('build-network-plus-quiz-from-practice-bank.cjs');
runNode('generate-network-plus-quiz-gift.mjs');

console.log('network-plus content build complete');
