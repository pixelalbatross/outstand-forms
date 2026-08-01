/* eslint-disable import/no-extraneous-dependencies */
/**
 * WordPress dependencies
 */
const { test, expect } = require('@wordpress/e2e-test-utils-playwright');

// The mask is applied by a chunk that `callbacks.initMask` imports lazily.
// These tests assert the observable behaviour rather than the chunk's URL:
// webpack names split chunks numerically (`660.js`), so matching on a
// filename would break on any rebuild that renumbers them.
test.describe('Input mask', () => {
	test.afterEach(async ({ requestUtils }) => {
		await requestUtils.deleteAllPosts();
	});

	test('typing into a masked field formats the value', async ({ page, requestUtils }) => {
		const post = await requestUtils.createPost({
			title: 'E2E Input Mask',
			status: 'publish',
			content: `<!-- wp:osf/form {"formId":"e2e-mask"} -->
<!-- wp:osf/form-fields -->
<!-- wp:osf/field-input {"fieldId":1,"type":"tel","label":"Phone","mask":"(999) 999-9999"} /-->
<!-- /wp:osf/form-fields -->
<!-- /wp:osf/form -->`,
		});

		await page.goto(post.link);

		const input = page.locator('input[name="field_1"]');
		await input.click();
		await input.pressSequentially('5551234567');

		await expect(input).toHaveValue('(555) 123-4567');
	});

	test('a form without any masked field renders no mask payload or init directive', async ({
		page,
		requestUtils,
	}) => {
		const post = await requestUtils.createPost({
			title: 'E2E No Mask',
			status: 'publish',
			content: `<!-- wp:osf/form {"formId":"e2e-no-mask"} -->
<!-- wp:osf/form-fields -->
<!-- wp:osf/field-input {"fieldId":1,"type":"text","label":"Name"} /-->
<!-- /wp:osf/form-fields -->
<!-- /wp:osf/form -->`,
		});

		await page.goto(post.link);

		const input = page.locator('input[name="field_1"]');

		// Without a mask attribute the server omits both the mask payload and
		// the init directive, so the lazy import is never reached at all.
		await expect(input).not.toHaveAttribute('data-inputmask');
		await expect(input).not.toHaveAttribute('data-wp-init---mask');

		await input.click();
		await input.pressSequentially('hello');
		await expect(input).toHaveValue('hello');
	});
});
