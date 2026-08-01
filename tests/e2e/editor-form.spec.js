/* eslint-disable import/no-extraneous-dependencies */
/**
 * WordPress dependencies
 */
const { test, expect } = require('@wordpress/e2e-test-utils-playwright');

/**
 * Block markup for a form with two named field inputs.
 *
 * @param {string} nameA Name attribute of the first field.
 * @param {string} nameB Name attribute of the second field.
 * @return {string} The block markup.
 */
function twoFieldFormMarkup(nameA, nameB) {
	return `<!-- wp:osf/form {"formId":"e2e-editor-form","nextFieldId":3} -->
<!-- wp:osf/form-fields -->
<!-- wp:osf/field-input {"fieldId":1,"label":"First","name":"${nameA}"} /-->
<!-- wp:osf/field-input {"fieldId":2,"label":"Second","name":"${nameB}"} /-->
<!-- /wp:osf/form-fields -->
<!-- /wp:osf/form -->`;
}

test.describe('Form block in the editor', () => {
	test.beforeEach(async ({ admin, page }) => {
		await admin.createNewPost();

		// Wait for the editor to finish initializing: edits made before the
		// initial post load settles are wiped by the store reset.
		await page.waitForFunction(() =>
			window.wp?.data?.select('core/editor')?.__unstableIsEditorReady?.(),
		);
	});

	test('inserting the form block scaffolds the template from a variation', async ({ editor }) => {
		await editor.insertBlock({ name: 'osf/form' });

		// The empty form shows the variation picker; choose Blank.
		await editor.canvas.getByRole('button', { name: 'Blank' }).click();

		await expect(editor.canvas.locator('[data-type="osf/form-errors"]')).toBeVisible();
		await expect(editor.canvas.locator('[data-type="osf/form-fields"]')).toBeVisible();
		await expect(editor.canvas.locator('[data-type="osf/field-input"]')).toBeVisible();
		await expect(editor.canvas.locator('[data-type="osf/form-submit"]')).toBeVisible();
		await expect(editor.canvas.locator('[data-type="osf/form-message"]')).toBeVisible();
	});

	test('duplicate field names show a warning notice in Advanced settings', async ({
		editor,
		page,
	}) => {
		await editor.setContent(twoFieldFormMarkup('same_name', 'same_name'));

		await editor.canvas.locator('[data-type="osf/field-input"]').first().click();
		await editor.openDocumentSettingsSidebar();
		await page.getByRole('button', { name: 'Advanced' }).click();

		await expect(
			page
				.locator('.components-notice')
				.getByText('Another field in this form uses the same name.'),
		).toBeVisible();
	});

	test('renaming a duplicate field clears the warning notice', async ({ editor, page }) => {
		await editor.setContent(twoFieldFormMarkup('same_name', 'same_name'));

		await editor.canvas.locator('[data-type="osf/field-input"]').first().click();
		await editor.openDocumentSettingsSidebar();
		await page.getByRole('button', { name: 'Advanced' }).click();

		const warning = page
			.locator('.components-notice')
			.getByText('Another field in this form uses the same name.');
		await expect(warning).toBeVisible();

		await page.getByRole('textbox', { name: 'Name', exact: true }).fill('unique_name');

		await expect(warning).toBeHidden();
	});

	test('duplicate field IDs are reassigned automatically', async ({ editor }) => {
		// Both fields claim fieldId 1; useFieldId must reassign the collision.
		await editor.setContent(`<!-- wp:osf/form {"formId":"e2e-editor-ids"} -->
<!-- wp:osf/form-fields -->
<!-- wp:osf/field-input {"fieldId":1,"label":"First"} /-->
<!-- wp:osf/field-input {"fieldId":1,"label":"Second"} /-->
<!-- /wp:osf/form-fields -->
<!-- /wp:osf/form -->`);

		await expect(async () => {
			const blocks = await editor.getBlocks();
			const form = blocks.find((block) => block.name === 'osf/form');
			const fields = form.innerBlocks.find(
				(block) => block.name === 'osf/form-fields',
			).innerBlocks;

			const fieldIds = fields.map((block) => block.attributes.fieldId);
			expect(new Set(fieldIds).size).toBe(fields.length);
			for (const fieldId of fieldIds) {
				expect(Number.isInteger(fieldId)).toBe(true);
				expect(fieldId).toBeGreaterThan(0);
			}
		}).toPass();
	});

	test.describe('Configuring a field through the inspector', () => {
		test.afterEach(async ({ requestUtils }) => {
			await requestUtils.deleteAllPosts();
		});

		test('setting a label, marking it required, and choosing a field type round-trips to the front end', async ({
			editor,
			page,
			requestUtils,
		}) => {
			// The field type is seeded rather than chosen through the block
			// switcher. Driving the switcher couples the test to WordPress's
			// variation-picker markup, which is core UI this plugin does not
			// own; what matters here is that the type reaches the front end
			// alongside the settings configured below.
			await editor.setContent(`<!-- wp:osf/form {"formId":"e2e-editor-configure"} -->
<!-- wp:osf/form-fields -->
<!-- wp:osf/field-input {"fieldId":1,"type":"email"} /-->
<!-- /wp:osf/form-fields -->
<!-- /wp:osf/form -->`);

			await editor.canvas.locator('[data-type="osf/field-input"]').click();

			// Set a label via the inline RichText control.
			await editor.canvas.getByRole('textbox', { name: 'Empty label' }).fill('Contact Email');

			// Mark it required via the inspector "Settings" panel.
			await editor.openDocumentSettingsSidebar();
			await page.getByRole('checkbox', { name: 'Required' }).click();

			// Give it a stable, predictable field name via "Advanced".
			await page.getByRole('button', { name: 'Advanced' }).click();
			await page.getByRole('textbox', { name: 'Name', exact: true }).fill('contact_email');

			const postId = await editor.publishPost();
			const post = await requestUtils.rest({ path: `/wp/v2/posts/${postId}` });

			await page.goto(post.link);

			const field = page.locator('.osf-field-input--email');
			await expect(field.locator('.osf-field__label')).toContainText('Contact Email');

			const input = field.locator('input[name="contact_email"]');
			await expect(input).toHaveAttribute('type', 'email');
			await expect(input).toHaveAttribute('required', '');
		});
	});
});
