<?php

namespace Outstand\WP\Forms\Fields;

use Outstand\WP\Forms\Components\Label;
use Outstand\WP\Forms\Components\Error;
use Outstand\WP\Forms\Components\HelpText;
use Outstand\WP\Forms\Components\Textarea as TextareaComponent;

class Textarea extends AbstractField {

	/**
	 * {@inheritDoc}
	 */
	public function get_type(): string {
		return 'textarea';
	}

	/**
	 * {@inheritDoc}
	 */
	public function initialize_components(): void {
		$this->components['label']     = new Label( $this );
		$this->components['help_text'] = new HelpText( $this );
		$this->components['error']     = new Error( $this );
		$this->components['field']     = new TextareaComponent( $this );
	}
}
