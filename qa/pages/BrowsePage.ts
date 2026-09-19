import type { Locator, Page } from '@playwright/test';
import { BasePage } from './BasePage';
import { HeaderComponent } from './HeaderComponent';

/** /browse — the public book-browsing grid (frontend/src/views/Browse.vue), fed by GET /api/v1/books/list. */
export class BrowsePage extends BasePage {
  readonly header: HeaderComponent;
  readonly loadingIndicator: Locator;
  readonly emptyMessage: Locator;
  readonly bookCards: Locator;

  constructor(page: Page) {
    super(page);
    this.header = new HeaderComponent(page);
    this.loadingIndicator = page.locator('.browse-page .spinner');
    this.emptyMessage = page.locator('.browse-page > p.text-muted');
    this.bookCards = page.locator('.browse-page .book-card');
  }

  async open(): Promise<void> {
    await this.goto('/browse');
  }

  cardByTitle(title: string): Locator {
    return this.bookCards.filter({ hasText: title });
  }
}
