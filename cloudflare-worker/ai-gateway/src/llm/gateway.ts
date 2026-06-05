import type { ChatMessage, Env } from '../types';
import { shouldFallbackAnthropic, streamAnthropic } from './anthropic';
import { completeOpenAi, streamOpenAi } from './openai';

export type LlmProvider = 'anthropic' | 'openai';

export interface StreamCompletionResult {
	provider: LlmProvider;
	fullText: string;
}

/**
 * Stream tutor completion with Anthropic primary and OpenAI fallback.
 */
export async function streamTutorCompletion(
	env: Env,
	systemPrompt: string,
	messages: ChatMessage[],
	signal: AbortSignal,
	onToken: (token: string) => void,
): Promise<StreamCompletionResult> {
	try {
		return await streamAnthropic(env, systemPrompt, messages, signal, onToken);
	} catch (error) {
		const status = (error as Error & { status?: number }).status;
		if (status !== undefined && !shouldFallbackAnthropic(status)) {
			throw error;
		}
		return streamOpenAi(env, systemPrompt, messages, signal, onToken);
	}
}

/**
 * Grade submission with OpenAI (structured JSON). Anthropic attempted first when available.
 */
export async function completeGrade(
	env: Env,
	systemPrompt: string,
	userContent: string,
	signal: AbortSignal,
): Promise<{ provider: LlmProvider; content: string }> {
	const url = `${env.AI_GATEWAY_URL}/anthropic/v1/messages`;

	try {
		const response = await fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'x-api-key': env.ANTHROPIC_API_KEY,
				'anthropic-version': '2023-06-01',
			},
			body: JSON.stringify({
				model: env.PRIMARY_MODEL,
				max_tokens: 2048,
				system: systemPrompt,
				messages: [{ role: 'user', content: userContent }],
			}),
			signal,
		});

		if (response.ok) {
			const json = (await response.json()) as {
				content?: Array<{ type: string; text?: string }>;
			};
			const text = json.content?.find((b) => b.type === 'text')?.text ?? '{}';
			return { provider: 'anthropic', content: text };
		}

		if (!shouldFallbackAnthropic(response.status)) {
			throw new Error(`Anthropic grade error: ${response.status}`);
		}
	} catch (error) {
		const status = (error as Error & { status?: number }).status;
		if (status !== undefined && !shouldFallbackAnthropic(status)) {
			throw error;
		}
	}

	const content = await completeOpenAi(env, systemPrompt, userContent, signal);
	return { provider: 'openai', content };
}
