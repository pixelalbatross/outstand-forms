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
- Lightweight and extensible.

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
composer require s3rgiosan/outstand-forms
```

2. Run `composer install` to install the plugin.
3. Activate the plugin from your WordPress admin area or using WP-CLI.

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

You can style forms using your theme’s styles or add custom styles targeting the `.osf-form`, `.osf-field`, and `.osf-field__input` classes.

## Hooks & Extensibility

### `outstand_forms_validation_messages`

Override or extend the default validation messages passed to the Interactivity API:

```php
add_filter( 'outstand_forms_validation_messages', function( $messages, $form_id ) {
    $messages['required'] = 'Custom required message.';
    return $messages;
}, 10, 2 );
```

## Changelog

A complete listing of all notable changes to this project are documented in [CHANGELOG.md](https://github.com/s3rgiosan/outstand-forms/blob/main/CHANGELOG.md).
