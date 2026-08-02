<?php

namespace Outstand\WP\Forms\Components;

class Select extends AbstractComponent {

	/**
	 * {@inheritDoc}
	 */
	public function get_markup(): string {

		$attributes = $this->get_attributes();
		$rules      = $this->field->get_validation_rules();

		$default_value = (string) ( $attributes['defaultValue'] ?? '' );
		$placeholder   = (string) ( $attributes['placeholder'] ?? '' );
		$aria_label    = $attributes['ariaLabel'] ?? '';
		$required      = ! empty( $rules['required'] );
		$options       = $attributes['options'] ?? [];

		$html_attributes = [
			'id'              => $this->get_field_id(),
			'name'            => $this->get_field_name(),
			'required'        => $required,
			'aria-required'   => $required ? 'true' : null,
			'aria-label'      => $aria_label ?: null,
			'aria-labelledby' => $this->get_field_label_id(),
			'class'           => 'osf-field__select',
		] + $this->get_interactivity_directives();

		// A select always has a value, so a required one needs an empty first
		// option for "nothing chosen yet" — without it the browser reports the
		// first real option as selected and `required` can never fail.
		$markup = '';
		if ( '' !== $placeholder || $required ) {
			$markup .= sprintf(
				'<option value="" %1$s>%2$s</option>',
				'' === $default_value ? 'selected' : '',
				esc_html( $placeholder )
			);
		}

		foreach ( $options as $option ) {
			$markup .= sprintf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $option['value'] ),
				(string) $option['value'] === $default_value ? 'selected' : '',
				esc_html( $option['label'] )
			);
		}

		return sprintf(
			'<select %1$s>%2$s</select>',
			$this->build_attributes( $html_attributes ),
			$markup
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * A select reports through `change`, not `input`: the value only settles
	 * once an option is committed.
	 */
	protected function get_interactivity_directives(): array {
		$directives = parent::get_interactivity_directives();

		unset( $directives['data-wp-on--input'] );

		$directives['data-wp-on--change'] = 'actions.handleFieldChange';

		return $directives;
	}
}
