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
// Get the plugin root directory (two levels up from templates/)
$plugin_root = dirname(dirname(__FILE__));
$plugin_url = plugin_dir_url($plugin_root);

// Enqueue easepick CSS from local vendor directory
wp_enqueue_style(
    'wp-mbb-easepick',
    $plugin_url . 'public/vendor/easepick/easepick.css',
    [],
    '1.2.1'
);

// Enqueue easepick dependencies from CDN (UMD bundles work best from CDN)
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
						<input type="text" id="wp-mbb-date-display" class="wp-mbb-hotel-selector__range-input" placeholder="<?php esc_attr_e('Select dates', 'mobile-bottom-bar'); ?>" readonly style="display: none;" />
						<div class="wp-mbb-hotel-selector__calendar"></div>
						<input type="hidden" id="wp-mbb-arrival" />
						<input type="hidden" id="wp-mbb-departure" />
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

<script>
// Make plugin URL available to inline script
window.wpMbbPluginUrl = '<?php echo esc_url($plugin_url); ?>';

(function() {
    'use strict';

    // Helper: format date as YYYY-MM-DD
    function formatDateYYYYMMDD(date) {
        if (!date) return '';
        if (typeof date.format === 'function') {
            return date.format('YYYY-MM-DD');
        }
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    const modal = document.getElementById('wp-mbb-multi-hotel-modal');
    const triggerInput = document.getElementById('wp-mbb-date-display');
    const calendarContainer = modal.querySelector('.wp-mbb-hotel-selector__calendar');
    const arrivalHidden = document.getElementById('wp-mbb-arrival');
    const departureHidden = document.getElementById('wp-mbb-departure');
    let pickerInstance = null;

    // Create hidden trigger input for easepick (prevents stray text nodes in calendar)
    function createTriggerInput() {
        if (!calendarContainer) return null;
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.className = 'wp-mbb-picker-trigger-input';
        calendarContainer.appendChild(hidden);
        return hidden;
    }

    // Initialize easepick picker with RangePlugin and LockPlugin (same as MLB)
    function initPicker() {
        let attempts = 0;

        function tryInitPicker() {
            attempts++;
            
            // Check if easepick is loaded
            if (typeof window.easepick === 'undefined') {
                console.log(`[WP-MBB] Attempt ${attempts}: window.easepick not found`);
                if (attempts > 50) {
                    console.error('[WP-MBB] EasePick failed to load after 50 attempts');
                    console.log('[WP-MBB] Available globals:', Object.keys(window).filter(k => k.includes('easepick') || k.includes('Easepick')));
                    return;
                }
                setTimeout(tryInitPicker, 100);
                return;
            }
            
            const easepickRef = window.easepick;
            console.log('[WP-MBB] EasePick found, checking properties:', Object.keys(easepickRef));

            if (!easepickRef || !easepickRef.Core) {
                if (attempts > 50) {
                    console.error('[WP-MBB] EasePick.Core not found after 50 attempts');
                    return;
                }
                setTimeout(tryInitPicker, 100);
                return;
            }

            const CoreClass = easepickRef.Core || easepickRef.create;
            if (!CoreClass) {
                console.error('[WP-MBB] CoreClass not found');
                return;
            }

            try {
                // Create hidden trigger input
                const triggerEl = createTriggerInput();
                const pickerElement = triggerEl || triggerInput || calendarContainer;

                // Configuration matching mylighthouse-booker
                const pickerConfig = {
                    element: pickerElement,
                    inline: true,
                    css: [
                        window.wpMbbPluginUrl + 'public/vendor/easepick/easepick.css'
                    ],
                    plugins: [easepickRef.RangePlugin, easepickRef.LockPlugin],
                    RangePlugin: {
                        tooltip: true,
                        locale: {
                            one: 'night',
                            other: 'nights'
                        }
                    },
                    LockPlugin: {
                        minDate: new Date()
                    },
                    setup(picker) {
                        console.log('[WP-MBB] Picker setup called');

                        // Remove header for cleaner modal display
                        setTimeout(function() {
                            const headerEl = calendarContainer.querySelector('.header');
                            if (headerEl) headerEl.remove();
                        }, 0);

                        picker.on('select', (e) => {
                            const { start, end } = e.detail;
                            if (!start || !end) return;

                            const arrival = formatDateYYYYMMDD(start);
                            const departure = formatDateYYYYMMDD(end);

                            // Update hidden inputs
                            if (arrivalHidden) arrivalHidden.value = arrival;
                            if (departureHidden) departureHidden.value = departure;

                            // Update display input
                            if (triggerInput) {
                                triggerInput.value = `${arrival} → ${departure}`;
                            }

                            console.log('[WP-MBB] Dates selected:', arrival, departure);
                        });
                    }
                };

                pickerInstance = new CoreClass(pickerConfig);
                console.log('[WP-MBB] Picker initialized successfully');

            } catch (err) {
                console.error('[WP-MBB] Failed to init picker:', err);
            }
        }

        tryInitPicker();
    }

    // Reset picker and fields
    function resetPicker() {
        if (pickerInstance) {
            try {
                if (typeof pickerInstance.clear === 'function') {
                    pickerInstance.clear();
                } else if (typeof pickerInstance.clearSelection === 'function') {
                    pickerInstance.clearSelection();
                } else if (typeof pickerInstance.destroy === 'function') {
                    pickerInstance.destroy();
                    pickerInstance = null;
                    initPicker(); // Reinitialize after destroy
                }
            } catch (e) {
                console.warn('[WP-MBB] Error clearing picker:', e);
            }
        }

        // Clear all date fields
        if (triggerInput) triggerInput.value = '';
        if (arrivalHidden) arrivalHidden.value = '';
        if (departureHidden) departureHidden.value = '';
    }

    // Wait for easepick to load, then init picker
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPicker);
    } else {
        initPicker();
    }

    // Listen for reset from frontend.js
    document.addEventListener('wp-mbb-reset-easepick', resetPicker);

    // Expose for debugging/external use
    window.wpMbbPicker = pickerInstance;
    window.wpMbbResetPicker = resetPicker;
})();
</script>