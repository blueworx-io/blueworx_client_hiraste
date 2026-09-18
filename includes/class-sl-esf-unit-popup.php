<?php
/**
 * Unit popup.
 *
 * Property pages list units in an Elementor loop; clicking one opens an
 * Elementor popup. The JS records which unit was clicked, and when the popup
 * opens it asks this class to render the unit template for that unit.
 *
 * Shortcode: [unit_popup_container] — placeholder the rendered template is
 * injected into. Place it inside the popup.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class SL_ESF_Unit_Popup {

	const AJAX_ACTION = 'unit_popup_render';

	/** Elementor popup that shows the unit. Override with the sl_esf_unit_popup_id filter. */
	const DEFAULT_POPUP_ID = 1374;

	/** Elementor template rendered inside the popup. Override with the sl_esf_unit_popup_template_id filter. */
	const DEFAULT_TEMPLATE_ID = 1353;

	public function __construct() {
		// The original Code Snippet still defines these; stand aside until it is switched off.
		if ( function_exists( 'unit_popup_render' ) || shortcode_exists( 'unit_popup_container' ) ) {
			add_action( 'admin_notices', [ $this, 'render_snippet_conflict_notice' ] );
			return;
		}

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'render_unit' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, [ $this, 'render_unit' ] );
		add_shortcode( 'unit_popup_container', [ $this, 'render_container' ] );
	}

	public function render_snippet_conflict_notice() {
		if ( ! current_user_can( 'manage_options' ) ) return;
		?>
		<div class="notice notice-warning">
			<p><?php echo esc_html__( 'The unit popup is now built into the Hiraste Travel plugin. Deactivate the "Render Template Inside Popup", "Log Unit ID to the URL" and "Get Unit Info from URL" code snippets so the plugin version can take over.', 'blueworx-hiraste-enhancements' ); ?></p>
		</div>
		<?php
	}

	public static function get_popup_id() {
		return (int) apply_filters( 'sl_esf_unit_popup_id', self::DEFAULT_POPUP_ID );
	}

	public static function get_template_id() {
		return (int) apply_filters( 'sl_esf_unit_popup_template_id', self::DEFAULT_TEMPLATE_ID );
	}

	public function enqueue_assets() {
		wp_enqueue_script(
			'sl-esf-unit-popup',
			SL_ESF_PLUGIN_URL . 'assets/sl-esf-unit-popup.js',
			[],
			SL_ESF_VERSION,
			true
		);
		wp_localize_script( 'sl-esf-unit-popup', 'slEsfUnitPopup', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'action'  => self::AJAX_ACTION,
			'popupId' => (string) self::get_popup_id(),
		] );
	}

	public function render_container() {
		return '<div id="unit-popup-render"></div>';
	}

	public function render_unit() {
		$unit_id = isset( $_POST['unit_id'] ) ? absint( $_POST['unit_id'] ) : 0;
		if ( ! $unit_id ) {
			wp_send_json_error( 'Missing unit_id', 400 );
		}

		$unit_post = get_post( $unit_id );
		if ( ! $unit_post || $unit_post->post_status !== 'publish' || $unit_post->post_type !== 'unit' ) {
			wp_send_json_error( 'Invalid unit', 404 );
		}

		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			wp_send_json_error( 'Elementor not available', 500 );
		}

		global $post;
		$old_post = $post;

		$post = $unit_post;
		setup_postdata( $post );

		$html = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( self::get_template_id(), true );

		wp_reset_postdata();
		$post = $old_post;

		wp_send_json_success( [ 'html' => $html ] );
	}
}
