/**
 * External dependencies
 */
const { execFileSync } = require('child_process');

/**
 * WordPress dependencies
 */
const wpScriptsGlobalSetup = require('@wordpress/scripts/config/playwright/global-setup.js');

const TESTS_ENV = 'tests-cli';
const THEME_SLUG = 'twentytwentytwo';
const PLUGIN_SLUG = 'outstand-forms';

/**
 * Runs a WP-CLI command inside the given wp-env environment container.
 *
 * @param {string}   env  wp-env environment name (e.g. `tests-cli`).
 * @param {string[]} args WP-CLI arguments, e.g. [ 'theme', 'activate', 'foo' ].
 * @return {string} Combined stdout/stderr from the command.
 */
function wpEnvRun(env, args) {
	return execFileSync('npx', ['wp-env', 'run', env, 'wp', ...args], {
		encoding: 'utf8',
	});
}

/**
 * Ensures the e2e theme fixture is active on the tests site. Activating an
 * already-active theme is a harmless no-op in WP-CLI, so this is safe to run
 * on every invocation.
 */
function ensureThemeActive() {
	wpEnvRun(TESTS_ENV, ['theme', 'activate', THEME_SLUG]);
}

/**
 * Ensures the plugin under test is active on the tests site. Activating an
 * already-active plugin is a harmless no-op in WP-CLI, so this is safe to
 * run on every invocation.
 */
function ensurePluginActive() {
	wpEnvRun(TESTS_ENV, ['plugin', 'activate', PLUGIN_SLUG]);
}

/**
 * Global setup for the e2e suite.
 *
 * wp-env only re-runs its own plugin/theme activation when its internal
 * config cache changes (in practice: the first `wp-env start` after
 * `wp-env destroy` or a config edit). On every other `wp-env start` —
 * including the common case where wp-env is already running and
 * Playwright's `webServer` just reuses it — nothing re-activates the theme
 * or plugin. A manual deactivation (or a fresh checkout without the fixture
 * state) then silently sticks, and the suite fails with confusing editor UI
 * timeouts instead of a clear environment error.
 *
 * Running this guard before every e2e invocation makes the suite
 * self-healing: it activates the fixture theme/plugin on the tests site (or
 * fails loudly, with a clear message, if it can't) before deferring to the
 * `@wordpress/scripts` global setup, which authenticates and saves the
 * storage state the specs rely on.
 *
 * @param {import('@playwright/test').FullConfig} config The Playwright config.
 */
module.exports = async function globalSetup(config) {
	try {
		ensureThemeActive();
		ensurePluginActive();
	} catch (error) {
		const output = error.stdout || error.stderr || error.message;

		throw new Error(
			'\n\n[e2e global-setup] Could not prepare the wp-env "tests" site: ' +
				`failed to activate theme "${THEME_SLUG}" and/or plugin "${PLUGIN_SLUG}" ` +
				`via \`npx wp-env run ${TESTS_ENV} wp ...\`.\n` +
				'Make sure wp-env is running (`npm run wp-env start`) and that ' +
				`"${PLUGIN_SLUG}" is mapped in .wp-env.json.\n\n` +
				`Original error:\n${output}\n`,
		);
	}

	await wpScriptsGlobalSetup(config);
};
