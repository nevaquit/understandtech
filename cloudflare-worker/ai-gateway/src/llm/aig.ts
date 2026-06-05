import type { Env } from '../types';

/** Trim secrets synced from Key Vault / wrangler (avoids trailing newline 401s). */
export function trimSecret(value: string | undefined): string {
	return (value ?? '').trim();
}

/**
 * Provider-native gateway URL. Uses AI_GATEWAY_URL (BYOK path). The AI binding
 * in wrangler.jsonc pre-authenticates fetch() to gateway.ai.cloudflare.com.
 */
export async function providerUrl(
	env: Env,
	provider: 'anthropic' | 'openai',
	suffix: string,
): Promise<string> {
	const path = suffix.startsWith('/') ? suffix : `/${suffix}`;
	return `${env.AI_GATEWAY_URL.replace(/\/$/, '')}/${provider}${path}`;
}

/**
 * Merge provider headers with optional Cloudflare AI Gateway auth.
 */
export function buildProviderHeaders(
	env: Env,
	headers: Record<string, string>,
): Record<string, string> {
	const out = { ...headers };
	const aigAuth = trimSecret(env.CF_AIG_AUTHORIZATION);
	if (aigAuth) {
		out['cf-aig-authorization'] = aigAuth.startsWith('Bearer ') ? aigAuth : `Bearer ${aigAuth}`;
	}
	return out;
}
