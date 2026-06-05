export interface TutorContext {
	courseid: number;
	activityid: number | null;
	conversation_id: string;
}

export interface MoodleJwtClaims {
	sub: string;
	iss: string;
	aud: string;
	iat: number;
	exp: number;
	context: TutorContext;
}

export interface ChatMessage {
	role: 'user' | 'assistant' | 'system';
	content: string;
}

export interface TutorRequestBody {
	messages: ChatMessage[];
	context?: TutorContext;
}

export interface Env {
	MOODLE_JWT_SECRET: string;
	MOODLE_WEBHOOK_HMAC_SECRET: string;
	MOODLE_WEBHOOK_URL: string;
	AI_GATEWAY_URL: string;
	PRIMARY_MODEL: string;
	SECONDARY_MODEL: string;
	CACHE_TTL_SECONDS: string;
	ANTHROPIC_API_KEY: string;
	OPENAI_API_KEY: string;
	PROMPT_CACHE: KVNamespace;
}
