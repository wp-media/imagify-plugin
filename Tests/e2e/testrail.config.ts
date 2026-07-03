import { defineConfig, devices } from '@playwright/test';

/**
 * TestRail release-QA config — separate from `playwright.config.ts` (the permanent
 * regression suite under `./specs`) because `testrail-run-agent` executes one `.spec.ts`
 * per TestRail case from the committed cache `./testrail-cases/` and must not mix those
 * into the regression suite's testDir. Cached case specs are kept across runs (content-
 * hash header decides reuse vs re-translation) and are promotion candidates for `./specs`.
 *
 * The run agent invokes this per case, one `npx playwright test` call at a time. `$OUT` is
 * captured as an ABSOLUTE path from repo root BEFORE `cd`ing into `Tests/e2e/`. stderr is
 * kept out of `results.json` — any npx/npm/Node warning on stderr would otherwise corrupt
 * the JSON the run agent parses for pass/fail:
 *
 *   OUT="$(pwd)/.ai/testrail/$RUN_ID/playwright/$CASE_ID"   # captured at repo root
 *   mkdir -p "$OUT"
 *   (
 *     cd Tests/e2e &&
 *     IMAGIFY_BASE_URL="$E2E_URL" IMAGIFY_ADMIN_USER="$WP_USER" IMAGIFY_ADMIN_PASS="$WP_PASS" \
 *     TESTRAIL_OUTPUT_DIR="$OUT/artifacts" \
 *     npx playwright test --config=testrail.config.ts "testrail-cases/case-$CASE_ID.spec.ts" \
 *       --reporter=json > "$OUT/results.json" 2> "$OUT/stderr.log"
 *   )
 *
 * TESTRAIL_OUTPUT_DIR must be a SUBDIR of $OUT ($OUT/artifacts), never $OUT itself:
 * Playwright CLEANS outputDir at test start, so a results.json redirected into the same
 * directory is deleted mid-run (verified live 2026-07-03). Playwright then creates a
 * per-test subdirectory inside outputDir — trace/video land at
 * `$OUT/artifacts/<test-result-dir>/trace.zip`; resolve by glob, never a flat path.
 * Node >= 18 is required (Playwright bundles global-fetch-based code).
 *
 * HAR capture is intentionally dropped: `use.recordHar` needs a static path per test and
 * doesn't compose with per-case dynamic naming. The trace.zip contains the network tab in
 * the trace viewer, which covers the same debugging need.
 */
export default defineConfig({
	testDir: './testrail-cases',
	fullyParallel: false, // one case, one browser, at a time
	workers: 1,
	retries: 0, // TestRail evidence is a single authoritative attempt, not a flaky-retry target
	reporter: 'list', // the run agent always overrides with `--reporter=json` on the CLI

	outputDir: process.env.TESTRAIL_OUTPUT_DIR ?? './.testrail-artifacts',

	use: {
		baseURL: process.env.IMAGIFY_BASE_URL ?? 'http://localhost:8801',
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
