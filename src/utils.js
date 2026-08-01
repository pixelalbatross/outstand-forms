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
