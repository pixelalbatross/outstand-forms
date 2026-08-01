<?php

namespace Outstand\WP\Forms\Components;

interface ComponentInterface {

	/**
	 * Get the markup for the component.
	 *
	 * @return string
	 */
	public function get_markup(): string;
}
