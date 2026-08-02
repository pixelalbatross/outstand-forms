/**
 * The value a ticked consent box submits.
 *
 * Mirrors `Components\Checkbox::CHECKED_VALUE`; the two must agree or a box
 * ticked in the browser would submit something the server's allowlist rejects.
 *
 * @type {string}
 */
export const CHECKED_VALUE = '1';

/**
 * Allowed formats for the help text field.
 *
 * @type {Array}
 */
export const HELP_TEXT_ALLOWED_FORMATS = [
	'core/bold',
	'core/italic',
	'core/strikethrough',
	'core/text-color',
	'core/subscript',
	'core/superscript',
	'core/underline',
];
