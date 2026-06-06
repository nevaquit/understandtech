import { test, expect } from '@playwright/test';
import { getStudentCredentials, TestUser } from './fixtures/test-user';

test.describe('Authentication', () => {
  test('login with valid credentials lands on dashboard', async ({ page }) => {
    const creds = getStudentCredentials();
    test.skip(!creds, 'STAGING_TEST_USER_EMAIL / STAGING_TEST_USER_PASSWORD not set');

    const user = new TestUser(page);
    await user.login(creds!.email, creds!.password);
    await user.expectDashboard();
  });

  test('login with invalid credentials shows error', async ({ page }) => {
    const user = new TestUser(page);
    await user.gotoLogin();
    await page.locator('#username').fill('invalid-e2e-user@example.com');
    await page.locator('#password').fill('not-a-real-password');
    await page.locator('#loginbtn').click();

    await expect(page).toHaveURL(/\/login\/index\.php/);
    await expect(
      page.getByRole('alert').or(page.locator('#loginerrormessage, .loginerrors')),
    ).toBeVisible({ timeout: 10_000 });
  });

  test('logout returns to login page', async ({ page }) => {
    const creds = getStudentCredentials();
    test.skip(!creds, 'STAGING_TEST_USER_EMAIL / STAGING_TEST_USER_PASSWORD not set');

    const user = new TestUser(page);
    await user.login(creds!.email, creds!.password);
    await user.expectDashboard();

    const logoutLink = page.getByRole('link', { name: /log out/i });
    if (await logoutLink.isVisible()) {
      await logoutLink.click();
    } else {
      const userMenu = page.locator('[data-region="usermenu"], .usermenu');
      await userMenu.click();
      await page.getByRole('menuitem', { name: /log out/i }).click();
    }

    // Moodle may show logout confirmation before returning to login.
    const continueBtn = page.getByRole('button', { name: /continue|log out/i });
    if (await continueBtn.isVisible({ timeout: 5_000 }).catch(() => false)) {
      await continueBtn.click();
    }

    await expect(page).toHaveURL(/\/login\/index\.php|\/\?/, { timeout: 15_000 });
    const loginForm = page.locator('.loginform');
    if (await loginForm.isVisible({ timeout: 3_000 }).catch(() => false)) {
      await expect(loginForm).toBeVisible();
    } else {
      await expect(page.getByText(/not logged in/i)).toBeVisible();
    }
  });

  test('session persists across page reload', async ({ page }) => {
    const creds = getStudentCredentials();
    test.skip(!creds, 'STAGING_TEST_USER_EMAIL / STAGING_TEST_USER_PASSWORD not set');

    const user = new TestUser(page);
    await user.login(creds!.email, creds!.password);
    await user.expectDashboard();

    await page.reload();
    await user.expectDashboard();
    await expect(page.locator('#username')).toHaveCount(0);
  });
});
