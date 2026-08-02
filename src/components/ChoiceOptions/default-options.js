/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * The options a choice field starts life with.
 *
 * Shaped as block-variation `innerBlocks` — `[ name, attributes ]` pairs — so
 * the inserter creates the field with its options already in place and they
 * are saved along with it.
 *
 * @return {Array} Option blocks for a variation's innerBlocks.
 */
export function defaultOptionBlocks() {
	return [
		['osf/field-option', { label: __('Option 1', 'outstand-forms') }],
		['osf/field-option', { label: __('Option 2', 'outstand-forms') }],
	];
}
