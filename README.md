# Outstand Forms

> [!WARNING]
> **Work in Progress:** This plugin is currently in development and not yet ready for production use.

## Description

Outstand Forms is a WordPress plugin for building forms using the Block Editor. It leverages the [Interactivity API](https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/) and provides features like field validation, input masking, and a clean UI built for usability and accessibility.

## Features

- Fully block-based form builder.
- Dynamic validation using JavaScript.
- Server-side validation with matching PHP validators.
- Field-level validation messages.
- Input mask support via [Inputmask](https://robinherbots.github.io/Inputmask/).
- Accessible markup with proper `aria` attributes.
- Works without JavaScript, with a progressively enhanced submit flow when it's available.
- Lightweight and extensible.

> [!NOTE]
> Forms work without JavaScript. By default a form posts back to the page it's rendered on; the submission is validated and processed through the same pipeline as the REST route, then redirected (Post/Redirect/Get) so a page refresh can't resubmit it. Validation errors and submitted values are replayed on the redirected page. When JavaScript is available, the Interactivity API takes over: it submits to the REST API instead, and disables the browser's native validation in favor of its own client-side checks.

## Installation

### Manual Installation

1. Download the plugin ZIP file from the GitHub repository.
2. Go to Plugins > Add New > Upload Plugin in your WordPress admin area.
3. Upload the ZIP file and click Install Now.
4. Activate the plugin.

### Install with Composer

To include this plugin as a dependency in your Composer-managed WordPress project:

1. Add the plugin to your project using the following command:

```bash
composer require outstand/forms
```

2. Run `composer install` to install the plugin.
3. Activate the plugin from your WordPress admin area or using WP-CLI.

## Requirements

- WordPress 6.7+
- PHP 8.2+

## Quick Start

1. Add a new form using the Outstand Forms block.
2. Use the available field blocks (e.g., Text, Email, Textarea, Submit) inside the form.
3. Configure each field via the block sidebar: labels, help text, validation rules, etc.
4. Preview the form and submit — validation will run automatically.
5. Customize styles using your theme or custom CSS.

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

You can style forms using your theme’s styles or add custom styles targeting the `.wp-block-osf-form`, `.osf-field`, and `.osf-field__input` classes.

## Hooks & Extensibility

### `outstand_forms_validation_messages`

Override or extend the default validation messages passed to the Interactivity API:

```php
add_filter( 'outstand_forms_validation_messages', function( $messages, $form_id ) {
    $messages['required'] = 'Custom required message.';
    return $messages;
}, 10, 2 );
```

### `outstand_forms_form_submitted`

Fires after a form is submitted and validated successfully:

```php
add_action( 'outstand_forms_form_submitted', function( $form_id, $post_id, $sanitized_data, $form_data ) {
    // Store submission, send email, trigger integrations, etc.
}, 10, 4 );
```

### `outstand_forms_rate_limit`

Adjust (or disable) the per-IP submission rate limit — default 5 submissions per minute:

```php
add_filter( 'outstand_forms_rate_limit', function( $max_submissions, $form_id ) {
    return 10; // Return 0 to disable rate limiting.
}, 10, 2 );
```

### Content Actions

Inject content around the fields area:

- `outstand_forms_before_fields` / `outstand_forms_after_fields` — before/after the fields wrapper content

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

Register custom server-side validators:

```php
$validator = new \Outstand\WP\Forms\Validation\Validator();
$validator->register( 'phone', function( $value, $params, $config ) {
    return preg_match( '/^\+?[\d\s\-()]+$/', $value );
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

A complete listing of all notable changes to this project are documented in [CHANGELOG.md](https://github.com/pixelalbatross/outstand-forms/blob/main/CHANGELOG.md).

## License

[GPL-3.0-or-later](https://spdx.org/licenses/GPL-3.0-or-later.html)
