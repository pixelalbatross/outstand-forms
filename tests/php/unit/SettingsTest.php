<?php

namespace Outstand\WP\Forms\Tests\Unit;

use Outstand\WP\Forms\Settings;

class SettingsTest extends \WP_UnitTestCase {

	/**
	 * Clean the option key between tests.
	 */
	public function tear_down(): void {
		delete_option( Settings::OPTION_NAME );

		parent::tear_down();
	}

	/**
	 * Settings sanitization must strip unexpected values.
	 */
	public function test_sanitize_settings_returns_known_keys_only(): void {
		$settings  = new Settings();
		$sanitized = $settings->sanitize_settings(
			[
				'site_key'   => "  example-site-key\n",
				'secret_key' => 'example-secret-key',
				'malicious'  => 'ignored',
			]
		);

		$this->assertSame(
			[
				'site_key'   => 'example-site-key',
				'secret_key' => 'example-secret-key',
			],
			$sanitized
		);
	}
}
