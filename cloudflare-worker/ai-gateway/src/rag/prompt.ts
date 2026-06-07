import type { RagChunk } from './retrieve';

/**
 * Inject course context blocks after the base Socratic system prompt.
 */
export function assembleTutorSystemPrompt(basePrompt: string, chunks: RagChunk[]): string {
	if (chunks.length === 0) {
		return basePrompt;
	}

	const blocks = chunks
		.map((c) => `- [${c.source_type}] ${c.content}`)
		.join('\n');

	return `${basePrompt}

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
