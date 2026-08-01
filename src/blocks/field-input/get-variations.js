/**
 * Build a single block variation for a field type.
 *
 * @param {string}  type        Field type.
 * @param {?Object} known       Editorial metadata for the type (title, icon, keywords, attributes, isDefault), if any.
 * @param {?string} label       Registry-provided label, used when there's no editorial metadata.
 * @param {*}       genericIcon Icon used when there's no editorial metadata.
 * @return {Object} The block variation.
 */
function toVariation(type, known, label, genericIcon) {
	const variation = {
		name: type,
		title: known?.title ?? label ?? type,
		icon: known?.icon ?? genericIcon,
		keywords: known?.keywords ?? [type],
		attributes: { type, ...(known?.attributes ?? {}) },
		scope: ['block', 'inserter', 'transform'],
	};

	if (known?.isDefault) {
		variation.isDefault = true;
	}

	return variation;
}

/**
 * Build the field-input block variations from the field type registry.
 *
 * Only field types whose editor control is `input` are included — types
 * rendered by a dedicated control (e.g. `textarea`, which has its own
 * `osf/field-textarea` block) are deliberately left out, since this block
 * only knows how to render an `<Input>`. Known types (present in
 * `fieldTypeMetadata`) keep their editorial title, icon, keywords and
 * ordering; anything else is appended afterwards with a generic title/icon.
 *
 * Pure and framework-free on purpose: the editorial metadata (translated
 * strings, icon elements) is assembled by the caller and passed in, so this
 * function can be unit tested without a `@wordpress/i18n` or
 * `@wordpress/icons` runtime.
 *
 * @param {Array<{type: string, label: string, control: string}>} fieldTypes        Registered field types.
 * @param {Object}                                                fieldTypeMetadata Editorial metadata for known types, keyed by type.
 * @param {*}                                                     genericIcon       Icon used for a type with no editorial metadata.
 * @return {Array} Block variations.
 */
export function buildFieldInputVariations(
	fieldTypes = [],
	fieldTypeMetadata = {},
	genericIcon = null,
) {
	const inputTypes = fieldTypes.filter(
		(fieldType) => (fieldType?.control ?? 'input') === 'input',
	);
	const registeredTypeNames = new Set(inputTypes.map((fieldType) => fieldType.type));

	const known = Object.keys(fieldTypeMetadata)
		.filter((type) => registeredTypeNames.has(type))
		.map((type) => toVariation(type, fieldTypeMetadata[type], null, genericIcon));

	const unknown = inputTypes
		.filter((fieldType) => !fieldTypeMetadata[fieldType.type])
		.map((fieldType) => toVariation(fieldType.type, null, fieldType.label, genericIcon));

	return [...known, ...unknown];
}
