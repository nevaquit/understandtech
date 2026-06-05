import type { Env, MoodleJwtClaims, TutorRequestBody } from '../types';
import { AuthError, validateJwt } from '../auth';
import {
	buildTutorCacheKey,
	checkRateLimit,
	getCachedResponse,
	RateLimitError,
	setCachedResponse,
} from '../cache';
import { streamTutorCompletion } from '../llm/gateway';
import { TUTOR_SYSTEM_PROMPT, TUTOR_SYSTEM_PROMPT_VERSION } from '../prompts';
import { contextToJson, postTranscriptWebhook } from '../webhook';

function sseFrame(data: unknown): Uint8Array {
	return new TextEncoder().encode(`data: ${JSON.stringify(data)}\n\n`);
}

function sseDone(): Uint8Array {
	return new TextEncoder().encode('data: [DONE]\n\n');
}

function replayCachedAsSse(cached: string): ReadableStream<Uint8Array> {
	return new ReadableStream({
		start(controller) {
			controller.enqueue(sseFrame({ token: cached }));
			controller.enqueue(sseDone());
			controller.close();
		},
	});
}

export async function handleTutor(
	request: Request,
	env: Env,
	ctx: ExecutionContext,
): Promise<Response> {
	let claims: MoodleJwtClaims;
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
		await checkRateLimit(env, claims.sub, 'tutor');
	} catch (error) {
		if (error instanceof RateLimitError) {
			return Response.json({ error: 'Rate limit exceeded' }, { status: 429 });
		}
		throw error;
	}

	const body = (await request.json()) as TutorRequestBody;
	const userMessage = body.messages.at(-1)?.content ?? '';
	const context = body.context ?? claims.context;
	const contextJson = contextToJson(context);
	const cacheKey = await buildTutorCacheKey(TUTOR_SYSTEM_PROMPT_VERSION, contextJson, userMessage);

	const cached = await getCachedResponse(env, cacheKey);
	if (cached) {
		ctx.waitUntil(
			postTranscriptWebhook(env, {
				conversation_id: context.conversation_id,
				userid: parseInt(claims.sub, 10),
				courseid: context.courseid,
				cmid: context.activityid,
				messages: [{ role: 'assistant', content: cached }],
				prompt_version: TUTOR_SYSTEM_PROMPT_VERSION,
				provider: 'cache',
				cache_hit: true,
			}).catch(() => undefined),
		);

		return new Response(replayCachedAsSse(cached), {
			headers: sseHeaders(),
		});
	}

	const stream = new ReadableStream<Uint8Array>({
		async start(controller) {
			const tokens: string[] = [];
			const abortController = new AbortController();
			request.signal.addEventListener('abort', () => abortController.abort());

			try {
				const result = await streamTutorCompletion(
					env,
					TUTOR_SYSTEM_PROMPT,
					body.messages,
					abortController.signal,
					(token) => {
						tokens.push(token);
						controller.enqueue(sseFrame({ token }));
					},
				);

				controller.enqueue(sseDone());
				controller.close();

				const fullText = result.fullText || tokens.join('');

				ctx.waitUntil(
					(async () => {
						await setCachedResponse(env, cacheKey, fullText);
						await postTranscriptWebhook(env, {
							conversation_id: context.conversation_id,
							userid: parseInt(claims.sub, 10),
							courseid: context.courseid,
							cmid: context.activityid,
							messages: [
								{ role: 'user', content: userMessage },
								{ role: 'assistant', content: fullText },
							],
							prompt_version: TUTOR_SYSTEM_PROMPT_VERSION,
							provider: result.provider,
							cache_hit: false,
						});
					})().catch(() => undefined),
				);
			} catch (error) {
				const message = error instanceof Error ? error.message : 'Stream failed';
				controller.enqueue(sseFrame({ error: message }));
				controller.enqueue(sseDone());
				controller.close();
			}
		},
	});

	return new Response(stream, { headers: sseHeaders() });
}

function sseHeaders(): HeadersInit {
	return {
		'Content-Type': 'text/event-stream',
		'Cache-Control': 'no-cache',
		Connection: 'keep-alive',
	};
}
