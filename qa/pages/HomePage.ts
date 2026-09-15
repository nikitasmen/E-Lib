import type { Locator, Page } from '@playwright/test';
import { BasePage } from './BasePage';
import { HeaderComponent } from './HeaderComponent';

export class HomePage extends BasePage {
  readonly header: HeaderComponent;
  readonly booksGrid: Locator;
  readonly bookCards: Locator;

  constructor(page: Page) {
    super(page);
    this.header = new HeaderComponent(page);
    this.booksGrid = page.locator('#booksGrid');
    this.bookCards = this.booksGrid.locator('.card');
  }

  async open(): Promise<void> {
    await this.goto('/');
  }

  bookCardByTitle(title: string): Locator {
    return this.bookCards.filter({ hasText: title });
  }
}
