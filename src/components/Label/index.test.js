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
import Label from '.';

/**
 * See the note in HelpText/index.test.js: `@wordpress/block-editor` can't be
 * resolved to its published build in this test environment, so it's mocked
 * with a minimal stand-in for `RichText`.
 */
jest.mock('@wordpress/block-editor', () => ({
	RichText: jest.fn(
		({ tagName: Tag = 'label', value, onChange, 'aria-label': ariaLabel, placeholder }) => (
			<Tag
				aria-label={ariaLabel}
				placeholder={placeholder}
				onClick={() => onChange('edited')}
			>
				{value}
			</Tag>
		),
	),
}));

describe('Label', () => {
	beforeEach(() => {
		RichText.mockClear();
	});

	it('renders the label text', () => {
		render(
			<Label
				attributes={{ label: 'Full name', required: false }}
				setAttributes={() => {}}
				context={{}}
			/>,
		);

		expect(screen.getByText('Full name')).not.toBeNull();
	});

	it('uses an "Empty label" aria-label when the label is blank', () => {
		render(
			<Label
				attributes={{ label: '', required: false }}
				setAttributes={() => {}}
				context={{}}
			/>,
		);

		expect(RichText).toHaveBeenCalledWith(
			expect.objectContaining({ 'aria-label': 'Empty label' }),
			{},
		);
	});

	it('uses a "Label" aria-label once text is present', () => {
		render(
			<Label
				attributes={{ label: 'Full name', required: false }}
				setAttributes={() => {}}
				context={{}}
			/>,
		);

		expect(RichText).toHaveBeenCalledWith(
			expect.objectContaining({ 'aria-label': 'Label' }),
			{},
		);
	});

	it('renders the required indicator when required and the context provides one', () => {
		render(
			<Label
				attributes={{ label: 'Full name', required: true }}
				setAttributes={() => {}}
				context={{ 'osf/requiredIndicator': '*' }}
			/>,
		);

		expect(screen.getByText('*')).not.toBeNull();
	});

	it('omits the required indicator when the field is not required', () => {
		render(
			<Label
				attributes={{ label: 'Full name', required: false }}
				setAttributes={() => {}}
				context={{ 'osf/requiredIndicator': '*' }}
			/>,
		);

		expect(screen.queryByText('*')).toBeNull();
	});

	it('omits the required indicator when the context provides none', () => {
		render(
			<Label
				attributes={{ label: 'Full name', required: true }}
				setAttributes={() => {}}
				context={{}}
			/>,
		);

		expect(screen.queryByText('*')).toBeNull();
	});

	it('calls setAttributes with the new label on change', () => {
		const setAttributes = jest.fn();
		render(
			<Label
				attributes={{ label: 'old', required: false }}
				setAttributes={setAttributes}
				context={{}}
			/>,
		);

		fireEvent.click(screen.getByText('old'));

		expect(setAttributes).toHaveBeenCalledWith({ label: 'edited' });
	});
});
