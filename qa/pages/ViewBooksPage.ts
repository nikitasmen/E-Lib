import type { Locator, Page } from '@playwright/test';
import { BasePage } from './BasePage';
import { HeaderComponent } from './HeaderComponent';

/** /view-books — the public book-browsing grid (App/Views/view_books.php), fed by GET /api/v1/books/list. */
export class ViewBooksPage extends BasePage {
  readonly header: HeaderComponent;
  readonly loadingIndicator: Locator;
  readonly noBooksMessage: Locator;
  readonly booksList: Locator;
  readonly bookCards: Locator;

  constructor(page: Page) {
    super(page);
    this.header = new HeaderComponent(page);
    this.loadingIndicator = page.locator('#loading-indicator');
    this.noBooksMessage = page.locator('#no-books');
    this.booksList = page.locator('#books-list');
    this.bookCards = this.booksList.locator('.book-card');
  }

  async open(): Promise<void> {
    await this.goto('/view-books');
  }

  cardByTitle(title: string): Locator {
    return this.bookCards.filter({ hasText: title });
  }
}
