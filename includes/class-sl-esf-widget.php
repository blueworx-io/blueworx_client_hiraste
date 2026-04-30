<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class SL_ESF_Widget extends Widget_Base {

	public function get_name() { return 'sl_esf_search_form'; }
	public function get_title() { return __( 'Search Form', 'sl-elementor-search-form' ); }
	public function get_icon() { return 'eicon-search'; }
	public function get_categories() { return [ 'general' ]; }
	public function get_script_depends() { return [ 'sl-esf-frontend' ]; }
	public function get_style_depends() { return [ 'sl-esf-frontend' ]; }

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[ 'label' => __( 'Content', 'sl-elementor-search-form' ), 'tab' => Controls_Manager::TAB_CONTENT ]
		);

		$this->add_control(
			'placeholder_destination',
			[ 'label' => __( 'Destination Placeholder', 'sl-elementor-search-form' ), 'type' => Controls_Manager::TEXT,
			  'default' => __( 'Where would you like to go?', 'sl-elementor-search-form' ) ]
		);

		$this->add_control(
			'search_button_label',
			[ 'label' => __( 'Button Label', 'sl-elementor-search-form' ), 'type' => Controls_Manager::TEXT,
			  'default' => __( 'SEARCH', 'sl-elementor-search-form' ) ]
		);

		$this->add_control(
			'max_units',
			[ 'label' => __( 'Max Units', 'sl-elementor-search-form' ), 'type' => Controls_Manager::NUMBER,
			  'min' => 1, 'max' => 20, 'step' => 1, 'default' => 10 ]
		);

		$this->add_control(
			'default_units',
			[ 'label' => __( 'Default Units', 'sl-elementor-search-form' ), 'type' => Controls_Manager::NUMBER,
			  'min' => 1, 'max' => 10, 'step' => 1, 'default' => 2 ]
		);

		$this->add_control(
			'default_guests',
			[ 'label' => __( 'Default Guests', 'sl-elementor-search-form' ), 'type' => Controls_Manager::NUMBER,
			  'min' => 1, 'max' => 50, 'step' => 1, 'default' => 4 ]
		);

		$this->add_control(
			'advanced_label',
			[ 'label' => __( 'Advanced Toggle Label', 'sl-elementor-search-form' ), 'type' => Controls_Manager::TEXT,
			  'default' => __( 'Advanced Search', 'sl-elementor-search-form' ) ]
		);

		$this->add_control(
			'form_action',
			[ 'label' => __( 'Form Action', 'sl-elementor-search-form' ), 'type' => Controls_Manager::TEXT,
			  'description' => __( 'Typically leave as "/" to use WordPress search results.', 'sl-elementor-search-form' ),
			  'default' => '/' ]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$uid = 'sl-esf-' . $this->get_id();
		$action = trim( (string) $settings['form_action'] );

		$max_units = (int) $settings['max_units'];
		$default_units = max( 1, min( (int) $settings['default_units'], $max_units ) );
		$default_guests = max( 1, min( (int) $settings['default_guests'], 50 ) );

		$dest_terms = get_terms([
			'taxonomy' => 'destinations',
			'hide_empty' => false,
		]);
		if ( is_wp_error( $dest_terms ) ) $dest_terms = [];

		?>
		<div class="sl-esf" id="<?php echo esc_attr( $uid ); ?>"
			 data-max-units="<?php echo esc_attr( $max_units ); ?>"
			 data-default-units="<?php echo esc_attr( $default_units ); ?>"
			 data-default-guests="<?php echo esc_attr( $default_guests ); ?>">

			<form class="sl-esf__form" method="GET" action="<?php echo esc_url( $action ?: '/' ); ?>">
				<!-- Required by WP core search -->
				<input type="hidden" name="post_type" value="property" />

				<div class="sl-esf__basic">
					<div class="sl-esf__field sl-esf__field--select sl-esf__field--destination">
						<label class="sl-esf__label" for="<?php echo esc_attr( $uid ); ?>-destination-term">Destination</label>
						<select id="<?php echo esc_attr( $uid ); ?>-destination-term" class="sl-esf__select" name="destination_term">
							<option value="">All destinations</option>
							<?php foreach ( $dest_terms as $t ) : ?>
								<option value="<?php echo esc_attr( $t->term_id ); ?>"><?php echo esc_html( $t->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="sl-esf__field sl-esf__field--select">
						<label class="sl-esf__label" for="<?php echo esc_attr( $uid ); ?>-guests">Guests</label>
						<select id="<?php echo esc_attr( $uid ); ?>-guests" class="sl-esf__select" name="guests">
							<option value="">Any Guests</option>
							<?php for ( $i=1; $i<=50; $i++ ) : ?>
								<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $i, $default_guests ); ?>>
									<?php echo esc_html( $i . ( $i === 1 ? ' Guest' : ' Guests' ) ); ?>
								</option>
							<?php endfor; ?>
						</select>
					</div>

					<div class="sl-esf__field sl-esf__field--select">
						<label class="sl-esf__label" for="<?php echo esc_attr( $uid ); ?>-units">Units</label>
						<select id="<?php echo esc_attr( $uid ); ?>-units" class="sl-esf__select" name="units">
							<option value="">Any Units</option>
							<?php for ( $i=1; $i<=20; $i++ ) : ?>
								<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $i, $default_units ); ?>>
									<?php echo esc_html( $i . ( $i === 1 ? ' Unit' : ' Units' ) ); ?>
								</option>
							<?php endfor; ?>
						</select>
					</div>

					<div class="sl-esf__field sl-esf__field--submit">
						<button class="sl-esf__button" type="submit" name="submit">
							<?php echo esc_html( $settings['search_button_label'] ); ?>
						</button>
						<button class="sl-esf__button" type="button" data-sl-esf-clear>Clear</button>
					</div>
				</div>

				<div class="sl-esf__advanced">
					<button type="button" class="sl-esf__advanced-toggle" aria-expanded="false">
						<span class="sl-esf__advanced-title"><?php echo esc_html( $settings['advanced_label'] ); ?></span>
						<span class="sl-esf__advanced-meta" data-sl-esf-advanced-meta>0 filters set</span>
					</button>

					<div class="sl-esf__advanced-panel" hidden>
						<div class="sl-esf__advanced-section">
							<div class="sl-esf__section-header">
								<h3 class="sl-esf__section-title">Units</h3>
								<button type="button" class="sl-esf__link" data-sl-esf-add-unit>+ Add unit</button>
							</div>

							<div class="sl-esf__units" data-sl-esf-units></div>
							<input type="hidden" id="<?php echo esc_attr( $uid ); ?>-unit-boxes" name="unit_boxes" data-sl-esf-unit-boxes value="" />
						</div>

						<div class="sl-esf__advanced-section">
							<h3 class="sl-esf__section-title">Destinations</h3>
							<div class="sl-esf__pill-grid">
								<?php foreach ( $dest_terms as $t ) : ?>
									<label class="sl-esf__check">
										<input type="checkbox" name="destinations[]" value="<?php echo esc_attr( $t->term_id ); ?>">
										<?php echo esc_html( $t->name ); ?>
									</label>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="sl-esf__advanced-section">
							<h3 class="sl-esf__section-title">Collections</h3>
							<div class="sl-esf__pill-grid">
								<?php
									$col_terms = get_terms([ 'taxonomy' => 'collections', 'hide_empty' => false ]);
									if ( ! is_wp_error( $col_terms ) ) :
										foreach ( $col_terms as $t ) :
								?>
									<label class="sl-esf__check">
										<input type="checkbox" name="collections[]" value="<?php echo esc_attr( $t->term_id ); ?>">
										<?php echo esc_html( $t->name ); ?>
									</label>
								<?php
										endforeach;
									endif;
								?>
							</div>
						</div>

						<div class="sl-esf__advanced-section">
							<h3 class="sl-esf__section-title">Amenities</h3>
							<div class="sl-esf__pill-grid">
								<?php
									$am_terms = get_terms([ 'taxonomy' => 'amenities', 'hide_empty' => false ]);
									if ( ! is_wp_error( $am_terms ) ) :
										foreach ( $am_terms as $t ) :
								?>
									<label class="sl-esf__check">
										<input type="checkbox" name="amenities[]" value="<?php echo esc_attr( $t->term_id ); ?>">
										<?php echo esc_html( $t->name ); ?>
									</label>
								<?php
										endforeach;
									endif;
								?>
							</div>
						</div>

					</div>
				</div>

				<!-- WP search needs 's' to be a search request. We keep it empty so Elementor still treats it as search. -->
				<input type="hidden" name="s" value="" />
			</form>

			<template data-sl-esf-unit-template>
				<div class="sl-esf__unit" data-sl-esf-unit>
					<div class="sl-esf__unit-head">
						<div class="sl-esf__unit-title">Unit <span data-sl-esf-unit-number></span></div>
						<button type="button" class="sl-esf__icon-btn" aria-label="Remove unit" data-sl-esf-remove-unit>×</button>
					</div>
					<div class="sl-esf__unit-body">
						<div class="sl-esf__counter">
							<div class="sl-esf__counter-label">Sleeps</div>
							<div class="sl-esf__counter-ui" data-sl-esf-counter="sleeps">
								<button type="button" class="sl-esf__counter-btn" data-sl-esf-minus>-</button>
								<div class="sl-esf__counter-value" data-sl-esf-value data-min="1" data-max="20">2</div>
								<button type="button" class="sl-esf__counter-btn" data-sl-esf-plus>+</button>
							</div>
						</div>
						<div class="sl-esf__counter">
							<div class="sl-esf__counter-label">Bedrooms</div>
							<div class="sl-esf__counter-ui" data-sl-esf-counter="bedrooms">
								<button type="button" class="sl-esf__counter-btn" data-sl-esf-minus>-</button>
								<div class="sl-esf__counter-value" data-sl-esf-value data-min="0" data-max="20">1</div>
								<button type="button" class="sl-esf__counter-btn" data-sl-esf-plus>+</button>
							</div>
						</div>
					</div>
				</div>
			</template>

		</div>
		<?php
	}
}
