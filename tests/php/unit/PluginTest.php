<?php

namespace Outstand\WP\Forms\Tests\Unit;

use Outstand\WP\Forms\FieldFactory;
use Outstand\WP\Forms\Plugin;

class PluginTest extends \WP_UnitTestCase {

	/**
	 * Discard any shared factory built by another test.
	 */
	public function set_up(): void {
		parent::set_up();

		FieldFactory::reset_instance();
	}

	/**
	 * Discard the shared factory and any registrations made through it.
	 */
	public function tear_down(): void {
		FieldFactory::reset_instance();

		parent::tear_down();
	}

	/**
	 * Metadata for a block other than field-input must pass through untouched.
	 */
	public function test_filter_field_input_type_enum_ignores_other_blocks(): void {
		$plugin   = Plugin::get_instance();
		$metadata = [
			'name'       => 'osf/field-textarea',
			'attributes' => [
				'type' => [ 'type' => 'string' ],
			],
		];

		$this->assertSame( $metadata, $plugin->filter_field_input_type_enum( $metadata ) );
	}

	/**
	 * The field-input `type` enum must list only the registry's input-control types.
	 */
	public function test_filter_field_input_type_enum_lists_input_control_types(): void {
		$plugin   = Plugin::get_instance();
		$metadata = [
			'name'       => 'osf/field-input',
			'attributes' => [
				'type' => [
					'type'    => 'string',
					'default' => 'text',
				],
			],
		];

		$filtered = $plugin->filter_field_input_type_enum( $metadata );
		$enum     = $filtered['attributes']['type']['enum'];

		foreach ( [ 'email', 'number', 'password', 'tel', 'text', 'url' ] as $type ) {
			$this->assertContains( $type, $enum );
		}

		// The `textarea` type is rendered by a dedicated block, not field-input.
		$this->assertNotContains( 'textarea', $enum );
	}

	/**
	 * A third-party type registered through the shared factory filter must
	 * be selectable via the field-input `type` attribute.
	 */
	public function test_filter_field_input_type_enum_includes_third_party_type(): void {
		$register = function ( FieldFactory $factory ): FieldFactory {
			$factory->register( 'slug', [ 'sanitize' => 'sanitize_title' ] );

			return $factory;
		};
		add_filter( 'outstand_forms_field_factory', $register );

		$plugin   = Plugin::get_instance();
		$metadata = [
			'name'       => 'osf/field-input',
			'attributes' => [
				'type' => [
					'type'    => 'string',
					'default' => 'text',
				],
			],
		];

		$filtered = $plugin->filter_field_input_type_enum( $metadata );

		remove_filter( 'outstand_forms_field_factory', $register );

		$this->assertContains( 'slug', $filtered['attributes']['type']['enum'] );
	}
}
