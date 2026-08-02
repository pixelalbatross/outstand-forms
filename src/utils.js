/* global osfSettings */
/* eslint-disable import/no-extraneous-dependencies */
/**
 * External dependencies
 */
import { v4 as uuidv4 } from 'uuid';

/**
 * Get a unique block ID.
 *
 * @param {number} length The length of the block ID. Default is 9.
 * @return {string} A unique block ID.
 */
export function getBlockId(length = 9) {
	return uuidv4().replace(/-/g, '').slice(-length);
}

/**
 * Get the blocks the server treats as form fields.
 *
 * `FormBlockParser::FIELD_BLOCK_NAMES` is the only definition of this list;
 * it reaches the editor as `osfSettings.fieldBlockNames`. There is
 * deliberately no JS fallback list — a stale mirror would silently disagree
 * with the server about what gets validated and submitted.
 *
 * @return {Array<string>} Field block names, or an empty array if none were localized.
 */
export function getFieldBlockNames() {
	const fieldBlockNames =
		typeof osfSettings !== 'undefined' ? osfSettings?.fieldBlockNames : undefined;

	return Array.isArray(fieldBlockNames) ? fieldBlockNames : [];
}

/**
 * Checks whether a block represents a form field.
 *
 * @param {Object} block The block to check.
 * @return {boolean} True if the block is a form field block.
 */
export function isFieldBlock(block) {
	return getFieldBlockNames().includes(block?.name);
}

/**
 * Build the inserter priority list for the fields wrapper.
 *
 * Derived from the field type registry so a type registered through
 * `outstand_forms_field_factory` reaches the inserter without a second list
 * being edited. A type rendered by the `input` control is offered as the
 * `osf/field-input` variation that `buildFieldInputVariations()` generates
 * for it; anything else is offered as its own block.
 *
 * The block that inserts a type is `osf/field-{control}` by convention. That
 * guess is checked against `getFieldBlockNames()`, so a type whose block does
 * not follow it is left out of the priority list rather than named wrongly —
 * it stays insertable through the regular inserter. If a block ever breaks the
 * convention, give the registry an explicit block name and read it here.
 *
 * The list is ordered, not just filtered: the inserter shows these first, in
 * the order given. `order` carries that editorial ranking, since the registry
 * knows which types exist but not which ones an author reaches for most.
 * Registry order alone would rank alphabetically and bury the text field.
 *
 * @param {Array<string>} order Field types in the order they should be offered.
 * @return {Array<string>} Block and variation names, ranked.
 */
export function getPrioritizedInserterBlocks(order = []) {
	const fieldBlockNames = getFieldBlockNames();

	const entries = getFieldTypes()
		.map((fieldType) => {
			const control = fieldType?.control ?? 'input';
			const block = `osf/field-${control}`;

			if (!fieldBlockNames.includes(block)) {
				return undefined;
			}

			const rank = order.indexOf(fieldType?.type);

			return {
				name: control === 'input' ? `${block}/${fieldType.type}` : block,
				// A type the ranking doesn't mention sorts after every one it
				// does, keeping its registration order among its peers.
				rank: rank === -1 ? Number.MAX_SAFE_INTEGER : rank,
			};
		})
		.filter(Boolean);

	entries.sort((a, b) => a.rank - b.rank);

	return [...new Set(entries.map((entry) => entry.name))];
}

/**
 * Get the field types registered on the server.
 *
 * `FieldFactory::get_registered_types()` is the only definition of which
 * field types exist and what renders them; it reaches the editor as
 * `osfSettings.fieldTypes`. There is deliberately no JS fallback list — see
 * `isFieldBlock()` above for why.
 *
 * @return {Array<{type: string, label: string, control: string}>} Registered field types, or an empty array if none were localized.
 */
export function getFieldTypes() {
	const fieldTypes = typeof osfSettings !== 'undefined' ? osfSettings?.fieldTypes : undefined;

	return Array.isArray(fieldTypes) ? fieldTypes : [];
}

/**
 * Resolve the editor control that renders a field type.
 *
 * A type absent from the registry — unregistered, or from a plugin that has
 * since been deactivated — resolves to `undefined` so callers can degrade
 * visibly instead of guessing at a control to render.
 *
 * @param {string} type Field type.
 * @return {string|undefined} The control name (e.g. 'input', 'textarea'), or undefined if the type isn't registered.
 */
export function resolveFieldControl(type) {
	return getFieldTypes().find((fieldType) => fieldType?.type === type)?.control;
}

/**
 * Checks whether a field type renders several inputs under one name.
 *
 * `FieldFactory::get_registered_types()` decides this, from whether the type's
 * control is a `GroupComponentInterface`; it reaches the editor as the field
 * type's `group` flag. A group labels a whole list rather than one control, so
 * it cannot take an inline label.
 *
 * @param {string} type Field type.
 * @return {boolean} True if the type renders a group of controls.
 */
export function isGroupFieldType(type) {
	return getFieldTypes().find((fieldType) => fieldType?.type === type)?.group === true;
}

/**
 * Checks whether an input type supports the mask attribute.
 *
 * `Input::UNMASKABLE_TYPES` is the only definition of which input types
 * don't support masking; it reaches the editor as
 * `osfSettings.unmaskableTypes`. There is deliberately no JS fallback list —
 * see `isFieldBlock()` above for why.
 *
 * @param {string} type Input type.
 * @return {boolean} True if the type supports masking.
 */
export function supportsMask(type) {
	const unmaskableTypes =
		typeof osfSettings !== 'undefined' ? osfSettings?.unmaskableTypes : undefined;

	if (!Array.isArray(unmaskableTypes)) {
		return false;
	}

	return !unmaskableTypes.includes(type);
}

/**
 * Get the valid label position values.
 *
 * `Field::LABEL_POSITIONS` is the only definition of this list; it reaches
 * the editor as `osfSettings.labelPositions`. There is deliberately no JS
 * fallback list — see `isFieldBlock()` above for why.
 *
 * @return {Array<string>} The valid label position values, or an empty array if none were localized.
 */
export function getLabelPositions() {
	const labelPositions =
		typeof osfSettings !== 'undefined' ? osfSettings?.labelPositions : undefined;

	return Array.isArray(labelPositions) ? labelPositions : [];
}

/**
 * Checks whether a label position places the label beside the field instead
 * of above or below it.
 *
 * `Field::INLINE_LABEL_POSITIONS` is the only definition of this list; it
 * reaches the editor as `osfSettings.inlineLabelPositions`. There is
 * deliberately no JS fallback list — see `isFieldBlock()` above for why.
 *
 * @param {string} labelPosition The label position.
 * @return {boolean} True if the label position is inline.
 */
export function isInlineLabelPosition(labelPosition) {
	const inlineLabelPositions =
		typeof osfSettings !== 'undefined' ? osfSettings?.inlineLabelPositions : undefined;

	if (!Array.isArray(inlineLabelPositions)) {
		return false;
	}

	return inlineLabelPositions.includes(labelPosition);
}

/**
 * Get the valid help text position values.
 *
 * `Field::HELP_TEXT_POSITIONS` is the only definition of this list; it
 * reaches the editor as `osfSettings.helpTextPositions`. There is
 * deliberately no JS fallback list — see `isFieldBlock()` above for why.
 *
 * @return {Array<string>} The valid help text position values, or an empty array if none were localized.
 */
export function getHelpTextPositions() {
	const helpTextPositions =
		typeof osfSettings !== 'undefined' ? osfSettings?.helpTextPositions : undefined;

	return Array.isArray(helpTextPositions) ? helpTextPositions : [];
}

/**
 * Get the form action IDs.
 *
 * `EmailNotification::ACTION_ADMIN_NOTIFICATION` and
 * `EmailNotification::ACTION_USER_NOTIFICATION` are the only definitions of
 * these values; they reach the editor as `osfSettings.formActionIds`. There
 * is deliberately no JS fallback — see `isFieldBlock()` above for why.
 *
 * @return {{adminNotification: string, userNotification: string}|{}} The form action IDs, or an empty object if none were localized.
 */
export function getFormActionIds() {
	const formActionIds =
		typeof osfSettings !== 'undefined' ? osfSettings?.formActionIds : undefined;

	return typeof formActionIds === 'object' && formActionIds !== null ? formActionIds : {};
}

/**
 * Resolve the submission name for a field block.
 *
 * Mirrors `AbstractField::get_field_name()` (PHP): an explicit `name`
 * attribute wins, otherwise the name falls back to `field_{fieldId}`. The PHP
 * side checks `! empty( $name )`, which treats `null`, `undefined`, `''` and
 * the string `"0"` as absent — but NOT a whitespace-only string, since PHP's
 * `empty()` only considers a non-empty string falsy when it equals `'0'`.
 * Match that exactly here rather than using a plain JS truthiness check,
 * which would treat `"0"` as a valid name.
 *
 * @param {Object} attributes           The field block attributes.
 * @param {string} [attributes.name]    The explicit field name, if set.
 * @param {string} [attributes.fieldId] The field's block ID.
 * @return {string} The resolved field name.
 */
export function resolveFieldName(attributes) {
	const name = attributes?.name;
	const isNameAbsent = name === undefined || name === null || name === '' || name === '0';

	return isNameAbsent ? `field_${attributes?.fieldId}` : name;
}

/**
 * Recursively finds all blocks that match a given condition.
 *
 * This function searches through a list of blocks and all their inner blocks,
 * returning those that satisfy the provided matcher function.
 *
 * @param {Function} matcher A function that receives a block and returns true if it should be included.
 * @param {Array}    blocks  An array of blocks to search through.
 *
 * @return {Array} An array of blocks that match the condition.
 *
 * @example
 * findBlocks(isFieldBlock, getBlocks(clientId));
 */
export function findBlocks(matcher, blocks = []) {
	return blocks.flatMap((block) => {
		const children = Array.isArray(block.innerBlocks) ? block.innerBlocks : [];
		const matches = matcher(block) ? [block] : [];
		return [...matches, ...findBlocks(matcher, children)];
	});
}
