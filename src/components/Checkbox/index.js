export default function Checkbox({ attributes }) {
	const { required, defaultValue } = attributes;

	// The editor preview is not a working control: ticking it in the canvas
	// would mean nothing, and the default is set from the sidebar.
	//
	// Wrapped to match the rendered markup, where the wrapper absorbs the
	// stretch a field's column layout would otherwise apply to the box itself.
	return (
		<span className="osf-field__checkbox-field">
			<input
				type="checkbox"
				className="osf-field__checkbox"
				checked={defaultValue === '1'}
				aria-required={required}
				readOnly
				tabIndex={-1}
			/>
		</span>
	);
}
