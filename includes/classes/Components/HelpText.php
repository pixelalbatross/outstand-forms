<?php

namespace Outstand\WP\Forms\Components;

class HelpText extends AbstractComponent {

	/**
	 * {@inheritDoc}
	 */
	public function get_markup(): string {
		$attributes = $this->get_attributes();

		if ( empty( $attributes['helpText'] ) ) {
			return '';
		}

		return sprintf(
			'<div
				id="%1$s"
				class="osf-field__help-text"
			>%2$s</div>',
			esc_attr( $this->get_field_help_text_id() ),
			wp_kses_post( $attributes['helpText'] )
		);
	}
}
