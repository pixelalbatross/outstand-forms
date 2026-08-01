/* eslint-env jest */
/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Input from '.';

describe('Input', () => {
	it('renders the placeholder prompt when no placeholder is set', () => {
		render(<Input attributes={{ placeholder: '' }} setAttributes={() => {}} />);

		expect(screen.getByPlaceholderText('Optional placeholder…')).not.toBeNull();
	});

	it('renders the given type and value', () => {
		render(
			<Input
				type="email"
				attributes={{ placeholder: 'name@example.com' }}
				setAttributes={() => {}}
			/>,
		);

		const input = screen.getByRole('textbox');
		expect(input.getAttribute('type')).toBe('email');
		expect(input.value).toBe('name@example.com');
		expect(input.className).toBe('osf-field__input osf-field__input--email');
	});

	it('defaults to a text type', () => {
		render(<Input attributes={{ placeholder: '' }} setAttributes={() => {}} />);

		expect(screen.getByRole('textbox').getAttribute('type')).toBe('text');
	});

	it('marks the field as required via aria-required', () => {
		render(<Input attributes={{ required: true, placeholder: '' }} setAttributes={() => {}} />);

		expect(screen.getByRole('textbox').getAttribute('aria-required')).toBe('true');
	});

	it('calls setAttributes with the new placeholder on change', () => {
		const setAttributes = jest.fn();
		render(<Input attributes={{ placeholder: 'old' }} setAttributes={setAttributes} />);

		fireEvent.change(screen.getByRole('textbox'), { target: { value: 'new placeholder' } });

		expect(setAttributes).toHaveBeenCalledWith({ placeholder: 'new placeholder' });
	});
});
