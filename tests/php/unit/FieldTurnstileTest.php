<?php

namespace Outstand\WP\Forms\Tests\Unit;

use Outstand\WP\Forms\Settings;
use WP_REST_Request;

class FieldTurnstileTest extends \WP_UnitTestCase {

	private const ROUTE = '/outstand-forms/v1/forms/submit';

	private const FORM_WITHOUT_TURNSTILE = '<!-- wp:osf/form {"formId":"form-a"} --><!-- wp:osf/form-fields --><!-- wp:osf/field-input {"fieldId":1,"type":"email","required":true,"label":"Email"} /--><!-- /wp:osf/form-fields --><!-- /wp:osf/form -->';

	private const FORM_WITH_TURNSTILE = '<!-- wp:osf/form {"formId":"form-t"} --><!-- wp:osf/form-fields --><!-- wp:osf/field-input {"fieldId":1,"type":"email","required":true,"label":"Email"} /--><!-- wp:osf/field-turnstile /--><!-- /wp:osf/form-fields --><!-- /wp:osf/form -->';

	/**
	 * Set up shared filters and neutralize the rate limiter.
	 */
	public function set_up(): void {
		parent::set_up();

		add_filter( 'outstand_forms_rate_limit', '__return_zero' );
	}

	/**
	 * Reset options and filters between tests.
	 */
	public function tear_down(): void {
		remove_filter( 'outstand_forms_rate_limit', '__return_zero' );
		delete_option( Settings::OPTION_NAME );

		parent::tear_down();
	}

	/**
	 * When the form has no Turnstile block, verification is skipped entirely.
	 */
	public function test_no_turnstile_block_skips_verification(): void {
		$post_id = self::factory()->post->create( [ 'post_content' => self::FORM_WITHOUT_TURNSTILE ] );

		$response = $this->dispatch_submission(
			[ 'field_1' => 'user@example.com' ],
			'form-a',
			$post_id
		);

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * Regression test: when the Turnstile block is present but the plugin is
	 * unconfigured (no site key and/or no secret key), verification must be
	 * skipped and the submission must proceed. The widget cannot render
	 * without a site key, so a token can never exist; failing closed here
	 * would take every form offline instead of blocking spam.
	 */
	public function test_unconfigured_turnstile_skips_verification(): void {
		delete_option( Settings::OPTION_NAME );

		$post_id = self::factory()->post->create( [ 'post_content' => self::FORM_WITH_TURNSTILE ] );

		$response = $this->dispatch_submission(
			[ 'field_1' => 'user@example.com' ],
			'form-t',
			$post_id
		);

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * When configured, a missing token must fail closed with a 403.
	 */
	public function test_configured_turnstile_missing_token_fails(): void {
		$this->configure_turnstile();

		$post_id = self::factory()->post->create( [ 'post_content' => self::FORM_WITH_TURNSTILE ] );

		$response = $this->dispatch_submission(
			[ 'field_1' => 'user@example.com' ],
			'form-t',
			$post_id
		);

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'turnstile_failed', $response->get_data()['code'] );
	}

	/**
	 * When configured, a token that Cloudflare reports as invalid must fail closed with a 403.
	 */
	public function test_configured_turnstile_invalid_token_fails(): void {
		$this->configure_turnstile();

		$mock_verify_response = $this->mock_turnstile_response( false );
		add_filter( 'pre_http_request', $mock_verify_response, 10, 3 );

		$post_id = self::factory()->post->create( [ 'post_content' => self::FORM_WITH_TURNSTILE ] );

		$response = $this->dispatch_submission(
			[
				'field_1'               => 'user@example.com',
				'cf-turnstile-response' => 'invalid-token',
			],
			'form-t',
			$post_id
		);

		remove_filter( 'pre_http_request', $mock_verify_response );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'turnstile_failed', $response->get_data()['code'] );
	}

	/**
	 * When configured, a token that Cloudflare reports as valid must let the submission proceed.
	 */
	public function test_configured_turnstile_valid_token_succeeds(): void {
		$this->configure_turnstile();

		$mock_verify_response = $this->mock_turnstile_response( true );
		add_filter( 'pre_http_request', $mock_verify_response, 10, 3 );

		$post_id = self::factory()->post->create( [ 'post_content' => self::FORM_WITH_TURNSTILE ] );

		$response = $this->dispatch_submission(
			[
				'field_1'               => 'user@example.com',
				'cf-turnstile-response' => 'valid-token',
			],
			'form-t',
			$post_id
		);

		remove_filter( 'pre_http_request', $mock_verify_response );

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * Set fake Turnstile site and secret keys so the plugin is considered configured.
	 *
	 * @return void
	 */
	private function configure_turnstile(): void {
		update_option(
			Settings::OPTION_NAME,
			[
				'site_key'   => 'test-site-key',
				'secret_key' => 'test-secret-key',
			]
		);
	}

	/**
	 * Build a `pre_http_request` filter callback that short-circuits the
	 * Cloudflare siteverify call with a fake success/failure response.
	 *
	 * @param bool $success Whether Cloudflare should report the token as valid.
	 * @return callable
	 */
	private function mock_turnstile_response( bool $success ): callable {
		return function () use ( $success ) {
			return [
				'body'     => wp_json_encode( [ 'success' => $success ] ),
				'response' => [ 'code' => 200 ],
			];
		};
	}

	/**
	 * Dispatch a submission request against the REST server.
	 *
	 * @param array  $fields  The field values.
	 * @param string $form_id The form ID.
	 * @param int    $post_id The post ID containing the form.
	 * @return \WP_REST_Response The dispatched response.
	 */
	private function dispatch_submission( array $fields, string $form_id, int $post_id ): \WP_REST_Response {
		$request = new WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array_merge(
				[
					'form_id' => $form_id,
					'post_id' => $post_id,
				],
				$fields
			)
		);

		return rest_get_server()->dispatch( $request );
	}
}
