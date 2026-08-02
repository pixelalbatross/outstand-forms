/**
 * Internal dependencies
 */
import ChoiceFieldEdit from '../../components/ChoiceFieldEdit';

export default function FieldRadioEdit({ attributes, setAttributes, context, clientId }) {
	return (
		<ChoiceFieldEdit
			type="radio"
			display="radio"
			attributes={attributes}
			setAttributes={setAttributes}
			context={context}
			clientId={clientId}
		/>
	);
}
