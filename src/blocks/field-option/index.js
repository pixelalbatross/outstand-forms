/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import { icon } from './icon';

// An option describes a choice; it renders nothing of its own. The choice
// field collects its children server-side and draws the control.
registerBlockType(metadata.name, {
	edit,
	save: () => null,
	icon,
});
