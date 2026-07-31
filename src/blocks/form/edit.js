/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * WordPress dependencies
 */
import {
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
	InspectorAdvancedControls,
	__experimentalBlockVariationPicker as BlockVariationPicker,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	TextControl,
	PanelBody,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { useEffect, useMemo, useRef } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as blocksStore, createBlocksFromInnerBlocksTemplate } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './editor.css';
import { TEMPLATE } from './constants';
import { labelPositionOptions, helpTextPositionOptions } from '../../options';
import { useFieldBlocks } from '../../hooks/useFieldBlocks';
import { useFieldId } from '../../hooks/useFieldId';
import { useEmailFieldOptions } from '../../hooks/useEmailFieldOptions';
import { useIsDuplicateFormBlock } from '../../hooks/useIsDuplicateFormBlock';
import { getBlockId } from '../../utils';
import FormActions from '../../components/FormActions';

function FormEditContainer({ attributes, setAttributes, clientId }) {
	const {
		formId,
		formTitle,
		formAction,
		labelPosition,
		helpTextPosition,
		requiredIndicator,
		actions: formActions,
	} = attributes;

	const newFormId = useMemo(() => getBlockId(), []);

	const isDuplicate = useIsDuplicateFormBlock(clientId, attributes);
	const fieldBlocks = useFieldBlocks(clientId);
	const emailFieldOptions = useEmailFieldOptions(fieldBlocks);
	const { updateBlockAttributes } = useDispatch(blockEditorStore);
	const didResetFieldIds = useRef(false);

	useEffect(() => {
		if (!formId || isDuplicate) {
			setAttributes({ formId: newFormId });

			if (isDuplicate && !didResetFieldIds.current) {
				fieldBlocks.forEach((block) => {
					updateBlockAttributes(block.clientId, {
						fieldId: null,
						name: '',
					});
				});
				setAttributes({ nextFieldId: 1 });
				didResetFieldIds.current = true;
			}
		}
	}, [
		formId,
		isDuplicate,
		setAttributes,
		newFormId,
		clientId,
		fieldBlocks,
		updateBlockAttributes,
	]);

	useFieldId(attributes, setAttributes, fieldBlocks);

	const blockProps = useBlockProps();

	const innerBlocksProps = useInnerBlocksProps(blockProps, {
		__experimentalCaptureToolbars: true,
		template: TEMPLATE,
		templateLock: 'insert',
	});

	const onFormTitleChange = (value) => {
		setAttributes({ formTitle: value });
	};

	const onFormActionChange = (value) => {
		setAttributes({ formAction: value });
	};

	const onLabelPositionChange = (value) => {
		setAttributes({ labelPosition: value });
	};

	const onHelpTextPositionChange = (value) => {
		setAttributes({ helpTextPosition: value });
	};

	const onRequiredIndicatorChange = (value) => {
		setAttributes({ requiredIndicator: value });
	};

	const onUpdateActions = (updatedActions) => {
		setAttributes({ actions: updatedActions });
	};

	return (
		<>
			<div {...innerBlocksProps} />
			<InspectorControls>
				<PanelBody title={__('Settings', 'outstand-forms')}>
					<TextControl
						label={__('Title', 'outstand-forms')}
						value={formTitle}
						onChange={onFormTitleChange}
						autoComplete="off"
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<TextControl
						type="url"
						label={__('Action', 'outstand-forms')}
						value={formAction}
						onChange={onFormActionChange}
						autoComplete="off"
						help={__(
							'The URL where the form will be submitted. Leave blank to handle the submission automatically.',
							'outstand-forms',
						)}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</PanelBody>
				{!formAction && (
					<PanelBody title={__('Actions', 'outstand-forms')}>
						<FormActions
							actions={formActions || []}
							onUpdateActions={onUpdateActions}
							emailFieldOptions={emailFieldOptions}
						/>
					</PanelBody>
				)}
			</InspectorControls>
			<InspectorAdvancedControls>
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
						return <ToggleGroupControlOption key={value} value={value} label={label} />;
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
						return <ToggleGroupControlOption key={value} value={value} label={label} />;
					})}
				</ToggleGroupControl>
				<TextControl
					label={__('Required Indicator', 'outstand-forms')}
					value={requiredIndicator}
					onChange={onRequiredIndicatorChange}
					autoComplete="off"
					help={__(
						'The character or string used to indicate that a field is required. Leave blank to disable.',
						'outstand-forms',
					)}
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			</InspectorAdvancedControls>
		</>
	);
}

function FormVariationPicker({ clientId, name, setAttributes }) {
	const { blockType, defaultVariation, variations } = useSelect(
		(select) => {
			const { getBlockVariations, getBlockType, getDefaultBlockVariation } =
				select(blocksStore);

			return {
				blockType: getBlockType(name),
				defaultVariation: getDefaultBlockVariation(name, 'block'),
				variations: getBlockVariations(name, 'block'),
			};
		},
		[name],
	);

	const { replaceInnerBlocks } = useDispatch(blockEditorStore);

	const blockProps = useBlockProps();

	return (
		<div {...blockProps}>
			<BlockVariationPicker
				icon={blockType?.icon?.src}
				allowSkip={false}
				label={blockType?.title}
				variations={variations}
				instructions={__(
					'Choose a form template or start from scratch to create your own.',
					'outstand-forms',
				)}
				onSelect={(nextVariation = defaultVariation) => {
					if (nextVariation.attributes) {
						setAttributes(nextVariation.attributes);
					}
					if (nextVariation.innerBlocks) {
						replaceInnerBlocks(
							clientId,
							createBlocksFromInnerBlocksTemplate(nextVariation.innerBlocks),
							true,
						);
					}
				}}
			/>
		</div>
	);
}

export default function FormEdit(props) {
	const { clientId } = props;

	const hasInnerBlocks = useSelect(
		(select) => select(blockEditorStore).getBlocks(clientId).length > 0,
		[clientId],
	);

	const Component = hasInnerBlocks ? FormEditContainer : FormVariationPicker;

	return <Component {...props} />;
}
