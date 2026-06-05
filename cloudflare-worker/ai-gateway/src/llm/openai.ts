import type { ChatMessage, Env } from '../types';
import { buildProviderHeaders, providerUrl, trimSecret } from './aig';

const OPENAI_DIRECT_URL = 'https://api.openai.com/v1/chat/completions';

export interface OpenAiStreamResult {
	provider: 'openai';
	fullText: string;
}

/**
 * Stream completion from OpenAI via Cloudflare AI Gateway (fallback provider).
 */
export async function streamOpenAi(
	env: Env,
	systemPrompt: string,
	messages: ChatMessage[],
	signal: AbortSignal,
	onToken: (token: string) => void,
): Promise<OpenAiStreamResult> {
	const chatMessages: ChatMessage[] = [
		{ role: 'system', content: systemPrompt },
		...messages.filter((m) => m.role !== 'system'),
	];

	const body = JSON.stringify({
		model: env.SECONDARY_MODEL,
		max_tokens: 2048,
		stream: true,
		messages: chatMessages,
	});

	const response = await fetchOpenAiChatCompletions(env, body, signal);

	if (!response.ok) {
		throw new Error(`OpenAI error: ${response.status}`);
	}

	const reader = response.body?.getReader();
	if (!reader) {
		throw new Error('OpenAI response has no body');
	}

	let fullText = '';
	const decoder = new TextDecoder();
	let buffer = '';

	try {
		while (true) {
			const { done, value } = await reader.read();
			if (done) {
				break;
			}

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
						choices?: Array<{ delta?: { content?: string } }>;
					};
					const token = parsed.choices?.[0]?.delta?.content;
					if (token) {
						fullText += token;
						onToken(token);
					}
				} catch {
					// Skip malformed SSE frames.
				}
			}
		}
	} finally {
		reader.releaseLock();
	}

	return { provider: 'openai', fullText };
}

/**
 * Non-streaming completion for the /grade endpoint.
 */
export async function completeOpenAi(
	env: Env,
	systemPrompt: string,
	userContent: string,
	signal: AbortSignal,
): Promise<string> {
	const body = JSON.stringify({
		model: env.SECONDARY_MODEL,
		max_tokens: 2048,
		response_format: { type: 'json_object' },
		messages: [
			{ role: 'system', content: systemPrompt },
			{ role: 'user', content: userContent },
		],
	});

	const response = await fetchOpenAiChatCompletions(env, body, signal);

	if (!response.ok) {
		throw new Error(`OpenAI grade error: ${response.status}`);
	}

	const json = (await response.json()) as {
		choices?: Array<{ message?: { content?: string } }>;
	};
	return json.choices?.[0]?.message?.content ?? '{}';
}

/** POST to OpenAI; retries direct API when gateway returns 401 (missing cf-aig auth). */
export async function fetchOpenAiChatCompletions(
	env: Env,
	body: string,
	signal: AbortSignal,
): Promise<Response> {
	const providerHeaders = {
		'Content-Type': 'application/json',
		Authorization: `Bearer ${trimSecret(env.OPENAI_API_KEY)}`,
	};

	const gatewayUrl = await providerUrl(env, 'openai', '/chat/completions');
	let response = await fetch(gatewayUrl, {
		method: 'POST',
		headers: buildProviderHeaders(env, providerHeaders),
		body,
		signal,
	});

	if (response.status === 401 && gatewayUrl.includes('gateway.ai.cloudflare.com')) {
		response = await fetch(OPENAI_DIRECT_URL, {
			method: 'POST',
			headers: providerHeaders,
			body,
			signal,
		});
	}

	return response;
}
