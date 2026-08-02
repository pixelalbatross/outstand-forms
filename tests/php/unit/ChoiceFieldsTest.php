<?php

namespace Outstand\WP\Forms\Tests\Unit;

use Outstand\WP\Forms\Components\Checkbox;
use Outstand\WP\Forms\Components\Choice;
use Outstand\WP\Forms\Components\GroupComponentInterface;
use Outstand\WP\Forms\Components\Select;
use Outstand\WP\Forms\FieldFactory;

/**
 * Tests for the select, radio, checkbox and consent field types.
 */
class ChoiceFieldsTest extends \WP_UnitTestCase {

	/**
	 * Options as a choice field receives them.
	 *
	 * @var array
	 */
	private const OPTIONS = [
		[
			'label' => 'Portugal',
			'value' => 'pt',
		],
		[
			'label' => 'Spain',
			'value' => 'es',
		],
	];

	/**
	 * Discard any shared factory built by another test.
	 */
	public function set_up(): void {
		parent::set_up();

		FieldFactory::reset_instance();
	}

	/**
	 * Discard the shared factory and any registrations made through it.
	 */
	public function tear_down(): void {
		FieldFactory::reset_instance();

		parent::tear_down();
	}

	/**
	 * Each choice type must render through its own control.
	 */
	public function test_choice_types_build_their_components(): void {

		$factory = new FieldFactory();

		$this->assertInstanceOf(
			Select::class,
			$factory->create( 'select', [] )->get_component( 'field' )
		);
		$this->assertInstanceOf(
			Choice::class,
			$factory->create( 'radio', [] )->get_component( 'field' )
		);
		$this->assertInstanceOf(
			Choice::class,
			$factory->create( 'checkbox', [] )->get_component( 'field' )
		);
		$this->assertInstanceOf(
			Checkbox::class,
			$factory->create( 'consent', [] )->get_component( 'field' )
		);
	}

	/**
	 * A radio or checkbox group names itself; a select and a consent box are
	 * single controls a label can point at.
	 */
	public function test_only_groups_are_group_components(): void {

		$factory = new FieldFactory();

		$this->assertInstanceOf(
			GroupComponentInterface::class,
			$factory->create( 'radio', [] )->get_component( 'field' )
		);
		$this->assertInstanceOf(
			GroupComponentInterface::class,
			$factory->create( 'checkbox', [] )->get_component( 'field' )
		);
		$this->assertNotInstanceOf(
			GroupComponentInterface::class,
			$factory->create( 'select', [] )->get_component( 'field' )
		);
		$this->assertNotInstanceOf(
			GroupComponentInterface::class,
			$factory->create( 'consent', [] )->get_component( 'field' )
		);
	}

	/**
	 * A group's label must drop `for`, since there is no single control to
	 * point at, and become a plain element instead.
	 */
	public function test_group_label_has_no_for_attribute(): void {

		$factory = new FieldFactory();

		$attributes = [
			'fieldId' => 7,
			'label'   => 'Size',
			'options' => self::OPTIONS,
		];

		$group_label = $factory->create( 'radio', $attributes )->get_component( 'label' )->get_markup();

		$this->assertStringContainsString( '<span', $group_label );
		$this->assertStringNotContainsString( 'for=', $group_label );
		$this->assertStringContainsString( 'id="osf-label-7"', $group_label );

		$select_label = $factory->create( 'select', $attributes )->get_component( 'label' )->get_markup();

		$this->assertStringContainsString( '<label', $select_label );
		$this->assertStringContainsString( 'for="osf-field-7"', $select_label );
	}

	/**
	 * A single control still honors an inline label.
	 */
	public function test_a_single_control_keeps_inline_label_positions(): void {

		$factory = new FieldFactory();

		ob_start();
		$factory->create(
			'select',
			[
				'fieldId'       => 1,
				'label'         => 'Country',
				'labelPosition' => 'left',
				'options'       => self::OPTIONS,
			]
		)->render();
		$markup = ob_get_clean();

		$this->assertStringContainsString( 'osf-field__wrapper', $markup );
	}

	/**
	 * Each option keeps its own label beside its own input, whatever the
	 * field's label position says.
	 */
	public function test_option_labels_are_unaffected_by_the_field_label_position(): void {

		$factory = new FieldFactory();

		$markup = [];
		foreach ( [ 'top', 'left', 'right' ] as $position ) {
			$markup[ $position ] = $factory->create(
				'radio',
				[
					'fieldId'       => 1,
					'name'          => 'size',
					'labelPosition' => $position,
					'options'       => self::OPTIONS,
				]
			)->get_component( 'field' )->get_markup();
		}

		$this->assertSame( $markup['top'], $markup['left'] );
		$this->assertSame( $markup['top'], $markup['right'] );
	}

	/**
	 * A tick box must not be stretched by the field's column layout, whatever
	 * the label position. The wrapper takes the stretch instead, which is what
	 * keeps this working without a stylesheet of the plugin's own.
	 */
	public function test_the_consent_box_is_wrapped_so_it_keeps_its_size(): void {

		$factory = new FieldFactory();

		$markup = $factory->create( 'consent', [ 'fieldId' => 1 ] )
			->get_component( 'field' )
			->get_markup();

		$this->assertStringContainsString( '<span class="osf-field__checkbox-field">', $markup );
		$this->assertStringContainsString( '</span>', $markup );
	}

	/**
	 * The allowlist must be derived from the authored options.
	 */
	public function test_rules_carry_the_option_values(): void {

		$factory = new FieldFactory();

		$rules = $factory->create( 'select', [ 'options' => self::OPTIONS ] )->get_validation_rules();

		$this->assertSame( [ 'pt', 'es' ], $rules['options'] );
	}

	/**
	 * String constraints describe a typed value and can never apply to a value
	 * picked from a list.
	 */
	public function test_rules_drop_the_string_constraints(): void {

		$factory = new FieldFactory();

		$rules = $factory->create(
			'radio',
			[
				'options'   => self::OPTIONS,
				'minLength' => 3,
				'maxLength' => 10,
				'pattern'   => '[a-z]+',
			]
		)->get_validation_rules();

		$this->assertArrayNotHasKey( 'minLength', $rules );
		$this->assertArrayNotHasKey( 'maxLength', $rules );
		$this->assertArrayNotHasKey( 'pattern', $rules );
	}

	/**
	 * Selection counts belong to the multi-value field alone.
	 */
	public function test_only_the_checkbox_group_takes_selection_counts(): void {

		$factory = new FieldFactory();

		$attributes = [
			'options'     => self::OPTIONS,
			'minSelected' => 1,
			'maxSelected' => 2,
		];

		$checkbox_rules = $factory->create( 'checkbox', $attributes )->get_validation_rules();

		$this->assertSame( 1, $checkbox_rules['minSelected'] );
		$this->assertSame( 2, $checkbox_rules['maxSelected'] );

		$radio_rules = $factory->create( 'radio', $attributes )->get_validation_rules();

		$this->assertArrayNotHasKey( 'minSelected', $radio_rules );
		$this->assertArrayNotHasKey( 'maxSelected', $radio_rules );
	}

	/**
	 * A consent box submits one fixed value, so its allowlist is fixed too.
	 */
	public function test_consent_allows_only_the_checked_value(): void {

		$factory = new FieldFactory();

		$rules = $factory->create( 'consent', [] )->get_validation_rules();

		$this->assertSame( [ Checkbox::CHECKED_VALUE ], $rules['options'] );
	}

	/**
	 * A multi-value submission must keep its shape and lose nothing but the
	 * values that are not scalars.
	 */
	public function test_checkbox_sanitizer_normalizes_to_a_list_of_strings(): void {

		$factory = new FieldFactory();

		$this->assertSame( [ 'pt', 'es' ], $factory->sanitize( 'checkbox', [ 'pt', 'es' ] ) );
		$this->assertSame( [ 'pt' ], $factory->sanitize( 'checkbox', 'pt' ) );
		$this->assertSame( [], $factory->sanitize( 'checkbox', [] ) );

		// Reindexed, so the result serializes as a JSON array.
		$this->assertSame( [ 'es' ], $factory->sanitize( 'checkbox', [ 1 => 'es' ] ) );

		// Non-scalars describe nothing a checkbox could have submitted.
		$this->assertSame( [ 'pt' ], $factory->sanitize( 'checkbox', [ 'pt', [ 'nested' ] ] ) );
	}

	/**
	 * A value the field never offered must survive sanitization and fail
	 * validation, rather than vanishing silently.
	 */
	public function test_a_forged_value_is_rejected_not_dropped(): void {

		$factory = new FieldFactory();

		$this->assertSame( [ 'fr' ], $factory->sanitize( 'checkbox', [ 'fr' ] ) );

		$field  = $factory->create( 'checkbox', [ 'options' => self::OPTIONS ] );
		$result = ( new \Outstand\WP\Forms\Validation\Validator() )->validate(
			[ 'fr' ],
			$field->get_validation_rules()
		);

		$this->assertFalse( $result['is_valid'] );
		$this->assertContains( 'options', $result['errors'] );
	}

	/**
	 * A checkbox group must submit its values as an array.
	 */
	public function test_checkbox_group_markup_uses_an_array_name(): void {

		$factory = new FieldFactory();

		$markup = $factory->create(
			'checkbox',
			[
				'fieldId' => 3,
				'name'    => 'topics',
				'options' => self::OPTIONS,
			]
		)->get_component( 'field' )->get_markup();

		$this->assertStringContainsString( 'name="topics[]"', $markup );
		$this->assertStringContainsString( 'role="group"', $markup );

		$radio_markup = $factory->create(
			'radio',
			[
				'fieldId' => 3,
				'name'    => 'size',
				'options' => self::OPTIONS,
			]
		)->get_component( 'field' )->get_markup();

		$this->assertStringContainsString( 'name="size"', $radio_markup );
		$this->assertStringNotContainsString( 'name="size[]"', $radio_markup );
		$this->assertStringContainsString( 'role="radiogroup"', $radio_markup );
	}

	/**
	 * A required select needs an empty first option, or the browser reports
	 * the first real option as selected and `required` can never fail.
	 */
	public function test_required_select_renders_an_empty_first_option(): void {

		$factory = new FieldFactory();

		$markup = $factory->create(
			'select',
			[
				'fieldId'     => 1,
				'required'    => true,
				'placeholder' => 'Choose a country',
				'options'     => self::OPTIONS,
			]
		)->get_component( 'field' )->get_markup();

		$this->assertStringContainsString( '<option value="" selected>Choose a country</option>', $markup );
	}

	/**
	 * The default value must arrive as the chosen option.
	 */
	public function test_default_values_mark_their_options_as_chosen(): void {

		$factory = new FieldFactory();

		$select = $factory->create(
			'select',
			[
				'fieldId'      => 1,
				'defaultValue' => 'es',
				'options'      => self::OPTIONS,
			]
		)->get_component( 'field' )->get_markup();

		$this->assertStringContainsString( '<option value="es" selected>Spain</option>', $select );

		$checkboxes = $factory->create(
			'checkbox',
			[
				'fieldId'      => 2,
				'name'         => 'topics',
				'defaultValue' => [ 'es' ],
				'options'      => self::OPTIONS,
			]
		)->get_component( 'field' )->get_markup();

		$this->assertStringContainsString( 'value="es" checked', $checkboxes );
		$this->assertStringNotContainsString( 'value="pt" checked', $checkboxes );
	}
}
