import type { Locator, Page } from '@playwright/test';
import { BasePage } from './BasePage';
import { HeaderComponent } from './HeaderComponent';

/**
 * /dashboard — the admin "Manage Books" console (App/Views/admin.php +
 * Components/ViewBooks.php), fed by GET /api/v1/books (all statuses).
 * Server-side, the route only requires a logged-in session (AuthMiddleware);
 * the admin-only check (`checkAdminAccess()`) runs client-side and redirects
 * non-admins to "/".
 */
export class DashboardPage extends BasePage {
  readonly header: HeaderComponent;
  readonly heading: Locator;
  readonly tableBody: Locator;

  constructor(page: Page) {
    super(page);
    this.header = new HeaderComponent(page);
    this.heading = page.getByRole('heading', { name: 'Manage Books' });
    this.tableBody = page.locator('#booksTableBody');
  }

  async open(): Promise<void> {
    await this.goto('/dashboard');
  }

  rowByTitle(title: string): Locator {
    return this.tableBody.locator('tr').filter({ hasText: title });
  }
}
