import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { wpCli } from '../fixtures/wp-cli';

/**
 * Edit Media screen — the Imagify buttons run through AJAX (#434).
 *
 * Clicking Optimize on `post.php?post=<id>&action=edit` must send an `admin-ajax.php` request and update the
 * panel in place, instead of going through `admin-post.php` and reloading the page.
 *
 * The Optimize button is only rendered when a valid API key is configured. Like reset-internal-state.spec.ts,
 * a missing key or seed is a hard failure (no test.skip), so it is visible instead of silently skipped.
 *
 * Seed requirement: run bin/dev-seed.sh with IMAGIFY_TESTS_API_KEY set (it also imports a test image).
 */
test.describe( 'Edit Media — AJAX buttons (#434)', () => {
	let attachmentId: string;

	test.beforeEach( async ( { page } ) => {
		expect(
			process.env.IMAGIFY_TESTS_API_KEY,
			'IMAGIFY_TESTS_API_KEY env var must be set — the Optimize button is only rendered when a valid API key is configured.'
		).toBeTruthy();

		attachmentId = wpCli( 'post list --post_type=attachment --post_status=inherit --post_mime_type=image --field=ID --posts_per_page=1' ).trim();
		expect( attachmentId, 'No image in the media library — run bin/dev-seed.sh first.' ).toMatch( /^\d+$/ );

		// Start from a media that is not optimized.
		wpCli( `eval 'delete_post_meta( ${ attachmentId }, "_imagify_status" ); delete_post_meta( ${ attachmentId }, "_imagify_data" );'` );

		await loginAsAdmin( page );
		await page.goto( `/wp-admin/post.php?post=${ attachmentId }&action=edit` );
	} );

	test( 'Panel is printed in the container the JS updates, under the Imagify heading', async ( { page } ) => {
		const container = page.locator( '.misc-pub-imagify .imagify-data-actions-container' );

		await expect( container ).toHaveAttribute( 'data-id', attachmentId );
		await expect( container ).toHaveAttribute( 'data-context', 'wp' );
		await expect( container.locator( 'a.button-imagify-optimize' ) ).toBeVisible();
		await expect( page.locator( '.misc-pub-imagify h4', { hasText: 'Imagify' } ) ).toBeVisible();
	} );

	test( 'Optimize sends an admin-ajax.php request and does not reload the page', async ( { page } ) => {
		const requested: string[] = [];
		const navigated: string[] = [];

		page.on( 'request', ( request ) => requested.push( request.url() ) );
		page.on( 'framenavigated', ( frame ) => {
			if ( frame === page.mainFrame() ) {
				navigated.push( frame.url() );
			}
		} );

		const ajaxRequest = page.waitForRequest(
			( request ) => request.url().includes( 'admin-ajax.php' ) && request.url().includes( 'action=imagify_manual_optimize' )
		);

		await page.locator( '.imagify-data-actions-container a.button-imagify-optimize' ).click();
		await ajaxRequest;

		// Let the page settle: a plain link would have navigated to admin-post.php and back by now.
		await page.waitForLoadState( 'networkidle' );

		expect( requested.filter( ( url ) => url.includes( 'admin-post.php' ) ) ).toHaveLength( 0 );
		expect( navigated ).toHaveLength( 0 );
		await expect( page ).toHaveURL( new RegExp( `post\\.php\\?post=${ attachmentId }&action=edit` ) );
		await expect( page.locator( '.imagify-data-actions-container' ) ).toHaveAttribute( 'data-id', attachmentId );
	} );
} );
