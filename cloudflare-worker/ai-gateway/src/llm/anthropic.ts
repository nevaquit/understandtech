import type { ChatMessage, Env } from '../types';
import { buildProviderHeaders, providerUrl, trimSecret } from './aig';

const ANTHROPIC_VERSION = '2023-06-01';
const ANTHROPIC_DIRECT_URL = 'https://api.anthropic.com/v1/messages';
const FIRST_TOKEN_TIMEOUT_MS = 10_000;

export interface AnthropicStreamResult {
	provider: 'anthropic';
	fullText: string;
}

/**
 * Stream completion from Anthropic via Cloudflare AI Gateway.
 */
export async function streamAnthropic(
	env: Env,
	systemPrompt: string,
	messages: ChatMessage[],
	signal: AbortSignal,
	onToken: (token: string) => void,
): Promise<AnthropicStreamResult> {
	const body = JSON.stringify({
		model: env.PRIMARY_MODEL,
		max_tokens: 2048,
		stream: true,
		system: systemPrompt,
		messages: messages.filter((m) => m.role !== 'system'),
	});

	const response = await fetchAnthropicMessages(env, body, signal);

	if (!response.ok) {
		const err = new Error(`Anthropic error: ${response.status}`);
		(err as Error & { status: number }).status = response.status;
		throw err;
	}

	const reader = response.body?.getReader();
	if (!reader) {
		throw new Error('Anthropic response has no body');
	}

	let fullText = '';
	const decoder = new TextDecoder();
	let buffer = '';
	let firstChunkReceived = false;

	try {
		while (true) {
			const readResult = firstChunkReceived
				? await reader.read()
				: await readWithFirstTokenTimeout(reader, FIRST_TOKEN_TIMEOUT_MS);

			const { done, value } = readResult;
			if (done) {
				break;
			}

			firstChunkReceived = true;
			buffer += decoder.decode(value, { stream: true });
			const lines = buffer.split('\n');
			buffer = lines.pop() ?? '';

			for (const line of lines) {
				if (!line.startsWith('data: ')) {
					continue;
				}
				const data = line.slice(6).trim();
				if (!data || data === '[DONE]') {
					continue;
				}

				try {
					const parsed = JSON.parse(data) as {
						type?: string;
						delta?: { type?: string; text?: string };
					};
					if (parsed.type === 'content_block_delta' && parsed.delta?.text) {
						fullText += parsed.delta.text;
						onToken(parsed.delta.text);
					}
				} catch {
					// Skip malformed SSE frames.
				}
			}
		}
	} finally {
		reader.releaseLock();
	}

	if (!firstChunkReceived) {
		const err = new Error('Anthropic first token timeout');
		(err as Error & { status: number }).status = 504;
		throw err;
	}

	return { provider: 'anthropic', fullText };
}

/** POST to Anthropic; retries direct API when gateway returns 401 (missing cf-aig auth). */
export async function fetchAnthropicMessages(
	env: Env,
	body: string,
	signal: AbortSignal,
): Promise<Response> {
	const providerHeaders = {
		'Content-Type': 'application/json',
		'x-api-key': trimSecret(env.ANTHROPIC_API_KEY),
		'anthropic-version': ANTHROPIC_VERSION,
	};

	const gatewayUrl = await providerUrl(env, 'anthropic', '/v1/messages');
	let response = await fetch(gatewayUrl, {
		method: 'POST',
		headers: buildProviderHeaders(env, providerHeaders),
		body,
		signal,
	});

	if (response.status === 401 && gatewayUrl.includes('gateway.ai.cloudflare.com')) {
		response = await fetch(ANTHROPIC_DIRECT_URL, {
			method: 'POST',
			headers: providerHeaders,
			body,
			signal,
		});
	}

	return response;
}

async function readWithFirstTokenTimeout(
	reader: ReadableStreamDefaultReader<Uint8Array>,
	timeoutMs: number,
): Promise<ReadableStreamReadResult<Uint8Array>> {
	return Promise.race([
		reader.read(),
		new Promise<never>((_, reject) => {
			setTimeout(() => {
				reader.cancel();
				const err = new Error('Anthropic first token timeout');
				(err as Error & { status: number }).status = 504;
				reject(err);
			}, timeoutMs);
		}),
	]);
}

export function shouldFallbackAnthropic(status: number): boolean {
	return status === 401 || status >= 500 || status === 429 || status === 504;
}
