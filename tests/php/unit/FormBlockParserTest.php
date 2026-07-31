<?php

namespace Outstand\WP\Forms\Tests\Unit;

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
}
