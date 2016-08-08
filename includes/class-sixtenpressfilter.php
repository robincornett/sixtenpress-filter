<?php
/**
 * Main plugin class.
 * @package   SixTenPressFilter
 * @author    Robin Cornett <hello@robincornett.com>
 * @license   GPL-2.0+
 * @link      http://robincornett.com
 * @copyright 2016 Robin Cornett Creative, LLC
 */
class SixTenPressFilter {

	/**
	 * The output class.
	 * @var $output SixTenPressFilterOutput
	 */
	protected $output;

	/**
	 * SixTenPressFilter constructor.
	 *
	 * @param $output
	 */
	public function __construct( $output ) {
		$this->output = $output;
	}

	/**
	 * Check for post type support, etc.
	 */
	public function run() {

		add_action( 'wp_loaded', array( $this, 'load_settings_page' ) );
		add_action( 'pre_get_posts', array( $this->output, 'add_post_type_support' ), 999 );
		add_action( 'template_redirect', array( $this->output, 'maybe_do_filter' ) );
	}

	/**
	 * Load the plugin settings page/classes.
	 * Checks for class before loading.
	 */
	public function load_settings_page() {
		if ( ! class_exists( 'SixTenPressSettings' ) ) {
			require plugin_dir_path( __FILE__ ) . '/common/class-sixtenpress-settings.php';
		}
		require plugin_dir_path( __FILE__ ) . 'class-sixtenpressfilter-settings-page.php';
		$settings = new SixTenPressFilterSettingsPage();
		add_action( 'admin_menu', array( $settings, 'do_submenu_page' ) );
		add_filter( 'sixtenpressfilter_get_plugin_setting', array( $settings, 'get_setting' ) );
	}
}
