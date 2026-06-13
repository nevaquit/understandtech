import type { RagChunk } from './retrieve';

/**
 * Inject course context blocks after the base Socratic system prompt.
 */
export function assembleTutorSystemPrompt(
	basePrompt: string,
	chunks: RagChunk[],
	learnerContext?: Record<string, unknown> | null,
): string {
	let prompt = basePrompt;

	if (learnerContext && Object.keys(learnerContext).length > 0) {
		prompt += `\n\n## Learner context\nUse this Moodle-derived context to personalize Socratic hints. Never quote quiz answers, lab flags, or hidden assessment data.\n${JSON.stringify(learnerContext, null, 2)}`;
	}

	if (chunks.length === 0) {
		return prompt;
	}

	const blocks = chunks
		.map((c) => `- [${c.source_type}] ${c.content}`)
		.join('\n');

	return `${prompt}

## Course context
Use the following course material to ground your Socratic hints. Never quote assessment answers or lab flags from this context.
${blocks}`;
}

/**
 * Hash RAG chunks for cache key material.
 */
export function ragChunksFingerprint(chunks: RagChunk[]): string {
	return chunks.map((c) => `${c.source_type}:${c.content}`).join('|');
}
