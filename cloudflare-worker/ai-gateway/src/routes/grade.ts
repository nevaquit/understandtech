import type { Env } from '../types';
import { validateJwt } from '../auth';

export async function handleGrade(request: Request, env: Env): Promise<Response> {
	try {
		await validateJwt(request, env);
	} catch {
		return Response.json({ error: 'Unauthorized' }, { status: 401 });
	}

	if (request.method !== 'POST') {
		return Response.json({ error: 'Method not allowed' }, { status: 405 });
	}

	return Response.json({
		status: 'stub',
		message: 'AI grading endpoint scaffold — LLM integration pending Phase 4.2 completion.',
	});
}
