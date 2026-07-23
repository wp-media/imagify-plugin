import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { SettingsPage } from '../pages/settings';

/**
 * Regression spec for issue #883 / PR #1078.
 *
 * When .htaccess is not writable, enabling "Display images in Next-Gen format
 * on the site" must show exactly ONE error notice — not two.
 *
 * Two independent subscribers (Imagify\Webp\Display and Imagify\Avif\Display)
 * both fire on imagify_settings_on_save when .htaccess is not writable, and
 * each appended an identical notice. The fix deduplicates at the notice-storage
 * layer in Notices::add_site_temporary_notice().
 *
 * PREREQUISITE: Before running this spec the .htaccess inside the WordPress
 * container must be set to 444 (read-only). Example:
 *
 *   docker exec wp-env-imagify-plugin-*-wordpress-1 chmod 444 /var/www/html/.htaccess
 *
 * The test itself does not mutate container permissions; it assumes they are
 * already set by the test harness / setup script.
 *
 * Because a live .htaccess permission is required but the standard CI image may
 * not have it pre-configured, the test checks for the notice only when the
 * IMAGIFY_HTACCESS_READONLY env var is set to '1'. Without it the test
 * verifies the settings page loads cleanly and a single save cycle produces at
 * most one notice block.
 */

test.describe( 'Issue #883 — .htaccess not writable: single error notice', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	// -----------------------------------------------------------------------
	// Smoke — settings page loads without fatal errors
	// -----------------------------------------------------------------------

	test( 'Settings page loads without fatal errors', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();

		await settings.expectNoFatalError();
		await expect( page ).toHaveURL( /page=imagify/ );
		await page.screenshot( { path: '.e2e-screenshots/htaccess-01-settings-loaded.png' } );
	} );

	// -----------------------------------------------------------------------
	// Core regression — only ONE notice when .htaccess is read-only
	// -----------------------------------------------------------------------

	test( 'Enabling display_nextgen with read-only .htaccess shows at most one error notice', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();

		await settings.expectNoFatalError();

		// The "Display images in Next-Gen format" checkbox.
		// Imagify uses a custom toggle: the label overlays the checkbox so we
		// click the label (for="imagify_display_nextgen") rather than the input.
		const displayNextgenCheckbox = page.locator( '[name="imagify_settings[display_nextgen]"]' ).first();
		const checkboxCount = await displayNextgenCheckbox.count();

		if ( checkboxCount === 0 ) {
			test.skip( true, 'display_nextgen checkbox not visible — IMAGIFY_TESTS_API_KEY not set, WebP section hidden.' );
		}

		// Ensure the checkbox is checked (enabled). Click the label which overlays
		// the input — using force:true bypasses the intercepting label.
		const isChecked = await displayNextgenCheckbox.isChecked();
		if ( ! isChecked ) {
			await displayNextgenCheckbox.click( { force: true } );
		}

		// Save settings — this triggers .htaccess write on the server.
		await settings.saveButton.click();
		await page.waitForLoadState( 'networkidle' );

		await settings.expectNoFatalError();
		await expect( page ).toHaveURL( /page=imagify/ );

		await page.screenshot( { path: '.e2e-screenshots/htaccess-03-after-save-with-nextgen.png' } );

		// Count all admin notice blocks rendered after save.
		// Imagify temporary notices are rendered inside .notice elements.
		const allNotices = page.locator( '.notice, .updated, .update-nag' );
		const noticeCount = await allNotices.count();

		// Gather notice texts for evidence.
		const noticeTexts: string[] = [];
		for ( let i = 0; i < noticeCount; i++ ) {
			noticeTexts.push( ( await allNotices.nth( i ).innerText() ).trim().substring( 0, 120 ) );
		}

		// Specifically look for htaccess-related error notices.
		const htaccessNotices = page.locator( '.notice-error:has-text(".htaccess"), .notice-error:has-text("htaccess"), .error:has-text("htaccess")' );
		const htaccessNoticeCount = await htaccessNotices.count();

		// REGRESSION GUARD: must not exceed 1 htaccess error notice.
		// (If .htaccess IS writable, htaccessNoticeCount will be 0 — that is also acceptable.)
		expect(
			htaccessNoticeCount,
			`Expected at most 1 .htaccess error notice, got ${ htaccessNoticeCount }. Notices: ${ JSON.stringify( noticeTexts ) }`,
		).toBeLessThanOrEqual( 1 );

		await page.screenshot( { path: '.e2e-screenshots/htaccess-04-notice-count.png' } );
	} );

	// -----------------------------------------------------------------------
	// Dedicated read-only gate — strict single-notice assertion
	// -----------------------------------------------------------------------

	test( 'Exactly ONE htaccess notice when IMAGIFY_HTACCESS_READONLY=1', async ( { page } ) => {
		test.skip(
			process.env.IMAGIFY_HTACCESS_READONLY !== '1',
			'IMAGIFY_HTACCESS_READONLY is not set to 1 — skipping strict single-notice assertion. ' +
			'Set the env var after running: docker exec <container> chmod 444 /var/www/html/.htaccess',
		);

		const settings = new SettingsPage( page );
		await settings.goto();

		// Enable display_nextgen to trigger .htaccess write attempt.
		// Use force:true because a label overlays the checkbox input.
		const displayNextgenCheckbox = page.locator( '[name="imagify_settings[display_nextgen]"]' ).first();
		await expect( displayNextgenCheckbox ).toBeVisible( { timeout: 10_000 } );

		// Toggle OFF first (to ensure a state change), then ON — guarantees a
		// fresh .htaccess write attempt regardless of the current saved value.
		const isChecked = await displayNextgenCheckbox.isChecked();
		if ( isChecked ) {
			// Turn off and save first so we start from a known "disabled" state.
			await displayNextgenCheckbox.click( { force: true } );
			await settings.saveButton.click();
			await page.waitForLoadState( 'networkidle' );
			await settings.goto();
			await expect( displayNextgenCheckbox ).toBeVisible( { timeout: 10_000 } );
		}

		// Now turn ON — this triggers the .htaccess write with read-only perms.
		await displayNextgenCheckbox.click( { force: true } );
		await settings.saveButton.click();
		await page.waitForLoadState( 'networkidle' );

		await settings.expectNoFatalError();

		await page.screenshot( { path: '.e2e-screenshots/htaccess-05-readonly-after-save.png' } );

		// With .htaccess at 444, Imagify fires two subscribers (WebP + AVIF) but
		// the deduplication fix ensures only ONE notice is stored and rendered.
		// The notice text contains "htaccess" or ".htaccess".
		// Imagify renders temporary notices as <div class="notice notice-error"> items.
		const htaccessNotices = page.locator(
			'.notice-error:has-text("htaccess"), .notice.error:has-text("htaccess")',
		);
		const count = await htaccessNotices.count();

		expect(
			count,
			`Expected exactly 1 .htaccess error notice after save with read-only .htaccess, but got ${ count }`,
		).toBe( 1 );

		await page.screenshot( { path: '.e2e-screenshots/htaccess-06-single-notice-verified.png' } );
	} );

	// -----------------------------------------------------------------------
	// Regression — no PHP Undefined variable warnings in page source
	// -----------------------------------------------------------------------

	test( 'No PHP Undefined variable warnings visible in settings page', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();

		const bodyText = await page.locator( 'body' ).innerText();
		expect( bodyText, 'PHP Warning for $file_path or $file_name must not appear' ).not.toMatch(
			/Undefined variable[:\s]+\$file_(path|name)/i,
		);
		expect( bodyText, 'Generic PHP Warning must not appear in page body' ).not.toMatch(
			/PHP Warning:/i,
		);

		await page.screenshot( { path: '.e2e-screenshots/htaccess-07-no-php-warnings.png' } );
	} );
} );
