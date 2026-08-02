/* eslint-disable import/no-extraneous-dependencies */
/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * WordPress dependencies
 */
import { __experimentalNumberControl as NumberControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ChoiceFieldEdit from '../../components/ChoiceFieldEdit';

export default function FieldCheckboxEdit({ attributes, setAttributes, context, clientId }) {
	const { minSelected, maxSelected } = attributes;

	const onMinSelectedChange = (value) => {
		setAttributes({
			minSelected: value !== '' ? parseInt(value, 10) : undefined,
		});
	};

	const onMaxSelectedChange = (value) => {
		setAttributes({
			maxSelected: value !== '' ? parseInt(value, 10) : undefined,
		});
	};

	return (
		<ChoiceFieldEdit
			type="checkbox"
			display="checkbox"
			attributes={attributes}
			setAttributes={setAttributes}
			context={context}
			clientId={clientId}
		>
			<NumberControl
				label={__('Min Selected', 'outstand-forms')}
				value={minSelected}
				min={0}
				onChange={onMinSelectedChange}
				help={__('Minimum number of options that must be checked.', 'outstand-forms')}
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
			<NumberControl
				label={__('Max Selected', 'outstand-forms')}
				value={maxSelected}
				min={0}
				onChange={onMaxSelectedChange}
				help={__('Maximum number of options that may be checked.', 'outstand-forms')}
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
		</ChoiceFieldEdit>
	);
}
