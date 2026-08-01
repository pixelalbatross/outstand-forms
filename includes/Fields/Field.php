<?php

namespace Outstand\WP\Forms\Fields;

use Outstand\WP\Forms\Components\ComponentInterface;
use Outstand\WP\Forms\Components\Error;
use Outstand\WP\Forms\Components\HelpText;
use Outstand\WP\Forms\Components\Input;
use Outstand\WP\Forms\Components\Label;

/**
 * A form field, entirely described by its type definition.
 *
 * Every field type shares this class; what differs between types lives in the
 * definition array supplied by the {@see \Outstand\WP\Forms\FieldFactory}
 * registry. See FieldFactory::register() for the definition shape.
 */
class Field implements FieldInterface {

	/**
	 * Valid label position values, in the order shown in the block editor.
	 *
	 * @var array
	 */
	public const LABEL_POSITIONS = [ 'top', 'left', 'right' ];

	/**
	 * Label positions that place the label beside the field instead of above or below it.
	 *
	 * @var array
	 */
	public const INLINE_LABEL_POSITIONS = [ 'left', 'right' ];

	/**
	 * Valid help text position values, in the order shown in the block editor.
	 *
	 * @var array
	 */
	public const HELP_TEXT_POSITIONS = [ 'bottom', 'top' ];

	/**
	 * Field type.
	 *
	 * @var string
	 */
	protected string $type;

	/**
	 * Field attributes.
	 *
	 * @var array
	 */
	protected array $attributes = [];

	/**
	 * Type definition.
	 *
	 * @var array
	 */
	protected array $definition = [];

	/**
	 * Field components.
	 *
	 * @var array
	 */
	protected array $components = [];

	/**
	 * Constructor.
	 *
	 * @param string $type       Field type.
	 * @param array  $attributes Field attributes.
	 * @param array  $definition Type definition.
	 */
	public function __construct( string $type, array $attributes, array $definition = [] ) {
		$this->type       = $type;
		$this->attributes = $attributes;
		$this->definition = $definition;
		$this->initialize_components();
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_attributes(): array {
		return $this->attributes;
	}

	/**
	 * {@inheritDoc}
	 */
	public function initialize_components(): void {
		$this->components['label']     = new Label( $this );
		$this->components['help_text'] = new HelpText( $this );
		$this->components['error']     = new Error( $this );
		$this->components['field']     = $this->create_field_component();
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

		$rules = $this->definition['rules'] ?? [];

		if ( is_callable( $rules ) ) {
			return (array) call_user_func( $rules, $validation_rules, $this->attributes );
		}

		return array_merge( $validation_rules, $rules );
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( mixed $value ): mixed {
		$sanitizer = $this->definition['sanitize'] ?? 'sanitize_text_field';

		return call_user_func( $sanitizer, $value );
	}

	/**
	 * {@inheritDoc}
	 */
	public function render(): void {

		$label_position     = $this->attributes['labelPosition'] ?? 'top';
		$help_text_position = $this->attributes['helpTextPosition'] ?? 'bottom';
		$has_inline_label   = in_array( $label_position, self::INLINE_LABEL_POSITIONS, true );

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

	/**
	 * Build the control component for this field type.
	 *
	 * @return ComponentInterface
	 */
	private function create_field_component(): ComponentInterface {

		$component = $this->definition['component'] ?? null;

		if ( null === $component ) {
			return new Input( $this, $this->type );
		}

		return call_user_func( $component, $this );
	}
}
