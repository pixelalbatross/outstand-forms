/* eslint-disable import/no-extraneous-dependencies */
/**
 * WordPress dependencies
 */
const { test, expect } = require('@wordpress/e2e-test-utils-playwright');

test.describe('Field type variations', () => {
	test.beforeEach(async ({ admin }) => {
		await admin.createNewPost();
	});

	test('every input-controlled type is offered as an inserter variation', async ({ page }) => {
		const names = await page.evaluate(() =>
			(window.wp.blocks.getBlockVariations('osf/field-input', 'inserter') || []).map(
				(variation) => variation.name,
			),
		);

		// The registry is the source of truth for which types exist, so this
		// asserts the six built-in input types reach the editor. It fails if
		// the localized settings are unavailable when variations.js evaluates,
		// which silently registers none at all.
		expect(names.sort()).toEqual(['email', 'number', 'password', 'tel', 'text', 'url']);
	});

	test('the settings the editor derives its lists from are defined before any block script runs', async ({
		page,
	}) => {
		const settings = await page.evaluate(() => window.osfSettings ?? null);

		expect(settings).not.toBeNull();
		expect(settings.fieldBlockNames).toContain('osf/field-input');
		expect(settings.fieldTypes.map((fieldType) => fieldType.type)).toContain('email');
	});

	test('types rendered by their own control are not offered as input variations', async ({
		page,
	}) => {
		const names = await page.evaluate(() =>
			(window.wp.blocks.getBlockVariations('osf/field-input', 'inserter') || []).map(
				(variation) => variation.name,
			),
		);

		// A select or checkbox has its own block; offering it as an <input>
		// variation would render a text field claiming to be a dropdown.
		expect(names).not.toContain('select');
		expect(names).not.toContain('radio');
		expect(names).not.toContain('checkbox');
		expect(names).not.toContain('consent');
		expect(names).not.toContain('textarea');
	});
});
