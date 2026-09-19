import type { Locator, Page } from '@playwright/test';
import { BasePage } from './BasePage';
import { HeaderComponent } from './HeaderComponent';

/**
 * /admin/upload — the only book-upload flow in the SPA
 * (frontend/src/views/admin/UploadPdf.vue). There's no single-book "add
 * book" form anymore; even a single PDF goes through this uploader, which
 * also accepts multiple files at once.
 * Admin-only, guarded the same way as /admin (see DashboardPage).
 */
export class UploadPdfPage extends BasePage {
  readonly header: HeaderComponent;
  readonly fileInput: Locator;
  readonly uploadButton: Locator;
  readonly feedback: Locator;

  constructor(page: Page) {
    super(page);
    this.header = new HeaderComponent(page);
    this.fileInput = page.getByTestId('file-input');
    this.uploadButton = page.getByTestId('upload-button');
    this.feedback = page.getByTestId('upload-feedback');
  }

  async open(): Promise<void> {
    await this.goto('/admin/upload');
  }

  titleInput(index: number): Locator {
    return this.page.getByTestId(`file-title-input-${index}`);
  }

  authorInput(index: number): Locator {
    return this.page.getByTestId(`file-author-input-${index}`);
  }

  /** Uploads a single PDF — files are appended in order, so the first (and only) one is index 0. */
  async uploadOne(input: { title: string; author?: string; pdf: Buffer; fileName?: string }): Promise<void> {
    const fileName = input.fileName ?? 'sample.pdf';
    await this.fileInput.setInputFiles({ name: fileName, mimeType: 'application/pdf', buffer: input.pdf });

    await this.titleInput(0).fill(input.title);
    if (input.author) {
      await this.authorInput(0).fill(input.author);
    }

    await this.uploadButton.click();
  }
}
