export default function Checkbox({ attributes }) {
	const { required, defaultValue } = attributes;

	// The editor preview is not a working control: ticking it in the canvas
	// would mean nothing, and the default is set from the sidebar.
	return (
		<input
			type="checkbox"
			className="osf-field__checkbox"
			checked={defaultValue === '1'}
			aria-required={required}
			readOnly
			tabIndex={-1}
		/>
	);
}
