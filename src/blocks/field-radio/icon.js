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
			className="osf-icon osf-icon--radio"
			viewBox="0 0 24 24"
		>
			<Path d="M6 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7ZM6 11a1 1 0 1 0 0 2 1 1 0 0 0 0-2ZM12 12h8" />
		</SVG>
	);
};
