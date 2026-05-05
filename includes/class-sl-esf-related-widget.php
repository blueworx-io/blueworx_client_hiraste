<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

class SL_ESF_Related_Widget extends Widget_Base {

	private $rendered_template_css = [];

	public function get_name() { return 'sl_esf_property_related_items'; }
	public function get_title() { return __( 'Property Related Items', 'blueworx-hiraste-enhancements' ); }
	public function get_icon() { return 'eicon-posts-grid'; }
	public function get_categories() { return [ 'general' ]; }
	public function get_style_depends() { return [ 'sl-esf-frontend' ]; }

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Related Items', 'blueworx-hiraste-enhancements' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'related_type',
			[
				'label'   => __( 'Show', 'blueworx-hiraste-enhancements' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'reviews'   => __( 'Reviews', 'blueworx-hiraste-enhancements' ),
					'units'     => __( 'Units', 'blueworx-hiraste-enhancements' ),
					'amenities' => __( 'Amenities', 'blueworx-hiraste-enhancements' ),
				],
				'default' => 'reviews',
			]
		);

		$this->add_control(
			'reviews_template_slug',
			[
				'label'     => __( 'Review Template Slug', 'blueworx-hiraste-enhancements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => 'hiraste-review-block-loop-item',
				'condition' => [ 'related_type' => 'reviews' ],
			]
		);

		$this->add_control(
			'units_template_slug',
			[
				'label'     => __( 'Unit Template Slug', 'blueworx-hiraste-enhancements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => 'hiraste-single-property-units-loop-item',
				'condition' => [ 'related_type' => 'units' ],
			]
		);

		$this->add_control(
			'columns',
			[
				'label'   => __( 'Columns', 'blueworx-hiraste-enhancements' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'1' => __( '1 Column', 'blueworx-hiraste-enhancements' ),
					'2' => __( '2 Columns', 'blueworx-hiraste-enhancements' ),
					'3' => __( '3 Columns', 'blueworx-hiraste-enhancements' ),
				],
				'default' => '3',
			]
		);

		$this->add_control(
			'column_gap',
			[
				'label'   => __( 'Column Spacing', 'blueworx-hiraste-enhancements' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 0,
				'max'     => 200,
				'step'    => 1,
				'default' => 40,
			]
		);

		$this->add_control(
			'row_gap',
			[
				'label'   => __( 'Row Spacing', 'blueworx-hiraste-enhancements' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 0,
				'max'     => 200,
				'step'    => 1,
				'default' => 40,
			]
		);

		$this->add_control(
			'no_results_message',
			[
				'label'   => __( 'Empty Message', 'blueworx-hiraste-enhancements' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'No related items found.', 'blueworx-hiraste-enhancements' ),
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$property_id = $this->get_current_property_id();
		$related_type = $this->sanitize_related_type( $settings['related_type'] ?? 'reviews' );
		$template_slug = '';
		$template_id = 0;
		$columns = $this->sanitize_columns( $settings['columns'] ?? '3' );
		$column_gap = $this->sanitize_pixel_value( $settings['column_gap'] ?? 40, 40 );
		$row_gap = $this->sanitize_pixel_value( $settings['row_gap'] ?? 40, 40 );
		$no_results_message = trim( (string) ( $settings['no_results_message'] ?? '' ) );

		if ( $related_type !== 'amenities' ) {
			$template_slug = $this->get_template_slug_for_type( $related_type, $settings );
			$template_id = $this->get_template_id_by_slug( $template_slug );
		}

		if ( $no_results_message === '' ) {
			$no_results_message = __( 'No related items found.', 'blueworx-hiraste-enhancements' );
		}

		$this->render_open_wrapper( $columns, $column_gap, $row_gap );

		if ( ! $property_id ) {
			$this->render_notice( __( 'No property found for this widget.', 'blueworx-hiraste-enhancements' ) );
			echo '</div>';
			return;
		}

		if ( $related_type !== 'amenities' && ! $template_id ) {
			$this->render_notice(
				sprintf(
					/* translators: %s is an Elementor template slug. */
					__( 'Template not found: %s', 'blueworx-hiraste-enhancements' ),
					$template_slug
				)
			);
			echo '</div>';
			return;
		}

		if ( $related_type === 'amenities' ) {
			$this->render_terms( $property_id, $no_results_message );
		} else {
			$this->render_posts( $property_id, $related_type, $template_id, $no_results_message );
		}

		echo '</div>';
	}

	private function render_posts( $property_id, $related_type, $template_id, $no_results_message ) {
		global $post;

		$field_name = $related_type === 'units' ? 'select_units' : 'select_reviews';
		$field_key = $related_type === 'units' ? 'field_6988dcb9717ab' : 'field_6988466a3d4cc';
		$post_type = $related_type === 'units' ? 'unit' : 'review';
		$post_ids = $this->get_related_post_ids( $property_id, $field_name, $field_key );

		if ( empty( $post_ids ) ) {
			$this->render_notice( $no_results_message );
			return;
		}

		$posts = get_posts([
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'post__in'       => $post_ids,
			'orderby'        => 'post__in',
			'posts_per_page' => -1,
		]);

		if ( empty( $posts ) ) {
			$this->render_notice( $no_results_message );
			return;
		}

		$old_post = $post instanceof WP_Post ? $post : null;

		echo '<div class="sl-esf-results__grid">';
		foreach ( $posts as $related_post ) {
			$post = $related_post;
			setup_postdata( $post );
			echo '<article class="sl-esf-results__item">';
			echo $this->render_loop_item_template( $template_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor returns rendered template HTML.
			echo '</article>';
		}
		echo '</div>';

		wp_reset_postdata();
		if ( $old_post instanceof WP_Post ) {
			$post = $old_post;
			setup_postdata( $post );
		}
	}

	private function render_terms( $property_id, $no_results_message ) {
		$terms = get_the_terms( $property_id, 'amenities' );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			$this->render_notice( $no_results_message );
			return;
		}

		usort( $terms, function( $a, $b ) {
			return strcasecmp( $a->name, $b->name );
		} );

		echo '<div class="sl-esf-results__grid">';
		foreach ( $terms as $term ) {
			echo '<article class="sl-esf-results__item">';
			printf(
				'<div class="sl-esf-results__amenity-card"><span class="sl-esf-results__amenity-name">%s</span></div>',
				esc_html( $term->name )
			);
			echo '</article>';
		}
		echo '</div>';
	}

	private function get_related_post_ids( $property_id, $field_name, $field_key ) {
		$value = get_post_meta( $property_id, $field_name, true );

		if ( empty( $value ) && function_exists( 'get_field' ) ) {
			$value = get_field( $field_name, $property_id, false );
		}

		if ( empty( $value ) && function_exists( 'get_field' ) ) {
			$value = get_field( $field_key, $property_id, false );
		}

		return $this->normalize_post_ids( $value );
	}

	private function normalize_post_ids( $value ) {
		$ids = [];

		if ( empty( $value ) ) {
			return $ids;
		}

		if ( is_string( $value ) ) {
			$value = maybe_unserialize( $value );
		}

		if ( is_numeric( $value ) ) {
			return [ absint( $value ) ];
		}

		if ( $value instanceof WP_Post ) {
			return [ absint( $value->ID ) ];
		}

		if ( is_object( $value ) && isset( $value->ID ) ) {
			return [ absint( $value->ID ) ];
		}

		if ( ! is_array( $value ) ) {
			return $ids;
		}

		foreach ( $value as $item ) {
			if ( is_numeric( $item ) ) {
				$ids[] = absint( $item );
				continue;
			}

			if ( $item instanceof WP_Post ) {
				$ids[] = absint( $item->ID );
				continue;
			}

			if ( is_object( $item ) && isset( $item->ID ) ) {
				$ids[] = absint( $item->ID );
				continue;
			}

			if ( is_array( $item ) && isset( $item['ID'] ) ) {
				$ids[] = absint( $item['ID'] );
			}
		}

		return array_values( array_unique( array_filter( $ids ) ) );
	}

	private function get_current_property_id() {
		$queried_id = absint( get_queried_object_id() );
		if ( $queried_id && get_post_type( $queried_id ) === 'property' ) {
			return $queried_id;
		}

		$current_id = absint( get_the_ID() );
		if ( $current_id && get_post_type( $current_id ) === 'property' ) {
			return $current_id;
		}

		return 0;
	}

	private function get_template_slug_for_type( $related_type, $settings ) {
		$defaults = [
			'reviews'   => 'hiraste-review-block-loop-item',
			'units'     => 'hiraste-single-property-units-loop-item',
		];
		$setting_key = $related_type . '_template_slug';
		$slug = sanitize_title( $settings[ $setting_key ] ?? $defaults[ $related_type ] );

		return $slug !== '' ? $slug : $defaults[ $related_type ];
	}

	private function get_template_id_by_slug( $slug ) {
		$templates = get_posts([
			'name'           => $slug,
			'post_type'      => 'elementor_library',
			'post_status'    => [ 'publish', 'private' ],
			'posts_per_page' => 1,
			'fields'         => 'ids',
		]);

		return ! empty( $templates ) ? absint( $templates[0] ) : 0;
	}

	private function render_loop_item_template( $template_id ) {
		if ( ! class_exists( '\Elementor\Plugin' ) || empty( \Elementor\Plugin::$instance->frontend ) ) {
			return '';
		}

		$with_css = empty( $this->rendered_template_css[ $template_id ] );
		$this->rendered_template_css[ $template_id ] = true;

		return \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id, $with_css );
	}

	private function render_term_template( $template_id, $term ) {
		global $wp_query;

		$old_queried_object = isset( $wp_query->queried_object ) ? $wp_query->queried_object : null;
		$old_queried_object_id = isset( $wp_query->queried_object_id ) ? $wp_query->queried_object_id : null;
		$old_loop_term_exists = isset( $wp_query->loop_term );
		$old_loop_term = $old_loop_term_exists ? $wp_query->loop_term : null;
		$old_global_term_exists = isset( $GLOBALS['term'] );
		$old_global_term = $old_global_term_exists ? $GLOBALS['term'] : null;

		$wp_query->queried_object = $term;
		$wp_query->queried_object_id = absint( $term->term_id );
		$wp_query->loop_term = $term;
		$GLOBALS['term'] = $term;

		$html = $this->render_loop_item_template( $template_id );

		$wp_query->queried_object = $old_queried_object;
		$wp_query->queried_object_id = $old_queried_object_id;
		if ( $old_loop_term_exists ) {
			$wp_query->loop_term = $old_loop_term;
		} else {
			unset( $wp_query->loop_term );
		}

		if ( $old_global_term_exists ) {
			$GLOBALS['term'] = $old_global_term;
		} else {
			unset( $GLOBALS['term'] );
		}

		return $html;
	}

	private function render_open_wrapper( $columns, $column_gap, $row_gap ) {
		printf(
			'<div class="sl-esf-results sl-esf-results--columns-%1$d" style="--sl-esf-results-column-gap:%2$dpx;--sl-esf-results-row-gap:%3$dpx;">',
			absint( $columns ),
			absint( $column_gap ),
			absint( $row_gap )
		);
	}

	private function render_notice( $message ) {
		printf(
			'<div class="sl-esf-results__notice">%s</div>',
			esc_html( $message )
		);
	}

	private function sanitize_related_type( $related_type ) {
		$related_type = (string) $related_type;
		return in_array( $related_type, [ 'reviews', 'units', 'amenities' ], true ) ? $related_type : 'reviews';
	}

	private function sanitize_columns( $columns ) {
		$columns = (string) $columns;
		return in_array( $columns, [ '1', '2', '3' ], true ) ? absint( $columns ) : 3;
	}

	private function sanitize_pixel_value( $value, $default ) {
		if ( $value === '' || ! is_numeric( $value ) ) {
			return $default;
		}

		return max( 0, min( absint( $value ), 200 ) );
	}
}
