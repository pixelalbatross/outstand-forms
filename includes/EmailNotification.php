<?php

namespace Outstand\WP\Forms;

class EmailNotification extends BaseModule {

	/**
	 * Action ID for the admin notification email.
	 *
	 * @var string
	 */
	public const ACTION_ADMIN_NOTIFICATION = 'admin_notification';

	/**
	 * Action ID for the user notification email.
	 *
	 * @var string
	 */
	public const ACTION_USER_NOTIFICATION = 'user_notification';

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'outstand_forms_form_submitted', [ $this, 'maybe_send_notification' ], 10, 4 );
	}

	/**
	 * Send email notifications for all enabled actions.
	 *
	 * @param string $form_id        The form ID.
	 * @param int    $post_id        The post ID containing the form.
	 * @param array  $sanitized_data The sanitized form data.
	 * @param array  $form_data      The parsed form data (field_configs, form_attributes).
	 */
	public function maybe_send_notification(
		string $form_id,
		int $post_id,
		array $sanitized_data,
		array $form_data
	): void {
		$field_configs   = $form_data['field_configs'] ?? [];
		$form_attributes = $form_data['form_attributes'] ?? [];

		if ( ! empty( $form_attributes['formAction'] ) ) {
			return;
		}

		$actions = $form_attributes['actions'] ?? [];

		if ( empty( $actions ) ) {
			return;
		}

		// Render the default body once per submission; it doubles as the
		// {all_fields} merge tag and the fallback body for every action.
		$rendered_body = $this->render_email_body( $form_id, $sanitized_data, $field_configs );

		$tags = $this->get_merge_tags( $form_id, $sanitized_data, $form_attributes, $rendered_body );

		foreach ( $actions as $action ) {
			if ( empty( $action['enabled'] ) ) {
				continue;
			}

			$this->send_notification( $action, $sanitized_data, $field_configs, $form_id, $tags, $rendered_body );
		}
	}

	/**
	 * Render the email body from the template.
	 *
	 * @param string $form_id       The form ID.
	 * @param array  $form_data     The sanitized form data.
	 * @param array  $field_configs The field configurations.
	 * @return string The rendered email body HTML.
	 */
	protected function render_email_body(
		string $form_id,
		array $form_data,
		array $field_configs
	): string {
		ob_start();
		include OUTSTAND_FORMS_PATH . 'includes/templates/email-notification.php';
		return ob_get_clean();
	}

	/**
	 * Send a single notification email.
	 *
	 * @param array  $action         The action configuration.
	 * @param array  $sanitized_data The sanitized form data.
	 * @param array  $field_configs  The field configurations.
	 * @param string $form_id        The form ID.
	 * @param array  $tags           The merge tags.
	 * @param string $rendered_body  The pre-rendered default email body.
	 */
	private function send_notification(
		array $action,
		array $sanitized_data,
		array $field_configs,
		string $form_id,
		array $tags,
		string $rendered_body
	): void {
		$to = $this->resolve_to_address( $action, $sanitized_data, $field_configs, $tags );

		if ( empty( $to ) ) {
			return;
		}

		$subject_template = ! empty( $action['subject'] )
			? $action['subject']
			: __( 'New form submission', 'outstand-forms' );
		$subject          = sanitize_text_field( $this->process_merge_tags( $subject_template, $tags ) );

		$body    = $this->build_body( $action, $tags, $rendered_body );
		$headers = $this->build_headers_for_action( $action, $tags );

		/**
		 * Filters the email notification arguments.
		 *
		 * @param array  $args          The email arguments (to, subject, body, headers).
		 * @param string $form_id       The form ID.
		 * @param array  $sanitized_data The sanitized form data.
		 * @param array  $field_configs  The field configurations.
		 * @param array  $action         The action configuration.
		 */
		$args = apply_filters(
			'outstand_forms_email_notification_args',
			[
				'to'      => $to,
				'subject' => $subject,
				'body'    => $body,
				'headers' => $headers,
			],
			$form_id,
			$sanitized_data,
			$field_configs,
			$action
		);

		$sent = wp_mail(
			$args['to'],
			$args['subject'],
			$args['body'],
			$args['headers']
		);

		if ( $sent ) {
			/**
			 * Fires after an email notification is sent successfully.
			 *
			 * @param array  $args           The email arguments (to, subject, body, headers).
			 * @param string $form_id        The form ID.
			 * @param array  $sanitized_data The sanitized form data.
			 * @param array  $field_configs  The field configurations.
			 * @param array  $action         The action configuration.
			 */
			do_action( 'outstand_forms_email_notification_sent', $args, $form_id, $sanitized_data, $field_configs, $action );
		} else {
			/**
			 * Fires when an email notification fails to send.
			 *
			 * @param array  $args           The email arguments (to, subject, body, headers).
			 * @param string $form_id        The form ID.
			 * @param array  $sanitized_data The sanitized form data.
			 * @param array  $field_configs  The field configurations.
			 * @param array  $action         The action configuration.
			 */
			do_action( 'outstand_forms_email_notification_failed', $args, $form_id, $sanitized_data, $field_configs, $action );
		}
	}

	/**
	 * Resolve the "to" address for an action.
	 *
	 * For admin_email actions, processes merge tags in the `to` field.
	 * For user_email actions, looks up the submitted email from the referenced field.
	 *
	 * @param array $action         The action configuration.
	 * @param array $sanitized_data The sanitized form data.
	 * @param array $field_configs  The field configurations.
	 * @param array $tags           The merge tags.
	 * @return string The resolved email address.
	 */
	private function resolve_to_address( array $action, array $sanitized_data, array $field_configs, array $tags ): string {
		$action_id = $action['id'] ?? '';

		if ( self::ACTION_USER_NOTIFICATION === $action_id ) {
			return $this->resolve_user_email_to( $action, $sanitized_data, $field_configs );
		}

		// admin_email or any other action with a `to` field.
		$to = ! empty( $action['to'] )
			? $this->process_merge_tags( $action['to'], $tags )
			: '';

		$email = sanitize_email( $to );

		return ! empty( $email ) ? $email : get_option( 'admin_email' );
	}

	/**
	 * Resolve the user email "to" address by matching toFieldId against field configs.
	 *
	 * @param array $action         The action configuration.
	 * @param array $sanitized_data The sanitized form data.
	 * @param array $field_configs  The field configurations.
	 * @return string The resolved email address, or empty string if not found.
	 */
	private function resolve_user_email_to( array $action, array $sanitized_data, array $field_configs ): string {
		$to_field_id = $action['toFieldId'] ?? '';

		if ( '' === $to_field_id ) {
			return '';
		}

		$target_field_id = (string) $to_field_id;

		foreach ( $field_configs as $field_name => $config ) {
			if ( ! isset( $config['fieldId'] ) ) {
				continue;
			}

			$config_field_id = (string) $config['fieldId'];

			if ( $config_field_id === $target_field_id ) {
				$value = $sanitized_data[ $field_name ] ?? '';
				return sanitize_email( $value );
			}
		}

		return '';
	}

	/**
	 * Build email headers for an action, processing merge tags.
	 *
	 * @param array $action The action configuration.
	 * @param array $tags   The merge tags.
	 * @return array The email headers.
	 */
	private function build_headers_for_action( array $action, array $tags ): array {
		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

		// From header.
		$from_email_raw = ! empty( $action['from']['email'] )
			? $this->process_merge_tags( $action['from']['email'], $tags )
			: '';
		$from_email     = sanitize_email( $from_email_raw );

		if ( $from_email ) {
			$from_name_raw = ! empty( $action['from']['name'] )
				? $this->process_merge_tags( $action['from']['name'], $tags )
				: '';
			$from_name     = sanitize_text_field( $from_name_raw );

			$headers[] = $from_name
				? sprintf( 'From: %s <%s>', $from_name, $from_email )
				: sprintf( 'From: %s', $from_email );
		}

		// Reply-To header.
		$reply_to_raw   = ! empty( $action['replyTo'] )
			? $this->process_merge_tags( $action['replyTo'], $tags )
			: '';
		$reply_to_email = sanitize_email( $reply_to_raw );

		if ( $reply_to_email ) {
			$headers[] = sprintf( 'Reply-To: %s', $reply_to_email );
		}

		// BCC headers.
		$bcc_raw = $action['bcc'] ?? '';
		if ( ! empty( $bcc_raw ) ) {
			$bcc_processed = $this->process_merge_tags( $bcc_raw, $tags );
			$bcc_emails    = array_map( 'trim', explode( ',', $bcc_processed ) );
			foreach ( $bcc_emails as $bcc_email ) {
				$email = sanitize_email( $bcc_email );
				if ( ! empty( $email ) ) {
					$headers[] = sprintf( 'Bcc: %s', $email );
				}
			}
		}

		return $headers;
	}

	/**
	 * Build the email body, using a custom message template or falling back to the default table layout.
	 *
	 * @param array  $action        The action configuration.
	 * @param array  $tags          The pre-built merge tags.
	 * @param string $rendered_body The pre-rendered default email body.
	 * @return string The email body HTML.
	 */
	private function build_body( array $action, array $tags, string $rendered_body ): string {
		$message = $action['message'] ?? '';

		if ( empty( $message ) ) {
			return $rendered_body;
		}

		$message = wp_kses_post( $message );

		return $this->process_merge_tags( $message, $tags );
	}

	/**
	 * Process merge tags in a template string.
	 *
	 * Replaces `{tag_name}` placeholders with their corresponding values.
	 * Unmatched tags are left as-is.
	 *
	 * @param string $template The template string containing merge tags.
	 * @param array  $tags     The merge tag values (tag name => replacement).
	 * @return string The processed string.
	 */
	private function process_merge_tags( string $template, array $tags ): string {
		return preg_replace_callback(
			'/\{([a-zA-Z0-9_-]+)\}/',
			function ( $matches ) use ( $tags ) {
				return $tags[ $matches[1] ] ?? $matches[0];
			},
			$template
		);
	}

	/**
	 * Build the merge tags array for template processing.
	 *
	 * @param string $form_id        The form ID.
	 * @param array  $sanitized_data The sanitized form data.
	 * @param array  $form_attrs     The form block attributes.
	 * @param string $rendered_body  The pre-rendered default email body.
	 * @return array The merge tags (tag name => replacement value).
	 */
	private function get_merge_tags(
		string $form_id,
		array $sanitized_data,
		array $form_attrs,
		string $rendered_body
	): array {
		$tags = [
			'form_title'  => esc_html( $form_attrs['formTitle'] ?? '' ),
			'site_name'   => esc_html( get_bloginfo( 'name' ) ),
			'admin_email' => sanitize_email( get_option( 'admin_email' ) ),
			'site_url'    => esc_url( home_url() ),
			'date'        => esc_html( wp_date( get_option( 'date_format' ) ) ),
			'all_fields'  => $rendered_body,
		];

		foreach ( $sanitized_data as $field_name => $value ) {
			if ( ! isset( $tags[ $field_name ] ) ) {
				$tags[ $field_name ] = esc_html( (string) $value );
			}
		}

		return $tags;
	}
}
