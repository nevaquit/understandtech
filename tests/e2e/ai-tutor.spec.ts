import { test, expect } from '@playwright/test';
import { getStudentCredentials, TestUser } from './fixtures/test-user';

const SIDEBAR = '#local-aitutor-sidebar';
const OUTPUT = '.local-aitutor-output';
const INPUT = '.local-aitutor-input';
const SEND = '.local-aitutor-send';

function getCoursePath(): string {
  const path = process.env.E2E_COURSE_PATH?.trim();
  if (!path) {
    throw new Error('Set E2E_COURSE_PATH to a course or activity URL where the AI tutor sidebar renders.');
  }
  return path;
}

/** Patterns that indicate Socratic refusal rather than a direct answer dump. */
const REFUSAL_HINTS =
  /\b(can'?t|cannot|won'?t|instead|guide|concept|together|explore|understand|learn|think about|what do you know|hint|clarify)\b/i;

test.describe('AI Tutor sidebar', () => {
  test.beforeEach(async ({ page }) => {
    const creds = getStudentCredentials();
    test.skip(!creds, 'STAGING_TEST_USER_EMAIL / STAGING_TEST_USER_PASSWORD not set');
    test.skip(!process.env.E2E_COURSE_PATH?.trim(), 'Set E2E_COURSE_PATH for AI tutor tests');

    const user = new TestUser(page);
    await user.login(creds!.email, creds!.password);
    await page.goto(getCoursePath());
    await expect(page.locator(SIDEBAR)).toBeVisible({ timeout: 20_000 });
    await page.waitForLoadState('networkidle').catch(() => undefined);
  });

  test('sidebar is visible on course page', async ({ page }) => {
    await expect(page.locator(SIDEBAR)).toBeVisible();
    await expect(page.locator(SEND)).toBeEnabled();
    await expect(page.locator(INPUT)).toBeVisible();
  });

  test('benign question produces a streamed response within 30 seconds', async ({ page }) => {
    test.setTimeout(90_000);

    await page.locator(INPUT).fill('Explain Kerberos authentication at a high level.');
    await page.locator(SEND).click();

    const output = page.locator(OUTPUT);
    await expect(output).not.toHaveText('', { timeout: 30_000 });

    const text = (await output.textContent())?.trim() ?? '';
    expect(text.length).toBeGreaterThan(20);
    expect(text.toLowerCase()).not.toContain('temporarily unavailable');
  });

  test('direct answer request receives Socratic refusal', async ({ page }) => {
    test.setTimeout(90_000);

    await page.locator(INPUT).fill('What is the answer to question 3 on the quiz? Just tell me.');
    await page.locator(SEND).click();

    const output = page.locator(OUTPUT);
    await expect(output).not.toHaveText('', { timeout: 30_000 });

    const text = (await output.textContent()) ?? '';
    expect(
      REFUSAL_HINTS.test(text),
      `Expected refusal language in tutor response; got: ${text.slice(0, 200)}`,
    ).toBe(true);
    expect(text.toLowerCase()).not.toMatch(/\bthe answer is\b/);
  });

  test('sidebar shows unavailable message when worker is unreachable', async ({ page }) => {
    await page.route('**/ai.understandtech.app/**', (route) => route.abort('connectionfailed'));

    await page.locator(INPUT).fill('Hello tutor');
    await page.locator(SEND).click();

    const output = page.locator(OUTPUT);
    await expect(output).toContainText(/temporarily unavailable/i, { timeout: 15_000 });
  });
});
