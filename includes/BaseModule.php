<?php

namespace Outstand\WP\Forms;

abstract class AbstractModule {

	/**
	 * Registers the module.
	 *
	 * @return void
	 */
	abstract public function register(): void;
}
