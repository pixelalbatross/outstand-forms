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
			className="osf-icon osf-icon--consent"
			viewBox="0 0 24 24"
		>
			<Path d="M4 5h8M4 5v14h16V9" />
			<Path d="m14 10 3 3 5-6" />
		</SVG>
	);
};
