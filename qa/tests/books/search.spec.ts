import { expect, test } from '../../fixtures';
import { SearchResultsPage } from '../../pages/SearchResultsPage';

// Known bug: search_results.php calls `GET /api/v1/search?title=...&author=...`,
// but the only registered route is `GET /api/v1/search/(\w+)` — a single path
// segment, not query params (see App/Router/ApiRouter.php and
// BookController::searchBooks($search)). The request never matches that route,
// so every search returns a 404/"not found" and the page renders an error
// instead of results. This test documents the *intended* behavior and is
// expected to fail until the route/frontend are reconciled.

test.describe('Book search', () => {
  test.fail(
    true,
    'Known bug: GET /api/v1/search?title=... does not match the registered ' +
      'GET /api/v1/search/(\\w+) route — search always errors. See BookController::searchBooks().',
  );

  test('searching by title shows matching results', async ({ page, seededBook }) => {
    const results = new SearchResultsPage(page);
    await results.openWithTitle(seededBook.title);

    await expect(results.resultsContainer).toContainText(seededBook.title);
  });
});
