# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Block-based form builder: a form container block with fields wrapper, submit wrapper, submission message, and submission error list blocks.
- Input and textarea field blocks with configurable label position, help text position, required indicator, placeholder, autocomplete, and input mask support.
- Client-side validation (required, email, url, pattern, minLength, maxLength, min, max) that mirrors server-side validation rules, so errors show instantly without a round trip.
- Server-side validation and sanitization for every submitted field, so a request cannot bypass the rules enforced in the browser.
- Cloudflare Turnstile spam protection block, with submission verification and automatic skipping when Turnstile is not configured.
- Per-IP rate limiting on form submissions, configurable via the `outstand_forms_rate_limit` filter.
- Email notifications sent on form submission, extensible via the `outstand_forms_form_submitted` action.
- A settings page for site-wide plugin configuration.
- Support for submitting forms without JavaScript: when JavaScript is unavailable, the form posts directly to the page and redirects back with the submission result (Post/Redirect/Get), so a page refresh cannot resubmit the form.
- Extensibility points for developers: the `outstand_forms_field_factory` filter to register custom field types, the `outstand_forms_validation_messages` filter to override validation messages, the `outstand_forms_form_pre_submission_check` filter to block a submission before it is processed, and the `outstandForms.form.allowedBlocks` JS filter to change which blocks can be inserted inside the fields wrapper.

### Changed

- Field inputs now update their value and validity while typing, rather than waiting until the field loses focus.
