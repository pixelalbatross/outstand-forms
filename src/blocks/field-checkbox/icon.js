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
			className="osf-icon osf-icon--checkbox"
			viewBox="0 0 24 24"
		>
			<Path d="M3 9a3 3 0 0 1 3-3h0a3 3 0 0 1 3 3v6a3 3 0 0 1-3 3h0a3 3 0 0 1-3-3z" />
			<Path d="m4.5 12 1.2 1.2L7.5 11M12 12h8" />
		</SVG>
	);
};
