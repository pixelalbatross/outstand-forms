/* eslint-disable import/no-extraneous-dependencies */
/**
 * WordPress dependencies
 */
const { test, expect } = require('@wordpress/e2e-test-utils-playwright');

const SUBMIT_BUTTON_MARKUP = `<!-- wp:buttons {"className":"wp-block-osf-form-submit__buttons"} -->
<div class="wp-block-buttons wp-block-osf-form-submit__buttons"><!-- wp:button {"tagName":"button","type":"submit","className":"wp-block-osf-form-submit__button"} -->
<div class="wp-block-button wp-block-osf-form-submit__button"><button type="submit" class="wp-block-button__link wp-element-button">Submit</button></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->`;

// The `osf/field-turnstile` block lives inside `osf/form-submit` (its
// `ancestor` is `osf/form-submit`, not `osf/form-fields`; see
// includes/Blocks/FieldTurnstile.php and src/blocks/field-turnstile/block.json).
const FORM_MARKUP = `<!-- wp:osf/form {"formId":"e2e-turnstile"} -->
<!-- wp:osf/form-fields -->
<!-- wp:osf/field-input {"fieldId":1,"type":"email","required":true,"label":"Email"} /-->
<!-- /wp:osf/form-fields -->
<!-- wp:osf/form-submit -->
<!-- wp:osf/field-turnstile /-->
${SUBMIT_BUTTON_MARKUP}
<!-- /wp:osf/form-submit -->
<!-- wp:osf/form-message -->
<!-- wp:paragraph -->
<p>Thanks for your submission!</p>
<!-- /wp:paragraph -->
<!-- /wp:osf/form-message -->
<!-- /wp:osf/form -->`;

test.describe('Turnstile field when unconfigured', () => {
	let postLink;

	test.beforeAll(async ({ requestUtils }) => {
		// The e2e mu-plugin forces `outstand_forms_settings` to empty site/secret
		// keys, so this suite is deterministic regardless of what a developer
		// may have configured locally (see tests/e2e/mu-plugins/outstand-forms-e2e.php).
		const post = await requestUtils.createPost({
			title: 'E2E Turnstile Unconfigured',
			status: 'publish',
			content: FORM_MARKUP,
		});
		postLink = post.link;
	});

	test.afterAll(async ({ requestUtils }) => {
		await requestUtils.deleteAllPosts();
	});

	test('the widget does not render when Turnstile is unconfigured', async ({ page }) => {
		await page.goto(postLink);

		// render.php returns early without a site key, so there is nothing to
		// hydrate: not even the hidden `cf-turnstile-response` input exists.
		await expect(page.locator('.cf-turnstile')).toHaveCount(0);
	});

	test('a form containing the Turnstile block still submits successfully', async ({ page }) => {
		await page.goto(postLink);

		const form = page.locator('form.wp-block-osf-form');
		await form.locator('input[name="field_1"]').fill('user@example.com');
		await form.locator('button[type="submit"]').click();

		// FieldTurnstile::verify_form_submission() short-circuits when the keys
		// are missing, so the absent widget never blocks the visitor.
		await expect(form.locator('.wp-block-osf-form-message')).toBeVisible();
		await expect(form.locator('.wp-block-osf-form-message')).toContainText(
			'Thanks for your submission!',
		);
		await expect(form.locator('.wp-block-osf-form-submit')).toBeHidden();
	});
});
