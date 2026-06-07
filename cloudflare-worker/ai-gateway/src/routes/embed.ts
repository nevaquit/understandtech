import type { Env } from '../types';
import { AuthError, validateJwt } from '../auth';
import { checkRateLimit, RateLimitError } from '../cache';
import { embedTexts } from '../rag/embed';

export interface EmbedRequestBody {
	texts: string[];
}

export async function handleEmbed(request: Request, env: Env): Promise<Response> {
	try {
		await validateJwt(request, env);
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
		await checkRateLimit(env, 'system', 'embed');
	} catch (error) {
		if (error instanceof RateLimitError) {
			return Response.json({ error: 'Rate limit exceeded' }, { status: 429 });
		}
		throw error;
	}

	const body = (await request.json()) as EmbedRequestBody;
	const texts = (body.texts ?? []).filter((t) => typeof t === 'string' && t.trim() !== '').slice(0, 32);
	if (texts.length === 0) {
		return Response.json({ error: 'texts array required' }, { status: 400 });
	}

	const abortController = new AbortController();
	request.signal.addEventListener('abort', () => abortController.abort());

	try {
		const embeddings = await embedTexts(env, texts, abortController.signal);
		return Response.json({ embeddings, model: 'text-embedding-3-small' });
	} catch (error) {
		const message = error instanceof Error ? error.message : 'Embed failed';
		return Response.json({ error: message }, { status: 500 });
	}
}
