<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

class SL_ESF_Results_Widget extends Widget_Base {

	private $rendered_template_css = [];

	public function get_name() { return 'sl_esf_enhanced_property_results'; }
	public function get_title() { return __( 'Enhanced Property Results', 'blueworx-hiraste-enhancements' ); }
	public function get_icon() { return 'eicon-post-list'; }
	public function get_categories() { return [ 'general' ]; }
	public function get_style_depends() { return [ 'sl-esf-frontend' ]; }

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Results', 'blueworx-hiraste-enhancements' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'template_slug',
			[
				'label'       => __( 'Loop Item Template Slug', 'blueworx-hiraste-enhancements' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'hiraste-archive-results-loop-item',
				'description' => __( 'The Elementor Loop Item used for each property.', 'blueworx-hiraste-enhancements' ),
			]
		);

		$this->add_control(
			'posts_per_page',
			[
				'label'   => __( 'Items Per Page', 'blueworx-hiraste-enhancements' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 48,
				'step'    => 1,
				'default' => 12,
			]
		);

		$this->add_control(
			'columns_desktop',
			[
				'label'   => __( 'Desktop Columns', 'blueworx-hiraste-enhancements' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'1' => __( '1 Column', 'blueworx-hiraste-enhancements' ),
					'2' => __( '2 Columns', 'blueworx-hiraste-enhancements' ),
					'3' => __( '3 Columns', 'blueworx-hiraste-enhancements' ),
					'4' => __( '4 Columns', 'blueworx-hiraste-enhancements' ),
				],
				'default' => '3',
			]
		);

		$this->add_control(
			'columns_tablet',
			[
				'label'   => __( 'Tablet Columns', 'blueworx-hiraste-enhancements' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'1' => __( '1 Column', 'blueworx-hiraste-enhancements' ),
					'2' => __( '2 Columns', 'blueworx-hiraste-enhancements' ),
					'3' => __( '3 Columns', 'blueworx-hiraste-enhancements' ),
					'4' => __( '4 Columns', 'blueworx-hiraste-enhancements' ),
				],
				'default' => '2',
			]
		);

		$this->add_control(
			'columns_mobile',
			[
				'label'   => __( 'Mobile Columns', 'blueworx-hiraste-enhancements' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'1' => __( '1 Column', 'blueworx-hiraste-enhancements' ),
					'2' => __( '2 Columns', 'blueworx-hiraste-enhancements' ),
					'3' => __( '3 Columns', 'blueworx-hiraste-enhancements' ),
					'4' => __( '4 Columns', 'blueworx-hiraste-enhancements' ),
				],
				'default' => '1',
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
			'order',
			[
				'label'       => __( 'Order', 'blueworx-hiraste-enhancements' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => [
					'shuffle' => __( 'Shuffled', 'blueworx-hiraste-enhancements' ),
					'newest'  => __( 'Newest First', 'blueworx-hiraste-enhancements' ),
				],
				'default'     => 'shuffle',
				'description' => __( 'Shuffled gives each search its own order, kept the same across pages.', 'blueworx-hiraste-enhancements' ),
			]
		);

		$this->add_control(
			'no_results_message',
			[
				'label'   => __( 'No Results Message', 'blueworx-hiraste-enhancements' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'No properties found.', 'blueworx-hiraste-enhancements' ),
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$template_slug = sanitize_title( $settings['template_slug'] ?? 'hiraste-archive-results-loop-item' );
		if ( $template_slug === '' ) {
			$template_slug = 'hiraste-archive-results-loop-item';
		}

		$template_id = $this->get_template_id_by_slug( $template_slug );
		$columns_desktop = $this->sanitize_columns( $settings['columns_desktop'] ?? $settings['columns'] ?? '3', 3 );
		$columns_tablet = $this->sanitize_columns( $settings['columns_tablet'] ?? '2', 2 );
		$columns_mobile = $this->sanitize_columns( $settings['columns_mobile'] ?? '1', 1 );
		$column_gap = $this->sanitize_pixel_value( $settings['column_gap'] ?? 40, 40 );
		$row_gap = $this->sanitize_pixel_value( $settings['row_gap'] ?? 40, 40 );
		$posts_per_page = max( 1, min( absint( $settings['posts_per_page'] ?? 12 ), 48 ) );
		$no_results_message = trim( (string) ( $settings['no_results_message'] ?? '' ) );

		if ( $no_results_message === '' ) {
			$no_results_message = __( 'No properties found.', 'blueworx-hiraste-enhancements' );
		}

		$this->render_open_wrapper( $columns_desktop, $columns_tablet, $columns_mobile, $column_gap, $row_gap );

		if ( ! $template_id ) {
			$this->render_notice(
				sprintf(
					/* translators: %s is an Elementor template slug. */
					__( 'Loop Item template not found: %s', 'blueworx-hiraste-enhancements' ),
					$template_slug
				)
			);
			echo '</div>';
			return;
		}

		$query_args = $this->get_query_args( $posts_per_page );
		if ( ( $settings['order'] ?? 'shuffle' ) === 'shuffle' && function_exists( 'sl_esf_get_shuffle_seed' ) ) {
			$query_args['orderby'] = 'RAND(' . sl_esf_get_shuffle_seed() . ')';
		}

		$query = new WP_Query( $query_args );

		if ( ! $query->have_posts() ) {
			$this->render_notice( $no_results_message );
			wp_reset_postdata();
			echo '</div>';
			return;
		}

		echo '<div class="sl-esf-results__grid">';
		while ( $query->have_posts() ) {
			$query->the_post();
			echo '<article class="sl-esf-results__item">';
			echo $this->render_loop_item_template( $template_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor returns rendered template HTML.
			if ( class_exists( 'SL_ESF_Property_Map' ) ) {
				echo SL_ESF_Property_Map::render( get_the_ID(), true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while building.
			}
			echo '</article>';
		}
		echo '</div>';

		$this->render_pagination( $query );

		wp_reset_postdata();
		echo '</div>';
	}

	private function render_open_wrapper( $columns_desktop, $columns_tablet, $columns_mobile, $column_gap, $row_gap ) {
		printf(
			'<div class="sl-esf-results sl-esf-results--columns-%1$d" style="--sl-esf-results-columns-desktop:%1$d;--sl-esf-results-columns-tablet:%2$d;--sl-esf-results-columns-mobile:%3$d;--sl-esf-results-column-gap:%4$dpx;--sl-esf-results-row-gap:%5$dpx;">',
			absint( $columns_desktop ),
			absint( $columns_tablet ),
			absint( $columns_mobile ),
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

	private function get_query_args( $posts_per_page ) {
		$search_request = function_exists( 'blueworx_hiraste_enhancements_get_search_request' )
			? blueworx_hiraste_enhancements_get_search_request()
			: [];

		$args = [
			'post_type'           => 'property',
			'post_status'         => 'publish',
			'posts_per_page'      => $posts_per_page,
			'paged'               => $this->get_current_page(),
			'ignore_sticky_posts' => true,
		];

		$search_text = function_exists( 'sl_esf_get_search_text' ) ? sl_esf_get_search_text() : '';
		if ( $search_text !== '' ) {
			$text_ids = sl_esf_find_text_matching_property_ids( $search_text );
			$args['post__in'] = ! empty( $text_ids ) ? $text_ids : [ 0 ];
		}

		$tax_query = $this->get_tax_query( $search_request );
		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query;
		}

		$requirements = function_exists( 'sl_esf_parse_unit_requirements' )
			? sl_esf_parse_unit_requirements( $search_request['unit_boxes'] ?? '' )
			: [];
		$min_units = absint( $search_request['units'] ?? 0 );
		$guests = absint( $search_request['guests'] ?? 0 );

		if ( ! empty( $requirements ) || $min_units || $guests ) {
			$matching_ids = function_exists( 'sl_esf_find_matching_property_ids' )
				? sl_esf_find_matching_property_ids( $requirements, $min_units, $guests )
				: [];

			if ( isset( $args['post__in'] ) ) {
				$matching_ids = array_values( array_intersect( $args['post__in'], $matching_ids ) );
			}

			$args['post__in'] = ! empty( $matching_ids ) ? $matching_ids : [ 0 ];
		}

		return $args;
	}

	private function get_tax_query( $search_request ) {
		$current_tax_clause = $this->get_current_tax_clause();
		$request_tax_query = $this->get_request_tax_query( $search_request );

		if ( ! empty( $current_tax_clause ) && ! empty( $request_tax_query ) ) {
			return [
				'relation' => 'AND',
				$current_tax_clause,
				$request_tax_query,
			];
		}

		if ( ! empty( $current_tax_clause ) ) {
			return [ $current_tax_clause ];
		}

		return $request_tax_query;
	}

	private function get_current_tax_clause() {
		$supported_taxonomies = [ 'destinations', 'collections', 'amenities' ];

		if ( ! is_tax( $supported_taxonomies ) ) {
			return [];
		}

		$term = get_queried_object();
		if ( ! $term || empty( $term->taxonomy ) || empty( $term->term_id ) ) {
			return [];
		}

		if ( ! in_array( $term->taxonomy, $supported_taxonomies, true ) ) {
			return [];
		}

		return [
			'taxonomy' => $term->taxonomy,
			'field'    => 'term_id',
			'terms'    => [ absint( $term->term_id ) ],
			'operator' => 'IN',
		];
	}

	private function get_request_tax_query( $search_request ) {
		if ( function_exists( 'blueworx_hiraste_enhancements_build_tax_query' ) ) {
			return blueworx_hiraste_enhancements_build_tax_query( $search_request );
		}

		$tax_clauses = [];
		$grouped_terms = [
			'destinations' => [],
			'collections'  => [],
			'amenities'    => [],
		];

		foreach ( $grouped_terms as $taxonomy => $terms ) {
			if ( ! empty( $search_request[ $taxonomy ] ) ) {
				$grouped_terms[ $taxonomy ] = array_merge(
					$grouped_terms[ $taxonomy ],
					array_map( 'absint', (array) $search_request[ $taxonomy ] )
				);
			}
		}

		$destination_term = absint( $search_request['destination_term'] ?? 0 );
		if ( $destination_term ) {
			$grouped_terms['destinations'][] = $destination_term;
		}

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

		if ( empty( $tax_clauses ) ) {
			return [];
		}

		if ( count( $tax_clauses ) < 2 ) {
			return $tax_clauses;
		}

		return array_merge( [ 'relation' => 'AND' ], $tax_clauses );
	}

	private function render_loop_item_template( $template_id ) {
		if ( ! class_exists( '\Elementor\Plugin' ) || empty( \Elementor\Plugin::$instance->frontend ) ) {
			return '';
		}

		$with_css = empty( $this->rendered_template_css[ $template_id ] );
		$this->rendered_template_css[ $template_id ] = true;

		return \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id, $with_css );
	}

	private function render_pagination( $query ) {
		if ( $query->max_num_pages < 2 ) {
			return;
		}

		$links = paginate_links([
			// Unescaped here: paginate_links() escapes each link itself, and an already-escaped
			// base turns "&" into "&#038;", which breaks the added query args into a fragment.
			'base'      => str_replace( 999999999, '%#%', get_pagenum_link( 999999999, false ) ),
			'format'    => '',
			'current'   => $this->get_current_page(),
			'total'     => $query->max_num_pages,
			'type'      => 'list',
			'prev_text' => __( 'Previous', 'blueworx-hiraste-enhancements' ),
			'next_text' => __( 'Next', 'blueworx-hiraste-enhancements' ),
			'add_args'  => $this->get_pagination_add_args(),
		]);

		if ( $links ) {
			echo '<nav class="sl-esf-results__pagination" aria-label="' . esc_attr__( 'Property results pagination', 'blueworx-hiraste-enhancements' ) . '">';
			echo wp_kses_post( $links );
			echo '</nav>';
		}
	}

	private function get_current_page() {
		$paged = absint( get_query_var( 'paged' ) );
		if ( ! $paged ) {
			$paged = absint( get_query_var( 'page' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public pagination query.
		if ( ! $paged && isset( $_GET['paged'] ) && ! is_array( $_GET['paged'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public pagination query.
			$paged = absint( wp_unslash( $_GET['paged'] ) );
		}

		return max( 1, $paged );
	}

	private function get_pagination_add_args() {
		$args = [];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public pagination query.
		foreach ( $_GET as $key => $value ) {
			$key = sanitize_key( wp_unslash( $key ) );
			if ( $key === '' || in_array( $key, [ 'paged', 'page' ], true ) ) {
				continue;
			}

			if ( is_array( $value ) ) {
				$args[ $key ] = array_map( 'sanitize_text_field', wp_unslash( $value ) );
				continue;
			}

			$args[ $key ] = sanitize_text_field( wp_unslash( $value ) );
		}

		return $args;
	}

	private function sanitize_columns( $columns, $default = 3 ) {
		$columns = (string) $columns;
		return in_array( $columns, [ '1', '2', '3', '4' ], true ) ? absint( $columns ) : $default;
	}

	private function sanitize_pixel_value( $value, $default ) {
		if ( $value === '' || ! is_numeric( $value ) ) {
			return $default;
		}

		return max( 0, min( absint( $value ), 200 ) );
	}
}
