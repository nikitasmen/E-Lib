import type { Locator, Page } from '@playwright/test';
import { BasePage } from './BasePage';
import { HeaderComponent } from './HeaderComponent';

export class AddBookPage extends BasePage {
  readonly header: HeaderComponent;
  readonly title: Locator;
  readonly author: Locator;
  readonly description: Locator;
  readonly fileInput: Locator;
  readonly submitButton: Locator;
  readonly formAlert: Locator;

  constructor(page: Page) {
    super(page);
    this.header = new HeaderComponent(page);
    this.title = page.locator('#title');
    this.author = page.locator('#author');
    this.description = page.locator('#description');
    this.fileInput = page.locator('#bookFile');
    this.submitButton = page.locator('#submitBookBtn');
    this.formAlert = page.locator('#formAlert');
  }

  async open(): Promise<void> {
    await this.goto('/add-book');
  }

  async fillAndSubmit(input: { title: string; author?: string; pdfPath: string }): Promise<void> {
    await this.title.fill(input.title);
    if (input.author) {
      await this.author.fill(input.author);
    }
    await this.fileInput.setInputFiles(input.pdfPath);
    await this.submitButton.click();
  }
}
