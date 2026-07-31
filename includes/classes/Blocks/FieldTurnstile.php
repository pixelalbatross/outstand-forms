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

		$settings   = get_option( Settings::OPTION_NAME, [] );
		$secret_key = $settings['secret_key'] ?? '';
		$token      = $request->get_param( 'cf-turnstile-response' );

		if ( empty( $token ) || empty( $secret_key ) ) {
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
