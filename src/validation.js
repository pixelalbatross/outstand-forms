/**
 * Client-side field validation.
 *
 * Validation rules are defined server-side by PHP (FieldFactory + field classes)
 * and exposed to the frontend via the Interactivity API context. This module
 * provides the validator functions that evaluate those rules on the client.
 *
 * Each validator follows the same convention:
 * - Empty/missing values pass for all rules except `required`.
 * - Returns `true` when valid, `false` when invalid.
 *
 * Third-party rules are registered through the plugin's own Interactivity
 * store namespace, mirroring `Validation\Validator::register()` on the server:
 *
 * `store( 'osf/form', { validators: { ruleName: ( value, params, config ) => true } } )`
 */

/**
 * WordPress dependencies
 */
import { store } from '@wordpress/interactivity';

/**
 * The plugin's Interactivity API store namespace.
 */
const NAMESPACE = 'osf/form';

/**
 * Email validation regex based on HTML5 spec.
 *
 * @see https://html.spec.whatwg.org/multipage/input.html#valid-e-mail-address
 */
const EMAIL_REGEX =
	/^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;

/**
 * URL validation regex.
 */
const URL_REGEX =
	/^https?:\/\/(?:(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}|localhost|\d{1,3}(?:\.\d{1,3}){3})(?::\d{1,5})?(?:\/[^\s]*)?$/i;

/**
 * Cache for compiled regex patterns used by the `pattern` validator.
 *
 * @type {Map<string, RegExp>}
 */
const regexCache = new Map();

/**
 * Determine whether a value counts as "absent" for rule guards.
 *
 * A value is only considered absent when it is an empty string, null, or
 * undefined. Falsy-but-present values such as the string "0", the number 0,
 * or the boolean false are treated as real values and must go through the
 * relevant rule, mirroring the server-side validator in Validator.php.
 *
 * @param {*} value The value to check.
 * @return {boolean} True if the value is absent.
 */
function isAbsent(value) {
	return value === '' || value === null || value === undefined;
}

/**
 * Cast a value to a string the way PHP does.
 *
 * JavaScript's `String()` and PHP's `(string)` cast agree on everything the
 * form actually submits, but disagree on booleans: `String( false )` is
 * `"false"` (5 characters) while `(string) false` is `""` (0). Since the
 * server is authoritative, the client has to match it — otherwise a value the
 * server rejects for being too short looks valid in the browser.
 *
 * @param {*} value The value to cast.
 * @return {string} The value as PHP would stringify it.
 */
function toStringValue(value) {
	if (typeof value === 'boolean') {
		return value ? '1' : '';
	}

	return String(value);
}

/**
 * Validate that a value is present and non-empty.
 *
 * @param {*}      value  The value to validate.
 * @param {Object} params Extra parameters (unused).
 * @param {*}      config The raw rule config. A falsy config disables the rule.
 * @return {boolean} True if the value is non-empty.
 */
function required(value, params, config) {
	if (!config) {
		return true;
	}

	if (value === undefined || value === null) {
		return false;
	}

	// A multi-value field arrives as an array, where "filled in" means at least
	// one box ticked. An array of empty strings is nothing ticked.
	if (Array.isArray(value)) {
		return value.some((item) => !isAbsent(item));
	}

	const trimmedValue = typeof value === 'string' ? value.trim() : value;
	return trimmedValue !== '';
}

/**
 * Count how many choices a value actually carries.
 *
 * @param {*} value The value to count.
 * @return {number} The number of selections.
 */
function countSelected(value) {
	if (isAbsent(value)) {
		return 0;
	}

	const values = Array.isArray(value) ? value : [value];

	return values.filter((item) => !isAbsent(item)).length;
}

/**
 * Validate that every submitted value is one the field offers.
 *
 * @param {*}     value  The value to validate.
 * @param {Array} config The allowed values.
 * @return {boolean} True if every value is allowed.
 */
function options(value, config) {
	if (!Array.isArray(config) || config.length === 0 || isAbsent(value)) {
		return true;
	}

	const allowed = config.map(toStringValue);
	const submitted = (Array.isArray(value) ? value : [value]).map(toStringValue);

	// An unticked group submits nothing rather than an empty item, so a blank
	// here is a caller normalizing absence; `required` decides whether that is
	// acceptable.
	return submitted.every((item) => item === '' || allowed.includes(item));
}

/**
 * Validate the minimum number of selected values.
 *
 * @param {*}      value  The value to validate.
 * @param {number} config The minimum count.
 * @return {boolean} True if enough values are selected.
 */
function minSelected(value, config = 0) {
	if (!Number.isFinite(Number(config)) || Number(config) < 1) {
		return true;
	}

	// A minimum says how many to pick, not that picking is compulsory:
	// `required` is the only rule that objects to an empty value. The two
	// compose — required with a minimum of two means "at least two", optional
	// with a minimum of two means "none, or at least two".
	if (countSelected(value) === 0) {
		return true;
	}

	return countSelected(value) >= Number(config);
}

/**
 * Validate the maximum number of selected values.
 *
 * @param {*}      value  The value to validate.
 * @param {number} config The maximum count.
 * @return {boolean} True if no more than the maximum are selected.
 */
function maxSelected(value, config = 0) {
	if (!Number.isFinite(Number(config)) || Number(config) < 1) {
		return true;
	}

	return countSelected(value) <= Number(config);
}

/**
 * Validate that a value is a valid email address.
 *
 * @param {string} value The value to validate.
 * @return {boolean} True if empty or a valid email.
 */
function email(value) {
	if (isAbsent(value)) {
		return true;
	}

	return EMAIL_REGEX.test(value);
}

/**
 * Validate that a value is a valid URL.
 *
 * @param {string} value The value to validate.
 * @return {boolean} True if empty or a valid URL.
 */
function url(value) {
	if (isAbsent(value)) {
		return true;
	}

	return URL_REGEX.test(value);
}

/**
 * Validate that a value matches a regex pattern.
 *
 * The pattern must match the entire value, mirroring the HTML `pattern`
 * attribute semantics and the server-side validator.
 *
 * Compiled RegExp objects are cached to avoid repeated construction.
 *
 * @param {string} value      The value to validate.
 * @param {string} patternStr The regex pattern string.
 * @return {boolean} True if empty, no pattern provided, or the value matches.
 */
function pattern(value, patternStr = '') {
	if (isAbsent(value) || !patternStr) {
		return true;
	}

	try {
		let regex = regexCache.get(patternStr);
		if (!regex) {
			regex = new RegExp(`^(?:${patternStr})$`);
			regexCache.set(patternStr, regex);
		}
		return regex.test(value);
	} catch {
		return false;
	}
}

/**
 * Validate that a string value meets a minimum length.
 *
 * Uses the spread operator to correctly count Unicode characters.
 *
 * @param {string} value  The value to validate.
 * @param {number} length The minimum character count.
 * @return {boolean} True if empty, length is 0, or the value meets the minimum.
 */
function minLength(value, length = 0) {
	if (isAbsent(value) || length === 0) {
		return true;
	}

	return [...toStringValue(value)].length >= length;
}

/**
 * Validate that a string value does not exceed a maximum length.
 *
 * Uses the spread operator to correctly count Unicode characters.
 *
 * @param {string} value  The value to validate.
 * @param {number} length The maximum character count.
 * @return {boolean} True if empty, length is 0, or the value is within the limit.
 */
function maxLength(value, length = 0) {
	if (isAbsent(value) || length === 0) {
		return true;
	}

	return [...toStringValue(value)].length <= length;
}

/**
 * Validate that a numeric value meets a minimum.
 *
 * @param {*}      value    The value to validate.
 * @param {number} minValue The minimum allowed value.
 * @return {boolean} True if empty, non-numeric, or the value meets the minimum.
 */
function min(value, minValue = 0) {
	if (isAbsent(value) || isNaN(value)) {
		return true;
	}

	return parseFloat(value) >= parseFloat(minValue);
}

/**
 * Validate that a numeric value does not exceed a maximum.
 *
 * @param {*}      value    The value to validate.
 * @param {number} maxValue The maximum allowed value.
 * @return {boolean} True if empty, non-numeric, or the value is within the limit.
 */
function max(value, maxValue = 0) {
	if (isAbsent(value) || isNaN(value)) {
		return true;
	}

	return parseFloat(value) <= parseFloat(maxValue);
}

/**
 * Map of built-in rule names to their validator functions.
 *
 * Mirrors `Validator::register_default_validators()`; the two sets are locked
 * together by src/validation-parity.test.js.
 *
 * @type {Object<string, Function>}
 */
const defaultValidators = {
	required,
	email,
	url,
	pattern,
	minLength,
	maxLength,
	min,
	max,
	options,
	minSelected,
	maxSelected,
};

/**
 * Registry of third-party validators, carried by the plugin's own store.
 *
 * `store()` deep-merges into a namespace-keyed registry and mutates the object
 * in place, so the reference captured here stays live no matter whether a
 * third-party module ran before or after this one. Seeding it with an empty
 * object never clobbers an earlier registration.
 *
 * @type {Object<string, Function>}
 */
const { validators: customValidators } = store(NAMESPACE, { validators: {} });

/**
 * Rule names already reported as unregistered.
 *
 * @type {Set<string>}
 */
const warnedRules = new Set();

/**
 * Warn once per rule name about a rule with no client-side validator.
 *
 * Not gated on `NODE_ENV`: the plugin only ever ships the production bundle, so
 * a development-only warning would be stripped from every site that could hit
 * this. Warning once per rule name keeps it out of the submit loop.
 *
 * @param {string} ruleName The unregistered rule name.
 */
function warnUnknownRule(ruleName) {
	if (warnedRules.has(ruleName)) {
		return;
	}

	warnedRules.add(ruleName);

	// eslint-disable-next-line no-console
	console.warn(
		`Outstand Forms: no client-side validator is registered for the rule "${ruleName}", ` +
			'so the field is treated as invalid. Register one with ' +
			`store( '${NAMESPACE}', { validators: { ${ruleName}: ( value, params, config ) => true } } ).`,
	);
}

/**
 * Resolve the validator function for a rule name.
 *
 * Registered validators take precedence over the built-in ones, matching the
 * last-write-wins semantics of `Validator::register()`.
 *
 * @param {string} ruleName The rule name.
 * @return {Function|undefined} The validator, or undefined when none is registered.
 */
function getValidator(ruleName) {
	const custom = customValidators?.[ruleName];

	if (typeof custom === 'function') {
		return custom;
	}

	const builtIn = defaultValidators[ruleName];

	return typeof builtIn === 'function' ? builtIn : undefined;
}

/**
 * Validate a value against a set of rules.
 *
 * Rules are keyed by validator name. A rule value of `false` disables the rule,
 * `true` runs the validator with no extra params, and any other value is passed
 * as the second argument to the validator function.
 *
 * A rule with no registered validator fails closed: the server is authoritative
 * and would reject the value on submit, so reporting it as valid here is the
 * one outcome that is always wrong.
 *
 * @param {*}      value The value to validate.
 * @param {Object} rules The validation rules keyed by validator name.
 * @return {{ isValid: boolean, errors: string[] }} Result with a boolean and an array of failed rule names.
 */
export function validate(value, rules = {}) {
	const errors = [];

	for (const [ruleName, ruleConfig] of Object.entries(rules)) {
		if (ruleConfig === false) {
			continue;
		}

		const validator = getValidator(ruleName);

		if (!validator) {
			warnUnknownRule(ruleName);
			errors.push(ruleName);
			continue;
		}

		const params = ruleConfig === true ? {} : ruleConfig;
		const isValid = validator(value, params, ruleConfig);

		if (!isValid) {
			errors.push(ruleName);
		}
	}

	return {
		isValid: errors.length === 0,
		errors,
	};
}
