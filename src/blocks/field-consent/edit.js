/* eslint-disable import/no-extraneous-dependencies */
/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import clsx from 'clsx';

/**
 * WordPress dependencies
 */
import {
	useBlockProps,
	InspectorControls,
	InspectorAdvancedControls,
} from '@wordpress/block-editor';
import {
	Notice,
	PanelBody,
	TextControl,
	ToggleControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { labelPositionOptions, helpTextPositionOptions } from '../../options';
import { useIsDuplicateFieldName } from '../../hooks/useIsDuplicateFieldName';
import { resolveFieldName } from '../../utils';
import Field from '../../fields';

export default function FieldConsentEdit({ attributes, setAttributes, context, clientId }) {
	const {
		'osf/labelPosition': defaultLabelPosition,
		'osf/helpTextPosition': defaultHelpTextPosition,
	} = context;

	const {
		fieldId,
		name: fieldName,
		defaultValue,
		required,
		ariaLabel,
		label,
		labelPosition = defaultLabelPosition,
		helpText,
		helpTextPosition = defaultHelpTextPosition,
	} = attributes;

	const isDuplicateFieldName = useIsDuplicateFieldName(clientId, attributes);

	const blockProps = useBlockProps({
		className: clsx(
			'osf-field',
			'osf-field-consent',
			`osf-field--label-${labelPosition}`,
			`osf-field--help-${helpTextPosition}`,
			{
				'osf-field--required': required,
				'osf-field--has-label': !!label,
				'osf-field--has-help': !!helpText,
			},
		),
	});

	const onNameChange = (value) => {
		setAttributes({ name: value.trim() });
	};

	const onRequiredChange = (value) => {
		setAttributes({ required: value });
	};

	const onCheckedByDefaultChange = (value) => {
		setAttributes({ defaultValue: value ? '1' : '' });
	};

	const onLabelPositionChange = (value) => {
		setAttributes({ labelPosition: value });
	};

	const onHelpTextPositionChange = (value) => {
		setAttributes({ helpTextPosition: value });
	};

	const onAriaLabelChange = (value) => {
		setAttributes({ ariaLabel: value || label });
	};

	return (
		<>
			<div {...blockProps}>
				<Field
					type="consent"
					attributes={attributes}
					setAttributes={setAttributes}
					context={context}
					showFieldId
				/>
			</div>
			<InspectorControls>
				<PanelBody title={__('Settings', 'outstand-forms')}>
					<ToggleControl
						label={__('Required', 'outstand-forms')}
						checked={required}
						onChange={onRequiredChange}
						help={__(
							'A consent box is normally required — that is what makes it consent.',
							'outstand-forms',
						)}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={__('Checked by Default', 'outstand-forms')}
						checked={defaultValue === '1'}
						onChange={onCheckedByDefaultChange}
						help={__(
							'Pre-ticked consent is not valid consent under the GDPR. Leave this off unless the box is not a consent request.',
							'outstand-forms',
						)}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</PanelBody>
				<PanelBody title={__('Appearance', 'outstand-forms')} initialOpen={false}>
					<ToggleGroupControl
						label={__('Label Position', 'outstand-forms')}
						value={labelPosition}
						isBlock
						onChange={onLabelPositionChange}
						help={__('Select the position of the label.', 'outstand-forms')}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					>
						{/* eslint-disable-next-line no-shadow */}
						{labelPositionOptions.map(({ value, label }) => {
							return (
								<ToggleGroupControlOption key={value} value={value} label={label} />
							);
						})}
					</ToggleGroupControl>
					<ToggleGroupControl
						label={__('Help Text Position', 'outstand-forms')}
						value={helpTextPosition}
						isBlock
						onChange={onHelpTextPositionChange}
						help={__('Select the position of the help text.', 'outstand-forms')}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					>
						{/* eslint-disable-next-line no-shadow */}
						{helpTextPositionOptions.map(({ value, label }) => {
							return (
								<ToggleGroupControlOption key={value} value={value} label={label} />
							);
						})}
					</ToggleGroupControl>
				</PanelBody>
			</InspectorControls>
			<InspectorAdvancedControls>
				<TextControl
					label={__('Name', 'outstand-forms')}
					value={resolveFieldName({ name: fieldName, fieldId })}
					onChange={onNameChange}
					autoComplete="off"
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
				{isDuplicateFieldName && (
					<Notice status="warning" isDismissible={false}>
						{__(
							'Another field in this form uses the same name. Only one value will be submitted.',
							'outstand-forms',
						)}
					</Notice>
				)}
				<TextControl
					label={__('ARIA Label', 'outstand-forms')}
					value={ariaLabel || label}
					onChange={onAriaLabelChange}
					help={__('The ARIA label for accessibility.', 'outstand-forms')}
					autoComplete="off"
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			</InspectorAdvancedControls>
		</>
	);
}
