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
  'https://understandtech.app';

export default defineConfig({
  testDir: '.',
  testMatch: '**/*.spec.ts',
  timeout: 60_000,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
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
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
    ...(process.platform === 'darwin'
      ? [{ name: 'webkit', use: { ...devices['Desktop Safari'] } }]
      : []),
  ],
});
