/* eslint-env jest */
/**
 * Internal dependencies
 */
import { buildFieldInputVariations } from './get-variations';

const GENERIC_ICON = 'generic-icon';

const TEXT_METADATA = {
	text: {
		title: 'Text',
		icon: 'text-icon',
		isDefault: true,
		keywords: ['field', 'input', 'text'],
	},
	email: {
		title: 'Email',
		icon: 'email-icon',
		keywords: ['field', 'input', 'email'],
		attributes: { autocomplete: 'email' },
	},
	url: {
		title: 'URL',
		icon: 'link-icon',
		keywords: ['field', 'input', 'url', 'link'],
		attributes: { autocomplete: 'url' },
	},
};

describe('buildFieldInputVariations', () => {
	it('returns an empty array when no field types are registered', () => {
		expect(buildFieldInputVariations([], TEXT_METADATA, GENERIC_ICON)).toEqual([]);
	});

	it('defaults a missing control to input', () => {
		const variations = buildFieldInputVariations(
			[{ type: 'text', label: 'Text' }],
			TEXT_METADATA,
			GENERIC_ICON,
		);

		expect(variations).toHaveLength(1);
		expect(variations[0].name).toBe('text');
	});

	it('keeps the editorial title, icon and default flag for a known type', () => {
		const variations = buildFieldInputVariations(
			[{ type: 'text', label: 'Text', control: 'input' }],
			TEXT_METADATA,
			GENERIC_ICON,
		);

		expect(variations[0]).toMatchObject({
			name: 'text',
			title: 'Text',
			icon: 'text-icon',
			isDefault: true,
			keywords: ['field', 'input', 'text'],
			attributes: { type: 'text' },
		});
	});

	it('ignores the registry label for a known type in favor of the editorial title', () => {
		const variations = buildFieldInputVariations(
			[{ type: 'text', label: 'Some other label', control: 'input' }],
			TEXT_METADATA,
			GENERIC_ICON,
		);

		expect(variations[0].title).toBe('Text');
	});

	it('keeps the editorial extra attributes for a known type', () => {
		const variations = buildFieldInputVariations(
			[{ type: 'email', label: 'Email', control: 'input' }],
			TEXT_METADATA,
			GENERIC_ICON,
		);

		expect(variations[0].attributes).toEqual({ type: 'email', autocomplete: 'email' });
	});

	it('preserves the editorial ordering regardless of registry order', () => {
		const variations = buildFieldInputVariations(
			[
				{ type: 'url', label: 'URL', control: 'input' },
				{ type: 'text', label: 'Text', control: 'input' },
				{ type: 'email', label: 'Email', control: 'input' },
			],
			TEXT_METADATA,
			GENERIC_ICON,
		);

		expect(variations.map((variation) => variation.name)).toEqual(['text', 'email', 'url']);
	});

	it('excludes types whose control is not input', () => {
		const variations = buildFieldInputVariations(
			[
				{ type: 'text', label: 'Text', control: 'input' },
				{ type: 'textarea', label: 'Textarea', control: 'textarea' },
			],
			TEXT_METADATA,
			GENERIC_ICON,
		);

		expect(variations.map((variation) => variation.name)).toEqual(['text']);
	});

	it('excludes a known type from the metadata lookup that is not currently registered', () => {
		const variations = buildFieldInputVariations(
			[{ type: 'text', label: 'Text', control: 'input' }],
			TEXT_METADATA,
			GENERIC_ICON,
		);

		expect(variations.map((variation) => variation.name)).not.toContain('email');
	});

	it('falls back to the registry label and generic icon for an unknown type', () => {
		const variations = buildFieldInputVariations(
			[{ type: 'signature', label: 'Signature', control: 'input' }],
			TEXT_METADATA,
			GENERIC_ICON,
		);

		expect(variations).toHaveLength(1);
		expect(variations[0]).toMatchObject({
			name: 'signature',
			title: 'Signature',
			icon: GENERIC_ICON,
			keywords: ['signature'],
			attributes: { type: 'signature' },
		});
		expect(variations[0].isDefault).toBeUndefined();
	});

	it('falls back to the type itself when an unknown type has no label', () => {
		const variations = buildFieldInputVariations(
			[{ type: 'signature', control: 'input' }],
			TEXT_METADATA,
			GENERIC_ICON,
		);

		expect(variations[0].title).toBe('signature');
	});

	it('appends unknown types after the known ones', () => {
		const variations = buildFieldInputVariations(
			[
				{ type: 'signature', label: 'Signature', control: 'input' },
				{ type: 'text', label: 'Text', control: 'input' },
			],
			TEXT_METADATA,
			GENERIC_ICON,
		);

		expect(variations.map((variation) => variation.name)).toEqual(['text', 'signature']);
	});

	it('does not set isActive - that is applied by the module wrapping the default export', () => {
		const variations = buildFieldInputVariations(
			[{ type: 'text', label: 'Text', control: 'input' }],
			TEXT_METADATA,
			GENERIC_ICON,
		);

		expect(variations[0].isActive).toBeUndefined();
	});
});
