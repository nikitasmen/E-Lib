import { expect, test } from '../../fixtures';
import { DashboardPage } from '../../pages/DashboardPage';

test.describe('Admin dashboard (/admin)', () => {
  test('a guest is redirected to the login page', async ({ page }) => {
    await page.goto('/admin');

    // `requireAdmin` router guard (frontend/src/router/guards.ts).
    await expect(page).toHaveURL(/\/login\?redirect=\/admin/);
  });

  test('a logged-in non-admin is bounced back to the home page', async ({ authenticatedPage }) => {
    await authenticatedPage.goto('/admin');

    await expect(authenticatedPage).toHaveURL(/\/$/);
  });

  test('an admin sees the Manage Books table', async ({ adminPage }) => {
    const dashboard = new DashboardPage(adminPage);
    await dashboard.open();

    await expect(dashboard.heading).toBeVisible();
    await expect(dashboard.table).toBeVisible();
  });

  test('an admin sees a seeded book in the table', async ({ adminPage, seededBook }) => {
    const dashboard = new DashboardPage(adminPage);
    await dashboard.open();

    await expect(dashboard.rowByTitle(seededBook.title)).toBeVisible();
  });
});
