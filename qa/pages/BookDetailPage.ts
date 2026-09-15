import type { Locator, Page } from '@playwright/test';
import { BasePage } from './BasePage';
import { HeaderComponent } from './HeaderComponent';

export class BookDetailPage extends BasePage {
  readonly header: HeaderComponent;
  readonly loadingIndicator: Locator;
  readonly details: Locator;
  readonly title: Locator;
  readonly previewButton: Locator;
  readonly saveButton: Locator;
  readonly downloadButton: Locator;

  constructor(page: Page) {
    super(page);
    this.header = new HeaderComponent(page);
    this.loadingIndicator = page.locator('#loading-indicator');
    this.details = page.locator('#book-details');
    this.title = this.details.locator('h1');
    this.previewButton = page.locator('#previewBtn');
    this.saveButton = page.locator('#saveBtn');
    this.downloadButton = page.locator('#downloadBtn');
  }

  async openBook(bookId: string): Promise<void> {
    await this.goto(`/book/${bookId}`);
    await this.details.waitFor({ state: 'visible' });
  }
}
