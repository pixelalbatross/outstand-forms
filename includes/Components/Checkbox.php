<?php

namespace Outstand\WP\Forms\Components;

/**
 * A single checkbox, the consent field's control.
 *
 * Unlike {@see Choice}, this is one input with one value: it is ticked or it
 * is absent from the submission, which is what makes it a scalar field.
 */
class Checkbox extends AbstractComponent {

	/**
	 * The value a ticked box submits.
	 *
	 * @var string
	 */
	public const CHECKED_VALUE = '1';

	/**
	 * {@inheritDoc}
	 */
	public function get_markup(): string {

		$attributes = $this->get_attributes();
		$rules      = $this->field->get_validation_rules();

		$aria_label = $attributes['ariaLabel'] ?? '';
		$required   = ! empty( $rules['required'] );
		$checked    = self::CHECKED_VALUE === (string) ( $attributes['defaultValue'] ?? '' );

		$html_attributes = [
			'type'                  => 'checkbox',
			'id'                    => $this->get_field_id(),
			'name'                  => $this->get_field_name(),
			'value'                 => self::CHECKED_VALUE,
			'checked'               => $checked,
			'required'              => $required,
			'aria-required'         => $required ? 'true' : null,
			'aria-label'            => $aria_label ?: null,
			'class'                 => 'osf-field__checkbox',
			'data-wp-bind--checked' => 'state.isFieldChecked',
			'data-wp-on--change'    => 'actions.handleConsentChange',
		] + $this->get_interactivity_directives();

		// `value` is fixed on a checkbox; binding it would fight the `checked`
		// binding that actually carries this field's state.
		unset( $html_attributes['data-wp-bind--value'], $html_attributes['data-wp-on--input'] );

		// Wrapped, so the box keeps its own size. A field lays its parts out in
		// a column, and a column stretches what it holds to the full width —
		// which is right for a text input and absurd for a tick box. The
		// wrapper takes the stretch; the box inside it stays inline.
		return sprintf(
			'<span class="osf-field__checkbox-field"><input %s /></span>',
			$this->build_attributes( $html_attributes )
		);
	}
}
