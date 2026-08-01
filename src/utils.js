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
 * Checks whether a block represents a form field.
 *
 * `FormBlockParser::FIELD_BLOCK_NAMES` is the only definition of this list;
 * it reaches the editor as `osfSettings.fieldBlockNames`. There is
 * deliberately no JS fallback list — a stale mirror would silently disagree
 * with the server about what gets validated and submitted.
 *
 * @param {Object} block The block to check.
 * @return {boolean} True if the block is a form field block.
 */
export function isFieldBlock(block) {
	const fieldBlockNames =
		typeof osfSettings !== 'undefined' ? osfSettings?.fieldBlockNames : undefined;

	if (!Array.isArray(fieldBlockNames)) {
		return false;
	}

	return fieldBlockNames.includes(block?.name);
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
