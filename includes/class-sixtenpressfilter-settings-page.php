<?php

/**
 * @copyright 2016 Robin Cornett
 */
class SixTenPressFilterSettingsPage extends SixTenPressSettings {

	/**
	 * Option registered by plugin.
	 * @var array $setting
	 */
	protected $setting;

	/**
	 * Public registered post types.
	 * @var array $post_types
	 */
	protected $post_types;

	/**
	 * Slug for settings page.
	 * @var string $page
	 */
	protected $page = 'sixtenpress';

	/**
	 * Settings fields registered by plugin.
	 * @var array
	 */
	protected $fields;

	/**
	 * Tab/page for settings.
	 * @var string $tab
	 */
	protected $tab = 'sixtenpressfilter';

	/**
	 * Maybe add the submenu page under Settings.
	 */
	public function do_submenu_page() {

		$this->setting = $this->get_setting();
		$sections      = $this->register_sections();
		$this->fields  = $this->register_fields();
		if ( ! class_exists( 'SixTenPress' ) ) {
			$this->page = $this->tab;
			add_options_page(
				__( '6/10 Press Filter Settings', 'sixtenpress-filter' ),
				__( '6/10 Press Filter', 'sixtenpress-filter' ),
				'manage_options',
				$this->page,
				array( $this, 'do_simple_settings_form' )
			);
		}

		add_filter( 'sixtenpress_settings_tabs', array( $this, 'add_tab' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		$help = new SixTenPressFilterHelp();
		if ( class_exists( 'SixTenPress' ) ) {
			add_filter( 'sixtenpress_help_tabs', array( $help, 'tabs' ), 10, 2 );
		} else {
			add_action( "load-settings_page_{$this->page}", array( $help, 'help' ) );
		}

		$this->add_sections( $sections );
		$this->add_fields( $this->fields, $sections );
	}

	/**
	 * Add filter settings to 6/10 Press as a new tab, rather than creating a unique page.
	 * @param $tabs
	 *
	 * @return array
	 */
	public function add_tab( $tabs ) {
		$tabs[] = array( 'id' => 'filter', 'tab' => __( 'Simple Filter', 'sixtenpress-filter' ) );

		return $tabs;
	}

	/**
	 * Add new fields to wp-admin/options-general.php?page=sixtenpressfilter
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		register_setting( 'sixtenpressfilter', 'sixtenpressfilter', array( $this, 'do_validation_things' ) );
	}

	/**
	 * @return array $setting for plugin, or defaults.
	 */
	public function get_setting() {

		$defaults = array(
			'posts_per_page' => (int) get_option( 'posts_per_page', 10 ),
			'style'          => 1,
			'infinite'       => 0,
		);

		$setting = get_option( 'sixtenpressfilter', $defaults );

		return wp_parse_args( $setting, $defaults );
	}

	/**
	 * Register sections for settings page.
	 *
	 * @since 3.0.0
	 */
	protected function register_sections() {

		$sections = array(
			'general' => array(
				'id'    => 'general',
				'tab'   => 'filter',
				'title' => __( 'General Settings', 'sixtenpress-filter' ),
			),
		);

		$this->post_types = $this->post_types();
		if ( $this->post_types ) {

			$sections['cpt'] = array(
				'id'    => 'cpt',
				'tab'   => 'filter',
				'title' => __( 'Filter Settings for Content Types', 'sixtenpress-filter' ),
			);
		}

		return $sections;
	}

	/**
	 * Define the array of post types for the plugin to show/use.
	 * @return array
	 */
	protected function post_types() {
		$args         = array(
			'public'      => true,
			'_builtin'    => false,
			'has_archive' => true,
		);
		$output       = 'names';
		$post_types   = get_post_types( $args, $output );
		$post_types[] = 'post';
		foreach ( $post_types as $post_type ) {
			$taxonomies = $this->get_taxonomies( $post_type );
			if ( ! $taxonomies ) {
				unset( $post_types[ $post_type ] );
			}
		}

		return $post_types;
	}

	/**
	 * Register settings fields
	 *
	 * @param  settings array $sections
	 *
	 * @return array $fields settings fields
	 *
	 * @since 1.0.0
	 */
	protected function register_fields() {

		$fields = array(
			array(
				'id'       => 'posts_per_page',
				'title'    => __( 'Number of Posts to Show on Filter Archives', 'sixtenpress-filter' ),
				'callback' => 'do_number',
				'section'  => 'general',
				'args'     => array(
					'setting' => 'posts_per_page',
					'min'     => 1,
					'max'     => 200,
					'label'   => __( 'Posts per Page', 'sixtenpress-filter' ),
				),
			),
			array(
				'id'       => 'infinite',
				'title'    => __( 'Infinite Scroll', 'sixtenpress-filter' ),
				'callback' => 'do_checkbox',
				'section'  => 'general',
				'args'     => array(
					'setting' => 'infinite',
					'label'   => __( 'Enable infinite scroll?', 'sixtenpress-filter' ),
				),
			),
			array(
				'id'       => 'style',
				'title'    => __( 'Plugin Stylesheet', 'sixtenpress-filter' ),
				'callback' => 'do_checkbox',
				'section'  => 'general',
				'args'     => array(
					'setting' => 'style',
					'label'   => __( 'Use the plugin styles?', 'sixtenpress-filter' ),
				),
			),
		);
		if ( $this->post_types ) {
			foreach ( $this->post_types as $post_type ) {
				$object   = get_post_type_object( $post_type );
				$label    = $object->labels->name;
				$fields[] = array(
					'id'       => '[post_types]' . esc_attr( $post_type ),
					'title'    => esc_attr( $label ),
					'callback' => 'set_post_type_options',
					'section'  => 'cpt',
					'args'     => array( 'post_type' => $post_type ),
				);
			}
		}

		return $fields;
	}

	/**
	 * Callback for general plugin settings section.
	 */
	public function general_section_description() {
		$description = __( 'You can set the default filter settings here.', 'sixtenpress-filter' );
		printf( '<p>%s</p>', wp_kses_post( $description ) );
	}

	/**
	 * Callback for the content types section description.
	 */
	public function cpt_section_description() {
		$description = __( 'Set the filter settings for each content type.', 'sixtenpress-filter' );
		printf( '<p>%s</p>', wp_kses_post( $description ) );
	}

	/**
	 * Set the field for each post type.
	 * @param $args
	 */
	public function set_post_type_options( $args ) {
		$post_type  = $args['post_type'];
		$taxonomies = $this->get_taxonomies( $post_type );
		if ( ! $taxonomies ) {
			return;
		}
		foreach ( $taxonomies as $taxonomy ) {
			$tax_object = get_taxonomy( $taxonomy );
			$tax_args   = array(
				'setting' => $taxonomy,
				'label'   => sprintf( __( 'Add a filter for %s', 'sixtenpress-filter' ), $tax_object->labels->name ),
				'key'     => $post_type,
			);
			$this->do_checkbox( $tax_args );
			echo '<br />';
		}
	}

	/**
	 * Get the taxonomies registered to a post type.
	 * @param $post_type
	 *
	 * @return array
	 */
	protected function get_taxonomies( $post_type ) {
		$taxonomies = get_object_taxonomies( $post_type, 'names' );
		return 'post' === $post_type ? array( 'category' ) : $taxonomies;
	}

	public function do_validation_things( $new_value ) {

		$action = $this->page . '_save-settings';
		$nonce  = $this->page . '_nonce';
		// If the user doesn't have permission to save, then display an error message
		if ( ! $this->user_can_save( $action, $nonce ) ) {
			wp_die( esc_attr__( 'Something unexpected happened. Please try again.', 'sixtenpress-filter' ) );
		}

		check_admin_referer( "{$this->page}_save-settings", "{$this->page}_nonce" );
		$new_value = array_merge( $this->setting, $new_value );

		foreach ( $this->fields as $field ) {
			switch ( $field['callback'] ) {
				case 'do_checkbox':
					$new_value[ $field['id'] ] = $this->one_zero( $new_value[ $field['id'] ] );
					break;

				case 'do_select':
					$new_value[ $field['id'] ] = esc_attr( $new_value[ $field['id'] ] );
					break;

				case 'do_number':
					$new_value[ $field['id'] ] = (int) $new_value[ $field['id'] ];
					break;

				case 'do_checkbox_array':
					foreach ( $field['args']['choices'] as $option ) {
						$new_value[ $field['id'] ][ $option['choice'] ] = $this->one_zero( $new_value[ $field['id'] ][ $option['choice'] ] );
					}
					break;
			}
		}
		foreach ( $this->post_types as $post_type ) {
			$taxonomies = $this->get_taxonomies( $post_type );
			foreach ( $taxonomies as $taxonomy ) {
				$new_value[ $post_type ][ $taxonomy ] = $this->one_zero( $new_value[ $post_type ][ $taxonomy ] );
			}
		}

		return $new_value;
	}
}
