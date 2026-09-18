<?php
/**
 * Plugin Name: BlueWorx Labs | Hiraste Travel
 * Description: Hiraste Travel enhancements: Elementor property search widgets, filtered results, related items, taxonomy ordering, and property favourites.
 * Version: 0.5.0
 * Author: BlueWorx Labs
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: blueworx-hiraste-enhancements
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SL_ESF_VERSION', '0.5.0' );
define( 'SL_ESF_PLUGIN_FILE', __FILE__ );
define( 'SL_ESF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SL_ESF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

final class SL_ESF_Plugin {

	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'init' ] );
	}

	public function init() {
		require_once SL_ESF_PLUGIN_DIR . 'includes/class-sl-esf-favourites.php';
		require_once SL_ESF_PLUGIN_DIR . 'includes/class-sl-esf-property-count.php';
		require_once SL_ESF_PLUGIN_DIR . 'includes/class-sl-esf-unit-popup.php';
		require_once SL_ESF_PLUGIN_DIR . 'includes/class-sl-esf-read-more.php';
		new SL_ESF_Favourites();
		new SL_ESF_Property_Count();
		new SL_ESF_Unit_Popup();
		new SL_ESF_Read_More();

		add_action( 'elementor/widgets/register', [ $this, 'register_widget' ] );
		add_action( 'elementor/frontend/after_register_scripts', [ $this, 'register_assets' ] );
		add_action( 'elementor/frontend/widget/before_render', [ $this, 'start_loop_grid_term_filtering' ] );
		add_action( 'elementor/frontend/widget/after_render', [ $this, 'stop_loop_grid_term_filtering' ] );
		add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
		add_action( 'admin_init', [ $this, 'ensure_property_admin_caps' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_term_order_admin_assets' ] );
		add_action( 'admin_notices', [ $this, 'render_term_order_notice' ] );
		add_action( 'wp_ajax_sl_esf_save_term_order', [ $this, 'save_term_order' ] );
		add_filter( 'user_has_cap', [ $this, 'grant_property_caps_to_administrators' ], 10, 4 );
		add_filter( 'terms_clauses', [ $this, 'order_supported_taxonomy_terms' ], 10, 3 );
	}

	public function ensure_property_admin_caps() {
		$role = get_role( 'administrator' );
		if ( ! $role ) {
			return;
		}

		foreach ( $this->get_property_admin_caps() as $cap ) {
			if ( empty( $role->capabilities[ $cap ] ) ) {
				$role->add_cap( $cap );
			}
		}
	}

	public function grant_property_caps_to_administrators( $allcaps, $caps, $args, $user ) {
		if ( ! is_admin() || ! $user instanceof WP_User || ! in_array( 'administrator', (array) $user->roles, true ) ) {
			return $allcaps;
		}

		foreach ( $this->get_property_admin_caps() as $cap ) {
			$allcaps[ $cap ] = true;
		}

		return $allcaps;
	}

	private function get_property_admin_caps() {
		$cap_names = [
			'edit_posts',
			'edit_others_posts',
			'publish_posts',
			'read_private_posts',
			'delete_posts',
			'delete_private_posts',
			'delete_published_posts',
			'delete_others_posts',
			'edit_private_posts',
			'edit_published_posts',
			'create_posts',
		];

		$caps = [ 'read' ];
		$post_type = get_post_type_object( 'property' );

		if ( $post_type && ! empty( $post_type->cap ) ) {
			foreach ( $cap_names as $cap_name ) {
				if ( ! empty( $post_type->cap->{$cap_name} ) ) {
					$caps[] = $post_type->cap->{$cap_name};
				}
			}
		}

		$caps = array_merge( $caps, [
			'edit_properties',
			'edit_others_properties',
			'publish_properties',
			'read_private_properties',
			'delete_properties',
			'delete_private_properties',
			'delete_published_properties',
			'delete_others_properties',
			'edit_private_properties',
			'edit_published_properties',
			'create_properties',
		] );

		return array_values( array_unique( array_filter( array_map( 'sanitize_key', $caps ) ) ) );
	}

	public function enqueue_term_order_admin_assets( $hook_suffix ) {
		if ( $hook_suffix !== 'edit-tags.php' ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || empty( $screen->taxonomy ) || ! $this->is_orderable_taxonomy( $screen->taxonomy ) ) {
			return;
		}

		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_register_script( 'sl-esf-admin-term-order', '', [ 'jquery', 'jquery-ui-sortable' ], SL_ESF_VERSION, true );
		wp_enqueue_script( 'sl-esf-admin-term-order' );
		wp_add_inline_script(
			'sl-esf-admin-term-order',
			sprintf(
				'jQuery(function($){var table=$(".wp-list-table.tags tbody");if(!table.length){return;}table.sortable({items:"tr[id^=\'tag-\']",axis:"y",cursor:"move",helper:function(e,row){row.children().each(function(){$(this).width($(this).width());});return row;},update:function(){var ids=table.children("tr[id^=\'tag-\']").map(function(){return this.id.replace("tag-","");}).get();$.post(ajaxurl,{action:"sl_esf_save_term_order",taxonomy:%s,term_ids:ids,_ajax_nonce:%s});}});table.find("tr[id^=\'tag-\']").css("cursor","move");});',
				wp_json_encode( $screen->taxonomy ),
				wp_json_encode( wp_create_nonce( 'sl_esf_save_term_order_' . $screen->taxonomy ) )
			)
		);
		wp_add_inline_style( 'common', '.wp-list-table.tags tbody tr[id^="tag-"]{cursor:move}.wp-list-table.tags tbody tr.ui-sortable-helper{background:#fff;box-shadow:0 2px 10px rgba(0,0,0,.15)}' );
	}

	public function render_term_order_notice() {
		$screen = get_current_screen();
		if ( ! $screen || empty( $screen->taxonomy ) || ! $this->is_orderable_taxonomy( $screen->taxonomy ) ) {
			return;
		}

		echo '<div class="notice notice-info"><p>' . esc_html__( 'Drag rows to reorder these terms. Changes save automatically.', 'blueworx-hiraste-enhancements' ) . '</p></div>';
	}

	public function save_term_order() {
		$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_key( wp_unslash( $_POST['taxonomy'] ) ) : '';
		if ( ! $this->is_orderable_taxonomy( $taxonomy ) ) {
			wp_send_json_error();
		}

		check_ajax_referer( 'sl_esf_save_term_order_' . $taxonomy );

		$taxonomy_object = get_taxonomy( $taxonomy );
		$manage_cap = $taxonomy_object && ! empty( $taxonomy_object->cap->manage_terms ) ? $taxonomy_object->cap->manage_terms : 'manage_categories';
		if ( ! current_user_can( $manage_cap ) ) {
			wp_send_json_error();
		}

		$term_ids = isset( $_POST['term_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['term_ids'] ) ) : [];
		foreach ( $term_ids as $index => $term_id ) {
			if ( $term_id ) {
				update_term_meta( $term_id, 'sl_esf_term_order', $index + 1 );
			}
		}

		wp_send_json_success();
	}

	public function order_supported_taxonomy_terms( $clauses, $taxonomies, $args ) {
		global $wpdb;

		if ( ! empty( $args['orderby'] ) && ! in_array( $args['orderby'], [ 'name', 'term_order' ], true ) ) {
			return $clauses;
		}

		$taxonomies = array_map( 'sanitize_key', (array) $taxonomies );
		if ( empty( array_intersect( $this->get_orderable_taxonomies(), $taxonomies ) ) ) {
			return $clauses;
		}

		if ( strpos( $clauses['join'], 'sl_esf_order_meta' ) === false ) {
			$clauses['join'] .= " LEFT JOIN {$wpdb->termmeta} AS sl_esf_order_meta ON t.term_id = sl_esf_order_meta.term_id AND sl_esf_order_meta.meta_key = 'sl_esf_term_order'";
		}

		$clauses['orderby'] = 'ORDER BY CAST(COALESCE(sl_esf_order_meta.meta_value, 999999) AS UNSIGNED), t.name';
		$clauses['order'] = 'ASC';

		return $clauses;
	}

	private function get_orderable_taxonomies() {
		return [ 'destinations', 'collections', 'amenities' ];
	}

	private function is_orderable_taxonomy( $taxonomy ) {
		return in_array( sanitize_key( $taxonomy ), $this->get_orderable_taxonomies(), true );
	}

	public function start_loop_grid_term_filtering( $widget ) {
		if ( $widget && method_exists( $widget, 'get_name' ) && $widget->get_name() === 'loop-grid' ) {
			$GLOBALS['sl_esf_filter_empty_loop_grid_terms'] = true;
		}
	}

	public function stop_loop_grid_term_filtering( $widget ) {
		if ( $widget && method_exists( $widget, 'get_name' ) && $widget->get_name() === 'loop-grid' ) {
			$GLOBALS['sl_esf_filter_empty_loop_grid_terms'] = false;
		}
	}

	public function register_assets() {
		wp_register_script(
			'sl-esf-frontend',
			SL_ESF_PLUGIN_URL . 'assets/sl-esf.js',
			[],
			SL_ESF_VERSION,
			true
		);
		wp_register_style(
			'sl-esf-frontend',
			SL_ESF_PLUGIN_URL . 'assets/sl-esf.css',
			[],
			SL_ESF_VERSION
		);
	}

	public function register_widget( $widgets_manager ) {
		if ( ! did_action( 'elementor/loaded' ) ) return;

		require_once SL_ESF_PLUGIN_DIR . 'includes/class-sl-esf-widget.php';
		require_once SL_ESF_PLUGIN_DIR . 'includes/class-sl-esf-results-widget.php';
		require_once SL_ESF_PLUGIN_DIR . 'includes/class-sl-esf-related-widget.php';
		require_once SL_ESF_PLUGIN_DIR . 'includes/class-sl-esf-amenities-widget.php';

		$widgets_manager->register( new \SL_ESF_Widget() );
		$widgets_manager->register( new \SL_ESF_Results_Widget() );
		$widgets_manager->register( new \SL_ESF_Related_Widget() );
		$widgets_manager->register( new \SL_ESF_Amenities_Widget() );
	}

	public function register_admin_page() {
		add_options_page(
			__( 'Active Enhancements', 'blueworx-hiraste-enhancements' ),
			__( 'Active Enhancements', 'blueworx-hiraste-enhancements' ),
			'manage_options',
			'sl-esf-active-enhancements',
			[ $this, 'render_admin_page' ]
		);
	}

	public function render_admin_page() {
		$features = [
			[
				'title' => __( 'Elementor Property Search Form', 'blueworx-hiraste-enhancements' ),
				'body'  => __( 'Adds an Elementor widget for property search with destination, guests, units, and advanced filters.', 'blueworx-hiraste-enhancements' ),
			],
			[
				'title' => __( 'Destination, Collection, and Amenity Filters', 'blueworx-hiraste-enhancements' ),
				'body'  => __( 'Connects selected destinations, collections, and amenities to WordPress property search results.', 'blueworx-hiraste-enhancements' ),
			],
			[
				'title' => __( 'Guest Capacity Filtering', 'blueworx-hiraste-enhancements' ),
				'body'  => __( 'Filters properties by the total guest capacity of their assigned units.', 'blueworx-hiraste-enhancements' ),
			],
			[
				'title' => __( 'Multi-Unit Search Matching', 'blueworx-hiraste-enhancements' ),
				'body'  => __( 'Checks each selected unit requirement against assigned property units before WordPress paginates results.', 'blueworx-hiraste-enhancements' ),
			],
			[
				'title' => __( 'Search Asset Loading', 'blueworx-hiraste-enhancements' ),
				'body'  => __( 'Loads the frontend JavaScript and CSS needed by the Elementor search form.', 'blueworx-hiraste-enhancements' ),
			],
			[
				'title' => __( 'Enhanced Property Results Widget', 'blueworx-hiraste-enhancements' ),
				'body'  => __( 'Adds an Elementor widget that renders filtered property results using a selected Elementor Loop Item template.', 'blueworx-hiraste-enhancements' ),
			],
			[
				'title' => __( 'Property Related Items Widget', 'blueworx-hiraste-enhancements' ),
				'body'  => __( 'Adds an Elementor widget for showing selected reviews or selected units on property pages in a grid or carousel.', 'blueworx-hiraste-enhancements' ),
			],
			[
				'title' => __( 'Property Amenities Widget', 'blueworx-hiraste-enhancements' ),
				'body'  => __( 'Adds an Elementor widget for showing assigned property amenities with icons in a loop grid.', 'blueworx-hiraste-enhancements' ),
			],
			[
				'title' => __( 'Taxonomy Ordering', 'blueworx-hiraste-enhancements' ),
				'body'  => __( 'Adds drag-and-drop ordering for destinations, collections, and amenities in WordPress admin.', 'blueworx-hiraste-enhancements' ),
			],
			[
				'title' => __( 'Property Favourites', 'blueworx-hiraste-enhancements' ),
				'body'  => __( 'Adds the [favourite_button] shortcode so logged-in users can save properties to the ACF "favourites" field on their user profile.', 'blueworx-hiraste-enhancements' ),
			],
			[
				'title' => __( 'Property Count', 'blueworx-hiraste-enhancements' ),
				'body'  => __( 'Adds the [tax_post_count] shortcode showing how many properties match on search results and archives.', 'blueworx-hiraste-enhancements' ),
			],
			[
				'title' => __( 'Unit Popup', 'blueworx-hiraste-enhancements' ),
				'body'  => __( 'Adds the [unit_popup_container] shortcode and loads the clicked unit\'s Elementor template into the unit popup.', 'blueworx-hiraste-enhancements' ),
			],
			[
				'title' => __( 'Read More Toggle', 'blueworx-hiraste-enhancements' ),
				'body'  => __( 'Trims any block with the read_more_text CSS class to 400 characters with a Read more link.', 'blueworx-hiraste-enhancements' ),
			],
		];
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Active Enhancements', 'blueworx-hiraste-enhancements' ); ?></h1>
			<p><?php echo esc_html__( 'These features are currently active in this plugin.', 'blueworx-hiraste-enhancements' ); ?></p>
			<div style="display:grid;gap:16px;max-width:1200px;">
				<?php foreach ( $features as $feature ) : ?>
					<div style="width:100%;background:#fff;border:1px solid #dcdcde;border-left:4px solid #2271b1;padding:18px 20px;box-sizing:border-box;">
						<h2 style="margin:0 0 8px;font-size:18px;"><?php echo esc_html( $feature['title'] ); ?></h2>
						<p style="margin:0;font-size:14px;"><?php echo esc_html( $feature['body'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}

new SL_ESF_Plugin();

add_filter( 'get_terms_args', function( $args, $taxonomies ) {
	if ( is_admin() || empty( $GLOBALS['sl_esf_filter_empty_loop_grid_terms'] ) ) {
		return $args;
	}

	$supported_taxonomies = [ 'destinations', 'collections' ];
	$taxonomies = array_map( 'sanitize_key', (array) $taxonomies );

	if ( empty( array_intersect( $supported_taxonomies, $taxonomies ) ) ) {
		return $args;
	}

	$args['hide_empty'] = true;
	return $args;
}, 10, 2 );


// ---- Search integration (properties + units filtering) ----

function blueworx_hiraste_enhancements_clean_int_array( $value ) {
	if ( ! is_array( $value ) ) {
		$value = [ $value ];
	}

	return array_values( array_filter( array_map( 'absint', $value ) ) );
}

function blueworx_hiraste_enhancements_get_search_request() {
	$data = [
		'destinations'     => [],
		'collections'      => [],
		'amenities'        => [],
		'destination_term' => 0,
		'unit_boxes'       => '',
		'units'            => 0,
		'guests'           => 0,
	];

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public search form; values are sanitized below.
	$destinations = isset( $_GET['destinations'] ) ? wp_unslash( $_GET['destinations'] ) : [];
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public search form; values are sanitized below.
	$collections = isset( $_GET['collections'] ) ? wp_unslash( $_GET['collections'] ) : [];
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public search form; values are sanitized below.
	$amenities = isset( $_GET['amenities'] ) ? wp_unslash( $_GET['amenities'] ) : [];
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public search form; value is sanitized below.
	$destination_term = isset( $_GET['destination_term'] ) ? wp_unslash( $_GET['destination_term'] ) : 0;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public search form; value is sanitized below.
	$unit_boxes = isset( $_GET['unit_boxes'] ) ? wp_unslash( $_GET['unit_boxes'] ) : '';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public search form; value is sanitized below.
	$units = isset( $_GET['units'] ) ? wp_unslash( $_GET['units'] ) : 0;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public search form; value is sanitized below.
	$guests = isset( $_GET['guests'] ) ? wp_unslash( $_GET['guests'] ) : 0;

	$data['destinations'] = blueworx_hiraste_enhancements_clean_int_array( $destinations );
	$data['collections'] = blueworx_hiraste_enhancements_clean_int_array( $collections );
	$data['amenities'] = blueworx_hiraste_enhancements_clean_int_array( $amenities );
	$data['destination_term'] = is_array( $destination_term ) ? 0 : absint( $destination_term );
	$data['unit_boxes'] = is_array( $unit_boxes ) ? '' : sanitize_text_field( $unit_boxes );
	$data['units'] = is_array( $units ) ? 0 : absint( $units );
	$data['guests'] = is_array( $guests ) ? 0 : absint( $guests );

	return $data;
}

function sl_esf_parse_unit_requirements( $unit_boxes ) {
	$requirements = [];

	if ( ! $unit_boxes ) {
		return $requirements;
	}

	$chunks = preg_split( '/\s*\/\s*/', trim( (string) $unit_boxes ) );
	foreach ( $chunks as $chunk ) {
		$chunk = trim( $chunk );
		if ( $chunk === '' ) continue;

		$parts = array_map( 'trim', explode( ',', $chunk ) );
		if ( count( $parts ) < 2 ) continue;

		$requirements[] = [
			'sleeps' => max( 0, intval( $parts[0] ) ),
			'beds'   => max( 0, intval( $parts[1] ) ),
		];
	}

	usort( $requirements, function( $a, $b ) {
		$score_a = $a['sleeps'] + $a['beds'];
		$score_b = $b['sleeps'] + $b['beds'];
		return $score_b <=> $score_a;
	} );

	return $requirements;
}

function sl_esf_get_property_unit_ids( $property_id ) {
	$unit_ids = get_post_meta( $property_id, 'select_units', true );
	if ( empty( $unit_ids ) ) return [];

	$unit_ids = is_string( $unit_ids ) ? maybe_unserialize( $unit_ids ) : $unit_ids;
	if ( is_numeric( $unit_ids ) ) $unit_ids = [ intval( $unit_ids ) ];
	if ( ! is_array( $unit_ids ) ) return [];

	return array_values( array_filter( array_map( 'intval', $unit_ids ) ) );
}

function sl_esf_property_matches_unit_filters( $property_id, $requirements, $min_units, $guests ) {
	$unit_ids = sl_esf_get_property_unit_ids( $property_id );

	if ( $min_units && count( $unit_ids ) < $min_units ) {
		return false;
	}

	$units = [];
	$total_guests = 0;

	foreach ( $unit_ids as $unit_id ) {
		$sleeps = intval( get_post_meta( $unit_id, 'total_guests', true ) );
		$beds   = intval( get_post_meta( $unit_id, 'total_bedrooms', true ) );

		$total_guests += $sleeps;
		$units[] = [
			'id'     => $unit_id,
			'sleeps' => $sleeps,
			'beds'   => $beds,
		];
	}

	if ( $guests && $total_guests < $guests ) {
		return false;
	}

	if ( empty( $requirements ) ) {
		return true;
	}

	if ( count( $units ) < count( $requirements ) ) {
		return false;
	}

	$used = array_fill( 0, count( $units ), false );

	$match_requirement = function( $index ) use ( &$match_requirement, &$requirements, &$units, &$used ) {
		if ( $index >= count( $requirements ) ) return true;

		$requirement = $requirements[ $index ];

		for ( $i = 0; $i < count( $units ); $i++ ) {
			if ( $used[ $i ] ) continue;

			$unit = $units[ $i ];
			if ( $unit['sleeps'] < $requirement['sleeps'] ) continue;
			if ( $unit['beds'] < $requirement['beds'] ) continue;

			$used[ $i ] = true;
			if ( $match_requirement( $index + 1 ) ) return true;
			$used[ $i ] = false;
		}

		return false;
	};

	return $match_requirement( 0 );
}

function sl_esf_get_filter_cache_version() {
	return (string) get_option( 'sl_esf_filter_cache_version', '1' );
}

function sl_esf_bump_filter_cache_version() {
	update_option( 'sl_esf_filter_cache_version', (string) time(), false );
}

function blueworx_hiraste_enhancements_build_tax_query( $search_request ) {
	$grouped_terms = [
		'destinations' => [],
		'collections'  => [],
		'amenities'    => [],
	];

	foreach ( $grouped_terms as $taxonomy => $terms ) {
		if ( ! empty( $search_request[ $taxonomy ] ) ) {
			$grouped_terms[ $taxonomy ] = array_merge(
				$grouped_terms[ $taxonomy ],
				blueworx_hiraste_enhancements_clean_int_array( $search_request[ $taxonomy ] )
			);
		}
	}

	if ( ! empty( $search_request['destination_term'] ) ) {
		$grouped_terms['destinations'][] = absint( $search_request['destination_term'] );
	}

	$tax_clauses = [];
	foreach ( $grouped_terms as $taxonomy => $terms ) {
		$terms = array_values( array_unique( array_filter( array_map( 'absint', $terms ) ) ) );
		if ( empty( $terms ) ) {
			continue;
		}

		$tax_clauses[] = [
			'taxonomy' => $taxonomy,
			'field'    => 'term_id',
			'terms'    => $terms,
			'operator' => 'IN',
		];
	}

	if ( count( $tax_clauses ) < 2 ) {
		return $tax_clauses;
	}

	return array_merge( [ 'relation' => 'AND' ], $tax_clauses );
}

function sl_esf_maybe_bump_filter_cache_version_for_post( $post_id, $post = null ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	$post_type = $post instanceof WP_Post ? $post->post_type : get_post_type( $post_id );
	if ( in_array( $post_type, [ 'property', 'unit' ], true ) ) {
		sl_esf_bump_filter_cache_version();
	}
}

add_action( 'save_post', 'sl_esf_maybe_bump_filter_cache_version_for_post', 10, 2 );
add_action( 'before_delete_post', 'sl_esf_maybe_bump_filter_cache_version_for_post', 10, 2 );
add_action( 'deleted_post', 'sl_esf_maybe_bump_filter_cache_version_for_post', 10, 2 );

function sl_esf_find_matching_property_ids( $requirements, $min_units, $guests ) {
	$cache_key = 'sl_esf_matching_' . md5( wp_json_encode([
		'requirements' => $requirements,
		'min_units'    => absint( $min_units ),
		'guests'       => absint( $guests ),
		'version'      => sl_esf_get_filter_cache_version(),
	]) );
	$cached = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return array_map( 'intval', $cached );
	}

	$property_ids = get_posts([
		'post_type'              => 'property',
		'post_status'            => 'publish',
		'posts_per_page'         => -1,
		'fields'                 => 'ids',
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	]);

	$matching_ids = [];

	foreach ( $property_ids as $property_id ) {
		if ( sl_esf_property_matches_unit_filters( $property_id, $requirements, $min_units, $guests ) ) {
			$matching_ids[] = intval( $property_id );
		}
	}

	set_transient( $cache_key, $matching_ids, HOUR_IN_SECONDS );

	return $matching_ids;
}

add_action( 'pre_get_posts', function( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) return;
	if ( ! $q->is_search() ) return;

	// Force search to return properties.
	$q->set( 'post_type', [ 'property' ] );
	$search_request = blueworx_hiraste_enhancements_get_search_request();

	$tax_query = blueworx_hiraste_enhancements_build_tax_query( $search_request );
	if ( ! empty( $tax_query ) ) {
		$q->set( 'tax_query', $tax_query );
	}

	$raw_unit_boxes = $search_request['unit_boxes'];
	$requirements = sl_esf_parse_unit_requirements( $raw_unit_boxes );
	$min_units = $search_request['units'];
	$guests = $search_request['guests'];

	if ( empty( $requirements ) && ! $min_units && ! $guests ) {
		return;
	}

	$matching_ids = sl_esf_find_matching_property_ids( $requirements, $min_units, $guests );
	$existing_ids = $q->get( 'post__in' );

	if ( is_array( $existing_ids ) && ! empty( $existing_ids ) ) {
		$matching_ids = array_values( array_intersect( array_map( 'intval', $existing_ids ), $matching_ids ) );
	}

	$q->set( 'post__in', ! empty( $matching_ids ) ? $matching_ids : [ 0 ] );
}, 20 );
