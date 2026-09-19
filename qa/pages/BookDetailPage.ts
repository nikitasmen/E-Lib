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
  readonly downloadDisabledButton: Locator;
  readonly loginHint: Locator;

  constructor(page: Page) {
    super(page);
    this.header = new HeaderComponent(page);
    this.title = page.getByTestId('book-title');
    this.previewButton = page.getByTestId('preview-button');
    this.saveButton = page.getByTestId('save-button');
    this.downloadButton = page.getByTestId('download-button');
    this.downloadDisabledButton = page.getByTestId('download-disabled-button');
    this.loginHint = page.getByTestId('login-hint');
  }

  async openBook(bookId: string): Promise<void> {
    await this.page.goto(`/books/${bookId}`);
    await this.title.waitFor({ state: 'visible' });
  }
}
