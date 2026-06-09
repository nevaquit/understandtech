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

const baseURL =
  process.env.STAGING_URL?.replace(/\/$/, '') ??
  process.env.BASE_URL?.replace(/\/$/, '') ??
  'https://staging.understandtech.app/learn';

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
