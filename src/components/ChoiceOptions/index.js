/* eslint-disable import/no-extraneous-dependencies */
/**
 * External dependencies
 */
import clsx from 'clsx';

/**
 * WordPress dependencies
 */
import { useInnerBlocksProps, store as blockEditorStore } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * The option children of a choice field, rendered as the field's control.
 *
 * Options are blocks rather than an attribute, so the editor gets reordering,
 * duplication and copy/paste for free and the server has one place to read
 * them from.
 *
 * The starting pair comes from each block's default variation rather than an
 * inner-blocks `template`. A template is applied as a non-persistent change: it
 * redraws on every load, so the editor looks correct while the saved block
 * stays empty and the front end renders a control with no choices in it. A
 * variation's `innerBlocks` are part of the block the inserter creates, so they
 * are saved with it.
 *
 * A field that ends up with no options — emptied by hand, or saved before the
 * block had a default — gets one back automatically, because a choice field
 * with nothing to choose from renders an empty control on the front end.
 *
 * @param {Object} props          Component props.
 * @param {string} props.display  How the options will render on the front end.
 * @param {string} props.clientId The field block's client ID. Passed in rather
 *                                than read from block context, which does not
 *                                resolve to this block here — and a missing
 *                                client ID makes `getBlockCount` count the
 *                                root blocks instead, which is never zero.
 * @return {Element} The options list.
 */
export default function ChoiceOptions({ display = 'select', clientId }) {
	const hasOptions = useSelect(
		(select) => select(blockEditorStore).getBlockCount(clientId) > 0,
		[clientId],
	);

	const { insertBlocks } = useDispatch(blockEditorStore);

	useEffect(() => {
		if (hasOptions) {
			return undefined;
		}

		// Deferred by a tick: on the first render of a post the editor is still
		// settling its own blocks, and an insert dispatched inside that pass is
		// discarded. The timeout is cleared if the field gains an option in the
		// meantime, so a paste or an undo is never doubled up.
		const timer = setTimeout(() => {
			insertBlocks(
				[createBlock('osf/field-option', { label: __('Option 1', 'outstand-forms') })],
				undefined,
				clientId,
				false,
			);
		}, 0);

		return () => clearTimeout(timer);
	}, [hasOptions, clientId, insertBlocks]);

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: clsx('osf-field__choices', `osf-field__choices--${display}`),
		},
		{
			allowedBlocks: ['osf/field-option'],
			templateLock: false,
		},
	);

	return <div {...innerBlocksProps} />;
}
