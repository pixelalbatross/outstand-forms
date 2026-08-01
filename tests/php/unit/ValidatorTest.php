<?php

namespace Outstand\WP\Forms\Tests\Unit;

use Outstand\WP\Forms\Validation\Validator;

class ValidatorTest extends \WP_UnitTestCase {

	/**
	 * Shared client/server validation cases.
	 *
	 * The same fixture file drives src/validation.test.js, locking the parity
	 * contract between the PHP and JS validators.
	 *
	 * @return array<string, array{value: mixed, rules: array, is_valid: bool, errors: array}>
	 */
	public function data_validation_cases(): array {
		$fixture_path = dirname( __DIR__, 2 ) . '/fixtures/validation-cases.json';
		$fixture_json = file_get_contents( $fixture_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$cases        = json_decode( $fixture_json, true );

		$data = [];
		foreach ( $cases as $case ) {
			$data[ $case['description'] ] = [
				$case['value'],
				$case['rules'],
				$case['is_valid'],
				$case['errors'],
			];
		}

		return $data;
	}

	/**
	 * Validation results must match the shared client/server fixture.
	 *
	 * @dataProvider data_validation_cases
	 *
	 * @param mixed $value           The value under validation.
	 * @param array $rules           The validation rules.
	 * @param bool  $expected_valid  The expected validity.
	 * @param array $expected_errors The expected failed rule names.
	 */
	public function test_validate_matches_shared_fixture( mixed $value, array $rules, bool $expected_valid, array $expected_errors ): void {
		$validator = new Validator();
		$result    = $validator->validate( $value, $rules );

		$this->assertSame( $expected_valid, $result['is_valid'] );
		$this->assertSame( $expected_errors, $result['errors'] );
	}

	/**
	 * Custom validators registered at runtime must participate in validation.
	 */
	public function test_custom_validator_can_be_registered(): void {
		$validator = new Validator();
		$validator->register(
			'starts_with_a',
			function ( $value ) {
				return str_starts_with( (string) $value, 'a' );
			}
		);

		$passing = $validator->validate( 'apple', [ 'starts_with_a' => true ] );
		$failing = $validator->validate( 'banana', [ 'starts_with_a' => true ] );

		$this->assertTrue( $passing['is_valid'] );
		$this->assertFalse( $failing['is_valid'] );
		$this->assertSame( [ 'starts_with_a' ], $failing['errors'] );
	}

	/**
	 * Rules with no server-side validator are skipped.
	 *
	 * This is deliberately not shared with src/validation.test.js: the server is
	 * authoritative, so an unresolvable rule here is a no-op, while the client
	 * fails such a rule closed rather than reporting a value the server would
	 * reject as valid.
	 */
	public function test_unknown_rules_are_ignored(): void {
		$validator = new Validator();
		$result    = $validator->validate( 'anything', [ 'nonexistentRule' => true ] );

		$this->assertTrue( $result['is_valid'] );
		$this->assertSame( [], $result['errors'] );
	}
}
