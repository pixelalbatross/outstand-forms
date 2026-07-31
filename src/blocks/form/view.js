/**
 * WordPress dependencies
 */
import { store, getContext, getElement, withSyncEvent, withScope } from '@wordpress/interactivity';

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

const { state, actions } = store('osf/form', {
	state: {
		/**
		 * Get the aria-describedby attribute for the field.
		 *
		 * @return {string|undefined} The aria-describedby attribute.
		 */
		get fieldAriaDescribedByAttribute() {
			const { isValid, helpTextId, errorId } = getContext();

			if (!helpTextId && !errorId) {
				return undefined;
			}

			if (!errorId) {
				return helpTextId;
			}

			return isValid ? helpTextId : `${errorId} ${helpTextId}`;
		},
		/**
		 * Get the error message for the field.
		 *
		 * @return {string} The error message.
		 */
		get fieldErrorMessage() {
			const { isValid, validationErrors, validationRules } = getContext();

			if (isValid || !validationErrors?.length) {
				return '';
			}

			const { validationMessages = {} } = getContext('osf/form');
			const error = validationErrors[0];

			// Skip if the error is not in the validation messages.
			if (validationMessages?.[error] === undefined) {
				return '';
			}

			let message = validationMessages[error];
			switch (error) {
				case 'minLength':
					message = message.replace('{{min}}', validationRules.minLength);
					break;
				case 'maxLength':
					message = message.replace('{{max}}', validationRules.maxLength);
					break;
				case 'min':
					message = message.replace('{{min}}', validationRules.min);
					break;
				case 'max':
					message = message.replace('{{max}}', validationRules.max);
					break;
				default:
					break;
			}

			return message;
		},
		/**
		 * Determine if the form is valid.
		 *
		 * @return {boolean} True if the form is valid, false otherwise.
		 */
		get isFormValid() {
			const { formFields } = getContext('osf/form');
			return Object.keys(formFields).length > 0 && Object.values(formFields).every(Boolean);
		},
	},
	actions: {
		/**
		 * Handle the field focus event.
		 */
		handleFieldFocus() {
			const context = getContext();
			context.isFocused = true;
		},
		/**
		 * Handle the field blur event.
		 */
		handleFieldBlur() {
			const context = getContext();
			context.isFocused = false;
		},
		/**
		 * Handle the field change event.
		 */
		handleFieldChange() {
			const context = getContext();
			const { ref } = getElement();
			context.value = ref.value;
		},
		/**
		 * Handle the field validate event.
		 */
		handleFieldValidate() {
			const context = getContext();
			const { fieldName, value, validationRules } = context;
			const { isValid, errors } = validate(value, validationRules);

			context.isValid = isValid;
			context.validationErrors = errors;

			const { ref } = getElement();

			const formContext = getContext('osf/form');
			if (!formContext.formFields) {
				formContext.formFields = {};
			}

			formContext.formFields[fieldName] = isValid;

			const event = new CustomEvent('osf-field-validated');
			ref.dispatchEvent(event);
		},
		/**
		 * Handle server-side validation errors for the field.
		 *
		 * Absorbs errors returned by the REST endpoint into the field's own
		 * context so the field has a single source of validity.
		 *
		 * @param {CustomEvent} event The event carrying the failed rule names.
		 */
		handleFieldServerErrors(event) {
			const context = getContext();
			const { fieldName } = context;
			const errors = event.detail?.errors || [];

			context.isValid = false;
			context.validationErrors = errors;

			const formContext = getContext('osf/form');
			if (formContext.formFields && fieldName in formContext.formFields) {
				formContext.formFields[fieldName] = false;
			}
		},
		/**
		 * Handle the form submit event.
		 */
		handleFormSubmit: withSyncEvent((ev) => {
			ev.preventDefault();

			const context = getContext('osf/form');

			// Guard against double-submit. Set synchronously, before the async
			// validation round trip, so a second rapid submit is blocked even
			// while validation is still in flight.
			if (context.isSubmitting) {
				return;
			}

			context.isSubmitting = true;

			const { ref: form } = getElement();

			const handleValidated = withScope(() => {
				if (state.isFormValid) {
					actions.submitForm();
				} else {
					// Validation completed but the form isn't valid, so
					// submitForm() (and its own reset) never runs.
					context.isSubmitting = false;
				}
			});

			form.addEventListener('osf-form-validated', handleValidated, { once: true });

			actions
				.validateForm()
				.then(() => {
					const event = new CustomEvent('osf-form-validated');
					form.dispatchEvent(event);
				})
				.catch(() => {
					form.removeEventListener('osf-form-validated', handleValidated);
					context.isSubmitting = false;
				});
		}),
		/**
		 * Submit the form.
		 */
		async submitForm() {
			const context = getContext('osf/form');
			const { ref: form } = getElement();
			const { submissionMessages = {} } = context;

			// Reset state.
			context.hasSubmissionError = false;
			context.submissionMessage = '';
			context.isSubmitting = true;

			try {
				const formData = new FormData(form);

				const response = await fetch(form.action, {
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
					context.submissionMessage = data.message || '';
				} else {
					setSubmissionError(context, submissionMessages);

					if (response.status === 400 && data.data?.errors) {
						Object.entries(data.data.errors).forEach(([fieldName, errors]) => {
							const fieldElement = form.querySelector(`[name="${fieldName}"]`);
							if (!fieldElement) {
								return;
							}

							const event = new CustomEvent('osf-field-server-error', {
								detail: { errors: Array.isArray(errors) ? errors : [errors] },
							});
							fieldElement.dispatchEvent(event);
						});
					}
				}
			} catch {
				setSubmissionError(context, submissionMessages);
			} finally {
				context.isSubmitting = false;
			}
		},
		/**
		 * Validate the form.
		 *
		 * @return {Promise} A promise that resolves when the validation is complete.
		 */
		validateForm() {
			const { formFields } = getContext('osf/form');
			const { ref: form } = getElement();

			const fieldNames = Object.keys(formFields || {});
			const validations = fieldNames.map((fieldName) => {
				return new Promise((resolve) => {
					const fieldElement = form.querySelector(`[name="${fieldName}"]`);
					if (!fieldElement) {
						return resolve();
					}

					const validationHandler = () => {
						fieldElement.removeEventListener('osf-field-validated', validationHandler);
						resolve();
					};

					fieldElement.addEventListener('osf-field-validated', validationHandler, {
						once: true,
					});

					const event = new CustomEvent('osf-field-validate', {
						bubbles: true,
					});
					fieldElement.dispatchEvent(event);
				});
			});

			return Promise.all(validations);
		},
	},
	callbacks: {
		/**
		 * Register the field in the form context.
		 */
		registerField() {
			const context = getContext();
			const { fieldName, isValid } = context;

			const formContext = getContext('osf/form');
			if (!formContext.formFields) {
				formContext.formFields = {};
			}

			formContext.formFields[fieldName] = isValid;
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
