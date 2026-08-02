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
			className="osf-icon osf-icon--select"
			viewBox="0 0 24 24"
		>
			<Path d="M4 6h16v12H4z" />
			<Path d="m9 11 3 3 3-3" />
		</SVG>
	);
};
