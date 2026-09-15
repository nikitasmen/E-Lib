import type { Locator, Page } from '@playwright/test';
import { LoginPopup } from './LoginPopup';
import { SignupPopup } from './SignupPopup';

/** The site-wide navbar (App/Views/Partials/Header.php), present on every page. */
export class HeaderComponent {
  readonly nav: Locator;
  readonly brand: Locator;
  readonly loginPopup: LoginPopup;
  readonly signupPopup: SignupPopup;

  constructor(private readonly page: Page) {
    this.nav = page.getByRole('navigation').first();
    this.brand = this.nav.locator('.navbar-brand');
    this.loginPopup = new LoginPopup(page);
    this.signupPopup = new SignupPopup(page);
  }

  navLink(name: string): Locator {
    return this.nav.getByRole('link', { name, exact: true });
  }

  async search(title: string): Promise<void> {
    await this.page.locator('#bookToSearch').fill(title);
    await this.page.locator('#searchForm').getByRole('button', { name: 'Search' }).click();
  }
}
