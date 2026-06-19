import type { ContentDraftType, ContentGenRequestBody, ContentGenResponse, Env } from '../types';
import { AuthError, validateJwt } from '../auth';
import { checkRateLimit, RateLimitError } from '../cache';
import { completeStructuredJson } from '../llm/gateway';
import { CONTENT_GEN_SYSTEM_PROMPT, CONTENT_GEN_SYSTEM_PROMPT_VERSION } from '../prompts';

const VALID_DRAFT_TYPES = new Set<ContentDraftType>([
	'lesson_summary',
	'quiz_draft',
	'flashcards',
	'scenario_variant',
]);

export async function handleContent(request: Request, env: Env): Promise<Response> {
	let claims;
	try {
		claims = await validateJwt(request, env);
	} catch (error) {
		if (error instanceof AuthError) {
			return Response.json({ error: 'Unauthorized' }, { status: 401 });
		}
		throw error;
	}

	if (request.method !== 'POST') {
		return Response.json({ error: 'Method not allowed' }, { status: 405 });
	}

	try {
		await checkRateLimit(env, claims.sub, 'content');
	} catch (error) {
		if (error instanceof RateLimitError) {
			return Response.json({ error: 'Rate limit exceeded' }, { status: 429 });
		}
		throw error;
	}

	const body = (await request.json()) as ContentGenRequestBody;
	if (!body.source_excerpt?.trim()) {
		return Response.json({ error: 'source_excerpt is required' }, { status: 400 });
	}
	if (!body.draft_type || !VALID_DRAFT_TYPES.has(body.draft_type)) {
		return Response.json({ error: 'draft_type is invalid' }, { status: 400 });
	}

	const userContent = JSON.stringify({
		draft_type: body.draft_type,
		source_excerpt: body.source_excerpt,
		courseid: body.context?.courseid ?? claims.context.courseid,
	});

	const abortController = new AbortController();
	request.signal.addEventListener('abort', () => abortController.abort());

	try {
		const result = await completeStructuredJson(
			env,
			CONTENT_GEN_SYSTEM_PROMPT,
			userContent,
			abortController.signal,
		);
		let parsed: Record<string, unknown>;
		try {
			parsed = JSON.parse(result.content) as Record<string, unknown>;
		} catch {
			return Response.json({ error: 'Invalid content response from model' }, { status: 502 });
		}

		const response: ContentGenResponse = {
			draft: parsed,
			provider: result.provider,
			prompt_version: CONTENT_GEN_SYSTEM_PROMPT_VERSION,
		};

		return Response.json(response);
	} catch (error) {
		const message = error instanceof Error ? error.message : 'Content request failed';
		return Response.json({ error: message }, { status: 500 });
	}
}
