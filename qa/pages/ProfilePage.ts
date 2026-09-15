import type { Locator, Page } from '@playwright/test';
import { BasePage } from './BasePage';
import { HeaderComponent } from './HeaderComponent';

export class ProfilePage extends BasePage {
  readonly header: HeaderComponent;
  readonly usernameHeading: Locator;
  readonly emailText: Locator;
  readonly menuTrigger: Locator;
  readonly editUsernameMenuItem: Locator;
  readonly changePasswordMenuItem: Locator;

  readonly editUsernameModal: Locator;
  readonly newUsernameInput: Locator;
  readonly saveUsernameButton: Locator;
  readonly usernameError: Locator;

  readonly changePasswordModal: Locator;
  readonly currentPasswordInput: Locator;
  readonly newPasswordInput: Locator;
  readonly confirmNewPasswordInput: Locator;
  readonly changePasswordSubmit: Locator;
  readonly changePasswordFeedback: Locator;

  constructor(page: Page) {
    super(page);
    this.header = new HeaderComponent(page);
    this.usernameHeading = page.locator('#current-username');
    this.emailText = page.locator('#user-email');
    this.menuTrigger = page.locator('#profile-menu-trigger');
    this.editUsernameMenuItem = page.locator('#menu-edit-username');
    this.changePasswordMenuItem = page.locator('#menu-change-password');

    this.editUsernameModal = page.locator('#editUsernameModal');
    this.newUsernameInput = page.locator('#new-username');
    this.saveUsernameButton = page.locator('#save-username-btn');
    this.usernameError = page.locator('#username-error');

    this.changePasswordModal = page.locator('#changePasswordModal');
    this.currentPasswordInput = page.locator('#current-password');
    this.newPasswordInput = page.locator('#new-password');
    this.confirmNewPasswordInput = page.locator('#confirm-new-password');
    this.changePasswordSubmit = page.locator('#change-password-submit');
    this.changePasswordFeedback = page.locator('#change-password-feedback');
  }

  async open(): Promise<void> {
    await this.goto('/profile');
  }

  async openEditUsername(newUsername: string): Promise<void> {
    await this.menuTrigger.click();
    await this.editUsernameMenuItem.click();
    await this.editUsernameModal.waitFor({ state: 'visible' });
    await this.newUsernameInput.fill(newUsername);
    await this.saveUsernameButton.click();
  }

  async changePassword(current: string, next: string): Promise<void> {
    await this.menuTrigger.click();
    await this.changePasswordMenuItem.click();
    await this.changePasswordModal.waitFor({ state: 'visible' });
    await this.currentPasswordInput.fill(current);
    await this.newPasswordInput.fill(next);
    await this.confirmNewPasswordInput.fill(next);
    await this.changePasswordSubmit.click();
  }
}
