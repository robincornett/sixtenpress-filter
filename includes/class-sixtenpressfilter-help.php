<?php

/**
 * Class for adding help tab to the isotope settings.
 * @package   SixTenPressFilter
 * @author    Robin Cornett <hello@robincornett.com>
 * @license   GPL-2.0+
 * @link      http://robincornett.com
 * @copyright 2016 Robin Cornett Creative, LLC
 */
class SixTenPressFilterHelp {

	/**
	 * Help tab for settings screen
	 *
	 * @since 1.0.0
	 */
	public function help() {

		$screen    = get_current_screen();
		$help_tabs = $this->define_tabs();
		if ( ! $help_tabs ) {
			return;
		}
		foreach ( $help_tabs as $tab ) {
			$screen->add_help_tab( $tab );
		}
	}

	public function tabs( $tabs, $active_tab ) {
		if ( 'filter' === $active_tab ) {
			$tabs = $this->define_tabs();
		}
		return $tabs;
	}

	protected function define_tabs() {
		return array(
			array(
				'id'      => 'sixtenpressfilter_general-help',
				'title'   => __( 'General Settings', 'sixtenpress-filter' ),
				'content' => $this->general(),
			),
			array(
				'id'      => 'sixtenpressfilter_cpt-help',
				'title'   => __( 'Filter Settings for Content Types', 'sixtenpress-filter' ),
				'content' => $this->cpt(),
			),
		);
	}

	protected function general() {
		$help = '<h3>' . __( 'Number of Posts to Show on Filter Archives', 'sixtenpress-filter' ) . '</h3>';
		$help .= '<p>' . __( 'Change the number of items which show on content archives, to show more or less items than your regular archives.', 'sixtenpress-filter' ) . '</p>';

		$help .= '<h3>' . __( 'Plugin Stylesheet', 'sixtenpress-filter' ) . '</h3>';
		$help .= '<p>' . __( 'The plugin adds a wee bit of styling to handle the filter layout, but if you want to do it yourself, disable the plugin style and enjoy!', 'sixtenpress-filter' ) . '</p>';

		return $help;
	}

	protected function cpt() {
		return '<p>' . __( 'Each content type on your site will be handled uniquely. Enable Filter, set the gutter width, and enable filters as you like.', 'sixtenpress-filter' ) . '</p>';
	}
}
