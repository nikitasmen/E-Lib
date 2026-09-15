import { test, expect } from '@playwright/test';

// The app has no dedicated login page: "Log in" opens a popup (#loginPopup) that's
// included on every page via the shared header, and GET /login just redirects to
// '/?showLogin=1' to trigger the same popup (see PageController::login()).

test.describe('Login', () => {
  test('the "Log in" nav button opens the login popup', async ({ page }) => {
    await page.goto('/');

    await page.getByRole('button', { name: 'Log in' }).first().click();

    const popup = page.locator('#loginPopup');
    await expect(popup).toBeVisible();
    await expect(popup.locator('#login-email')).toBeVisible();
    await expect(popup.locator('#login-password')).toBeVisible();
    await expect(popup.locator('#login-submit')).toBeVisible();
  });

  test('GET /login redirects to the home page with the popup open', async ({ page }) => {
    await page.goto('/login');

    await expect(page).toHaveURL(/\/\?showLogin=1/);
    await expect(page.locator('#loginPopup')).toBeVisible();
  });

  test('shows an error for invalid credentials', async ({ page }) => {
    await page.goto('/');
    await page.getByRole('button', { name: 'Log in' }).first().click();

    const popup = page.locator('#loginPopup');
    await popup.locator('#login-email').fill('nonexistent-user@example.com');
    await popup.locator('#login-password').fill('wrong-password');
    await popup.locator('#login-submit').click();

    await expect(popup.locator('#error-message')).toBeVisible();
  });
});
