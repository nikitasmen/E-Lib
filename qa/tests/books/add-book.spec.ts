import { deleteBookViaApi, findBookIdByTitle, loginAsAdmin } from '../../support/api';
import { buildSamplePdf } from '../../support/samplePdf';
import { uniqueBookTitle } from '../../support/testData';
import { expect, test } from '../../fixtures';
import { AddBookPage } from '../../pages/AddBookPage';

test.describe('Add book', () => {
  test('a logged-in non-admin cannot add a book', async ({ authenticatedPage }) => {
    const addBook = new AddBookPage(authenticatedPage);
    await addBook.open();
    const title = uniqueBookTitle();

    await addBook.fillAndSubmit({ title, pdf: buildSamplePdf(title) });

    await expect(addBook.formAlert).toContainText(/admin/i);
  });

  test('an admin can upload a new book', async ({ adminPage, request, adminCredentials }) => {
    const addBook = new AddBookPage(adminPage);
    await addBook.open();
    const title = uniqueBookTitle();

    await addBook.fillAndSubmit({ title, author: 'QA Author', pdf: buildSamplePdf(title) });

    await expect(addBook.formAlert).toContainText('added successfully');

    // Clean up via the same API the app itself exposes for this — the UI flow
    // that created the book never handed the test its id.
    const token = await loginAsAdmin(request, adminCredentials.email, adminCredentials.password);
    const id = await findBookIdByTitle(request, title);
    if (id) {
      await deleteBookViaApi(request, token, id);
    }
  });
});
