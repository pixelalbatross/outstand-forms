<?php

namespace Outstand\WP\Forms\Tests\Unit;

use WP_REST_Request;

class RestFormsTest extends \WP_UnitTestCase {

	private const ROUTE = '/outstand-forms/v1/forms/submit';

	private const FORM_CONTENT = '<!-- wp:osf/form {"formId":"form-a"} --><!-- wp:osf/form-fields --><!-- wp:osf/field-input {"fieldId":1,"type":"email","required":true,"label":"Email"} /--><!-- wp:osf/field-input {"fieldId":2,"type":"text","pattern":"[0-9]+","label":"Code"} /--><!-- /wp:osf/form-fields --><!-- /wp:osf/form -->';

	private const NUMBER_FORM_CONTENT = '<!-- wp:osf/form {"formId":"form-b"} --><!-- wp:osf/form-fields --><!-- wp:osf/field-input {"fieldId":3,"type":"number","required":true,"label":"Quantity"} /--><!-- wp:osf/field-input {"fieldId":4,"type":"number","label":"Optional Amount"} /--><!-- /wp:osf/form-fields --><!-- /wp:osf/form -->';

	/**
	 * The post holding the test form.
	 *
	 * @var int
	 */
	private int $post_id;

	/**
	 * Set up a form post and neutralize the rate limiter.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->post_id = self::factory()->post->create( [ 'post_content' => self::FORM_CONTENT ] );

		add_filter( 'outstand_forms_rate_limit', '__return_zero' );
	}

	/**
	 * Reset rate limiter state between tests.
	 */
	public function tear_down(): void {
		remove_filter( 'outstand_forms_rate_limit', '__return_zero' );
		unset( $_SERVER['REMOTE_ADDR'] );

		parent::tear_down();
	}

	/**
	 * A valid submission must return 200 and fire outstand_forms_form_submitted.
	 */
	public function test_valid_submission_succeeds_and_fires_action(): void {
		$captured = null;
		add_action(
			'outstand_forms_form_submitted',
			function ( $form_id, $post_id, $sanitized_data, $form_data ) use ( &$captured ) {
				$captured = [
					'form_id'        => $form_id,
					'post_id'        => $post_id,
					'sanitized_data' => $sanitized_data,
					'form_data'      => $form_data,
				];
			},
			10,
			4
		);

		$response = $this->dispatch_submission(
			[
				'field_1' => 'user@example.com',
				'field_2' => '12345',
			]
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
		$this->assertSame( 'form-a', $captured['form_id'] );
		$this->assertSame( $this->post_id, $captured['post_id'] );
		$this->assertSame( 'user@example.com', $captured['sanitized_data']['field_1'] );
		$this->assertArrayHasKey( 'field_configs', $captured['form_data'] );
	}

	/**
	 * Invalid fields must return 400 with per-field arrays of failed rule names.
	 */
	public function test_invalid_submission_returns_field_errors(): void {
		$response = $this->dispatch_submission(
			[
				'field_1' => '',
				'field_2' => 'abc',
			]
		);

		$this->assertSame( 400, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( 'validation_failed', $data['code'] );
		$this->assertSame( [ 'required' ], $data['data']['errors']['field_1'] );
		$this->assertSame( [ 'pattern' ], $data['data']['errors']['field_2'] );
	}

	/**
	 * An empty required number field must fail validation instead of being coerced to 0.
	 */
	public function test_empty_required_number_field_fails_validation(): void {
		$number_post_id = self::factory()->post->create( [ 'post_content' => self::NUMBER_FORM_CONTENT ] );

		$response = $this->dispatch_submission(
			[
				'field_3' => '',
			],
			'form-b',
			$number_post_id
		);

		$this->assertSame( 400, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( 'validation_failed', $data['code'] );
		$this->assertSame( [ 'required' ], $data['data']['errors']['field_3'] );
	}

	/**
	 * An empty optional number field must not produce a spurious validation error.
	 */
	public function test_empty_optional_number_field_succeeds(): void {
		$number_post_id = self::factory()->post->create( [ 'post_content' => self::NUMBER_FORM_CONTENT ] );

		$response = $this->dispatch_submission(
			[
				'field_3' => '5',
				'field_4' => '',
			],
			'form-b',
			$number_post_id
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
	}

	/**
	 * An unknown form must return invalid_form.
	 */
	public function test_unknown_form_returns_invalid_form(): void {
		$response = $this->dispatch_submission( [], 'missing-form' );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'invalid_form', $response->get_data()['code'] );
	}

	/**
	 * A WP_Error from the pre-submission check must abort the submission.
	 */
	public function test_pre_submission_check_can_abort(): void {
		$abort = function () {
			return new \WP_Error( 'spam_detected', 'Nope.', [ 'status' => 403 ] );
		};
		add_filter( 'outstand_forms_form_pre_submission_check', $abort );

		$response = $this->dispatch_submission(
			[
				'field_1' => 'user@example.com',
				'field_2' => '12345',
			]
		);

		remove_filter( 'outstand_forms_form_pre_submission_check', $abort );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'spam_detected', $response->get_data()['code'] );
	}

	/**
	 * Submissions beyond the per-IP budget must return 429.
	 */
	public function test_rate_limit_returns_429(): void {
		remove_filter( 'outstand_forms_rate_limit', '__return_zero' );

		$_SERVER['REMOTE_ADDR'] = '203.0.113.7';

		$limit_to_two = function () {
			return 2;
		};
		add_filter( 'outstand_forms_rate_limit', $limit_to_two );

		$params = [
			'field_1' => 'user@example.com',
			'field_2' => '12345',
		];

		$first  = $this->dispatch_submission( $params );
		$second = $this->dispatch_submission( $params );
		$third  = $this->dispatch_submission( $params );

		remove_filter( 'outstand_forms_rate_limit', $limit_to_two );
		delete_transient( 'outstand_forms_rl_' . md5( '203.0.113.7' ) );

		$this->assertSame( 200, $first->get_status() );
		$this->assertSame( 200, $second->get_status() );
		$this->assertSame( 429, $third->get_status() );
		$this->assertSame( 'rate_limited', $third->get_data()['code'] );
	}

	/**
	 * Dispatch a submission request against the REST server.
	 *
	 * @param array    $fields  The field values.
	 * @param string   $form_id The form ID. Defaults to the test form.
	 * @param int|null $post_id The post ID containing the form. Defaults to the test post.
	 * @return \WP_REST_Response The dispatched response.
	 */
	private function dispatch_submission( array $fields, string $form_id = 'form-a', ?int $post_id = null ): \WP_REST_Response {
		$request = new WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array_merge(
				[
					'form_id' => $form_id,
					'post_id' => $post_id ?? $this->post_id,
				],
				$fields
			)
		);

		return rest_get_server()->dispatch( $request );
	}
}
