<?php
/**
 * Plugin Name: BlueWorx Hiraste Enhancements
 * Description: Adds Elementor widgets for Hiraste property search, filtered property results, and related property items.
 * Version: 0.3.2
 * Author: ChatGPT
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: blueworx-hiraste-enhancements
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SL_ESF_VERSION', '0.3.2' );
define( 'SL_ESF_PLUGIN_FILE', __FILE__ );
define( 'SL_ESF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SL_ESF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

final class SL_ESF_Plugin {

	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'init' ] );
	}

	public function init() {
		add_action( 'elementor/widgets/register', [ $this, 'register_widget' ] );
		add_action( 'elementor/frontend/after_register_scripts', [ $this, 'register_assets' ] );
		add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
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

		$widgets_manager->register( new \SL_ESF_Widget() );
		$widgets_manager->register( new \SL_ESF_Results_Widget() );
		$widgets_manager->register( new \SL_ESF_Related_Widget() );
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
				'body'  => __( 'Adds an Elementor widget for showing selected reviews, selected units, or assigned amenities on property pages.', 'blueworx-hiraste-enhancements' ),
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

function sl_esf_find_matching_property_ids( $requirements, $min_units, $guests ) {
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

	return $matching_ids;
}

add_action( 'pre_get_posts', function( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) return;
	if ( ! $q->is_search() ) return;

	// Force search to return properties.
	$q->set( 'post_type', [ 'property' ] );
	$search_request = blueworx_hiraste_enhancements_get_search_request();

	// Taxonomy OR across destinations/collections/amenities (any selected term in any taxonomy).
	$tax_clauses = [];

	$map = [
		'destinations' => 'destinations',
		'collections'  => 'collections',
		'amenities'    => 'amenities',
	];

	foreach ( $map as $param => $tax ) {
		$ids = $search_request[ $param ];
		if ( ! empty( $ids ) ) {
			$tax_clauses[] = [
				'taxonomy' => $tax,
				'field'    => 'term_id',
				'terms'    => $ids,
				'operator' => 'IN',
			];
		}
	}

	// Destination single select (term id) - treated as another OR clause.
	$dest_single = $search_request['destination_term'];
	if ( $dest_single ) {
		$tax_clauses[] = [
			'taxonomy' => 'destinations',
			'field'    => 'term_id',
			'terms'    => [ $dest_single ],
			'operator' => 'IN',
		];
	}

	if ( ! empty( $tax_clauses ) ) {
		$q->set( 'tax_query', array_merge( [ 'relation' => 'OR' ], $tax_clauses ) );
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
