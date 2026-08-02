<?php

namespace Outstand\WP\Forms\Components;

use Outstand\WP\Forms\Fields\FieldInterface;

class Label extends AbstractComponent {

	/**
	 * Whether the label names a group of controls rather than a single one.
	 *
	 * @var bool
	 */
	protected bool $is_group;

	/**
	 * Constructor.
	 *
	 * @param FieldInterface $field    Field instance.
	 * @param bool           $is_group Whether the label names a group of controls.
	 */
	public function __construct( FieldInterface $field, bool $is_group = false ) {
		parent::__construct( $field );
		$this->is_group = $is_group;
	}

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

		// A group of radios or checkboxes has no single control to point at, so
		// the label drops `for` and becomes a plain element the group names
		// itself after through `aria-labelledby`. A `<label for>` aimed at a
		// container would be ignored by assistive technology anyway.
		$html_attributes = [
			'id'    => $this->get_field_label_id(),
			'for'   => $this->is_group ? null : $this->get_field_id(),
			'class' => 'osf-field__label',
		];

		$element = $this->is_group ? 'span' : 'label';

		return sprintf(
			'<%1$s %2$s>%3$s%4$s</%1$s>',
			$element,
			$this->build_attributes( $html_attributes ),
			wp_kses_post( $attributes['label'] ),
			wp_kses_post( $required_indicator )
		);
	}
}
