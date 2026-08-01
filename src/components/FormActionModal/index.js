/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * WordPress dependencies
 */
import { Modal, SlotFillProvider, __experimentalVStack as VStack } from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ACTION_LABELS } from '../FormActions';
import EmailActionFields from './EmailActionFields';
import { getFormActionIds } from '../../utils';
import './style.css';

export default function FormActionModal({
	action,
	actionIndex,
	onClose,
	onUpdate,
	emailFieldOptions,
}) {
	const { userNotification } = getFormActionIds();

	const actionId = action?.id || '';
	const title = ACTION_LABELS[actionId] || actionId;
	const isUserEmail = actionId === userNotification;

	const [isMessageSelected, setIsMessageSelected] = useState(false);
	const messageWrapperRef = useRef();

	useEffect(() => {
		if (!isMessageSelected) {
			return;
		}

		const ownerDocument = messageWrapperRef.current?.ownerDocument;
		if (!ownerDocument) {
			return;
		}

		const handleMouseDown = (event) => {
			const wrapper = messageWrapperRef.current;

			// Check if click is inside the RichText wrapper
			if (wrapper?.contains(event.target)) {
				return;
			}

			// Check if click is inside the toolbar or any popover (link input, etc.)
			const toolbar = ownerDocument.querySelector(
				'.block-editor-rich-text__inline-format-toolbar',
			);

			if (toolbar?.contains(event.target)) {
				return;
			}

			// Check if click is inside any popover (link URL input, etc.)
			const popover = event.target.closest('.components-popover');

			if (popover) {
				return;
			}

			setIsMessageSelected(false);
		};

		ownerDocument.addEventListener('mousedown', handleMouseDown);
		return () => ownerDocument.removeEventListener('mousedown', handleMouseDown);
	}, [isMessageSelected]);

	const messageProps = {
		action,
		actionIndex,
		onUpdate,
		isMessageSelected,
		setIsMessageSelected,
		messageWrapperRef,
	};

	return (
		<Modal title={title} onRequestClose={onClose} size="medium" className="osf-action-modal">
			<SlotFillProvider>
				<VStack spacing={4}>
					<EmailActionFields
						{...messageProps}
						isUserEmail={isUserEmail}
						emailFieldOptions={emailFieldOptions}
					/>
				</VStack>
			</SlotFillProvider>
		</Modal>
	);
}
