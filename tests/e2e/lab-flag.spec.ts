import { test, expect } from '@playwright/test';

/**
 * CTF lab flag submission flow.
 * Set E2E_CTFFLAG_PATH to mod_ctfflag view URL (e.g. /mod/ctfflag/view.php?id=5).
 */
const labPath = process.env.E2E_CTFFLAG_PATH || '';

test.describe('CTF lab flag', () => {
  test.skip(!labPath, 'Set E2E_CTFFLAG_PATH to a ctfflag activity URL');

  test('flag submission form is visible', async ({ page }) => {
    await page.goto(labPath);
    await expect(page.locator('form input[name="flag"], #id_flag')).toBeVisible({ timeout: 15_000 });
  });

  test('invalid flag shows validation feedback', async ({ page }) => {
    await page.goto(labPath);
    const input = page.locator('form input[name="flag"], #id_flag').first();
    await input.fill('UT{invalid_test_flag}');
    await page.locator('form button[type="submit"], #id_submitbutton').first().click();
    await expect(page.locator('.alert, .notifyproblem, [role="alert"]')).toBeVisible({ timeout: 10_000 });
  });
});
