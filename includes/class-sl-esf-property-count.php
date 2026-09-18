<?php
/**
 * Property count heading for search results and archives.
 *
 * Shortcode: [tax_post_count]
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class SL_ESF_Property_Count {

	public function __construct() {
		// The original Code Snippet still defines these; stand aside until it is switched off.
		if ( function_exists( 'sl_taxonomy_post_count_shortcode' ) || shortcode_exists( 'tax_post_count' ) ) {
			add_action( 'admin_notices', [ $this, 'render_snippet_conflict_notice' ] );
			return;
		}

		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
		add_shortcode( 'tax_post_count', [ $this, 'render' ] );
	}

	public function render_snippet_conflict_notice() {
		if ( ! current_user_can( 'manage_options' ) ) return;
		?>
		<div class="notice notice-warning">
			<p><?php echo esc_html__( 'The property count is now built into the Hiraste Travel plugin. Deactivate the "Display Property Count" code snippet so the plugin version can take over.', 'blueworx-hiraste-enhancements' ); ?></p>
		</div>
		<?php
	}

	public function register_assets() {
		wp_register_style(
			'sl-esf-property-count',
			SL_ESF_PLUGIN_URL . 'assets/sl-esf-property-count.css',
			[],
			SL_ESF_VERSION
		);
	}

	public function render() {
		global $wp_query;

		if ( is_tax() ) {
			$term  = get_queried_object();
			$count = ( $term && ! empty( $term->term_id ) ) ? (int) $term->count : 0;
		} elseif ( is_search() || is_post_type_archive( 'property' ) ) {
			$count = (int) $wp_query->found_posts;
		} else {
			return '';
		}

		if ( $count === 0 ) {
			$count_text = __( 'Properties available, please search again', 'blueworx-hiraste-enhancements' );
			$extra_text = '';
		} elseif ( $count === 1 ) {
			$count_text = __( 'Positively perfect place curated by us', 'blueworx-hiraste-enhancements' );
			$extra_text = __( 'This property is an exact match for your search criteria.', 'blueworx-hiraste-enhancements' );
		} else {
			$count_text = __( 'Positively perfect places curated by us', 'blueworx-hiraste-enhancements' );
			$extra_text = __( 'These properties are an exact match for your search criteria.', 'blueworx-hiraste-enhancements' );
		}

		wp_enqueue_style( 'sl-esf-property-count' );

		$html  = '<div class="archive_count_wrap">';
		$html .= '<h3 class="archive_count_h3 elementor-heading-title elementor-size-default">' . esc_html( $count . ' ' . $count_text ) . '</h3>';
		if ( $extra_text ) {
			$html .= '<p class="archive_count_text"><strong>' . esc_html( $extra_text ) . '</strong></p>';
		}
		$html .= '</div>';

		return $html;
	}
}
