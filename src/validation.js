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
 */

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
	const trimmedValue = typeof value === 'string' ? value.trim() : value;
	return trimmedValue !== '';
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

	return [...String(value)].length >= length;
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

	return [...String(value)].length <= length;
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
 * Map of rule names to their validator functions.
 *
 * @type {Object<string, Function>}
 */
const validators = {
	required,
	email,
	url,
	pattern,
	minLength,
	maxLength,
	min,
	max,
};

/**
 * Validate a value against a set of rules.
 *
 * Rules are keyed by validator name. A rule value of `false` disables the rule,
 * `true` runs the validator with no extra params, and any other value is passed
 * as the second argument to the validator function.
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

		const validator = validators[ruleName];

		if (!validator) {
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
