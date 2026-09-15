import { expect, type Locator, type Page } from '@playwright/test';

/**
 * The #signupPopup overlay is included on every page via the shared header
 * (App/Views/Partials/Header.php). This is the ONLY working signup entry
 * point — see the standalone /signup page caveat in
 * qa/tests/auth/signup.spec.ts.
 */
export class SignupPopup {
  readonly root: Locator;
  readonly username: Locator;
  readonly email: Locator;
  readonly password: Locator;
  readonly confirmPassword: Locator;
  readonly terms: Locator;
  readonly submit: Locator;
  readonly message: Locator;

  constructor(private readonly page: Page) {
    this.root = page.locator('#signupPopup');
    this.username = this.root.locator('#name');
    this.email = this.root.locator('#email');
    this.password = this.root.locator('#password');
    this.confirmPassword = this.root.locator('#confirm-password');
    this.terms = this.root.locator('#terms');
    this.submit = this.root.locator('button[type="submit"]');
    this.message = this.root.locator('#signup-error-message');
  }

  async openFromNav(): Promise<void> {
    await this.page.getByRole('button', { name: 'Sign up' }).first().click();
    await expect(this.root).toBeVisible();
  }

  async signUp(input: { username: string; email: string; password: string }): Promise<void> {
    await this.username.fill(input.username);
    await this.email.fill(input.email);
    await this.password.fill(input.password);
    await this.confirmPassword.fill(input.password);
    await this.terms.check();
    await this.submit.click();
  }
}
