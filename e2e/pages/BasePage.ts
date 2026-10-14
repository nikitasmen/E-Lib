import type { Locator, Page } from '@playwright/test';

export class BasePage {
  /** The app-wide toast stack (frontend/src/components/ToastContainer.vue): success and error messages. */
  readonly toasts: Locator;

  constructor(protected readonly page: Page) {
    this.toasts = page.getByTestId('toasts');
  }
}
