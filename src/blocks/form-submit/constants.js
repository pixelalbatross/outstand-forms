/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

export const SUBMIT_BUTTON_WRAPPER_BLOCK = {
	name: 'core/buttons',
	attributes: {
		className: 'wp-block-osf-form-submit__buttons',
		lock: {
			remove: true,
		},
	},
};

export const SUBMIT_BUTTON_BLOCK = {
	name: 'core/button',
	attributes: {
		text: __('Submit', 'outstand-forms'),
		tagName: 'button',
		type: 'submit',
		className: 'wp-block-osf-form-submit__button',
		lock: {
			remove: true,
		},
	},
};

export const CONSENT_BLOCK = {
	name: 'osf/field-consent',
	attributes: {
		label: __('I agree to the terms and conditions.', 'outstand-forms'),
		required: true,
	},
};

// The consent box sits above the buttons: it is the last thing a visitor
// confirms before submitting, and it is not one of the fields being collected.
export const TEMPLATE = [
	['osf/field-consent', CONSENT_BLOCK.attributes],
	[
		'core/buttons',
		SUBMIT_BUTTON_WRAPPER_BLOCK.attributes,
		[['core/button', SUBMIT_BUTTON_BLOCK.attributes]],
	],
];
