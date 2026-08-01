/* eslint-env jest */
/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Textarea from '.';

describe('Textarea', () => {
	it('renders the placeholder prompt when no placeholder is set', () => {
		render(<Textarea attributes={{ placeholder: '' }} setAttributes={() => {}} />);

		expect(screen.getByPlaceholderText('Optional placeholder…')).not.toBeNull();
	});

	it('renders the given value, rows and cols', () => {
		render(
			<Textarea
				attributes={{ placeholder: 'Tell us more', rows: 6, cols: 40 }}
				setAttributes={() => {}}
			/>,
		);

		const textarea = screen.getByRole('textbox');
		expect(textarea.value).toBe('Tell us more');
		expect(textarea.getAttribute('rows')).toBe('6');
		expect(textarea.getAttribute('cols')).toBe('40');
		expect(textarea.className).toBe('osf-field__textarea');
	});

	it('marks the field as required via aria-required', () => {
		render(
			<Textarea attributes={{ required: true, placeholder: '' }} setAttributes={() => {}} />,
		);

		expect(screen.getByRole('textbox').getAttribute('aria-required')).toBe('true');
	});

	it('calls setAttributes with the new placeholder on change', () => {
		const setAttributes = jest.fn();
		render(<Textarea attributes={{ placeholder: 'old' }} setAttributes={setAttributes} />);

		fireEvent.change(screen.getByRole('textbox'), { target: { value: 'new text' } });

		expect(setAttributes).toHaveBeenCalledWith({ placeholder: 'new text' });
	});
});
