import type { ChatMessage, Env } from '../types';

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
	const url = `${env.AI_GATEWAY_URL}/openai/chat/completions`;
	const chatMessages: ChatMessage[] = [
		{ role: 'system', content: systemPrompt },
		...messages.filter((m) => m.role !== 'system'),
	];

	const response = await fetch(url, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			Authorization: `Bearer ${env.OPENAI_API_KEY}`,
		},
		body: JSON.stringify({
			model: env.SECONDARY_MODEL,
			max_tokens: 2048,
			stream: true,
			messages: chatMessages,
		}),
		signal,
	});

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
	const url = `${env.AI_GATEWAY_URL}/openai/chat/completions`;
	const response = await fetch(url, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			Authorization: `Bearer ${env.OPENAI_API_KEY}`,
		},
		body: JSON.stringify({
			model: env.SECONDARY_MODEL,
			max_tokens: 2048,
			response_format: { type: 'json_object' },
			messages: [
				{ role: 'system', content: systemPrompt },
				{ role: 'user', content: userContent },
			],
		}),
		signal,
	});

	if (!response.ok) {
		throw new Error(`OpenAI grade error: ${response.status}`);
	}

	const json = (await response.json()) as {
		choices?: Array<{ message?: { content?: string } }>;
	};
	return json.choices?.[0]?.message?.content ?? '{}';
}
