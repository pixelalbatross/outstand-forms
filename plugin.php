<?php // phpcs:ignore Generic.Commenting.DocComment.MissingShort
/**
 * @wordpress-plugin
 * Plugin Name:       Outstand Forms
 * Description:       Build flexible, modern forms using the WordPress block editor.
 * Plugin URI:        https://outstand.site/?utm_source=wp-plugins&utm_medium=outstand-forms&utm_campaign=plugin-uri
 * Requires at least: 6.7
 * Requires PHP:      8.2
 * Version:           1.0.0
 * Author:            Outstand
 * Author URI:        https://outstand.site/?utm_source=wp-plugins&utm_medium=outstand-forms&utm_campaign=author-uri
 * License:           GPL-3.0-or-later
 * License URI:       https://spdx.org/licenses/GPL-3.0-or-later.html
 * Update URI:        https://outstand.site/
 * GitHub Plugin URI: https://github.com/pixelalbatross/outstand-forms
 * Text Domain:       outstand-forms
 * Domain Path:       /languages
 */

namespace Outstand\WP\Forms;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'OUTSTAND_FORMS_VERSION', '1.0.0' );
define( 'OUTSTAND_FORMS_BASENAME', plugin_basename( __FILE__ ) );
define( 'OUTSTAND_FORMS_URL', plugin_dir_url( __FILE__ ) );
define( 'OUTSTAND_FORMS_PATH', plugin_dir_path( __FILE__ ) );
define( 'OUTSTAND_FORMS_DIST_URL', OUTSTAND_FORMS_URL . 'build/' );
define( 'OUTSTAND_FORMS_DIST_PATH', OUTSTAND_FORMS_PATH . 'build/' );

if ( file_exists( OUTSTAND_FORMS_PATH . 'vendor/autoload.php' ) ) {
	require_once OUTSTAND_FORMS_PATH . 'vendor/autoload.php';
}

if ( class_exists( PucFactory::class ) ) {
	PucFactory::buildUpdateChecker(
		'https://github.com/pixelalbatross/outstand-forms/',
		__FILE__,
		'outstand-forms'
	)->setBranch( 'main' );
}

add_action(
	'plugins_loaded',
	function () {
		Plugin::get_instance()->enable();
	}
);
