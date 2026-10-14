import type { Locator, Page } from '@playwright/test';

/** The site-wide navbar (frontend/src/components/NavBar.vue), present on every page. */
export class HeaderComponent {
  readonly brand: Locator;
  readonly homeLink: Locator;
  readonly browseLink: Locator;
  readonly loginLink: Locator;
  readonly userChip: Locator;
  readonly username: Locator;
  readonly logoutMenuButton: Locator;

  constructor(private readonly page: Page) {
    this.brand = page.getByTestId('navbar-brand');
    this.homeLink = page.getByTestId('nav-link-home');
    this.browseLink = page.getByTestId('nav-link-browse');
    // Guest-only link — replaced by the user chip once authenticated.
    this.loginLink = page.getByTestId('nav-login-link');
    // Authenticated-only: clicking the chip reveals the dropdown menu.
    this.userChip = page.getByTestId('nav-user-chip');
    this.username = page.getByTestId('nav-username');
    this.logoutMenuButton = page.getByTestId('nav-menu-logout');
  }

  async logout(): Promise<void> {
    await this.userChip.click();
    await this.logoutMenuButton.click();
    // NavBar's handleLogout is async — it awaits POST /v1/logout before clearing
    // auth state and redirecting. The click itself resolves long before that
    // finishes, so without this, a caller that immediately navigates elsewhere
    // can race the app's own delayed `router.push('/')` and get yanked back
    // mid-test. Wait for the guest-only nav state to actually appear.
    await this.loginLink.waitFor({ state: 'visible' });
  }
}
