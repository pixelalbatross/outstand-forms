# Architecture

## Overview

Outstand Forms is a WordPress block-based forms plugin built on Gutenberg blocks (editor) and the Interactivity API (frontend). PHP serves as the single source of truth for field configuration, validation rules, and rendering. JavaScript consumes this configuration reactively.

**Core Principle:** PHP defines, JS executes. Block attributes are stored in post content by the editor. At render time, PHP extracts those attributes, builds validation rules, and passes them to the frontend via `wp_interactivity_data_wp_context()`. The JS Interactivity API store reads this context and runs validators accordingly.

## Directory Structure

```
outstand-forms/
├── plugin.php                          # Bootstrap: constants, autoloader, Plugin::enable()
├── composer.json                       # PHP dependencies (PSR-4 autoload)
├── package.json                        # JS dependencies (@wordpress/scripts, Inputmask)
├── includes/classes/
│   ├── Plugin.php                      # Singleton, module loader, block registration
│   ├── AbstractModule.php              # Base class for feature modules
│   ├── FieldFactory.php                # Type registry: definitions, create(), sanitize()
│   ├── Fields/
│   │   ├── FieldInterface.php          # Field contract
│   │   └── Field.php                   # The field: attributes, components, render, validation, sanitize
│   ├── Components/
│   │   ├── ComponentInterface.php      # Component contract (get_markup)
│   │   ├── AbstractComponent.php       # Shared helpers (field accessors)
│   │   ├── Input.php                   # <input> with Interactivity directives
│   │   ├── Textarea.php               # <textarea> with Interactivity directives
│   │   ├── Label.php                   # <label> with required indicator
│   │   ├── Error.php                   # Error message container
│   │   └── HelpText.php               # Help text container
│   ├── REST/V1/
│   │   ├── AbstractRoute.php           # REST endpoint base (namespace outstand-forms/v1)
│   │   └── Forms.php                   # Forms submission endpoint
│   ├── Blocks/
│   │   └── FieldTurnstile.php          # Turnstile script registration + submission verification
│   ├── EmailNotification.php           # Sends email notifications on form submission
│   ├── Settings.php                    # Plugin settings page (outstand_forms_settings option)
│   └── Validation/
│       └── Validator.php               # Server-side validation engine
├── src/
│   ├── blocks/
│   │   ├── form/                       # osf/form: container block
│   │   │   ├── block.json
│   │   │   ├── edit.js
│   │   │   ├── render.php
│   │   │   ├── view.js                 # Interactivity API store
│   │   │   ├── variations.js           # Block variations (Blank, Contact Us)
│   │   │   └── style.css
│   │   ├── form-fields/                # osf/form-fields: fields wrapper
│   │   │   ├── block.json
│   │   │   ├── edit.js
│   │   │   └── render.php
│   │   ├── form-submit/                # osf/form-submit: submit wrapper
│   │   │   ├── block.json
│   │   │   ├── edit.js
│   │   │   └── render.php
│   │   ├── form-message/               # osf/form-message: submission message
│   │   │   ├── block.json
│   │   │   ├── edit.js
│   │   │   └── render.php
│   │   ├── form-errors/                # osf/form-errors: submission error list
│   │   │   ├── block.json
│   │   │   ├── edit.js
│   │   │   └── render.php
│   │   ├── field-input/                # osf/field-input: input field
│   │   │   ├── block.json
│   │   │   ├── edit.js
│   │   │   └── render.php
│   │   ├── field-textarea/             # osf/field-textarea: textarea field
│   │   │   ├── block.json
│   │   │   ├── edit.js
│   │   │   └── render.php
│   │   └── field-turnstile/            # osf/field-turnstile: Cloudflare Turnstile spam protection
│   │       ├── block.json
│   │       ├── edit.js
│   │       ├── view.js
│   │       └── render.php
│   ├── components/                     # React editor components
│   ├── fields/                         # React field wrappers
│   ├── hooks/                          # React hooks (useFieldBlocks, useIsDuplicateFormBlock)
│   ├── validation.js                   # Client-side field validation
│   ├── utils.js
│   ├── options.js
│   └── constants.js
├── build/                              # Compiled output (@wordpress/scripts)
├── vendor/                             # Composer dependencies
├── languages/                          # i18n translation files
└── node_modules/
```

## Bootstrap Flow

```
plugin.php
├── Define OUTSTAND_FORMS_VERSION, OUTSTAND_FORMS_BASENAME, OUTSTAND_FORMS_URL, OUTSTAND_FORMS_PATH,
│   OUTSTAND_FORMS_DIST_URL, OUTSTAND_FORMS_DIST_PATH constants
├── Require Composer autoloader (PSR-4: Outstand\WP\Forms → includes/classes)
├── Initialize plugin update checker (GitHub-based)
└── Hook on 'plugins_loaded' → Plugin::get_instance()->enable()
    ├── Iterate modules array (FieldTurnstile, REST\V1\Forms, EmailNotification, Settings)
    │   ├── FieldTurnstile → registers Turnstile script + submission filters
    │   ├── Forms → registers REST routes on 'rest_api_init'
    │   ├── EmailNotification → listens on 'outstand_forms_form_submitted'
    │   └── Settings → registers the settings page
    ├── Hook register_blocks() on 'init'
    │   ├── Glob build/blocks/*/block.json
    │   ├── register_block_type_from_metadata() for each block
    │   └── Set up script translations
    ├── Hook register_block_categories() on 'block_categories_all'
    │   └── Add "Outstand Forms" category (slug: osf)
    └── Hook blocks_editor_scripts() on 'enqueue_block_editor_assets'
        └── Localizes Turnstile configuration state for the block editor
```

## Block Hierarchy

```
osf/form (top-level container)
│   providesContext: osf/formId, osf/labelPosition, osf/helpTextPosition, osf/requiredIndicator
│   allowedBlocks: osf/form-errors, osf/form-fields, osf/form-submit, osf/form-message
│
├── osf/form-errors (submission error list)
│   │   usesContext: osf/formId
│   │   ancestor: osf/form
│
├── osf/form-fields (fields wrapper)
│   │   usesContext: osf/formId
│   │   ancestor: osf/form
│   │
│   ├── osf/field-input
│   │   usesContext: osf/formId, osf/labelPosition, osf/helpTextPosition, osf/requiredIndicator
│   │   ancestor: osf/form-fields
│   │
│   └── osf/field-textarea
│       usesContext: osf/formId, osf/labelPosition, osf/helpTextPosition, osf/requiredIndicator
│       ancestor: osf/form-fields
│
├── osf/form-submit (submit wrapper)
│   │   usesContext: osf/formId
│   │   ancestor: osf/form
│   │   allowedBlocks: core/button, core/buttons, osf/field-turnstile
│   │
│   └── osf/field-turnstile (Cloudflare Turnstile widget)
│       usesContext: osf/formId
│       ancestor: osf/form-submit
│
└── osf/form-message (submission message)
    usesContext: osf/formId
    ancestor: osf/form
    allowedBlocks: core/paragraph
```

Context flows down via `providesContext` / `usesContext`. The form block sets form-wide defaults (label position, help text position, required indicator) that all field blocks inherit. Fields can override inherited values through their own attributes.

## PHP Field System

### FieldFactory (Type Registry)

A field type is a **definition array**, not a class. `FieldFactory` holds the
definitions and creates `Fields\Field` instances from them:

```php
$factory = FieldFactory::instance();
$field   = $factory->create( 'email', $attributes );
```

`FieldFactory::instance()` builds the registry once and passes it through the
`outstand_forms_field_factory` filter, so block rendering, `FormBlockParser` and
REST sanitization all read the same registry (including third-party types).

A definition supports three optional keys:

| Key | Shape | Default |
|-----|-------|---------|
| `component` | `callable( FieldInterface $field ): ComponentInterface` | `<input type="{type}">` |
| `rules` | array merged into the base rules, or `callable( array $rules, array $attributes ): array` | `[]` |
| `sanitize` | `callable( mixed $value ): mixed` | `sanitize_text_field` |

Built-in types:

| Type | Extra rules | Sanitizer |
|------|-------------|-----------|
| `text`, `password`, `tel` | — | `sanitize_text_field` |
| `email` | `email` | `sanitize_email` |
| `url` | `url` | `esc_url_raw` |
| `number` | adds `min`/`max`, drops `minLength`/`maxLength`/`pattern` | numeric cast, non-numeric → `null` |
| `textarea` | — (renders `<textarea>`) | `sanitize_textarea_field` |

The `number` sanitizer deliberately yields `null` rather than `0` for non-numeric
input, so a required number field still fails the `required` rule.

### Fields\Field

One class serves every type. It provides:

- **ID generation**: `get_field_id()`, `get_label_id()`, `get_help_text_id()`, `get_error_id()`
- **Base validation**: required, minLength, maxLength, pattern (extracted from attributes), then the type's `rules`
- **Sanitization**: `sanitize()`, using the type's `sanitize` callable
- **Rendering**: assembles component markup based on label/help text position

### Component Composition

Fields are composed of components, each implementing `ComponentInterface`:

| Component | Responsibility |
|-----------|---------------|
| `Label` | `<label>` with optional required indicator |
| `Input` | `<input>` with type-specific attributes and Interactivity directives |
| `Textarea` | `<textarea>` with Interactivity directives |
| `Error` | Error message container (reactive via `data-wp-text`) |
| `HelpText` | Help text container |

Components receive a field reference via `AbstractComponent` and use it to access field IDs, attributes, and type information.

The `Input` component conditionally renders attributes based on input type:

- **number**: `step`, `min`, `max` (no `minlength`, `maxlength`, `pattern`, `mask`)
- **email**, **url**: no `mask` attributes
- **all types**: `placeholder`, `autocomplete`, `required`, `aria-label`

## Data Flow: PHP → Interactivity API

```
Block Editor saves attributes to post_content
    ↓
Frontend request → render.php executes
    ↓
Form render.php:
├── wp_interactivity_state('osf/form')
├── wp_interactivity_data_wp_context({
│       formFields, isSubmitting, isSubmitted, hasSubmissionError,
│       submissionMessage, submissionMessages,
│       validationMessages: apply_filters('outstand_forms_validation_messages', defaults, formId)
│   })
├── Outputs <form data-wp-interactive="osf/form">
└── Hidden fields: form_id, post_id, _wpnonce
    ↓
Field render.php:
├── FieldFactory::instance()->create(type, attributes)
├── field->get_validation_rules()  →  { required: true, email: 'email', minLength: 5, ... }
├── wp_interactivity_data_wp_context({
│       fieldId, fieldName, helpTextId, errorId,
│       initialRecord: { value, validationRules }
│   })
└── field->render()  →  Components output HTML with data-wp-* directives
```

Key data boundaries:

- `wp_interactivity_data_wp_context()` — per-instance state; this is where submission and validation state live, not `wp_interactivity_config()`. The form context owns all mutable field state in its `formFields` registry; field-local context carries identity only (plus `initialRecord`, a one-way seed consumed by `callbacks.registerField`)
- `data-wp-*` directives on HTML elements — bind reactive state to DOM

## Frontend Interactivity Store

The `osf/form` store (`src/blocks/form/view.js`) is organized into three sections:

### State (Computed Getters)

| Getter | Purpose |
|--------|---------|
| `fieldAriaDescribedByAttribute` | Returns `aria-describedby` value: error ID when invalid, help text ID otherwise |
| `fieldErrorMessage` | Looks up message from the form's `validationMessages` context, interpolates `{{min}}`/`{{max}}` placeholders |
| `fieldValue` | Current value from the field's registry record, falling back to the server-rendered `initialRecord.value` |
| `isFieldFocused` | Focus flag from the field's registry record |
| `isFieldValid` | Validity flag from the field's registry record (`true` while unregistered) |
| `isFormValid` | Returns `true` only when all registered fields pass validation |

Every field-scoped getter reads `fieldName` from field-local context, then resolves its record out of the form's `formFields` registry.

### Actions (Event Handlers)

| Action | Trigger | Effect |
|--------|---------|--------|
| `handleFieldFocus` | `focus` event | Sets `isFocused = true` on the field's registry record |
| `handleFieldBlur` | `blur` event | Sets `isFocused = false` on the field's registry record |
| `handleFieldChange` | `change` event | Updates `value` on the field's registry record from the element |
| `handleFormSubmit` | form `submit` event | Prevents default, guards against double-submit, runs `validateForm()`, then `submitForm()` if valid |
| `validateForm` | Called by `handleFormSubmit` | Synchronous loop over `formFields`: runs `validate()` per record and writes `isValid`/`errors` back |
| `submitForm` | Called by `handleFormSubmit` when the form is valid | POSTs `FormData` to the form's `action` URL, updates submission state, writes 400-response errors straight into the matching registry records |

### Callbacks (Init Hooks)

| Callback | Directive | Purpose |
|----------|-----------|---------|
| `registerField` | `data-wp-init--register` | Seeds the field's record in the form's `formFields` registry on mount |
| `initMask` | `data-wp-init--mask` | Lazy-loads Inputmask library, applies mask |

### Parent-Owned Field State

The form owns all mutable field state. `formFields` is a registry keyed by field name:

```
formFields: { [fieldName]: { value, isValid, isFocused, errors, validationRules } }
```

1. Each field self-registers on init (`data-wp-init--register`), seeding its record from `initialRecord`
2. Field-bound actions (`focus`/`blur`/`change`) write into their own record
3. `handleFormSubmit` calls `validateForm()`, a synchronous loop over the registry, then reads `isFormValid` and submits
4. A 400 response writes failed rule names into the matching records; the next `validateForm()` pass clears them

There are no custom DOM events and no per-field timeouts: an unregistered field is simply absent from the registry and cannot stall a submission.

## Validation Architecture

```
PHP (source of truth)                    JS (execution)
─────────────────────                    ──────────────
AbstractField::get_validation_rules()
    ↓
[required: true, email: 'email', ...]
    ↓
Serialized into data-wp-context          registerField() copies them into
    ↓                                    formFields[fieldName].validationRules
Frontend receives context        ──→         ↓
                                         validateForm() → validate(value, rules)
                                            ↓
                                         For each rule:
                                            Look up validator by name
                                            validator(value, ruleConfig)
                                            ↓
                                         { isValid: bool, errors: [...] }
                                            ↓
                                         Written to the field's registry record
                                            ↓
                                         Reactive state updates DOM (error messages, ARIA)
```

The `validate()` function exported from `src/validation.js` iterates over rules, looks up each validator by name from an internal map, and returns `{ isValid, errors }`.

Built-in validators: `required`, `email`, `url`, `pattern`, `minLength`, `maxLength`, `min`, `max`.

## Server-Side Validation

The `Validation\Validator` class provides server-side validation that mirrors the JS `validate()` function.

### Usage

```php
$validator = new Validator();
$result = $validator->validate( $value, $rules );
// $result = [ 'is_valid' => bool, 'errors' => [ 'ruleName', ... ] ]
```

### Built-in Validators

| Validator | Method | Behavior |
|-----------|--------|----------|
| `required` | `validate_required` | Fails if value is `null` or empty/whitespace string |
| `email` | `validate_email` | HTML5 spec regex (matches `src/validation.js`); skips empty values |
| `url` | `validate_url` | Regex requiring `http(s)://` (matches `src/validation.js`); skips empty values |
| `pattern` | `validate_pattern` | Regex match using ASCII SOH (`chr(1)`) delimiter; fails closed on invalid regex |
| `minLength` | `validate_min_length` | `mb_strlen()` comparison; skips empty values |
| `maxLength` | `validate_max_length` | `mb_strlen()` comparison; skips empty values |
| `min` | `validate_min` | Float comparison; skips non-numeric values |
| `max` | `validate_max` | Float comparison; skips non-numeric values |

### Custom Validator Registration

```php
$validator = new Validator();
$validator->register( 'phone', function ( $value, $params, $config ) {
    return preg_match( '/^\+?[\d\s\-()]+$/', $value );
} );
```

### Submission Flow

```
Client POST /outstand-forms/v1/forms/submit
    ↓
Rate limit check (per-IP transient, 5 attempts/minute by default)
    ↓
FormBlockParser extracts field configs from block content using post_id
    ↓
outstand_forms_form_pre_submission_check filter (Turnstile verification runs here)
    ↓
Sanitize values (type-aware, scoped to known fields only)
    ↓
For each field config:
    Validator->validate( submitted_value, validation_rules )
    ↓
If errors → WP_Error (400) with per-field error rule names
    ↓
do_action( 'outstand_forms_form_submitted', $form_id, $post_id, $sanitized_data, $form_data )
    ↓
WP_REST_Response (200)
```

**Security Note:** The submission endpoint is intentionally public and unauthenticated (`permission_callback` returns `true`). The `_wpnonce` field emitted by the form is not verified server-side. Abuse protection relies on the per-IP rate limit (configurable via `outstand_forms_rate_limit` filter) and optional Cloudflare Turnstile verification (enabled via the `osf/field-turnstile` block within the form).

## Design Patterns

| Pattern | Where | Purpose |
|---------|-------|---------|
| **Singleton** | `Plugin` | Single plugin instance |
| **Registry/Factory** | `FieldFactory` | Field type definitions; creates `Fields\Field` instances |
| **Template Method** | `AbstractComponent`, `AbstractRoute` | Base behavior with override points |
| **Strategy** | Field type definitions | Per-type component, rules and sanitizer callables |
| **Composition** | Fields → Components | Fields composed of Label, Input/Textarea, Error, HelpText |
| **Registry** | `Validator` | Central store for validator functions (PHP) |
| **Observer** | Custom events, WP hooks | Decoupled communication between form and fields |
| **Context/DI** | Block context system | Form-wide settings cascade to child blocks |

## Extensibility Points

### PHP

**`outstand_forms_field_factory` filter** — Register custom field types on the shared registry:

```php
add_filter( 'outstand_forms_field_factory', function ( $factory ) {
    $factory->register( 'slug', [
        'rules'    => [ 'pattern' => '[a-z0-9-]+' ],
        'sanitize' => 'sanitize_title',
    ] );

    return $factory;
} );
```

**`outstand_forms_validation_messages` filter** — Override validation messages:

```php
add_filter('outstand_forms_validation_messages', function ($messages, $form_id) {
    $messages['required'] = 'Please fill in this field.';
    return $messages;
}, 10, 2);
```

**`outstand_forms_form_submitted` action** — Fires after successful validation and sanitization:

```php
add_action( 'outstand_forms_form_submitted', function ( $form_id, $post_id, $sanitized_data, $form_data ) {
    // Store submission, send email, trigger integrations, etc.
}, 10, 4 );
```

**Actions** — Inject content around form/fields:

- `outstand_forms_before_fields` / `outstand_forms_after_fields` — before/after fields wrapper content

### JavaScript

**`outstandForms.form.allowedBlocks` filter** — Change which blocks can be inserted inside the fields wrapper:

```js
addFilter('outstandForms.form.allowedBlocks', 'my-plugin/allowed-blocks', (blocks) => [
	...blocks,
	'core/separator',
]);
```

JS validators are internal to `src/validation.js` and not exposed as a public API. To add custom validation rules, use the PHP `Validator::register()` method on the server side.
