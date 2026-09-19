import { expect, test } from '../../fixtures';
import { LoginPage } from '../../pages/LoginPage';

// The app has no login popup anymore: "Log in" in the nav is a RouterLink to
// a dedicated, guest-only /login page (frontend/src/views/Login.vue).

test.describe('Login', () => {
  test('the "Log in" nav link opens the login page', async ({ page }) => {
    await page.goto('/');
    await page.locator('.navbar').getByRole('link', { name: 'Log in' }).click();

    await expect(page).toHaveURL(/\/login$/);
    const login = new LoginPage(page);
    await expect(login.email).toBeVisible();
    await expect(login.password).toBeVisible();
    await expect(login.submit).toBeVisible();
  });

  test('an authenticated visitor is redirected away from /login', async ({ authenticatedPage }) => {
    await authenticatedPage.goto('/login');

    // `guestOnly` router guard (frontend/src/router/guards.ts).
    await expect(authenticatedPage).toHaveURL(/\/$/);
  });

  test('shows an error for invalid credentials', async ({ page }) => {
    const login = new LoginPage(page);
    await login.open();

    await login.login('nonexistent-user@example.com', 'wrong-password');

    await expect(login.errorMessage).toBeVisible();
  });

  test('an existing user can log in and sees the account menu', async ({ page, registeredUser }) => {
    const login = new LoginPage(page);
    await login.open();

    await login.login(registeredUser.email, registeredUser.password);

    await expect(page).toHaveURL(/\/$/);
    const nav = page.locator('.navbar');
    await expect(nav.locator('.user-chip')).toBeVisible();
    await expect(nav.locator('.user-chip .username')).toHaveText(registeredUser.username);
  });
});
