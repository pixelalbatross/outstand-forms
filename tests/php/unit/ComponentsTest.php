<?php

namespace Outstand\WP\Forms\Tests\Unit;

use Outstand\WP\Forms\FieldFactory;

class ComponentsTest extends \WP_UnitTestCase {

	/**
	 * Text input constraint attributes must derive from the validation rules.
	 */
	public function test_text_input_markup_derives_constraints_from_rules(): void {
		$markup = $this->field_markup(
			'text',
			[
				'fieldId'   => 1,
				'required'  => true,
				'minLength' => 2,
				'maxLength' => 10,
				'pattern'   => '[a-z]+',
			]
		);

		$this->assertStringContainsString( 'required', $markup );
		$this->assertStringContainsString( 'aria-required="true"', $markup );
		$this->assertStringContainsString( 'minlength="2"', $markup );
		$this->assertStringContainsString( 'maxlength="10"', $markup );
		$this->assertStringContainsString( 'pattern="[a-z]+"', $markup );
	}

	/**
	 * Number inputs must drop string constraints and expose numeric bounds.
	 */
	public function test_number_input_markup_uses_numeric_bounds(): void {
		$markup = $this->field_markup(
			'number',
			[
				'fieldId'   => 1,
				'minLength' => 2,
				'maxLength' => 10,
				'pattern'   => '[0-9]+',
				'min'       => 1,
				'max'       => 99,
				'step'      => 5,
				'mask'      => '999',
			]
		);

		$this->assertStringNotContainsString( 'minlength=', $markup );
		$this->assertStringNotContainsString( 'maxlength=', $markup );
		$this->assertStringNotContainsString( 'pattern=', $markup );
		$this->assertStringNotContainsString( 'data-inputmask', $markup );
		$this->assertStringContainsString( 'min="1"', $markup );
		$this->assertStringContainsString( 'max="99"', $markup );
		$this->assertStringContainsString( 'step="5"', $markup );
	}

	/**
	 * Email inputs must not carry mask attributes or directives.
	 */
	public function test_email_input_markup_has_no_mask(): void {
		$markup = $this->field_markup(
			'email',
			[
				'fieldId' => 1,
				'mask'    => '999',
			]
		);

		$this->assertStringNotContainsString( 'data-inputmask', $markup );
		$this->assertStringNotContainsString( 'data-wp-init--mask', $markup );
	}

	/**
	 * Interactivity directives must bind field state and server errors.
	 */
	public function test_input_markup_carries_interactivity_directives(): void {
		$markup = $this->field_markup( 'text', [ 'fieldId' => 1 ] );

		$this->assertStringContainsString( 'data-wp-bind--aria-invalid="!context.isValid"', $markup );
		$this->assertStringContainsString( 'data-wp-on--osf-field-validate="actions.handleFieldValidate"', $markup );
		$this->assertStringContainsString( 'data-wp-on--osf-field-server-error="actions.handleFieldServerErrors"', $markup );
		$this->assertStringContainsString( 'data-wp-init--register="callbacks.registerField"', $markup );
	}

	/**
	 * Textarea markup must derive constraints from rules and carry directives.
	 */
	public function test_textarea_markup_derives_constraints_and_directives(): void {
		$markup = $this->field_markup(
			'textarea',
			[
				'fieldId'   => 1,
				'required'  => true,
				'maxLength' => 200,
			]
		);

		$this->assertStringContainsString( '<textarea', $markup );
		$this->assertStringContainsString( 'required', $markup );
		$this->assertStringContainsString( 'maxlength="200"', $markup );
		$this->assertStringContainsString( 'data-wp-on--osf-field-server-error="actions.handleFieldServerErrors"', $markup );
	}

	/**
	 * Name and id attributes must resolve through the field.
	 */
	public function test_input_markup_uses_resolved_name_and_id(): void {
		$markup = $this->field_markup(
			'text',
			[
				'fieldId' => 42,
				'name'    => 'custom_name',
			]
		);

		$this->assertStringContainsString( 'id="osf-field-42"', $markup );
		$this->assertStringContainsString( 'name="custom_name"', $markup );
	}

	/**
	 * Build the field component markup for a type/attribute combination.
	 *
	 * @param string $type       The field type.
	 * @param array  $attributes The block attributes.
	 * @return string The rendered markup.
	 */
	private function field_markup( string $type, array $attributes ): string {
		$factory = new FieldFactory();
		$field   = $factory->create( $type, $attributes );

		return $field->get_component( 'field' )->get_markup();
	}
}
