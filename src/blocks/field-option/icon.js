/**
 * WordPress dependencies
 */
import { SVG, Path } from '@wordpress/primitives';

export const icon = () => {
	return (
		<SVG
			xmlns="http://www.w3.org/2000/svg"
			fill="none"
			stroke="currentColor"
			strokeLinecap="round"
			strokeLinejoin="round"
			strokeWidth="1.5"
			className="osf-icon osf-icon--option"
			viewBox="0 0 24 24"
		>
			<Path d="M5 12h14M5 8h9M5 16h9" />
		</SVG>
	);
};
