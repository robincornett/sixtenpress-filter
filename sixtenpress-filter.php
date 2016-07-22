<?php
/**
 * Filter handler for Six/Ten Press
 *
 * @package   SixTenPressFilter
 * @author    Robin Cornett <hello@robincornett.com>
 * @license   GPL-2.0+
 * @link      http://robincornett.com
 * @copyright 2016 Robin Cornett Creative, LLC
 *
 * Plugin Name:       Six/Ten Press Simple Filter
 * Plugin URI:        http://robincornett.com
 * Description:       Six/Ten Press Simple Filter makes allows for very simple filtering of a post type archive.
 * Author:            Robin Cornett
 * Author URI:        https://robincornett.com
 * Text Domain:       sixtenpress-filter
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Version:           0.1.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'SIXTENPRESSFILTER_BASENAME' ) ) {
	define( 'SIXTENPRESSFILTER_BASENAME', plugin_basename( __FILE__ ) );
}

// Include classes
function sixtenpressfilter_require() {
	$files = array(
		'class-sixtenpressfilter',
		'class-sixtenpressfilter-help',
		'class-sixtenpressfilter-output',
	);

	foreach ( $files as $file ) {
		require plugin_dir_path( __FILE__ ) . 'includes/' . $file . '.php';
	}
}
sixtenpressfilter_require();

// Instantiate dependent classes
$sixtenpressfilter_output = new SixTenPressFilterOutput;

// Instantiate main class and pass in dependencies
$sixtenpressfilter = new SixTenPressFilter(
	$sixtenpressfilter_output
);

// Run the plugin
$sixtenpressfilter->run();

/**
 * Helper function to retrieve the plugin setting, with defaults.
 * @return mixed|void
 */
function sixtenpressfilter_get_settings() {
	return apply_filters( 'sixtenpressfilter_get_plugin_setting', false );
}
