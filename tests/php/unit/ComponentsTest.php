<?php

namespace Outstand\WP\Forms\Tests\Unit;

use Outstand\WP\Forms\Components\Input;
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
		$this->assertStringNotContainsString( 'data-wp-init---mask', $markup );
	}

	/**
	 * Interactivity directives must bind to the form-owned field state and
	 * register the field with the form on init.
	 */
	public function test_input_markup_carries_interactivity_directives(): void {
		$markup = $this->field_markup( 'text', [ 'fieldId' => 1 ] );

		$this->assertStringContainsString( 'data-wp-bind--value="state.fieldValue"', $markup );
		$this->assertStringContainsString( 'data-wp-bind--aria-invalid="!state.isFieldValid"', $markup );
		$this->assertStringContainsString(
			'data-wp-bind--aria-describedby="state.fieldAriaDescribedByAttribute"',
			$markup
		);
		$this->assertStringContainsString( 'data-wp-on--focus="actions.handleFieldFocus"', $markup );
		$this->assertStringContainsString( 'data-wp-on--blur="actions.handleFieldBlur"', $markup );
		$this->assertStringContainsString( 'data-wp-on--input="actions.handleFieldChange"', $markup );
		$this->assertStringContainsString( 'data-wp-init---register="callbacks.registerField"', $markup );
	}

	/**
	 * Validation is a synchronous loop over the form-owned registry, so no
	 * field control may carry a directive for the retired event bridge.
	 */
	public function test_input_markup_has_no_custom_event_directives(): void {
		$markup = $this->field_markup( 'text', [ 'fieldId' => 1 ] );

		$this->assertStringNotContainsString( 'osf-field-validate', $markup );
		$this->assertStringNotContainsString( 'osf-field-validated', $markup );
		$this->assertStringNotContainsString( 'osf-field-server-error', $markup );
		$this->assertStringNotContainsString( 'osf-form-validated', $markup );
	}

	/**
	 * The error region visibility must follow the form-owned field validity.
	 */
	public function test_error_component_binds_to_form_owned_validity(): void {
		$factory = new FieldFactory();
		$field   = $factory->create( 'text', [ 'fieldId' => 1 ] );
		$markup  = $field->get_component( 'error' )->get_markup();

		$this->assertStringContainsString( 'data-wp-text="state.fieldErrorMessage"', $markup );
		$this->assertStringContainsString( 'data-wp-bind--aria-hidden="state.isFieldValid"', $markup );
		$this->assertStringNotContainsString( 'context.isValid', $markup );
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
		$this->assertStringContainsString( 'data-wp-bind--aria-invalid="!state.isFieldValid"', $markup );
		$this->assertStringContainsString( 'data-wp-init---register="callbacks.registerField"', $markup );
	}

	/**
	 * Textarea has no `value` attribute; its default value must render as
	 * escaped inner text instead, since HTML textareas have no value attribute.
	 */
	public function test_textarea_markup_renders_default_value_as_inner_text(): void {
		$markup = $this->field_markup(
			'textarea',
			[
				'fieldId'      => 1,
				'defaultValue' => 'Hello <script>alert(1)</script> & "quotes"',
			]
		);

		// Must not match a real value attribute, while tolerating the
		// data-wp-bind--value directive, which legitimately contains "value=".
		$this->assertDoesNotMatchRegularExpression( '/<textarea[^>]*\svalue=/', $markup );
		$this->assertStringContainsString(
			'>Hello &lt;script&gt;alert(1)&lt;/script&gt; &amp; &quot;quotes&quot;</textarea>',
			$markup
		);
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
	 * `Input::UNMASKABLE_TYPES` is the registry's only definition of which
	 * input types don't support masking; the block editor localizes it as
	 * `osfSettings.unmaskableTypes` to decide, without re-declaring the list,
	 * whether to show the Mask control.
	 */
	public function test_unmaskable_types_matches_registered_types_without_mask(): void {
		$this->assertSame( [ 'number', 'email', 'url' ], Input::UNMASKABLE_TYPES );

		foreach ( Input::UNMASKABLE_TYPES as $type ) {
			$markup = $this->field_markup(
				$type,
				[
					'fieldId' => 1,
					'mask'    => '999',
				]
			);

			$this->assertStringNotContainsString( 'data-inputmask', $markup );
		}
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
