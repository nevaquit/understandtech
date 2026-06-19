import type { Env, StudyPlanRequestBody, StudyPlanResponse } from '../types';
import { AuthError, validateJwt } from '../auth';
import { checkRateLimit, RateLimitError } from '../cache';
import { completeStructuredJson } from '../llm/gateway';
import { STUDY_PLAN_SYSTEM_PROMPT, STUDY_PLAN_SYSTEM_PROMPT_VERSION } from '../prompts';

export async function handleStudyPlan(request: Request, env: Env): Promise<Response> {
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
		await checkRateLimit(env, claims.sub, 'study-plan');
	} catch (error) {
		if (error instanceof RateLimitError) {
			return Response.json({ error: 'Rate limit exceeded' }, { status: 429 });
		}
		throw error;
	}

	const body = (await request.json()) as StudyPlanRequestBody;
	if (!body.weak_objectives?.length) {
		return Response.json({ error: 'weak_objectives is required' }, { status: 400 });
	}

	const userContent = JSON.stringify({
		weak_objectives: body.weak_objectives,
		misconceptions: body.misconceptions ?? {},
		activities: body.activities ?? [],
		courseid: body.context?.courseid ?? claims.context.courseid,
	});

	const abortController = new AbortController();
	request.signal.addEventListener('abort', () => abortController.abort());

	try {
		const result = await completeStructuredJson(
			env,
			STUDY_PLAN_SYSTEM_PROMPT,
			userContent,
			abortController.signal,
		);
		let parsed: Omit<StudyPlanResponse, 'provider' | 'prompt_version'>;
		try {
			parsed = JSON.parse(result.content) as Omit<StudyPlanResponse, 'provider' | 'prompt_version'>;
		} catch {
			return Response.json({ error: 'Invalid study plan response from model' }, { status: 502 });
		}

		if (!parsed.summary || !Array.isArray(parsed.activities)) {
			return Response.json({ error: 'Malformed study plan response' }, { status: 502 });
		}

		const response: StudyPlanResponse = {
			...parsed,
			provider: result.provider,
			prompt_version: STUDY_PLAN_SYSTEM_PROMPT_VERSION,
		};

		return Response.json(response);
	} catch (error) {
		const message = error instanceof Error ? error.message : 'Study plan request failed';
		return Response.json({ error: message }, { status: 500 });
	}
}
