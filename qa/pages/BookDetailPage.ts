import type { Locator, Page } from '@playwright/test';
import { BasePage } from './BasePage';
import { HeaderComponent } from './HeaderComponent';

/** /books/:id (frontend/src/views/BookDetail.vue). */
export class BookDetailPage extends BasePage {
  readonly header: HeaderComponent;
  readonly title: Locator;
  readonly previewButton: Locator;
  readonly saveButton: Locator;
  readonly downloadButton: Locator;
  readonly loginHint: Locator;

  constructor(page: Page) {
    super(page);
    this.header = new HeaderComponent(page);
    this.title = page.locator('.info-column h1');
    this.previewButton = page.getByRole('link', { name: 'Online Preview' });
    this.saveButton = page.getByRole('button', { name: /^(Save to Reading List|Saved to List|Saving…)$/ });
    this.downloadButton = page.getByRole('button', { name: /^(Download PDF|Downloading…)$/ });
    this.loginHint = page.locator('.login-hint');
  }

  async openBook(bookId: string): Promise<void> {
    await this.goto(`/books/${bookId}`);
    await this.title.waitFor({ state: 'visible' });
  }
}
