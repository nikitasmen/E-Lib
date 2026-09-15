import type { Locator, Page } from '@playwright/test';
import { BasePage } from './BasePage';
import { HeaderComponent } from './HeaderComponent';

export class SearchResultsPage extends BasePage {
  readonly header: HeaderComponent;
  readonly loadingIndicator: Locator;
  readonly resultsContainer: Locator;

  constructor(page: Page) {
    super(page);
    this.header = new HeaderComponent(page);
    this.loadingIndicator = page.locator('#loading-indicator');
    this.resultsContainer = page.locator('#search-results');
  }

  async openWithTitle(title: string): Promise<void> {
    await this.goto(`/search_results?title=${encodeURIComponent(title)}`);
  }
}
