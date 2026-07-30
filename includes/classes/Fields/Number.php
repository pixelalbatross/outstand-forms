<?php

namespace Outstand\WP\Forms\Fields;

use Outstand\WP\Forms\Components\Label;
use Outstand\WP\Forms\Components\Error;
use Outstand\WP\Forms\Components\HelpText;
use Outstand\WP\Forms\Components\Input;

class Number extends AbstractField {

	/**
	 * {@inheritDoc}
	 */
	public function get_type(): string {
		return 'number';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_validation_rules(): array {
		$validation_rules = parent::get_validation_rules();

		$remove_list = [ 'minLength', 'maxLength', 'pattern' ];
		foreach ( $remove_list as $rule ) {
			if ( isset( $validation_rules[ $rule ] ) ) {
				unset( $validation_rules[ $rule ] );
			}
		}

		if ( ! empty( $this->attributes['min'] ) ) {
			$validation_rules['min'] = (float) $this->attributes['min'];
		}

		if ( ! empty( $this->attributes['max'] ) ) {
			$validation_rules['max'] = (float) $this->attributes['max'];
		}

		return $validation_rules;
	}

	/**
	 * {@inheritDoc}
	 */
	public function initialize_components(): void {
		$this->components['label']     = new Label( $this );
		$this->components['help_text'] = new HelpText( $this );
		$this->components['error']     = new Error( $this );
		$this->components['field']     = new Input( $this, $this->get_type() );
	}
}
