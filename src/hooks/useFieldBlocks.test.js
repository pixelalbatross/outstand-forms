/* eslint-env jest */
/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { useFieldBlocks } from './useFieldBlocks';

/**
 * `@wordpress/data` resolves fine in this test environment, but
 * `@wordpress/block-editor` doesn't (it always resolves to its untranspiled
 * TypeScript source, which the CJS test runtime can't parse). Both are
 * mocked here: `useSelect` is reduced to "call the selector with a fake
 * `select()`", which lets the test control exactly what `getBlocks()`
 * returns without needing a real registered block-editor store.
 */
jest.mock('@wordpress/data', () => ({
	useSelect: (mapSelect) => mapSelect(() => global.__fakeSelect),
}));

jest.mock('@wordpress/block-editor', () => ({ store: 'core/block-editor' }));

describe('useFieldBlocks', () => {
	afterEach(() => {
		delete global.osfSettings;
		delete global.__fakeSelect;
	});

	it('returns only the field blocks found within the subtree', () => {
		global.osfSettings = { fieldBlockNames: ['osf/field-input', 'osf/field-textarea'] };
		global.__fakeSelect = {
			getBlocks: jest.fn(() => [
				{
					name: 'core/group',
					innerBlocks: [
						{ name: 'osf/field-input', innerBlocks: [] },
						{ name: 'core/paragraph', innerBlocks: [] },
					],
				},
				{ name: 'osf/field-textarea', innerBlocks: [] },
			]),
		};

		const { result } = renderHook(() => useFieldBlocks('root-client-id'));

		expect(result.current.map((block) => block.name)).toEqual([
			'osf/field-input',
			'osf/field-textarea',
		]);
		expect(global.__fakeSelect.getBlocks).toHaveBeenCalledWith('root-client-id');
	});

	it('returns an empty array when the subtree has no field blocks', () => {
		global.osfSettings = { fieldBlockNames: ['osf/field-input'] };
		global.__fakeSelect = {
			getBlocks: jest.fn(() => [{ name: 'core/paragraph', innerBlocks: [] }]),
		};

		const { result } = renderHook(() => useFieldBlocks('root-client-id'));

		expect(result.current).toEqual([]);
	});
});
