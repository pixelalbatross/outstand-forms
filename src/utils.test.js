/* eslint-env jest */
/**
 * Internal dependencies
 */
import {
	getBlockId,
	findBlocks,
	getFieldTypes,
	getFormActionIds,
	getHelpTextPositions,
	getLabelPositions,
	isFieldBlock,
	isInlineLabelPosition,
	resolveFieldControl,
	resolveFieldName,
	supportsMask,
} from './utils';

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

describe('isFieldBlock', () => {
	afterEach(() => {
		delete global.osfSettings;
	});

	it('returns false when osfSettings is undefined', () => {
		expect(isFieldBlock({ name: 'osf/field-input' })).toBe(false);
	});

	it('returns false when fieldBlockNames is not an array', () => {
		global.osfSettings = { fieldBlockNames: 'nope' };

		expect(isFieldBlock({ name: 'osf/field-input' })).toBe(false);
	});

	it('returns true for a block name declared by PHP', () => {
		global.osfSettings = { fieldBlockNames: ['osf/field-input', 'osf/field-textarea'] };

		expect(isFieldBlock({ name: 'osf/field-input' })).toBe(true);
	});

	it('returns false for a block name not declared by PHP', () => {
		global.osfSettings = { fieldBlockNames: ['osf/field-input'] };

		expect(isFieldBlock({ name: 'core/paragraph' })).toBe(false);
	});

	it('returns false when the block is missing a name', () => {
		global.osfSettings = { fieldBlockNames: ['osf/field-input'] };

		expect(isFieldBlock({})).toBe(false);
	});
});

describe('getFieldTypes', () => {
	afterEach(() => {
		delete global.osfSettings;
	});

	it('returns an empty array when osfSettings is undefined', () => {
		expect(getFieldTypes()).toEqual([]);
	});

	it('returns an empty array when fieldTypes is not an array', () => {
		global.osfSettings = { fieldTypes: 'nope' };

		expect(getFieldTypes()).toEqual([]);
	});

	it('returns the localized field types', () => {
		global.osfSettings = {
			fieldTypes: [{ type: 'text', label: 'Text', control: 'input' }],
		};

		expect(getFieldTypes()).toEqual([{ type: 'text', label: 'Text', control: 'input' }]);
	});
});

describe('resolveFieldControl', () => {
	afterEach(() => {
		delete global.osfSettings;
	});

	it('returns undefined when the type is not registered', () => {
		global.osfSettings = {
			fieldTypes: [{ type: 'text', label: 'Text', control: 'input' }],
		};

		expect(resolveFieldControl('date')).toBeUndefined();
	});

	it('returns undefined when osfSettings is undefined', () => {
		expect(resolveFieldControl('text')).toBeUndefined();
	});

	it('returns the control for a registered type', () => {
		global.osfSettings = {
			fieldTypes: [
				{ type: 'text', label: 'Text', control: 'input' },
				{ type: 'textarea', label: 'Textarea', control: 'textarea' },
			],
		};

		expect(resolveFieldControl('text')).toBe('input');
		expect(resolveFieldControl('textarea')).toBe('textarea');
	});
});

describe('supportsMask', () => {
	afterEach(() => {
		delete global.osfSettings;
	});

	it('returns false when osfSettings is undefined', () => {
		expect(supportsMask('text')).toBe(false);
	});

	it('returns false when unmaskableTypes is not an array', () => {
		global.osfSettings = { unmaskableTypes: 'nope' };

		expect(supportsMask('text')).toBe(false);
	});

	it('returns false for an unmaskable type declared by PHP', () => {
		global.osfSettings = { unmaskableTypes: ['number', 'email', 'url'] };

		expect(supportsMask('email')).toBe(false);
	});

	it('returns true for a type not in the unmaskable list', () => {
		global.osfSettings = { unmaskableTypes: ['number', 'email', 'url'] };

		expect(supportsMask('text')).toBe(true);
	});
});

describe('getLabelPositions', () => {
	afterEach(() => {
		delete global.osfSettings;
	});

	it('returns an empty array when osfSettings is undefined', () => {
		expect(getLabelPositions()).toEqual([]);
	});

	it('returns the localized label positions', () => {
		global.osfSettings = { labelPositions: ['top', 'left', 'right'] };

		expect(getLabelPositions()).toEqual(['top', 'left', 'right']);
	});
});

describe('isInlineLabelPosition', () => {
	afterEach(() => {
		delete global.osfSettings;
	});

	it('returns false when osfSettings is undefined', () => {
		expect(isInlineLabelPosition('left')).toBe(false);
	});

	it('returns true for a position declared inline by PHP', () => {
		global.osfSettings = { inlineLabelPositions: ['left', 'right'] };

		expect(isInlineLabelPosition('left')).toBe(true);
		expect(isInlineLabelPosition('right')).toBe(true);
	});

	it('returns false for a position not declared inline by PHP', () => {
		global.osfSettings = { inlineLabelPositions: ['left', 'right'] };

		expect(isInlineLabelPosition('top')).toBe(false);
	});
});

describe('getHelpTextPositions', () => {
	afterEach(() => {
		delete global.osfSettings;
	});

	it('returns an empty array when osfSettings is undefined', () => {
		expect(getHelpTextPositions()).toEqual([]);
	});

	it('returns the localized help text positions', () => {
		global.osfSettings = { helpTextPositions: ['bottom', 'top'] };

		expect(getHelpTextPositions()).toEqual(['bottom', 'top']);
	});
});

describe('getFormActionIds', () => {
	afterEach(() => {
		delete global.osfSettings;
	});

	it('returns an empty object when osfSettings is undefined', () => {
		expect(getFormActionIds()).toEqual({});
	});

	it('returns the localized form action ids', () => {
		global.osfSettings = {
			formActionIds: {
				adminNotification: 'admin_notification',
				userNotification: 'user_notification',
			},
		};

		expect(getFormActionIds()).toEqual({
			adminNotification: 'admin_notification',
			userNotification: 'user_notification',
		});
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
