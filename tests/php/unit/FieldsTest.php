<?php

namespace Outstand\WP\Forms\Tests\Unit;

use Outstand\WP\Forms\FieldFactory;
use Outstand\WP\Forms\Fields\FieldInterface;

class FieldsTest extends \WP_UnitTestCase {

	/**
	 * Field factory instance.
	 *
	 * @var FieldFactory
	 */
	private FieldFactory $field_factory;

	/**
	 * Build an unfiltered factory so the tests pin built-in behavior only.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->field_factory = new FieldFactory();
	}

	/**
	 * Base rules derive from the shared attributes.
	 */
	public function test_text_field_derives_base_rules_from_attributes(): void {
		$field = $this->create_field(
			'text',
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
		$field = $this->create_field( 'text', [ 'fieldId' => 7 ] );

		$this->assertSame( [ 'required' => false ], $field->get_validation_rules() );
	}

	/**
	 * Types that only differ by their input type must share the base rules.
	 */
	public function test_plain_types_share_base_rules(): void {
		foreach ( [ 'text', 'password', 'tel' ] as $type ) {
			$field = $this->create_field( $type, [ 'fieldId' => 7 ] );

			$this->assertSame( $type, $field->get_type() );
			$this->assertSame( [ 'required' => false ], $field->get_validation_rules() );
			$this->assertStringContainsString(
				sprintf( 'type="%s"', $type ),
				$field->get_component( 'field' )->get_markup()
			);
		}
	}

	/**
	 * The email type must inject the email rule.
	 */
	public function test_email_field_adds_email_rule(): void {
		$field = $this->create_field( 'email', [ 'fieldId' => 7 ] );
		$rules = $field->get_validation_rules();

		$this->assertTrue( $rules['email'] );
	}

	/**
	 * The url type must inject the url rule.
	 */
	public function test_url_field_adds_url_rule(): void {
		$field = $this->create_field( 'url', [ 'fieldId' => 7 ] );
		$rules = $field->get_validation_rules();

		$this->assertTrue( $rules['url'] );
	}

	/**
	 * Number fields must strip string rules and add numeric bounds.
	 */
	public function test_number_field_replaces_string_rules_with_numeric_bounds(): void {
		$field = $this->create_field(
			'number',
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
	 * The textarea type must render a textarea control.
	 */
	public function test_textarea_field_uses_textarea_component(): void {
		$field = $this->create_field( 'textarea', [ 'fieldId' => 7 ] );

		$this->assertStringContainsString( '<textarea ', $field->get_component( 'field' )->get_markup() );
	}

	/**
	 * Each type must own the sanitizer it has always used.
	 */
	public function test_field_sanitizes_by_type(): void {
		$this->assertSame(
			'user@example.com',
			$this->create_field( 'email', [ 'fieldId' => 7 ] )->sanitize( ' user@example.com ' )
		);
		$this->assertNull( $this->create_field( 'number', [ 'fieldId' => 7 ] )->sanitize( 'abc' ) );
		$this->assertSame( 5.0, $this->create_field( 'number', [ 'fieldId' => 7 ] )->sanitize( '5' ) );
		$this->assertSame(
			"a\nb",
			$this->create_field( 'textarea', [ 'fieldId' => 7 ] )->sanitize( "a\nb" )
		);
		$this->assertSame(
			'plain',
			$this->create_field( 'text', [ 'fieldId' => 7 ] )->sanitize( '<b>plain</b>' )
		);
	}

	/**
	 * The field name falls back to field_{fieldId} when no name is set.
	 */
	public function test_field_name_falls_back_to_field_id(): void {
		$field = $this->create_field( 'text', [ 'fieldId' => 42 ] );

		$this->assertSame( 'field_42', $field->get_field_name() );
	}

	/**
	 * An explicit name attribute wins over the fallback.
	 */
	public function test_explicit_name_attribute_wins(): void {
		$field = $this->create_field(
			'text',
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
		$field = $this->create_field( 'text', [ 'fieldId' => 42 ] );

		$this->assertSame( 'osf-field-42', $field->get_field_id() );
		$this->assertSame( 'osf-label-42', $field->get_label_id() );
		$this->assertSame( 'osf-help-text-42', $field->get_help_text_id() );
		$this->assertSame( 'osf-error-42', $field->get_error_id() );
	}

	/**
	 * Create a built-in field.
	 *
	 * @param string $type       The field type.
	 * @param array  $attributes The block attributes.
	 * @return FieldInterface
	 */
	private function create_field( string $type, array $attributes ): FieldInterface {
		return $this->field_factory->create( $type, $attributes );
	}
}
