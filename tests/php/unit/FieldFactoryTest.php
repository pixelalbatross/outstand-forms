<?php

namespace Outstand\WP\Forms\Tests\Unit;

use InvalidArgumentException;
use Outstand\WP\Forms\Components\ComponentInterface;
use Outstand\WP\Forms\FieldFactory;
use Outstand\WP\Forms\Fields\Field;
use Outstand\WP\Forms\Fields\FieldInterface;

class FieldFactoryTest extends \WP_UnitTestCase {

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
	 * The create() method must return a field carrying the requested type.
	 */
	public function test_create_returns_field_of_requested_type(): void {
		$factory = new FieldFactory();
		$field   = $factory->create( 'email', [ 'fieldId' => 1 ] );

		$this->assertInstanceOf( Field::class, $field );
		$this->assertSame( 'email', $field->get_type() );
		$this->assertTrue( $field->get_validation_rules()['email'] );
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
	 * Each built-in type must sanitize the way it always has.
	 */
	public function test_sanitize_per_built_in_type(): void {
		$factory = new FieldFactory();

		$this->assertSame( 'user@example.com', $factory->sanitize( 'email', ' user@example.com ' ) );
		$this->assertSame( 'https://example.com/a', $factory->sanitize( 'url', 'https://example.com/a' ) );
		$this->assertSame( 12.5, $factory->sanitize( 'number', '12.5' ) );
		$this->assertSame( "a\nb", $factory->sanitize( 'textarea', "a\nb" ) );
		$this->assertSame( 'plain', $factory->sanitize( 'text', '<b>plain</b>' ) );
		$this->assertSame( 'plain', $factory->sanitize( 'unknown-type', '<b>plain</b>' ) );
	}

	/**
	 * A non-numeric number submission must become null, never 0.
	 */
	public function test_sanitize_number_rejects_non_numeric(): void {
		$factory = new FieldFactory();

		$this->assertNull( $factory->sanitize( 'number', 'abc' ) );
		$this->assertNull( $factory->sanitize( 'number', '' ) );
	}

	/**
	 * Runtime registration must make the type creatable, renderable and sanitizable.
	 */
	public function test_register_custom_type(): void {
		$factory = new FieldFactory();
		$factory->register(
			'slug',
			[
				'component' => static function ( FieldInterface $field ): ComponentInterface {
					return new StubComponent( $field );
				},
				'rules'     => [ 'pattern' => '[a-z-]+' ],
				'sanitize'  => 'sanitize_title',
			]
		);

		$this->assertTrue( $factory->supports( 'slug' ) );

		$field = $factory->create( 'slug', [ 'fieldId' => 1 ] );

		$this->assertSame( 'slug', $field->get_type() );
		$this->assertSame( '[a-z-]+', $field->get_validation_rules()['pattern'] );
		$this->assertSame( '<stub id="osf-field-1"></stub>', $field->get_component( 'field' )->get_markup() );

		ob_start();
		$field->render();
		$rendered = ob_get_clean();

		$this->assertStringContainsString( '<stub id="osf-field-1"></stub>', $rendered );

		$this->assertSame( 'hello-world', $field->sanitize( 'Hello World' ) );
		$this->assertSame( 'hello-world', $factory->sanitize( 'slug', 'Hello World' ) );
	}

	/**
	 * A definition may omit every key and still produce a working text input.
	 */
	public function test_register_minimal_definition(): void {
		$factory = new FieldFactory();
		$factory->register( 'code', [] );

		$field = $factory->create( 'code', [ 'fieldId' => 1 ] );

		$this->assertSame( [ 'required' => false ], $field->get_validation_rules() );
		$this->assertStringContainsString( 'type="code"', $field->get_component( 'field' )->get_markup() );
		$this->assertSame( 'plain', $factory->sanitize( 'code', '<b>plain</b>' ) );
	}

	/**
	 * A rules callable must be able to rewrite the base rules entirely.
	 */
	public function test_register_rules_callable(): void {
		$factory = new FieldFactory();
		$factory->register(
			'bounded',
			[
				'rules' => static function ( array $rules, array $attributes ): array {
					unset( $rules['maxLength'] );
					$rules['max'] = (float) ( $attributes['max'] ?? 0 );

					return $rules;
				},
			]
		);

		$field = $factory->create(
			'bounded',
			[
				'fieldId'   => 1,
				'maxLength' => 5,
				'max'       => 3,
			]
		);

		$rules = $field->get_validation_rules();

		$this->assertArrayNotHasKey( 'maxLength', $rules );
		$this->assertSame( 3.0, $rules['max'] );
	}

	/**
	 * The register() method must reject an empty type.
	 */
	public function test_register_throws_on_empty_type(): void {
		$factory = new FieldFactory();

		$this->expectException( InvalidArgumentException::class );
		$factory->register( '', [] );
	}

	/**
	 * The register() method must reject a non-callable component.
	 */
	public function test_register_throws_on_invalid_component(): void {
		$factory = new FieldFactory();

		$this->expectException( InvalidArgumentException::class );
		$factory->register( 'broken', [ 'component' => 'not-a-callable' ] );
	}

	/**
	 * The register() method must reject a non-callable sanitizer.
	 */
	public function test_register_throws_on_invalid_sanitizer(): void {
		$factory = new FieldFactory();

		$this->expectException( InvalidArgumentException::class );
		$factory->register( 'broken', [ 'sanitize' => 'osf_not_a_function' ] );
	}

	/**
	 * The register() method must reject rules that are neither array nor callable.
	 */
	public function test_register_throws_on_invalid_rules(): void {
		$factory = new FieldFactory();

		$this->expectException( InvalidArgumentException::class );
		$factory->register( 'broken', [ 'rules' => 'nope' ] );
	}

	/**
	 * The shared instance must be reused and exposed through the filter.
	 */
	public function test_instance_is_shared_and_filtered(): void {
		$register = function ( FieldFactory $factory ): FieldFactory {
			$factory->register( 'rating', [ 'sanitize' => 'absint' ] );

			return $factory;
		};
		add_filter( 'outstand_forms_field_factory', $register );

		$factory = FieldFactory::instance();

		remove_filter( 'outstand_forms_field_factory', $register );

		$this->assertSame( $factory, FieldFactory::instance() );
		$this->assertTrue( $factory->supports( 'rating' ) );
		$this->assertSame( 4, FieldFactory::instance()->sanitize( 'rating', '4.7' ) );
	}
}
