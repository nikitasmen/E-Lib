import { createBookViaApi, deleteBookViaApi, getBookByTitle, loginAsAdmin, setBookVisibility } from '../../support/api';
import { buildSamplePdf } from '../../support/samplePdf';
import { uniqueBookTitle } from '../../support/testData';
import { expect, test } from '../../fixtures';
import { DashboardPage } from '../../pages/DashboardPage';
import { BrowsePage } from '../../pages/BrowsePage';
import { BookDetailPage } from '../../pages/BookDetailPage';

// Three of the admin dashboard's mutating actions (frontend/src/views/admin/Dashboard.vue)
// have no coverage in dashboard.spec.ts, which only checks page access/display. All three
// are either irreversible or gate a server-enforced access rule, so each one here
// cross-checks the resulting server state — and, where relevant, another page entirely —
// not just the dashboard's own row/toast.
//
// Books here are created directly via the API rather than the `seededBook` fixture,
// since each test needs to control (or itself perform) the delete.

test.describe('Admin book management actions', () => {
  test('cancelling the delete confirmation leaves the book untouched', async ({ adminPage, request, adminCredentials }) => {
    const token = await loginAsAdmin(request, adminCredentials.email, adminCredentials.password);
    const title = uniqueBookTitle('QA Delete Cancel');
    const id = await createBookViaApi(request, token, { title, pdf: buildSamplePdf(title) });

    try {
      const dashboard = new DashboardPage(adminPage);
      await dashboard.open();
      const row = dashboard.rowById(id);
      await expect(row).toBeVisible();

      adminPage.once('dialog', (dialog) => dialog.dismiss());
      await dashboard.deleteButton(id).click();

      await expect(row).toBeVisible();
      expect(await getBookByTitle(request, title)).not.toBeNull();
    } finally {
      await deleteBookViaApi(request, token, id);
    }
  });

  test('confirming delete permanently removes the book', async ({ adminPage, request, adminCredentials }) => {
    const token = await loginAsAdmin(request, adminCredentials.email, adminCredentials.password);
    const title = uniqueBookTitle('QA Delete Confirm');
    const id = await createBookViaApi(request, token, { title, pdf: buildSamplePdf(title) });

    const dashboard = new DashboardPage(adminPage);
    await dashboard.open();
    const row = dashboard.rowById(id);
    await expect(row).toBeVisible();

    adminPage.once('dialog', (dialog) => dialog.accept());
    await dashboard.deleteButton(id).click();

    // Cross-check the row disappearing against the book actually being gone
    // server-side — not just removed from this one rendered list.
    await expect(row).toHaveCount(0);
    expect(await getBookByTitle(request, title)).toBeNull();
  });

  test('toggling status controls whether the book is publicly visible', async ({
    adminPage,
    request,
    adminCredentials,
  }, testInfo) => {
    // Five page loads plus several API round-trips against the dev server's
    // single-threaded `php -S` — comfortably under 30s alone, but tight when
    // the full suite's other workers are hammering the same server. Give it
    // more headroom rather than trim the round-trip coverage that's the point
    // of this test.
    testInfo.setTimeout(60_000);

    const token = await loginAsAdmin(request, adminCredentials.email, adminCredentials.password);
    const title = uniqueBookTitle('QA Status Toggle');
    const id = await createBookViaApi(request, token, { title, pdf: buildSamplePdf(title) });
    await setBookVisibility(request, token, id, { status: 'draft' });

    try {
      const browse = new BrowsePage(adminPage);
      const dashboard = new DashboardPage(adminPage);

      await browse.open();
      await expect(browse.cardById(id)).toHaveCount(0); // draft baseline

      await dashboard.open();
      await expect(dashboard.statusToggle(id)).toHaveText('Draft');
      await dashboard.statusToggle(id).click();
      await expect(dashboard.statusToggle(id)).toHaveText('Public');

      expect((await getBookByTitle(request, title))?.status).toBe('public');
      await browse.open();
      await expect(browse.cardById(id)).toBeVisible();

      // Full round-trip, not just a one-way flip.
      await dashboard.open();
      await dashboard.statusToggle(id).click();
      await expect(dashboard.statusToggle(id)).toHaveText('Draft');

      expect((await getBookByTitle(request, title))?.status).toBe('draft');
      await browse.open();
      await expect(browse.cardById(id)).toHaveCount(0);
    } finally {
      await deleteBookViaApi(request, token, id);
    }
  });

  test('disabling downloads is enforced both in the UI and by the server', async ({
    adminPage,
    request,
    adminCredentials,
  }) => {
    const token = await loginAsAdmin(request, adminCredentials.email, adminCredentials.password);
    const title = uniqueBookTitle('QA Downloadable Toggle');
    const id = await createBookViaApi(request, token, { title, pdf: buildSamplePdf(title) });
    await setBookVisibility(request, token, id, { status: 'public' });

    try {
      const dashboard = new DashboardPage(adminPage);
      await dashboard.open();
      await dashboard.editButton(id).click();
      await expect(dashboard.downloadableCheckbox).toBeChecked();
      await dashboard.downloadableCheckbox.uncheck();
      await dashboard.saveEditButton.click();

      // UI layer: the detail page shows "download disabled" instead of the download button.
      const bookDetail = new BookDetailPage(adminPage);
      await bookDetail.openBook(id);
      await expect(bookDetail.downloadDisabledButton).toBeVisible();
      await expect(bookDetail.downloadButton).toHaveCount(0);

      // Server layer: the endpoint itself refuses, independent of what the UI happens to show —
      // this is the check that actually catches a client/server enforcement mismatch.
      const response = await request.get(`/api/v1/books/${id}/download`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      expect(response.status()).toBe(403);
    } finally {
      await deleteBookViaApi(request, token, id);
    }
  });
});
