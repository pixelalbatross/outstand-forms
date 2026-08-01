<?php

namespace Outstand\WP\Forms\Tests\Unit;

use Outstand\WP\Forms\EmailNotification;

class EmailNotificationTest extends \WP_UnitTestCase {

	/**
	 * Captured wp_mail() calls.
	 *
	 * @var array
	 */
	private array $sent_mail = [];

	/**
	 * Intercept wp_mail so no mail leaves the test run.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->sent_mail = [];
		add_filter( 'pre_wp_mail', [ $this, 'capture_mail' ], 10, 2 );
	}

	/**
	 * Remove the wp_mail interception.
	 */
	public function tear_down(): void {
		remove_filter( 'pre_wp_mail', [ $this, 'capture_mail' ] );

		parent::tear_down();
	}

	/**
	 * Short-circuit wp_mail and record the call.
	 *
	 * @param null|bool $short_circuit The pre-send value.
	 * @param array     $atts          The wp_mail attributes.
	 * @return bool True to skip actual sending.
	 */
	public function capture_mail( $short_circuit, array $atts ): bool {
		$this->sent_mail[] = $atts;
		return true;
	}

	/**
	 * A custom formAction must suppress notifications entirely.
	 */
	public function test_no_notification_when_form_action_is_custom(): void {
		$this->send( [ 'formAction' => 'https://example.com/webhook' ] );

		$this->assertCount( 0, $this->sent_mail );
	}

	/**
	 * Disabled actions must be skipped.
	 */
	public function test_disabled_actions_are_skipped(): void {
		$this->send(
			[
				'actions' => [
					[
						'id'      => 'admin_email',
						'enabled' => false,
					],
				],
			]
		);

		$this->assertCount( 0, $this->sent_mail );
	}

	/**
	 * An enabled action without a `to` must fall back to the admin email.
	 */
	public function test_admin_email_fallback_and_default_body(): void {
		$this->send(
			[
				'actions' => [
					[
						'id'      => 'admin_email',
						'enabled' => true,
					],
				],
			]
		);

		$this->assertCount( 1, $this->sent_mail );

		$mail = $this->sent_mail[0];
		$this->assertSame( get_option( 'admin_email' ), $mail['to'] );
		$this->assertSame( 'New form submission', $mail['subject'] );
		// Default body renders the submitted values via the template.
		$this->assertStringContainsString( 'user@example.com', $mail['message'] );
	}

	/**
	 * Merge tags must resolve in subject and custom message body.
	 */
	public function test_merge_tags_resolve_in_subject_and_message(): void {
		$this->send(
			[
				'formTitle' => 'Contact',
				'actions'   => [
					[
						'id'      => 'admin_email',
						'enabled' => true,
						'to'      => 'inbox@example.com',
						'subject' => 'New entry on {form_title}',
						'message' => '<p>From: {field_1}</p><p>Unknown: {nope}</p>',
					],
				],
			]
		);

		$mail = $this->sent_mail[0];
		$this->assertSame( 'inbox@example.com', $mail['to'] );
		$this->assertSame( 'New entry on Contact', $mail['subject'] );
		$this->assertStringContainsString( 'From: user@example.com', $mail['message'] );
		// Unknown tags stay verbatim.
		$this->assertStringContainsString( '{nope}', $mail['message'] );
	}

	/**
	 * `EmailNotification::ACTION_ADMIN_NOTIFICATION` and
	 * `ACTION_USER_NOTIFICATION` are the registry's only definitions of the
	 * form action IDs; the block editor localizes them as
	 * `osfSettings.formActionIds` to label and compare actions without
	 * re-declaring the values.
	 */
	public function test_action_id_constants_match_the_ids_used_by_the_form_block(): void {
		$this->assertSame( 'admin_notification', EmailNotification::ACTION_ADMIN_NOTIFICATION );
		$this->assertSame( 'user_notification', EmailNotification::ACTION_USER_NOTIFICATION );
	}

	/**
	 * A user_notification action must resolve the recipient from toFieldId.
	 */
	public function test_user_notification_resolves_to_field(): void {
		$this->send(
			[
				'actions' => [
					[
						'id'        => EmailNotification::ACTION_USER_NOTIFICATION,
						'enabled'   => true,
						'toFieldId' => 1,
					],
				],
			]
		);

		$this->assertSame( 'user@example.com', $this->sent_mail[0]['to'] );
	}

	/**
	 * A user_notification without a resolvable field must not send.
	 */
	public function test_user_notification_without_field_does_not_send(): void {
		$this->send(
			[
				'actions' => [
					[
						'id'        => 'user_notification',
						'enabled'   => true,
						'toFieldId' => 999,
					],
				],
			]
		);

		$this->assertCount( 0, $this->sent_mail );
	}

	/**
	 * From, Reply-To, and Bcc headers must be built and sanitized.
	 */
	public function test_headers_are_built(): void {
		$this->send(
			[
				'actions' => [
					[
						'id'      => 'admin_email',
						'enabled' => true,
						'to'      => 'inbox@example.com',
						'from'    => [
							'name'  => 'Example Sender',
							'email' => 'sender@example.com',
						],
						'replyTo' => 'reply@example.com',
						'bcc'     => 'copy@example.com, not-an-email',
					],
				],
			]
		);

		$headers = $this->sent_mail[0]['headers'];
		$this->assertContains( 'Content-Type: text/html; charset=UTF-8', $headers );
		$this->assertContains( 'From: Example Sender <sender@example.com>', $headers );
		$this->assertContains( 'Reply-To: reply@example.com', $headers );
		$this->assertContains( 'Bcc: copy@example.com', $headers );
		$this->assertNotContains( 'Bcc: not-an-email', $headers );
	}

	/**
	 * Run maybe_send_notification with fabricated submission data.
	 *
	 * @param array $form_attributes The form block attributes.
	 */
	private function send( array $form_attributes ): void {
		$notification = new EmailNotification();

		$notification->maybe_send_notification(
			'form-a',
			123,
			[
				'field_1' => 'user@example.com',
				'field_2' => 'Example message',
			],
			[
				'field_configs'   => [
					'field_1' => [
						'type'    => 'email',
						'label'   => 'Email',
						'fieldId' => 1,
					],
					'field_2' => [
						'type'    => 'text',
						'label'   => 'Message',
						'fieldId' => 2,
					],
				],
				'form_attributes' => $form_attributes,
			]
		);
	}
}
