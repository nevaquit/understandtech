import {
	env,
	createExecutionContext,
	waitOnExecutionContext,
} from 'cloudflare:test';
import { describe, it, expect } from 'vitest';
import worker from '../src/index';

const IncomingRequest = Request<unknown, IncomingRequestCfProperties>;

describe('AI Gateway Worker', () => {
	it('GET /health returns ok', async () => {
		const request = new IncomingRequest('https://ai.understandtech.app/health');
		const ctx = createExecutionContext();
		const response = await worker.fetch(request, env, ctx);
		await waitOnExecutionContext(ctx);

		expect(response.status).toBe(200);
		expect(await response.json()).toEqual({ status: 'ok' });
	});

	it('POST /tutor without JWT returns 401', async () => {
		const request = new IncomingRequest('https://ai.understandtech.app/tutor', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ messages: [{ role: 'user', content: 'hello' }] }),
		});
		const ctx = createExecutionContext();
		const response = await worker.fetch(request, env, ctx);
		await waitOnExecutionContext(ctx);

		expect(response.status).toBe(401);
	});

	it('POST /grade without JWT returns 401', async () => {
		const request = new IncomingRequest('https://ai.understandtech.app/grade', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ submission: 'test', rubric: 'rubric' }),
		});
		const ctx = createExecutionContext();
		const response = await worker.fetch(request, env, ctx);
		await waitOnExecutionContext(ctx);

		expect(response.status).toBe(401);
	});

	it('POST /study-plan without JWT returns 401', async () => {
		const request = new IncomingRequest('https://ai.understandtech.app/study-plan', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ weak_objectives: [{ shortname: 'obj1', fullname: 'Obj 1', score: 40 }] }),
		});
		const ctx = createExecutionContext();
		const response = await worker.fetch(request, env, ctx);
		await waitOnExecutionContext(ctx);

		expect(response.status).toBe(401);
	});

	it('POST /content without JWT returns 401', async () => {
		const request = new IncomingRequest('https://ai.understandtech.app/content', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ draft_type: 'lesson_summary', source_excerpt: 'Sample lesson text.' }),
		});
		const ctx = createExecutionContext();
		const response = await worker.fetch(request, env, ctx);
		await waitOnExecutionContext(ctx);

		expect(response.status).toBe(401);
	});

	it('unknown route returns 404', async () => {
		const request = new IncomingRequest('https://ai.understandtech.app/unknown');
		const ctx = createExecutionContext();
		const response = await worker.fetch(request, env, ctx);
		await waitOnExecutionContext(ctx);

		expect(response.status).toBe(404);
	});
});
