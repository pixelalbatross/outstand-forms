/**
 * External dependencies
 */
const { defineConfig } = require('@playwright/test');

// Match the ports used by this project's wp-env (see package.json scripts).
process.env.WP_BASE_URL ??= 'http://localhost:8891';

/**
 * WordPress dependencies
 */
const baseConfig = require('@wordpress/scripts/config/playwright.config.js');

module.exports = defineConfig({
	...baseConfig,
	testDir: './tests/e2e',
	webServer: {
		...baseConfig.webServer,
		command: 'WP_ENV_PORT=8890 WP_ENV_TESTS_PORT=8891 npm run wp-env start',
		port: 8891,
	},
});
