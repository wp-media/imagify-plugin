// @testrail-case: 26359
// @steps-hash: 1a3a5e30f287
// @grounding: 542b43d4
// @status: green 2026-07-03
//
// Grounding note: TestRail prose names the MCP adapter endpoint, but the grounded spec
// (.claude/testrail/specs/mcp-abilities.md, "How to invoke") establishes the Abilities REST
// discovery endpoint as the observable entry point for C26359 — grounding wins over prose.
import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';

test('TR-26359: MCP - Endpoint discovery - GET returns all 7 ability slugs', async ({ page }) => {
	await test.step('log in', async () => {
		await loginAsAdmin(page);
	});

	await test.step('Send a GET request to the abilities discovery endpoint as an administrator.', async () => {
		const result = await page.evaluate(async () => {
			const nonceResp = await fetch('/wp-admin/admin-ajax.php?action=rest-nonce', { credentials: 'same-origin' });
			const nonce = (await nonceResp.text()).trim();
			const resp = await fetch('/wp-json/wp-abilities/v1/abilities', {
				headers: { 'X-WP-Nonce': nonce },
				credentials: 'same-origin',
			});
			return { status: resp.status, body: await resp.text() };
		});

		expect.soft(result.status, 'checks: HTTP 200 is returned').toBe(200);

		let slugs: string[] = [];
		try {
			const data = JSON.parse(result.body);
			slugs = Array.isArray(data) ? data.map((a: { name: string }) => a.name) : [];
		} catch {
			// leave slugs empty — the per-slug assertions below will report the failure
		}
		const expected = [
			'imagify/get-account',
			'imagify/get-media-status',
			'imagify/get-nextgen-coverage',
			'imagify/get-settings',
			'imagify/get-stats',
			'imagify/optimize-media',
			'imagify/update-settings',
		];
		for (const slug of expected) {
			expect.soft(slugs, `checks: response body lists ability slug ${slug}`).toContain(slug);
		}
	});
});
