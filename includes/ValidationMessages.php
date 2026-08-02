<?php

namespace Outstand\WP\Forms;

/**
 * The validation message catalog.
 *
 * Most messages are the same for every field in a form, so they are resolved
 * once and carried in the form's context. Four are not: `minLength`,
 * `maxLength`, `minSelected` and `maxSelected` name a number, and a number
 * needs `_n()` to read correctly — "at least 1 character", not "at least 1
 * characters". Those are resolved per field, where the count is known, and
 * travel in the field's own context.
 */
class ValidationMessages {

	/**
	 * Rules whose message names a number.
	 *
	 * @var array
	 */
	public const COUNTED_RULES = [ 'minLength', 'maxLength', 'minSelected', 'maxSelected' ];

	/**
	 * Filtered catalogs, keyed by form ID.
	 *
	 * @var array<string, array>
	 */
	private static array $catalogs = [];

	/**
	 * Get the message catalog for a form.
	 *
	 * @param string $form_id The form ID.
	 * @return array<string, string>
	 */
	public static function for_form( string $form_id ): array {

		if ( isset( self::$catalogs[ $form_id ] ) ) {
			return self::$catalogs[ $form_id ];
		}

		/**
		 * Filters the validation messages.
		 *
		 * The four counted rules — `minLength`, `maxLength`, `minSelected` and
		 * `maxSelected` — are pluralized per field against that field's own
		 * number. Overriding one here opts out of that: the string given is
		 * used as-is, with `{{key}}` substituted.
		 *
		 * @param array  $messages An associative array of messages keyed by rule name.
		 *                         Example:
		 *                         [
		 *                             'required'  => 'This field is required.',
		 *                             'minLength' => 'Please enter at least {{min}} characters.',
		 *                             'maxLength' => 'Please enter no more than {{max}} characters.',
		 *                         ]
		 * @param string $form_id  The form ID.
		 * @return array
		 */
		self::$catalogs[ $form_id ] = apply_filters(
			'outstand_forms_validation_messages',
			self::get_defaults(),
			$form_id
		);

		return self::$catalogs[ $form_id ];
	}

	/**
	 * Get the counted messages for one field, pluralized against its own rules.
	 *
	 * Returns only the rules this field actually carries, and only while the
	 * form catalog still holds the default for them — an author who overrode
	 * the message through the filter gets their string, not a pluralized one.
	 *
	 * @param string $form_id The form ID.
	 * @param array  $rules   The field's validation rules.
	 * @return array<string, string>
	 */
	public static function for_field( string $form_id, array $rules ): array {

		$catalog  = self::for_form( $form_id );
		$defaults = self::get_defaults();

		$messages = [];

		foreach ( self::COUNTED_RULES as $rule ) {
			$count = $rules[ $rule ] ?? 0;

			if ( ! is_numeric( $count ) || $count < 1 ) {
				continue;
			}

			if ( ( $catalog[ $rule ] ?? '' ) !== ( $defaults[ $rule ] ?? '' ) ) {
				continue;
			}

			$messages[ $rule ] = self::get_counted_message( $rule, (int) $count );
		}

		return $messages;
	}

	/**
	 * Discard the memoized catalogs.
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$catalogs = [];
	}

	/**
	 * Get the default messages.
	 *
	 * The counted rules keep a `{{key}}` template here: it is what the filter
	 * is compared against, and what a form built entirely of default messages
	 * falls back to if a field never contributes its own.
	 *
	 * @return array<string, string>
	 */
	private static function get_defaults(): array {

		return [
			'required'    => __( 'This field is required.', 'outstand-forms' ),
			'pattern'     => __( 'The value does not match the expected format.', 'outstand-forms' ),
			'email'       => __( 'Please enter a valid email address.', 'outstand-forms' ),
			'url'         => __( 'Please enter a valid URL.', 'outstand-forms' ),
			'minLength'   => __( 'Please enter at least {{min}} characters.', 'outstand-forms' ),
			'maxLength'   => __( 'Please enter no more than {{max}} characters.', 'outstand-forms' ),
			'min'         => __( 'Please enter a value greater than or equal to {{min}}.', 'outstand-forms' ),
			'max'         => __( 'Please enter a value less than or equal to {{max}}.', 'outstand-forms' ),
			// Deliberately vague: this only fires on a tampered or stale
			// submission, and naming the allowed values would hand back the
			// allowlist.
			'options'     => __( 'Please choose a valid option.', 'outstand-forms' ),
			'minSelected' => __( 'Please choose at least {{min}} options.', 'outstand-forms' ),
			'maxSelected' => __( 'Please choose no more than {{max}} options.', 'outstand-forms' ),
		];
	}

	/**
	 * Build the message for a counted rule.
	 *
	 * @param string $rule  The rule name.
	 * @param int    $count The rule's number.
	 * @return string
	 */
	private static function get_counted_message( string $rule, int $count ): string {

		switch ( $rule ) {
			case 'minLength':
				/* translators: %d is the minimum number of characters. */
				$message = _n(
					'Please enter at least %d character.',
					'Please enter at least %d characters.',
					$count,
					'outstand-forms'
				);
				break;
			case 'maxLength':
				/* translators: %d is the maximum number of characters. */
				$message = _n(
					'Please enter no more than %d character.',
					'Please enter no more than %d characters.',
					$count,
					'outstand-forms'
				);
				break;
			case 'minSelected':
				/* translators: %d is the minimum number of options. */
				$message = _n(
					'Please choose at least %d option.',
					'Please choose at least %d options.',
					$count,
					'outstand-forms'
				);
				break;
			case 'maxSelected':
				/* translators: %d is the maximum number of options. */
				$message = _n(
					'Please choose no more than %d option.',
					'Please choose no more than %d options.',
					$count,
					'outstand-forms'
				);
				break;
			default:
				return '';
		}

		return sprintf( $message, $count );
	}
}
