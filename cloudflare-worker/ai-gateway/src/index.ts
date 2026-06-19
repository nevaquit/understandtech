import { Router } from 'itty-router';
import type { Env } from './types';
import { AuthError } from './auth';
import { RateLimitError } from './cache';
import { handleHealth } from './routes/health';
import { handleTutor } from './routes/tutor';
import { handleGrade } from './routes/grade';
import { handleEmbed } from './routes/embed';
import { handleStudyPlan } from './routes/study-plan';
import { handleContent } from './routes/content';

const ALLOWED_ORIGINS = new Set([
	'https://understandtech.app',
	'https://www.understandtech.app',
]);

const router = Router();

router.get('/health', () => handleHealth());
router.post('/tutor', (request: Request, env: Env, ctx: ExecutionContext) =>
	handleTutor(request, env, ctx),
);
router.post('/grade', (request: Request, env: Env) => handleGrade(request, env));
router.post('/embed', (request: Request, env: Env) => handleEmbed(request, env));
router.post('/study-plan', (request: Request, env: Env) => handleStudyPlan(request, env));
router.post('/content', (request: Request, env: Env) => handleContent(request, env));
router.all('*', () => Response.json({ error: 'Not found' }, { status: 404 }));

function corsHeaders(request: Request): HeadersInit {
	const origin = request.headers.get('Origin');
	if (origin && ALLOWED_ORIGINS.has(origin)) {
		return {
			'Access-Control-Allow-Origin': origin,
			'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
			'Access-Control-Allow-Headers': 'Authorization, Content-Type',
			'Vary': 'Origin',
		};
	}
	return {};
}

function withCors(response: Response, request: Request): Response {
	const headers = new Headers(response.headers);
	for (const [key, value] of Object.entries(corsHeaders(request))) {
		headers.set(key, value);
	}
	return new Response(response.body, {
		status: response.status,
		statusText: response.statusText,
		headers,
	});
}

export default {
	async fetch(request: Request, env: Env, ctx: ExecutionContext): Promise<Response> {
		if (request.method === 'OPTIONS') {
			return new Response(null, { status: 204, headers: corsHeaders(request) });
		}

		try {
			const response = await router.fetch(request, env, ctx);
			return withCors(response, request);
		} catch (error) {
			if (error instanceof AuthError) {
				return withCors(Response.json({ error: error.message }, { status: 401 }), request);
			}
			if (error instanceof RateLimitError) {
				return withCors(Response.json({ error: error.message }, { status: 429 }), request);
			}
			const message = error instanceof Error ? error.message : 'Unexpected error';
			return withCors(Response.json({ error: message }, { status: 500 }), request);
		}
	},
} satisfies ExportedHandler<Env>;
