<?php

namespace Outstand\WP\Forms;

use Outstand\WP\Forms\Blocks\FieldTurnstile;
use Outstand\WP\Forms\Components\Input;
use Outstand\WP\Forms\Fields\Field;

class Plugin {

	/**
	 * Plugin singleton instance.
	 *
	 * @var ?Plugin
	 */
	private static ?Plugin $instance = null;

	/**
	 * Retrieve the plugin instance.
	 *
	 * @return Plugin The plugin instance.
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Enable the plugin.
	 *
	 * @return void
	 */
	public function enable(): void {

		$modules = [
			new Blocks\FieldTurnstile(),
			new REST\V1\Forms(),
			new FormSubmission(),
			new EmailNotification(),
			new Settings(),
		];

		foreach ( $modules as $module ) {
			$can_register = $module->can_register();
			if ( ! $can_register ) {
				continue;
			}

			$module->register();
		}

		add_action( 'init', [ $this, 'register_blocks' ] );
		add_filter( 'block_categories_all', [ $this, 'register_block_categories' ] );
		add_filter( 'block_type_metadata', [ $this, 'filter_field_input_type_enum' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'blocks_editor_scripts' ] );
	}

	/**
	 * Registers the blocks using the metadata loaded from the `block.json` files.
	 *
	 * @return void
	 */
	public function register_blocks(): void {

		$block_json_files = glob( OUTSTAND_FORMS_PATH . 'build/blocks/*/block.json' );

		foreach ( $block_json_files as $filename ) {

			$block_folder = dirname( $filename );
			$block_type   = register_block_type_from_metadata( $block_folder );

			if ( ! empty( $block_type->editor_script_handles ) ) {
				foreach ( $block_type->editor_script_handles as $handle ) {
					wp_set_script_translations(
						$handle,
						'outstand-forms',
						OUTSTAND_FORMS_PATH . 'languages'
					);
				}
			}
		}
	}

	/**
	 * Enqueue editor-only JavaScript for blocks.
	 *
	 * @return void
	 */
	public function blocks_editor_scripts(): void {
		$is_configured = FieldTurnstile::is_configured();

		wp_localize_script(
			'osf-form-editor-script',
			'osfSettings',
			[
				'spam'                 => [
					'turnstile' => [
						'isConfigured' => $is_configured,
					],
				],
				'fieldBlockNames'      => FormBlockParser::FIELD_BLOCK_NAMES,
				'fieldTypes'           => FieldFactory::instance()->get_registered_types(),
				'unmaskableTypes'      => Input::UNMASKABLE_TYPES,
				'labelPositions'       => Field::LABEL_POSITIONS,
				'inlineLabelPositions' => Field::INLINE_LABEL_POSITIONS,
				'helpTextPositions'    => Field::HELP_TEXT_POSITIONS,
				'formActionIds'        => [
					'adminNotification' => EmailNotification::ACTION_ADMIN_NOTIFICATION,
					'userNotification'  => EmailNotification::ACTION_USER_NOTIFICATION,
				],
			]
		);
	}

	/**
	 * Registers the block categories.
	 *
	 * @param  array $categories The block categories.
	 * @return array The updated block categories.
	 */
	public function register_block_categories( array $categories ): array {

		$categories[] = [
			'slug'  => 'osf',
			'title' => esc_html__( 'Outstand Forms', 'outstand-forms' ),
		];

		return $categories;
	}

	/**
	 * Restrict the field-input block's `type` attribute to registered field types.
	 *
	 * `field-input/block.json` ships without an `enum` for `type` so the
	 * field type registry stays the single source of truth: third parties can
	 * register a type through the `outstand_forms_field_factory` filter and
	 * have it selectable without a block.json change. But an attribute with
	 * no `enum` is only validated as "a string" by
	 * {@see WP_Block_Type::prepare_attributes_for_render()}, so a type that
	 * no longer exists in the registry (a typo, or a plugin that registered
	 * it later deactivated) would otherwise reach
	 * {@see FieldFactory::create()} and throw. Rebuilding the `enum` here,
	 * from the same registry, restores that safety net: an unrecognized
	 * value reverts to the attribute's default at render time, exactly as it
	 * did when the six built-ins were hardcoded.
	 *
	 * @param array $metadata Block type metadata.
	 * @return array The (possibly) updated block type metadata.
	 */
	public function filter_field_input_type_enum( array $metadata ): array {

		$name = $metadata['name'] ?? '';
		if ( 'osf/field-input' !== $name ) {
			return $metadata;
		}

		if ( ! isset( $metadata['attributes']['type'] ) ) {
			return $metadata;
		}

		$input_types = array_filter(
			FieldFactory::instance()->get_registered_types(),
			static function ( array $field_type ): bool {
				return 'input' === $field_type['control'];
			}
		);

		$metadata['attributes']['type']['enum'] = array_values( wp_list_pluck( $input_types, 'type' ) );

		return $metadata;
	}
}
