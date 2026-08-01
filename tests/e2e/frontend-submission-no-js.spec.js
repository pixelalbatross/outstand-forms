/* eslint-disable import/no-extraneous-dependencies */
/**
 * WordPress dependencies
 */
const { test, expect } = require('@wordpress/e2e-test-utils-playwright');

// Mirrors the constants in includes/FormSubmission.php: the query args the
// template_redirect handler adds to the Post/Redirect/Get target.
const SUCCESS_ARG = 'osf-success';
const STATE_ARG = 'osf-state';

const SUBMIT_BUTTON_MARKUP = `<!-- wp:buttons {"className":"wp-block-osf-form-submit__buttons"} -->
<div class="wp-block-buttons wp-block-osf-form-submit__buttons"><!-- wp:button {"tagName":"button","type":"submit","className":"wp-block-osf-form-submit__button"} -->
<div class="wp-block-button wp-block-osf-form-submit__button"><button type="submit" class="wp-block-button__link wp-element-button">Submit</button></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->`;

/**
 * Build the block markup for a complete test form.
 *
 * @param {string} formId The form ID.
 * @return {string} The block markup.
 */
function formMarkup(formId) {
	return `<!-- wp:osf/form {"formId":"${formId}"} -->
<!-- wp:osf/form-errors /-->
<!-- wp:osf/form-fields -->
<!-- wp:osf/field-input {"fieldId":1,"type":"email","required":true,"label":"Email"} /-->
<!-- wp:osf/field-input {"fieldId":2,"type":"text","pattern":"[0-9]+","label":"Code"} /-->
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
 * With JS unavailable the Interactivity runtime never hydrates, so the
 * Post/Redirect/Get round trip through `includes/FormSubmission.php` is
 * exercised exactly as a no-JS visitor's browser would drive it: a native
 * form POST followed by a plain GET.
 *
 * @param {import('@playwright/test').Browser} browser The Playwright browser.
 * @param {string}                             url     The URL to visit.
 * @return {Promise<{context: import('@playwright/test').BrowserContext, page: import('@playwright/test').Page}>} The context/page pair. Caller is responsible for closing the context.
 */
async function openWithoutJs(browser, url) {
	const context = await browser.newContext({ javaScriptEnabled: false });
	const page = await context.newPage();
	await page.goto(url);

	return { context, page };
}

test.describe('Frontend form submission without JavaScript', () => {
	let postLink;

	test.beforeAll(async ({ requestUtils }) => {
		const post = await requestUtils.createPost({
			title: 'E2E Form No JS',
			status: 'publish',
			content: formMarkup('e2e-form-nojs'),
		});
		postLink = post.link;
	});

	test.afterAll(async ({ requestUtils }) => {
		await requestUtils.deleteAllPosts();
	});

	test('novalidate is added by JavaScript but absent from the server-rendered markup', async ({
		page,
		browser,
	}) => {
		const { context: noJsContext, page: noJsPage } = await openWithoutJs(browser, postLink);

		// A no-JS visitor never runs `callbacks.initNoValidate`, so the browser's
		// own constraint validation must still be in play.
		expect(
			await noJsPage.locator('form.wp-block-osf-form').getAttribute('novalidate'),
		).toBeNull();

		await noJsContext.close();

		// With JavaScript available, the Interactivity init callback adds it so
		// the client-side validator becomes the sole source of truth.
		await page.goto(postLink);
		await expect(page.locator('form.wp-block-osf-form')).toHaveAttribute('novalidate', '');
	});

	test('a valid submission succeeds, redirects, and hides the fields', async ({ browser }) => {
		const { context, page } = await openWithoutJs(browser, postLink);

		const form = page.locator('form.wp-block-osf-form');
		await form.locator('input[name="field_1"]').fill('user@example.com');
		await form.locator('input[name="field_2"]').fill('12345');

		await Promise.all([
			page.waitForNavigation(),
			form.locator('button[type="submit"]').click(),
		]);

		const redirectUrl = new URL(page.url());
		expect(redirectUrl.searchParams.get(SUCCESS_ARG)).toBe('e2e-form-nojs');
		expect(redirectUrl.searchParams.has(STATE_ARG)).toBe(false);

		await expect(form.locator('.wp-block-osf-form-message')).toBeVisible();
		await expect(form.locator('.wp-block-osf-form-message')).toContainText(
			'Thanks for your submission!',
		);
		await expect(form.locator('.wp-block-osf-form-fields')).toBeHidden();
		await expect(form.locator('.wp-block-osf-form-submit')).toBeHidden();

		await context.close();
	});

	test('an invalid submission redirects with a state token, surfaces errors, and repopulates values', async ({
		browser,
	}) => {
		const { context, page } = await openWithoutJs(browser, postLink);

		const form = page.locator('form.wp-block-osf-form');

		// Leave the required email empty. With JS disabled the browser's native
		// constraint validation (backed by the very same `required` attribute)
		// would normally block this before it ever reaches the server, so
		// `.submit()` is used instead of clicking the button: unlike a real
		// click or `.requestSubmit()`, the plain DOM `.submit()` method bypasses
		// constraint validation entirely. That simulates a client that, for
		// whatever reason, didn't enforce it — exactly the case the server-side
		// pipeline exists to guard against.
		await form.locator('input[name="field_2"]').fill('12345');
		await Promise.all([page.waitForNavigation(), form.evaluate((node) => node.submit())]);

		const redirectUrl = new URL(page.url());
		expect(redirectUrl.searchParams.has(SUCCESS_ARG)).toBe(false);

		const token = redirectUrl.searchParams.get(STATE_ARG);
		expect(token).toMatch(/^[A-Za-z0-9]{32}$/);

		await expect(form.locator('#osf-error-1')).toHaveText('This field is required.');
		await expect(form.locator('input[name="field_1"]')).toHaveValue('');

		// Every field's submitted value is round-tripped, not only the invalid
		// one, so the visitor never has to retype what they already got right.
		await expect(form.locator('input[name="field_2"]')).toHaveValue('12345');

		await context.close();
	});

	test('a refresh after a successful submission does not resubmit the form', async ({
		browser,
	}) => {
		const { context, page } = await openWithoutJs(browser, postLink);

		let postRequests = 0;
		page.on('request', (request) => {
			if (request.method() === 'POST') {
				postRequests++;
			}
		});

		const form = page.locator('form.wp-block-osf-form');
		await form.locator('input[name="field_1"]').fill('user@example.com');
		await form.locator('input[name="field_2"]').fill('12345');

		await Promise.all([
			page.waitForNavigation(),
			form.locator('button[type="submit"]').click(),
		]);

		expect(postRequests).toBe(1);
		await expect(form.locator('.wp-block-osf-form-message')).toBeVisible();

		// The redirect target is a GET, so a reload cannot replay the POST body:
		// this is the entire point of Post/Redirect/Get.
		await page.reload();

		expect(postRequests).toBe(1);
		await expect(form.locator('.wp-block-osf-form-message')).toBeVisible();

		await context.close();
	});
});
