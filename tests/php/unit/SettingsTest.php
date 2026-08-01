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
	 * The register() method must hook the settings page and settings registration.
	 */
	public function test_register_hooks_admin_actions(): void {
		$settings = new Settings();
		$settings->register();

		$this->assertSame( 10, has_action( 'admin_menu', [ $settings, 'add_settings_page' ] ) );
		$this->assertSame( 10, has_action( 'admin_init', [ $settings, 'register_settings' ] ) );
	}

	/**
	 * The add_settings_page() method must add the page under Settings with the manage_options capability.
	 */
	public function test_add_settings_page_registers_options_submenu_page(): void {
		global $submenu;

		$admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		$settings = new Settings();
		$settings->add_settings_page();

		$this->assertArrayHasKey( 'options-general.php', $submenu );

		$slugs = wp_list_pluck( $submenu['options-general.php'], 2 );
		$this->assertContains( Settings::PAGE_SLUG, $slugs );

		$capabilities = wp_list_pluck( $submenu['options-general.php'], 1 );
		$page_index   = array_search( Settings::PAGE_SLUG, $slugs, true );
		$this->assertSame( 'manage_options', $capabilities[ $page_index ] );
	}

	/**
	 * The register_settings() method must register the option with its default and sanitize callback.
	 */
	public function test_register_settings_registers_option_with_default(): void {
		$settings = new Settings();
		$settings->register_settings();

		$registered = get_registered_settings();

		$this->assertArrayHasKey( Settings::OPTION_NAME, $registered );
		$this->assertSame( 'array', $registered[ Settings::OPTION_NAME ]['type'] );
		$this->assertSame(
			[
				'site_key'   => '',
				'secret_key' => '',
			],
			$registered[ Settings::OPTION_NAME ]['default']
		);
		$this->assertSame( [ $settings, 'sanitize_settings' ], $registered[ Settings::OPTION_NAME ]['sanitize_callback'] );
	}

	/**
	 * The register_settings() method must register the section and both fields on the settings page.
	 */
	public function test_register_settings_registers_section_and_fields(): void {
		global $wp_settings_sections, $wp_settings_fields;

		$settings = new Settings();
		$settings->register_settings();

		$this->assertArrayHasKey( 'os_forms_turnstile_section', $wp_settings_sections[ Settings::PAGE_SLUG ] );
		$this->assertArrayHasKey(
			'os_forms_turnstile_site_key',
			$wp_settings_fields[ Settings::PAGE_SLUG ]['os_forms_turnstile_section']
		);
		$this->assertArrayHasKey(
			'os_forms_turnstile_secret_key',
			$wp_settings_fields[ Settings::PAGE_SLUG ]['os_forms_turnstile_section']
		);
	}

	/**
	 * The registered sanitize callback must actually run when the option is updated,
	 * proving the wiring (not just sanitize_settings() in isolation).
	 */
	public function test_registered_sanitize_callback_runs_through_update_option(): void {
		$settings = new Settings();
		$settings->register_settings();

		update_option(
			Settings::OPTION_NAME,
			[
				'site_key'   => "  example-site-key\n",
				'secret_key' => 'example-secret-key',
				'unexpected' => 'stripped',
			]
		);

		$stored = get_option( Settings::OPTION_NAME );

		$this->assertSame(
			[
				'site_key'   => 'example-site-key',
				'secret_key' => 'example-secret-key',
			],
			$stored
		);
	}

	/**
	 * WEAK SANITIZATION: no encryption or hashing is applied to the secret key.
	 * It is written to wp_options in plaintext like any other option value.
	 */
	public function test_secret_key_is_persisted_in_plaintext(): void {
		global $wpdb;

		$settings = new Settings();
		$settings->register_settings();

		update_option( Settings::OPTION_NAME, [ 'secret_key' => 'example-secret-key' ] );

		$raw = $wpdb->get_var(
			$wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", Settings::OPTION_NAME ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		$this->assertStringContainsString( 'example-secret-key', $raw );
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

	/**
	 * Missing keys must sanitize to an empty string rather than being omitted.
	 */
	public function test_sanitize_settings_defaults_missing_keys_to_empty_string(): void {
		$settings = new Settings();

		$this->assertSame(
			[
				'site_key'   => '',
				'secret_key' => '',
			],
			$settings->sanitize_settings( [] )
		);
	}

	/**
	 * WordPress' sanitize_text_field() strips a script tag including its content,
	 * so a script tag payload sanitizes to an empty string rather than being
	 * rendered inert-but-present.
	 */
	public function test_sanitize_settings_strips_script_tags_and_content(): void {
		$settings  = new Settings();
		$sanitized = $settings->sanitize_settings(
			[
				'site_key'   => '<script>alert(1)</script>example-site-key',
				'secret_key' => '<script>alert(1)</script>',
			]
		);

		$this->assertSame( 'example-site-key', $sanitized['site_key'] );
		$this->assertSame( '', $sanitized['secret_key'] );
	}

	/**
	 * Documents actual behaviour: non-array input is tolerated rather than
	 * rejected. `$input['key'] ?? ''` resolves to the default for any scalar
	 * or null input without raising a warning or exception, so malformed
	 * input silently produces the empty defaults instead of failing loudly.
	 */
	public function test_sanitize_settings_tolerates_non_array_input(): void {
		$settings = new Settings();

		foreach ( [ 'not-an-array', 123, null ] as $input ) {
			$sanitized = $settings->sanitize_settings( $input );

			$this->assertSame(
				[
					'site_key'   => '',
					'secret_key' => '',
				],
				$sanitized,
				'Unexpected result for input: ' . wp_json_encode( $input )
			);
		}
	}

	/**
	 * WEAK SANITIZATION: no maximum length is enforced, so an arbitrarily
	 * long value is stored verbatim even though Cloudflare Turnstile keys are
	 * short, fixed-format tokens. Nothing here guards against a
	 * pathologically large value being written to wp_options.
	 */
	public function test_sanitize_settings_does_not_enforce_a_maximum_length(): void {
		$settings   = new Settings();
		$long_value = str_repeat( 'a', 5000 );

		$sanitized = $settings->sanitize_settings( [ 'site_key' => $long_value ] );

		$this->assertSame( 5000, strlen( $sanitized['site_key'] ) );
	}

	/**
	 * The render_settings_page() method must not output anything for a user who lacks manage_options.
	 */
	public function test_render_settings_page_requires_manage_options(): void {
		$subscriber_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber_id );

		$settings = new Settings();

		ob_start();
		$settings->render_settings_page();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/**
	 * The render_settings_page() method must render the settings form for an administrator.
	 */
	public function test_render_settings_page_outputs_form_for_administrator(): void {
		$admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		$settings = new Settings();
		$settings->register_settings();

		ob_start();
		$settings->render_settings_page();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<form action="options.php" method="post">', $output );
		$this->assertStringContainsString( 'Cloudflare Turnstile', $output );
		$this->assertStringContainsString( 'name="outstand_forms_settings[site_key]"', $output );
		$this->assertStringContainsString( 'name="outstand_forms_settings[secret_key]"', $output );
	}

	/**
	 * The render_section_description() method must link out to the Cloudflare Turnstile docs.
	 */
	public function test_render_section_description_outputs_docs_link(): void {
		$settings = new Settings();

		ob_start();
		$settings->render_section_description();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'https://developers.cloudflare.com/turnstile/get-started/', $output );
	}

	/**
	 * The site key field must render as a plain text input reflecting the stored value.
	 */
	public function test_render_site_key_field_outputs_stored_value(): void {
		update_option(
			Settings::OPTION_NAME,
			[
				'site_key'   => 'example-site-key',
				'secret_key' => '',
			]
		);

		$settings = new Settings();

		ob_start();
		$settings->render_site_key_field();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'type="text"', $output );
		$this->assertStringContainsString( 'value="example-site-key"', $output );
	}

	/**
	 * The secret key field must render as a password input. Note the stored
	 * value is still echoed into the `value` attribute, so it is visible in
	 * the page source to any user who can reach the settings screen.
	 */
	public function test_render_secret_key_field_outputs_stored_value_as_password_input(): void {
		update_option(
			Settings::OPTION_NAME,
			[
				'site_key'   => '',
				'secret_key' => 'example-secret-key',
			]
		);

		$settings = new Settings();

		ob_start();
		$settings->render_secret_key_field();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'type="password"', $output );
		$this->assertStringContainsString( 'value="example-secret-key"', $output );
	}

	/**
	 * Both fields must default to an empty value when the option has never been saved.
	 */
	public function test_render_fields_default_to_empty_value_when_option_missing(): void {
		delete_option( Settings::OPTION_NAME );

		$settings = new Settings();

		ob_start();
		$settings->render_site_key_field();
		$site_key_output = ob_get_clean();

		ob_start();
		$settings->render_secret_key_field();
		$secret_key_output = ob_get_clean();

		$this->assertStringContainsString( 'value=""', $site_key_output );
		$this->assertStringContainsString( 'value=""', $secret_key_output );
	}
}
