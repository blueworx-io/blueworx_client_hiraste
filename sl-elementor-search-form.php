<?php
/**
 * Plugin Name: SL Elementor Search Form
 * Description: Adds an Elementor widget that renders a dynamic search form UI and filters Elementor/WordPress search results for Properties.
 * Version: 0.2.6
 * Author: ChatGPT
 * Text Domain: sl-elementor-search-form
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SL_ESF_VERSION', '0.2.6' );
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
		$widgets_manager->register( new \SL_ESF_Widget() );
	}
}

new SL_ESF_Plugin();


// ---- Search integration (properties + units filtering) ----

/**
 * Apply taxonomy filters via WP_Query where possible.
 * Units/per-unit matching is handled in posts_results due to per-property unit assignment logic.
 */
add_action( 'pre_get_posts', function( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) return;
	if ( ! $q->is_search() ) return;

	// Force search to return properties.
	$q->set( 'post_type', [ 'property' ] );

	// Taxonomy OR across destinations/collections/amenities (any selected term in any taxonomy).
	$tax_clauses = [];

	$map = [
		'destinations' => 'destinations',
		'collections'  => 'collections',
		'amenities'    => 'amenities',
	];

	foreach ( $map as $param => $tax ) {
		$raw = isset( $_GET[ $param ] ) ? $_GET[ $param ] : [];
		if ( ! is_array( $raw ) ) $raw = [ $raw ];
		$ids = array_values( array_filter( array_map( 'intval', $raw ) ) );
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
	$dest_single = isset( $_GET['destination_term'] ) ? intval( $_GET['destination_term'] ) : 0;
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
}, 20 );

/**
 * Filter posts after query to enforce unit assignment requirements:
 * - Property has ACF Post Object field "select_units" (multiple) containing unit IDs
 * - Each unit has meta total_guests (sleeps) and total_bedrooms (bedrooms)
 * - User provides unit_boxes string "sleeps,bedrooms/ sleeps,bedrooms/ ..."
 */
add_filter( 'posts_results', function( $posts, $q ) {
	if ( is_admin() || ! $q->is_main_query() ) return $posts;
	if ( ! $q->is_search() ) return $posts;

	$unit_boxes = isset( $_GET['unit_boxes'] ) ? (string) $_GET['unit_boxes'] : '';
	$requirements = [];

	if ( $unit_boxes ) {
		$chunks = preg_split( '/\s*\/\s*/', trim( $unit_boxes ) );
		foreach ( $chunks as $c ) {
			$c = trim( $c );
			if ( $c === '' ) continue;
			$parts = array_map( 'trim', explode( ',', $c ) );
			if ( count( $parts ) < 2 ) continue;
			$sleeps = intval( $parts[0] );
			$beds   = intval( $parts[1] );
			$requirements[] = [ 'sleeps' => max( 0, $sleeps ), 'beds' => max( 0, $beds ) ];
		}
	}

	$min_units = ( isset( $_GET['units'] ) && $_GET['units'] !== '' ) ? max( 1, intval( $_GET['units'] ) ) : 0;

	// If no unit-based constraints, nothing to filter here.
	if ( empty( $requirements ) && ! $min_units ) return $posts;

	// Sort requirements hardest-first.
	usort( $requirements, function( $a, $b ) {
		$sa = $a['sleeps'] + $a['beds'];
		$sb = $b['sleeps'] + $b['beds'];
		return $sb <=> $sa;
	} );

	$filtered = [];

	foreach ( $posts as $p ) {
		$property_id = $p->ID;

		// ACF Post Object (multiple) stored in meta under "select_units".
		$unit_ids = get_post_meta( $property_id, 'select_units', true );
		if ( empty( $unit_ids ) ) $unit_ids = [];

		$tmp = is_string( $unit_ids ) ? maybe_unserialize( $unit_ids ) : $unit_ids;
		$unit_ids = $tmp;

		if ( is_numeric( $unit_ids ) ) $unit_ids = [ intval( $unit_ids ) ];
		if ( ! is_array( $unit_ids ) ) $unit_ids = [];
		$unit_ids = array_values( array_filter( array_map( 'intval', $unit_ids ) ) );

		if ( $min_units && count( $unit_ids ) < $min_units ) continue;

		// Load unit capacities.
		$units = [];
		foreach ( $unit_ids as $uid ) {
			$sleeps = intval( get_post_meta( $uid, 'total_guests', true ) );
			$beds   = intval( get_post_meta( $uid, 'total_bedrooms', true ) );
			$units[] = [ 'id' => $uid, 'sleeps' => $sleeps, 'beds' => $beds ];
		}

		if ( empty( $requirements ) ) {
			$filtered[] = $p;
			continue;
		}

		if ( count( $units ) < count( $requirements ) ) continue;

		$used = array_fill( 0, count( $units ), false );

		$dfs = function( $i ) use ( &$dfs, &$requirements, &$units, &$used ) {
			if ( $i >= count( $requirements ) ) return true;

			$req = $requirements[ $i ];

			for ( $u = 0; $u < count( $units ); $u++ ) {
				if ( $used[ $u ] ) continue;

				$unit = $units[ $u ];
				if ( $unit['sleeps'] < $req['sleeps'] ) continue;
				if ( $unit['beds'] < $req['beds'] ) continue;

				$used[ $u ] = true;
				if ( $dfs( $i + 1 ) ) return true;
				$used[ $u ] = false;
			}
			return false;
		};

		if ( $dfs( 0 ) ) $filtered[] = $p;
	}

	return $filtered;
}, 30, 2 );
