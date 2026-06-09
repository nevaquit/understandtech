import { defineConfig, devices } from '@playwright/test';
import fs from 'fs';
import path from 'path';

/** Load tests/e2e/.env when present (gitignored; never committed). */
function loadDotEnv(): void {
  const envPath = path.join(__dirname, '.env');
  if (!fs.existsSync(envPath)) {
    return;
  }
  for (const line of fs.readFileSync(envPath, 'utf8').split('\n')) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#') || !trimmed.includes('=')) {
      continue;
    }
    const eq = trimmed.indexOf('=');
    const key = trimmed.slice(0, eq);
    const value = trimmed.slice(eq + 1);
    if (!process.env[key]) {
      process.env[key] = value;
    }
  }
}

loadDotEnv();

/** Staging VM user from setup-e2e-test-user-vm.sh / post-deploy-stabilize (CI only). */
if (process.env.CI === 'true') {
  if (!process.env.STAGING_TEST_USER_EMAIL) {
    process.env.STAGING_TEST_USER_EMAIL = process.env.MOODLE_E2E_USER || 'e2etest';
  }
  if (!process.env.STAGING_TEST_USER_PASSWORD) {
    process.env.STAGING_TEST_USER_PASSWORD = process.env.MOODLE_E2E_PASS || 'UtE2eTest2026Secure';
  }
}

/** Moodle wwwroot path is /learn on staging and production marketing hosts. */
function normalizeMoodleBaseUrl(raw: string | undefined): string {
  const fallback = 'https://staging.understandtech.app/learn';
  const trimmed = (raw ?? fallback).replace(/\/$/, '');
  if (trimmed.endsWith('/learn')) {
    return trimmed;
  }
  if (/understandtech\.app$/i.test(trimmed) || /staging\.understandtech\.app$/i.test(trimmed)) {
    return `${trimmed}/learn`;
  }
  return trimmed;
}

const baseURL = normalizeMoodleBaseUrl(
  process.env.STAGING_URL ?? process.env.BASE_URL,
);

const authFile = path.join(__dirname, '.auth', 'student.json');

export default defineConfig({
  testDir: '.',
  testMatch: '**/*.spec.ts',
  timeout: 60_000,
  retries: process.env.CI ? 2 : 0,
  workers: 1,
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  reporter: [['html', { open: 'never' }], ['list']],
  use: {
    baseURL,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  projects: [
    { name: 'setup', testMatch: /auth\.setup\.ts/ },
    {
      name: 'chromium',
      testIgnore: [/auth\.spec\.ts/, /payment-flow\.spec\.ts/],
      use: {
        ...devices['Desktop Chrome'],
        storageState: authFile,
      },
      dependencies: ['setup'],
    },
    {
      name: 'chromium-auth',
      testMatch: /auth\.spec\.ts/,
      use: { ...devices['Desktop Chrome'] },
      dependencies: ['chromium'],
    },
    {
      name: 'firefox',
      testIgnore: [/auth\.spec\.ts/, /payment-flow\.spec\.ts/],
      use: {
        ...devices['Desktop Firefox'],
        storageState: authFile,
      },
      dependencies: ['setup'],
    },
    ...(process.platform === 'darwin'
      ? [{
          name: 'webkit',
          testIgnore: [/auth\.spec\.ts/, /payment-flow\.spec\.ts/],
          use: {
            ...devices['Desktop Safari'],
            storageState: authFile,
          },
          dependencies: ['setup'],
        }]
      : []),
    ...(process.env.STRIPE_TEST === '1' || process.env.STRIPE_TEST === 'true'
      ? [{
          name: 'chromium-stripe',
          testMatch: /payment-flow\.spec\.ts/,
          use: {
            ...devices['Desktop Chrome'],
            storageState: authFile,
          },
          dependencies: ['setup'],
        }]
      : []),
  ],
});
