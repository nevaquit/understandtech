import type { Env } from '../types';
import { buildProviderHeaders, providerUrl, trimSecret } from '../llm/aig';

const EMBEDDING_MODEL = 'text-embedding-3-small';

/**
 * Normalize query text for embedding (collapse whitespace, trim).
 */
export function normalizeQuery(text: string): string {
	return text.replace(/\s+/g, ' ').trim().slice(0, 8000);
}

/**
 * Embed a single query via OpenAI through Cloudflare AI Gateway.
 */
export async function embedQuery(env: Env, text: string, signal: AbortSignal): Promise<number[]> {
	const input = normalizeQuery(text);
	if (!input) {
		return [];
	}

	const url = await providerUrl(env, 'openai', '/v1/embeddings');
	const response = await fetch(url, {
		method: 'POST',
		headers: buildProviderHeaders(env, {
			'Content-Type': 'application/json',
			Authorization: `Bearer ${trimSecret(env.OPENAI_API_KEY)}`,
		}),
		body: JSON.stringify({
			model: EMBEDDING_MODEL,
			input,
		}),
		signal,
	});

	if (!response.ok) {
		throw new Error(`Embedding error: ${response.status}`);
	}

	const json = (await response.json()) as {
		data?: Array<{ embedding?: number[] }>;
	};
	return json.data?.[0]?.embedding ?? [];
}

/**
 * Batch embed content chunks for Moodle ingestion.
 */
export async function embedTexts(env: Env, texts: string[], signal: AbortSignal): Promise<number[][]> {
	if (texts.length === 0) {
		return [];
	}

	const url = await providerUrl(env, 'openai', '/v1/embeddings');
	const response = await fetch(url, {
		method: 'POST',
		headers: buildProviderHeaders(env, {
			'Content-Type': 'application/json',
			Authorization: `Bearer ${trimSecret(env.OPENAI_API_KEY)}`,
		}),
		body: JSON.stringify({
			model: EMBEDDING_MODEL,
			input: texts.map((t) => normalizeQuery(t)),
		}),
		signal,
	});

	if (!response.ok) {
		throw new Error(`Batch embedding error: ${response.status}`);
	}

	const json = (await response.json()) as {
		data?: Array<{ index: number; embedding?: number[] }>;
	};
	const sorted = (json.data ?? []).sort((a, b) => a.index - b.index);
	return sorted.map((row) => row.embedding ?? []);
}
