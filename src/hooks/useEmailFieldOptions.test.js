/* eslint-env jest */
/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { useEmailFieldOptions } from './useEmailFieldOptions';

describe('useEmailFieldOptions', () => {
	it('always leads with the placeholder option', () => {
		const { result } = renderHook(() => useEmailFieldOptions([]));

		expect(result.current).toEqual([{ label: '— Select field —', value: '' }]);
	});

	it('ignores non-email field blocks', () => {
		const fieldBlocks = [
			{ attributes: { type: 'text', fieldId: 1, label: 'Name' } },
			{ attributes: { type: 'textarea', fieldId: 2, label: 'Message' } },
		];

		const { result } = renderHook(() => useEmailFieldOptions(fieldBlocks));

		expect(result.current).toEqual([{ label: '— Select field —', value: '' }]);
	});

	it('ignores an email field block with no fieldId', () => {
		const fieldBlocks = [{ attributes: { type: 'email', label: 'Email' } }];

		const { result } = renderHook(() => useEmailFieldOptions(fieldBlocks));

		expect(result.current).toEqual([{ label: '— Select field —', value: '' }]);
	});

	it('builds a labelled option for each email field, preferring the label', () => {
		const fieldBlocks = [
			{ attributes: { type: 'email', fieldId: 3, label: 'Work Email', name: 'work_email' } },
		];

		const { result } = renderHook(() => useEmailFieldOptions(fieldBlocks));

		expect(result.current).toEqual([
			{ label: '— Select field —', value: '' },
			{ label: 'Work Email (#3)', value: '3' },
		]);
	});

	it('falls back to the field name when no label is set', () => {
		const fieldBlocks = [{ attributes: { type: 'email', fieldId: 4, name: 'work_email' } }];

		const { result } = renderHook(() => useEmailFieldOptions(fieldBlocks));

		expect(result.current[1]).toEqual({ label: 'work_email (#4)', value: '4' });
	});

	it('falls back to just the field id when neither label nor name is set', () => {
		const fieldBlocks = [{ attributes: { type: 'email', fieldId: 5 } }];

		const { result } = renderHook(() => useEmailFieldOptions(fieldBlocks));

		expect(result.current[1]).toEqual({ label: '#5', value: '5' });
	});

	it('lists multiple email fields in order', () => {
		const fieldBlocks = [
			{ attributes: { type: 'email', fieldId: 1, label: 'Primary Email' } },
			{ attributes: { type: 'text', fieldId: 2, label: 'Name' } },
			{ attributes: { type: 'email', fieldId: 3, label: 'Secondary Email' } },
		];

		const { result } = renderHook(() => useEmailFieldOptions(fieldBlocks));

		expect(result.current).toEqual([
			{ label: '— Select field —', value: '' },
			{ label: 'Primary Email (#1)', value: '1' },
			{ label: 'Secondary Email (#3)', value: '3' },
		]);
	});
});
