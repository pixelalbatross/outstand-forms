/* eslint-env jest */
/**
 * Internal dependencies
 */
import { getBlockId, findBlocks, resolveFieldName } from './utils';

describe('getBlockId', () => {
	it('returns an id of the requested length', () => {
		expect(getBlockId()).toHaveLength(9);
		expect(getBlockId(12)).toHaveLength(12);
	});

	it('returns unique ids', () => {
		expect(getBlockId()).not.toBe(getBlockId());
	});
});

describe('findBlocks', () => {
	const tree = [
		{
			name: 'core/group',
			innerBlocks: [
				{ name: 'osf/field-input', innerBlocks: [] },
				{
					name: 'osf/form-fields',
					innerBlocks: [
						{ name: 'osf/field-textarea', innerBlocks: [] },
						{ name: 'core/paragraph' },
					],
				},
			],
		},
		{ name: 'osf/field-input', innerBlocks: [] },
	];

	it('finds matching blocks recursively', () => {
		const fields = findBlocks((block) => block.name?.startsWith('osf/field-'), tree);

		expect(fields.map((block) => block.name)).toEqual([
			'osf/field-input',
			'osf/field-textarea',
			'osf/field-input',
		]);
	});

	it('returns an empty array when nothing matches', () => {
		expect(findBlocks((block) => block.name === 'osf/nope', tree)).toEqual([]);
	});

	it('handles blocks without innerBlocks', () => {
		expect(findBlocks((block) => block.name === 'core/paragraph', tree)).toHaveLength(1);
	});

	it('defaults to an empty block list', () => {
		expect(findBlocks(() => true)).toEqual([]);
	});
});

describe('resolveFieldName', () => {
	it('returns the explicit name when set', () => {
		expect(resolveFieldName({ name: 'email', fieldId: 'abc123456' })).toBe('email');
	});

	it('falls back to field_{fieldId} when the name is an empty string', () => {
		expect(resolveFieldName({ name: '', fieldId: 'abc123456' })).toBe('field_abc123456');
	});

	it('keeps a whitespace-only name, matching PHP empty() semantics', () => {
		expect(resolveFieldName({ name: '   ', fieldId: 'abc123456' })).toBe('   ');
	});

	it('falls back to field_{fieldId} when the name is the string "0"', () => {
		expect(resolveFieldName({ name: '0', fieldId: 'abc123456' })).toBe('field_abc123456');
	});

	it('falls back to field_{fieldId} when the name is missing', () => {
		expect(resolveFieldName({ fieldId: 'abc123456' })).toBe('field_abc123456');
	});
});
