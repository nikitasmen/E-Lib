import type { Locator, Page } from '@playwright/test';
import { BasePage } from './BasePage';
import { HeaderComponent } from './HeaderComponent';

export class HomePage extends BasePage {
  readonly header: HeaderComponent;
  readonly featuredSection: Locator;

  constructor(page: Page) {
    super(page);
    this.header = new HeaderComponent(page);
    this.featuredSection = page.getByTestId('featured-section');
  }

  async open(): Promise<void> {
    await this.page.goto('/');
  }

  bookCardById(bookId: string): Locator {
    return this.page.getByTestId(`book-card-${bookId}`);
  }
}
