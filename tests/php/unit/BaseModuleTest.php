<?php

namespace Outstand\WP\Forms\Tests\Unit;

use Outstand\WP\Forms\BaseModule;

class BaseModuleTest extends \WP_UnitTestCase {

	/**
	 * The default can_register() implementation must allow registration.
	 */
	public function test_can_register_defaults_to_true(): void {
		$module = new class() extends BaseModule {
			/**
			 * {@inheritDoc}
			 */
			public function register(): void {}
		};

		$this->assertTrue( $module->can_register() );
	}

	/**
	 * A subclass must be able to opt out by overriding can_register().
	 */
	public function test_can_register_can_be_overridden_to_opt_out(): void {
		$module = new class() extends BaseModule {
			/**
			 * {@inheritDoc}
			 */
			public function register(): void {}

			/**
			 * {@inheritDoc}
			 */
			public function can_register(): bool {
				return false;
			}
		};

		$this->assertFalse( $module->can_register() );
	}

	/**
	 * The register() method is abstract; concrete subclasses must supply their own implementation.
	 */
	public function test_register_must_be_implemented_by_subclass(): void {
		$module = new class() extends BaseModule {
			/**
			 * Whether the module was registered.
			 *
			 * @var bool
			 */
			public bool $registered = false;

			/**
			 * {@inheritDoc}
			 */
			public function register(): void {
				$this->registered = true;
			}
		};

		$module->register();

		$this->assertTrue( $module->registered );
	}
}
