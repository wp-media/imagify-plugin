import { execSync } from 'node:child_process';

/**
 * Run a WP-CLI command inside the wp-env `cli` container. Throws on non-zero
 * exit. Used by tests for deterministic state setup (setting options, running
 * crons, querying database state).
 */
export function wpCli( command: string ): string {
	const root = `${ __dirname }/../../..`;
	const cmd = `npx --yes @wordpress/env run cli wp ${ command }`;
	return execSync( cmd, { cwd: root, encoding: 'utf-8', stdio: [ 'ignore', 'pipe', 'pipe' ] } );
}

/**
 * Run an arbitrary shell command from the repo root. Intended for seed scripts
 * and other repo-level tooling.
 */
export function runFromRepoRoot( command: string ): string {
	const root = `${ __dirname }/../../..`;
	return execSync( command, { cwd: root, encoding: 'utf-8', stdio: [ 'ignore', 'pipe', 'pipe' ] } );
}

/**
 * Returns true if the IMAGIFY_API_KEY env var is set to a non-empty string.
 * Use to skip tests that require a live API key.
 */
export function hasApiKey(): boolean {
	return !! process.env.IMAGIFY_API_KEY;
}
