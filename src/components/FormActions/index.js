/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * WordPress dependencies
 */
import {
	Button,
	ToggleControl,
	__experimentalHStack as HStack,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './style.css';
import FormActionModal from '../FormActionModal';
import { getFormActionIds } from '../../utils';

const { adminNotification, userNotification } = getFormActionIds();

export const ACTION_LABELS = {
	[adminNotification]: __('Admin Email', 'outstand-forms'),
	[userNotification]: __('User Email', 'outstand-forms'),
};

export default function FormActions({ actions, onUpdateActions, emailFieldOptions }) {
	const [editingIndex, setEditingIndex] = useState(null);

	const onToggle = (index, enabled) => {
		const updated = [...actions];
		updated[index] = { ...updated[index], enabled };
		onUpdateActions(updated);
	};

	const onUpdate = (index, key, value) => {
		const updated = [...actions];
		updated[index] = { ...updated[index], [key]: value };
		onUpdateActions(updated);
	};

	const editingAction = editingIndex !== null ? actions[editingIndex] : null;

	return (
		<>
			<VStack spacing={4}>
				{actions.map((action, index) => {
					const label = ACTION_LABELS[action.id] || action.id;
					return (
						<HStack
							key={action.id}
							justify="space-between"
							alignment="flex-start"
							spacing={4}
						>
							<ToggleControl
								label={label}
								checked={!!action.enabled}
								onChange={(value) => onToggle(index, value)}
								className="osf-action-list__toggle"
							/>
							{action.enabled && (
								<Button variant="link" onClick={() => setEditingIndex(index)}>
									{__('Edit', 'outstand-forms')}
								</Button>
							)}
						</HStack>
					);
				})}
			</VStack>

			{editingAction && (
				<FormActionModal
					action={editingAction}
					actionIndex={editingIndex}
					onClose={() => setEditingIndex(null)}
					onUpdate={onUpdate}
					emailFieldOptions={emailFieldOptions}
				/>
			)}
		</>
	);
}
