<?php

namespace Outstand\WP\Forms\Components;

class Error extends AbstractComponent {

	/**
	 * {@inheritDoc}
	 */
	public function get_markup(): string {
		return sprintf(
			'<div
				id="%1$s"
				class="osf-field__error"
				data-wp-text="state.fieldErrorMessage"
				data-wp-bind--aria-hidden="context.isValid"
			></div>',
			esc_attr( $this->get_field_error_id() )
		);
	}
}
