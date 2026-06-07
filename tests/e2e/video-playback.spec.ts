import { test, expect } from '@playwright/test';

/**
 * Cloudflare Stream signed JWT player — requires a course Page with stream_player embed.
 * Set E2E_STREAM_COURSE_PATH when staging has a Stream lesson (e.g. /course/view.php?id=2).
 */
const coursePath = process.env.E2E_STREAM_COURSE_PATH || process.env.E2E_COURSE_PATH || '';

test.describe('Stream video playback', () => {
  test.skip(!coursePath, 'Set E2E_STREAM_COURSE_PATH or E2E_COURSE_PATH');

  test('stream iframe or player region is present', async ({ page }) => {
    await page.goto(coursePath);
    const player = page.locator('[data-region="stream-player"], iframe[src*="cloudflarestream"], .local_certmaster_stream_player');
    await expect(player.first()).toBeVisible({ timeout: 15_000 });
  });
});
