/**
 * WordPress dependencies
 */
import { Notice } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Label from '../components/Label';
import HelpText from '../components/HelpText';
import Input from '../components/Input';
import Textarea from '../components/Textarea';
import { isInlineLabelPosition, resolveFieldControl } from '../utils';

export default function Field({
	type = 'text',
	attributes,
	setAttributes,
	context,
	showFieldId = false,
}) {
	const {
		'osf/labelPosition': defaultLabelPosition,
		'osf/helpTextPosition': defaultHelpTextPosition,
	} = context;

	const {
		fieldId,
		labelPosition = defaultLabelPosition,
		helpTextPosition = defaultHelpTextPosition,
	} = attributes;

	const hasInlineLabel = isInlineLabelPosition(labelPosition);

	const label = <Label attributes={attributes} setAttributes={setAttributes} context={context} />;
	const helpText = (
		<HelpText attributes={attributes} setAttributes={setAttributes} context={context} />
	);

	// The `control` names the JS component that renders a type, mirroring
	// `FieldFactory::get_registered_types()` on the server. A type absent
	// from the registry — unregistered, or from a plugin that has since
	// been deactivated — resolves to `undefined` and falls through to the
	// warning below rather than rendering a silent blank gap.
	const control = resolveFieldControl(type);

	let field;
	switch (control) {
		case 'input':
			field = (
				<Input
					type={type}
					attributes={attributes}
					setAttributes={setAttributes}
					context={context}
				/>
			);
			break;
		case 'textarea':
			field = (
				<Textarea attributes={attributes} setAttributes={setAttributes} context={context} />
			);
			break;
		default:
			field = (
				<Notice status="warning" isDismissible={false}>
					{sprintf(
						/* translators: %s is the field type */
						__(
							'Unknown field type "%s". Is the plugin that registers it active?',
							'outstand-forms',
						),
						type,
					)}
				</Notice>
			);
			break;
	}

	return (
		<>
			{showFieldId && fieldId && (
				<span className="osf-field__id">
					{sprintf(
						/* translators: %s is the field ID */
						__('ID: %s', 'outstand-forms'),
						fieldId,
					)}
				</span>
			)}

			{labelPosition !== 'right' && label}
			{!hasInlineLabel && helpTextPosition === 'top' && helpText}

			{hasInlineLabel ? (
				<div className="osf-field__wrapper">
					{helpTextPosition === 'top' && helpText}
					{field}
					{helpTextPosition === 'bottom' && helpText}
				</div>
			) : (
				field
			)}

			{!hasInlineLabel && helpTextPosition === 'bottom' && helpText}
			{labelPosition === 'right' && label}
		</>
	);
}
