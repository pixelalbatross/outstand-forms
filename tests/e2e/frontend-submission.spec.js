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
 * Whether a URL targets the form submit REST endpoint.
 *
 * Handles both pretty permalinks and plain `?rest_route=` URLs.
 *
 * @param {URL} url The request URL.
 * @return {boolean} True when the URL is the submit endpoint.
 */
function isSubmitEndpoint(url) {
	return decodeURIComponent(url.href).includes('outstand-forms/v1/forms/submit');
}

test.describe('Frontend form submission', () => {
	let postLink;

	test.beforeAll(async ({ requestUtils }) => {
		const post = await requestUtils.createPost({
			title: 'E2E Form',
			status: 'publish',
			content: formMarkup('e2e-form'),
		});
		postLink = post.link;
	});

	test.afterAll(async ({ requestUtils }) => {
		await requestUtils.deleteAllPosts();
	});

	test('client validation blocks submission and marks fields', async ({ page }) => {
		let submitRequests = 0;
		page.on('request', (request) => {
			if (isSubmitEndpoint(new URL(request.url()))) {
				submitRequests++;
			}
		});

		await page.goto(postLink);

		const form = page.locator('form.wp-block-osf-form');
		const emailInput = form.locator('input[name="field_1"]');
		const emailError = form.locator('#osf-error-1');

		await form.locator('button[type="submit"]').click();

		await expect(emailError).toHaveText('This field is required.');
		await expect(emailInput).toHaveAttribute('aria-invalid', 'true');
		await expect(emailInput).toHaveAttribute('aria-describedby', /osf-error-1/);
		await expect(form.locator('.osf-field').first()).toHaveClass(/is-invalid/);

		expect(submitRequests).toBe(0);
	});

	test('valid submission succeeds against the real endpoint', async ({ page }) => {
		await page.goto(postLink);

		const form = page.locator('form.wp-block-osf-form');
		await form.locator('input[name="field_1"]').fill('user@example.com');
		await form.locator('input[name="field_2"]').fill('12345');
		await form.locator('button[type="submit"]').click();

		await expect(form.locator('.wp-block-osf-form-message')).toBeVisible();
		await expect(form.locator('.wp-block-osf-form-message')).toContainText(
			'Thanks for your submission!',
		);
		await expect(form.locator('.wp-block-osf-form-submit')).toBeHidden();
	});

	test('server-side errors surface per field and clear on resubmit', async ({ page }) => {
		await page.route(
			(url) => isSubmitEndpoint(url),
			async (route) => {
				await route.fulfill({
					status: 400,
					contentType: 'application/json',
					body: JSON.stringify({
						code: 'validation_failed',
						message: 'Form validation failed.',
						data: { status: 400, errors: { field_1: ['email'] } },
					}),
				});
			},
		);

		await page.goto(postLink);

		const form = page.locator('form.wp-block-osf-form');
		const emailInput = form.locator('input[name="field_1"]');
		const alert = form.locator('.wp-block-osf-form-errors[role="alert"]');

		// Client-side valid so the request actually fires.
		await emailInput.fill('user@example.com');
		await form.locator('input[name="field_2"]').fill('12345');
		await form.locator('button[type="submit"]').click();

		await expect(alert).toBeVisible();
		await expect(alert).toHaveText(
			'There was a problem submitting the form. Please try again.',
		);
		await expect(form.locator('#osf-error-1')).toHaveText(
			'Please enter a valid email address.',
		);
		await expect(emailInput).toHaveAttribute('aria-invalid', 'true');

		// Let the next submission through to the real endpoint.
		await page.unrouteAll();

		await emailInput.fill('corrected@example.com');
		await form.locator('button[type="submit"]').click();

		await expect(form.locator('.wp-block-osf-form-message')).toBeVisible();
		await expect(alert).toBeHidden();
		await expect(emailInput).toHaveAttribute('aria-invalid', 'false');
	});

	test('double submit sends a single request and shows the spinner', async ({ page }) => {
		let submitRequests = 0;
		await page.route(
			(url) => isSubmitEndpoint(url),
			async (route) => {
				submitRequests++;
				await new Promise((resolve) => {
					setTimeout(resolve, 600);
				});
				await route.fulfill({
					status: 200,
					contentType: 'application/json',
					body: JSON.stringify({ success: true, message: 'ok' }),
				});
			},
		);

		await page.goto(postLink);

		const form = page.locator('form.wp-block-osf-form');
		await form.locator('input[name="field_1"]').fill('user@example.com');
		await form.locator('input[name="field_2"]').fill('12345');
		await form.locator('button[type="submit"]').click();

		// Pending state: spinner class on the form, second submit is a no-op.
		await expect(form).toHaveClass(/is-submitting/);
		await form.evaluate((node) => node.requestSubmit());

		await expect(form.locator('.wp-block-osf-form-message')).toBeVisible();
		await expect(form).not.toHaveClass(/is-submitting/);
		expect(submitRequests).toBe(1);
	});

	test('a field that never initializes does not stall submission', async ({ page }) => {
		// Serve the page with every Interactivity directive stripped from the
		// second field's control, so the runtime never enhances it: it never
		// registers, and nothing on it can respond to the form. Validation is
		// a synchronous loop over the form-owned registry, so an unregistered
		// field is simply skipped and submission cannot be left waiting.
		await page.route(postLink, async (route) => {
			const response = await route.fetch();
			const body = (await response.text()).replace(/<input[^>]*name="field_2"[^>]*>/, (tag) =>
				tag.replace(/\sdata-wp-[\w-]+(="[^"]*")?/g, ''),
			);

			await route.fulfill({ response, body });
		});

		await page.goto(postLink);

		const form = page.locator('form.wp-block-osf-form');
		const brokenInput = form.locator('input[name="field_2"]');

		// Guard the fixture: the test is vacuous if the directives survived.
		await expect(brokenInput).not.toHaveAttribute('data-wp-init---register');
		await expect(brokenInput).not.toHaveAttribute('data-wp-on--input');

		await form.locator('input[name="field_1"]').fill('user@example.com');
		await brokenInput.fill('12345');

		const startedAt = Date.now();
		await form.locator('button[type="submit"]').click();

		// The remaining, healthy field still validates and the request still
		// goes out. The bound is well inside the 5s per-field wait the retired
		// event bridge imposed on any field that failed to answer it.
		await expect(form.locator('.wp-block-osf-form-message')).toBeVisible({ timeout: 3000 });
		await expect(form).not.toHaveClass(/is-submitting/);
		expect(Date.now() - startedAt).toBeLessThan(3000);
	});

	test('an unregistered field cannot mask a sibling failing validation', async ({ page }) => {
		await page.route(postLink, async (route) => {
			const response = await route.fetch();
			const body = (await response.text()).replace(/<input[^>]*name="field_2"[^>]*>/, (tag) =>
				tag.replace(/\sdata-wp-[\w-]+(="[^"]*")?/g, ''),
			);

			await route.fulfill({ response, body });
		});

		let submitRequests = 0;
		page.on('request', (request) => {
			if (isSubmitEndpoint(new URL(request.url()))) {
				submitRequests++;
			}
		});

		await page.goto(postLink);

		const form = page.locator('form.wp-block-osf-form');
		await form.locator('button[type="submit"]').click();

		// The required email is still enforced, promptly, and the broken
		// sibling neither blocks nor silently waves the submission through.
		await expect(form.locator('#osf-error-1')).toHaveText('This field is required.', {
			timeout: 3000,
		});
		await expect(form).not.toHaveClass(/is-submitting/);
		expect(submitRequests).toBe(0);
	});

	test('two forms on one page keep independent state', async ({ page, requestUtils }) => {
		const post = await requestUtils.createPost({
			title: 'E2E Two Forms',
			status: 'publish',
			content: `${formMarkup('e2e-form-a')}\n${formMarkup('e2e-form-b')}`,
		});

		await page.goto(post.link);

		const formA = page.locator('#osf-e2e-form-a');
		const formB = page.locator('#osf-e2e-form-b');

		await formA.locator('input[name="field_1"]').fill('user@example.com');
		await formA.locator('input[name="field_2"]').fill('12345');
		await formA.locator('button[type="submit"]').click();

		await expect(formA.locator('.wp-block-osf-form-message')).toBeVisible();
		await expect(formA.locator('.wp-block-osf-form-submit')).toBeHidden();

		// Form B is untouched: submit still visible, message hidden, no errors.
		await expect(formB.locator('.wp-block-osf-form-submit')).toBeVisible();
		await expect(formB.locator('.wp-block-osf-form-message')).toBeHidden();
		await expect(formB.locator('.osf-field.is-invalid')).toHaveCount(0);
	});
});
