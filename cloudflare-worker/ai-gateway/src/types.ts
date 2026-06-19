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
	learner_context?: Record<string, unknown> | null;
}

export interface GradeRequestBody {
	submission: string;
	rubric: string;
	context?: TutorContext;
}

export interface GradeResponse {
	score: number;
	max_score: number;
	feedback: string;
	rubric_breakdown: Array<{
		criterion: string;
		score: number;
		max_score: number;
		comment: string;
	}>;
	provider: string;
	prompt_version: string;
}

export interface StudyPlanObjectiveInput {
	shortname: string;
	fullname: string;
	score: number;
}

export interface StudyPlanActivityInput {
	objective: string;
	title: string;
	type: string;
	minutes: number;
	reason: string;
	mastery_score: number;
}

export interface StudyPlanRequestBody {
	weak_objectives: StudyPlanObjectiveInput[];
	misconceptions?: Record<string, string>;
	activities?: StudyPlanActivityInput[];
	context?: TutorContext;
}

export interface StudyPlanActivityOutput {
	objective: string;
	title: string;
	type: 'lesson_review' | 'practice_quiz' | 'lab';
	minutes: number;
	reason: string;
}

export interface StudyPlanResponse {
	summary: string;
	activities: StudyPlanActivityOutput[];
	provider: string;
	prompt_version: string;
}

export type ContentDraftType = 'lesson_summary' | 'quiz_draft' | 'flashcards' | 'scenario_variant';

export interface ContentGenRequestBody {
	draft_type: ContentDraftType;
	source_excerpt: string;
	context?: TutorContext;
}

export interface ContentGenResponse {
	draft: Record<string, unknown>;
	provider: string;
	prompt_version: string;
}

export interface RagChunk {
	content: string;
	source_type: string;
}

export interface Env {
	MOODLE_JWT_SECRET: string;
	MOODLE_WEBHOOK_HMAC_SECRET: string;
	MOODLE_WEBHOOK_URL: string;
	MOODLE_RAG_URL: string;
	AI_GATEWAY_URL: string;
	AI_GATEWAY_ID: string;
	PRIMARY_MODEL: string;
	SECONDARY_MODEL: string;
	CACHE_TTL_SECONDS: string;
	ANTHROPIC_API_KEY: string;
	OPENAI_API_KEY: string;
	/** Optional; only needed when gateway auth is on and AI binding is unavailable. */
	CF_AIG_AUTHORIZATION?: string;
	PROMPT_CACHE: KVNamespace;
}
