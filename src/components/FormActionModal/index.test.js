/* eslint-env jest */
/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * `@wordpress/components` can't be resolved to its published build in this
 * test environment (it always resolves to its untranspiled TypeScript
 * source, which the CJS test runtime can't parse), so it's mocked with
 * minimal stand-ins for the primitives this component uses directly. The
 * heavier field rendering (RichText, SelectControl, TextControl, …) is
 * already covered by ActionMessageField.test.js and
 * EmailActionFields.test.js, so `EmailActionFields` is mocked here to keep
 * this file focused on FormActionModal's own title/close logic.
 */
jest.mock('@wordpress/components', () => ({
	Modal: ({ title, onRequestClose, children }) => (
		<div role="dialog" aria-label={title}>
			<button type="button" onClick={onRequestClose}>
				close modal
			</button>
			{children}
		</div>
	),
	SlotFillProvider: ({ children }) => children,
	__experimentalVStack: ({ children }) => <div>{children}</div>,
}));

jest.mock('./EmailActionFields', () =>
	jest.fn(({ isUserEmail }) => <div>email fields ({isUserEmail ? 'user' : 'admin'})</div>),
);

describe('FormActionModal', () => {
	function loadFormActionModal() {
		global.osfSettings = {
			formActionIds: {
				adminNotification: 'admin_notification',
				userNotification: 'user_notification',
			},
		};

		// See FormActions/index.test.js for why a plain (non-isolated)
		// `require` is used instead of `jest.isolateModules`.
		// eslint-disable-next-line global-require
		return require('.').default;
	}

	afterEach(() => {
		delete global.osfSettings;
	});

	it('titles the modal using the known label for the action', () => {
		const FormActionModal = loadFormActionModal();

		render(
			<FormActionModal
				action={{ id: 'admin_notification' }}
				actionIndex={0}
				onClose={() => {}}
				onUpdate={() => {}}
				emailFieldOptions={[]}
			/>,
		);

		expect(screen.getByRole('dialog', { name: 'Admin Email' })).not.toBeNull();
	});

	it('falls back to the raw action id when no label is known', () => {
		const FormActionModal = loadFormActionModal();

		render(
			<FormActionModal
				action={{ id: 'unknown_action' }}
				actionIndex={0}
				onClose={() => {}}
				onUpdate={() => {}}
				emailFieldOptions={[]}
			/>,
		);

		expect(screen.getByRole('dialog', { name: 'unknown_action' })).not.toBeNull();
	});

	it('treats a missing action id as an empty string title', () => {
		const FormActionModal = loadFormActionModal();

		render(
			<FormActionModal
				action={{}}
				actionIndex={0}
				onClose={() => {}}
				onUpdate={() => {}}
				emailFieldOptions={[]}
			/>,
		);

		expect(screen.getByRole('dialog', { name: '' })).not.toBeNull();
	});

	it('flags the user notification action as a user email', () => {
		const FormActionModal = loadFormActionModal();

		render(
			<FormActionModal
				action={{ id: 'user_notification' }}
				actionIndex={0}
				onClose={() => {}}
				onUpdate={() => {}}
				emailFieldOptions={[]}
			/>,
		);

		expect(screen.getByText('email fields (user)')).not.toBeNull();
	});

	it('flags the admin notification action as not a user email', () => {
		const FormActionModal = loadFormActionModal();

		render(
			<FormActionModal
				action={{ id: 'admin_notification' }}
				actionIndex={0}
				onClose={() => {}}
				onUpdate={() => {}}
				emailFieldOptions={[]}
			/>,
		);

		expect(screen.getByText('email fields (admin)')).not.toBeNull();
	});

	it('calls onClose when the modal requests close', () => {
		const FormActionModal = loadFormActionModal();
		const onClose = jest.fn();

		render(
			<FormActionModal
				action={{ id: 'admin_notification' }}
				actionIndex={0}
				onClose={onClose}
				onUpdate={() => {}}
				emailFieldOptions={[]}
			/>,
		);

		fireEvent.click(screen.getByText('close modal'));

		expect(onClose).toHaveBeenCalled();
	});
});
