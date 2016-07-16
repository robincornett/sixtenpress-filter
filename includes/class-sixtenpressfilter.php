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
	 * The settings class.
	 * @var $settings SixTenPressFilterSettings
	 */
	protected $settings;

	/**
	 * SixTenPressFilter constructor.
	 *
	 * @param $settings
	 */
	public function __construct( $output, $settings ) {
		$this->output   = $output;
		$this->settings = $settings;
	}

	/**
	 * Check for post type support, etc.
	 */
	public function run() {
		add_action( 'admin_menu', array( $this->settings, 'do_submenu_page' ) );
		add_filter( 'sixtenpressfilter_get_plugin_setting', array( $this->settings, 'get_setting' ) );
		add_action( 'pre_get_posts', array( $this->output, 'add_post_type_support' ), 999 );
		add_action( 'template_redirect', array( $this->output, 'maybe_do_filter' ) );
	}
}