<?php

namespace Outstand\WP\Forms;

class Settings {

	/**
	 * Settings option key.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'outstand_forms_settings';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'outstand-forms-settings';

	/**
	 * Register the module.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Add the settings page to the admin menu.
	 *
	 * @return void
	 */
	public function add_settings_page(): void {

		add_options_page(
			__( 'Outstand Forms', 'outstand-forms' ),
			__( 'Outstand Forms', 'outstand-forms' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Register the settings.
	 *
	 * @return void
	 */
	public function register_settings(): void {

		register_setting(
			self::PAGE_SLUG,
			self::OPTION_NAME,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
				'default'           => [
					'site_key'   => '',
					'secret_key' => '',
				],
			]
		);

		add_settings_section(
			'outstand_forms_turnstile_section',
			__( 'Cloudflare Turnstile', 'outstand-forms' ),
			[ $this, 'render_section_description' ],
			self::PAGE_SLUG
		);

		add_settings_field(
			'outstand_forms_turnstile_site_key',
			__( 'Site Key', 'outstand-forms' ),
			[ $this, 'render_site_key_field' ],
			self::PAGE_SLUG,
			'outstand_forms_turnstile_section'
		);

		add_settings_field(
			'outstand_forms_turnstile_secret_key',
			__( 'Secret Key', 'outstand-forms' ),
			[ $this, 'render_secret_key_field' ],
			self::PAGE_SLUG,
			'outstand_forms_turnstile_section'
		);
	}

	/**
	 * Sanitize the settings.
	 *
	 * @param array $input The input values.
	 * @return array
	 */
	public function sanitize_settings( $input ): array {
		$sanitized = [];

		$sanitized['site_key']   = sanitize_text_field( $input['site_key'] ?? '' );
		$sanitized['secret_key'] = sanitize_text_field( $input['secret_key'] ?? '' );

		return $sanitized;
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::PAGE_SLUG );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the section description.
	 *
	 * @return void
	 */
	public function render_section_description(): void {
		printf(
			'<p>%s <a href="%s" target="_blank" rel="noopener noreferrer">%s</a></p>',
			esc_html__( 'Configure Cloudflare Turnstile to protect your forms from spam and abuse.', 'outstand-forms' ),
			esc_url( 'https://developers.cloudflare.com/turnstile/get-started/' ),
			esc_html__( 'Get your API keys', 'outstand-forms' )
		);
	}

	/**
	 * Render the site key field.
	 *
	 * @return void
	 */
	public function render_site_key_field(): void {
		$settings = get_option( self::OPTION_NAME, [] );
		$value    = $settings['site_key'] ?? '';
		?>
		<input
			type="text"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[site_key]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			autocomplete="off"
		>
		<p class="description">
			<?php esc_html_e( 'Enter your Cloudflare Turnstile site key.', 'outstand-forms' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the secret key field.
	 *
	 * @return void
	 */
	public function render_secret_key_field(): void {
		$settings = get_option( self::OPTION_NAME, [] );
		$value    = $settings['secret_key'] ?? '';
		?>
		<input
			type="password"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[secret_key]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			autocomplete="off"
		>
		<p class="description">
			<?php esc_html_e( 'Enter your Cloudflare Turnstile secret key.', 'outstand-forms' ); ?>
		</p>
		<?php
	}
}
