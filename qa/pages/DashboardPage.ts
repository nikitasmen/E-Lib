import type { Locator, Page } from '@playwright/test';
import { BasePage } from './BasePage';
import { HeaderComponent } from './HeaderComponent';

/**
 * /admin — the admin "Manage Books" console (frontend/src/views/admin/Dashboard.vue),
 * fed by GET /api/v1/books (all statuses). Guarded client-side by the `requireAdmin`
 * router guard (frontend/src/router/guards.ts): an unauthenticated visitor is sent to
 * /login, an authenticated non-admin is bounced to '/'.
 */
export class DashboardPage extends BasePage {
  readonly header: HeaderComponent;
  readonly heading: Locator;
  readonly table: Locator;
  readonly massUploadLink: Locator;

  constructor(page: Page) {
    super(page);
    this.header = new HeaderComponent(page);
    this.heading = page.getByTestId('dashboard-heading');
    this.table = page.getByTestId('books-table');
    this.massUploadLink = page.getByTestId('mass-upload-link');
  }

  async open(): Promise<void> {
    await this.goto('/admin');
  }

  rowById(bookId: string): Locator {
    return this.page.getByTestId(`book-row-${bookId}`);
  }
}
