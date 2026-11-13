import { test, expect, type Page } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { hasApiKey } from '../fixtures/wp-cli';

/**
 * Upgrade modal — focus handling (#1062).
 *
 * Chrome logs "Blocked aria-hidden on an element because its descendant retained focus"
 * when `aria-hidden="true"` is set on an element that still contains the focused element.
 * `assets/js/admin.js` (closing a modal) and `assets/js/pricing-modal.js` (switching views)
 * must therefore move the focus out of the element before hiding it, and send it somewhere
 * useful:
 *   - when the modal closes, back to the element that opened it,
 *   - when a view is replaced by another one, into the new view.
 *
 * Like `pricing-modal-layout.spec.ts`, the upgrade modal only renders for an account in a
 * specific plan/quota state, so the spec skips when that state is not available.
 *
 * Related issue: #1062
 */
test.describe( 'Upgrade modal — focus handling (#1062)', () => {
	const MODAL = '#imagify-pricing-modal';
	// The settings-page CTA: the admin-bar "Upgrade Plan" item uses the same trigger classes but sits in a hidden hover submenu.
	const TRIGGER = '.imagify-upsell-cta[data-target="#imagify-pricing-modal"]';

	test.beforeEach( async ( { page } ) => {
		test.skip( ! hasApiKey(), 'IMAGIFY_TESTS_API_KEY not set — cannot reach an account/quota state that renders the upgrade modal' );

		// Chrome reports "Blocked aria-hidden" itself, not through `console.*`, so record the condition it blocks
		// directly: `aria-hidden="true"` set on an element that contains the focused element.
		await page.addInitScript( () => {
			const win = window as Window & { imagifyBlockedAriaHidden?: string[] };
			const setAttribute = Element.prototype.setAttribute;

			win.imagifyBlockedAriaHidden = [];
			Element.prototype.setAttribute = function ( name: string, value: string ) {
				if ( 'aria-hidden' === name && 'true' === String( value ) && this.contains( document.activeElement ) && document.activeElement !== document.body ) {
					win.imagifyBlockedAriaHidden?.push( this.id || this.className || this.tagName );
				}
				return setAttribute.call( this, name, value );
			};
		} );

		await loginAsAdmin( page );
		await page.goto( '/wp-admin/options-general.php?page=imagify' );
		await page.waitForLoadState( 'networkidle' );

		const triggerCount = await page.locator( TRIGGER ).count();
		test.skip( triggerCount === 0, 'Configured account\'s plan/quota state does not render the upgrade modal trigger (see Imagify_Views::get_user_info()) — needs a free-plan test account with quota remaining.' );
	} );

	/**
	 * Elements that got `aria-hidden="true"` while they contained the focused element.
	 */
	function blockedAriaHidden( page: Page ) {
		return page.evaluate( () => ( window as Window & { imagifyBlockedAriaHidden?: string[] } ).imagifyBlockedAriaHidden ?? [] );
	}

	/**
	 * Open the modal from its trigger and go to the payment view, by choosing a plan.
	 */
	async function openModalAndChoosePlan( page: Page ) {
		await page.locator( TRIGGER ).click();
		await expect( page.locator( MODAL ) ).toBeVisible();

		// The plans come from the Imagify API, wait for them to be listed.
		const planButton = page.locator( `${ MODAL } .imagify-payment-btn-select-plan` ).first();
		await expect( planButton ).toBeVisible( { timeout: 15000 } );
		await planButton.click();

		await expect( page.locator( '#imagify-payment-process-view' ) ).toBeVisible();
	}

	test( 'choosing a plan moves the focus into the payment view', async ( { page } ) => {
		await openModalAndChoosePlan( page );

		await expect.poll( () => page.evaluate( () => {
			const view = document.querySelector( '#imagify-payment-process-view' );
			return !! view && view.contains( document.activeElement );
		} ) ).toBe( true );

		expect( await blockedAriaHidden( page ) ).toEqual( [] );
	} );

	test( 'closing with the close button gives the focus back to the trigger', async ( { page } ) => {
		await openModalAndChoosePlan( page );

		await page.locator( `${ MODAL } .close-btn` ).click();

		await expect( page.locator( MODAL ) ).toBeHidden();
		await expect( page.locator( TRIGGER ) ).toBeFocused();
		expect( await blockedAriaHidden( page ) ).toEqual( [] );
	} );

	test( 'closing with Esc gives the focus back to the trigger', async ( { page } ) => {
		await openModalAndChoosePlan( page );

		await page.keyboard.press( 'Escape' );

		await expect( page.locator( MODAL ) ).toBeHidden();
		await expect( page.locator( TRIGGER ) ).toBeFocused();
		expect( await blockedAriaHidden( page ) ).toEqual( [] );
	} );
} );
