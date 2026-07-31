/**
 * WordPress dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { DEFAULT_BLOCK, PRIORITIZED_INSERTER_BLOCKS } from './constants';

export default function FormFieldsEdit() {
	const blockProps = useBlockProps({
		className: 'osf-form__fields',
	});

	const allowedBlocks = applyFilters('outstandForms.form.allowedBlocks', [
		'core/button',
		'core/buttons',
		'core/column',
		'core/columns',
		'core/cover',
		'core/embed',
		'core/gallery',
		'core/group',
		'core/heading',
		'core/image',
		'core/list-item',
		'core/list',
		'core/media-text',
		'core/paragraph',
		'core/separator',
		'core/spacer',
		'core/table',
		'osf/field-input',
		'osf/field-textarea',
	]);

	const innerBlocksProps = useInnerBlocksProps(blockProps, {
		__experimentalCaptureToolbars: true,
		templateLock: false,
		allowedBlocks,
		prioritizedInserterBlocks: PRIORITIZED_INSERTER_BLOCKS,
		defaultBlock: DEFAULT_BLOCK,
	});

	return <div {...innerBlocksProps} />;
}
