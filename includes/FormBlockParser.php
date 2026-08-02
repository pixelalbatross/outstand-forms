<?php

namespace Outstand\WP\Forms;

class FormBlockParser {

	/**
	 * Block names that represent form fields.
	 *
	 * @var string[]
	 */
	public const FIELD_BLOCK_NAMES = [
		'osf/field-input',
		'osf/field-textarea',
		'osf/field-select',
		'osf/field-radio',
		'osf/field-checkbox',
		'osf/field-consent',
	];

	/**
	 * Field blocks whose type is the block itself, not a `type` attribute.
	 *
	 * `osf/field-input` carries its type in an attribute because six types
	 * share it; every other field block is one type.
	 *
	 * @var array<string, string>
	 */
	private const BLOCK_FIELD_TYPES = [
		'osf/field-textarea' => 'textarea',
		'osf/field-select'   => 'select',
		'osf/field-radio'    => 'radio',
		'osf/field-checkbox' => 'checkbox',
		'osf/field-consent'  => 'consent',
	];

	/**
	 * Field factory instance.
	 *
	 * @var FieldFactory
	 */
	private FieldFactory $field_factory;

	/**
	 * Constructor.
	 *
	 * @param FieldFactory|null $field_factory Optional field factory instance.
	 *                                        Defaults to the shared, filtered registry.
	 */
	public function __construct( ?FieldFactory $field_factory = null ) {
		$this->field_factory = $field_factory ?? FieldFactory::instance();
	}

	/**
	 * Extract form attributes and field configurations.
	 *
	 * @param string $form_id The form ID to find.
	 * @param int    $post_id The post ID containing the form.
	 * @return array{form_attributes: array, field_configs: array}
	 */
	public function extract_form_data( string $form_id, int $post_id ): array {

		$post = get_post( $post_id );

		if ( ! $post || empty( $post->post_content ) ) {
			return [
				'form_attributes' => [],
				'field_configs'   => [],
			];
		}

		$blocks     = parse_blocks( $post->post_content );
		$form_block = $this->find_form_block( $blocks, $form_id );

		if ( ! $form_block ) {
			return [
				'form_attributes' => [],
				'field_configs'   => [],
			];
		}

		$field_blocks = $this->collect_field_blocks( $form_block['innerBlocks'] ?? [] );

		return [
			'form_attributes' => $form_block['attrs'] ?? [],
			'field_configs'   => $this->build_field_configs( $field_blocks ),
		];
	}

	/**
	 * Find a specific block within a form and return it.
	 *
	 * @param string $form_id    The form ID to find.
	 * @param int    $post_id    The post ID containing the form.
	 * @param string $block_name The block name to search for.
	 * @return array|null The found block, or null.
	 */
	public function find_block( string $form_id, int $post_id, string $block_name ): ?array {

		$post = get_post( $post_id );

		if ( ! $post || empty( $post->post_content ) ) {
			return null;
		}

		$blocks     = parse_blocks( $post->post_content );
		$form_block = $this->find_form_block( $blocks, $form_id );

		if ( ! $form_block ) {
			return null;
		}

		return $this->find_block_by_name( $form_block['innerBlocks'] ?? [], $block_name );
	}

	/**
	 * Recursively find a block by name within inner blocks.
	 *
	 * @param array  $blocks     The blocks to search.
	 * @param string $block_name The block name to find.
	 * @return array|null The found block, or null.
	 */
	public function find_block_by_name( array $blocks, string $block_name ): ?array {

		foreach ( $blocks as $block ) {
			if ( $block_name === $block['blockName'] ) {
				return $block;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$found = $this->find_block_by_name( $block['innerBlocks'], $block_name );

				if ( $found ) {
					return $found;
				}
			}
		}

		return null;
	}

	/**
	 * Recursively find the osf/form block with the matching form ID.
	 *
	 * @param array  $blocks  Parsed blocks to search.
	 * @param string $form_id The form ID to match.
	 * @return array|null The matching form block, or null.
	 */
	private function find_form_block( array $blocks, string $form_id ): ?array {

		foreach ( $blocks as $block ) {
			// Resolve synced patterns (core/block).
			if ( 'core/block' === $block['blockName'] && ! empty( $block['attrs']['ref'] ) ) {
				$reusable_post = get_post( $block['attrs']['ref'] );

				if ( $reusable_post && ! empty( $reusable_post->post_content ) ) {
					$inner    = parse_blocks( $reusable_post->post_content );
					$resolved = $this->find_form_block( $inner, $form_id );

					if ( $resolved ) {
						return $resolved;
					}
				}

				continue;
			}

			if ( 'osf/form' === $block['blockName'] ) {
				$block_form_id = $block['attrs']['formId'] ?? '';

				if ( $block_form_id === $form_id ) {
					return $block;
				}
			}

			// Recurse into inner blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$found = $this->find_form_block( $block['innerBlocks'], $form_id );

				if ( $found ) {
					return $found;
				}
			}
		}

		return null;
	}

	/**
	 * Recursively collect field blocks from inner blocks.
	 *
	 * @param array $blocks Inner blocks to search.
	 * @return array Field blocks found.
	 */
	private function collect_field_blocks( array $blocks ): array {

		$field_blocks = [];
		foreach ( $blocks as $block ) {
			if ( in_array( $block['blockName'], self::FIELD_BLOCK_NAMES, true ) ) {
				$field_blocks[] = $block;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$field_blocks = array_merge( $field_blocks, $this->collect_field_blocks( $block['innerBlocks'] ) );
			}
		}

		return $field_blocks;
	}

	/**
	 * Build field configurations from collected field blocks.
	 *
	 * @param array $field_blocks The field blocks.
	 * @return array Field configurations keyed by field name.
	 */
	private function build_field_configs( array $field_blocks ): array {

		$configs = [];
		foreach ( $field_blocks as $block ) {
			$type  = $this->get_field_type( $block );
			$attrs = $block['attrs'] ?? [];

			if ( ! $this->field_factory->supports( $type ) ) {
				continue;
			}

			// A choice field's options are authored as child blocks, so they
			// have to be folded into the attributes before the field is built:
			// its rules are derived from them.
			$attrs['options'] = Options::from_parsed_blocks( $block['innerBlocks'] ?? [] );

			$field      = $this->field_factory->create( $type, $attrs );
			$field_name = $field->get_field_name();

			$configs[ $field_name ] = [
				'type'             => $type,
				'label'            => $attrs['label'] ?? '',
				'fieldId'          => $attrs['fieldId'] ?? '',
				'options'          => $attrs['options'],
				'validation_rules' => $field->get_validation_rules(),
			];
		}

		return $configs;
	}

	/**
	 * Determine the field type from a block.
	 *
	 * @param array $block The parsed block.
	 * @return string The field type.
	 */
	private function get_field_type( array $block ): string {
		return self::BLOCK_FIELD_TYPES[ $block['blockName'] ] ?? ( $block['attrs']['type'] ?? 'text' );
	}
}
