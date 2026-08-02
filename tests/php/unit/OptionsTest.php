<?php

namespace Outstand\WP\Forms\Tests\Unit;

use Outstand\WP\Forms\Options;

/**
 * Tests for the option collector.
 */
class OptionsTest extends \WP_UnitTestCase {

	/**
	 * Parsed option blocks must become label/value pairs.
	 */
	public function test_from_parsed_blocks_collects_options(): void {

		$options = Options::from_parsed_blocks(
			[
				[
					'blockName' => 'osf/field-option',
					'attrs'     => [
						'label' => 'Portugal',
						'value' => 'pt',
					],
				],
				[
					'blockName' => 'osf/field-option',
					'attrs'     => [
						'label' => 'Spain',
						'value' => 'es',
					],
				],
			]
		);

		$this->assertSame(
			[
				[
					'label' => 'Portugal',
					'value' => 'pt',
				],
				[
					'label' => 'Spain',
					'value' => 'es',
				],
			],
			$options
		);
	}

	/**
	 * An option with no value must submit its label.
	 */
	public function test_from_parsed_blocks_defaults_value_to_label(): void {

		$options = Options::from_parsed_blocks(
			[
				[
					'blockName' => 'osf/field-option',
					'attrs'     => [ 'label' => 'Portugal' ],
				],
			]
		);

		$this->assertSame( 'Portugal', $options[0]['value'] );
	}

	/**
	 * A wholly empty option describes nothing and must be dropped, so a
	 * freshly inserted option block cannot widen the submitted allowlist with
	 * an empty string.
	 */
	public function test_from_parsed_blocks_drops_empty_options(): void {

		$options = Options::from_parsed_blocks(
			[
				[
					'blockName' => 'osf/field-option',
					'attrs'     => [],
				],
				[
					'blockName' => 'osf/field-option',
					'attrs'     => [ 'label' => 'Portugal' ],
				],
			]
		);

		$this->assertCount( 1, $options );
		$this->assertSame( 'Portugal', $options[0]['label'] );
	}

	/**
	 * Blocks that are not options must be ignored.
	 */
	public function test_from_parsed_blocks_ignores_other_blocks(): void {

		$options = Options::from_parsed_blocks(
			[
				[
					'blockName' => 'core/paragraph',
					'attrs'     => [ 'content' => 'Not an option' ],
				],
			]
		);

		$this->assertSame( [], $options );
	}

	/**
	 * Rendered blocks must produce exactly what parsed blocks produce, or the
	 * options a field validates against would differ from the ones it renders.
	 */
	public function test_from_block_list_matches_from_parsed_blocks(): void {

		$parsed = [
			[
				'blockName' => 'osf/field-option',
				'attrs'     => [
					'label' => 'Portugal',
					'value' => 'pt',
				],
			],
			[
				'blockName' => 'core/paragraph',
				'attrs'     => [],
			],
			[
				'blockName' => 'osf/field-option',
				'attrs'     => [ 'label' => 'Spain' ],
			],
		];

		$blocks = array_map(
			static function ( array $block ): \WP_Block {
				return new \WP_Block( $block );
			},
			$parsed
		);

		$this->assertSame(
			Options::from_parsed_blocks( $parsed ),
			Options::from_block_list( $blocks )
		);
	}

	/**
	 * The allowlist is the option values, in order.
	 */
	public function test_get_values_returns_the_values(): void {

		$values = Options::get_values(
			[
				[
					'label' => 'Portugal',
					'value' => 'pt',
				],
				[
					'label' => 'Spain',
					'value' => 'es',
				],
			]
		);

		$this->assertSame( [ 'pt', 'es' ], $values );
	}
}
