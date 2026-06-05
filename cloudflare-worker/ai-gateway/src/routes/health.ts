import type { Env } from '../types';

export function handleHealth(): Response {
	return Response.json({ status: 'ok' });
}

export async function handleHealthAuthenticated(request: Request, env: Env): Promise<Response> {
	const { validateJwt } = await import('../auth');
	await validateJwt(request, env);
	return handleHealth();
}
