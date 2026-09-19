import type { Locator, Page } from '@playwright/test';
import { BasePage } from './BasePage';
import { HeaderComponent } from './HeaderComponent';

/**
 * /admin/mass-upload — the only book-upload flow in the SPA
 * (frontend/src/views/admin/MassUpload.vue). There's no single-book "add
 * book" form anymore; even a single PDF goes through this bulk uploader.
 * Admin-only, guarded the same way as /admin (see DashboardPage).
 */
export class MassUploadPage extends BasePage {
  readonly header: HeaderComponent;
  readonly fileInput: Locator;
  readonly uploadButton: Locator;
  readonly feedback: Locator;

  constructor(page: Page) {
    super(page);
    this.header = new HeaderComponent(page);
    this.fileInput = page.locator('#pdf-files-input');
    this.uploadButton = page.getByRole('button', { name: /^(Upload All Files|Uploading…)$/ });
    this.feedback = page.locator('.upload-status .alert');
  }

  async open(): Promise<void> {
    await this.goto('/admin/mass-upload');
  }

  fileRow(fileName: string): Locator {
    return this.page.locator('.file-row').filter({ hasText: fileName });
  }

  async uploadOne(input: { title: string; author?: string; pdf: Buffer; fileName?: string }): Promise<void> {
    const fileName = input.fileName ?? 'sample.pdf';
    await this.fileInput.setInputFiles({ name: fileName, mimeType: 'application/pdf', buffer: input.pdf });

    const row = this.fileRow(fileName);
    await row.getByPlaceholder('Book Title').fill(input.title);
    if (input.author) {
      await row.getByPlaceholder('Author (optional)').fill(input.author);
    }

    await this.uploadButton.click();
  }
}
