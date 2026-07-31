<?php

namespace Outstand\WP\Forms\Tests\Unit;

use InvalidArgumentException;
use Outstand\WP\Forms\FieldFactory;
use Outstand\WP\Forms\Fields\Email;
use Outstand\WP\Forms\Fields\Text;

class FieldFactoryTest extends \WP_UnitTestCase {

	/**
	 * All built-in field types must be supported.
	 */
	public function test_supports_built_in_types(): void {
		$factory = new FieldFactory();

		foreach ( [ 'email', 'number', 'password', 'tel', 'text', 'textarea', 'url' ] as $type ) {
			$this->assertTrue( $factory->supports( $type ), "Type {$type} should be supported" );
		}

		$this->assertFalse( $factory->supports( 'date' ) );
	}

	/**
	 * The create() method must return the class mapped to the type.
	 */
	public function test_create_returns_mapped_field(): void {
		$factory = new FieldFactory();
		$field   = $factory->create( 'email', [ 'fieldId' => 1 ] );

		$this->assertInstanceOf( Email::class, $field );
		$this->assertSame( 'email', $field->get_type() );
	}

	/**
	 * The create() method must reject unsupported types.
	 */
	public function test_create_throws_on_unsupported_type(): void {
		$factory = new FieldFactory();

		$this->expectException( InvalidArgumentException::class );
		$factory->create( 'date', [] );
	}

	/**
	 * Runtime registration must make the type creatable.
	 */
	public function test_register_custom_type(): void {
		$factory = new FieldFactory();
		$factory->register( 'fancy-text', Text::class );

		$this->assertTrue( $factory->supports( 'fancy-text' ) );
		$this->assertInstanceOf( Text::class, $factory->create( 'fancy-text', [ 'fieldId' => 1 ] ) );
	}

	/**
	 * The register() method must reject classes that do not exist.
	 */
	public function test_register_throws_on_missing_class(): void {
		$factory = new FieldFactory();

		$this->expectException( InvalidArgumentException::class );
		$factory->register( 'broken', 'Outstand\WP\Forms\Fields\DoesNotExist' );
	}

	/**
	 * The register() method must reject classes that do not implement FieldInterface.
	 */
	public function test_register_throws_on_wrong_interface(): void {
		$factory = new FieldFactory();

		$this->expectException( InvalidArgumentException::class );
		$factory->register( 'broken', FieldFactory::class );
	}
}
