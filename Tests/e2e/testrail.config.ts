import { defineConfig, devices } from '@playwright/test';

/**
 * TestRail release-QA config — separate from `playwright.config.ts` (the permanent
 * regression suite under `./specs`) because `testrail-run-agent` generates one throwaway
 * `.spec.ts` per TestRail case into `./.testrail-tmp/` and must not mix them into the
 * committed suite's testDir.
 *
 * The run agent invokes this per case, one `npx playwright test` call at a time. `$OUT` is
 * captured as an ABSOLUTE path from repo root BEFORE `cd`ing into `Tests/e2e/` — every other
 * `.ai/...` path the run agent uses (config, dashboard, trace links) is repo-root-relative, so
 * this must not be a bare relative path once you're inside `Tests/e2e/`. stderr is kept out of
 * `results.json` — any npx/npm/Node warning on stderr would otherwise corrupt the JSON the run
 * agent parses for pass/fail:
 *
 *   OUT="$(pwd)/.ai/testrail/$RUN_ID/playwright/$CASE_ID"   # captured at repo root
 *   mkdir -p "$OUT"
 *   (
 *     cd Tests/e2e &&
 *     IMAGIFY_BASE_URL="$E2E_URL" IMAGIFY_ADMIN_USER="$WP_USER" IMAGIFY_ADMIN_PASS="$WP_PASS" \
 *     TESTRAIL_OUTPUT_DIR="$OUT" \
 *     npx playwright test --config=testrail.config.ts ".testrail-tmp/case-$CASE_ID.spec.ts" \
 *       --reporter=json > "$OUT/results.json" 2> "$OUT/stderr.log"
 *   )
 *
 * Evidence lands directly under TESTRAIL_OUTPUT_DIR — no post-hoc relocation step needed
 * (unlike the old Canary flow, which had to `mv ~/.canary/sessions/<id>` after the fact).
 *
 * HAR capture is intentionally dropped: Playwright's `use.recordHar` needs a static path per
 * test and doesn't compose cleanly with per-case dynamic naming. The trace.zip already
 * contains the network tab in the trace viewer, which covers the same debugging need.
 */
export default defineConfig({
	testDir: './.testrail-tmp',
	fullyParallel: false, // one case, one browser, at a time — same requirement as before
	workers: 1,
	retries: 0, // TestRail evidence is a single authoritative attempt, not a flaky-retry target
	reporter: 'list', // the run agent always overrides with `--reporter=json` on the CLI

	outputDir: process.env.TESTRAIL_OUTPUT_DIR ?? './.testrail-tmp/artifacts',

	use: {
		baseURL: process.env.IMAGIFY_BASE_URL ?? 'http://localhost:8888',
		trace: 'on', // always-on, not retain-on-failure — TestRail wants evidence for PASS too
		video: 'on',
		screenshot: 'on',
		viewport: { width: 1440, height: 900 },
	},

	projects: [
		{
			name: 'chromium',
			use: { ...devices['Desktop Chrome'] },
		},
	],

	expect: {
		timeout: 10_000,
	},
	// One test = one whole TestRail case (login + every step via test.step()), not one step —
	// give it more headroom than the main suite's 60s.
	timeout: 120_000,
});
