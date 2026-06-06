import { test as setup, expect } from '@playwright/test';
import { getStudentCredentials, TestUser } from './fixtures/test-user';
import fs from 'fs';
import path from 'path';

const authFile = path.join(__dirname, '.auth', 'student.json');

setup('authenticate student', async ({ page }) => {
  setup.setTimeout(180_000);
  const creds = getStudentCredentials();
  if (!creds) {
    setup.skip();
    return;
  }

  fs.mkdirSync(path.dirname(authFile), { recursive: true });
  if (fs.existsSync(authFile)) {
    const ageMs = Date.now() - fs.statSync(authFile).mtimeMs;
    if (ageMs < 30 * 60_000) {
      return;
    }
  }

  const user = new TestUser(page);
  await user.login(creds.email, creds.password);
  await page.goto('/my/', { waitUntil: 'domcontentloaded' });
  await expect(page).toHaveURL(/\/my\//);

  await page.context().storageState({ path: authFile });
});
