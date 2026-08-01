/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { TEMPLATE } from './constants';
import { blank, contactUs } from './icon';

const variations = [
	{
		name: 'blank',
		title: __('Blank', 'outstand-forms'),
		isDefault: true,
		icon: blank,
		innerBlocks: TEMPLATE,
		scope: ['block'],
	},
	{
		name: 'contact-us',
		title: __('Contact Us', 'outstand-forms'),
		attributes: {
			nextFieldId: 4,
		},
		icon: contactUs,
		innerBlocks: [
			['osf/form-errors', {}],
			[
				'osf/form-fields',
				{},
				[
					[
						'osf/field-input',
						{
							fieldId: 1,
							name: 'name',
							label: __('Name', 'outstand-forms'),
							required: true,
							helpText: __('Please enter your full name.', 'outstand-forms'),
							autocomplete: 'name',
						},
					],
					[
						'osf/field-input',
						{
							fieldId: 2,
							type: 'email',
							name: 'email',
							label: __('Email', 'outstand-forms'),
							required: true,
							helpText: __('Please enter your email address.', 'outstand-forms'),
							autocomplete: 'email',
						},
					],
					[
						'osf/field-textarea',
						{
							fieldId: 3,
							name: 'message',
							label: __('Message', 'outstand-forms'),
							required: true,
							helpText: __('Please enter your message.', 'outstand-forms'),
						},
					],
				],
			],
			['osf/form-submit', {}],
			['osf/form-message', {}],
		],
		scope: ['block'],
	},
];

export default variations;
