<?php
/**
 * Property favourites.
 *
 * Stores favourited post IDs in the ACF "favourites" field on the user and
 * exposes a [favourite_button] shortcode that toggles them via admin-ajax.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class SL_ESF_Favourites {

	const FIELD        = 'favourites';
	const AJAX_ACTION  = 'sl_toggle_favourite';
	const NONCE_ACTION = 'sl_toggle_favourite_nonce';
	const DEFAULT_ICON = 'https://res.cloudinary.com/dwzmujon3/images/v1773774517/WordPress/Icon-5/Icon-5.svg?_i=AA';

	public function __construct() {
		// The original Code Snippet still defines these; stand aside until it is switched off.
		if ( function_exists( 'sl_toggle_favourite' ) || shortcode_exists( 'favourite_button' ) ) {
			add_action( 'admin_notices', [ $this, 'render_snippet_conflict_notice' ] );
			return;
		}

		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'toggle_favourite' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, [ $this, 'toggle_favourite' ] );
		add_shortcode( 'favourite_button', [ $this, 'render_button' ] );
	}

	public function render_snippet_conflict_notice() {
		if ( ! current_user_can( 'manage_options' ) ) return;
		?>
		<div class="notice notice-warning">
			<p><?php echo esc_html__( 'Favourites are now built into the Hiraste Travel plugin. Deactivate the "Create Favourites Button" code snippets so the plugin version can take over.', 'blueworx-hiraste-enhancements' ); ?></p>
		</div>
		<?php
	}

	public function register_assets() {
		wp_register_style(
			'sl-esf-favourites',
			SL_ESF_PLUGIN_URL . 'assets/sl-esf-favourites.css',
			[],
			SL_ESF_VERSION
		);
		wp_register_script(
			'sl-esf-favourites',
			SL_ESF_PLUGIN_URL . 'assets/sl-esf-favourites.js',
			[],
			SL_ESF_VERSION,
			true
		);
		wp_localize_script( 'sl-esf-favourites', 'slEsfFavourites', [
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'action'      => self::AJAX_ACTION,
			'loggedIn'    => is_user_logged_in(),
			'loginNotice' => __( 'Please log in to save favourites.', 'blueworx-hiraste-enhancements' ),
			'errorNotice' => __( 'Something went wrong.', 'blueworx-hiraste-enhancements' ),
		] );
	}

	public static function get_user_favourites( $user_id ) {
		$favourites = function_exists( 'get_field' ) ? get_field( self::FIELD, 'user_' . $user_id ) : get_user_meta( $user_id, self::FIELD, true );

		if ( ! is_array( $favourites ) ) {
			return [];
		}

		return array_values( array_filter( array_map( 'intval', $favourites ) ) );
	}

	public static function is_favourited( $user_id, $post_id ) {
		return in_array( (int) $post_id, self::get_user_favourites( $user_id ), true );
	}

	public function toggle_favourite() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'You must be logged in.', 'blueworx-hiraste-enhancements' ) ], 401 );
		}

		$user_id = get_current_user_id();
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( ! $post_id || get_post_status( $post_id ) === false ) {
			wp_send_json_error( [ 'message' => __( 'Invalid post.', 'blueworx-hiraste-enhancements' ) ], 400 );
		}

		$favourites = self::get_user_favourites( $user_id );

		if ( in_array( $post_id, $favourites, true ) ) {
			$favourites    = array_values( array_diff( $favourites, [ $post_id ] ) );
			$is_favourited = false;
		} else {
			$favourites[]  = $post_id;
			$favourites    = array_values( array_unique( $favourites ) );
			$is_favourited = true;
		}

		if ( function_exists( 'update_field' ) ) {
			update_field( self::FIELD, $favourites, 'user_' . $user_id );
		} else {
			update_user_meta( $user_id, self::FIELD, $favourites );
		}

		wp_send_json_success( [ 'is_favourited' => $is_favourited ] );
	}

	/**
	 * Usage: [favourite_button] or [favourite_button post_id="123" icon="https://..."]
	 */
	public function render_button( $atts ) {
		$atts = shortcode_atts( [
			'post_id' => get_the_ID(),
			'icon'    => self::DEFAULT_ICON,
		], $atts, 'favourite_button' );

		$post_id = absint( $atts['post_id'] );
		if ( ! $post_id ) {
			return '';
		}

		wp_enqueue_style( 'sl-esf-favourites' );
		wp_enqueue_script( 'sl-esf-favourites' );

		$is_favourited = is_user_logged_in() && self::is_favourited( get_current_user_id(), $post_id );

		return sprintf(
			'<button type="button" class="sl-favourite-btn%1$s" data-post-id="%2$s" data-nonce="%3$s" aria-pressed="%4$s" aria-label="%5$s"><img src="%6$s" alt="" /></button>',
			$is_favourited ? ' is-active' : '',
			esc_attr( $post_id ),
			esc_attr( wp_create_nonce( self::NONCE_ACTION ) ),
			$is_favourited ? 'true' : 'false',
			esc_attr__( 'Toggle favourite', 'blueworx-hiraste-enhancements' ),
			esc_url( $atts['icon'] )
		);
	}
}
