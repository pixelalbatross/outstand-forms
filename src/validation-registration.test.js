/* eslint-env jest */
/**
 * WordPress dependencies
 */
import { store } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { validate } from './validation';

const { validators } = store('osf/form');

afterEach(() => {
	Object.keys(validators).forEach((ruleName) => {
		delete validators[ruleName];
	});
});

describe('client-side validator registration', () => {
	it('runs a newly registered rule', () => {
		store('osf/form', {
			validators: {
				startsWithA: (value) => String(value).startsWith('a'),
			},
		});

		expect(validate('acme', { startsWithA: true })).toEqual({
			isValid: true,
			errors: [],
		});
		expect(validate('example', { startsWithA: true })).toEqual({
			isValid: false,
			errors: ['startsWithA'],
		});
	});

	it('passes the rule config to the registered validator', () => {
		const spy = jest.fn(() => true);

		store('osf/form', { validators: { startsWith: spy } });

		validate('acme', { startsWith: 'a' });

		expect(spy).toHaveBeenCalledWith('acme', 'a', 'a');
	});

	it('lets a registered rule override a built-in one', () => {
		expect(validate('not-an-address', { email: true })).toEqual({
			isValid: false,
			errors: ['email'],
		});

		store('osf/form', { validators: { email: () => true } });

		expect(validate('not-an-address', { email: true })).toEqual({
			isValid: true,
			errors: [],
		});
	});

	it('fails a rule with no registered validator closed, and warns', () => {
		expect(validate('anything', { unregisteredRule: true })).toEqual({
			isValid: false,
			errors: ['unregisteredRule'],
		});

		expect(console).toHaveWarned();
	});

	it('still skips a rule that is disabled but unregistered', () => {
		expect(validate('anything', { disabledUnknownRule: false })).toEqual({
			isValid: true,
			errors: [],
		});
	});

	it('accepts a registration made after the store already exists', () => {
		expect(validate('anything', { lateRule: true })).toEqual({
			isValid: false,
			errors: ['lateRule'],
		});

		expect(console).toHaveWarned();

		store('osf/form', { validators: { lateRule: () => true } });

		expect(validate('anything', { lateRule: true })).toEqual({
			isValid: true,
			errors: [],
		});
	});

	it('accepts a registration made before validation.js is evaluated', () => {
		jest.isolateModules(() => {
			const { store: freshStore } = require('@wordpress/interactivity');

			freshStore('osf/form', { validators: { earlyRule: () => true } });

			const { validate: freshValidate } = require('./validation');

			expect(freshValidate('anything', { earlyRule: true })).toEqual({
				isValid: true,
				errors: [],
			});
		});
	});
});
