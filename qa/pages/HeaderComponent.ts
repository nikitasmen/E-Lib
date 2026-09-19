import type { Locator, Page } from '@playwright/test';

/** The site-wide navbar (frontend/src/components/NavBar.vue), present on every page. */
export class HeaderComponent {
  readonly nav: Locator;
  readonly brand: Locator;
  readonly homeLink: Locator;
  readonly browseLink: Locator;
  readonly searchInput: Locator;
  readonly searchSubmit: Locator;
  readonly loginLink: Locator;
  readonly signupLink: Locator;
  readonly userChip: Locator;
  readonly username: Locator;
  readonly userMenu: Locator;
  readonly profileMenuLink: Locator;
  readonly dashboardMenuLink: Locator;
  readonly logoutMenuButton: Locator;

  constructor(private readonly page: Page) {
    this.nav = page.getByTestId('navbar');
    this.brand = page.getByTestId('navbar-brand');
    this.homeLink = page.getByTestId('nav-link-home');
    this.browseLink = page.getByTestId('nav-link-browse');
    this.searchInput = page.getByTestId('nav-search-input');
    this.searchSubmit = page.getByTestId('nav-search-submit');
    // Guest-only links — replaced by the user chip once authenticated.
    this.loginLink = page.getByTestId('nav-login-link');
    this.signupLink = page.getByTestId('nav-signup-link');
    // Authenticated-only: clicking the chip reveals the dropdown menu.
    this.userChip = page.getByTestId('nav-user-chip');
    this.username = page.getByTestId('nav-username');
    this.userMenu = page.getByTestId('nav-user-menu');
    this.profileMenuLink = page.getByTestId('nav-menu-profile');
    this.dashboardMenuLink = page.getByTestId('nav-menu-dashboard');
    this.logoutMenuButton = page.getByTestId('nav-menu-logout');
  }

  async search(term: string): Promise<void> {
    await this.searchInput.fill(term);
    await this.searchSubmit.click();
  }

  async openUserMenu(): Promise<void> {
    await this.userChip.click();
  }

  async logout(): Promise<void> {
    await this.openUserMenu();
    await this.logoutMenuButton.click();
    // NavBar's handleLogout is async — it awaits POST /v1/logout before clearing
    // auth state and redirecting. The click itself resolves long before that
    // finishes, so without this, a caller that immediately navigates elsewhere
    // can race the app's own delayed `router.push('/')` and get yanked back
    // mid-test. Wait for the guest-only nav state to actually appear.
    await this.loginLink.waitFor({ state: 'visible' });
  }
}
