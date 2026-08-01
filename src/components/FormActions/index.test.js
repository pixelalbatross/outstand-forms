/* eslint-env jest */
/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * `@wordpress/components` can't be resolved to its published build in this
 * test environment (it always resolves to its untranspiled TypeScript
 * source, which the CJS test runtime can't parse), so it's mocked with
 * minimal stand-ins for the handful of primitives FormActions actually uses.
 * `FormActionModal` is mocked separately below since it pulls in RichText
 * and a much larger slice of `@wordpress/components` that isn't relevant to
 * testing FormActions' own toggle/edit-link logic.
 */
jest.mock('@wordpress/components', () => ({
	Button: jest.fn(({ children, onClick, variant }) => (
		<button type="button" data-variant={variant} onClick={onClick}>
			{children}
		</button>
	)),
	ToggleControl: jest.fn(({ label, checked, onChange, className }) => (
		<label className={className} htmlFor="mock-toggle-control">
			{label}
			<input
				id="mock-toggle-control"
				type="checkbox"
				checked={checked}
				onChange={(event) => onChange(event.target.checked)}
			/>
		</label>
	)),
	__experimentalHStack: ({ children }) => <div>{children}</div>,
	__experimentalVStack: ({ children }) => <div>{children}</div>,
}));

jest.mock('../FormActionModal', () =>
	jest.fn(({ action, onClose }) => (
		<div data-testid="form-action-modal">
			<span>{action.id}</span>
			<button type="button" onClick={onClose}>
				close
			</button>
		</div>
	)),
);

describe('FormActions', () => {
	// `ACTION_LABELS` and `getFormActionIds()` are both evaluated at
	// module-load time from `osfSettings`, so `osfSettings` has to be set
	// before the module is required, not merely before render.
	function loadFormActions() {
		global.osfSettings = {
			formActionIds: {
				adminNotification: 'admin_notification',
				userNotification: 'user_notification',
			},
		};

		// A plain (non-isolated) `require` re-uses this file's existing
		// module registry, so `react` stays a single instance. Using
		// `jest.isolateModules` here would give the freshly required
		// `FormActions` its own copy of `react`, breaking hooks.
		// eslint-disable-next-line global-require
		return require('.').default;
	}

	afterEach(() => {
		delete global.osfSettings;
	});

	it('renders a toggle for each action, using its label', () => {
		const FormActions = loadFormActions();

		render(
			<FormActions
				actions={[
					{ id: 'admin_notification', enabled: false },
					{ id: 'user_notification', enabled: false },
				]}
				onUpdateActions={() => {}}
				emailFieldOptions={[]}
			/>,
		);

		expect(screen.getByText('Admin Email')).not.toBeNull();
		expect(screen.getByText('User Email')).not.toBeNull();
	});

	it('falls back to the raw id when no label is known', () => {
		const FormActions = loadFormActions();

		render(
			<FormActions
				actions={[{ id: 'unknown_action', enabled: false }]}
				onUpdateActions={() => {}}
				emailFieldOptions={[]}
			/>,
		);

		expect(screen.getByText('unknown_action')).not.toBeNull();
	});

	it('calls onUpdateActions with the toggled action enabled', () => {
		const FormActions = loadFormActions();
		const onUpdateActions = jest.fn();

		render(
			<FormActions
				actions={[{ id: 'admin_notification', enabled: false }]}
				onUpdateActions={onUpdateActions}
				emailFieldOptions={[]}
			/>,
		);

		fireEvent.click(screen.getByRole('checkbox'));

		expect(onUpdateActions).toHaveBeenCalledWith([{ id: 'admin_notification', enabled: true }]);
	});

	it('only shows an Edit link for enabled actions', () => {
		const FormActions = loadFormActions();

		render(
			<FormActions
				actions={[
					{ id: 'admin_notification', enabled: true },
					{ id: 'user_notification', enabled: false },
				]}
				onUpdateActions={() => {}}
				emailFieldOptions={[]}
			/>,
		);

		expect(screen.getAllByText('Edit')).toHaveLength(1);
	});

	it('opens the modal for the clicked action and closes it again', () => {
		const FormActions = loadFormActions();

		render(
			<FormActions
				actions={[{ id: 'admin_notification', enabled: true }]}
				onUpdateActions={() => {}}
				emailFieldOptions={[]}
			/>,
		);

		expect(screen.queryByTestId('form-action-modal')).toBeNull();

		fireEvent.click(screen.getByText('Edit'));

		expect(screen.getByTestId('form-action-modal')).not.toBeNull();

		fireEvent.click(screen.getByText('close'));

		expect(screen.queryByTestId('form-action-modal')).toBeNull();
	});
});
