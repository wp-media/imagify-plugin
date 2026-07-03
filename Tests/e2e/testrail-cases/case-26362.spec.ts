// @testrail-case: 26362
// @steps-hash: 90a930142c1f
// @grounding: 542b43d4
// @status: green 2026-07-03
//
// Grounded on .claude/testrail/specs/mcp-abilities.md: get-settings is a READ ability —
// GET on the run endpoint (POST returns 405), response strips api_key AND version.
import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';

test('TR-26362: MCP - execute-ability - imagify/get-settings returns settings without api_key or version', async ({ page }) => {
	await test.step('log in', async () => {
		await loginAsAdmin(page);
	});

	await test.step("Call the execute-ability tool with ability_id: 'imagify/get-settings' and no input args.", async () => {
		const result = await page.evaluate(async () => {
			const nonceResp = await fetch('/wp-admin/admin-ajax.php?action=rest-nonce', { credentials: 'same-origin' });
			const nonce = (await nonceResp.text()).trim();
			const resp = await fetch('/wp-json/wp-abilities/v1/abilities/imagify/get-settings/run', {
				headers: { 'X-WP-Nonce': nonce },
				credentials: 'same-origin',
			});
			return { status: resp.status, body: await resp.text() };
		});

		expect.soft(result.status, 'checks: the get-settings run endpoint returns HTTP 200').toBe(200);

		let settings: Record<string, unknown> = {};
		try {
			settings = JSON.parse(result.body);
		} catch {
			// leave settings empty — the key assertions below will report the failure
		}
		const keys = Object.keys(settings);

		for (const key of ['optimization_level', 'backup', 'auto_optimize', 'convert_to_webp', 'convert_to_avif', 'optimization_format']) {
			expect.soft(keys, `checks: response contains Imagify configuration key ${key}`).toContain(key);
		}
		expect.soft(keys, 'checks: response does NOT contain api_key').not.toContain('api_key');
		expect.soft(keys, 'checks: response does NOT contain version').not.toContain('version');
	});
});
