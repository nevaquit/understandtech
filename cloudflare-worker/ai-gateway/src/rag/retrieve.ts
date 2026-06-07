import type { Env } from '../types';

export interface RagChunk {
	content: string;
	source_type: string;
}

/**
 * Fetch course-scoped RAG chunks from Moodle origin API (JWT-authenticated).
 */
export async function fetchRagChunks(
	env: Env,
	jwt: string,
	query: string,
	embedding: number[],
	signal: AbortSignal,
): Promise<RagChunk[]> {
	const url = env.MOODLE_RAG_URL;
	if (!url) {
		return [];
	}

	const response = await fetch(url, {
		method: 'POST',
		headers: {
			Authorization: `Bearer ${jwt}`,
			'Content-Type': 'application/json',
		},
		body: JSON.stringify({
			query,
			embedding: embedding.length > 0 ? embedding : undefined,
		}),
		signal,
	});

	if (!response.ok) {
		return [];
	}

	const json = (await response.json()) as { chunks?: RagChunk[] };
	return json.chunks ?? [];
}
