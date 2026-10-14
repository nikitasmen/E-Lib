import type { Locator, Page } from '@playwright/test';
import { BasePage } from './BasePage';
import { HeaderComponent } from './HeaderComponent';

/** /search (frontend/src/views/SearchResults.vue), fed by GET /api/v1/catalog (title/author + filters). */
export class SearchResultsPage extends BasePage {
  readonly header: HeaderComponent;
  readonly message: Locator;
  readonly clearFilters: Locator;

  constructor(page: Page) {
    super(page);
    this.header = new HeaderComponent(page);
    this.message = page.getByTestId('search-message');
    this.clearFilters = page.getByTestId('clear-filters');
  }

  async openWithTerm(term: string): Promise<void> {
    await this.page.goto(`/search?q=${encodeURIComponent(term)}`);
  }

  /** A filter option (e.g. facet 'category', value 'Networking'): its checkbox and how many books picking it gives. */
  facetOption(facet: string, value: string): { checkbox: Locator; count: Locator } {
    return {
      checkbox: this.page.getByTestId(`facet-${facet}-${value}`),
      count: this.page.getByTestId(`facet-${facet}-${value}-count`),
    };
  }

  cardById(bookId: string): Locator {
    return this.page.getByTestId(`book-card-${bookId}`);
  }
}
