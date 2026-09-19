import type { Locator, Page } from '@playwright/test';
import { BasePage } from './BasePage';
import { HeaderComponent } from './HeaderComponent';

/** /browse — the public book-browsing grid (frontend/src/views/Browse.vue), fed by GET /api/v1/books/list. */
export class BrowsePage extends BasePage {
  readonly header: HeaderComponent;
  readonly loadingIndicator: Locator;
  readonly emptyMessage: Locator;
  readonly grid: Locator;

  constructor(page: Page) {
    super(page);
    this.header = new HeaderComponent(page);
    this.loadingIndicator = page.getByTestId('browse-loading');
    this.emptyMessage = page.getByTestId('browse-error');
    this.grid = page.getByTestId('browse-grid');
  }

  async open(): Promise<void> {
    await this.goto('/browse');
  }

  cardById(bookId: string): Locator {
    return this.page.getByTestId(`book-card-${bookId}`);
  }
}
