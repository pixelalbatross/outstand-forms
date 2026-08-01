/* eslint-env jest */
/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { useFieldId } from './useFieldId';

/**
 * See useFieldBlocks.test.js for why `@wordpress/data` and
 * `@wordpress/block-editor` are mocked this way.
 */
jest.mock('@wordpress/data', () => ({
	useDispatch: () => ({ updateBlockAttributes: global.__updateBlockAttributes }),
}));

jest.mock('@wordpress/block-editor', () => ({ store: 'core/block-editor' }));

describe('useFieldId', () => {
	beforeEach(() => {
		global.__updateBlockAttributes = jest.fn();
	});

	afterEach(() => {
		delete global.__updateBlockAttributes;
	});

	it('does nothing when there are no field blocks', () => {
		const setAttributes = jest.fn();

		renderHook(() => useFieldId({ nextFieldId: 1 }, setAttributes, []));

		expect(global.__updateBlockAttributes).not.toHaveBeenCalled();
		expect(setAttributes).not.toHaveBeenCalled();
	});

	it('assigns a sequential id to a block with no existing fieldId', () => {
		const setAttributes = jest.fn();
		const fieldBlocks = [{ clientId: 'field-1', attributes: {} }];

		renderHook(() => useFieldId({ nextFieldId: 1 }, setAttributes, fieldBlocks));

		expect(global.__updateBlockAttributes).toHaveBeenCalledWith('field-1', {
			fieldId: 1,
			name: '',
		});
		expect(setAttributes).toHaveBeenCalledWith({ nextFieldId: 2 });
	});

	it('leaves blocks with a valid, unique fieldId untouched', () => {
		const setAttributes = jest.fn();
		const fieldBlocks = [{ clientId: 'field-1', attributes: { fieldId: 5 } }];

		renderHook(() => useFieldId({ nextFieldId: 6 }, setAttributes, fieldBlocks));

		expect(global.__updateBlockAttributes).not.toHaveBeenCalled();
		expect(setAttributes).not.toHaveBeenCalled();
	});

	it('reassigns a block whose fieldId collides with another block', () => {
		const setAttributes = jest.fn();
		const fieldBlocks = [
			{ clientId: 'field-1', attributes: { fieldId: 5 } },
			{ clientId: 'field-2', attributes: { fieldId: 5 } },
		];

		renderHook(() => useFieldId({ nextFieldId: 1 }, setAttributes, fieldBlocks));

		expect(global.__updateBlockAttributes).toHaveBeenCalledTimes(1);
		expect(global.__updateBlockAttributes).toHaveBeenCalledWith('field-2', {
			fieldId: 6,
			name: '',
		});
		expect(setAttributes).toHaveBeenCalledWith({ nextFieldId: 7 });
	});

	it('treats a string fieldId as valid when it parses to a positive integer', () => {
		const setAttributes = jest.fn();
		const fieldBlocks = [{ clientId: 'field-1', attributes: { fieldId: '5' } }];

		renderHook(() => useFieldId({ nextFieldId: 6 }, setAttributes, fieldBlocks));

		expect(global.__updateBlockAttributes).not.toHaveBeenCalled();
	});

	it('reassigns a fieldId of zero, since it is not a valid positive integer', () => {
		const setAttributes = jest.fn();
		const fieldBlocks = [{ clientId: 'field-1', attributes: { fieldId: 0 } }];

		renderHook(() => useFieldId({ nextFieldId: 1 }, setAttributes, fieldBlocks));

		expect(global.__updateBlockAttributes).toHaveBeenCalledWith('field-1', {
			fieldId: 1,
			name: '',
		});
	});

	it('starts numbering from the highest existing id, ignoring a stale nextFieldId', () => {
		const setAttributes = jest.fn();
		const fieldBlocks = [
			{ clientId: 'field-1', attributes: { fieldId: 10 } },
			{ clientId: 'field-2', attributes: {} },
		];

		renderHook(() => useFieldId({ nextFieldId: 1 }, setAttributes, fieldBlocks));

		expect(global.__updateBlockAttributes).toHaveBeenCalledWith('field-2', {
			fieldId: 11,
			name: '',
		});
		expect(setAttributes).toHaveBeenCalledWith({ nextFieldId: 12 });
	});
});
