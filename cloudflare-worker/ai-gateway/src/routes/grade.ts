import type { Env, GradeRequestBody, GradeResponse } from '../types';
import { AuthError, validateJwt } from '../auth';
import { checkRateLimit, RateLimitError } from '../cache';
import { completeGrade } from '../llm/gateway';
import { GRADE_SYSTEM_PROMPT, GRADE_SYSTEM_PROMPT_VERSION } from '../prompts';

export async function handleGrade(request: Request, env: Env): Promise<Response> {
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
		await checkRateLimit(env, claims.sub, 'grade');
	} catch (error) {
		if (error instanceof RateLimitError) {
			return Response.json({ error: 'Rate limit exceeded' }, { status: 429 });
		}
		throw error;
	}

	const body = (await request.json()) as GradeRequestBody;
	if (!body.submission?.trim() || !body.rubric?.trim()) {
		return Response.json({ error: 'submission and rubric are required' }, { status: 400 });
	}

	const userContent = JSON.stringify({
		submission: body.submission,
		rubric: body.rubric,
		courseid: body.context?.courseid ?? claims.context.courseid,
	});

	const abortController = new AbortController();
	request.signal.addEventListener('abort', () => abortController.abort());

	try {
		const result = await completeGrade(env, GRADE_SYSTEM_PROMPT, userContent, abortController.signal);
		let parsed: Omit<GradeResponse, 'provider' | 'prompt_version'>;
		try {
			parsed = JSON.parse(result.content) as Omit<GradeResponse, 'provider' | 'prompt_version'>;
		} catch {
			return Response.json({ error: 'Invalid grade response from model' }, { status: 502 });
		}

		const response: GradeResponse = {
			...parsed,
			provider: result.provider,
			prompt_version: GRADE_SYSTEM_PROMPT_VERSION,
		};

		return Response.json(response);
	} catch (error) {
		const message = error instanceof Error ? error.message : 'Grade request failed';
		return Response.json({ error: message }, { status: 500 });
	}
}
