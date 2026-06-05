import { test, expect } from '@playwright/test';
import { getStudentCredentials, TestUser } from './fixtures/test-user';

/**
 * Course path where the AI tutor sidebar is expected (course or module context).
 * Example: /course/view.php?id=2
 */
function getCoursePath(): string | null {
  return process.env.E2E_COURSE_PATH?.trim() || null;
}

test.describe('Dashboard and course navigation', () => {
  test.beforeEach(async ({ page }) => {
    const creds = getStudentCredentials();
    test.skip(!creds, 'STAGING_TEST_USER_EMAIL / STAGING_TEST_USER_PASSWORD not set');
    const user = new TestUser(page);
    await user.login(creds!.email, creds!.password);
  });

  test('dashboard loads for authenticated student', async ({ page }) => {
    const user = new TestUser(page);
    await user.expectDashboard();
    await expect(page.locator('body')).not.toContainText('Error');
  });

  test('course page renders when E2E_COURSE_PATH is configured', async ({ page }) => {
    const coursePath = getCoursePath();
    test.skip(!coursePath, 'Set E2E_COURSE_PATH (e.g. /course/view.php?id=2) for course navigation tests');

    await page.goto(coursePath!);
    await expect(page.locator('#page-course-view, #region-main')).toBeVisible({
      timeout: 15_000,
    });
  });

  test('breadcrumb navigation works on course page', async ({ page }) => {
    const coursePath = getCoursePath();
    test.skip(!coursePath, 'Set E2E_COURSE_PATH for breadcrumb test');

    await page.goto(coursePath!);
    const dashboardCrumb = page.locator('.breadcrumb a, nav[aria-label="Navigation bar"] a').first();
    if (await dashboardCrumb.isVisible()) {
      await dashboardCrumb.click();
      await expect(page).toHaveURL(/\/my\/|\/course\//);
    }
  });
});
