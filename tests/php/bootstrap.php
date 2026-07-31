<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Outstand\WP\Forms
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php. Ensure WP_TESTS_DIR is set or run: npm run test:setup\n"; // phpcs:ignore
	exit( 1 );
}

// Load Composer autoloader (plugin classes live under PSR-4).
$plugin_dir = dirname( __DIR__, 2 );
if ( file_exists( $plugin_dir . '/vendor/autoload.php' ) ) {
	require_once $plugin_dir . '/vendor/autoload.php';
}

// Load WordPress test suite functions.
require_once $_tests_dir . '/includes/functions.php';

// Load the plugin (defines constants and boots the singleton on plugins_loaded).
tests_add_filter(
	'muplugins_loaded',
	function () use ( $plugin_dir ) {
		require $plugin_dir . '/plugin.php';
	}
);

// Bootstrap WordPress test suite.
require $_tests_dir . '/includes/bootstrap.php';
