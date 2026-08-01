/* eslint-env jest */
/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * WordPress dependencies
 */
// eslint-disable-next-line import/no-extraneous-dependencies
import { RichText } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import HelpText from '.';

/**
 * `@wordpress/block-editor` cannot be resolved to its published build in this
 * test environment (it always resolves to its untranspiled TypeScript
 * source, which the CJS test runtime can't parse), so it's mocked here with
 * just enough of `RichText`'s contract — `tagName`, `value`, `onChange`,
 * `placeholder`, `aria-label`, `className` — to exercise HelpText's own
 * wiring rather than RichText's internals.
 */
jest.mock('@wordpress/block-editor', () => ({
	RichText: jest.fn(
		({
			tagName: Tag = 'div',
			value,
			onChange,
			placeholder,
			className,
			'aria-label': ariaLabel,
		}) => (
			<Tag
				className={className}
				placeholder={placeholder}
				aria-label={ariaLabel}
				onClick={() => onChange('edited')}
			>
				{value}
			</Tag>
		),
	),
}));

describe('HelpText', () => {
	beforeEach(() => {
		RichText.mockClear();
	});

	it('renders the current help text', () => {
		render(<HelpText attributes={{ helpText: 'Some help' }} setAttributes={() => {}} />);

		expect(screen.getByText('Some help')).not.toBeNull();
	});

	it('passes the placeholder and aria-label through to RichText', () => {
		render(<HelpText attributes={{ helpText: '' }} setAttributes={() => {}} />);

		expect(RichText).toHaveBeenCalledWith(
			expect.objectContaining({
				tagName: 'div',
				value: '',
				placeholder: 'Add help text',
				'aria-label': 'Optional help text…',
				className: 'osf-field__help-text',
			}),
			{},
		);
	});

	it('calls setAttributes with the new help text on change', () => {
		const setAttributes = jest.fn();
		render(<HelpText attributes={{ helpText: 'old' }} setAttributes={setAttributes} />);

		fireEvent.click(screen.getByText('old'));

		expect(setAttributes).toHaveBeenCalledWith({ helpText: 'edited' });
	});
});
