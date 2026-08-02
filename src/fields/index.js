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
import Checkbox from '../components/Checkbox';
import { isInlineLabelPosition, resolveFieldControl } from '../utils';

export default function Field({
	type = 'text',
	attributes,
	setAttributes,
	context,
	showFieldId = false,
	options = null,
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
		// A choice field's control is its option children, so the block passes
		// the inner-blocks list in rather than the field building one.
		case 'select':
		case 'radio':
		case 'checkbox':
			field = options;
			break;
		case 'consent':
			field = <Checkbox attributes={attributes} />;
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

			{helpTextPosition === 'top' && helpText}

			{/* An inline label sits beside the control, so the two are kept in
			    one row and the help text is left out of it. On the front end the
			    wrapper holds the help text too, but there it is either empty or
			    real content; here it is an "Add help text" placeholder, which
			    would size the row and push the label away from its control. */}
			{hasInlineLabel ? (
				<div className="osf-field__row">
					{labelPosition !== 'right' && label}
					<div className="osf-field__wrapper">{field}</div>
					{labelPosition === 'right' && label}
				</div>
			) : (
				<>
					{labelPosition !== 'right' && label}
					{field}
					{labelPosition === 'right' && label}
				</>
			)}

			{helpTextPosition === 'bottom' && helpText}
		</>
	);
}
