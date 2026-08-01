<?php

namespace Outstand\WP\Forms\Tests\Unit;

use Outstand\WP\Forms\Components\AbstractComponent;

/**
 * Minimal third-party control component used by the registration tests.
 */
class StubComponent extends AbstractComponent {

	/**
	 * {@inheritDoc}
	 */
	public function get_markup(): string {
		return sprintf( '<stub id="%s"></stub>', esc_attr( $this->get_field_id() ) );
	}
}
