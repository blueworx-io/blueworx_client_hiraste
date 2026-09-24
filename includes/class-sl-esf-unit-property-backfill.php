<?php
/**
 * Unit property backfill.
 *
 * Units have an ACF "unit_property" field linked two-way to the property's
 * "select_units" field. ACF only syncs changes, so links made before the
 * field existed stay one-sided. This runs once and fills unit_property on any
 * unit a property already lists. Units that already have a property are left
 * alone.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class SL_ESF_Unit_Property_Backfill {

	const OPTION    = 'sl_esf_unit_property_backfill_done';
	const FIELD     = 'unit_property';
	const FIELD_KEY = 'field_6ab52dd3f8c6a';

	public function __construct() {
		add_action( 'admin_init', [ $this, 'maybe_run' ] );
	}

	public function maybe_run() {
		if ( get_option( self::OPTION ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$property_ids = get_posts( [
			'post_type'      => 'property',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		] );

		$filled = 0;
		foreach ( $property_ids as $property_id ) {
			foreach ( sl_esf_get_property_unit_ids( $property_id ) as $unit_id ) {
				if ( get_post_type( $unit_id ) !== 'unit' || get_post_meta( $unit_id, self::FIELD, true ) ) {
					continue;
				}

				update_post_meta( $unit_id, self::FIELD, (int) $property_id );
				update_post_meta( $unit_id, '_' . self::FIELD, self::FIELD_KEY );
				$filled++;
			}
		}

		update_option( self::OPTION, [ 'time' => time(), 'filled' => $filled ], false );
	}
}
