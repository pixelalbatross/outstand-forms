/* eslint-env jest */
/**
 * Internal dependencies
 */
import { HELP_TEXT_ALLOWED_FORMATS } from './constants';

describe('HELP_TEXT_ALLOWED_FORMATS', () => {
	it('lists the allowed RichText formats for help text', () => {
		expect(HELP_TEXT_ALLOWED_FORMATS).toEqual([
			'core/bold',
			'core/italic',
			'core/strikethrough',
			'core/text-color',
			'core/subscript',
			'core/superscript',
			'core/underline',
		]);
	});
});
