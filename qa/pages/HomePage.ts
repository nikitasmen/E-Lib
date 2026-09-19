import type { Locator, Page } from '@playwright/test';
import { BasePage } from './BasePage';
import { HeaderComponent } from './HeaderComponent';

export class HomePage extends BasePage {
  readonly header: HeaderComponent;
  readonly featuredSection: Locator;
  readonly featuredLoading: Locator;
  readonly featuredError: Locator;
  readonly featuredGrid: Locator;

  constructor(page: Page) {
    super(page);
    this.header = new HeaderComponent(page);
    this.featuredSection = page.getByTestId('featured-section');
    this.featuredLoading = page.getByTestId('featured-loading');
    this.featuredError = page.getByTestId('featured-error');
    this.featuredGrid = page.getByTestId('featured-grid');
  }

  async open(): Promise<void> {
    await this.goto('/');
  }

  bookCardById(bookId: string): Locator {
    return this.page.getByTestId(`book-card-${bookId}`);
  }
}
