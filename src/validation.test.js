/* eslint-env jest */
/**
 * Internal dependencies
 */
import { validate } from './validation';
import cases from '../tests/fixtures/validation-cases.json';

describe('validate', () => {
	// The same fixture file drives tests/php/unit/ValidatorTest.php, locking
	// the parity contract between the JS and PHP validators.
	it.each(cases)('$description', ({ value, rules, is_valid: isValid, errors }) => {
		const result = validate(value, rules);

		expect(result.isValid).toBe(isValid);
		expect(result.errors).toEqual(errors);
	});
});
