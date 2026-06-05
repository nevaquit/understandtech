import type { ChatMessage, Env, TutorContext } from './types';

export interface WebhookPayload {
	conversation_id: string;
	userid: number;
	courseid: number;
	cmid: number | null;
	messages: ChatMessage[];
	prompt_version: string;
	provider: string;
	cache_hit: boolean;
}

/**
 * POST transcript audit record to Moodle webhook with HMAC signature.
 */
export async function postTranscriptWebhook(env: Env, payload: WebhookPayload): Promise<void> {
	const body = JSON.stringify({
		conversation_id: payload.conversation_id,
		userid: payload.userid,
		courseid: payload.courseid,
		cmid: payload.cmid,
		messages: payload.messages,
	});

	const signature = await hmacSha256Hex(body, env.MOODLE_WEBHOOK_HMAC_SECRET);

	await fetch(env.MOODLE_WEBHOOK_URL, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'X-Moodle-Signature': signature,
		},
		body,
	});
}

async function hmacSha256Hex(message: string, secret: string): Promise<string> {
	const key = await crypto.subtle.importKey(
		'raw',
		new TextEncoder().encode(secret),
		{ name: 'HMAC', hash: 'SHA-256' },
		false,
		['sign'],
	);
	const signature = await crypto.subtle.sign('HMAC', key, new TextEncoder().encode(message));
	return [...new Uint8Array(signature)].map((b) => b.toString(16).padStart(2, '0')).join('');
}

export function contextToJson(context: TutorContext): string {
	return JSON.stringify({
		courseid: context.courseid,
		activityid: context.activityid,
		conversation_id: context.conversation_id,
	});
}
