import type { Env, MoodleJwtClaims, TutorRequestBody } from '../types';
import { validateJwt } from '../auth';

export async function handleTutor(request: Request, env: Env): Promise<Response> {
	let claims: MoodleJwtClaims;
	try {
		claims = await validateJwt(request, env);
	} catch (error) {
		const status = error instanceof Error && 'status' in error ? (error as { status: number }).status : 401;
		return Response.json({ error: 'Unauthorized' }, { status });
	}

	if (request.method !== 'POST') {
		return Response.json({ error: 'Method not allowed' }, { status: 405 });
	}

	const body = (await request.json()) as TutorRequestBody;
	const userMessage = body.messages.at(-1)?.content ?? '';

	const stream = new ReadableStream({
		start(controller) {
			const chunk = JSON.stringify({ token: `[stub] Tutor response for user ${claims.sub}: ${userMessage.slice(0, 80)}` });
			controller.enqueue(new TextEncoder().encode(`data: ${chunk}\n\n`));
			controller.enqueue(new TextEncoder().encode('data: [DONE]\n\n'));
			controller.close();
		},
	});

	return new Response(stream, {
		headers: {
			'Content-Type': 'text/event-stream',
			'Cache-Control': 'no-cache',
			Connection: 'keep-alive',
		},
	});
}
