<?php
/**
 *
 * Class to handle filter output.
 *
 * @package   SixTenPressFilter
 * @author    Robin Cornett <hello@robincornett.com>
 * @license   GPL-2.0+
 * @link      http://robincornett.com
 * @copyright 2016 Robin Cornett Creative, LLC
 */

class SixTenPressFilterOutput {

	/**
	 * @var array The plugin setting.
	 */
	protected $setting;

	public function maybe_do_filter() {
		if ( is_singular() || is_admin() ) {
			return;
		}
		if ( ! $this->post_type_supports() ) {
			return;
		}
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_filter' ) );
		add_action( 'wp_head', array( $this, 'inline_style' ) );
		$filter = $this->pick_filter();
		add_action( 'genesis_before_loop', array( $this, $filter ), 20 );
	}

	/**
	 * Check whether the current post type supports filter.
	 * Can be modified via filter (eg on taxonomies).
	 * @return bool
	 */
	protected function post_type_supports( $post_type = '' ) {
		$support   = false;
		$post_type = empty( $post_type ) ? $this->get_current_post_type() : $post_type;
		if ( is_array( $post_type ) ) {
			foreach( $post_type as $type ) {
				$support = post_type_supports( $type, 'sixtenpress-filter' );
				if ( ! $support ) {
					break;
				}
			}
		} else {
			$support = post_type_supports( $post_type, 'sixtenpress-filter' );
		}
		return (bool) apply_filters( 'sixtenpress_filter_support', $support );
	}

	/**
	 * Function to enqueue filter scripts and do the filter things.
	 */
	public function enqueue_filter() {
		$dependent_scripts = array();
		wp_register_script( 'infinite-scroll', plugin_dir_url( __FILE__ ) . 'js/jquery.infinitescroll.min.js', array(), '2.1.0', true );
		if ( $this->setting['infinite'] ) {
			$dependent_scripts[] = 'infinite-scroll';
		}
		wp_enqueue_script( 'sixtenpress-filter', plugin_dir_url( __FILE__ ) . 'js/filter.js', $dependent_scripts, '1.0.0', true );

		add_action( 'wp_print_scripts', array( $this, 'localize' ) );
	}

	/**
	 * Localize the script for filter output.
	 */
	public function localize() {
		$options = $this->get_filter_options();
		wp_localize_script( 'sixtenpress-filter', 'SixTenPressFilter', $options );
	}

	/**
	 * Get the filter options for localization, inline scripts
	 * @return mixed|void
	 */
	protected function get_filter_options() {
		$options = apply_filters( 'sixtenpress_filter_options', array(
			'selector' => 'main .entry',
		) );

		$array = array(
			'infinite' => (bool) $this->setting['infinite'],
			'loading'  => plugin_dir_url( __FILE__ ) . 'images/ajax-loading.gif',
			'finished' => __( 'No more items to load.', 'sixtenpress-isotope' ),
		);

		return array_merge( $options, $array );
	}

	/**
	 * Check the current post type.
	 * @return false|mixed|string
	 */
	protected function get_current_post_type() {
		$post_type_name = get_post_type();
		if ( ! $post_type_name ) {
			$post_type_name = get_query_var( 'post_type' );
		}
		$post_type_name = is_home() ? 'post' : $post_type_name;
		return $post_type_name;
	}

	/**
	 * Add filter support to the relevant post types.
	 *
	 * @param $query WP_Query
	 */
	public function add_post_type_support( $query ) {
		$this->setting = sixtenpressfilter_get_settings();
		if ( ! $query->is_main_query() || $query->is_search() || $query->is_feed() || is_admin() ) {
			return;
		}
		$post_type = $query->get( 'post_type' );
		if ( empty( $post_type ) ) {
			$post_type = 'post';
		}
		$taxonomies = $this->updated_filters();
		if ( empty( $taxonomies ) ) {
			return;
		}
		add_post_type_support( $post_type, 'sixtenpress-filter' );
		$this->posts_per_page( $query, $post_type );
	}

	/**
	 * @param $query WP_Query
	 */
	public function posts_per_page( $query, $post_type ) {
		// add a filter to optionally override this query
		if ( apply_filters( 'sixtenpress_filter_override_query', false, $post_type ) ) {
			return;
		}
		$query->set( 'posts_per_page', $this->setting['posts_per_page'] );
	}

	public function modify_genesis_options( $args ) {
		if ( 'default' !== $this->setting['image_size'] ) {
			$args['content_archive_thumbnail'] = 1;
			$args['image_size']                = $this->setting['image_size'];
		}
		if ( 'default' !== $this->setting['alignment'] ) {
			$args['image_alignment'] = $this->setting['alignment'];
		}

		return $args;
	}

	/**
	 * Add the inline stylesheet.
	 */
	public function inline_style() {
		if ( ! $this->setting['style'] || apply_filters( 'sixtenpress_filter_remove_inline_style', false ) ) {
			return;
		}
		$options = $this->get_filter_options();
		$width   = '100%';
		$css     = sprintf( '.main-filter { width: %s; }
			.main-filter ul { text-align: center; }
			.main-filter li { display: inline-block; margin: 1px; }',
			$width
		);

		$css = apply_filters( 'sixtenpressfilter_inline_style', $css, $this->setting, $options );
		// Minify a bit
		$css = str_replace( "\t", '', $css );
		$css = str_replace( array( "\n", "\r" ), ' ', $css );

		// Echo the CSS
		echo '<style type="text/css" media="screen">' . strip_tags( $css ) . '</style>';
	}

	/**
	 * Build the array/string of taxonomies to use as a filter.
	 *
	 * @param array $tax_filters
	 *
	 * @return array|string
	 */
	public function build_filter_array( $tax_filters = array() ) {
		$post_type  = $this->get_current_post_type();
		$taxonomies = get_object_taxonomies( $post_type, 'names' );
		$taxonomies = 'post' === $post_type ? array( 'category' ) : $taxonomies;
		if ( $taxonomies ) {
			foreach ( $taxonomies as $taxonomy ) {
				if ( isset( $this->setting[ $post_type ] ) && $this->setting[ $post_type ][ $taxonomy ] ) {
					$tax_filters[] = $taxonomy;
				};
			}
		}
		return apply_filters( 'sixtenpressfilter_terms', $tax_filters, $post_type, $taxonomies, $this->setting );
	}

	/**
	 * Count the taxonomies for the filters--if one, return a string instead of an array.
	 * @return array|string
	 */
	protected function updated_filters() {
		$tax_filters = $this->build_filter_array();
		$count       = count( $tax_filters );
		if ( $count === 1 ) {
			$tax_filters = implode( $tax_filters );
		}
		return $tax_filters;
	}

	/**
	 * Determine which filter to use.
	 */
	public function pick_filter() {
		if ( ! is_post_type_archive() && ! is_home() ) {
			return '';
		}
		$filters = $this->updated_filters();
		if ( empty( $filters ) ) {
			return '';
		}
		$action = 'do_filter_select';
		if ( is_string( $filters ) ) {
			$action = 'do_filter_buttons';
		}
		return $action;
	}

	/**
	 * Build the filter(s) for the filter.
	 * @param $select_options array containing terms, name, singular name, and optional class for the select.
	 * @param string $filter_name string What to name the filter heading (optional)
	 */
	public function do_filter_select() {
		$select_options = $this->updated_filters();
		if ( ! $select_options ) {
			return;
		}
		$count        = count( $select_options );
		$column_class = $this->select_class( $count );
		$output       = '<div class="main-filter">';
		$object       = get_post_type_object( $this->get_current_post_type() );
		$filter_text  = sprintf( __( 'Filter %s By:', 'sixtenpress-filter' ), esc_attr( $object->labels->name ) );
		$output      .= sprintf( '<h4>%s</h4>', esc_html( $filter_text ) );
		$i            = 0;
		foreach ( $select_options as $option ) {
			$class = $column_class;
			if ( 0 === $i ) {
				$class .= ' first';
			}
			$output .= $this->build_taxonomy_select( $option, $class );
			$i++;
		}
		$output .= '<br clear="all" />';
		$output .= '</div>';
		echo $output;
	}

	/**
	 * Build a select/dropdown for filter filtering.
	 * @param $option array
	 */
	protected function build_taxonomy_select( $option, $class ) {
		$output = sprintf( '<select name="%1$s" id="%1$s-filters" class="%2$s" data-filter-group="%1$s">',
			esc_attr( strtolower( $option ) ),
			esc_attr( $class )
		);
		$tax_object = get_taxonomy( $option );
		$label      = $tax_object->labels->name;
		$all_things = sprintf( __( 'All %s', 'sixtenpress-filter' ), $label );
		$output .= sprintf( '<option value="all" data-filter-value="">%s</option>',
			esc_html( $all_things )
		);
		$terms = get_terms( $option );
		$items = '';
		foreach ( $terms as $term ) {
			$class  = sprintf( '%s-%s', esc_attr( $option ), esc_attr( $term->slug ) );
			$items .= sprintf( '<option value="%1$s" data-filter-value=".%1$s">%2$s</option>',
				esc_attr( $class ),
				esc_attr( $term->name )
			);
		}
		$output .= apply_filters( "sixtenpressfilter_{$option}_items", $items, $option, $class, $terms );
		$output .= '</select>';
		return $output;
	}

	/**
	 * @param $count
	 * @param string $class
	 *
	 * @return string
	 */
	protected function select_class( $count ) {
		$class = 'filter';
		if ( 0 === $count % 3 ) {
			$class .= ' one-third';
		} elseif ( 0 === $count % 4 ) {
			$class .= ' one-fourth';
		} elseif ( 0 === $count % 2 ) {
			$class .= ' one-half';
		}

		return apply_filters( 'sixtenpressfilter_select_class', $class );
	}

	/**
	 * @param $taxonomy string taxonomy for which to generate buttons
	 *
	 * @return string
	 * example:
	 * function soulcarepeople_buttons() {
	 *     sixtenpress_do_filter_buttons( 'group' );
	 * }
	 */
	public function do_filter_buttons() {
		$taxonomy = $this->updated_filters();
		if ( ! $taxonomy ) {
			return;
		}

		$terms = get_terms( $taxonomy );
		if ( ! $terms ) {
			return;
		}
		$output  = '<div class="main-filter">';
		$output .= sprintf( '<h4>%s</h4>', __( 'Filter By: ', 'sixtenpress-filter' ) );
		$output .= sprintf( '<ul id="%s" class="filter">', esc_html( $taxonomy ) );
		$output .= sprintf( '<li><button class="active" data-filter="*">%s</button></li>', __( 'All', 'sixtenpress-filter' ) );
		$items = '';
		foreach ( $terms as $term ) {
			$items .= sprintf( '<li><button data-filter=".%s-%s">%s</button></li>',
				esc_html( $taxonomy ),
				esc_html( $term->slug ),
				esc_html( $term->name )
			);
		}
		$output .= apply_filters( "sixtenpressfilter_{$taxonomy}_items", $items, $taxonomy, $terms );
		$output .= '</ul>';
		$output .= '</div>';

		echo $output;
	}
}
