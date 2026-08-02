/* eslint-env jest */
/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Field from '.';

/**
 * `@wordpress/components` and `@wordpress/block-editor` can't be resolved to
 * their published builds in this test environment (they always resolve to
 * their untranspiled TypeScript source, which the CJS test runtime can't
 * parse). `Field` renders `Label` and `HelpText` directly (both of which use
 * `RichText`), so `@wordpress/block-editor` needs mocking here too, not just
 * `@wordpress/components`.
 */
jest.mock('@wordpress/components', () => ({
	Notice: ({ children }) => <div role="alert">{children}</div>,
}));

jest.mock('@wordpress/block-editor', () => ({
	RichText: ({ tagName: Tag = 'div', value, className, 'aria-label': ariaLabel }) => (
		<Tag className={className} aria-label={ariaLabel}>
			{value}
		</Tag>
	),
}));

describe('Field', () => {
	const context = {
		'osf/labelPosition': 'top',
		'osf/helpTextPosition': 'bottom',
	};

	afterEach(() => {
		delete global.osfSettings;
	});

	it('renders an unknown-type notice when the type has no registered control', () => {
		global.osfSettings = { fieldTypes: [] };

		render(
			<Field
				type="mystery"
				attributes={{ label: 'X', helpText: '' }}
				setAttributes={() => {}}
				context={context}
			/>,
		);

		expect(screen.getByRole('alert').textContent).toBe(
			'Unknown field type "mystery". Is the plugin that registers it active?',
		);
	});

	it('renders the input control for an "input"-controlled type', () => {
		global.osfSettings = { fieldTypes: [{ type: 'text', label: 'Text', control: 'input' }] };

		render(
			<Field
				type="text"
				attributes={{ label: 'Name', helpText: '' }}
				setAttributes={() => {}}
				context={context}
			/>,
		);

		expect(screen.queryByRole('alert')).toBeNull();
		expect(screen.getByRole('textbox')).not.toBeNull();
	});

	it('renders the textarea control for a "textarea"-controlled type', () => {
		global.osfSettings = {
			fieldTypes: [{ type: 'message', label: 'Message', control: 'textarea' }],
		};

		render(
			<Field
				type="message"
				attributes={{ label: 'Message', helpText: '' }}
				setAttributes={() => {}}
				context={context}
			/>,
		);

		expect(screen.getByRole('textbox').tagName).toBe('TEXTAREA');
	});

	it('shows the field id badge when showFieldId is true and a fieldId is set', () => {
		global.osfSettings = { fieldTypes: [{ type: 'text', label: 'Text', control: 'input' }] };

		render(
			<Field
				type="text"
				attributes={{ label: 'Name', helpText: '', fieldId: 42 }}
				setAttributes={() => {}}
				context={context}
				showFieldId
			/>,
		);

		expect(screen.getByText('ID: 42')).not.toBeNull();
	});

	it('hides the field id badge when showFieldId is false', () => {
		global.osfSettings = { fieldTypes: [{ type: 'text', label: 'Text', control: 'input' }] };

		render(
			<Field
				type="text"
				attributes={{ label: 'Name', helpText: '', fieldId: 42 }}
				setAttributes={() => {}}
				context={context}
			/>,
		);

		expect(screen.queryByText('ID: 42')).toBeNull();
	});

	it('hides the field id badge when there is no fieldId yet, even if requested', () => {
		global.osfSettings = { fieldTypes: [{ type: 'text', label: 'Text', control: 'input' }] };

		render(
			<Field
				type="text"
				attributes={{ label: 'Name', helpText: '' }}
				setAttributes={() => {}}
				context={context}
				showFieldId
			/>,
		);

		expect(screen.queryByText(/^ID:/)).toBeNull();
	});

	it('renders the label after the field when the label position is "right"', () => {
		global.osfSettings = {
			fieldTypes: [{ type: 'text', label: 'Text', control: 'input' }],
			inlineLabelPositions: ['right'],
		};

		const { container } = render(
			<Field
				type="text"
				attributes={{ label: 'Name', helpText: '', labelPosition: 'right' }}
				setAttributes={() => {}}
				context={context}
			/>,
		);

		// An inline label pairs with its control in a row of its own, so that
		// the help-text placeholder cannot size the row and push the two apart.
		const row = container.querySelector('.osf-field__row');

		expect(row).not.toBeNull();

		const children = Array.from(row.children);
		const inputWrapperIndex = children.findIndex((el) =>
			el.classList.contains('osf-field__wrapper'),
		);
		const labelIndex = children.findIndex((el) => el.classList.contains('osf-field__label'));

		expect(inputWrapperIndex).toBeGreaterThanOrEqual(0);
		expect(labelIndex).toBeGreaterThan(inputWrapperIndex);
	});

	it('falls back to the context label and help text positions when the attributes are unset', () => {
		global.osfSettings = { fieldTypes: [{ type: 'text', label: 'Text', control: 'input' }] };

		render(
			<Field
				type="text"
				attributes={{ label: 'Name', helpText: 'Some help' }}
				setAttributes={() => {}}
				context={{ 'osf/labelPosition': 'top', 'osf/helpTextPosition': 'bottom' }}
			/>,
		);

		// helpTextPosition "bottom" from context, not inline, so help text
		// renders as a sibling after the field rather than wrapped with it.
		expect(screen.getByText('Some help').className).toBe('osf-field__help-text');
	});
});
