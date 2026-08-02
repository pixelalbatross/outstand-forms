/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { icon } from './icon';
import { defaultOptionBlocks } from '../../components/ChoiceOptions/default-options';

/**
 * The default variation, which exists to carry the starting options.
 *
 * A choice field with no options renders an empty control, so it has to start
 * with some. They are declared here rather than through an inner-blocks
 * `template` because a template is applied as a non-persistent change: it
 * redraws on every load, so the editor looks right while the saved block stays
 * empty. A variation's `innerBlocks` are part of the block the inserter
 * creates, so they are saved with it.
 */
const variations = [
	{
		name: 'default',
		title: __('Select', 'outstand-forms'),
		description: __('A dropdown the visitor picks one option from.', 'outstand-forms'),
		icon,
		isDefault: true,
		innerBlocks: defaultOptionBlocks(),
		scope: ['block', 'inserter'],
	},
];

export default variations;
