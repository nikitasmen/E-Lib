import { expect, test } from '../../fixtures';
import { DashboardPage } from '../../pages/DashboardPage';

test.describe('Admin dashboard (/dashboard)', () => {
  test('a guest is redirected to the login popup', async ({ page }) => {
    await page.goto('/dashboard');

    await expect(page).toHaveURL(/\/\?showLogin=1&redirect=%2Fdashboard/);
    await expect(page.locator('#loginPopup')).toBeVisible();
  });

  test('a logged-in non-admin is bounced back to the home page', async ({ authenticatedPage }) => {
    await authenticatedPage.goto('/dashboard');

    // Server-side AuthMiddleware only requires *a* session; the admin check
    // (checkAdminAccess() in admin.php) runs client-side and redirects non-admins.
    await expect(authenticatedPage).toHaveURL(/\/$/);
  });

  test('an admin sees the Manage Books table', async ({ adminPage }) => {
    const dashboard = new DashboardPage(adminPage);
    await dashboard.open();

    await expect(dashboard.heading).toBeVisible();
    await expect(dashboard.tableBody).toBeVisible();
  });

  test('an admin sees a seeded book in the table', async ({ adminPage, seededBook }) => {
    const dashboard = new DashboardPage(adminPage);
    await dashboard.open();

    await expect(dashboard.rowByTitle(seededBook.title)).toBeVisible();
  });
});
