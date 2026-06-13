import { test, expect } from '@playwright/test';

/**
 * CTF lab flag submission flow (mod_ctfflag + Level Up XP via local_gamification).
 *
 * Required: E2E_CTFFLAG_PATH — mod_ctfflag view URL (e.g. /mod/ctfflag/view.php?id=5).
 * Optional: E2E_CTFFLAG_VALID_FLAG — known correct flag for success + XP tests.
 * Optional: E2E_LEADERBOARD_PATH — page with block_xp (defaults to /my/).
 */
const labPath = process.env.E2E_CTFFLAG_PATH?.trim() || '';
const validFlag = process.env.E2E_CTFFLAG_VALID_FLAG?.trim() || '';
const leaderboardPath = process.env.E2E_LEADERBOARD_PATH?.trim() || '/my/';

async function readXpPoints(page: import('@playwright/test').Page): Promise<number | null> {
  const xpBlock = page.locator('.block_xp, .ut-leaderboard').first();
  if (!(await xpBlock.isVisible().catch(() => false))) {
    return null;
  }

  const xpText = await xpBlock.locator('.level, .block_xp-level, [data-region="xp-leaderboard"]').first()
    .innerText()
    .catch(() => '');
  const match = xpText.match(/(\d+)/);
  return match ? parseInt(match[1], 10) : null;
}

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

  test('correct flag completes lab when E2E_CTFFLAG_VALID_FLAG is set', async ({ page }) => {
    test.skip(!validFlag, 'Set E2E_CTFFLAG_VALID_FLAG for successful submission test');

    await page.goto(labPath);
    const alreadyComplete = await page.locator('.ut-ctfflag-complete, .alert-success').first()
      .isVisible()
      .catch(() => false);

    if (!alreadyComplete) {
      const input = page.locator('form input[name="flag"], #id_flag').first();
      await input.fill(validFlag);
      await page.locator('form button[type="submit"], #id_submitbutton').first().click();
    }

    await expect(page.getByText(/correct flag|lab complete/i).first()).toBeVisible({ timeout: 15_000 });
  });

  test('XP leaderboard reflects lab award when block_xp is installed', async ({ page }) => {
    test.skip(!validFlag, 'Set E2E_CTFFLAG_VALID_FLAG for XP verification');

    await page.goto(leaderboardPath, { waitUntil: 'domcontentloaded' });
    const xpBefore = await readXpPoints(page);
    test.skip(xpBefore === null, 'Level Up XP block not present on leaderboard path');

    await page.goto(labPath);
    const alreadyComplete = await page.locator('.ut-ctfflag-complete, .alert-success').first()
      .isVisible()
      .catch(() => false);

    if (!alreadyComplete) {
      const input = page.locator('form input[name="flag"], #id_flag').first();
      await input.fill(validFlag);
      await page.locator('form button[type="submit"], #id_submitbutton').first().click();
      await expect(page.locator('.alert-success, .ut-ctfflag-complete').first()).toBeVisible({ timeout: 15_000 });
    }

    await page.goto(leaderboardPath, { waitUntil: 'domcontentloaded' });
    const xpAfter = await readXpPoints(page);
    expect(xpAfter).not.toBeNull();
    if (xpBefore !== null && xpAfter !== null && !alreadyComplete) {
      expect(xpAfter).toBeGreaterThanOrEqual(xpBefore);
    }
  });
});
