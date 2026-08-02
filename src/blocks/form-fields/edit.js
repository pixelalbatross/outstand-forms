/**
 * WordPress dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { CORE_ALLOWED_BLOCKS, DEFAULT_BLOCK, PRIORITIZED_FIELD_TYPES } from './constants';
import { getFieldBlockNames, getPrioritizedInserterBlocks } from '../../utils';

export default function FormFieldsEdit() {
	const blockProps = useBlockProps();

	const allowedBlocks = applyFilters('outstandForms.form.allowedBlocks', [
		...CORE_ALLOWED_BLOCKS,
		...getFieldBlockNames(),
	]);

	const innerBlocksProps = useInnerBlocksProps(blockProps, {
		__experimentalCaptureToolbars: true,
		templateLock: false,
		allowedBlocks,
		prioritizedInserterBlocks: getPrioritizedInserterBlocks(PRIORITIZED_FIELD_TYPES),
		defaultBlock: DEFAULT_BLOCK,
	});

	return <div {...innerBlocksProps} />;
}
