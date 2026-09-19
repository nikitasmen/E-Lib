import type { Locator, Page } from '@playwright/test';
import { BasePage } from './BasePage';

/**
 * /login — a dedicated, guest-only page (frontend/src/views/Login.vue), not a
 * popup. The `guestOnly` router guard bounces an already-authenticated
 * visitor to '/' before this ever renders.
 */
export class LoginPage extends BasePage {
  readonly email: Locator;
  readonly password: Locator;
  readonly submit: Locator;
  readonly errorMessage: Locator;

  constructor(page: Page) {
    super(page);
    this.email = page.locator('#login-email');
    this.password = page.locator('#login-password');
    this.submit = page.locator('.login-form button[type="submit"]');
    this.errorMessage = page.locator('.login-form .alert-danger');
  }

  async open(): Promise<void> {
    await this.goto('/login');
  }

  async login(email: string, password: string): Promise<void> {
    await this.email.fill(email);
    await this.password.fill(password);
    await this.submit.click();
  }
}
