<?php
/**
 * Field: Consent
 *
 * @var array     $attributes Block attributes.
 * @var string    $content    Block default content.
 * @var \WP_Block $block      Block instance.
 */

namespace Outstand\WP\Forms;

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the components.
echo FieldRenderer::render( $attributes, $block, 'consent' );
