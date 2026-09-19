import type { Locator, Page } from '@playwright/test';
import { BasePage } from './BasePage';

/**
 * /signup — a dedicated, guest-only page (frontend/src/views/Signup.vue), not
 * a popup. Renders a single SignupForm instance, so (unlike the pre-SPA app)
 * there's no duplicate-id bug to work around here.
 */
export class SignupPage extends BasePage {
  readonly username: Locator;
  readonly email: Locator;
  readonly password: Locator;
  readonly confirmPassword: Locator;
  readonly terms: Locator;
  readonly submit: Locator;
  readonly message: Locator;

  constructor(page: Page) {
    super(page);
    this.username = page.locator('#signup-username');
    this.email = page.locator('#signup-email');
    this.password = page.locator('#signup-password');
    this.confirmPassword = page.locator('#signup-confirm');
    this.terms = page.locator('.terms-check input[type="checkbox"]');
    this.submit = page.locator('.signup-form button[type="submit"]');
    this.message = page.locator('.signup-form .alert');
  }

  async open(): Promise<void> {
    await this.goto('/signup');
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
