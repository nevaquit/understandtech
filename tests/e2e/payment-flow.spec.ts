import { test, expect } from '@playwright/test';
import { getStudentCredentials } from './fixtures/test-user';

/**
 * Stripe checkout E2E — skipped unless STRIPE_TEST=1 and course enrolment on payment is configured.
 *
 * Requires:
 *   STRIPE_TEST=1
 *   STAGING_TEST_USER_EMAIL / STAGING_TEST_USER_PASSWORD
 *   E2E_PAID_COURSE_PATH — course with enrol_fee + paygw_stripe payment account linked
 *
 * Test card (Stripe test mode): 4242 4242 4242 4242, any future expiry, any CVC.
 */
function stripeTestEnabled(): boolean {
  return process.env.STRIPE_TEST === '1' || process.env.STRIPE_TEST === 'true';
}

function getPaidCoursePath(): string | null {
  const path = process.env.E2E_PAID_COURSE_PATH?.trim();
  return path || null;
}

test.describe('Stripe payment flow', () => {
  test.beforeEach(() => {
    test.skip(!stripeTestEnabled(), 'Set STRIPE_TEST=1 after Stripe test keys and paid course are configured');
    test.skip(!getStudentCredentials(), 'STAGING_TEST_USER_EMAIL / STAGING_TEST_USER_PASSWORD not set');
    test.skip(!getPaidCoursePath(), 'Set E2E_PAID_COURSE_PATH to a course with Enrolment on payment');
  });

  test('guest sees enrolment on payment option', async ({ browser }) => {
    const coursePath = getPaidCoursePath()!;
    const context = await browser.newContext();
    const page = await context.newPage();
    await page.goto(coursePath, { waitUntil: 'load', timeout: 60_000 });
    await expect(page.getByRole('link', { name: /log in/i }).first()).toBeVisible({ timeout: 15_000 });
    await context.close();
  });

  test('authenticated user can start Stripe checkout', async ({ page }) => {
    const coursePath = getPaidCoursePath()!;
    await page.goto(coursePath, { waitUntil: 'load', timeout: 60_000 });
    // TODO: click Enrolment on payment → proceed to Stripe Checkout when payment account is live.
    test.fixme(true, 'Implement after paygw_stripe payment account + enrol_fee on a test course');
    await expect(page).toHaveURL(/understandtech\.app/);
  });
});
