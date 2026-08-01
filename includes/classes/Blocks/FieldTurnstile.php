<?php

namespace Outstand\WP\Forms\Blocks;

use Outstand\WP\Forms\FormBlockParser;
use Outstand\WP\Forms\Settings;
use WP_Error;
use WP_REST_Request;

class FieldTurnstile extends AbstractBlock {

	/**
	 * {@inheritDoc}
	 */
	public function get_name(): string {
		return 'field-turnstile';
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_script' ], 10 );
		add_filter( 'outstand_forms_rest_form_submit_args', [ $this, 'register_form_submit_args' ] );
		add_filter( 'outstand_forms_form_pre_submission_check', [ $this, 'verify_form_submission' ], 10, 2 );
	}

	/**
	 * Registers the Turnstile script.
	 *
	 * @return void
	 */
	public function register_script(): void {

		wp_register_script(
			'cloudflare-turnstile',
			'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=osfTurnstileReady',
			[],
			null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			[
				'strategy'  => 'defer',
				'in_footer' => true,
			]
		);
	}

	/**
	 * Register the Turnstile response parameter for the submit endpoint.
	 *
	 * @param array $additional_args Additional endpoint arguments.
	 * @return array
	 */
	public function register_form_submit_args( array $additional_args ): array {

		$additional_args['cf-turnstile-response'] = [
			'description'       => __( 'Turnstile verification token.', 'outstand-forms' ),
			'type'              => 'string',
			'required'          => false,
			'sanitize_callback' => 'sanitize_text_field',
		];

		return $additional_args;
	}

	/**
	 * Determine whether Turnstile is fully configured, i.e. both a site key
	 * and a secret key are set.
	 *
	 * Both keys are required end to end: the site key is needed for the
	 * widget to render on the frontend (@see render.php), and the secret
	 * key is needed to verify the resulting token on the backend
	 * (@see self::verify_form_submission()). This single test is shared by
	 * both sides so they can never disagree about whether Turnstile is
	 * usable.
	 *
	 * @return bool
	 */
	public static function is_configured(): bool {

		$settings   = get_option( Settings::OPTION_NAME, [] );
		$site_key   = $settings['site_key'] ?? '';
		$secret_key = $settings['secret_key'] ?? '';

		return ! empty( $site_key ) && ! empty( $secret_key );
	}

	/**
	 * Verify the Turnstile token during form submission.
	 *
	 * @param true|WP_Error   $result  The current check result.
	 * @param WP_REST_Request $request The REST request.
	 * @return true|WP_Error
	 */
	public function verify_form_submission( $result, WP_REST_Request $request ) {

		// Short-circuit if a previous check already failed.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$form_id = $request->get_param( 'form_id' );
		$post_id = $request->get_param( 'post_id' );

		$parser = new FormBlockParser();
		$block  = $parser->find_block( $form_id, $post_id, 'osf/' . $this->get_name() );

		// No Turnstile block in this form, nothing to check.
		if ( null === $block ) {
			return $result;
		}

		// When Turnstile isn't configured, the widget cannot render on the
		// frontend (see render.php), so a token can never exist. Demanding
		// one here would not protect the form, it would take it offline:
		// every submission would fail. Skip verification instead and rely
		// on the editor warning to make the misconfiguration visible.
		if ( ! self::is_configured() ) {
			return $result;
		}

		$settings   = get_option( Settings::OPTION_NAME, [] );
		$secret_key = $settings['secret_key'] ?? '';
		$token      = $request->get_param( 'cf-turnstile-response' );

		if ( empty( $token ) ) {
			return new WP_Error(
				'turnstile_failed',
				__( 'Security verification failed.', 'outstand-forms' ),
				[ 'status' => 403 ]
			);
		}

		$verified = $this->verify_turnstile( $token, $secret_key );

		if ( ! $verified ) {
			return new WP_Error(
				'turnstile_failed',
				__( 'Security verification failed.', 'outstand-forms' ),
				[ 'status' => 403 ]
			);
		}

		return $result;
	}

	/**
	 * Verify the Turnstile token with Cloudflare.
	 *
	 * @param string $token  The Turnstile token.
	 * @param string $secret The secret key.
	 * @return bool
	 */
	protected function verify_turnstile( string $token, string $secret ): bool {

		$response = wp_remote_post(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			[
				'body' => [
					'secret'   => $secret,
					'response' => $token,
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body, true );

		return $body['success'] ?? false;
	}
}
