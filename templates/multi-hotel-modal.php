<?php
/**
 * Template for Multi-Hotel Selection Modal
 * 
 * This template is rendered in wp_footer and hidden by default.
 * JavaScript will populate it with hotel data and show it when needed.
 *
 * @package Mobile_Bottom_Bar
 */

defined('ABSPATH') || exit;
?>

<div id="wp-mbb-multi-hotel-modal" class="wp-mbb-modal-overlay" aria-hidden="true" style="display: none;">
	<div class="wp-mbb-modal-container">
		<button type="button" class="wp-mbb-modal-close" aria-label="<?php esc_attr_e('Close', 'mobile-bottom-bar'); ?>">
			<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none">
				<line x1="18" y1="6" x2="6" y2="18"></line>
				<line x1="6" y1="6" x2="18" y2="18"></line>
			</svg>
		</button>

		<div class="wp-mbb-modal-header">
			<h2 class="wp-mbb-modal-title"><?php esc_html_e('Book Your Stay', 'mobile-bottom-bar'); ?></h2>
		</div>

		<div class="wp-mbb-modal-body">
			<div class="wp-mbb-hotel-selector">
				
				<!-- Hotel Selection -->
				<div class="wp-mbb-hotel-selector__field">
					<label for="wp-mbb-hotel-select" class="wp-mbb-hotel-selector__label">
						<?php esc_html_e('Select Hotel', 'mobile-bottom-bar'); ?>
					</label>
					<select id="wp-mbb-hotel-select" class="wp-mbb-hotel-selector__select">
						<!-- Options populated by JavaScript -->
					</select>
				</div>

				<!-- Date Selection -->
				<div class="wp-mbb-hotel-selector__field">
					<label class="wp-mbb-hotel-selector__label">
						<?php esc_html_e('Select Dates', 'mobile-bottom-bar'); ?>
					</label>
					<div class="wp-mbb-hotel-selector__dates">
						<div class="wp-mbb-hotel-selector__calendar">
							<!-- Calendar initialized by JavaScript -->
						</div>
					</div>
				</div>

				<!-- CTA Button -->
				<button type="button" class="wp-mbb-hotel-selector__cta wp-mbb-hotel-list__button">
					<?php esc_html_e('Check Availability', 'mobile-bottom-bar'); ?>
				</button>

			</div>
		</div>
	</div>
</div>
