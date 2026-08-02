/* eslint-disable import/no-extraneous-dependencies */
/**
 * WordPress dependencies
 */
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ChoiceFieldEdit from '../../components/ChoiceFieldEdit';

export default function FieldSelectEdit({ attributes, setAttributes, context, clientId }) {
	const { placeholder } = attributes;

	const onPlaceholderChange = (value) => {
		setAttributes({ placeholder: value });
	};

	return (
		<ChoiceFieldEdit
			type="select"
			display="select"
			attributes={attributes}
			setAttributes={setAttributes}
			context={context}
			clientId={clientId}
		>
			<TextControl
				label={__('Placeholder', 'outstand-forms')}
				value={placeholder}
				onChange={onPlaceholderChange}
				autoComplete="off"
				help={__(
					'Shown as the first, unselected entry — for example "Choose a country".',
					'outstand-forms',
				)}
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
		</ChoiceFieldEdit>
	);
}
