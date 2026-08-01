<?php

namespace Outstand\WP\Forms;

abstract class BaseModule {

	/**
	 * Registers the module.
	 *
	 * @return void
	 */
	abstract public function register(): void;

	/**
	 * Whether the module can be registered.
	 *
	 * @return bool
	 */
	public function can_register(): bool {
		return true;
	}
}
