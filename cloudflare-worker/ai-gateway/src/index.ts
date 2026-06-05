import { Router } from 'itty-router';
import type { Env } from './types';
import { handleHealth } from './routes/health';
import { handleTutor } from './routes/tutor';
import { handleGrade } from './routes/grade';

const router = Router();

router.get('/health', () => handleHealth());
router.post('/tutor', (request: Request, env: Env) => handleTutor(request, env));
router.post('/grade', (request: Request, env: Env) => handleGrade(request, env));
router.all('*', () => Response.json({ error: 'Not found' }, { status: 404 }));

export default {
	async fetch(request: Request, env: Env, ctx: ExecutionContext): Promise<Response> {
		try {
			return await router.fetch(request, env, ctx);
		} catch (error) {
			const message = error instanceof Error ? error.message : 'Unexpected error';
			return Response.json({ error: message }, { status: 500 });
		}
	},
} satisfies ExportedHandler<Env>;
