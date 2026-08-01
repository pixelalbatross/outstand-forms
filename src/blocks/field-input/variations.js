/* eslint-disable import/no-extraneous-dependencies */
/**
 * WordPress dependencies
 */
import { plugins as genericIcon } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { text, email, hash, lock, phone, link } from './icon';
import { buildFieldInputVariations } from './get-variations';
import { getFieldTypes } from '../../utils';

/**
 * Editorial metadata for the built-in field types.
 *
 * `osfSettings.fieldTypes` (the field type registry, see
 * `FieldFactory::get_registered_types()`) is the source of truth for *which*
 * types exist; this lookup only adds presentation details that aren't
 * derivable from the registry — title, icon and keywords — and is what
 * keeps the six built-ins looking exactly as they did before the registry
 * existed. See `buildFieldInputVariations()` for how the two are combined.
 */
const FIELD_TYPE_METADATA = {
	text: {
		title: __('Text', 'outstand-forms'),
		icon: text,
		isDefault: true,
		keywords: [
			__('field', 'outstand-forms'),
			__('input', 'outstand-forms'),
			__('text', 'outstand-forms'),
		],
	},
	email: {
		title: __('Email', 'outstand-forms'),
		icon: email,
		keywords: [
			__('field', 'outstand-forms'),
			__('input', 'outstand-forms'),
			__('email', 'outstand-forms'),
		],
		attributes: { autocomplete: 'email' },
	},
	number: {
		title: __('Number', 'outstand-forms'),
		icon: hash,
		keywords: [
			__('field', 'outstand-forms'),
			__('input', 'outstand-forms'),
			__('number', 'outstand-forms'),
		],
	},
	password: {
		title: __('Password', 'outstand-forms'),
		icon: lock,
		keywords: [
			__('field', 'outstand-forms'),
			__('input', 'outstand-forms'),
			__('password', 'outstand-forms'),
		],
		attributes: { autocomplete: 'new-password' },
	},
	tel: {
		title: __('Phone', 'outstand-forms'),
		icon: phone,
		keywords: [
			__('field', 'outstand-forms'),
			__('input', 'outstand-forms'),
			__('telephone', 'outstand-forms'),
			__('tel', 'outstand-forms'),
		],
		attributes: { autocomplete: 'tel' },
	},
	url: {
		title: __('URL', 'outstand-forms'),
		icon: link,
		keywords: [
			__('field', 'outstand-forms'),
			__('input', 'outstand-forms'),
			__('url', 'outstand-forms'),
			__('link', 'outstand-forms'),
		],
		attributes: { autocomplete: 'url' },
	},
};

const variations = buildFieldInputVariations(getFieldTypes(), FIELD_TYPE_METADATA, genericIcon);

variations.forEach((variation) => {
	if (variation.isActive) {
		return;
	}
	variation.isActive = (blockAttributes, variationAttributes) =>
		blockAttributes.type === variationAttributes.type;
});

export default variations;
