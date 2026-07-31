# E2E Test Backlog

P1 suite is implemented in this directory. The following cases are deferred.

## P2 — worth having

- [ ] **Message interpolation** — trigger a `minLength` violation and assert the rendered
      message has `{{min}}` replaced with the rule value.
- [ ] **Non-400 failure recovery** — route the submit endpoint to a 500, assert the generic
      error shows and the form stays recoverable (a follow-up valid submission succeeds).
- [ ] **Rate limit UX** — route the submit endpoint to a 429 and assert the generic error
      message is shown. (The real limiter is covered by `RestFormsTest`.)
- [ ] **Input mask** — type into a masked field and assert the formatted value; also assert
      the Inputmask chunk is NOT requested on forms without any mask (lazy-load contract).
- [ ] **Email notification capture** — extend `tests/e2e/mu-plugins/outstand-forms-e2e.php`
      to capture `pre_wp_mail` into an option; submit a form with an email action configured
      and assert the capture (recipient/subject) via the REST API. No SMTP needed.
- [ ] **Keyboard-only run** — tab through the form, trigger a validation error, assert the
      `role="alert"` container updates and focus is neither trapped nor lost.

## P3 — conditional (add when the feature area changes)

- [ ] **Turnstile** — seed Cloudflare's permanent test keys (always-pass sitekey `1x00000000000000000000AA`
      + matching secret) into `outstand_forms_settings`, assert the widget renders and the hidden
      `cf-turnstile-response` input populates, and the submission passes; flip to the always-block
      key and assert rejection. Flakiest suite — only worth it when Turnstile code changes.
- [ ] **Editor allowed-blocks restriction** — the inserter inside `osf/form-fields` offers only
      the permitted blocks (`outstandForms.form.allowedBlocks` contract).

## Out of scope (decided)

- Visual/pixel assertions for the spinner — class/attribute checks in P1 suffice.
- Re-testing validation rule matrices in the browser — `tests/fixtures/validation-cases.json`
  already locks client/server parity; E2E tests wiring, not rules.
- Settings page CRUD — covered by `SettingsTest` (PHPUnit).
