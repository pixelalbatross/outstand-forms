/* eslint-disable import/no-extraneous-dependencies */
/**
 * WordPress dependencies
 */
const { test, expect } = require('@wordpress/e2e-test-utils-playwright');

// Mirrors the constants in includes/FormSubmission.php.
const SUCCESS_ARG = 'osf-success';
const STATE_ARG = 'osf-state';

const SUBMIT_BUTTON_MARKUP = `<!-- wp:buttons {"className":"wp-block-osf-form-submit__buttons"} -->
<div class="wp-block-buttons wp-block-osf-form-submit__buttons"><!-- wp:button {"tagName":"button","type":"submit","className":"wp-block-osf-form-submit__button"} -->
<div class="wp-block-button wp-block-osf-form-submit__button"><button type="submit" class="wp-block-button__link wp-element-button">Submit</button></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->`;

/**
 * Build a form carrying one of each choice field.
 *
 * @param {string} formId The form ID.
 * @return {string} The block markup.
 */
function formMarkup(formId) {
	return `<!-- wp:osf/form {"formId":"${formId}"} -->
<!-- wp:osf/form-errors /-->
<!-- wp:osf/form-fields -->
<!-- wp:osf/field-select {"fieldId":1,"name":"country","label":"Country","required":true,"placeholder":"Choose a country"} -->
<!-- wp:osf/field-option {"label":"Portugal","value":"pt"} /-->
<!-- wp:osf/field-option {"label":"Spain","value":"es"} /-->
<!-- /wp:osf/field-select -->
<!-- wp:osf/field-radio {"fieldId":2,"name":"size","label":"Size"} -->
<!-- wp:osf/field-option {"label":"Small","value":"s"} /-->
<!-- wp:osf/field-option {"label":"Large","value":"l"} /-->
<!-- /wp:osf/field-radio -->
<!-- wp:osf/field-checkbox {"fieldId":3,"name":"topics","label":"Topics","minSelected":2} -->
<!-- wp:osf/field-option {"label":"News","value":"news"} /-->
<!-- wp:osf/field-option {"label":"Offers","value":"offers"} /-->
<!-- wp:osf/field-option {"label":"Events","value":"events"} /-->
<!-- /wp:osf/field-checkbox -->
<!-- wp:osf/field-consent {"fieldId":4,"name":"terms","label":"I agree to the terms","required":true} /-->
<!-- /wp:osf/form-fields -->
<!-- wp:osf/form-submit -->
${SUBMIT_BUTTON_MARKUP}
<!-- /wp:osf/form-submit -->
<!-- wp:osf/form-message -->
<!-- wp:paragraph -->
<p>Thanks for your submission!</p>
<!-- /wp:paragraph -->
<!-- /wp:osf/form-message -->
<!-- /wp:osf/form -->`;
}

/**
 * Open the given URL in a fresh, JavaScript-disabled browser context.
 *
 * @param {import('@playwright/test').Browser} browser The Playwright browser.
 * @param {string}                             url     The URL to visit.
 * @return {Promise<{context: import('@playwright/test').BrowserContext, page: import('@playwright/test').Page}>} The context/page pair. Caller closes the context.
 */
async function openWithoutJs(browser, url) {
	const context = await browser.newContext({ javaScriptEnabled: false });
	const page = await context.newPage();
	await page.goto(url);

	return { context, page };
}

test.describe('Choice fields', () => {
	let postLink;

	test.beforeAll(async ({ requestUtils }) => {
		const post = await requestUtils.createPost({
			title: 'E2E Choice Fields',
			status: 'publish',
			content: formMarkup('e2e-choice'),
		});
		postLink = post.link;
	});

	test.afterAll(async ({ requestUtils }) => {
		await requestUtils.deleteAllPosts();
	});

	test('renders the options authored as child blocks', async ({ page }) => {
		await page.goto(postLink);

		const form = page.locator('form.wp-block-osf-form');

		// The select gets an empty first option because it is required, then
		// one per option block.
		await expect(form.locator('select[name="country"] option')).toHaveCount(3);
		await expect(form.locator('input[type="radio"][name="size"]')).toHaveCount(2);
		await expect(form.locator('input[type="checkbox"][name="topics[]"]')).toHaveCount(3);
		await expect(form.locator('input[type="checkbox"][name="terms"]')).toHaveCount(1);
	});

	test('names a group through aria-labelledby rather than a label for', async ({ page }) => {
		await page.goto(postLink);

		const form = page.locator('form.wp-block-osf-form');
		const group = form.locator('[role="radiogroup"]');

		await expect(group).toHaveAttribute('aria-labelledby', 'osf-label-2');
		await expect(form.locator('#osf-label-2')).toHaveText(/Size/);

		// The checkbox group is a set of independent boxes, not one control.
		await expect(form.locator('.osf-field-checkbox [role="group"]')).toHaveCount(1);
	});

	test('a checkbox group submits every ticked value without JavaScript', async ({ browser }) => {
		const { context, page } = await openWithoutJs(browser, postLink);

		const form = page.locator('form.wp-block-osf-form');
		await form.locator('select[name="country"]').selectOption('pt');
		await form.locator('input[value="news"]').check();
		await form.locator('input[value="events"]').check();
		await form.locator('input[name="terms"]').check();

		await Promise.all([
			page.waitForNavigation(),
			form.locator('button[type="submit"]').click(),
		]);

		const redirectUrl = new URL(page.url());
		expect(redirectUrl.searchParams.get(SUCCESS_ARG)).toBe('e2e-choice');

		await expect(form.locator('.wp-block-osf-form-message')).toContainText(
			'Thanks for your submission!',
		);

		await context.close();
	});

	test('minSelected is enforced server-side and the choices are replayed', async ({
		browser,
	}) => {
		const { context, page } = await openWithoutJs(browser, postLink);

		const form = page.locator('form.wp-block-osf-form');
		await form.locator('select[name="country"]').selectOption('es');
		await form.locator('input[value="news"]').check();
		await form.locator('input[name="terms"]').check();

		// One box ticked against a minimum of two. No HTML attribute expresses
		// this, so the browser lets it through and the server has to catch it.
		await Promise.all([
			page.waitForNavigation(),
			form.locator('button[type="submit"]').click(),
		]);

		const redirectUrl = new URL(page.url());
		expect(redirectUrl.searchParams.get(SUCCESS_ARG)).toBeNull();
		expect(redirectUrl.searchParams.has(STATE_ARG)).toBe(true);

		await expect(form.locator('#osf-error-3')).toContainText('at least 2');

		// What the visitor chose survives the round trip.
		await expect(form.locator('select[name="country"]')).toHaveValue('es');
		await expect(form.locator('input[value="news"]')).toBeChecked();
		await expect(form.locator('input[value="offers"]')).not.toBeChecked();

		await context.close();
	});

	test('a value no option offers is rejected', async ({ browser }) => {
		const { context, page } = await openWithoutJs(browser, postLink);

		const form = page.locator('form.wp-block-osf-form');

		// Forge an option the field never rendered.
		await form.locator('select[name="country"]').evaluate((select) => {
			const forged = document.createElement('option');
			forged.value = 'fr';
			select.appendChild(forged);
			select.value = 'fr';
		});
		await form.locator('input[value="news"]').check();
		await form.locator('input[value="events"]').check();
		await form.locator('input[name="terms"]').check();

		await Promise.all([
			page.waitForNavigation(),
			form.locator('button[type="submit"]').click(),
		]);

		expect(new URL(page.url()).searchParams.get(SUCCESS_ARG)).toBeNull();
		await expect(form.locator('#osf-error-1')).toContainText('valid option');

		await context.close();
	});

	test('ticking boxes with JavaScript clears the error the server set', async ({ page }) => {
		await page.goto(postLink);

		const form = page.locator('form.wp-block-osf-form');
		await form.locator('select[name="country"]').selectOption('pt');
		await form.locator('input[value="news"]').check();
		await form.locator('input[name="terms"]').check();

		await form.locator('button[type="submit"]').click();

		// Client-side validation runs before the request, so minSelected fails
		// without a round trip.
		await expect(form.locator('#osf-error-3')).toContainText('at least 2');

		await form.locator('input[value="offers"]').check();
		await form.locator('button[type="submit"]').click();

		await expect(form.locator('.wp-block-osf-form-message')).toBeVisible();
	});
});
