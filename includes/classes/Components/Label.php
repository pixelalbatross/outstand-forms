<?php

namespace Outstand\WP\Forms\Components;

class Label extends AbstractComponent {

	/**
	 * {@inheritDoc}
	 */
	public function get_markup(): string {
		$attributes = $this->get_attributes();

		if ( empty( $attributes['label'] ) ) {
			return '';
		}

		$required = $attributes['required'] ?? false;

		$required_indicator = '';
		if ( ! empty( $required ) && ! empty( $attributes['requiredIndicator'] ) ) {
			$required_indicator = sprintf(
				' <span class="osf-field__required-indicator">%s</span>',
				esc_html( $attributes['requiredIndicator'] )
			);
		}

		return sprintf(
			'<label
				id="%1$s"
				for="%2$s"
				class="osf-field__label"
			>%3$s%4$s</label>',
			esc_attr( $this->get_field_label_id() ),
			esc_attr( $this->get_field_id() ),
			wp_kses_post( $attributes['label'] ),
			wp_kses_post( $required_indicator )
		);
	}
}
