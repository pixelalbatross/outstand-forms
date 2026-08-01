/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { findBlocks, isFieldBlock, resolveFieldName } from '../utils';

/**
 * Checks if another field in the same form resolves to the same submission name.
 *
 * Field values are keyed by `name` in both the frontend form state and the
 * server-side field configs, so duplicate names silently drop all but one value.
 *
 * @param {string} clientId   Client ID of the field block to check.
 * @param {Object} attributes Attributes of the field block to check.
 * @return {boolean} True if another field uses the same name.
 */
export function useIsDuplicateFieldName(clientId, attributes) {
	const { name, fieldId } = attributes;

	return useSelect(
		(select) => {
			const { getBlockParentsByBlockName, getBlocks } = select(blockEditorStore);

			const [formClientId] = getBlockParentsByBlockName(clientId, 'osf/form');
			if (!formClientId) {
				return false;
			}

			const fieldBlocks = findBlocks(isFieldBlock, getBlocks(formClientId));
			const ownName = resolveFieldName({ name, fieldId });

			return fieldBlocks.some(
				(block) =>
					block.clientId !== clientId && resolveFieldName(block.attributes) === ownName,
			);
		},
		[clientId, name, fieldId],
	);
}
