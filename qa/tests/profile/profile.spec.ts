import { expect, test } from '../../fixtures';
import { ProfilePage } from '../../pages/ProfilePage';

test.describe('Profile', () => {
  test('shows the logged-in user\'s account info', async ({ authenticatedPage, registeredUser }) => {
    const profile = new ProfilePage(authenticatedPage);
    await profile.open();

    await expect(profile.usernameHeading).toHaveText(registeredUser.username);
    await expect(profile.emailText).toHaveText(registeredUser.email);
  });

  test('can update the username', async ({ authenticatedPage, registeredUser }) => {
    const profile = new ProfilePage(authenticatedPage);
    await profile.open();
    const newUsername = `${registeredUser.username}-renamed`;

    await profile.openEditUsername(newUsername);

    await expect(profile.editUsernameModal).toBeHidden();
    await expect(profile.usernameHeading).toHaveText(newUsername);
  });

  test('rejects a too-short username', async ({ authenticatedPage }) => {
    const profile = new ProfilePage(authenticatedPage);
    await profile.open();

    await profile.openEditUsername('ab');

    await expect(profile.usernameError).toContainText('at least 3 characters');
  });

  test('can change the password', async ({ authenticatedPage, registeredUser }) => {
    const profile = new ProfilePage(authenticatedPage);
    await profile.open();
    const newPassword = `${registeredUser.password}-2`;

    await profile.changePassword(registeredUser.password, newPassword);

    await expect(profile.changePasswordFeedback).toContainText('Password updated successfully');
  });

  test('rejects the wrong current password', async ({ authenticatedPage }) => {
    const profile = new ProfilePage(authenticatedPage);
    await profile.open();

    await profile.changePassword('definitely-the-wrong-password', 'NewPassw0rd!');

    await expect(profile.changePasswordFeedback).toBeVisible();
    await expect(profile.changePasswordFeedback).not.toContainText('successfully');
  });
});
