import { expect, test } from '../../fixtures';
import { BookDetailPage } from '../../pages/BookDetailPage';
import { HomePage } from '../../pages/HomePage';

test.describe('Book detail page', () => {
  test('shows book details to a guest, with preview but no save/download', async ({
    page,
    seededBook,
  }) => {
    const detail = new BookDetailPage(page);
    await detail.openBook(seededBook.id);

    await expect(detail.title).toHaveText(seededBook.title);
    await expect(detail.previewButton).toBeVisible();
    await expect(detail.saveButton).toBeHidden();
    await expect(detail.downloadButton).toBeHidden();
  });

  test('the "Online Preview" button opens the reader for the book', async ({
    page,
    seededBook,
  }) => {
    const detail = new BookDetailPage(page);
    await detail.openBook(seededBook.id);

    await detail.previewButton.click();

    await expect(page).toHaveURL(new RegExp(`/read/${seededBook.id}$`));
  });

  test('a logged-in user also sees save and download actions', async ({
    authenticatedPage,
    seededBook,
  }) => {
    const detail = new BookDetailPage(authenticatedPage);
    await detail.openBook(seededBook.id);

    await expect(detail.saveButton).toBeVisible();
    await expect(detail.downloadButton).toBeVisible();
  });

  test('the home page Featured Collection links to the book detail page', async ({
    page,
    seededBook,
  }) => {
    const home = new HomePage(page);
    await home.open();

    // Asserting the href (rather than clicking through) sidesteps home.php's
    // unrelated "auto-open #loginPopup on scroll" behavior, which reliably
    // intercepts a real click on a card this far down the page.
    const card = home.bookCardByTitle(seededBook.title);
    await expect(card).toBeVisible();
    await expect(card.getByRole('link', { name: 'View Details' })).toHaveAttribute(
      'href',
      `/book/${seededBook.id}`,
    );
  });
});
