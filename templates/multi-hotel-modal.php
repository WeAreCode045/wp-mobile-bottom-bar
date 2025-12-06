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

// Enqueue easepick assets for the date picker
$plugin_url = plugin_dir_url(dirname(__FILE__));

// Enqueue easepick CSS from local vendor directory
wp_enqueue_style(
    'wp-mbb-easepick',
    $plugin_url . 'public/vendor/easepick/easepick.css',
    [],
    '1.2.1'
);

// Enqueue easepick dependencies from CDN
wp_enqueue_script(
    'easepick-datetime',
    'https://cdn.jsdelivr.net/npm/@easepick/datetime@1.2.1/dist/index.umd.js',
    [],
    '1.2.1',
    true
);

wp_enqueue_script(
    'easepick-base-plugin',
    'https://cdn.jsdelivr.net/npm/@easepick/base-plugin@1.2.1/dist/index.umd.js',
    ['easepick-datetime'],
    '1.2.1',
    true
);

// Enqueue easepick core from local vendor directory
wp_enqueue_script(
    'wp-mbb-easepick-core',
    $plugin_url . 'public/vendor/easepick/easepick.js',
    ['easepick-datetime', 'easepick-base-plugin'],
    '1.2.1',
    true
);

// Enqueue easepick range plugin from local vendor directory
wp_enqueue_script(
    'wp-mbb-easepick-range',
    $plugin_url . 'public/vendor/easepick/easepick-range.js',
    ['wp-mbb-easepick-core'],
    '1.2.1',
    true
);
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
