import { expect, test } from '../../fixtures';
import { HomePage } from '../../pages/HomePage';

// The app has no dedicated login page: "Log in" opens a popup (#loginPopup) that's
// included on every page via the shared header, and GET /login just redirects to
// '/?showLogin=1' to trigger the same popup (see PageController::login()).

test.describe('Login', () => {
  test('the "Log in" nav button opens the login popup', async ({ page }) => {
    const home = new HomePage(page);
    await home.open();
    const { loginPopup } = home.header;

    await loginPopup.openFromNav();

    await expect(loginPopup.email).toBeVisible();
    await expect(loginPopup.password).toBeVisible();
    await expect(loginPopup.submit).toBeVisible();
  });

  test('GET /login redirects to the home page with the popup open', async ({ page }) => {
    await page.goto('/login');

    await expect(page).toHaveURL(/\/\?showLogin=1/);
    await expect(page.locator('#loginPopup')).toBeVisible();
  });

  test('shows an error for invalid credentials', async ({ page }) => {
    const home = new HomePage(page);
    await home.open();
    const { loginPopup } = home.header;

    await loginPopup.openFromNav();
    await loginPopup.login('nonexistent-user@example.com', 'wrong-password');

    await expect(loginPopup.errorMessage).toBeVisible();
  });

  test('an existing user can log in and sees the account menu', async ({ page, registeredUser }) => {
    const home = new HomePage(page);
    await home.open();
    const { loginPopup } = home.header;

    await loginPopup.openFromNav();
    await loginPopup.login(registeredUser.email, registeredUser.password);

    // Login triggers a full-page redirect back to '/' on success.
    await expect(page.locator('#profileDropdown')).toBeVisible();
    await expect(page.locator('#userDropdown')).toContainText(registeredUser.username);
  });
});
