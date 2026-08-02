<?php

namespace Outstand\WP\Forms\Tests\Unit;

use Outstand\WP\Forms\ValidationMessages;

/**
 * Tests for the validation message catalog.
 */
class ValidationMessagesTest extends \WP_UnitTestCase {

	/**
	 * Discard any catalog memoized by another test.
	 */
	public function set_up(): void {
		parent::set_up();

		ValidationMessages::reset();
	}

	/**
	 * Discard the memoized catalogs and any filters a test added.
	 */
	public function tear_down(): void {
		ValidationMessages::reset();

		parent::tear_down();
	}

	/**
	 * A count of one must read as a singular.
	 */
	public function test_a_count_of_one_is_singular(): void {

		$messages = ValidationMessages::for_field(
			'form-1',
			[
				'minLength'   => 1,
				'minSelected' => 1,
			]
		);

		$this->assertSame( 'Please enter at least 1 character.', $messages['minLength'] );
		$this->assertSame( 'Please choose at least 1 option.', $messages['minSelected'] );
	}

	/**
	 * Any other count must read as a plural.
	 */
	public function test_other_counts_are_plural(): void {

		$messages = ValidationMessages::for_field(
			'form-1',
			[
				'maxLength'   => 5,
				'maxSelected' => 3,
			]
		);

		$this->assertSame( 'Please enter no more than 5 characters.', $messages['maxLength'] );
		$this->assertSame( 'Please choose no more than 3 options.', $messages['maxSelected'] );
	}

	/**
	 * A field carrying no counted rule must contribute nothing, so its context
	 * stays as small as it was before pluralization existed.
	 */
	public function test_a_field_without_counted_rules_contributes_nothing(): void {

		$messages = ValidationMessages::for_field(
			'form-1',
			[
				'required' => true,
				'email'    => true,
			]
		);

		$this->assertSame( [], $messages );
	}

	/**
	 * A rule set to zero is disabled, so it needs no message.
	 */
	public function test_a_disabled_rule_contributes_nothing(): void {

		$messages = ValidationMessages::for_field(
			'form-1',
			[
				'minLength'   => 0,
				'maxSelected' => 0,
			]
		);

		$this->assertSame( [], $messages );
	}

	/**
	 * An author who overrode a counted message through the filter must get
	 * their string, not a pluralized one.
	 */
	public function test_an_overridden_message_opts_out_of_pluralization(): void {

		$override = static function ( array $messages ): array {
			$messages['minLength'] = 'Too short! Needs {{min}}.';

			return $messages;
		};

		add_filter( 'outstand_forms_validation_messages', $override );

		$messages = ValidationMessages::for_field(
			'form-1',
			[
				'minLength' => 1,
				'maxLength' => 5,
			]
		);

		remove_filter( 'outstand_forms_validation_messages', $override );

		// The overridden rule falls through to the form catalog, template and
		// all.
		$this->assertArrayNotHasKey( 'minLength', $messages );

		// The rule they left alone is still pluralized.
		$this->assertSame( 'Please enter no more than 5 characters.', $messages['maxLength'] );
	}

	/**
	 * The form catalog must still carry every rule, since a field only ever
	 * contributes the counted ones.
	 */
	public function test_the_form_catalog_carries_every_rule(): void {

		$catalog = ValidationMessages::for_form( 'form-1' );

		foreach ( [ 'required', 'pattern', 'email', 'url', 'min', 'max', 'options' ] as $rule ) {
			$this->assertArrayHasKey( $rule, $catalog );
		}

		foreach ( ValidationMessages::COUNTED_RULES as $rule ) {
			$this->assertArrayHasKey( $rule, $catalog );
		}
	}

	/**
	 * The filter must run once per form, not once per field.
	 */
	public function test_the_catalog_is_memoized_per_form(): void {

		$calls = 0;

		$counter = static function ( array $messages ) use ( &$calls ): array {
			++$calls;

			return $messages;
		};

		add_filter( 'outstand_forms_validation_messages', $counter );

		ValidationMessages::for_form( 'form-1' );
		ValidationMessages::for_form( 'form-1' );
		ValidationMessages::for_field( 'form-1', [ 'minLength' => 2 ] );
		ValidationMessages::for_form( 'form-2' );

		remove_filter( 'outstand_forms_validation_messages', $counter );

		$this->assertSame( 2, $calls );
	}
}
