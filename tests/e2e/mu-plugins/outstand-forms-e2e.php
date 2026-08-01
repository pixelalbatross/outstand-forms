<?php
/**
 * Plugin Name: Outstand Forms E2E Helpers
 * Description: Test-environment-only helpers for the Playwright E2E suite. Mapped into the wp-env tests environment via .wp-env.json.
 */

namespace Outstand\WP\Forms\E2E;

// Disable the per-IP rate limit so repeated E2E submissions never trip 429.
add_filter( 'outstand_forms_rate_limit', '__return_zero' );

// Force Turnstile settings to always read as unconfigured on the e2e site, so
// the "unconfigured" suite is deterministic regardless of what a developer
// may have saved locally through the settings screen.
//
// This file is mounted into the tests environment, which is also where PHPUnit
// runs, so the override is confined to web requests. Applying it under CLI
// would make the settings and Turnstile unit tests unable to observe a
// configured state.
if ( 'cli' !== PHP_SAPI ) {
	add_filter(
		'pre_option_outstand_forms_settings',
		static function () {
			return [
				'site_key'   => '',
				'secret_key' => '',
			];
		}
	);
}
