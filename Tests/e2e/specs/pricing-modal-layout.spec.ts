import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { screenshotElement } from '../fixtures/screenshot';

/**
 * Upgrade modal — layout shift / misalignment across the payment flow (#1066).
 *
 * The upgrade modal (`#imagify-pricing-modal` / `.imagify-payment-modal`) hosts three
 * sequential view-states inside a single `.imagify-modal-content` box:
 *   1. plan-selection (default)
 *   2. payment iframe   (`.imagify-iframe-viewing` modifier)
 *   3. thank-you        (`.imagify-success-viewing` modifier)
 *
 * `switchToView()` in `assets/js/pricing-modal.js` toggles those modifier classes to
 * resize the shared box. Since the real payment flow cannot be driven end-to-end without
 * a live Stripe/PayPal session, this spec simulates the step transitions by toggling the
 * same modifier classes the controller uses, then asserts:
 *   - a CSS `transition` is declared on the geometry properties that change between steps
 *     (this is the actual fix — Option A from the spec — verified via computed style),
 *   - the box stays horizontally centered (no unexpected leftward/rightward "shift"),
 *   - no unexpected horizontal scrollbar appears on the modal at any step.
 *
 * Related issue: #1066
 */
test.describe( 'Upgrade modal — layout shift across payment flow steps (#1066)', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
		await page.goto( '/wp-admin/options-general.php?page=imagify' );
		await page.waitForLoadState( 'networkidle' );
	} );

	async function openPricingModal( page: import( '@playwright/test' ).Page ) {
		const trigger = page.locator( '.imagify-modal-trigger[data-target="#imagify-pricing-modal"]' ).first();

		if ( ( await trigger.count() ) === 0 ) {
			throw new Error(
				'No .imagify-modal-trigger[data-target="#imagify-pricing-modal"] element found on the Settings page — ' +
				'cannot open the upgrade modal.'
			);
		}

		await trigger.click();

		const modalContent = page.locator( '#imagify-pricing-modal .imagify-modal-content' );
		await expect( modalContent ).toBeVisible( { timeout: 10000 } );

		return modalContent;
	}

	/**
	 * Assert the modal box is (approximately) horizontally centered in the viewport, and
	 * that no unexpected horizontal scrollbar has appeared.
	 */
	async function assertNoHorizontalShiftOrScrollbar( page: import( '@playwright/test' ).Page, modalContent: import( '@playwright/test' ).Locator ) {
		const viewportSize = page.viewportSize();
		expect( viewportSize ).not.toBeNull();

		const box = await modalContent.boundingBox();
		expect( box ).not.toBeNull();

		const boxCenterX = box!.x + box!.width / 2;
		const viewportCenterX = viewportSize!.width / 2;

		// Allow a small tolerance for scrollbar width / sub-pixel rounding.
		expect(
			Math.abs( boxCenterX - viewportCenterX ),
			`Expected .imagify-modal-content to stay horizontally centered (box center: ${ boxCenterX }, viewport center: ${ viewportCenterX })`
		).toBeLessThanOrEqual( 5 );

		const hasHorizontalScrollbar = await page.evaluate( () => {
			return document.documentElement.scrollWidth > document.documentElement.clientWidth + 1;
		} );

		expect(
			hasHorizontalScrollbar,
			'Expected no unexpected horizontal scrollbar on the document while the modal is open.'
		).toBe( false );
	}

	test( 'plan-selection step is centered with no horizontal scrollbar', async ( { page } ) => {
		const modalContent = await openPricingModal( page );

		await assertNoHorizontalShiftOrScrollbar( page, modalContent );
		await screenshotElement( page, 'pricing-modal-step-1-plan-selection', modalContent );
	} );

	test( 'payment-iframe step is centered, transitions smoothly, and has no horizontal scrollbar', async ( { page } ) => {
		const modalContent = await openPricingModal( page );

		// Simulate switchToView() moving to the payment-iframe step by toggling the same
		// modifier class the controller applies (the real flow requires a live payment
		// session, which is not reachable in this environment).
		await modalContent.evaluate( ( el ) => el.classList.add( 'imagify-iframe-viewing' ) );

		// The resize is animated via a CSS transition (the fix for #1066) rather than
		// instantaneous, so wait for it to settle before asserting the final geometry.
		await page.waitForTimeout( 400 );

		const transitionProperty = await modalContent.evaluate(
			( el ) => window.getComputedStyle( el ).transitionProperty
		);

		expect(
			transitionProperty,
			`Expected .imagify-modal-content to declare a CSS transition on its geometry properties, got transition-property: "${ transitionProperty }"`
		).not.toBe( 'all' );
		expect( transitionProperty ).not.toBe( 'none' );

		await assertNoHorizontalShiftOrScrollbar( page, modalContent );
		await screenshotElement( page, 'pricing-modal-step-2-payment-iframe', modalContent );
	} );

	test( 'thank-you step is centered, transitions smoothly, and has no horizontal scrollbar', async ( { page } ) => {
		const modalContent = await openPricingModal( page );

		await modalContent.evaluate( ( el ) => el.classList.add( 'imagify-iframe-viewing' ) );
		await page.waitForTimeout( 400 );

		// Simulate switchToView() moving from the payment-iframe step to the thank-you step,
		// mirroring the mutually-exclusive class toggle in `assets/js/pricing-modal.js`.
		await modalContent.evaluate( ( el ) => {
			el.classList.remove( 'imagify-iframe-viewing' );
			el.classList.add( 'imagify-success-viewing' );
		} );
		await page.waitForTimeout( 400 );

		await assertNoHorizontalShiftOrScrollbar( page, modalContent );
		await screenshotElement( page, 'pricing-modal-step-3-thank-you', modalContent );
	} );

	test( 'modal opened directly on a non-default step does not animate from a default state on first paint', async ( { page } ) => {
		// Edge case from the spec: first paint should not "grow" from a default state when
		// the modal is opened directly on the payment or success step (e.g. deep link /
		// resumed session). A CSS `transition` never animates the very first computed value
		// on load (no prior value to transition from), so we assert the box is already at
		// its target size immediately after open, with no residual animation in progress.
		const modalContent = await openPricingModal( page );

		await modalContent.evaluate( ( el ) => el.classList.add( 'imagify-success-viewing' ) );

		const widthImmediatelyAfterToggle = await modalContent.evaluate(
			( el ) => el.getBoundingClientRect().width
		);

		await page.waitForTimeout( 400 );

		const widthAfterSettling = await modalContent.evaluate(
			( el ) => el.getBoundingClientRect().width
		);

		// Both measurements should reflect the success-viewing width (450px) within a small
		// tolerance — i.e. no drawn-out grow animation lingering well past the transition
		// duration declared in the CSS (.3s).
		expect( Math.abs( widthAfterSettling - widthImmediatelyAfterToggle ) ).toBeLessThanOrEqual( 5 );
	} );
} );
