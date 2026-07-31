<?php

namespace Outstand\WP\Forms\REST\V1;

use Outstand\WP\Forms\FormBlockParser;
use Outstand\WP\Forms\Validation\Validator;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class Forms extends AbstractRoute {

	/**
	 * {@inheritDoc}
	 */
	protected string $rest_base = 'forms';

	/**
	 * {@inheritDoc}
	 */
	public function register_routes(): void {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/submit',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'submit_form' ],
					'permission_callback' => '__return_true',
					'args'                => $this->get_submit_args(),
				],
			]
		);
	}

	/**
	 * Get submission endpoint arguments.
	 *
	 * @return array
	 */
	protected function get_submit_args(): array {

		$args = [
			'form_id' => [
				'description'       => __( 'The form ID.', 'outstand-forms' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			],
			'post_id' => [
				'description'       => __( 'The post ID containing the form.', 'outstand-forms' ),
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
			],
		];

		/**
		 * Filters additional REST endpoint arguments for form submission.
		 *
		 * @param array $additional_args Additional endpoint arguments.
		 * @return array
		 */
		$additional_args = apply_filters( 'outstand_forms_rest_form_submit_args', [] );

		return array_merge( $additional_args, $args );
	}

	/**
	 * Handle form submission.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function submit_form( WP_REST_Request $request ): WP_REST_Response|WP_Error {

		$form_id = $request->get_param( 'form_id' );
		$post_id = $request->get_param( 'post_id' );
		$params  = $request->get_params();

		// Remove internal parameters.
		unset( $params['form_id'], $params['post_id'], $params['_wpnonce'] );

		// Get field configurations and form attributes by parsing block content.
		$parser    = new FormBlockParser();
		$form_data = $parser->extract_form_data( $form_id, $post_id );

		$field_configs = $form_data['field_configs'];

		if ( empty( $field_configs ) ) {
			return new WP_Error(
				'invalid_form',
				__( 'Form not found.', 'outstand-forms' ),
				[ 'status' => 400 ]
			);
		}

		/**
		 * Filters pre-submission checks before field validation.
		 *
		 * Allows blocks to perform security or spam checks with full form context.
		 * Return true to continue or WP_Error to abort.
		 *
		 * @param true|WP_Error   $result  The current check result.
		 * @param WP_REST_Request $request The REST request.
		 * @return true|WP_Error
		 */
		$pre_check = apply_filters( 'outstand_forms_form_pre_submission_check', true, $request );

		if ( is_wp_error( $pre_check ) ) {
			return $pre_check;
		}

		// Sanitize form data based on field configurations.
		$sanitized_data = $this->sanitize_form_data( $params, $field_configs );

		// Validate all fields.
		$validator         = new Validator();
		$validation_errors = [];

		foreach ( $field_configs as $field_name => $config ) {
			$value  = $sanitized_data[ $field_name ] ?? null;
			$rules  = $config['validation_rules'] ?? [];
			$result = $validator->validate( $value, $rules );

			if ( ! $result['is_valid'] ) {
				$validation_errors[ $field_name ] = $result['errors'];
			}
		}

		if ( ! empty( $validation_errors ) ) {
			return new WP_Error(
				'validation_failed',
				__( 'Form validation failed.', 'outstand-forms' ),
				[
					'status' => 400,
					'errors' => $validation_errors,
				]
			);
		}

		/**
		 * Fires when a form is submitted and validated successfully.
		 *
		 * @param string $form_id        The form ID.
		 * @param int    $post_id        The post ID containing the form.
		 * @param array  $sanitized_data The sanitized form data.
		 * @param array  $form_data      The parsed form data.
		 */
		do_action( 'outstand_forms_form_submitted', $form_id, $post_id, $sanitized_data, $form_data );

		$response = [
			'success' => true,
			'message' => __( 'Form submitted successfully.', 'outstand-forms' ),
		];

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Sanitize form data based on field configurations.
	 *
	 * @param array $data          The form data.
	 * @param array $field_configs The field configurations.
	 * @return array
	 */
	protected function sanitize_form_data( array $data, array $field_configs ): array {

		$sanitized = [];
		foreach ( $field_configs as $field_name => $config ) {

			if ( ! array_key_exists( $field_name, $data ) ) {
				continue;
			}

			$value = $data[ $field_name ];
			$type  = $config['type'] ?? 'text';

			$sanitized[ $field_name ] = match ( $type ) {
				'email'    => sanitize_email( $value ),
				'url'      => esc_url_raw( $value ),
				'number'   => is_numeric( $value ) ? (float) $value : null,
				'textarea' => sanitize_textarea_field( $value ),
				default    => sanitize_text_field( $value ),
			};
		}

		return $sanitized;
	}
}
