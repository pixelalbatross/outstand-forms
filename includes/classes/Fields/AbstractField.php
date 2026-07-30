<?php

namespace Outstand\WP\Forms\Fields;

use Outstand\WP\Forms\Components\ComponentInterface;

abstract class AbstractField implements FieldInterface {

	/**
	 * Field attributes.
	 *
	 * @var array
	 */
	protected array $attributes = [];

	/**
	 * Field components
	 *
	 * @var array
	 */
	protected array $components = [];

	/**
	 * Constructor.
	 *
	 * @param array $attributes Field attributes.
	 */
	public function __construct( array $attributes ) {
		$this->attributes = $attributes;
		$this->initialize_components();
	}

	/**
	 * {@inheritDoc}
	 */
	abstract public function get_type(): string;

	/**
	 * {@inheritDoc}
	 */
	abstract public function initialize_components(): void;

	/**
	 * {@inheritDoc}
	 */
	public function get_attributes(): array {
		return $this->attributes;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_component( string $name ): ?ComponentInterface {
		return $this->components[ $name ] ?? null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_components(): array {
		return $this->components;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_field_id(): string {
		return sprintf( 'osf-field-%1$s', $this->attributes['fieldId'] ?? '' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_field_name(): string {

		if ( ! empty( $this->attributes['name'] ) ) {
			return $this->attributes['name'];
		}

		return sprintf( 'field_%1$s', $this->attributes['fieldId'] ?? '' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label_id(): string {
		return sprintf( 'osf-label-%1$s', $this->attributes['fieldId'] ?? '' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_help_text_id(): string {
		return sprintf( 'osf-help-text-%1$s', $this->attributes['fieldId'] ?? '' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_error_id(): string {
		return sprintf( 'osf-error-%1$s', $this->attributes['fieldId'] ?? '' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_validation_rules(): array {

		$validation_rules = [
			'required' => $this->attributes['required'] ?? false,
		];

		if ( ! empty( $this->attributes['minLength'] ) ) {
			$validation_rules['minLength'] = (int) $this->attributes['minLength'];
		}

		if ( ! empty( $this->attributes['maxLength'] ) ) {
			$validation_rules['maxLength'] = (int) $this->attributes['maxLength'];
		}

		if ( ! empty( $this->attributes['pattern'] ) ) {
			$validation_rules['pattern'] = $this->attributes['pattern'];
		}

		return $validation_rules;
	}

	/**
	 * {@inheritDoc}
	 */
	public function render(): void {

		$label_position     = $this->attributes['labelPosition'] ?? 'top';
		$help_text_position = $this->attributes['helpTextPosition'] ?? 'bottom';
		$has_inline_label   = in_array( $label_position, [ 'left', 'right' ], true );

		$label     = $this->get_component( 'label' )?->get_markup() ?? '';
		$help_text = $this->get_component( 'help_text' )?->get_markup() ?? '';
		$error     = $this->get_component( 'error' )?->get_markup() ?? '';
		$field     = $this->get_component( 'field' )?->get_markup() ?? '';

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		?>

		<?php if ( 'right' !== $label_position ) : ?>
			<?php echo $label; ?>
		<?php endif; ?>

		<?php if ( ! $has_inline_label && 'top' === $help_text_position ) : ?>
			<?php echo $help_text; ?>
		<?php endif; ?>

		<?php if ( $has_inline_label ) : ?>
			<div class="osf-field__wrapper">
				<?php if ( 'top' === $help_text_position ) : ?>
					<?php echo $help_text; ?>
				<?php endif; ?>
				<?php echo $field; ?>
				<?php echo $error; ?>
				<?php if ( 'bottom' === $help_text_position ) : ?>
					<?php echo $help_text; ?>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<?php echo $field; ?>
		<?php endif; ?>

		<?php if ( ! $has_inline_label ) : ?>
			<?php echo $error; ?>
		<?php endif; ?>

		<?php if ( ! $has_inline_label && 'bottom' === $help_text_position ) : ?>
			<?php echo $help_text; ?>
		<?php endif; ?>

		<?php if ( 'right' === $label_position ) : ?>
			<?php echo $label; ?>
		<?php endif; ?>

		<?php
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
