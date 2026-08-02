/**
 * The block inserted when the user adds a field without picking a type.
 *
 * Editorial, not derived: it names the starting field, the same way the form
 * templates do.
 */
export const DEFAULT_BLOCK = {
	name: 'osf/field-input',
	attributes: {
		type: 'text',
	},
};

/**
 * The order field types are offered in, most-used first.
 *
 * Editorial, and the whole point of `prioritizedInserterBlocks`: these are
 * pushed to the top of the inserter in this order. A type missing from this
 * list is still offered, after these, in registration order — so a type
 * registered through `outstand_forms_field_factory` needs no edit here.
 */
export const PRIORITIZED_FIELD_TYPES = [
	'text',
	'email',
	'tel',
	'url',
	'number',
	'password',
	'textarea',
	'select',
	'radio',
	'checkbox',
	'consent',
];

/**
 * Blocks allowed inside the fields wrapper that are not form fields.
 *
 * The field blocks themselves are deliberately absent: they come from
 * `getFieldBlockNames()`, so a field block the server knows about is
 * insertable without this list being edited.
 */
export const CORE_ALLOWED_BLOCKS = [
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
];
