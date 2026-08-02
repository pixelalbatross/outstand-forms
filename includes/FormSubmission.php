<?php

namespace Outstand\WP\Forms;

use Outstand\WP\Forms\Components\Checkbox;
use Outstand\WP\Forms\REST\V1\Forms;
use WP_Error;
use WP_REST_Request;

/**
 * Handles form submissions posted straight to the page.
 *
 * With JavaScript unavailable the Interactivity submit handler never runs and
 * the browser performs a plain POST back to the permalink. This module turns
 * that POST into the very same pipeline the REST route uses
 * ({@see Forms::process_submission()}) and answers with a Post/Redirect/Get so
 * a refresh cannot resubmit.
 */
class FormSubmission extends BaseModule {

	/**
	 * Hidden field marking a page POST as a form submission.
	 *
	 * @var string
	 */
	public const MARKER_FIELD = 'osf_submit';

	/**
	 * Hidden field carrying the submission nonce.
	 *
	 * @var string
	 */
	public const NONCE_FIELD = 'osf_nonce';

	/**
	 * Nonce action for the page POST.
	 *
	 * @var string
	 */
	public const NONCE_ACTION = 'outstand_forms_submit';

	/**
	 * Query arg carrying the ID of the form that was submitted successfully.
	 *
	 * @var string
	 */
	public const SUCCESS_ARG = 'osf-success';

	/**
	 * Query arg carrying the token of the stored failure state.
	 *
	 * @var string
	 */
	public const STATE_ARG = 'osf-state';

	/**
	 * Transient prefix for stored failure state.
	 *
	 * @var string
	 */
	private const TRANSIENT_PREFIX = 'osf_submission_';

	/**
	 * Length of the state token.
	 *
	 * @var int
	 */
	private const TOKEN_LENGTH = 32;

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'template_redirect', [ $this, 'handle_submission' ] );
	}

	/**
	 * Handle a form posted back to the page.
	 *
	 * @return void
	 */
	public function handle_submission(): void {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The nonce is verified immediately below.
		if ( ! isset( $_POST[ self::MARKER_FIELD ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is the nonce.
		$nonce = isset( $_POST[ self::NONCE_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_die(
				esc_html__( 'Your session has expired. Please go back, reload the page and submit the form again.', 'outstand-forms' ),
				esc_html__( 'Form submission failed', 'outstand-forms' ),
				[ 'response' => 403 ]
			);
		}

		$request = $this->build_request();
		$form_id = (string) $request->get_param( 'form_id' );
		$post_id = (int) $request->get_param( 'post_id' );

		$route  = new Forms();
		$result = $route->process_submission( $request );

		$permalink    = get_permalink( $post_id );
		$redirect_url = $permalink ? $permalink : home_url( '/' );
		$redirect_url = remove_query_arg( [ self::SUCCESS_ARG, self::STATE_ARG ], $redirect_url );

		if ( is_wp_error( $result ) ) {
			$token        = $this->store_failure_state( $request, $result );
			$redirect_url = add_query_arg( self::STATE_ARG, $token, $redirect_url );
		} else {
			$redirect_url = add_query_arg( self::SUCCESS_ARG, rawurlencode( $form_id ), $redirect_url );
		}

		if ( wp_safe_redirect( $redirect_url ) ) {
			exit;
		}
	}

	/**
	 * Resolve the server-rendered state for a form on the GET after a redirect.
	 *
	 * @param string $form_id The form ID.
	 * @return array{isSubmitted: bool, hasSubmissionError: bool, submissionMessage: string, errors: array, values: array}
	 */
	public static function get_render_state( string $form_id ): array {

		$state = [
			'isSubmitted'        => false,
			'hasSubmissionError' => false,
			'submissionMessage'  => '',
			'errors'             => [],
			'values'             => [],
		];

		if ( '' === $form_id ) {
			return $state;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only rendering of the visitor's own state.
		$submitted = isset( $_GET[ self::SUCCESS_ARG ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::SUCCESS_ARG ] ) ) : '';
		$token     = isset( $_GET[ self::STATE_ARG ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::STATE_ARG ] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( $submitted === $form_id ) {
			$state['isSubmitted'] = true;

			return $state;
		}

		if ( ! preg_match( '/^[A-Za-z0-9]{' . self::TOKEN_LENGTH . '}$/', $token ) ) {
			return $state;
		}

		$stored = get_transient( self::TRANSIENT_PREFIX . $token );

		if ( ! is_array( $stored ) ) {
			return $state;
		}

		$stored_form_id = (string) ( $stored['form_id'] ?? '' );

		if ( $stored_form_id !== $form_id ) {
			return $state;
		}

		$state['hasSubmissionError'] = true;
		$state['submissionMessage']  = (string) ( $stored['message'] ?? '' );
		$state['errors']             = (array) ( $stored['errors'] ?? [] );
		$state['values']             = (array) ( $stored['values'] ?? [] );

		return $state;
	}

	/**
	 * Register the server-side counterpart of the field getters.
	 *
	 * The field markup binds its value, validity and error text to derived
	 * state that only `view.js` provides, so without these the directive
	 * processor resolves every one of them to null and strips the very
	 * attributes a re-render needs. Each getter reads the same `initialRecord`
	 * the client uses to seed the registry, which keeps the two surfaces
	 * agreeing on what a field looks like before hydration.
	 *
	 * @return void
	 */
	public static function register_field_state(): void {

		wp_interactivity_state(
			'osf/form',
			[
				'fieldValue'                    => static function () {
					$context = wp_interactivity_get_context();

					return $context['initialRecord']['value'] ?? '';
				},
				'isFieldValid'                  => static function () {
					$context = wp_interactivity_get_context();

					return $context['initialRecord']['isValid'] ?? true;
				},
				'isOptionChecked'               => static function () {
					$context = wp_interactivity_get_context();

					return self::resolve_option_checked( $context );
				},
				'isFieldChecked'                => static function () {
					$context = wp_interactivity_get_context();

					return Checkbox::CHECKED_VALUE === (string) ( $context['initialRecord']['value'] ?? '' );
				},
				'isFieldFocused'                => static function () {
					return false;
				},
				'fieldErrorMessage'             => static function () {
					$context = wp_interactivity_get_context();

					return self::resolve_field_error_message( $context );
				},
				'fieldAriaDescribedByAttribute' => static function () {
					$context = wp_interactivity_get_context();

					return self::resolve_field_described_by( $context );
				},
			]
		);
	}

	/**
	 * Build a REST request from the posted fields.
	 *
	 * Values get a baseline pass here; the per-type sanitizers still run inside
	 * the shared pipeline, exactly as they do for a REST submission.
	 *
	 * @return WP_REST_Request
	 */
	private function build_request(): WP_REST_Request {

		$skipped = [ self::MARKER_FIELD, self::NONCE_FIELD, '_wpnonce', '_wp_http_referer' ];

		$params = [];

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The nonce is verified by the caller.
		foreach ( (array) wp_unslash( $_POST ) as $key => $value ) {
			if ( ! is_string( $key ) || in_array( $key, $skipped, true ) ) {
				continue;
			}

			// A checkbox group posts `name[]` and arrives as an array. Casting
			// that to a string would submit the literal "Array", so each item
			// gets the baseline pass instead.
			if ( is_array( $value ) ) {
				$params[ $key ] = array_values(
					array_map(
						'sanitize_textarea_field',
						array_map( 'strval', array_filter( $value, 'is_scalar' ) )
					)
				);
				continue;
			}

			if ( ! is_scalar( $value ) ) {
				continue;
			}

			$params[ $key ] = sanitize_textarea_field( (string) $value );
		}

		$params['form_id'] = isset( $params['form_id'] ) ? sanitize_text_field( $params['form_id'] ) : '';
		$params['post_id'] = isset( $params['post_id'] ) ? absint( $params['post_id'] ) : 0;

		$request = new WP_REST_Request( 'POST', '/outstand-forms/v1/forms/submit' );
		$request->set_body_params( $params );

		return $request;
	}

	/**
	 * Store the failure state for the redirect target to pick up.
	 *
	 * Only values belonging to a field of the submitted form are kept, and
	 * password fields are dropped so a secret is never written to the options
	 * table nor echoed back into the markup.
	 *
	 * @param WP_REST_Request $request The request.
	 * @param WP_Error        $error   The failure returned by the pipeline.
	 * @return string The token identifying the stored state.
	 */
	private function store_failure_state( WP_REST_Request $request, WP_Error $error ): string {

		$form_id = (string) $request->get_param( 'form_id' );
		$post_id = (int) $request->get_param( 'post_id' );

		$parser        = new FormBlockParser();
		$form_data     = $parser->extract_form_data( $form_id, $post_id );
		$field_configs = $form_data['field_configs'];

		$values = [];
		foreach ( $field_configs as $field_name => $config ) {
			$type = $config['type'] ?? 'text';

			if ( 'password' === $type ) {
				continue;
			}

			$value = $request->get_param( $field_name );

			// A multi-value field replays as a list, so the boxes the visitor
			// ticked come back ticked. Casting it to a string here would drop
			// every choice they made.
			if ( is_array( $value ) ) {
				$values[ $field_name ] = array_values(
					array_map( 'strval', array_filter( $value, 'is_scalar' ) )
				);
				continue;
			}

			if ( ! is_scalar( $value ) ) {
				continue;
			}

			$values[ $field_name ] = (string) $value;
		}

		$error_data = $error->get_error_data();
		$errors     = is_array( $error_data ) ? (array) ( $error_data['errors'] ?? [] ) : [];

		$token = wp_generate_password( self::TOKEN_LENGTH, false, false );

		set_transient(
			self::TRANSIENT_PREFIX . $token,
			[
				'form_id' => $form_id,
				'message' => $error->get_error_message(),
				'errors'  => $errors,
				'values'  => $values,
			],
			5 * MINUTE_IN_SECONDS
		);

		return $token;
	}

	/**
	 * Resolve the message for the first rule a field broke.
	 *
	 * Mirrors the `fieldErrorMessage` getter in `view.js`, including the
	 * `{{key}}` substitution a message such as "at least {{min}} characters"
	 * relies on.
	 *
	 * @param array $context The merged context at the current element.
	 * @return string
	 */
	private static function resolve_field_error_message( array $context ): string {

		$record = $context['initialRecord'] ?? [];
		$errors = $record['errors'] ?? [];

		if ( empty( $errors ) ) {
			return '';
		}

		$error = (string) reset( $errors );

		// A field's own message wins: it is the one pluralized against this
		// field's number.
		$message = $context['fieldMessages'][ $error ] ?? $context['validationMessages'][ $error ] ?? '';

		if ( '' === $message ) {
			return '';
		}

		$rule_config = $record['validationRules'][ $error ] ?? null;

		return (string) preg_replace_callback(
			'/{{\s*([\w-]+)\s*}}/',
			static function ( array $matches ) use ( $rule_config ): string {
				$value = is_array( $rule_config ) ? ( $rule_config[ $matches[1] ] ?? null ) : $rule_config;

				return is_scalar( $value ) ? (string) $value : '';
			},
			$message
		);
	}

	/**
	 * Resolve the `aria-describedby` value for a field.
	 *
	 * Mirrors the `fieldAriaDescribedByAttribute` getter in `view.js`.
	 *
	 * @param array $context The merged context at the current element.
	 * @return string|null
	 */
	private static function resolve_field_described_by( array $context ): ?string {

		$help_text_id = $context['helpTextId'] ?? '';
		$error_id     = $context['errorId'] ?? '';

		if ( '' === $help_text_id && '' === $error_id ) {
			return null;
		}

		if ( '' === $error_id ) {
			return $help_text_id;
		}

		$is_valid = $context['initialRecord']['isValid'] ?? true;

		return $is_valid ? $help_text_id : sprintf( '%s %s', $error_id, $help_text_id );
	}

	/**
	 * Resolve whether the option being rendered is the chosen one.
	 *
	 * Mirrors the `isOptionChecked` getter in view.js. Without a server-side
	 * counterpart the directive processor resolves the binding to null and
	 * strips the `checked` attribute the component rendered, so a replayed
	 * submission would come back with every box cleared.
	 *
	 * @param array $context The merged field and option context.
	 * @return bool
	 */
	private static function resolve_option_checked( array $context ): bool {

		$option_value = (string) ( $context['optionValue'] ?? '' );
		$value        = $context['initialRecord']['value'] ?? '';

		if ( is_array( $value ) ) {
			return in_array( $option_value, array_map( 'strval', $value ), true );
		}

		return (string) $value === $option_value;
	}
}
