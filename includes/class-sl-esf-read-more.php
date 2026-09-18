<?php
/**
 * Read more toggle.
 *
 * Any block with the read_more_text CSS class is trimmed to 400 characters
 * with a "Read more" link. Front-end JS only.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class SL_ESF_Read_More {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public function enqueue_assets() {
		wp_enqueue_script(
			'sl-esf-read-more',
			SL_ESF_PLUGIN_URL . 'assets/sl-esf-read-more.js',
			[],
			SL_ESF_VERSION,
			true
		);
	}
}
