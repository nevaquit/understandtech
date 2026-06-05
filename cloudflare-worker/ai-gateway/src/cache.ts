import type { Env } from './types';

const RATE_LIMIT_MAX = 30;
const RATE_LIMIT_WINDOW_SECONDS = 60;

export class RateLimitError extends Error {
	status = 429;
}

/**
 * SHA-256 hex digest for cache keys.
 */
export async function sha256(input: string): Promise<string> {
	const data = new TextEncoder().encode(input);
	const hash = await crypto.subtle.digest('SHA-256', data);
	return [...new Uint8Array(hash)].map((b) => b.toString(16).padStart(2, '0')).join('');
}

/**
 * Build KV cache key from prompt version, context, and latest user message.
 */
export async function buildTutorCacheKey(
	promptVersion: string,
	contextJson: string,
	userMessage: string,
): Promise<string> {
	const material = `${promptVersion}:${contextJson}:${userMessage}`;
	return `cache:${await sha256(material)}`;
}

export async function getCachedResponse(env: Env, key: string): Promise<string | null> {
	return env.PROMPT_CACHE.get(key);
}

export async function setCachedResponse(env: Env, key: string, value: string): Promise<void> {
	const ttl = parseInt(env.CACHE_TTL_SECONDS, 10) || 60;
	await env.PROMPT_CACHE.put(key, value, { expirationTtl: ttl });
}

/**
 * Enforce 30 messages per minute per user (playbook default).
 */
export async function checkRateLimit(env: Env, userId: string, route: string): Promise<void> {
	const key = `rl:${userId}:${route}`;
	const current = await env.PROMPT_CACHE.get(key);
	const count = current ? parseInt(current, 10) : 0;

	if (count >= RATE_LIMIT_MAX) {
		throw new RateLimitError('Rate limit exceeded');
	}

	await env.PROMPT_CACHE.put(key, String(count + 1), {
		expirationTtl: RATE_LIMIT_WINDOW_SECONDS,
	});
}
