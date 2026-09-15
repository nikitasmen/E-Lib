import { buildTestUser } from '../../support/testData';
import { expect, test } from '../../fixtures';
import { HomePage } from '../../pages/HomePage';

// Accounts created below are not deleted afterwards — there's no self-service
// "delete my account" API, and this suite only ever talks to the app's HTTP/UI
// surface, never the database directly. They're tagged with the
// e2e.e-lib.test domain (support/testData.ts) so they're easy to spot.

// Signup has two entry points that are NOT equivalent:
//  - the #signupPopup overlay (opened via the "Sign up" nav button), included on
//    every page via the shared header — this is the one with a working submit handler.
//  - the standalone /signup page (App/Views/signup.php), which renders the header
//    (bringing in a *second*, hidden copy of #signupPopup > #signupForm) AND its
//    own *visible* #signupForm directly in the body. Because both instances share
//    the id "signupForm", `document.getElementById('signupForm')` in each
//    inline <script> resolves to the SAME (first, hidden) node — so the visible
//    form on /signup never gets its submit listener attached. See
//    signup.spec.ts's dedicated regression test below.

test.describe('Signup (popup)', () => {
  test('a new user can sign up and is prompted to log in', async ({ page }) => {
    const user = buildTestUser();
    const home = new HomePage(page);
    await home.open();
    const { signupPopup } = home.header;

    await signupPopup.openFromNav();
    await signupPopup.signUp(user);

    await expect(signupPopup.message).toContainText('Account created successfully');
    // Signup succeeds -> the app redirects to the login popup after a short delay.
    await expect(page).toHaveURL(/\/\?showLogin=1/, { timeout: 5_000 });
    await expect(page.locator('#loginPopup')).toBeVisible();
  });

  test('rejects mismatched passwords before calling the API', async ({ page }) => {
    const user = buildTestUser();
    const home = new HomePage(page);
    await home.open();
    const { signupPopup } = home.header;

    await signupPopup.openFromNav();
    await signupPopup.username.fill(user.username);
    await signupPopup.email.fill(user.email);
    await signupPopup.password.fill(user.password);
    await signupPopup.confirmPassword.fill(`different-${user.password}`);
    await signupPopup.terms.check();
    await signupPopup.submit.click();

    await expect(signupPopup.message).toContainText('Passwords do not match');
    // Client-side validation should short-circuit — no account should be created.
  });

  test('requires agreeing to the Terms of Service', async ({ page }) => {
    const user = buildTestUser();
    const home = new HomePage(page);
    await home.open();
    const { signupPopup } = home.header;

    await signupPopup.openFromNav();
    await signupPopup.username.fill(user.username);
    await signupPopup.email.fill(user.email);
    await signupPopup.password.fill(user.password);
    await signupPopup.confirmPassword.fill(user.password);
    // The checkbox has the native "required" attribute, so the browser blocks
    // the submit event before the app's own JS (which also checks it) ever runs.
    await signupPopup.submit.click();

    const isValid = await signupPopup.terms.evaluate((el: HTMLInputElement) => el.validity.valid);
    expect(isValid).toBe(false);
    await expect(signupPopup.root).toBeVisible(); // popup stays open — nothing was submitted
  });
});

test.describe('Signup (standalone /signup page)', () => {
  test.fail(
    true,
    'Known bug: /signup renders the shared header (a second, hidden #signupForm ' +
      "inside #signupPopup) plus its own visible #signupForm. Both share the same " +
      "id, so both inline scripts' document.getElementById('signupForm') bind to " +
      'the first (hidden) node — the visible form on this page has no submit ' +
      'handler and falls back to a native (unhandled) HTML form POST.',
  );

  test('the page\'s own visible form can create an account', async ({ page }) => {
    const user = buildTestUser();
    await page.goto('/signup');

    const visibleForm = page.locator('#signupForm:visible');
    await visibleForm.locator('#name').fill(user.username);
    await visibleForm.locator('#email').fill(user.email);
    await visibleForm.locator('#password').fill(user.password);
    await visibleForm.locator('#confirm-password').fill(user.password);
    await visibleForm.locator('#terms').check();
    await visibleForm.getByRole('button', { name: 'Sign Up' }).click();

    await expect(page.locator('#signup-error-message:visible')).toContainText(
      'Account created successfully',
    );
  });
});
