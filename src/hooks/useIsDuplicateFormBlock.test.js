/* eslint-env jest */
/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { useIsDuplicateFormBlock } from './useIsDuplicateFormBlock';

/**
 * See useFieldBlocks.test.js for why `@wordpress/data` and
 * `@wordpress/block-editor` are mocked this way.
 */
jest.mock('@wordpress/data', () => ({
	useSelect: (mapSelect) => mapSelect(() => global.__fakeSelect),
}));

jest.mock('@wordpress/block-editor', () => ({ store: 'core/block-editor' }));

describe('useIsDuplicateFormBlock', () => {
	afterEach(() => {
		delete global.__fakeSelect;
	});

	it('returns false when the block has no root container', () => {
		global.__fakeSelect = {
			getBlockRootClientId: jest.fn(() => ''),
			getBlocks: jest.fn(() => []),
			getBlockIndex: jest.fn(() => -1),
		};

		const { result } = renderHook(() =>
			useIsDuplicateFormBlock('form-1', { title: 'Contact' }),
		);

		expect(result.current).toBe(false);
	});

	it('returns false when there are no other form blocks in the root', () => {
		global.__fakeSelect = {
			getBlockRootClientId: jest.fn(() => 'root-1'),
			getBlocks: jest.fn(() => [{ clientId: 'form-1', name: 'osf/form', innerBlocks: [] }]),
			getBlockIndex: jest.fn((clientId) => (clientId === 'form-1' ? 0 : -1)),
		};

		const { result } = renderHook(() =>
			useIsDuplicateFormBlock('form-1', { title: 'Contact' }),
		);

		expect(result.current).toBe(false);
	});

	it('returns true when an earlier form block has identical attributes', () => {
		const attributes = { title: 'Contact' };
		global.__fakeSelect = {
			getBlockRootClientId: jest.fn(() => 'root-1'),
			getBlocks: jest.fn(() => [
				{ clientId: 'form-1', name: 'osf/form', attributes, innerBlocks: [] },
				{
					clientId: 'form-2',
					name: 'osf/form',
					attributes: { ...attributes },
					innerBlocks: [],
				},
			]),
			getBlockIndex: jest.fn((clientId) => (clientId === 'form-1' ? 0 : 1)),
		};

		const { result } = renderHook(() => useIsDuplicateFormBlock('form-2', attributes));

		expect(result.current).toBe(true);
	});

	it('returns false when the matching form block appears later, not earlier', () => {
		const attributes = { title: 'Contact' };
		global.__fakeSelect = {
			getBlockRootClientId: jest.fn(() => 'root-1'),
			getBlocks: jest.fn(() => [
				{ clientId: 'form-1', name: 'osf/form', attributes, innerBlocks: [] },
				{
					clientId: 'form-2',
					name: 'osf/form',
					attributes: { ...attributes },
					innerBlocks: [],
				},
			]),
			getBlockIndex: jest.fn((clientId) => (clientId === 'form-1' ? 0 : 1)),
		};

		const { result } = renderHook(() => useIsDuplicateFormBlock('form-1', attributes));

		expect(result.current).toBe(false);
	});

	it('returns false when attributes differ', () => {
		global.__fakeSelect = {
			getBlockRootClientId: jest.fn(() => 'root-1'),
			getBlocks: jest.fn(() => [
				{
					clientId: 'form-1',
					name: 'osf/form',
					attributes: { title: 'A' },
					innerBlocks: [],
				},
				{
					clientId: 'form-2',
					name: 'osf/form',
					attributes: { title: 'B' },
					innerBlocks: [],
				},
			]),
			getBlockIndex: jest.fn((clientId) => (clientId === 'form-1' ? 0 : 1)),
		};

		const { result } = renderHook(() => useIsDuplicateFormBlock('form-2', { title: 'B' }));

		expect(result.current).toBe(false);
	});

	it('returns false when the current block itself cannot be located', () => {
		global.__fakeSelect = {
			getBlockRootClientId: jest.fn(() => 'root-1'),
			getBlocks: jest.fn(() => [{ clientId: 'form-1', name: 'osf/form', innerBlocks: [] }]),
			getBlockIndex: jest.fn(() => -1),
		};

		const { result } = renderHook(() => useIsDuplicateFormBlock('form-missing', {}));

		expect(result.current).toBe(false);
	});
});
