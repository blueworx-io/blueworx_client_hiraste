<?php
/**
 * Property map.
 *
 * An ad-free OpenStreetMap of a property, with its name, area and a Get
 * directions link. Shown three ways:
 *  - [property_map] on the property page;
 *  - under each search result card, slid open by its "View on map" link;
 *  - "View on map" on a property page scrolls to that page's map.
 *
 * The coordinates come from the property's existing Google Maps link, so
 * nothing new needs filling in. The Map location box on the property edit
 * screen can override them and sets the area line under the map.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class SL_ESF_Property_Map {

	const META_LAT     = '_sl_esf_map_lat';
	const META_LNG     = '_sl_esf_map_lng';
	const META_AREA    = '_sl_esf_map_area';
	const META_FOUND   = '_sl_esf_map_found';
	const NONCE_ACTION = 'sl_esf_map_location';
	const ZOOM         = 14;

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
		add_shortcode( 'property_map', [ $this, 'render_shortcode' ] );
		add_action( 'add_meta_boxes_property', [ $this, 'add_meta_box' ] );
		add_action( 'save_post_property', [ $this, 'save_meta_box' ], 10, 2 );
	}

	public function register_assets() {
		wp_register_style(
			'sl-esf-leaflet',
			SL_ESF_PLUGIN_URL . 'assets/vendor/leaflet/leaflet.css',
			[],
			'1.9.4'
		);
		wp_register_script(
			'sl-esf-leaflet',
			SL_ESF_PLUGIN_URL . 'assets/vendor/leaflet/leaflet.js',
			[],
			'1.9.4',
			true
		);
		wp_register_style(
			'sl-esf-property-map',
			SL_ESF_PLUGIN_URL . 'assets/sl-esf-property-map.css',
			[ 'sl-esf-leaflet' ],
			SL_ESF_VERSION
		);
		wp_register_script(
			'sl-esf-property-map',
			SL_ESF_PLUGIN_URL . 'assets/sl-esf-property-map.js',
			[ 'sl-esf-leaflet' ],
			SL_ESF_VERSION,
			true
		);
		wp_localize_script( 'sl-esf-property-map', 'slEsfPropertyMap', [
			'zoom'       => self::ZOOM,
			'markerIcon' => SL_ESF_PLUGIN_URL . 'assets/images/map-marker.svg',
		] );
	}

	/**
	 * Usage: [property_map] or [property_map post_id="123"]
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts( [
			'post_id' => get_the_ID(),
		], $atts, 'property_map' );

		return self::render( absint( $atts['post_id'] ) );
	}

	/**
	 * The map panel for a property, or '' when it has no location.
	 * A collapsible panel starts closed and opens from the card's "View on map".
	 */
	public static function render( $post_id, $collapsible = false ) {
		$location = self::get_location( $post_id );
		if ( ! $location ) {
			return '';
		}

		wp_enqueue_style( 'sl-esf-property-map' );
		wp_enqueue_script( 'sl-esf-property-map' );

		$title = get_the_title( $post_id );
		$directions_url = add_query_arg(
			[
				'api'         => 1,
				'destination' => $location['lat'] . ',' . $location['lng'],
			],
			'https://www.google.com/maps/dir/'
		);
		$icons = SL_ESF_PLUGIN_URL . 'assets/images/';

		ob_start();
		?>
		<div class="sl-esf-map<?php echo $collapsible ? ' sl-esf-map--collapsible' : ''; ?>"<?php echo $collapsible ? ' hidden' : ''; ?> data-sl-esf-map data-lat="<?php echo esc_attr( $location['lat'] ); ?>" data-lng="<?php echo esc_attr( $location['lng'] ); ?>" data-title="<?php echo esc_attr( $title ); ?>">
			<div class="sl-esf-map__preview">
				<div class="sl-esf-map__canvas" role="region" aria-label="<?php echo esc_attr( sprintf( /* translators: %s is a property name. */ __( 'Map of %s', 'blueworx-hiraste-enhancements' ), $title ) ); ?>"></div>
				<button type="button" class="sl-esf-map__expand" data-sl-esf-map-expand aria-label="<?php esc_attr_e( 'Full screen map', 'blueworx-hiraste-enhancements' ); ?>">
					<img src="<?php echo esc_url( $icons . 'map-expand.svg' ); ?>" width="44" height="44" alt="" />
				</button>
				<div class="sl-esf-map__zoom">
					<img src="<?php echo esc_url( $icons . 'map-zoom.svg' ); ?>" width="44" height="88" alt="" />
					<button type="button" class="sl-esf-map__zoom-in" data-sl-esf-map-zoom="1" aria-label="<?php esc_attr_e( 'Zoom in', 'blueworx-hiraste-enhancements' ); ?>"></button>
					<button type="button" class="sl-esf-map__zoom-out" data-sl-esf-map-zoom="-1" aria-label="<?php esc_attr_e( 'Zoom out', 'blueworx-hiraste-enhancements' ); ?>"></button>
				</div>
				<div class="sl-esf-map__attribution">
					&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> <?php esc_html_e( 'contributors', 'blueworx-hiraste-enhancements' ); ?>
				</div>
			</div>
			<div class="sl-esf-map__info">
				<div class="sl-esf-map__place">
					<p class="sl-esf-map__title"><?php echo esc_html( $title ); ?></p>
					<?php if ( $location['area'] !== '' ) : ?>
						<p class="sl-esf-map__area"><?php echo esc_html( $location['area'] ); ?></p>
					<?php endif; ?>
				</div>
				<a class="sl-esf-map__directions" href="<?php echo esc_url( $directions_url ); ?>" target="_blank" rel="noopener">
					<?php esc_html_e( 'Get directions', 'blueworx-hiraste-enhancements' ); ?>
					<img src="<?php echo esc_url( $icons . 'map-directions-arrow.svg' ); ?>" width="14.2803" height="14.2803" alt="" />
				</a>
			</div>
		</div>
		<?php
		return trim( ob_get_clean() );
	}

	/**
	 * Lat, lng and area line for a property, or null when it has no location.
	 */
	public static function get_location( $post_id ) {
		if ( ! $post_id || get_post_type( $post_id ) !== 'property' ) {
			return null;
		}

		$lat = get_post_meta( $post_id, self::META_LAT, true );
		$lng = get_post_meta( $post_id, self::META_LNG, true );

		if ( ! self::is_coordinate_pair( $lat, $lng ) ) {
			$found = self::find_from_google_link( $post_id );
			if ( ! $found ) {
				return null;
			}
			list( $lat, $lng ) = $found;
		}

		$area = trim( (string) get_post_meta( $post_id, self::META_AREA, true ) );
		if ( $area === '' ) {
			$destinations = get_the_terms( $post_id, 'destinations' );
			if ( is_array( $destinations ) ) {
				$area = implode( ', ', wp_list_pluck( $destinations, 'name' ) );
			}
		}

		return [
			'lat'  => (float) $lat,
			'lng'  => (float) $lng,
			'area' => $area,
		];
	}

	/**
	 * Coordinates read from the property's Google Maps link, remembered against
	 * that link so a changed link is looked up again. Short links are followed
	 * once to where they point, which holds the coordinates.
	 */
	private static function find_from_google_link( $post_id ) {
		$link = self::get_google_link( $post_id );
		if ( $link === '' ) {
			return null;
		}

		$found = get_post_meta( $post_id, self::META_FOUND, true );
		if ( is_array( $found ) && isset( $found['link'] ) && $found['link'] === $link ) {
			return isset( $found['lat'], $found['lng'] ) ? [ $found['lat'], $found['lng'] ] : null;
		}

		$coords = self::parse_coordinates( $link );
		if ( ! $coords && preg_match( '#^https?://(maps\.app\.goo\.gl|goo\.gl/maps)/#i', $link ) ) {
			$retry_key = 'sl_esf_map_retry_' . $post_id;
			if ( get_transient( $retry_key ) ) {
				return null;
			}

			$response = wp_remote_head( $link, [ 'redirection' => 0, 'timeout' => 5 ] );
			if ( is_wp_error( $response ) ) {
				// Google could not be reached: try again later rather than on every page view.
				set_transient( $retry_key, 1, HOUR_IN_SECONDS );
				return null;
			}
			$coords = self::parse_coordinates( rawurldecode( (string) wp_remote_retrieve_header( $response, 'location' ) ) );
		}

		update_post_meta( $post_id, self::META_FOUND, $coords
			? [ 'link' => $link, 'lat' => $coords[0], 'lng' => $coords[1] ]
			: [ 'link' => $link ]
		);

		return $coords;
	}

	/**
	 * The first Google Maps link saved anywhere on the property.
	 */
	private static function get_google_link( $post_id ) {
		foreach ( get_post_meta( $post_id ) as $key => $values ) {
			if ( strpos( $key, '_' ) === 0 ) {
				continue;
			}
			foreach ( $values as $value ) {
				if ( is_string( $value ) && preg_match( '#^https?://(maps\.app\.goo\.gl/|goo\.gl/maps/|(www\.)?google\.[a-z.]+/maps)\S*$#i', trim( $value ) ) ) {
					return trim( $value );
				}
			}
		}

		return '';
	}

	private static function parse_coordinates( $url ) {
		$patterns = [
			'#!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)#',            // Place pin.
			'#@(-?\d+\.\d+),(-?\d+\.\d+)#',                 // Map view.
			'#(?:search/|[?&](?:q|query|ll)=)(-?\d+\.\d+),\+?\s*(-?\d+\.\d+)#', // Dropped pin or search.
		];

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $url, $match ) && self::is_coordinate_pair( $match[1], $match[2] ) ) {
				return [ (float) $match[1], (float) $match[2] ];
			}
		}

		return null;
	}

	private static function is_coordinate_pair( $lat, $lng ) {
		return is_numeric( $lat ) && is_numeric( $lng )
			&& abs( (float) $lat ) <= 90 && abs( (float) $lng ) <= 180
			&& ! ( (float) $lat === 0.0 && (float) $lng === 0.0 );
	}

	public function add_meta_box() {
		add_meta_box(
			'sl-esf-map-location',
			__( 'Map location', 'blueworx-hiraste-enhancements' ),
			[ $this, 'render_meta_box' ],
			'property',
			'side'
		);
	}

	public function render_meta_box( $post ) {
		wp_nonce_field( self::NONCE_ACTION, 'sl_esf_map_nonce' );

		$found = self::find_from_google_link( $post->ID );
		$fields = [
			self::META_AREA => [
				'label'       => __( 'Area shown under the map', 'blueworx-hiraste-enhancements' ),
				'placeholder' => __( 'e.g. Lizard peninsula, Cornwall', 'blueworx-hiraste-enhancements' ),
			],
			self::META_LAT  => [
				'label'       => __( 'Latitude', 'blueworx-hiraste-enhancements' ),
				'placeholder' => $found ? (string) $found[0] : '',
			],
			self::META_LNG  => [
				'label'       => __( 'Longitude', 'blueworx-hiraste-enhancements' ),
				'placeholder' => $found ? (string) $found[1] : '',
			],
		];
		?>
		<p><?php echo $found
			? esc_html__( 'The pin is taken from the Google Maps link. Fill in latitude and longitude only to move it.', 'blueworx-hiraste-enhancements' )
			: esc_html__( 'No Google Maps link with a location was found, so no map shows. Add the link, or fill in latitude and longitude.', 'blueworx-hiraste-enhancements' ); ?></p>
		<?php foreach ( $fields as $key => $field ) : ?>
			<p>
				<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
				<input type="text" class="widefat" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( get_post_meta( $post->ID, $key, true ) ); ?>" placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" />
			</p>
		<?php endforeach; ?>
		<p class="description"><?php esc_html_e( 'Area is left blank? The destination is shown instead.', 'blueworx-hiraste-enhancements' ); ?></p>
		<?php
	}

	public function save_meta_box( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['sl_esf_map_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sl_esf_map_nonce'] ) ), self::NONCE_ACTION ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( [ self::META_AREA, self::META_LAT, self::META_LNG ] as $key ) {
			$value = isset( $_POST[ $key ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ) : '';
			if ( $key !== self::META_AREA && $value !== '' && ! is_numeric( $value ) ) {
				$value = '';
			}

			if ( $value === '' ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}
	}
}
