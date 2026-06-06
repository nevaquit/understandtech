import { test, expect } from '@playwright/test';
import { getStudentCredentials } from './fixtures/test-user';

/**
 * Dashboard path where block_examreadiness is expected (defaults to /my/).
 */
function getDashboardPath(): string {
  return process.env.E2E_DASHBOARD_PATH?.trim() || '/my/';
}

test.describe('Exam readiness block', () => {
  test.beforeEach(() => {
    test.skip(!getStudentCredentials(), 'STAGING_TEST_USER_EMAIL / STAGING_TEST_USER_PASSWORD not set');
  });

  test('radar canvas and screen-reader summary table', async ({ page }) => {
    await page.goto(getDashboardPath(), { waitUntil: 'domcontentloaded' });

    const block = page.locator('.block_examreadiness, [data-block="examreadiness"]').first();
    const blockVisible = await block.isVisible().catch(() => false);
    test.skip(!blockVisible, 'Exam readiness block not present on dashboard — add block in Moodle');

    const emptyState = block.getByText(/complete a quiz|no attempts/i);
    if (await emptyState.isVisible().catch(() => false)) {
      test.skip(true, 'No seeded mastery data — empty state shown');
    }

    const canvas = block.locator('canvas.block-examreadiness-radar');
    await expect(canvas).toBeVisible({ timeout: 15_000 });
    await expect(canvas).toHaveAttribute('data-certificationid');

    const chartFigure = block.locator('.block-examreadiness-chart');
    const summaryTable = chartFigure.locator('table');
    await expect(summaryTable).toHaveCount(1);
    await expect(summaryTable.locator('caption')).toBeAttached();
    await expect(summaryTable.locator('tbody tr').first()).toBeAttached();

    const readinessScore = block.locator('.block-examreadiness-score .display-6');
    await expect(readinessScore).toContainText('%');
  });
});
