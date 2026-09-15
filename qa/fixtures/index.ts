import path from 'node:path';
import { test as base, expect, type APIRequestContext, type Page } from '@playwright/test';
import { createBookViaApi, deleteBookViaApi, loginAsAdmin, setBookVisibility } from '../support/api';
import { requireEnv } from '../support/env';
import { buildTestUser, uniqueBookTitle, type TestUser } from '../support/testData';
import { HomePage } from '../pages/HomePage';

const SAMPLE_PDF = path.join(__dirname, '..', 'fixtures', 'files', 'sample.pdf');

/**
 * Signs up a user through the real API (not the UI — the signup *flow* itself
 * has its own dedicated spec) so other specs can assume a valid account
 * without re-testing signup every time.
 *
 * There's no self-service "delete my account" API, so these accounts are not
 * cleaned up afterwards — they're tagged with the e2e.e-lib.test domain
 * (see support/testData.ts) so they're easy to identify and purge separately
 * from the app/database side if that's ever needed. The suite itself only
 * ever talks to the app's HTTP/UI surface, never the database.
 */
async function registerViaApi(request: APIRequestContext, user: TestUser): Promise<void> {
  const response = await request.post('/api/v1/signup', {
    data: { username: user.username, email: user.email, password: user.password },
  });
  expect(response.ok(), `signup API setup call failed: ${await response.text()}`).toBeTruthy();
}

/** Logs in through the real login popup and waits for the post-login reload to settle. */
async function loginViaUi(page: Page, credentials: { email: string; password: string }): Promise<void> {
  const home = new HomePage(page);
  await home.open();
  await home.header.loginPopup.openFromNav();
  await home.header.loginPopup.login(credentials.email, credentials.password);
  await expect(page.locator('#profileDropdown')).toBeVisible();
}

interface AdminCredentials {
  email: string;
  password: string;
}

interface Fixtures {
  registeredUser: TestUser;
  adminCredentials: AdminCredentials;
  authenticatedPage: Page;
  adminPage: Page;
  seededBook: { id: string; title: string };
}

export const test = base.extend<Fixtures>({
  registeredUser: async ({ request }, use) => {
    const user = buildTestUser();
    await registerViaApi(request, user);
    await use(user);
  },

  // The app has no self-service way to become an admin (by design — see
  // AuthenticatedUser::isAdmin()), so this is not something the suite can set
  // up for itself. Provision one out-of-band and point the suite at it:
  //   composer admin:create -- <email> <password>
  //   QA_ADMIN_EMAIL=<email> QA_ADMIN_PASSWORD=<password> npm run test:e2e
  adminCredentials: async ({}, use) => {
    const email = requireEnv(
      'QA_ADMIN_EMAIL',
      "Provision an admin first: composer admin:create -- <email> <password>, then set QA_ADMIN_EMAIL/QA_ADMIN_PASSWORD.",
    );
    const password = requireEnv(
      'QA_ADMIN_PASSWORD',
      "Provision an admin first: composer admin:create -- <email> <password>, then set QA_ADMIN_EMAIL/QA_ADMIN_PASSWORD.",
    );
    await use({ email, password });
  },

  authenticatedPage: async ({ page, registeredUser }, use) => {
    await loginViaUi(page, registeredUser);
    await use(page);
  },

  adminPage: async ({ page, adminCredentials }, use) => {
    await loginViaUi(page, adminCredentials);
    await use(page);
  },

  // Creates a book through the real /api/v1/books upload + status/featured
  // toggle endpoints — the same ones the dashboard UI uses — rather than
  // inserting into MongoDB directly.
  seededBook: async ({ request, adminCredentials }, use) => {
    const token = await loginAsAdmin(request, adminCredentials.email, adminCredentials.password);
    const title = uniqueBookTitle();
    const id = await createBookViaApi(request, token, { title, pdfPath: SAMPLE_PDF });
    await setBookVisibility(request, token, id, { status: 'public', featured: true });

    await use({ id, title });

    await deleteBookViaApi(request, token, id);
  },
});

export { expect } from '@playwright/test';
