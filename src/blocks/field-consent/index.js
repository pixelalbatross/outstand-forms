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

registerBlockType(metadata.name, {
	edit,
	save: () => null,
	icon,
});
