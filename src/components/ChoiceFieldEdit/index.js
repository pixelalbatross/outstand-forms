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
import ChoiceOptions from '../ChoiceOptions';
import Field from '../../fields';

/**
 * The editor shell shared by the select, radio and checkbox field blocks.
 *
 * These three differ in the control they render and in the settings they take,
 * not in how a field is put together — label, help text, name and positioning
 * are identical. Blocks pass their own extra settings in as children.
 *
 * @param {Object}   props               Component props.
 * @param {string}   props.type          Field type, as registered on the server.
 * @param {string}   props.display       How the options render (select, radio, checkbox).
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Block attribute setter.
 * @param {Object}   props.context       Block context.
 * @param {string}   props.clientId      Block client ID.
 * @param {Element}  props.children      Extra settings, rendered in the Settings panel.
 * @return {Element} The block editor UI.
 */
export default function ChoiceFieldEdit({
	type,
	display,
	attributes,
	setAttributes,
	context,
	clientId,
	children = null,
}) {
	const {
		'osf/labelPosition': defaultLabelPosition,
		'osf/helpTextPosition': defaultHelpTextPosition,
	} = context;

	const {
		fieldId,
		name: fieldName,
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
			`osf-field-${type}`,
			`osf-field--label-${labelPosition}`,
			`osf-field-${type}--label-${labelPosition}`,
			`osf-field-${type}--help-${helpTextPosition}`,
			{
				[`osf-field-${type}--required`]: required,
				[`osf-field-${type}--has-label`]: !!label,
				[`osf-field-${type}--has-help`]: !!helpText,
			},
		),
	});

	const onNameChange = (value) => {
		setAttributes({ name: value.trim() });
	};

	const onRequiredChange = (value) => {
		setAttributes({ required: value });
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
					type={type}
					attributes={attributes}
					setAttributes={setAttributes}
					context={context}
					options={<ChoiceOptions display={display} clientId={clientId} />}
					showFieldId
				/>
			</div>
			<InspectorControls>
				<PanelBody title={__('Settings', 'outstand-forms')}>
					<ToggleControl
						label={__('Required', 'outstand-forms')}
						checked={required}
						onChange={onRequiredChange}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					{children}
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
