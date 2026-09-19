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
    this.heading = page.getByRole('heading', { name: 'Manage Books' });
    this.table = page.locator('.admin-table');
    this.massUploadLink = page.getByRole('link', { name: 'Mass Upload PDFs' });
  }

  async open(): Promise<void> {
    await this.goto('/admin');
  }

  rowByTitle(title: string): Locator {
    return this.table.locator('tbody tr').filter({ hasText: title });
  }
}
