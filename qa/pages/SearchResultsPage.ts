import type { Locator, Page } from '@playwright/test';
import { BasePage } from './BasePage';
import { HeaderComponent } from './HeaderComponent';

/** /search (frontend/src/views/SearchResults.vue), fed by GET /api/v1/search/:term. */
export class SearchResultsPage extends BasePage {
  readonly header: HeaderComponent;
  readonly loadingIndicator: Locator;
  readonly resultsGrid: Locator;
  readonly message: Locator;

  constructor(page: Page) {
    super(page);
    this.header = new HeaderComponent(page);
    this.loadingIndicator = page.locator('.search-page .spinner');
    this.resultsGrid = page.locator('.search-page .grid-books');
    this.message = page.locator('.search-page > p.text-muted');
  }

  async openWithTerm(term: string): Promise<void> {
    await this.goto(`/search?q=${encodeURIComponent(term)}`);
  }
}
