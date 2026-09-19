import { expect, test } from '../../fixtures';
import { SearchResultsPage } from '../../pages/SearchResultsPage';

// Was a known bug: GET /api/v1/search/:term was routed with `(\w+)`, which
// doesn't match spaces — so any multi-word title 404'd and the frontend
// showed a generic "An error occurred" message instead of real results or a
// clean "no results" message. Fixed in App/Router/ApiRouter.php (route now
// captures the whole segment) and App/Controllers/BookController::searchBooks
// (urldecodes it before querying). These tests cover multi-word terms
// specifically to guard against a regression.

test.describe('Book search', () => {
  test('searching by a multi-word title shows matching results', async ({ page, seededBook }) => {
    const results = new SearchResultsPage(page);
    await results.openWithTerm(seededBook.title);

    await expect(results.resultsGrid).toContainText(seededBook.title);
  });

  test('shows a "no results" message for an unmatched multi-word search', async ({ page }) => {
    const results = new SearchResultsPage(page);
    await results.openWithTerm('this title definitely does not exist anywhere');

    await expect(results.message).toContainText('No books found for');
  });
});
