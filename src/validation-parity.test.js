/* eslint-env jest */
/**
 * PHP/JS validator parity guard.
 *
 * `includes/classes/Validation/Validator.php` and `src/validation.js` are two
 * independent implementations of the same validation rules, kept in sync only
 * by a comment convention ("Must match the regex used in src/validation.js").
 * Nothing enforces that convention, so this suite reads the literal source of
 * both files and asserts they still agree on:
 *
 * - the EMAIL_REGEX pattern
 * - the URL_REGEX pattern
 * - the set of registered rule names
 *
 * This lives on the JS side (rather than in PHPUnit) because the JS test
 * suite (`npm run test:js`) runs standalone in Node with no WordPress test
 * database required, so this guard keeps running even when the PHP suite
 * cannot (e.g. `wp-env` is not started). Node's built-in `fs` module also
 * makes reading the sibling PHP source trivial, with no parser dependency.
 *
 * If any extraction below fails to find what it is looking for, the test
 * throws rather than skipping — a parity guard that silently stops checking
 * is worse than no guard at all.
 */

const fs = require('fs');
const path = require('path');

const PHP_VALIDATOR_PATH = path.join(__dirname, '../includes/classes/Validation/Validator.php');
const JS_VALIDATOR_PATH = path.join(__dirname, './validation.js');

const phpSource = fs.readFileSync(PHP_VALIDATOR_PATH, 'utf8');
const jsSource = fs.readFileSync(JS_VALIDATOR_PATH, 'utf8');

/**
 * Extract a `private const NAME = '/pattern/flags';` PHP regex constant.
 *
 * PCRE requires the delimiter character (`/`) to be escaped everywhere it
 * appears in the pattern, including inside character classes, while JS only
 * requires it outside character classes. That is a delimiter-escaping
 * artifact, not a real behavioral difference, so `\/` is collapsed to `/`
 * on both sides before comparison.
 *
 * @param {string} src  PHP source text.
 * @param {string} name Constant name (e.g. "EMAIL_REGEX").
 * @return {{source: string, flags: string}} The pattern body and flags.
 */
function extractPhpConstantRegex(src, name) {
	const constantPattern = new RegExp(`private const ${name} = '((?:[^'\\\\]|\\\\.)*)';`);
	const match = src.match(constantPattern);

	if (!match) {
		throw new Error(
			`Parity guard could not find PHP constant "${name}" in ${PHP_VALIDATOR_PATH}. ` +
				'Either it was renamed/removed, or its declaration no longer matches ' +
				"the expected `private const NAME = '...';` shape. Update the extraction " +
				'regex in validation-parity.test.js rather than skipping this check.',
		);
	}

	// PHP single-quoted strings only recognize two escapes: \\ -> \ and \' -> '.
	const unescaped = match[1].replace(/\\(['\\])/g, '$1');
	const delimiterMatch = unescaped.match(/^\/(.*)\/([a-z]*)$/s);

	if (!delimiterMatch) {
		throw new Error(
			`Parity guard could not parse PCRE delimiters for PHP constant "${name}": ${unescaped}`,
		);
	}

	return {
		source: delimiterMatch[1].replace(/\\\//g, '/'),
		flags: delimiterMatch[2],
	};
}

/**
 * Extract a `const NAME = /pattern/flags;` JS regex literal.
 *
 * A plain "find the next slash" regex is not safe here: the source contains
 * an unescaped `/` inside a character class (`+/=?`), which a naive scan
 * would misread as the closing delimiter. This walks the source character by
 * character, tracking character-class state, to find the true end of the
 * regex literal.
 *
 * @param {string} src  JS source text.
 * @param {string} name Constant name (e.g. "EMAIL_REGEX").
 * @return {{source: string, flags: string}} The pattern body and flags.
 */
function extractJsConstantRegex(src, name) {
	const anchor = `const ${name} =`;
	const anchorIndex = src.indexOf(anchor);

	if (-1 === anchorIndex) {
		throw new Error(
			`Parity guard could not find JS constant "${name}" in ${JS_VALIDATOR_PATH}. ` +
				'Either it was renamed/removed, or its declaration no longer matches ' +
				'the expected `const NAME = /.../;` shape. Update the extraction logic ' +
				'in validation-parity.test.js rather than skipping this check.',
		);
	}

	const afterAnchor = anchorIndex + anchor.length;
	const slashIndex = src.indexOf('/', afterAnchor);

	if (-1 === slashIndex || !/^\s*$/.test(src.slice(afterAnchor, slashIndex))) {
		throw new Error(
			`Parity guard could not locate the regex literal for JS constant "${name}".`,
		);
	}

	let cursor = slashIndex + 1;
	let inCharacterClass = false;

	while (cursor < src.length) {
		const character = src[cursor];

		if ('\\' === character) {
			cursor += 2;
			continue;
		}

		if ('[' === character) {
			inCharacterClass = true;
			cursor++;
			continue;
		}

		if (']' === character) {
			inCharacterClass = false;
			cursor++;
			continue;
		}

		if ('/' === character && !inCharacterClass) {
			break;
		}

		cursor++;
	}

	if (cursor >= src.length) {
		throw new Error(
			`Parity guard found an unterminated regex literal for JS constant "${name}".`,
		);
	}

	const source = src.slice(slashIndex + 1, cursor);
	const flagsMatch = src.slice(cursor + 1).match(/^[a-z]*/);

	return {
		source: source.replace(/\\\//g, '/'),
		flags: flagsMatch[0],
	};
}

/**
 * Extract the rule names registered in
 * `Validator::register_default_validators()`.
 *
 * @param {string} src PHP source text.
 * @return {string[]} Sorted, de-duplicated rule names.
 */
function extractPhpRuleNames(src) {
	const methodMatch = src.match(
		/protected function register_default_validators\(\): void \{([\s\S]*?)\n\t\}/,
	);

	if (!methodMatch) {
		throw new Error(
			'Parity guard could not find PHP method `register_default_validators()` in ' +
				`${PHP_VALIDATOR_PATH}. Update the extraction logic in validation-parity.test.js.`,
		);
	}

	const names = [...methodMatch[1].matchAll(/\$this->register\(\s*'([a-zA-Z0-9_]+)'/g)].map(
		(match) => match[1],
	);

	if (0 === names.length) {
		throw new Error(
			'Parity guard found `register_default_validators()` but no `$this->register(...)` calls inside it.',
		);
	}

	return [...new Set(names)].sort();
}

/**
 * Extract the rule names listed in the JS `validators` map.
 *
 * @param {string} src JS source text.
 * @return {string[]} Sorted, de-duplicated rule names.
 */
function extractJsRuleNames(src) {
	const mapMatch = src.match(/const validators = \{([\s\S]*?)\};/);

	if (!mapMatch) {
		throw new Error(
			`Parity guard could not find the JS \`validators\` map in ${JS_VALIDATOR_PATH}. ` +
				'Update the extraction logic in validation-parity.test.js.',
		);
	}

	const names = [...mapMatch[1].matchAll(/\b([a-zA-Z_$][a-zA-Z0-9_$]*)\b/g)].map(
		(match) => match[1],
	);

	if (0 === names.length) {
		throw new Error('Parity guard found the JS `validators` map but no rule names inside it.');
	}

	return [...new Set(names)].sort();
}

/**
 * Assert that a PHP-extracted regex and a JS-extracted regex are identical,
 * failing with both values named and shown when they are not.
 *
 * @param {string}                          constantName Name of the drifted constant, for the failure message.
 * @param {{source: string, flags: string}} php          Regex extracted from Validator.php.
 * @param {{source: string, flags: string}} js           Regex extracted from validation.js.
 */
function assertRegexParity(constantName, php, js) {
	if (php.source !== js.source || php.flags !== js.flags) {
		throw new Error(
			`${constantName} has drifted between Validator.php and validation.js.\n` +
				`  Validator.php:   /${php.source}/${php.flags}\n` +
				`  validation.js:   /${js.source}/${js.flags}`,
		);
	}

	expect(php).toEqual(js);
}

describe('PHP/JS validator parity', () => {
	it('keeps EMAIL_REGEX identical between Validator.php and validation.js', () => {
		assertRegexParity(
			'EMAIL_REGEX',
			extractPhpConstantRegex(phpSource, 'EMAIL_REGEX'),
			extractJsConstantRegex(jsSource, 'EMAIL_REGEX'),
		);
	});

	it('keeps URL_REGEX identical between Validator.php and validation.js', () => {
		assertRegexParity(
			'URL_REGEX',
			extractPhpConstantRegex(phpSource, 'URL_REGEX'),
			extractJsConstantRegex(jsSource, 'URL_REGEX'),
		);
	});

	it('keeps the registered rule name set identical between Validator.php and validation.js', () => {
		const phpRuleNames = extractPhpRuleNames(phpSource);
		const jsRuleNames = extractJsRuleNames(jsSource);

		if (phpRuleNames.join(',') !== jsRuleNames.join(',')) {
			throw new Error(
				'Registered validation rule names have drifted between Validator.php and validation.js.\n' +
					`  Validator.php:  [${phpRuleNames.join(', ')}]\n` +
					`  validation.js:  [${jsRuleNames.join(', ')}]`,
			);
		}

		expect(phpRuleNames).toEqual(jsRuleNames);
	});
});
