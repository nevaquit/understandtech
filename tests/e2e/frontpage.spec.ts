import { test, expect } from '@playwright/test';
import { getStudentCredentials } from './fixtures/test-user';

/** Moodle site home — relative to baseURL (/learn); avoid leading slash (Playwright origin-root trap). */
const frontpagePath = 'index.php?redirect=0';

test.describe('Front page (guest)', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('marketing hero and cert tracks load', async ({ page }) => {
    await page.goto(frontpagePath, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('body.ut-frontpage')).toBeVisible({ timeout: 15_000 });
    await expect(page.locator('#ut-frontpage-top .ut-frontpage-hero')).toBeVisible();
    await expect(page.locator('#ut-tracks')).toBeVisible();
    await expect(page.getByRole('link', { name: /start free audit/i })).toBeVisible();
  });

  test('guest members section prompts sign-in', async ({ page }) => {
    await page.goto(frontpagePath, { waitUntil: 'domcontentloaded' });
    const guestMembers = page.locator('#ut-members.ut-frontpage-members-guest');
    await expect(guestMembers).toBeVisible({ timeout: 15_000 });
    await expect(guestMembers.getByRole('link', { name: /sign in to open members area/i })).toBeVisible();
    await expect(page.locator('#ut-members .ut-member-nav-grid--locked')).toBeVisible();
  });
});

test.describe('Front page (logged-in members hub)', () => {
  test.beforeEach(() => {
    test.skip(!getStudentCredentials(), 'STAGING_TEST_USER_EMAIL / STAGING_TEST_USER_PASSWORD not set');
  });

  test('members navigation grid links to community tools', async ({ page }) => {
    await page.goto(frontpagePath, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('body.ut-frontpage')).toBeVisible({ timeout: 15_000 });

    const membersSection = page.locator('#ut-members.ut-frontpage-members');
    await expect(membersSection).toBeVisible();
    await expect(membersSection.locator('#ut-members-heading')).toContainText(/members area/i);

    const navGrid = membersSection.locator('nav.ut-member-nav-grid');
    await expect(navGrid).toBeVisible();
    const cards = navGrid.locator('a.ut-member-nav-card');
    await expect(cards.first()).toBeVisible();
    expect(await cards.count()).toBeGreaterThanOrEqual(4);

    await expect(navGrid.locator('a.ut-member-nav-card').filter({ hasText: /community/i }).first())
      .toHaveAttribute('href', /community/);
    await expect(navGrid.locator('a.ut-member-nav-card').filter({ hasText: /classroom/i }).first())
      .toHaveAttribute('href', /classroom/);
    await expect(navGrid.locator('a.ut-member-nav-card').filter({ hasText: /calendar/i }).first())
      .toHaveAttribute('href', /calendar/);
  });

  test('open members area anchor scrolls to hub', async ({ page }) => {
    await page.goto(frontpagePath, { waitUntil: 'domcontentloaded' });
    await page.getByRole('link', { name: /open members area/i }).click();
    await expect(page.locator('#ut-members')).toBeInViewport({ timeout: 10_000 });
  });
});
