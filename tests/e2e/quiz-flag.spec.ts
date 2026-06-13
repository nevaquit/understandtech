import { test, expect, type Page } from '@playwright/test';

/**
 * Quiz question flag toggle (theme_understandtech quiz_flag_fallback).
 *
 * Set E2E_QUIZ_ATTEMPT_PATH to an in-progress attempt URL, or E2E_QUIZ_PATH to a quiz
 * view URL (the spec will start or continue an attempt).
 */
const attemptPath = process.env.E2E_QUIZ_ATTEMPT_PATH?.trim() || '';
const quizPath = process.env.E2E_QUIZ_PATH?.trim() || '';

async function openQuizAttempt(page: Page): Promise<string> {
  if (attemptPath) {
    await page.goto(attemptPath, { waitUntil: 'domcontentloaded' });
    return attemptPath;
  }

  await page.goto(quizPath, { waitUntil: 'domcontentloaded' });

  const continueBtn = page.getByRole('button', { name: /continue attempt/i }).first();
  if (await continueBtn.isVisible().catch(() => false)) {
    await continueBtn.click();
  } else {
    const attemptBtn = page.getByRole('button', { name: /attempt quiz|preview quiz now/i }).first();
    const attemptLink = page.locator('a[href*="startattempt.php"]').first();
    if (await attemptBtn.isVisible().catch(() => false)) {
      await attemptBtn.click();
    } else if (await attemptLink.isVisible().catch(() => false)) {
      await attemptLink.click();
    }

    const preflightSubmit = page.locator('#id_submitbutton, input[name="submitbutton"]').first();
    if (await preflightSubmit.isVisible({ timeout: 5000 }).catch(() => false)) {
      await preflightSubmit.click();
    }
  }

  await expect(page.locator('.que, #responseform, form#attemptquiz').first()).toBeVisible({
    timeout: 20_000,
  });
  return page.url();
}

test.describe('Quiz question flag', () => {
  test.skip(!attemptPath && !quizPath, 'Set E2E_QUIZ_ATTEMPT_PATH or E2E_QUIZ_PATH');

  test('flag toggle persists after click and reload', async ({ page }) => {
    await openQuizAttempt(page);

    const flagRegion = page.locator('.que .questionflag.editable, .que .questionflag.ut-flag-ready').first();
    await expect(flagRegion).toBeVisible({ timeout: 20_000 });

    const flagControl = flagRegion.locator('a.aabtn, label, input[type=checkbox]').first();
    await expect(flagControl).toBeVisible();

    const hiddenValue = flagRegion.locator('input.questionflagvalue');
    const hasHidden = await hiddenValue.count() > 0;
    const initialPressed = await flagRegion.locator('[aria-pressed]').getAttribute('aria-pressed').catch(() => null);

    await flagControl.click();

    if (hasHidden) {
      await expect(hiddenValue).toHaveValue('1', { timeout: 10_000 });
    } else {
      await expect(flagRegion.locator('[aria-pressed="true"]')).toBeVisible({ timeout: 10_000 });
    }

    await page.reload({ waitUntil: 'domcontentloaded' });
    await expect(page.locator('.que .questionflag').first()).toBeVisible({ timeout: 20_000 });

    if (hasHidden) {
      await expect(page.locator('.que .questionflag input.questionflagvalue').first()).toHaveValue('1');
    } else {
      await expect(page.locator('.que .questionflag [aria-pressed="true"]').first()).toBeVisible();
    }

    if (initialPressed === 'true') {
      // Already flagged before test — persistence check above is sufficient.
      return;
    }
  });
});
