import type { APIRequestContext } from '@playwright/test';

/**
 * Thin wrappers around the app's real HTTP API for test setup/teardown that
 * can't be done through a UI flow (e.g. an admin creating a book fixture
 * ahead of a test). These never touch the database directly — everything
 * here is a request the app itself serves and validates.
 */

interface LoginResponse {
  status: string;
  data?: { token?: string; user?: { isAdmin?: boolean } };
  message?: unknown;
}

/**
 * Logs in via POST /api/v1/login on the given request context. The context
 * keeps the session cookie for subsequent calls, which is what
 * AuthenticatedUser::isAdmin() actually checks server-side (not the JWT).
 */
export async function loginAsAdmin(
  request: APIRequestContext,
  email: string,
  password: string,
): Promise<string> {
  const response = await request.post('/api/v1/login', { data: { email, password } });
  const body = (await response.json()) as LoginResponse;

  if (!response.ok() || body.status !== 'success' || !body.data?.token) {
    throw new Error(
      `QA_ADMIN_EMAIL/QA_ADMIN_PASSWORD login failed: ${response.status()} ${JSON.stringify(body)}`,
    );
  }
  if (!body.data.user?.isAdmin) {
    throw new Error(
      `"${email}" logged in but is not an admin. Provision one with: ` +
        `composer admin:create -- ${email} <password>`,
    );
  }

  return body.data.token;
}

interface CreateBookResponse {
  status: string;
  data?: { insertedId?: string };
  message?: unknown;
}

/**
 * POST /api/v1/books (multipart) — the single-book endpoint behind the app's
 * upload flow (frontend/src/views/admin/UploadPdf.vue calls the equivalent
 * mass-upload endpoint; this is its one-file sibling, used here for fixture
 * setup that doesn't need a UI round-trip).
 */
export async function createBookViaApi(
  request: APIRequestContext,
  adminToken: string,
  input: { title: string; author?: string; pdf: Buffer },
): Promise<string> {
  const response = await request.post('/api/v1/books', {
    headers: { Authorization: `Bearer ${adminToken}` },
    multipart: {
      title: input.title,
      author: input.author ?? 'QA Test Author',
      categories: '[]',
      downloadable: 'true',
      bookFile: {
        name: 'sample.pdf',
        mimeType: 'application/pdf',
        buffer: input.pdf,
      },
    },
  });
  const body = (await response.json()) as CreateBookResponse;

  if (!response.ok() || !body.data?.insertedId) {
    throw new Error(`createBookViaApi failed: ${response.status()} ${JSON.stringify(body)}`);
  }

  return body.data.insertedId;
}

/** PUT /api/v1/books/:id — same endpoint the dashboard's status/featured toggles use. */
export async function setBookVisibility(
  request: APIRequestContext,
  adminToken: string,
  id: string,
  fields: { status?: 'public' | 'draft'; featured?: boolean },
): Promise<void> {
  const response = await request.put(`/api/v1/books/${id}`, {
    headers: { Authorization: `Bearer ${adminToken}` },
    data: fields,
  });
  if (!response.ok()) {
    throw new Error(`setBookVisibility failed: ${response.status()} ${await response.text()}`);
  }
}

interface RawBook {
  title: string;
  _id: string | { $oid: string };
}

/**
 * GET /api/v1/books is unauthenticated and returns every book regardless of
 * status — used here to find the id of a book created through the UI (whose
 * response the test never saw), for cleanup only.
 */
export async function findBookIdByTitle(
  request: APIRequestContext,
  title: string,
): Promise<string | null> {
  const response = await request.get('/api/v1/books');
  if (!response.ok()) {
    return null;
  }
  const body = (await response.json()) as { data?: RawBook[] };
  const match = body.data?.find((book) => book.title === title);
  if (!match) {
    return null;
  }
  return typeof match._id === 'string' ? match._id : match._id.$oid;
}

/** DELETE /api/v1/books/:id */
export async function deleteBookViaApi(
  request: APIRequestContext,
  adminToken: string,
  id: string,
): Promise<void> {
  await request.delete(`/api/v1/books/${id}`, {
    headers: { Authorization: `Bearer ${adminToken}` },
  });
}

/**
 * Finds and deletes each book by title — cleanup for books created through a
 * UI flow (e.g. the upload page) whose ids the test never saw. A title with
 * no matching book is skipped, not an error: the lookup only ever misses
 * when creation itself failed, in which case there's nothing to delete.
 */
export async function deleteBooksByTitle(
  request: APIRequestContext,
  adminToken: string,
  titles: string[],
): Promise<void> {
  for (const title of titles) {
    const id = await findBookIdByTitle(request, title);
    if (id) {
      await deleteBookViaApi(request, adminToken, id);
    }
  }
}
