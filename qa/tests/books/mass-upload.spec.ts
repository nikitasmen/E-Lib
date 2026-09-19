import { deleteBookViaApi, findBookIdByTitle, loginAsAdmin } from '../../support/api';
import { buildSamplePdf } from '../../support/samplePdf';
import { uniqueBookTitle } from '../../support/testData';
import { expect, test } from '../../fixtures';
import { MassUploadPage } from '../../pages/MassUploadPage';

// The SPA has no single-book "add book" form anymore (see App.vue routes) —
// /admin/mass-upload (frontend/src/views/admin/MassUpload.vue) is the only
// upload flow, and it's admin-gated by the same `requireAdmin` router guard
// as the dashboard (see dashboard.spec.ts).

test.describe('Mass upload (admin book upload)', () => {
  test('a logged-in non-admin is bounced back to the home page', async ({ authenticatedPage }) => {
    await authenticatedPage.goto('/admin/mass-upload');

    // `requireAdmin` router guard should block the page outright — check both
    // the URL and that the upload form never rendered, not just where we
    // ended up.
    await expect(authenticatedPage).toHaveURL('/');
    await expect(authenticatedPage.getByTestId('file-input')).toHaveCount(0);
  });

  test('an admin can upload a new book', async ({ adminPage, request, adminCredentials }) => {
    const massUpload = new MassUploadPage(adminPage);
    await massUpload.open();
    const title = uniqueBookTitle();

    await massUpload.uploadOne({ title, author: 'QA Test Author', pdf: buildSamplePdf(title) });

    await expect(massUpload.feedback).toContainText('uploaded successfully');

    // Clean up via the same API the app itself exposes for this — the UI flow
    // that created the book never handed the test its id.
    const token = await loginAsAdmin(request, adminCredentials.email, adminCredentials.password);
    const id = await findBookIdByTitle(request, title);
    // Cleanup guard, not test logic under test — id is only ever null if the
    // lookup itself failed, in which case there's nothing to delete.
    // eslint-disable-next-line playwright/no-conditional-in-test
    if (id) {
      await deleteBookViaApi(request, token, id);
    }
  });
});
