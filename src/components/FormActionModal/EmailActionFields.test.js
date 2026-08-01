/* eslint-env jest */
/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import EmailActionFields from './EmailActionFields';

const mockControlId = (label) => `mock-control-${label.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`;

// `@wordpress/components` can't be resolved to its published build in this
// test environment (it always resolves to its untranspiled TypeScript
// source, which the CJS test runtime can't parse), so it's mocked with
// minimal stand-ins for `SelectControl` and `TextControl`. `ActionMessageField`
// is mocked separately since it pulls in `RichText`/`BlockControls`, which
// aren't relevant to testing which fields render and how they're wired.
jest.mock('@wordpress/components', () => ({
	SelectControl: ({ label, value, options, onChange }) => (
		<label htmlFor={mockControlId(label)}>
			{label}
			<select
				id={mockControlId(label)}
				value={value}
				onChange={(event) => onChange(event.target.value)}
			>
				{options.map((option) => (
					<option key={option.value} value={option.value}>
						{option.label}
					</option>
				))}
			</select>
		</label>
	),
	TextControl: ({ label, value, onChange, placeholder }) => (
		<label htmlFor={mockControlId(label)}>
			{label}
			<input
				id={mockControlId(label)}
				value={value}
				placeholder={placeholder}
				onChange={(event) => onChange(event.target.value)}
			/>
		</label>
	),
}));

jest.mock('./ActionMessageField', () =>
	jest.fn(({ value, onChange }) => (
		<div>
			<span>message:{value}</span>
			<button type="button" onClick={() => onChange('edited message')}>
				edit message
			</button>
		</div>
	)),
);

describe('EmailActionFields', () => {
	const emailFieldOptions = [
		{ label: '— Select field —', value: '' },
		{ label: 'Email (#3)', value: '3' },
	];

	it('renders a "To Email" select for the user notification action', () => {
		render(
			<EmailActionFields
				action={{ toFieldId: '3' }}
				actionIndex={0}
				onUpdate={() => {}}
				isUserEmail
				emailFieldOptions={emailFieldOptions}
			/>,
		);

		expect(
			screen.getByText('To Email').closest('label').querySelector('select'),
		).not.toBeNull();
	});

	it('renders a "To Email" text field for the admin notification action', () => {
		render(
			<EmailActionFields
				action={{ to: '{admin_email}' }}
				actionIndex={0}
				onUpdate={() => {}}
				isUserEmail={false}
				emailFieldOptions={emailFieldOptions}
			/>,
		);

		expect(screen.getByPlaceholderText('e.g. {admin_email}')).not.toBeNull();
	});

	it('calls onUpdate when the recipient select changes', () => {
		const onUpdate = jest.fn();
		render(
			<EmailActionFields
				action={{ toFieldId: '' }}
				actionIndex={2}
				onUpdate={onUpdate}
				isUserEmail
				emailFieldOptions={emailFieldOptions}
			/>,
		);

		fireEvent.change(screen.getByText('To Email').closest('label').querySelector('select'), {
			target: { value: '3' },
		});

		expect(onUpdate).toHaveBeenCalledWith(2, 'toFieldId', '3');
	});

	it('merges the from name into the existing from object', () => {
		const onUpdate = jest.fn();
		render(
			<EmailActionFields
				action={{ from: { email: 'sender@example.com' } }}
				actionIndex={0}
				onUpdate={onUpdate}
				isUserEmail={false}
				emailFieldOptions={emailFieldOptions}
			/>,
		);

		fireEvent.change(screen.getByText('From Name').closest('label').querySelector('input'), {
			target: { value: 'ACME CORP' },
		});

		expect(onUpdate).toHaveBeenCalledWith(0, 'from', {
			email: 'sender@example.com',
			name: 'ACME CORP',
		});
	});

	it('merges the from email into the existing from object', () => {
		const onUpdate = jest.fn();
		render(
			<EmailActionFields
				action={{ from: { name: 'ACME CORP' } }}
				actionIndex={0}
				onUpdate={onUpdate}
				isUserEmail={false}
				emailFieldOptions={emailFieldOptions}
			/>,
		);

		fireEvent.change(screen.getByText('From Email').closest('label').querySelector('input'), {
			target: { value: 'sender@example.com' },
		});

		expect(onUpdate).toHaveBeenCalledWith(0, 'from', {
			name: 'ACME CORP',
			email: 'sender@example.com',
		});
	});

	it('passes the message through to ActionMessageField and forwards its onChange', () => {
		const onUpdate = jest.fn();
		render(
			<EmailActionFields
				action={{ message: 'hello there' }}
				actionIndex={5}
				onUpdate={onUpdate}
				isUserEmail={false}
				emailFieldOptions={emailFieldOptions}
			/>,
		);

		expect(screen.getByText('message:hello there')).not.toBeNull();

		fireEvent.click(screen.getByText('edit message'));

		expect(onUpdate).toHaveBeenCalledWith(5, 'message', 'edited message');
	});
});
