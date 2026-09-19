import { expect, test } from '../../fixtures';
import { DashboardPage } from '../../pages/DashboardPage';

test.describe('Admin dashboard (/admin)', () => {
  test('a guest is redirected to the login page', async ({ page }) => {
    await page.goto('/admin');

    // `requireAdmin` router guard (frontend/src/router/guards.ts).
    await expect(page).toHaveURL('/login?redirect=/admin');
  });

  test('a logged-in non-admin is bounced back to the home page', async ({ authenticatedPage }) => {
    await authenticatedPage.goto('/admin');

    // `requireAdmin` router guard should block the page outright — check both
    // the URL and that the "Manage Books" table never rendered, not just
    // where we ended up.
    await expect(authenticatedPage).toHaveURL('/');
    await expect(authenticatedPage.getByTestId('dashboard-heading')).toHaveCount(0);
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

    await expect(dashboard.rowById(seededBook.id)).toBeVisible();
  });
});
