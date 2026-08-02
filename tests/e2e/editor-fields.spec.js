/* eslint-disable import/no-extraneous-dependencies */
/**
 * WordPress dependencies
 */
const { test, expect } = require('@wordpress/e2e-test-utils-playwright');

/**
 * Block markup for a form holding a single field block.
 *
 * @param {string} inner The field block markup.
 * @return {string} The form markup.
 */
function formMarkup(inner) {
	return `<!-- wp:osf/form {"formId":"e2e-editor-fields","nextFieldId":2} -->
<!-- wp:osf/form-fields -->
${inner}
<!-- /wp:osf/form-fields -->
<!-- /wp:osf/form -->`;
}

/**
 * Insert a field block into the form's fields wrapper the way the inserter does.
 *
 * `setContent` describes a block that already exists; this is a real insertion,
 * which is what the seeding of default options keys off.
 *
 * @param {import('@playwright/test').Page} page       The page.
 * @param {string}                          blockName  The block to insert.
 * @param {Object}                          attributes Attributes for the block.
 * @return {Promise<void>}
 */
async function insertIntoFields(page, blockName, attributes = {}) {
	await page.evaluate(
		([name, attrs]) => {
			const { select, dispatch } = window.wp.data;
			const { createBlock, createBlocksFromInnerBlocksTemplate, getBlockVariations } =
				window.wp.blocks;

			const findWrapper = (blocks) => {
				for (const block of blocks) {
					if (block.name === 'osf/form-fields') {
						return block.clientId;
					}
					const found = findWrapper(block.innerBlocks || []);
					if (found) {
						return found;
					}
				}
				return null;
			};

			const rootClientId = findWrapper(select('core/block-editor').getBlocks());

			// Mirror what the inserter does with a default variation: the block
			// is created with the variation's innerBlocks, which is what makes
			// the starting options part of the saved block.
			const variation = (getBlockVariations(name, 'inserter') || []).find(
				(item) => item.isDefault,
			);
			const innerBlocks = variation?.innerBlocks
				? createBlocksFromInnerBlocksTemplate(variation.innerBlocks)
				: [];

			return dispatch('core/block-editor').insertBlocks(
				[createBlock(name, { ...(variation?.attributes || {}), ...attrs }, innerBlocks)],
				undefined,
				rootClientId,
			);
		},
		[blockName, attributes],
	);
}

test.describe('Field blocks in the editor', () => {
	test.beforeEach(async ({ admin, page }) => {
		await admin.createNewPost();

		// Wait for the editor to finish initializing: edits made before the
		// initial post load settles are wiped by the store reset.
		await page.waitForFunction(() =>
			window.wp?.data?.select('core/editor')?.__unstableIsEditorReady?.(),
		);
	});

	test('an input field renders its control', async ({ editor }) => {
		await editor.setContent(
			formMarkup('<!-- wp:osf/field-input {"fieldId":1,"type":"email","label":"Email"} /-->'),
		);

		await expect(editor.canvas.locator('.osf-field-input input')).toBeVisible();
	});

	test('a textarea field renders its control', async ({ editor }) => {
		await editor.setContent(
			formMarkup('<!-- wp:osf/field-textarea {"fieldId":1,"label":"Message"} /-->'),
		);

		await expect(editor.canvas.locator('.osf-field-textarea textarea')).toBeVisible();
	});

	test('a consent field renders a single checkbox', async ({ editor }) => {
		await editor.setContent(
			formMarkup('<!-- wp:osf/field-consent {"fieldId":1,"label":"I agree"} /-->'),
		);

		await expect(
			editor.canvas.locator('.osf-field-consent input[type="checkbox"]'),
		).toHaveCount(1);
	});

	for (const [blockName, display] of [
		['osf/field-select', 'select'],
		['osf/field-radio', 'radio'],
		['osf/field-checkbox', 'checkbox'],
	]) {
		test(`${blockName} renders the options authored as children`, async ({ editor }) => {
			await editor.setContent(
				formMarkup(
					`<!-- wp:${blockName} {"fieldId":1,"label":"Pick"} -->` +
						'<!-- wp:osf/field-option {"label":"One","value":"1"} /-->' +
						'<!-- wp:osf/field-option {"label":"Two","value":"2"} /-->' +
						`<!-- /wp:${blockName} -->`,
				),
			);

			const canvas = editor.canvas;

			await expect(canvas.locator(`[data-type="${blockName}"]`)).toBeVisible();
			await expect(canvas.locator('[data-type="osf/field-option"]')).toHaveCount(2);
			await expect(canvas.locator(`.osf-field__choices--${display}`)).toBeVisible();
			await expect(canvas.locator('.osf-option__label').first()).toHaveText('One');
		});

		test(`${blockName} seeds two options when freshly inserted`, async ({ editor, page }) => {
			await editor.setContent(formMarkup(''));
			await insertIntoFields(page, blockName, { fieldId: 1, label: 'Pick' });

			// A newly inserted field starts with options, so an author never
			// meets an empty control.
			await expect(editor.canvas.locator('[data-type="osf/field-option"]')).toHaveCount(2);
		});

		test(`${blockName} gets an option back when saved without any`, async ({ editor }) => {
			await editor.setContent(
				formMarkup(`<!-- wp:${blockName} {"fieldId":1,"label":"Pick"} /-->`),
			);

			// A field with nothing to choose from renders an empty control, so
			// it is repaired rather than left broken.
			await expect(editor.canvas.locator('[data-type="osf/field-option"]')).toHaveCount(1);
		});
	}

	test('a radio option renders as a radio and a checkbox option as a checkbox', async ({
		editor,
	}) => {
		await editor.setContent(
			formMarkup(
				'<!-- wp:osf/field-radio {"fieldId":1,"label":"Size"} -->' +
					'<!-- wp:osf/field-option {"label":"Small","value":"s"} /-->' +
					'<!-- /wp:osf/field-radio -->' +
					'<!-- wp:osf/field-checkbox {"fieldId":2,"label":"Topics"} -->' +
					'<!-- wp:osf/field-option {"label":"News","value":"news"} /-->' +
					'<!-- /wp:osf/field-checkbox -->',
			),
		);

		const canvas = editor.canvas;

		await expect(canvas.locator('.osf-option--radio input[type="radio"]')).toHaveCount(1);
		await expect(canvas.locator('.osf-option--checkbox input[type="checkbox"]')).toHaveCount(1);
	});

	test('a choice field with every option removed gets one back', async ({ editor, page }) => {
		await editor.setContent(formMarkup(''));
		await insertIntoFields(page, 'osf/field-select', { fieldId: 1, label: 'Country' });

		await expect(editor.canvas.locator('[data-type="osf/field-option"]')).toHaveCount(2);

		// Remove every option, leaving the field with nothing to offer.
		await page.evaluate(() => {
			const { select, dispatch } = window.wp.data;

			const findOptions = (blocks, out = []) => {
				for (const block of blocks) {
					if (block.name === 'osf/field-option') {
						out.push(block.clientId);
					}
					findOptions(block.innerBlocks || [], out);
				}
				return out;
			};

			return dispatch('core/block-editor').removeBlocks(
				findOptions(select('core/block-editor').getBlocks()),
			);
		});

		// Emptied by hand, refilled automatically: the field always has
		// something to offer.
		await expect(editor.canvas.locator('[data-type="osf/field-option"]')).toHaveCount(1);
	});

	test('the submit block seeds a consent box above the buttons', async ({ editor }) => {
		await editor.setContent(
			`<!-- wp:osf/form {"formId":"e2e-consent-template"} -->
<!-- wp:osf/form-submit /-->
<!-- /wp:osf/form -->`,
		);

		const blocks = await editor.getBlocks();
		const submit = blocks[0].innerBlocks[0];

		expect(submit.name).toBe('osf/form-submit');
		expect(submit.innerBlocks.map((block) => block.name)).toEqual([
			'osf/field-consent',
			'core/buttons',
		]);
		expect(submit.innerBlocks[0].attributes.required).toBe(true);
	});

	test('the fields wrapper pushes the field blocks to the top of the inserter, text first', async ({
		editor,
		page,
	}) => {
		await editor.setContent(
			formMarkup('<!-- wp:osf/field-input {"fieldId":1,"label":"Email"} /-->'),
		);

		await editor.canvas.locator('[data-type="osf/form-fields"]').click();

		const prioritized = await page.evaluate(() => {
			const { getBlockRootClientId, getBlockName, getBlocks, getSettings } =
				window.wp.data.select('core/block-editor');

			const findWrapper = (blocks) => {
				for (const block of blocks) {
					if (block.name === 'osf/form-fields') {
						return block.clientId;
					}
					const found = findWrapper(block.innerBlocks || []);
					if (found) {
						return found;
					}
				}
				return null;
			};

			const clientId = findWrapper(getBlocks());

			return window.wp.data.select('core/block-editor').getBlockListSettings(clientId)
				?.prioritizedInserterBlocks;
		});

		// Ordered, not merely present: the inserter shows these first, in this
		// order, and the text field has to lead.
		expect(prioritized[0]).toBe('osf/field-input/text');
		expect(prioritized.slice(0, 4)).toEqual([
			'osf/field-input/text',
			'osf/field-input/email',
			'osf/field-input/tel',
			'osf/field-input/url',
		]);
		expect(prioritized).toContain('osf/field-select');
		expect(prioritized).toContain('osf/field-consent');
	});

	test('seeded options survive a save', async ({ editor, page }) => {
		await editor.setContent(formMarkup(''));
		await insertIntoFields(page, 'osf/field-select', { fieldId: 1, label: 'Country' });

		// Present in the editor…
		await expect(editor.canvas.locator('[data-type="osf/field-option"]')).toHaveCount(2);

		await editor.saveDraft();

		// …and still present in what the server stores. A template applied at
		// render time is a non-persistent change: it redraws every load and can
		// leave the saved block empty, which renders an empty control on the
		// front end.
		const content = await page.evaluate(async () => {
			const { getCurrentPostId, getCurrentPostType } = window.wp.data.select('core/editor');
			const post = await window.wp.apiFetch({
				path: `/wp/v2/${getCurrentPostType()}s/${getCurrentPostId()}?context=edit`,
			});

			return post.content.raw;
		});

		expect(content).toContain('osf/field-option');
	});

	test('an option block is restricted to the choice fields', async ({ page }) => {
		const parents = await page.evaluate(
			() => window.wp.blocks.getBlockType('osf/field-option').parent,
		);

		expect(parents).toEqual(['osf/field-select', 'osf/field-radio', 'osf/field-checkbox']);
	});
});
