import { test, expect } from '@playwright/test';

test.describe('Home page', () => {
  test('loads and shows the site title', async ({ page }) => {
    await page.goto('/');

    await expect(page).toHaveTitle('Epictetus Library - Home of Knowledge');
    await expect(page.locator('.navbar-brand')).toContainText('Epictetus Library');
  });

  test('primary navigation links to the main sections', async ({ page }) => {
    await page.goto('/');

    const nav = page.getByRole('navigation').first();
    await expect(nav.getByRole('link', { name: 'Books', exact: true })).toHaveAttribute(
      'href',
      '/view-books',
    );
    await expect(nav.getByRole('link', { name: 'Add Book', exact: true })).toHaveAttribute(
      'href',
      '/add-book',
    );
  });
});
