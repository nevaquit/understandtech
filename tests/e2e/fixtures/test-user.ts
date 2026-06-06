import { expect, type Page } from '@playwright/test';

export type TestCredentials = {
  email: string;
  password: string;
};

/**
 * Read student credentials from environment (never hardcode secrets).
 */
export function getStudentCredentials(): TestCredentials | null {
  const email = process.env.STAGING_TEST_USER_EMAIL;
  const password = process.env.STAGING_TEST_USER_PASSWORD;
  if (!email || !password) {
    return null;
  }
  return { email, password };
}

export function requireStudentCredentials(): TestCredentials {
  const creds = getStudentCredentials();
  if (!creds) {
    throw new Error(
      'Set STAGING_TEST_USER_EMAIL and STAGING_TEST_USER_PASSWORD to run authenticated E2E tests.',
    );
  }
  return creds;
}

/**
 * Moodle login page object helpers.
 */
export class TestUser {
  constructor(private readonly page: Page) {}

  async gotoLogin(): Promise<void> {
    await this.page.goto('/login/index.php', { waitUntil: 'domcontentloaded', timeout: 60_000 });
    await this.waitForLoginForm();
  }

  /**
   * Wait for login form or backoff when nginx rate-limits /login (5 req/min).
   */
  async waitForLoginForm(): Promise<void> {
    const loginForm = this.page.locator('.loginform, .ut-login-form');
    const maxAttempts = 5;

    for (let attempt = 0; attempt < maxAttempts; attempt++) {
      const bodyText = (await this.page.locator('body').innerText().catch(() => '')).toLowerCase();
      const rateLimited = bodyText.includes('503 service temporarily unavailable')
        || bodyText.includes('too many requests');
      const dbError = bodyText.includes('error reading from database');

      if (rateLimited || dbError) {
        if (attempt < maxAttempts - 1) {
          await this.page.waitForTimeout(rateLimited ? 65_000 : 20_000);
          await this.page.goto('/login/index.php', { waitUntil: 'domcontentloaded', timeout: 60_000 });
          continue;
        }
        throw new Error(
          dbError
            ? 'Moodle login unavailable: Error reading from database (check PgBouncer/Redis on origin).'
            : 'Moodle login rate-limited (nginx 5 req/min on /login/index.php).',
        );
      }

      await expect(loginForm).toBeVisible({ timeout: 15_000 });
      return;
    }
  }

  async login(email: string, password: string): Promise<void> {
    await this.gotoLogin();
    await this.page.locator('#username').fill(email);
    await this.page.locator('#password').fill(password);
    await this.page.locator('#loginbtn').click();
    await this.page.waitForURL((url) => !url.pathname.endsWith('/login/index.php'), {
      timeout: 60_000,
      waitUntil: 'domcontentloaded',
    });
  }

  async logout(): Promise<void> {
    const userMenu = this.page.locator('[data-region="usermenu"]');
    if (await userMenu.isVisible()) {
      await userMenu.click();
      await this.page.getByRole('menuitem', { name: /log out/i }).click();
    } else {
      await this.page.goto('/login/logout.php?sesskey=skip');
    }
    await expect(this.page.locator('.loginform')).toBeVisible({ timeout: 15_000 });
  }

  async expectDashboard(): Promise<void> {
    await expect(this.page).toHaveURL(/\/my\//);
    await expect(
      this.page.locator('#page-my-index, #page-my-dashboard').first(),
    ).toBeVisible({ timeout: 15_000 });
  }
}
