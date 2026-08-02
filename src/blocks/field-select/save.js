/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

// A dynamic block still has to serialize its children: without this the option
// blocks live only in the editor's store and the saved field is self-closing,
// which renders a control with no choices in it.
export default function ChoiceFieldSave() {
	return <InnerBlocks.Content />;
}
