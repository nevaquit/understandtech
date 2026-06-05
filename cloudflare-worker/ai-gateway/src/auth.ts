import { jwtVerify } from 'jose';
import type { Env, MoodleJwtClaims } from './types';

export class AuthError extends Error {
	status = 401;
}

export async function validateJwt(request: Request, env: Env): Promise<MoodleJwtClaims> {
	const header = request.headers.get('Authorization');
	if (!header?.startsWith('Bearer ')) {
		throw new AuthError('Missing bearer token');
	}

	const token = header.slice('Bearer '.length);
	const secret = new TextEncoder().encode(env.MOODLE_JWT_SECRET);

	try {
		const { payload } = await jwtVerify(token, secret, {
			algorithms: ['HS256'],
			issuer: 'moodle',
			audience: 'ai-worker',
		});

		return payload as unknown as MoodleJwtClaims;
	} catch {
		throw new AuthError('Invalid or expired token');
	}
}
