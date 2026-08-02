<?php

namespace Outstand\WP\Forms\Tests\Unit;

use Outstand\WP\Forms\FieldFactory;
use Outstand\WP\Forms\FormBlockParser;

class FormBlockParserTest extends \WP_UnitTestCase {

	private const FORM_CONTENT = '<!-- wp:osf/form {"formId":"form-a"} --><!-- wp:osf/form-fields --><!-- wp:osf/field-input {"fieldId":1,"type":"email","required":true,"label":"Email"} /--><!-- wp:osf/field-input {"fieldId":2,"type":"text","name":"nickname","label":"Nickname"} /--><!-- wp:osf/field-textarea {"fieldId":3,"label":"Message"} /--><!-- /wp:osf/form-fields --><!-- wp:osf/form-submit /--><!-- /wp:osf/form -->';

	/**
	 * Field configs must be keyed by resolved field name with derived rules.
	 */
	public function test_extract_form_data_builds_field_configs(): void {
		$post_id = self::factory()->post->create( [ 'post_content' => self::FORM_CONTENT ] );

		$parser = new FormBlockParser();
		$data   = $parser->extract_form_data( 'form-a', $post_id );

		$this->assertSame( 'form-a', $data['form_attributes']['formId'] );
		$this->assertSame( [ 'field_1', 'nickname', 'field_3' ], array_keys( $data['field_configs'] ) );

		$email_config = $data['field_configs']['field_1'];
		$this->assertSame( 'email', $email_config['type'] );
		$this->assertTrue( $email_config['validation_rules']['required'] );
		$this->assertTrue( $email_config['validation_rules']['email'] );

		$textarea_config = $data['field_configs']['field_3'];
		$this->assertSame( 'textarea', $textarea_config['type'] );
	}

	/**
	 * An unknown form ID must return empty structures.
	 */
	public function test_extract_form_data_returns_empty_for_unknown_form(): void {
		$post_id = self::factory()->post->create( [ 'post_content' => self::FORM_CONTENT ] );

		$parser = new FormBlockParser();
		$data   = $parser->extract_form_data( 'missing-form', $post_id );

		$this->assertSame( [], $data['form_attributes'] );
		$this->assertSame( [], $data['field_configs'] );
	}

	/**
	 * Forms nested in wrapper blocks must still be found.
	 */
	public function test_extract_form_data_finds_nested_form(): void {
		$nested  = '<!-- wp:group --><div class="wp-block-group">' . self::FORM_CONTENT . '</div><!-- /wp:group -->';
		$post_id = self::factory()->post->create( [ 'post_content' => $nested ] );

		$parser = new FormBlockParser();
		$data   = $parser->extract_form_data( 'form-a', $post_id );

		$this->assertNotEmpty( $data['field_configs'] );
	}

	/**
	 * Forms inside synced patterns (core/block) must be resolved.
	 */
	public function test_extract_form_data_resolves_synced_pattern(): void {
		$pattern_id = self::factory()->post->create(
			[
				'post_type'    => 'wp_block',
				'post_content' => self::FORM_CONTENT,
			]
		);
		$post_id    = self::factory()->post->create(
			[ 'post_content' => sprintf( '<!-- wp:block {"ref":%d} /-->', $pattern_id ) ]
		);

		$parser = new FormBlockParser();
		$data   = $parser->extract_form_data( 'form-a', $post_id );

		$this->assertSame( 'form-a', $data['form_attributes']['formId'] );
		$this->assertNotEmpty( $data['field_configs'] );
	}

	/**
	 * The find_block() method must locate an inner block by name within the form.
	 */
	public function test_find_block_locates_inner_block(): void {
		$post_id = self::factory()->post->create( [ 'post_content' => self::FORM_CONTENT ] );

		$parser = new FormBlockParser();
		$block  = $parser->find_block( 'form-a', $post_id, 'osf/form-submit' );

		$this->assertSame( 'osf/form-submit', $block['blockName'] );
		$this->assertNull( $parser->find_block( 'form-a', $post_id, 'osf/nonexistent' ) );
	}

	/**
	 * A non-existent post must return empty structures rather than erroring.
	 */
	public function test_extract_form_data_returns_empty_for_missing_post(): void {
		$parser = new FormBlockParser();
		$data   = $parser->extract_form_data( 'form-a', 999999999 );

		$this->assertSame( [], $data['form_attributes'] );
		$this->assertSame( [], $data['field_configs'] );
	}

	/**
	 * A post with empty content must return empty structures.
	 */
	public function test_extract_form_data_returns_empty_for_empty_content(): void {
		$post_id = self::factory()->post->create( [ 'post_content' => '' ] );

		$parser = new FormBlockParser();
		$data   = $parser->extract_form_data( 'form-a', $post_id );

		$this->assertSame( [], $data['form_attributes'] );
		$this->assertSame( [], $data['field_configs'] );
	}

	/**
	 * The find_block() method must return null rather than error for a non-existent post.
	 */
	public function test_find_block_returns_null_for_missing_post(): void {
		$parser = new FormBlockParser();

		$this->assertNull( $parser->find_block( 'form-a', 999999999, 'osf/form-submit' ) );
	}

	/**
	 * A field block wrapped in another block (e.g. a group) inside form-fields
	 * must still be collected into the field configs.
	 */
	public function test_extract_form_data_collects_field_blocks_nested_in_wrappers(): void {
		$content = '<!-- wp:osf/form {"formId":"form-nested-fields"} --><!-- wp:osf/form-fields --><!-- wp:group --><!-- wp:osf/field-input {"fieldId":1,"type":"text","label":"Nested"} /--><!-- /wp:group --><!-- /wp:osf/form-fields --><!-- /wp:osf/form -->';
		$post_id = self::factory()->post->create( [ 'post_content' => $content ] );

		$parser = new FormBlockParser();
		$data   = $parser->extract_form_data( 'form-nested-fields', $post_id );

		$this->assertArrayHasKey( 'field_1', $data['field_configs'] );
	}

	/**
	 * The find_block() method must recurse through wrapper blocks to find the target block.
	 */
	public function test_find_block_recurses_into_nested_wrappers(): void {
		$content = '<!-- wp:osf/form {"formId":"form-deep"} --><!-- wp:osf/form-fields --><!-- wp:group --><!-- wp:osf/field-turnstile /--><!-- /wp:group --><!-- /wp:osf/form-fields --><!-- /wp:osf/form -->';
		$post_id = self::factory()->post->create( [ 'post_content' => $content ] );

		$parser = new FormBlockParser();
		$block  = $parser->find_block( 'form-deep', $post_id, 'osf/field-turnstile' );

		$this->assertSame( 'osf/field-turnstile', $block['blockName'] );
	}

	/**
	 * A field type unsupported by the field factory must be skipped, while
	 * sibling supported fields must still be built.
	 */
	public function test_extract_form_data_skips_unsupported_field_type(): void {
		$content = '<!-- wp:osf/form {"formId":"form-unsupported"} --><!-- wp:osf/form-fields --><!-- wp:osf/field-input {"fieldId":1,"type":"not-a-real-type","label":"Ghost"} /--><!-- wp:osf/field-input {"fieldId":2,"type":"text","label":"Real"} /--><!-- /wp:osf/form-fields --><!-- /wp:osf/form -->';
		$post_id = self::factory()->post->create( [ 'post_content' => $content ] );

		$parser = new FormBlockParser();
		$data   = $parser->extract_form_data( 'form-unsupported', $post_id );

		$this->assertArrayNotHasKey( 'field_1', $data['field_configs'] );
		$this->assertArrayHasKey( 'field_2', $data['field_configs'] );
	}

	/**
	 * A field-input block without a `type` attribute must default to `text`.
	 */
	public function test_field_input_without_type_attribute_defaults_to_text(): void {
		$content = '<!-- wp:osf/form {"formId":"form-default-type"} --><!-- wp:osf/form-fields --><!-- wp:osf/field-input {"fieldId":1,"label":"Untyped"} /--><!-- /wp:osf/form-fields --><!-- /wp:osf/form -->';
		$post_id = self::factory()->post->create( [ 'post_content' => $content ] );

		$parser = new FormBlockParser();
		$data   = $parser->extract_form_data( 'form-default-type', $post_id );

		$this->assertSame( 'text', $data['field_configs']['field_1']['type'] );
	}

	/**
	 * A field-textarea block's own `type` attribute must be ignored; its type
	 * is always resolved to `textarea`.
	 */
	public function test_field_textarea_type_is_always_textarea(): void {
		$content = '<!-- wp:osf/form {"formId":"form-textarea-type"} --><!-- wp:osf/form-fields --><!-- wp:osf/field-textarea {"fieldId":1,"type":"email","label":"Message"} /--><!-- /wp:osf/form-fields --><!-- /wp:osf/form -->';
		$post_id = self::factory()->post->create( [ 'post_content' => $content ] );

		$parser = new FormBlockParser();
		$data   = $parser->extract_form_data( 'form-textarea-type', $post_id );

		$this->assertSame( 'textarea', $data['field_configs']['field_1']['type'] );
	}

	/**
	 * Each choice block must resolve to its own type, the way field-textarea
	 * does, rather than falling back to the `type` attribute.
	 */
	public function test_choice_blocks_resolve_their_own_type(): void {
		$content = '<!-- wp:osf/form {"formId":"form-choice-types"} --><!-- wp:osf/form-fields -->'
			. '<!-- wp:osf/field-select {"fieldId":1} /-->'
			. '<!-- wp:osf/field-radio {"fieldId":2} /-->'
			. '<!-- wp:osf/field-checkbox {"fieldId":3} /-->'
			. '<!-- wp:osf/field-consent {"fieldId":4} /-->'
			. '<!-- /wp:osf/form-fields --><!-- /wp:osf/form -->';
		$post_id = self::factory()->post->create( [ 'post_content' => $content ] );

		$parser = new FormBlockParser();
		$data   = $parser->extract_form_data( 'form-choice-types', $post_id );

		$this->assertSame( 'select', $data['field_configs']['field_1']['type'] );
		$this->assertSame( 'radio', $data['field_configs']['field_2']['type'] );
		$this->assertSame( 'checkbox', $data['field_configs']['field_3']['type'] );
		$this->assertSame( 'consent', $data['field_configs']['field_4']['type'] );
	}

	/**
	 * Options are authored as child blocks, so the parser has to fold them into
	 * the field config: sanitization and validation both need the allowlist and
	 * neither can see the block tree.
	 */
	public function test_choice_field_config_carries_its_options(): void {
		$content = '<!-- wp:osf/form {"formId":"form-choice-options"} --><!-- wp:osf/form-fields -->'
			. '<!-- wp:osf/field-select {"fieldId":1,"name":"country"} -->'
			. '<!-- wp:osf/field-option {"label":"Portugal","value":"pt"} /-->'
			. '<!-- wp:osf/field-option {"label":"Spain","value":"es"} /-->'
			. '<!-- /wp:osf/field-select -->'
			. '<!-- /wp:osf/form-fields --><!-- /wp:osf/form -->';
		$post_id = self::factory()->post->create( [ 'post_content' => $content ] );

		$parser = new FormBlockParser();
		$data   = $parser->extract_form_data( 'form-choice-options', $post_id );
		$config = $data['field_configs']['country'];

		$this->assertSame(
			[
				[
					'label' => 'Portugal',
					'value' => 'pt',
				],
				[
					'label' => 'Spain',
					'value' => 'es',
				],
			],
			$config['options']
		);
		$this->assertSame( [ 'pt', 'es' ], $config['validation_rules']['options'] );
	}

	/**
	 * An option block is not a field: it must never gain a field config of its
	 * own, however deeply the parser recurses.
	 */
	public function test_option_blocks_are_not_fields(): void {
		$content = '<!-- wp:osf/form {"formId":"form-option-not-field"} --><!-- wp:osf/form-fields -->'
			. '<!-- wp:osf/field-radio {"fieldId":1,"name":"size"} -->'
			. '<!-- wp:osf/field-option {"label":"Small","value":"s"} /-->'
			. '<!-- /wp:osf/field-radio -->'
			. '<!-- /wp:osf/form-fields --><!-- /wp:osf/form -->';
		$post_id = self::factory()->post->create( [ 'post_content' => $content ] );

		$parser = new FormBlockParser();
		$data   = $parser->extract_form_data( 'form-option-not-field', $post_id );

		$this->assertSame( [ 'size' ], array_keys( $data['field_configs'] ) );
	}

	/**
	 * A synced pattern whose reusable block post has been emptied must resolve
	 * to no fields rather than erroring.
	 */
	public function test_extract_form_data_handles_empty_synced_pattern(): void {
		$pattern_id = self::factory()->post->create(
			[
				'post_type'    => 'wp_block',
				'post_content' => '',
			]
		);
		$post_id    = self::factory()->post->create(
			[ 'post_content' => sprintf( '<!-- wp:block {"ref":%d} /-->', $pattern_id ) ]
		);

		$parser = new FormBlockParser();
		$data   = $parser->extract_form_data( 'form-a', $post_id );

		$this->assertSame( [], $data['field_configs'] );
	}

	/**
	 * A synced pattern referencing a post that no longer exists must resolve
	 * to no fields rather than erroring.
	 */
	public function test_extract_form_data_handles_missing_synced_pattern(): void {
		$post_id = self::factory()->post->create( [ 'post_content' => '<!-- wp:block {"ref":999999999} /-->' ] );

		$parser = new FormBlockParser();
		$data   = $parser->extract_form_data( 'form-a', $post_id );

		$this->assertSame( [], $data['field_configs'] );
	}

	/**
	 * A parser built with an explicit field factory must use it instead of the shared registry.
	 */
	public function test_constructor_uses_the_provided_field_factory(): void {
		$factory = new FieldFactory();
		$factory->register(
			'example-custom',
			[ 'sanitize' => 'sanitize_title' ]
		);

		$content = '<!-- wp:osf/form {"formId":"form-custom"} --><!-- wp:osf/form-fields --><!-- wp:osf/field-input {"fieldId":9,"type":"example-custom","label":"Custom"} /--><!-- /wp:osf/form-fields --><!-- /wp:osf/form -->';
		$post_id = self::factory()->post->create( [ 'post_content' => $content ] );

		$parser = new FormBlockParser( $factory );
		$data   = $parser->extract_form_data( 'form-custom', $post_id );

		$this->assertArrayHasKey( 'field_9', $data['field_configs'] );
		$this->assertSame( 'example-custom', $data['field_configs']['field_9']['type'] );
	}
}
