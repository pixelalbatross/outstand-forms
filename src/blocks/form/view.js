/**
 * WordPress dependencies
 */
import { store, getContext, getElement, withSyncEvent } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { validate } from './../../validation';

/**
 * Flag the form as failed and surface the generic error message.
 *
 * @param {Object} context            The form context.
 * @param {Object} submissionMessages The submission messages.
 */
function setSubmissionError(context, submissionMessages) {
	context.hasSubmissionError = true;
	context.submissionMessage = submissionMessages.error || '';
}

/**
 * Absorb the errors returned by the REST endpoint into the registry.
 *
 * The registry is the single source of validity, so a server-side rejection is
 * written exactly the way a client-side one is and clears on the next
 * validateForm() pass.
 *
 * Takes the form context as an argument rather than resolving it itself: this
 * runs after an `await`, where the Interactivity API scope of the originating
 * directive is no longer on the stack and `getContext()` would resolve nothing.
 *
 * @param {Object} context      The form context.
 * @param {Object} serverErrors Failed rule names keyed by field name.
 */
function applyServerErrors(context, serverErrors) {
	const { formFields = {} } = context;

	Object.entries(serverErrors).forEach(([fieldName, errors]) => {
		const record = formFields[fieldName];

		if (!record) {
			return;
		}

		record.isValid = false;
		record.errors = Array.isArray(errors) ? errors : [errors];
	});
}

/**
 * Resolve the form-owned record for the field in the current scope.
 *
 * Field state lives in the form context under `formFields`, keyed by field
 * name; a field element's own context carries only its identity. Directives
 * resolve `getContext()` against the element they are attached to, so a
 * field-scoped directive reads its `fieldName` locally and looks the record up
 * in the shared registry.
 *
 * The record does not exist until `callbacks.registerField` has run, and a
 * field whose view script never initializes never gets one. Every caller must
 * therefore tolerate `undefined` and fall back to the server-rendered state.
 *
 * @return {Object|undefined} The field record, or undefined when unregistered.
 */
function getFieldRecord() {
	const { fieldName } = getContext();
	const { formFields } = getContext('osf/form');

	return formFields?.[fieldName];
}

/**
 * Determine whether a value can be interpolated into a message directly.
 *
 * Excludes objects/arrays, which would otherwise stringify as
 * `[object Object]` (or a comma-joined dump) in user-facing text.
 *
 * @param {*} value The value to check.
 * @return {boolean} True if the value is safe to interpolate as-is.
 */
function isScalar(value) {
	return value !== null && typeof value !== 'object' && typeof value !== 'function';
}

/**
 * Substitute `{{key}}` placeholders in a validation message.
 *
 * A rule's config in `validationRules` is usually a single scalar (e.g.
 * `minLength: 3`), and a message has a single placeholder referring to that
 * value regardless of its literal name -- the `minLength` message reads
 * "at least {{min}} characters", not `{{minLength}}`. In that case every
 * placeholder is replaced with the scalar itself. If a rule's config is
 * instead an object (e.g. a hypothetical multi-parameter rule), placeholders
 * are resolved by matching key instead.
 *
 * A placeholder that can't be resolved this way (missing key, non-scalar
 * value, or no config at all) is replaced with an empty string so it never
 * leaks the literal `{{...}}` to the end user.
 *
 * @param {string} message    The raw message, possibly containing `{{key}}` placeholders.
 * @param {*}      ruleConfig The failed rule's own value from `validationRules`.
 * @return {string} The message with placeholders substituted.
 */
function substitutePlaceholders(message, ruleConfig) {
	return message.replace(/{{\s*([\w-]+)\s*}}/g, (match, key) => {
		if (ruleConfig !== null && typeof ruleConfig === 'object') {
			const value = ruleConfig[key];
			return isScalar(value) ? String(value) : '';
		}

		return isScalar(ruleConfig) ? String(ruleConfig) : '';
	});
}

const { state, actions } = store('osf/form', {
	state: {
		/**
		 * Get the aria-describedby attribute for the field.
		 *
		 * @return {string|undefined} The aria-describedby attribute.
		 */
		get fieldAriaDescribedByAttribute() {
			const { helpTextId, errorId } = getContext();

			if (!helpTextId && !errorId) {
				return undefined;
			}

			if (!errorId) {
				return helpTextId;
			}

			return state.isFieldValid ? helpTextId : `${errorId} ${helpTextId}`;
		},
		/**
		 * Get the error message for the field.
		 *
		 * @return {string} The error message.
		 */
		get fieldErrorMessage() {
			const record = getFieldRecord();

			if (!record || record.isValid || !record.errors?.length) {
				return '';
			}

			const { validationMessages = {} } = getContext('osf/form');
			const error = record.errors[0];
			const message = validationMessages?.[error];

			// Skip if the error is not in the validation messages.
			if (message === undefined) {
				return '';
			}

			return substitutePlaceholders(message, record.validationRules?.[error]);
		},
		/**
		 * Get the current value of the field.
		 *
		 * @return {string} The field value.
		 */
		get fieldValue() {
			const record = getFieldRecord();

			if (record) {
				return record.value;
			}

			const { initialRecord } = getContext();
			return initialRecord?.value ?? '';
		},
		/**
		 * Determine if the field is focused.
		 *
		 * @return {boolean} True if the field is focused, false otherwise.
		 */
		get isFieldFocused() {
			const record = getFieldRecord();

			return record ? record.isFocused : false;
		},
		/**
		 * Determine if the field is valid.
		 *
		 * @return {boolean} True if the field is valid, false otherwise.
		 */
		get isFieldValid() {
			const record = getFieldRecord();

			return record ? record.isValid : true;
		},
		/**
		 * Determine if the form is valid.
		 *
		 * @return {boolean} True if the form is valid, false otherwise.
		 */
		get isFormValid() {
			const { formFields } = getContext('osf/form');

			// An empty registry (no fields, or none registered yet) is
			// vacuously valid: Object.values( {} ).every( ... ) is already
			// true, so no explicit length check is needed.
			return Object.values(formFields || {}).every((record) => Boolean(record?.isValid));
		},
	},
	actions: {
		/**
		 * Handle the field focus event.
		 */
		handleFieldFocus() {
			const record = getFieldRecord();

			if (!record) {
				return;
			}

			record.isFocused = true;
		},
		/**
		 * Handle the field blur event.
		 */
		handleFieldBlur() {
			const record = getFieldRecord();

			if (!record) {
				return;
			}

			record.isFocused = false;
		},
		/**
		 * Handle the field change event.
		 */
		handleFieldChange() {
			const record = getFieldRecord();

			if (!record) {
				return;
			}

			const { ref } = getElement();
			record.value = ref.value;
		},
		/**
		 * Handle the form submit event.
		 */
		handleFormSubmit: withSyncEvent((ev) => {
			ev.preventDefault();

			const context = getContext('osf/form');

			// Guard against double-submit. Validation is synchronous, but the
			// request submitForm() fires is not, so the flag has to be set
			// before it to block a second rapid submit.
			if (context.isSubmitting) {
				return;
			}

			context.isSubmitting = true;

			actions.validateForm();

			if (!state.isFormValid) {
				// submitForm() (and the reset in its `finally`) never runs.
				context.isSubmitting = false;
				return;
			}

			actions.submitForm();
		}),
		/**
		 * Submit the form.
		 */
		async submitForm() {
			const context = getContext('osf/form');
			const { ref: form } = getElement();
			const { submissionMessages = {}, submitUrl } = context;

			// Reset state.
			context.hasSubmissionError = false;
			context.submissionMessage = '';
			context.isSubmitting = true;

			try {
				const formData = new FormData(form);

				// The form's `action` points at the page so a submission
				// without JavaScript gets an HTML response; the endpoint this
				// path posts to travels in the context instead.
				const response = await fetch(submitUrl || form.action, {
					method: 'POST',
					headers: {
						'X-WP-Nonce': formData.get('_wpnonce'),
					},
					body: formData,
					credentials: 'same-origin',
				});

				const data = await response.json();

				if (response.ok) {
					context.isSubmitted = true;
				} else {
					setSubmissionError(context, submissionMessages);

					if (response.status === 400 && data.data?.errors) {
						applyServerErrors(context, data.data.errors);
					}
				}
			} catch {
				setSubmissionError(context, submissionMessages);
			} finally {
				context.isSubmitting = false;
			}
		},
		/**
		 * Validate every registered field.
		 *
		 * A field that never registered is simply absent from the registry and
		 * is skipped, so a field whose view script failed can never stall the
		 * submission.
		 */
		validateForm() {
			const { formFields = {} } = getContext('osf/form');

			Object.values(formFields).forEach((record) => {
				const { isValid, errors } = validate(record.value, record.validationRules);

				record.isValid = isValid;
				record.errors = errors;
			});
		},
	},
	callbacks: {
		/**
		 * Register the field in the form-owned registry.
		 *
		 * Fields self-register on init rather than being enumerated by the form
		 * template, so each field block stays responsible for its own rules and
		 * the form renders progressively.
		 *
		 * `initialRecord` is a one-way handoff: it seeds the registry here and
		 * is never read again as state.
		 */
		registerField() {
			const { fieldName, initialRecord } = getContext();
			const {
				value = '',
				validationRules = {},
				isValid = true,
				errors = [],
			} = initialRecord ?? {};

			const formContext = getContext('osf/form');
			if (!formContext.formFields) {
				formContext.formFields = {};
			}

			formContext.formFields[fieldName] = {
				value,
				isValid,
				isFocused: false,
				errors: [...errors],
				validationRules: { ...validationRules },
			};
		},
		/**
		 * Disable native validation once the client takes over.
		 *
		 * The attribute is not server-rendered: a visitor without JavaScript
		 * would otherwise lose browser validation as well as the custom one.
		 */
		initNoValidate() {
			const { ref } = getElement();

			ref.noValidate = true;
		},
		/**
		 * Initialize the field mask.
		 *
		 * @see https://robinherbots.github.io/Inputmask/
		 */
		async initMask() {
			const { ref } = getElement();
			const { inputmask } = ref?.dataset ?? {};

			if (!inputmask) {
				return;
			}

			try {
				const { default: Inputmask } = await import('inputmask');
				const im = new Inputmask(inputmask);
				im.mask(ref);
			} catch {}
		},
	},
});
