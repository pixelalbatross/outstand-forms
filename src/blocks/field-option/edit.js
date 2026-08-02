/* eslint-disable import/no-extraneous-dependencies */
/**
 * External dependencies
 */
import clsx from 'clsx';

/**
 * WordPress dependencies
 */
import { useBlockProps, RichText, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function FieldOptionEdit({ attributes, setAttributes, context }) {
	const { label, value } = attributes;
	const { 'osf/optionDisplay': display = 'select' } = context;

	const blockProps = useBlockProps({
		className: clsx('osf-option', `osf-option--${display}`),
	});

	const onLabelChange = (nextLabel) => {
		setAttributes({ label: nextLabel });
	};

	const onValueChange = (nextValue) => {
		setAttributes({ value: nextValue.trim() });
	};

	return (
		<>
			<div {...blockProps}>
				{/* A radio or checkbox option looks like what it will become;
				    a select option is a plain row, since a real <option> can't
				    host an editable label. */}
				{display !== 'select' && (
					<input
						type={display}
						className={`osf-field__choice-input osf-field__choice-input--${display}`}
						disabled
						aria-hidden="true"
						tabIndex={-1}
					/>
				)}
				<RichText
					tagName="span"
					className="osf-option__label"
					value={label}
					onChange={onLabelChange}
					aria-label={
						label
							? __('Option label', 'outstand-forms')
							: __('Empty option', 'outstand-forms')
					}
					placeholder={__('Type an option', 'outstand-forms')}
					allowedFormats={[]}
				/>
			</div>
			<InspectorControls>
				<PanelBody title={__('Settings', 'outstand-forms')}>
					<TextControl
						label={__('Value', 'outstand-forms')}
						value={value}
						onChange={onValueChange}
						autoComplete="off"
						help={__(
							'The value submitted when this option is chosen. Defaults to the label.',
							'outstand-forms',
						)}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
		</>
	);
}
