<?php
/**
 * Email notification template.
 *
 * @var string $form_id       The form ID.
 * @var array  $form_data     Sanitized submitted data keyed by field name.
 * @var array  $field_configs Field configurations keyed by field name.
 */

defined( 'ABSPATH' ) || exit;
?>
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 600px; margin: 0 auto;">
	<h2><?php echo esc_html__( 'New Form Submission', 'outstand-forms' ); ?></h2>
	<table style="width: 100%; border-collapse: collapse;" role="presentation">
		<?php foreach ( $field_configs as $field_name => $config ) : ?>
			<?php
			$label = ! empty( $config['label'] ) ? $config['label'] : $field_name;
			$value = $form_data[ $field_name ] ?? '';
			?>
			<tr>
				<td style="padding: 8px 12px; border-bottom: 1px solid #e0e0e0; font-weight: 600; vertical-align: top; width: 30%;">
					<?php echo esc_html( $label ); ?>
				</td>
				<td style="padding: 8px 12px; border-bottom: 1px solid #e0e0e0;">
					<?php echo nl2br( esc_html( $value ) ); ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
</div>
