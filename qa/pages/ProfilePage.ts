import type { Locator, Page } from '@playwright/test';
import { BasePage } from './BasePage';
import { HeaderComponent } from './HeaderComponent';

/**
 * /profile (frontend/src/views/Profile.vue) — a tabbed page, not the old
 * dropdown-menu-triggered modals. Username/password editing live inline
 * under the "Account" tab.
 */
export class ProfilePage extends BasePage {
  readonly header: HeaderComponent;
  readonly usernameHeading: Locator;
  readonly emailText: Locator;
  readonly accountTab: Locator;

  readonly usernameInput: Locator;
  readonly saveUsernameButton: Locator;
  readonly usernameError: Locator;

  readonly currentPasswordInput: Locator;
  readonly newPasswordInput: Locator;
  readonly confirmNewPasswordInput: Locator;
  readonly updatePasswordButton: Locator;
  readonly changePasswordFeedback: Locator;

  constructor(page: Page) {
    super(page);
    this.header = new HeaderComponent(page);
    this.usernameHeading = page.locator('.profile-header h1');
    this.emailText = page.locator('.profile-header .text-muted').first();
    this.accountTab = page.getByRole('button', { name: 'Account' });

    const usernameCard = page.locator('.account-card').filter({ hasText: 'Edit username' });
    this.usernameInput = usernameCard.locator('#profile-username');
    this.saveUsernameButton = usernameCard.getByRole('button', { name: /^(Save|Saving…)$/ });
    this.usernameError = usernameCard.locator('.alert-danger');

    const passwordCard = page.locator('.account-card').filter({ hasText: 'Change password' });
    this.currentPasswordInput = passwordCard.locator('#current-password');
    this.newPasswordInput = passwordCard.locator('#new-password');
    this.confirmNewPasswordInput = passwordCard.locator('#confirm-new-password');
    this.updatePasswordButton = passwordCard.getByRole('button', { name: /^(Update password|Updating…)$/ });
    this.changePasswordFeedback = passwordCard.locator('.alert');
  }

  async open(): Promise<void> {
    await this.goto('/profile');
  }

  async openEditUsername(newUsername: string): Promise<void> {
    await this.accountTab.click();
    await this.usernameInput.fill(newUsername);
    await this.saveUsernameButton.click();
  }

  async changePassword(current: string, next: string): Promise<void> {
    await this.accountTab.click();
    await this.currentPasswordInput.fill(current);
    await this.newPasswordInput.fill(next);
    await this.confirmNewPasswordInput.fill(next);
    await this.updatePasswordButton.click();
  }
}
