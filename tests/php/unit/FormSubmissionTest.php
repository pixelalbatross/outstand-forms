<?php

namespace Outstand\WP\Forms\Tests\Unit;

use Outstand\WP\Forms\FieldFactory;
use Outstand\WP\Forms\FormSubmission;
use WP_Error;

class FormSubmissionTest extends \WP_UnitTestCase {

	private const FORM_CONTENT = '<!-- wp:osf/form {"formId":"form-a"} --><!-- wp:osf/form-fields --><!-- wp:osf/field-input {"fieldId":1,"type":"email","required":true,"label":"Email"} /--><!-- wp:osf/field-input {"fieldId":2,"type":"text","pattern":"[0-9]+","label":"Code"} /--><!-- /wp:osf/form-fields --><!-- /wp:osf/form -->';

	private const SECRET_FORM_CONTENT = '<!-- wp:osf/form {"formId":"form-secret"} --><!-- wp:osf/form-fields --><!-- wp:osf/field-input {"fieldId":7,"type":"password","required":true,"label":"Passphrase"} /--><!-- /wp:osf/form-fields --><!-- /wp:osf/form -->';

	/**
	 * The post holding the test form.
	 *
	 * @var int
	 */
	private int $post_id;

	/**
	 * The handler under test.
	 *
	 * @var FormSubmission
	 */
	private FormSubmission $handler;

	/**
	 * The URL passed to the last redirect, if any.
	 *
	 * @var string
	 */
	private string $redirect_url = '';

	/**
	 * Set up a form post, capture redirects and neutralize the rate limiter.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->post_id      = self::factory()->post->create( [ 'post_content' => self::FORM_CONTENT ] );
		$this->handler      = new FormSubmission();
		$this->redirect_url = '';

		$_SERVER['REMOTE_ADDR'] = '10.0.0.1';

		add_filter( 'outstand_forms_rate_limit', '__return_zero' );
		add_filter( 'wp_redirect', [ $this, 'capture_redirect' ] );
	}

	/**
	 * Reset request and rate limiter state between tests.
	 */
	public function tear_down(): void {
		remove_filter( 'wp_redirect', [ $this, 'capture_redirect' ] );
		remove_filter( 'outstand_forms_rate_limit', '__return_zero' );

		delete_transient( 'outstand_forms_rl_' . md5( '10.0.0.1' ) );

		$_POST = [];
		$_GET  = [];
		unset( $_SERVER['REMOTE_ADDR'] );

		FieldFactory::reset_instance();

		parent::tear_down();
	}

	/**
	 * Record the redirect target and cancel the redirect so no exit happens.
	 *
	 * @param string $location The redirect target.
	 * @return string
	 */
	public function capture_redirect( $location ) {
		$this->redirect_url = (string) $location;

		return '';
	}

	/**
	 * A POST without the marker field must be left alone entirely.
	 */
	public function test_post_without_marker_is_ignored(): void {
		$_POST = [
			'form_id' => 'form-a',
			'post_id' => (string) $this->post_id,
			'field_1' => 'visitor@example.com',
		];

		$this->handler->handle_submission();

		$this->assertSame( '', $this->redirect_url );
	}

	/**
	 * A missing or forged nonce must abort before anything is processed.
	 */
	public function test_invalid_nonce_fails_closed(): void {
		$fired = false;
		add_action(
			'outstand_forms_form_submitted',
			function () use ( &$fired ) {
				$fired = true;
			}
		);

		$_POST = $this->build_post( [ 'field_1' => 'visitor@example.com' ] );

		$_POST[ FormSubmission::NONCE_FIELD ] = 'not-a-valid-nonce';

		$this->expectException( \WPDieException::class );

		try {
			$this->handler->handle_submission();
		} finally {
			$this->assertFalse( $fired );
			$this->assertSame( '', $this->redirect_url );
		}
	}

	/**
	 * A valid submission must fire the shared action and redirect with the
	 * success arg so a refresh cannot resubmit.
	 */
	public function test_valid_submission_fires_action_and_redirects(): void {
		$captured = null;
		add_action(
			'outstand_forms_form_submitted',
			function ( $form_id, $post_id, $sanitized_data ) use ( &$captured ) {
				$captured = [
					'form_id'        => $form_id,
					'post_id'        => $post_id,
					'sanitized_data' => $sanitized_data,
				];
			},
			10,
			3
		);

		$_POST = $this->build_post(
			[
				'field_1' => 'visitor@example.com',
				'field_2' => '12345',
			]
		);

		$this->handler->handle_submission();

		$this->assertSame( 'form-a', $captured['form_id'] );
		$this->assertSame( $this->post_id, $captured['post_id'] );
		$this->assertSame( 'visitor@example.com', $captured['sanitized_data']['field_1'] );
		$this->assertStringContainsString( FormSubmission::SUCCESS_ARG . '=form-a', $this->redirect_url );
		$this->assertStringNotContainsString( FormSubmission::STATE_ARG, $this->redirect_url );

		$_GET[ FormSubmission::SUCCESS_ARG ] = 'form-a';

		$state = FormSubmission::get_render_state( 'form-a' );

		$this->assertTrue( $state['isSubmitted'] );
		$this->assertFalse( $state['hasSubmissionError'] );
	}

	/**
	 * A failed validation must redirect with a state token that re-renders the
	 * errors and the submitted values.
	 */
	public function test_validation_failure_redirects_with_replayable_state(): void {
		$fired = false;
		add_action(
			'outstand_forms_form_submitted',
			function () use ( &$fired ) {
				$fired = true;
			}
		);

		$_POST = $this->build_post(
			[
				'field_1' => '',
				'field_2' => 'abc',
			]
		);

		$this->handler->handle_submission();

		$this->assertFalse( $fired );

		$token = $this->extract_state_token();

		$this->assertNotSame( '', $token );

		$_GET[ FormSubmission::STATE_ARG ] = $token;

		$state = FormSubmission::get_render_state( 'form-a' );

		$this->assertTrue( $state['hasSubmissionError'] );
		$this->assertFalse( $state['isSubmitted'] );
		$this->assertNotSame( '', $state['submissionMessage'] );
		$this->assertSame( [ 'required' ], $state['errors']['field_1'] );
		$this->assertSame( [ 'pattern' ], $state['errors']['field_2'] );
		$this->assertSame( 'abc', $state['values']['field_2'] );

		// A different form on the same page must not read this state.
		$this->assertFalse( FormSubmission::get_render_state( 'form-b' )['hasSubmissionError'] );
	}

	/**
	 * Submitted secrets must never be written to the state store.
	 */
	public function test_password_values_are_not_stored(): void {
		$secret_post_id = self::factory()->post->create( [ 'post_content' => self::SECRET_FORM_CONTENT ] );

		$_POST = $this->build_post(
			[ 'field_7' => '' ],
			'form-secret',
			$secret_post_id
		);

		$_POST['field_7'] = 'EXAMPLE PASSPHRASE';

		$this->handler->handle_submission();

		$token = $this->extract_state_token();

		$_GET[ FormSubmission::STATE_ARG ] = $token;

		$state = FormSubmission::get_render_state( 'form-secret' );

		$this->assertArrayNotHasKey( 'field_7', $state['values'] );
	}

	/**
	 * The pre-submission check must gate the page POST exactly as it gates the
	 * REST route.
	 */
	public function test_pre_submission_check_is_honored(): void {
		$fired = false;
		add_action(
			'outstand_forms_form_submitted',
			function () use ( &$fired ) {
				$fired = true;
			}
		);

		$check = static function () {
			return new WP_Error( 'spam_detected', 'Blocked by the spam check.', [ 'status' => 403 ] );
		};

		add_filter( 'outstand_forms_form_pre_submission_check', $check, 10, 2 );

		$_POST = $this->build_post(
			[
				'field_1' => 'visitor@example.com',
				'field_2' => '12345',
			]
		);

		$this->handler->handle_submission();

		remove_filter( 'outstand_forms_form_pre_submission_check', $check, 10 );

		$this->assertFalse( $fired );

		$_GET[ FormSubmission::STATE_ARG ] = $this->extract_state_token();

		$state = FormSubmission::get_render_state( 'form-a' );

		$this->assertTrue( $state['hasSubmissionError'] );
		$this->assertSame( 'Blocked by the spam check.', $state['submissionMessage'] );
	}

	/**
	 * The rate limit must apply to the page POST too, so it cannot be used to
	 * bypass the cap the REST route enforces.
	 */
	public function test_rate_limit_is_honored(): void {
		remove_filter( 'outstand_forms_rate_limit', '__return_zero' );

		$limit = static function () {
			return 1;
		};

		add_filter( 'outstand_forms_rate_limit', $limit );

		$submissions = 0;
		add_action(
			'outstand_forms_form_submitted',
			function () use ( &$submissions ) {
				++$submissions;
			}
		);

		$payload = [
			'field_1' => 'visitor@example.com',
			'field_2' => '12345',
		];

		$_POST = $this->build_post( $payload );
		$this->handler->handle_submission();

		$_POST = $this->build_post( $payload );
		$this->handler->handle_submission();

		remove_filter( 'outstand_forms_rate_limit', $limit );

		$this->assertSame( 1, $submissions );

		$_GET[ FormSubmission::STATE_ARG ] = $this->extract_state_token();

		$state = FormSubmission::get_render_state( 'form-a' );

		$this->assertTrue( $state['hasSubmissionError'] );
	}

	/**
	 * Without the server-side getters the directive processor strips the very
	 * attributes a re-render needs, so a field must resolve its value, validity
	 * and error text before any hydration.
	 */
	public function test_field_state_renders_submitted_value_and_error(): void {
		FormSubmission::register_field_state();

		$form_context  = wp_json_encode( [ 'validationMessages' => [ 'minLength' => 'Please enter at least {{min}} characters.' ] ] );
		$field_context = wp_json_encode(
			[
				'errorId'       => 'osf-error-1',
				'helpTextId'    => 'osf-help-text-1',
				'initialRecord' => [
					'value'           => 'ab',
					'isValid'         => false,
					'errors'          => [ 'minLength' ],
					'validationRules' => [ 'minLength' => 5 ],
				],
			]
		);

		$html = sprintf(
			'<form data-wp-interactive="osf/form" data-wp-context=\'%1$s\'><div data-wp-context=\'%2$s\'><input data-wp-bind--value="state.fieldValue" data-wp-bind--aria-invalid="!state.isFieldValid" data-wp-bind--aria-describedby="state.fieldAriaDescribedByAttribute" /><span data-wp-text="state.fieldErrorMessage"></span></div></form>',
			$form_context,
			$field_context
		);

		$processed = wp_interactivity_process_directives( $html );

		$this->assertStringContainsString( 'value="ab"', $processed );
		$this->assertStringContainsString( 'aria-invalid="true"', $processed );
		$this->assertStringContainsString( 'aria-describedby="osf-error-1 osf-help-text-1"', $processed );
		$this->assertStringContainsString( '>Please enter at least 5 characters.</span>', $processed );
	}

	/**
	 * Build a valid $_POST payload for the page submission.
	 *
	 * @param array    $fields  The submitted field values.
	 * @param string   $form_id The form ID.
	 * @param int|null $post_id The post ID holding the form.
	 * @return array
	 */
	private function build_post( array $fields, string $form_id = 'form-a', ?int $post_id = null ): array {
		return array_merge(
			$fields,
			[
				'form_id'                    => $form_id,
				'post_id'                    => (string) ( $post_id ?? $this->post_id ),
				'_wpnonce'                   => wp_create_nonce( 'wp_rest' ),
				FormSubmission::MARKER_FIELD => '1',
				FormSubmission::NONCE_FIELD  => wp_create_nonce( FormSubmission::NONCE_ACTION ),
			]
		);
	}

	/**
	 * Pull the state token out of the captured redirect URL.
	 *
	 * @return string
	 */
	private function extract_state_token(): string {
		$query = wp_parse_url( $this->redirect_url, PHP_URL_QUERY );

		if ( ! $query ) {
			return '';
		}

		$args = [];
		wp_parse_str( $query, $args );

		return (string) ( $args[ FormSubmission::STATE_ARG ] ?? '' );
	}
}
