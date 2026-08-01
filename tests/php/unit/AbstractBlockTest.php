<?php

namespace Outstand\WP\Forms\Tests\Unit;

use Outstand\WP\Forms\Blocks\AbstractBlock;

class AbstractBlockTest extends \WP_UnitTestCase {

	/**
	 * A concrete block must expose its own name and honor the register() contract.
	 */
	public function test_get_name_and_register_are_implemented_by_subclass(): void {
		$block = new class() extends AbstractBlock {
			/**
			 * Whether register() ran.
			 *
			 * @var bool
			 */
			public bool $registered = false;

			/**
			 * {@inheritDoc}
			 */
			public function get_name(): string {
				return 'example-block';
			}

			/**
			 * {@inheritDoc}
			 */
			public function register(): void {
				$this->registered = true;
			}
		};

		$this->assertSame( 'example-block', $block->get_name() );

		$block->register();

		$this->assertTrue( $block->registered );
	}

	/**
	 * A block inherits the BaseModule default for can_register() unless it opts out.
	 */
	public function test_can_register_defaults_to_true(): void {
		$block = new class() extends AbstractBlock {
			/**
			 * {@inheritDoc}
			 */
			public function get_name(): string {
				return 'example-block';
			}

			/**
			 * {@inheritDoc}
			 */
			public function register(): void {}
		};

		$this->assertTrue( $block->can_register() );
	}
}
