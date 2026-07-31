<?php

namespace Outstand\WP\Forms\Tests\Unit;

use Outstand\WP\Forms\Fields\Email;
use Outstand\WP\Forms\Fields\Number;
use Outstand\WP\Forms\Fields\Text;
use Outstand\WP\Forms\Fields\URL;

class FieldsTest extends \WP_UnitTestCase {

	/**
	 * Base rules derive from the shared attributes.
	 */
	public function test_text_field_derives_base_rules_from_attributes(): void {
		$field = new Text(
			[
				'fieldId'   => 7,
				'required'  => true,
				'minLength' => 2,
				'maxLength' => 10,
				'pattern'   => '[a-z]+',
			]
		);

		$this->assertSame(
			[
				'required'  => true,
				'minLength' => 2,
				'maxLength' => 10,
				'pattern'   => '[a-z]+',
			],
			$field->get_validation_rules()
		);
	}

	/**
	 * Empty optional attributes must not produce rules.
	 */
	public function test_text_field_omits_empty_rules(): void {
		$field = new Text( [ 'fieldId' => 7 ] );

		$this->assertSame( [ 'required' => false ], $field->get_validation_rules() );
	}

	/**
	 * The email type must inject the email rule.
	 */
	public function test_email_field_adds_email_rule(): void {
		$field = new Email( [ 'fieldId' => 7 ] );
		$rules = $field->get_validation_rules();

		$this->assertTrue( $rules['email'] );
	}

	/**
	 * The url type must inject the url rule.
	 */
	public function test_url_field_adds_url_rule(): void {
		$field = new URL( [ 'fieldId' => 7 ] );
		$rules = $field->get_validation_rules();

		$this->assertTrue( $rules['url'] );
	}

	/**
	 * Number fields must strip string rules and add numeric bounds.
	 */
	public function test_number_field_replaces_string_rules_with_numeric_bounds(): void {
		$field = new Number(
			[
				'fieldId'   => 7,
				'required'  => true,
				'minLength' => 2,
				'maxLength' => 10,
				'pattern'   => '[0-9]+',
				'min'       => 1,
				'max'       => 99,
			]
		);

		$rules = $field->get_validation_rules();

		$this->assertArrayNotHasKey( 'minLength', $rules );
		$this->assertArrayNotHasKey( 'maxLength', $rules );
		$this->assertArrayNotHasKey( 'pattern', $rules );
		$this->assertSame( 1.0, $rules['min'] );
		$this->assertSame( 99.0, $rules['max'] );
	}

	/**
	 * The field name falls back to field_{fieldId} when no name is set.
	 */
	public function test_field_name_falls_back_to_field_id(): void {
		$field = new Text( [ 'fieldId' => 42 ] );

		$this->assertSame( 'field_42', $field->get_field_name() );
	}

	/**
	 * An explicit name attribute wins over the fallback.
	 */
	public function test_explicit_name_attribute_wins(): void {
		$field = new Text(
			[
				'fieldId' => 42,
				'name'    => 'custom_name',
			]
		);

		$this->assertSame( 'custom_name', $field->get_field_name() );
	}

	/**
	 * Element IDs derive from the field ID.
	 */
	public function test_element_ids_derive_from_field_id(): void {
		$field = new Text( [ 'fieldId' => 42 ] );

		$this->assertSame( 'osf-field-42', $field->get_field_id() );
		$this->assertSame( 'osf-label-42', $field->get_label_id() );
		$this->assertSame( 'osf-help-text-42', $field->get_help_text_id() );
		$this->assertSame( 'osf-error-42', $field->get_error_id() );
	}
}
