# Outstand Forms

> Build forms with the block editor.

> [!WARNING]
> **Work in Progress:** This plugin is currently in development and not yet ready for production use.

## Description

Outstand Forms is a WordPress plugin for building forms using the Block Editor. It leverages the [Interactivity API](https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/) and provides features like field validation, input masking, and a clean UI built for usability and accessibility.

PHP is the single source of truth: field configuration, validation rules and sanitization are defined server-side, and the frontend consumes them.

## Features

- Fully block-based form builder.
- Client-side validation via the Interactivity API, with matching PHP validators server-side.
- Field-level validation messages.
- Input mask support via [Inputmask](https://robinherbots.github.io/Inputmask/), loaded only when a mask is set.
- Email notifications on submission.
- Spam protection through the Cloudflare Turnstile block.
- Per-IP submission rate limiting (5 per minute by default).
- Accessible markup with proper `aria` attributes.
- Works without JavaScript, with a progressively enhanced submit flow when it's available.
- Lightweight and extensible.

> [!NOTE]
> Forms work without JavaScript. By default a form posts back to the page it's rendered on; the submission is validated and processed through the same pipeline as the REST route, then redirected (Post/Redirect/Get) so a page refresh can't resubmit it. Validation errors and submitted values are replayed on the redirected page. When JavaScript is available, the Interactivity API takes over: it submits to the REST API instead, and disables the browser's native validation in favor of its own client-side checks.

## Requirements

- WordPress 6.7+
- PHP 8.2+

The Turnstile block additionally requires a [Cloudflare Turnstile](https://www.cloudflare.com/products/turnstile/) site key and secret key, configured under **Settings → Outstand Forms**. It stays inert until both are set.

## Installation

### Manual Installation

1. Download the latest release ZIP from the [Releases page](https://github.com/pixelalbatross/outstand-forms/releases/latest).
2. Go to Plugins > Add New > Upload Plugin in your WordPress admin area.
3. Upload the ZIP file and click Install Now.
4. Activate the plugin.

### Install with Composer

To include this plugin as a dependency in your Composer-managed WordPress project:

1. Add the plugin to your project using the following command:

```bash
composer require outstand/forms
```

2. Run `composer install`.
3. Activate the plugin from your WordPress admin area or using WP-CLI.

## Quick Start

1. Insert the **Outstand Forms** block (`osf/form`). It comes with a **Form Fields** wrapper, a **Form Submit** button, and the **Form Errors** and **Form Message** blocks for feedback.
2. Add field blocks inside the Form Fields wrapper:
   - **Input** (`osf/field-input`) — with Text, Email, Number, Password, Phone and URL variations.
   - **Textarea** (`osf/field-textarea`).
   - **Turnstile** (`osf/field-turnstile`) — spam protection, requires configured keys.
3. Configure each field via the block sidebar: label, help text, validation rules, input mask.
4. Configure the notification email on the form block.
5. Preview the form and submit — validation runs on both the client and the server.

## Input Masks

To enable input masking:

- Add a mask string to the Mask field in the block settings.
- Internally powered by the [Inputmask](https://robinherbots.github.io/Inputmask/) library.
- Only loaded when an input mask is defined.

Example:

```json
"inputmask": "999-999-9999"
```

## Styling

Forms inherit your theme's styles. For custom styling, the markup exposes two layers of class names:

Block wrappers (one per block):

- `.wp-block-osf-form`, `.wp-block-osf-form-fields`, `.wp-block-osf-form-submit`, `.wp-block-osf-form-errors`, `.wp-block-osf-form-message`, `.wp-block-osf-field-turnstile`
- `.osf-field-input`, `.osf-field-textarea` — with `--required`, `--has-label` and `--has-help` modifiers

Field internals (shared by every field type):

- `.osf-field` — the field root, with `--label-left` / `--label-right` modifiers
- `.osf-field__wrapper`, `.osf-field__label`, `.osf-field__required-indicator`
- `.osf-field__input`, `.osf-field__textarea`
- `.osf-field__help-text`, `.osf-field__error`

## Hooks & Extensibility

### PHP filters

#### `outstand_forms_validation_messages`

Override or extend the default validation messages passed to the Interactivity API:

```php
add_filter( 'outstand_forms_validation_messages', function( $messages, $form_id ) {
    $messages['required'] = 'Custom required message.';
    return $messages;
}, 10, 2 );
```

#### `outstand_forms_rate_limit`

Adjust (or disable) the per-IP submission rate limit — default 5 submissions per minute:

```php
add_filter( 'outstand_forms_rate_limit', function( $max_submissions, $form_id ) {
    return 10; // Return 0 to disable rate limiting.
}, 10, 2 );
```

#### `outstand_forms_email_notification_args`

Filter the notification email before it is sent. Receives `to`, `subject`, `body` and `headers`:

```php
add_filter( 'outstand_forms_email_notification_args', function( $args, $form_id, $sanitized_data, $field_configs, $action ) {
    $args['headers'][] = 'Bcc: archive@example.com';
    return $args;
}, 10, 5 );
```

#### `outstand_forms_form_pre_submission_check`

Run a security or spam check with full form context, before field validation. Return `true` to continue or a `WP_Error` to abort. This is how the Turnstile block verifies its token:

```php
add_filter( 'outstand_forms_form_pre_submission_check', function( $result, $request ) {
    if ( is_wp_error( $result ) ) {
        return $result;
    }

    return my_spam_check( $request ) ? true : new \WP_Error( 'spam', 'Submission rejected.', [ 'status' => 400 ] );
}, 10, 2 );
```

#### `outstand_forms_rest_form_submit_args`

Add arguments to the submission REST endpoint, so a custom field or check can receive its own sanitized parameter:

```php
add_filter( 'outstand_forms_rest_form_submit_args', function( $args ) {
    $args['my-token'] = [
        'type'              => 'string',
        'required'          => false,
        'sanitize_callback' => 'sanitize_text_field',
    ];

    return $args;
} );
```

### PHP actions

#### `outstand_forms_form_submitted`

Fires after a form is submitted and validated successfully:

```php
add_action( 'outstand_forms_form_submitted', function( $form_id, $post_id, $sanitized_data, $form_data ) {
    // Store submission, send email, trigger integrations, etc.
}, 10, 4 );
```

#### `outstand_forms_email_notification_sent` / `outstand_forms_email_notification_failed`

Fire after `wp_mail()` succeeds or fails, with the same five arguments as `outstand_forms_email_notification_args`:

```php
add_action( 'outstand_forms_email_notification_failed', function( $args, $form_id, $sanitized_data, $field_configs, $action ) {
    error_log( sprintf( 'Notification failed for form %s.', $form_id ) );
}, 10, 5 );
```

#### Content actions

Inject content around the fields area:

- `outstand_forms_before_fields` / `outstand_forms_after_fields` — before/after the fields wrapper content. Both receive the form ID.

### JavaScript filters

#### `outstandForms.form.allowedBlocks`

Change which blocks can be inserted inside the fields wrapper:

```js
import { addFilter } from '@wordpress/hooks';

addFilter( 'outstandForms.form.allowedBlocks', 'my-plugin/allowed-blocks', ( blocks ) => [
	...blocks,
	'core/separator',
] );
```

### Custom Field Types

A field type is a definition array, registered on the shared factory through the
`outstand_forms_field_factory` filter. Rendering, validation and sanitization all
read from that same registry:

```php
add_filter( 'outstand_forms_field_factory', function( $factory ) {
    $factory->register( 'slug', [
        // Optional. Builds the control. Defaults to an <input type="{type}">.
        'component' => function( $field ) {
            return new \Outstand\WP\Forms\Components\Input( $field, 'text' );
        },
        // Optional. Array merged into the base rules, or a callable
        // ( array $rules, array $attributes ): array for full control.
        'rules'     => [ 'pattern' => '[a-z0-9-]+' ],
        // Optional. Defaults to sanitize_text_field.
        'sanitize'  => 'sanitize_title',
    ] );

    return $factory;
} );
```

Note: the `osf/field-input` block only offers the built-in types in the editor,
so a custom type currently needs its own block (or a `block.json` override) to be
selectable there.

### Custom Validators

Register custom server-side validators on the shared validator, through the
`outstand_forms_validator` filter. Both submission paths — the REST route and the
no-JavaScript fallback — read from that same instance:

```php
add_filter( 'outstand_forms_validator', function( $validator ) {
    $validator->register( 'phone', function( $value, $params, $config ) {
        return preg_match( '/^\+?[\d\s\-()]+$/', $value );
    } );

    return $validator;
} );
```

Register the matching client-side validator from a script module, so the rule is
enforced while typing instead of only on submit:

```js
import { store } from '@wordpress/interactivity';

store( 'osf/form', {
	validators: {
		phone: ( value, params, config ) => value === '' || /^\+?[\d\s\-()]+$/.test( value ),
	},
} );
```

The callback receives the same `( value, params, config )` arguments as the PHP
one, and registering a built-in name overrides it. Registration order does not
matter: the store merges the map whether your module runs before or after the
plugin's.

The server stays authoritative. A rule registered only in PHP is failed closed
on the client — the field is marked invalid and a `console.warn()` names the
missing validator — so a value can never look valid in the browser and be
rejected by the server.

## Changelog

All notable changes to this project are documented in [CHANGELOG.md](https://github.com/pixelalbatross/outstand-forms/blob/main/CHANGELOG.md).

## License

This project is licensed under the [GPL-3.0-or-later](https://spdx.org/licenses/GPL-3.0-or-later.html).
