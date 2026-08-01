/* eslint-env jest */
/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { useIsDuplicateFieldName } from './useIsDuplicateFieldName';

/**
 * See useFieldBlocks.test.js for why `@wordpress/data` and
 * `@wordpress/block-editor` are mocked this way.
 */
jest.mock('@wordpress/data', () => ({
	useSelect: (mapSelect) => mapSelect(() => global.__fakeSelect),
}));

jest.mock('@wordpress/block-editor', () => ({ store: 'core/block-editor' }));

describe('useIsDuplicateFieldName', () => {
	afterEach(() => {
		delete global.osfSettings;
		delete global.__fakeSelect;
	});

	it('returns false when the block has no parent form', () => {
		global.osfSettings = { fieldBlockNames: ['osf/field-input'] };
		global.__fakeSelect = {
			getBlockParentsByBlockName: jest.fn(() => []),
			getBlocks: jest.fn(() => []),
		};

		const { result } = renderHook(() =>
			useIsDuplicateFieldName('field-1', { name: 'email', fieldId: '1' }),
		);

		expect(result.current).toBe(false);
	});

	it('returns true when another field in the same form resolves to the same name', () => {
		global.osfSettings = { fieldBlockNames: ['osf/field-input'] };
		global.__fakeSelect = {
			getBlockParentsByBlockName: jest.fn(() => ['form-1']),
			getBlocks: jest.fn(() => [
				{
					clientId: 'field-1',
					name: 'osf/field-input',
					attributes: { name: 'email', fieldId: '1' },
				},
				{
					clientId: 'field-2',
					name: 'osf/field-input',
					attributes: { name: 'email', fieldId: '2' },
				},
			]),
		};

		const { result } = renderHook(() =>
			useIsDuplicateFieldName('field-1', { name: 'email', fieldId: '1' }),
		);

		expect(result.current).toBe(true);
	});

	it('returns false when no other field resolves to the same name', () => {
		global.osfSettings = { fieldBlockNames: ['osf/field-input'] };
		global.__fakeSelect = {
			getBlockParentsByBlockName: jest.fn(() => ['form-1']),
			getBlocks: jest.fn(() => [
				{
					clientId: 'field-1',
					name: 'osf/field-input',
					attributes: { name: 'email', fieldId: '1' },
				},
				{
					clientId: 'field-2',
					name: 'osf/field-input',
					attributes: { name: 'phone', fieldId: '2' },
				},
			]),
		};

		const { result } = renderHook(() =>
			useIsDuplicateFieldName('field-1', { name: 'email', fieldId: '1' }),
		);

		expect(result.current).toBe(false);
	});

	it('ignores the block being checked when comparing names', () => {
		global.osfSettings = { fieldBlockNames: ['osf/field-input'] };
		global.__fakeSelect = {
			getBlockParentsByBlockName: jest.fn(() => ['form-1']),
			getBlocks: jest.fn(() => [
				{
					clientId: 'field-1',
					name: 'osf/field-input',
					attributes: { name: '', fieldId: '1' },
				},
			]),
		};

		const { result } = renderHook(() =>
			useIsDuplicateFieldName('field-1', { name: '', fieldId: '1' }),
		);

		expect(result.current).toBe(false);
	});

	it('compares resolved names, so an explicit name colliding with a fallback name is caught', () => {
		global.osfSettings = { fieldBlockNames: ['osf/field-input'] };
		global.__fakeSelect = {
			getBlockParentsByBlockName: jest.fn(() => ['form-1']),
			getBlocks: jest.fn(() => [
				{
					clientId: 'field-1',
					name: 'osf/field-input',
					attributes: { name: '', fieldId: '2' },
				},
				{
					clientId: 'field-2',
					name: 'osf/field-input',
					attributes: { name: 'field_2', fieldId: '99' },
				},
			]),
		};

		const { result } = renderHook(() =>
			useIsDuplicateFieldName('field-1', { name: '', fieldId: '2' }),
		);

		expect(result.current).toBe(true);
	});
});
