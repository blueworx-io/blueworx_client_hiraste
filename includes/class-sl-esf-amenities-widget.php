<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

class SL_ESF_Amenities_Widget extends Widget_Base {

	private $rendered_template_css = [];

	public function get_name() { return 'sl_esf_property_amenities'; }
	public function get_title() { return __( 'Property Amenities', 'blueworx-hiraste-enhancements' ); }
	public function get_icon() { return 'eicon-gallery-grid'; }
	public function get_categories() { return [ 'general' ]; }
	public function get_style_depends() { return [ 'sl-esf-frontend' ]; }

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Amenities', 'blueworx-hiraste-enhancements' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'template_slug',
			[
				'label'   => __( 'Amenity Template Slug', 'blueworx-hiraste-enhancements' ),
				'type'    => Controls_Manager::TEXT,
				'default' => 'hiraste-amenities-loop-item',
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
			'limit',
			[
				'label'       => __( 'Limit', 'blueworx-hiraste-enhancements' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 100,
				'step'        => 1,
				'default'     => 0,
				'description' => __( 'Leave as 0 to show all amenities.', 'blueworx-hiraste-enhancements' ),
			]
		);

		$this->add_control(
			'no_results_message',
			[
				'label'   => __( 'Empty Message', 'blueworx-hiraste-enhancements' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'No amenities found.', 'blueworx-hiraste-enhancements' ),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			[
				'label' => __( 'Style', 'blueworx-hiraste-enhancements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => __( 'Title Colour', 'blueworx-hiraste-enhancements' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .elementor-image-box-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$property_id = $this->get_current_property_id();
		$template_slug = sanitize_title( $settings['template_slug'] ?? 'hiraste-amenities-loop-item' );
		$template_slug = $template_slug !== '' ? $template_slug : 'hiraste-amenities-loop-item';
		$template_id = $this->get_template_id_by_slug( $template_slug );
		$columns_desktop = $this->sanitize_columns( $settings['columns_desktop'] ?? $settings['columns'] ?? '3', 3 );
		$columns_tablet = $this->sanitize_columns( $settings['columns_tablet'] ?? '2', 2 );
		$columns_mobile = $this->sanitize_columns( $settings['columns_mobile'] ?? '1', 1 );
		$column_gap = $this->sanitize_pixel_value( $settings['column_gap'] ?? 40, 40 );
		$row_gap = $this->sanitize_pixel_value( $settings['row_gap'] ?? 40, 40 );
		$limit = $this->sanitize_limit( $settings['limit'] ?? 0 );
		$no_results_message = trim( (string) ( $settings['no_results_message'] ?? '' ) );

		if ( $no_results_message === '' ) {
			$no_results_message = __( 'No amenities found.', 'blueworx-hiraste-enhancements' );
		}

		printf(
			'<div class="sl-esf-results sl-esf-results--amenities sl-esf-results--columns-%1$d" style="--sl-esf-results-columns-desktop:%1$d;--sl-esf-results-columns-tablet:%2$d;--sl-esf-results-columns-mobile:%3$d;--sl-esf-results-column-gap:%4$dpx;--sl-esf-results-row-gap:%5$dpx;">',
			absint( $columns_desktop ),
			absint( $columns_tablet ),
			absint( $columns_mobile ),
			absint( $column_gap ),
			absint( $row_gap )
		);

		if ( ! $property_id ) {
			$this->render_notice( __( 'No property found for this widget.', 'blueworx-hiraste-enhancements' ) );
			echo '</div>';
			return;
		}

		if ( ! $template_id ) {
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

		$terms = get_the_terms( $property_id, 'amenities' );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			$this->render_notice( $no_results_message );
			echo '</div>';
			return;
		}

		usort( $terms, function( $a, $b ) {
			return strcasecmp( $a->name, $b->name );
		} );

		if ( $limit > 0 ) {
			$terms = array_slice( $terms, 0, $limit );
		}

		echo '<div class="sl-esf-results__grid">';
		foreach ( $terms as $term ) {
			echo '<article class="sl-esf-results__item">';
			echo $this->render_amenity_template( $template_id, $term, $property_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor returns rendered template HTML, then the title is replaced with escaped term text.
			echo '</article>';
		}
		echo '</div></div>';
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

	private function render_amenity_template( $template_id, $term, $property_id ) {
		$html = $this->render_loop_item_template( $template_id );
		$property_title = get_the_title( $property_id );
		$image_html = $this->get_amenity_image_html( $term );

		if ( $property_title !== '' ) {
			$html = str_replace(
				[
					esc_html( $property_title ),
					esc_attr( $property_title ),
					$property_title,
				],
				esc_html( $term->name ),
				$html
			);
		}

		$html = $this->add_image_box_wrapper_class( $html );
		$html = $this->replace_image_box_title( $html, $term->name );

		if ( $image_html !== '' ) {
			if ( preg_match( '/<figure([^>]*class="[^"]*elementor-image-box-img[^"]*"[^>]*)>.*?<\/figure>/is', $html ) ) {
				$html = preg_replace_callback(
					'/<figure([^>]*class="[^"]*elementor-image-box-img[^"]*"[^>]*)>.*?<\/figure>/is',
					function( $matches ) use ( $image_html ) {
						return '<figure' . $matches[1] . '>' . $image_html . '</figure>';
					},
					$html,
					1
				);
			} else {
				$html = preg_replace_callback(
					'/<div([^>]*class="[^"]*elementor-image-box-wrapper[^"]*"[^>]*)>/is',
					function( $matches ) use ( $image_html ) {
						return '<div' . $matches[1] . '><figure class="elementor-image-box-img">' . $image_html . '</figure>';
					},
					$html,
					1
				);
			}
		}

		return $html;
	}

	private function replace_image_box_title( $html, $title ) {
		$updated = preg_replace_callback(
			'/(<([a-z0-9]+)\b[^>]*class=(["\'])(?:(?!\3).)*\belementor-image-box-title\b(?:(?!\3).)*\3[^>]*>).*?(<\/\2>)/is',
			function( $matches ) use ( $title ) {
				return $matches[1] . esc_html( $title ) . $matches[4];
			},
			$html,
			1
		);

		return $updated !== null ? $updated : $html;
	}

	private function add_image_box_wrapper_class( $html ) {
		if ( strpos( $html, 'sl-esf-amenity-image-box-wrapper' ) !== false ) {
			return $html;
		}

		return preg_replace(
			'/class="([^"]*\belementor-image-box-wrapper\b[^"]*)"/i',
			'class="$1 sl-esf-amenity-image-box-wrapper"',
			$html,
			1
		);
	}

	private function get_amenity_image_html( $term ) {
		$image = null;
		$acf_targets = [
			'amenities_' . absint( $term->term_id ),
			'term_' . absint( $term->term_id ),
		];

		if ( function_exists( 'get_field' ) ) {
			foreach ( $acf_targets as $target ) {
				$image = get_field( 'featured_image', $target );
				if ( empty( $image ) ) {
					$image = get_field( 'field_697a61b58db97', $target );
				}
				if ( ! empty( $image ) ) {
					break;
				}
			}
		}

		if ( empty( $image ) ) {
			$image = get_term_meta( $term->term_id, 'featured_image', true );
		}

		if ( empty( $image ) ) {
			return '';
		}

		if ( is_array( $image ) ) {
			if ( ! empty( $image['ID'] ) ) {
				return wp_get_attachment_image( absint( $image['ID'] ), 'full', false, [ 'alt' => $term->name ] );
			}

			if ( ! empty( $image['url'] ) ) {
				return sprintf(
					'<img src="%1$s" alt="%2$s" loading="lazy">',
					esc_url( $image['url'] ),
					esc_attr( $term->name )
				);
			}
		}

		if ( is_numeric( $image ) ) {
			return wp_get_attachment_image( absint( $image ), 'full', false, [ 'alt' => $term->name ] );
		}

		if ( is_string( $image ) && filter_var( $image, FILTER_VALIDATE_URL ) ) {
			return sprintf(
				'<img src="%1$s" alt="%2$s" loading="lazy">',
				esc_url( $image ),
				esc_attr( $term->name )
			);
		}

		return '';
	}

	private function render_notice( $message ) {
		printf(
			'<div class="sl-esf-results__notice">%s</div>',
			esc_html( $message )
		);
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

	private function sanitize_limit( $value ) {
		if ( $value === '' || ! is_numeric( $value ) ) {
			return 0;
		}

		return max( 0, min( absint( $value ), 100 ) );
	}
}
