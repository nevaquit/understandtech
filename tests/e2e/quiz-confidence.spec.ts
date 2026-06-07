import { test, expect } from '@playwright/test';

/**
 * CertMaster confidence behaviour UI on quiz attempt pages.
 * Set E2E_QUIZ_PATH to a quiz URL with qbehaviour_certmasterconfidence enabled.
 */
const quizPath = process.env.E2E_QUIZ_PATH || '';

test.describe('Quiz confidence UI', () => {
  test.skip(!quizPath, 'Set E2E_QUIZ_PATH to a quiz using certmaster_confidence behaviour');

  test('confidence radio options render after answering', async ({ page }) => {
    await page.goto(quizPath);
    const confidence = page.locator('input[name*="confidence"], .qbehaviour_certmasterconfidence');
    if (await confidence.count() === 0) {
      const firstAnswer = page.locator('.answer input[type="radio"]').first();
      if (await firstAnswer.isVisible()) {
        await firstAnswer.check();
      }
    }
    await expect(page.locator('text=/Guessing|Unsure|Confident|Certain/i').first()).toBeVisible({ timeout: 20_000 });
  });
});
