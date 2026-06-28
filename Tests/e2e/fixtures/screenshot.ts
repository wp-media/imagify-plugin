import type { Locator, Page } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

const SCREENSHOTS_DIR = path.join( __dirname, '..', '.e2e-screenshots' );

/**
 * Scroll a locator into view, then capture a viewport screenshot.
 *
 * Always use this instead of `page.screenshot()` directly — taking a
 * screenshot without scrolling captures the top of the page rather than
 * the element under test (session learning #1045).
 */
export async function screenshotElement(
	page: Page,
	name: string,
	locator: Locator,
): Promise<string> {
	if ( ! fs.existsSync( SCREENSHOTS_DIR ) ) {
		fs.mkdirSync( SCREENSHOTS_DIR, { recursive: true } );
	}
	await locator.scrollIntoViewIfNeeded();
	const filePath = path.join( SCREENSHOTS_DIR, `${ name }.png` );
	await page.screenshot( { path: filePath, fullPage: false } );
	return filePath;
}
