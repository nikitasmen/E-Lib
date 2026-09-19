import type { Locator, Page } from '@playwright/test';

/** The site-wide navbar (frontend/src/components/NavBar.vue), present on every page. */
export class HeaderComponent {
  readonly nav: Locator;
  readonly brand: Locator;
  readonly loginLink: Locator;
  readonly signupLink: Locator;
  readonly userChip: Locator;
  readonly userMenu: Locator;
  readonly profileMenuLink: Locator;
  readonly dashboardMenuLink: Locator;
  readonly logoutMenuButton: Locator;

  constructor(private readonly page: Page) {
    this.nav = page.getByRole('navigation').first();
    this.brand = this.nav.locator('.brand');
    // Guest-only links — replaced by the user chip once authenticated.
    this.loginLink = this.nav.getByRole('link', { name: 'Log in' });
    this.signupLink = this.nav.getByRole('link', { name: 'Sign up' });
    // Authenticated-only: clicking the chip reveals the dropdown menu.
    this.userChip = this.nav.locator('.user-chip');
    this.userMenu = this.nav.locator('.user-menu');
    this.profileMenuLink = this.userMenu.getByRole('link', { name: 'Profile' });
    this.dashboardMenuLink = this.userMenu.getByRole('link', { name: 'Dashboard' });
    this.logoutMenuButton = this.userMenu.getByRole('button', { name: 'Log out' });
  }

  navLink(name: string): Locator {
    return this.nav.getByRole('link', { name, exact: true });
  }

  async search(term: string): Promise<void> {
    await this.nav.locator('.search-form input[type="search"]').fill(term);
    await this.nav.locator('.search-form').getByRole('button', { name: 'Search' }).click();
  }

  async openUserMenu(): Promise<void> {
    await this.userChip.click();
  }

  async logout(): Promise<void> {
    await this.openUserMenu();
    await this.logoutMenuButton.click();
  }
}
