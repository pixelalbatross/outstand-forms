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
import ActionMessageField from './ActionMessageField';

/**
 * `@wordpress/components` and `@wordpress/block-editor` can't be resolved to
 * their published builds in this test environment (they always resolve to
 * their untranspiled TypeScript source, which the CJS test runtime can't
 * parse), so both are mocked with minimal stand-ins for the primitives this
 * component actually uses.
 */
jest.mock('@wordpress/components', () => {
	// eslint-disable-next-line global-require
	const { forwardRef } = require('@wordpress/element');

	return {
		BaseControl: ({ id, label, help, children }) => (
			<div>
				<label htmlFor={id}>{label}</label>
				{children}
				{help && <span>{help}</span>}
			</div>
		),
		__experimentalHStack: forwardRef(({ children, onFocus }, ref) => (
			<div ref={ref} onFocus={onFocus}>
				{children}
			</div>
		)),
		Fill: ({ children }) => children,
		Slot: () => null,
	};
});

jest.mock('@wordpress/block-editor', () => ({
	RichText: jest.fn(({ value, onChange, placeholder }) => (
		<button type="button" onClick={() => onChange('edited message')}>
			{value || placeholder}
		</button>
	)),
	BlockControls: () => null,
}));

describe('ActionMessageField', () => {
	beforeEach(() => {
		RichText.mockClear();
	});

	it('renders the label and help text', () => {
		render(
			<ActionMessageField
				actionIndex={0}
				value=""
				onChange={() => {}}
				placeholder="Write a message"
				help="Some help text"
				isMessageSelected={false}
				setIsMessageSelected={() => {}}
				messageWrapperRef={{ current: null }}
			/>,
		);

		expect(screen.getByText('Message')).not.toBeNull();
		expect(screen.getByText('Some help text')).not.toBeNull();
	});

	it('shows the placeholder when the value is empty', () => {
		render(
			<ActionMessageField
				actionIndex={0}
				value=""
				onChange={() => {}}
				placeholder="Write a message"
				isMessageSelected={false}
				setIsMessageSelected={() => {}}
				messageWrapperRef={{ current: null }}
			/>,
		);

		expect(screen.getByText('Write a message')).not.toBeNull();
	});

	it('calls onChange with the new message', () => {
		const onChange = jest.fn();
		render(
			<ActionMessageField
				actionIndex={1}
				value="original"
				onChange={onChange}
				placeholder="Write a message"
				isMessageSelected={false}
				setIsMessageSelected={() => {}}
				messageWrapperRef={{ current: null }}
			/>,
		);

		fireEvent.click(screen.getByText('original'));

		expect(onChange).toHaveBeenCalledWith('edited message');
	});

	it('calls setIsMessageSelected when the wrapper receives focus', () => {
		const setIsMessageSelected = jest.fn();
		render(
			<ActionMessageField
				actionIndex={0}
				value="original"
				onChange={() => {}}
				placeholder="Write a message"
				isMessageSelected={false}
				setIsMessageSelected={setIsMessageSelected}
				messageWrapperRef={{ current: null }}
			/>,
		);

		fireEvent.focus(screen.getByText('original'));

		expect(setIsMessageSelected).toHaveBeenCalledWith(true);
	});
});
