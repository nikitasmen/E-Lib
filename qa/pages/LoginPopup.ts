import { expect, type Locator, type Page } from '@playwright/test';

/**
 * The #loginPopup overlay is included on every page via the shared header
 * (App/Views/Partials/Header.php) — it's not a route of its own.
 */
export class LoginPopup {
  readonly root: Locator;
  readonly email: Locator;
  readonly password: Locator;
  readonly rememberMe: Locator;
  readonly submit: Locator;
  readonly errorMessage: Locator;

  constructor(private readonly page: Page) {
    this.root = page.locator('#loginPopup');
    this.email = this.root.locator('#login-email');
    this.password = this.root.locator('#login-password');
    this.rememberMe = this.root.locator('#remember-me');
    this.submit = this.root.locator('#login-submit');
    this.errorMessage = this.root.locator('#error-message');
  }

  async openFromNav(): Promise<void> {
    await this.page.getByRole('button', { name: 'Log in' }).first().click();
    await expect(this.root).toBeVisible();
  }

  async login(email: string, password: string): Promise<void> {
    await this.email.fill(email);
    await this.password.fill(password);
    await this.submit.click();
  }
}
