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
	SelectControl,
	TextControl,
	ToggleControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { labelPositionOptions, helpTextPositionOptions, autocompleteOptions } from '../../options';
import { useIsDuplicateFieldName } from '../../hooks/useIsDuplicateFieldName';
import { resolveFieldName } from '../../utils';
import Field from '../../fields';

export default function FieldTextareaEdit({ attributes, setAttributes, context, clientId }) {
	const {
		'osf/labelPosition': defaultLabelPosition,
		'osf/helpTextPosition': defaultHelpTextPosition,
	} = context;

	const {
		fieldId,
		name: fieldName,
		defaultValue,
		required,
		placeholder,
		autocomplete,
		minLength,
		maxLength,
		rows,
		cols,
		mask,
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
			'osf-field-textarea',
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

	const onDefaultValueChange = (value) => {
		setAttributes({ defaultValue: value });
	};

	const onRequiredChange = (value) => {
		setAttributes({ required: value });
	};

	const onPlaceholderChange = (value) => {
		setAttributes({ placeholder: value });
	};

	const onAutocompleteChange = (value) => {
		setAttributes({ autocomplete: value });
	};

	const onMinLengthChange = (value) => {
		setAttributes({
			minLength: value !== '' ? parseInt(value, 10) : undefined,
		});
	};

	const onMaxLengthChange = (value) => {
		setAttributes({
			maxLength: value !== '' ? parseInt(value, 10) : undefined,
		});
	};

	const onRowsChange = (value) => {
		setAttributes({ rows: parseInt(value, 10) });
	};

	const onColsChange = (value) => {
		setAttributes({ cols: parseInt(value, 10) });
	};

	const onMaskChange = (value) => {
		setAttributes({ mask: value });
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
					type="textarea"
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
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<TextControl
						label={__('Default Value', 'outstand-forms')}
						value={defaultValue}
						onChange={onDefaultValueChange}
						autoComplete="off"
						help={__('The value that will be displayed by default.', 'outstand-forms')}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<TextControl
						label={__('Placeholder', 'outstand-forms')}
						value={placeholder}
						onChange={onPlaceholderChange}
						autoComplete="off"
						help={__(
							'The text that will be displayed as a placeholder.',
							'outstand-forms',
						)}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<NumberControl
						label={__('Min Characters', 'outstand-forms')}
						value={minLength}
						min={0}
						onChange={onMinLengthChange}
						help={__('Minimum number of characters required.', 'outstand-forms')}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<NumberControl
						label={__('Max Characters', 'outstand-forms')}
						value={maxLength}
						min={0}
						onChange={onMaxLengthChange}
						help={__('Maximum number of characters allowed.', 'outstand-forms')}
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
					<NumberControl
						label={__('Rows', 'outstand-forms')}
						value={rows}
						min={1}
						onChange={onRowsChange}
						help={__('Number of rows.', 'outstand-forms')}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<NumberControl
						label={__('Columns', 'outstand-forms')}
						value={cols}
						min={1}
						onChange={onColsChange}
						help={__('Number of columns.', 'outstand-forms')}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
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
				<SelectControl
					label={__('Autocomplete', 'outstand-forms')}
					value={autocomplete}
					options={autocompleteOptions}
					onChange={onAutocompleteChange}
					help={__('Define how browsers autofill this field.', 'outstand-forms')}
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
				<TextControl
					label={__('ARIA Label', 'outstand-forms')}
					value={ariaLabel || label}
					onChange={onAriaLabelChange}
					help={__('The ARIA label for accessibility.', 'outstand-forms')}
					autoComplete="off"
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
				<TextControl
					label={__('Mask', 'outstand-forms')}
					value={mask}
					onChange={onMaskChange}
					autoComplete="off"
					help={__('The mask that will be applied to the field.', 'outstand-forms')}
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			</InspectorAdvancedControls>
		</>
	);
}
