<?php

namespace Outstand\WP\Forms;

/**
 * Collects the option children of a choice field.
 *
 * Options are authored as `osf/field-option` blocks, but the field system
 * downstream — {@see \Outstand\WP\Forms\Fields\Field}, the components, the
 * validators — only ever sees plain arrays. This is the one place that
 * converts, and it has to do it from two different shapes: the parsed block
 * arrays `FormBlockParser` works with, and the `WP_Block` instances a
 * `render.php` receives.
 */
class Options {

	/**
	 * The block that declares a single option.
	 *
	 * @var string
	 */
	public const OPTION_BLOCK_NAME = 'osf/field-option';

	/**
	 * Collect options from parsed inner blocks.
	 *
	 * Parsed blocks are nested arrays with `blockName`, `attrs` and
	 * `innerBlocks` keys, the shape `parse_blocks()` returns.
	 *
	 * @param array $blocks Inner blocks of a choice field.
	 * @return array<int, array{label: string, value: string}>
	 */
	public static function from_parsed_blocks( array $blocks ): array {

		$options = [];

		foreach ( $blocks as $block ) {
			if ( ( $block['blockName'] ?? '' ) !== self::OPTION_BLOCK_NAME ) {
				continue;
			}

			$option = self::to_option( $block['attrs'] ?? [] );

			if ( null !== $option ) {
				$options[] = $option;
			}
		}

		return $options;
	}

	/**
	 * Collect options from a rendered block's inner blocks.
	 *
	 * @param iterable $blocks Inner blocks of a choice field, as `WP_Block` instances.
	 * @return array<int, array{label: string, value: string}>
	 */
	public static function from_block_list( iterable $blocks ): array {

		$options = [];

		foreach ( $blocks as $block ) {
			if ( self::OPTION_BLOCK_NAME !== $block->name ) {
				continue;
			}

			$option = self::to_option( $block->attributes ?? [] );

			if ( null !== $option ) {
				$options[] = $option;
			}
		}

		return $options;
	}

	/**
	 * Reduce a list of options to the values a submission may carry.
	 *
	 * @param array $options Collected options.
	 * @return array<int, string>
	 */
	public static function get_values( array $options ): array {
		return array_column( $options, 'value' );
	}

	/**
	 * Build a single option from an option block's attributes.
	 *
	 * An option with no label and no value describes nothing and is dropped —
	 * a freshly inserted, not-yet-filled-in option block would otherwise
	 * render an empty choice and widen the submitted value allowlist with an
	 * empty string.
	 *
	 * @param array $attributes Option block attributes.
	 * @return array{label: string, value: string}|null
	 */
	private static function to_option( array $attributes ): ?array {

		$label = (string) ( $attributes['label'] ?? '' );
		$value = (string) ( $attributes['value'] ?? '' );

		if ( '' === $label && '' === $value ) {
			return null;
		}

		return [
			'label' => $label,
			// An option that only has a label submits its label, matching what
			// an author who left the value blank would expect to receive.
			'value' => '' === $value ? $label : $value,
		];
	}
}
