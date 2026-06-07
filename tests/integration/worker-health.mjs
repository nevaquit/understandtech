#!/usr/bin/env node
/**
 * Integration smoke: AI Gateway /health and /tutor 401 without JWT.
 */
const workerBase = (process.env.WORKER_URL || 'https://ai.understandtech.app').replace(/\/$/, '');

async function assert(condition, message) {
  if (!condition) {
    console.error(`[FAIL] ${message}`);
    process.exit(1);
  }
  console.log(`[OK] ${message}`);
}

const healthRes = await fetch(`${workerBase}/health`);
assert(healthRes.ok, `GET /health → ${healthRes.status}`);
const healthJson = await healthRes.json();
assert(healthJson.status === 'ok', '/health status ok');

const tutorRes = await fetch(`${workerBase}/tutor`, { method: 'POST', body: '{}' });
assert(tutorRes.status === 401, `POST /tutor without JWT → 401 (got ${tutorRes.status})`);

console.log('Integration smoke passed');
