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

	// These branches (the "absent value" early return for `url`, `min` and
	// `max`) aren't exercised by the shared fixture file above, which only
	// covers the "passes on empty value" case for `email`, `pattern`,
	// `minLength` and `maxLength`. Adding cases here rather than to the
	// shared fixture keeps the JS/PHP parity contract untouched.
	it('url passes on an empty value', () => {
		expect(validate('', { url: true })).toEqual({ isValid: true, errors: [] });
	});

	it('url passes on a null value', () => {
		expect(validate(null, { url: true })).toEqual({ isValid: true, errors: [] });
	});

	it('min passes on an empty value', () => {
		expect(validate('', { min: 10 })).toEqual({ isValid: true, errors: [] });
	});

	it('min passes on a null value', () => {
		expect(validate(null, { min: 10 })).toEqual({ isValid: true, errors: [] });
	});

	it('max passes on an empty value', () => {
		expect(validate('', { max: 10 })).toEqual({ isValid: true, errors: [] });
	});

	it('max passes on a null value', () => {
		expect(validate(null, { max: 10 })).toEqual({ isValid: true, errors: [] });
	});
});
