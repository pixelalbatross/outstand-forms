/* eslint-disable @wordpress/no-unsafe-wp-apis */
/* eslint-disable import/no-extraneous-dependencies */
/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { __experimentalHStack as HStack, __experimentalText as Text } from '@wordpress/components';
import { Icon } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { icon } from './icon';

export default function FormErrorsEdit() {
	const blockProps = useBlockProps();

	return (
		<HStack {...blockProps} alignment="center" justify="flex-start" spacing={2}>
			<Icon icon={icon()} width="24" height="24" />
			<Text>{__('Error messages will be displayed here.', 'outstand-forms')}</Text>
		</HStack>
	);
}
