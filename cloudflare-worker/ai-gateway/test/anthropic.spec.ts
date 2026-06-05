import { describe, expect, it } from 'vitest';
import { shouldFallbackAnthropic } from '../src/llm/anthropic';

describe('shouldFallbackAnthropic', () => {
	it('falls back on auth failure and provider outages', () => {
		expect(shouldFallbackAnthropic(401)).toBe(true);
		expect(shouldFallbackAnthropic(429)).toBe(true);
		expect(shouldFallbackAnthropic(500)).toBe(true);
		expect(shouldFallbackAnthropic(504)).toBe(true);
	});

	it('does not fall back on other client errors', () => {
		expect(shouldFallbackAnthropic(400)).toBe(false);
		expect(shouldFallbackAnthropic(403)).toBe(false);
	});
});
