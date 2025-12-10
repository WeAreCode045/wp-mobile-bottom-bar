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
// Get the plugin directory URL correctly by using the main plugin file
$plugin_file = dirname(dirname(__FILE__)) . '/wp-mobile-bottom-bar.php';
$plugin_url = plugin_dir_url($plugin_file);

// Force use of local easepick CSS with our custom modifications
// Check if mylighthouse-booker has already registered easepick, if so use that, otherwise use ours
if (!wp_style_is('easepick', 'registered')) {
    // Register with handle 'easepick' to ensure compatibility
    $easepick_css_path = dirname(dirname(__FILE__)) . '/public/vendor/easepick/easepick.css';
    $easepick_css_ver = (file_exists($easepick_css_path)) ? filemtime($easepick_css_path) : '1.2.1';
    wp_register_style(
        'easepick',
        $plugin_url . 'public/vendor/easepick/easepick.css',
        [],
        $easepick_css_ver
    );
} else {
    // If already registered, check if it's from CDN and deregister
    global $wp_styles;
    if (isset($wp_styles->registered['easepick'])) {
        $registered_src = $wp_styles->registered['easepick']->src;
        // If it's from CDN, deregister and use our local version
        if (strpos($registered_src, 'cdn.') !== false) {
            wp_deregister_style('easepick');
            $easepick_css_path = dirname(dirname(__FILE__)) . '/public/vendor/easepick/easepick.css';
            $easepick_css_ver = (file_exists($easepick_css_path)) ? filemtime($easepick_css_path) : '1.2.1';
            wp_register_style(
                'easepick',
                $plugin_url . 'public/vendor/easepick/easepick.css',
                [],
                $easepick_css_ver
            );
        }
    }
}

// Enqueue the easepick CSS
if (!wp_style_is('easepick', 'enqueued')) {
    wp_enqueue_style('easepick');
}

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

<div id="wp-mbb-multi-hotel-modal" class="wp-mbb-modal-overlay" aria-hidden="true" style="display: none;" data-single-hotel="">
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
				
				<!-- Hotel Selection (hidden for single hotel mode) -->
				<div id="wp-mbb-hotel-field" class="wp-mbb-hotel-selector__field">
					<label for="wp-mbb-hotel-select" class="wp-mbb-hotel-selector__label">
						<?php esc_html_e('Choose your preferred hotel', 'mobile-bottom-bar'); ?>
					</label>
					<select id="wp-mbb-hotel-select" class="wp-mbb-hotel-selector__select">
						<!-- Options populated by JavaScript -->
					</select>
				</div>

				<!-- Date Selection -->
				<div class="wp-mbb-hotel-selector__field">
					<label class="wp-mbb-hotel-selector__label">
						<?php esc_html_e('Arrival and Departure Date', 'mobile-bottom-bar'); ?>
					</label>
					<div class="wp-mbb-hotel-selector__dates">
						<input type="text" id="wp-mbb-date-display" class="wp-mbb-hotel-selector__range-input" placeholder="<?php esc_attr_e('Select dates', 'mobile-bottom-bar'); ?>" readonly style="display: none;" />
						<div class="wp-mbb-hotel-selector__calendar"></div>
						<input type="hidden" id="wp-mbb-arrival" />
						<input type="hidden" id="wp-mbb-departure" />
					</div>
				</div>

				<!-- Selection Summary -->
				<div id="wp-mbb-selection-summary" class="wp-mbb-selection-summary" style="display: none;">
					<div class="wp-mbb-selection-summary__content">
						<div class="wp-mbb-selection-summary__item">
							<span class="wp-mbb-selection-summary__label">Hotel:</span>
							<span id="wp-mbb-summary-hotel" class="wp-mbb-selection-summary__value">-</span>
						</div>
						<div class="wp-mbb-selection-summary__item">
							<span class="wp-mbb-selection-summary__label">Arrival:</span>
							<span id="wp-mbb-summary-arrival" class="wp-mbb-selection-summary__value">-</span>
						</div>
						<div class="wp-mbb-selection-summary__item">
							<span class="wp-mbb-selection-summary__label">Departure:</span>
							<span id="wp-mbb-summary-departure" class="wp-mbb-selection-summary__value">-</span>
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

    // Helper: format date string as DD-MM-YYYY for summary display
    function formatDateDDMMYYYY(dateStr) {
        if (!dateStr) return '';
        const parts = dateStr.split('-');
        if (parts.length === 3) {
            const [y, m, d] = parts;
            return `${d}-${m}-${y}`;
        }
        return dateStr;
    }

    const modal = document.getElementById('wp-mbb-multi-hotel-modal');
    const triggerInput = document.getElementById('wp-mbb-date-display');
    const calendarContainer = modal.querySelector('.wp-mbb-hotel-selector__calendar');
    const arrivalHidden = document.getElementById('wp-mbb-arrival');
    const departureHidden = document.getElementById('wp-mbb-departure');
    const hotelSelect = document.getElementById('wp-mbb-hotel-select');
    const summaryContainer = document.getElementById('wp-mbb-selection-summary');
    const summaryHotel = document.getElementById('wp-mbb-summary-hotel');
    const summaryArrival = document.getElementById('wp-mbb-summary-arrival');
    const summaryDeparture = document.getElementById('wp-mbb-summary-departure');
    let pickerInstance = null;

    // Initialize modal for single hotel mode (hide dropdown, use preselected hotel)
    function initializeSingleHotelMode(hotelName) {
        const hotelField = document.getElementById('wp-mbb-hotel-field');
        if (hotelField) {
            hotelField.style.display = 'none';
        }
        // Set the hotel select to the preselected value
        if (hotelSelect && hotelSelect.options.length > 0) {
            // Try to find the hotel in the options
            for (let i = 0; i < hotelSelect.options.length; i++) {
                if (hotelSelect.options[i].text === hotelName || hotelSelect.options[i].value === hotelName) {
                    hotelSelect.value = hotelSelect.options[i].value;
                    break;
                }
            }
            // If not found, select the first option
            if (!hotelSelect.value && hotelSelect.options.length > 0) {
                hotelSelect.selectedIndex = 0;
            }
        }
    }

    // Update selection summary if both hotel and dates are selected
    function updateSummary() {
        const selectedHotel = hotelSelect.value;
        const selectedHotelText = hotelSelect.options[hotelSelect.selectedIndex]?.text || '';
        const arrival = arrivalHidden.value;
        const departure = departureHidden.value;

        // Show summary only if all three values are selected
        if (selectedHotel && arrival && departure) {
            summaryHotel.textContent = selectedHotelText;
            summaryArrival.textContent = formatDateDDMMYYYY(arrival);
            summaryDeparture.textContent = formatDateDDMMYYYY(departure);
            summaryContainer.style.display = 'block';
        } else {
            summaryContainer.style.display = 'none';
        }
    }

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
                        'wp-content/assets/vendor/easepick/easepick.css'
                    ],
                    plugins: [easepickRef.RangePlugin, easepickRef.LockPlugin],
                    RangePlugin: {
                        tooltip: true,
                        locale: {
                            one: 'nacht',
                            other: 'nachten'
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
                            
                            // Reapply custom colors after easepick updates the DOM
                            reapplyDateColors();
                            
                            // Update summary if hotel is also selected
                            updateSummary();
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

    // Reapply custom date colors after easepick updates the DOM
    function reapplyDateColors() {
        setTimeout(() => {
            const days = calendarContainer.querySelectorAll('.easepick-day');
            days.forEach(day => {
                if (day.classList.contains('start') || day.classList.contains('end')) {
                    day.style.backgroundColor = '#ef4444 !important';
                    day.style.color = '#ffffff !important';
                } else if (day.classList.contains('in-range')) {
                    day.style.backgroundColor = '#606163ff !important';
                    day.style.color = '#1f2937 !important';
                }
            });
            console.log('[WP-MBB] Date colors reapplied');
        }, 50);
    }

    // Wait for easepick to load, then init picker
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPicker);
    } else {
        initPicker();
    }

    // Listen for reset from frontend.js
    document.addEventListener('wp-mbb-reset-easepick', resetPicker);

    // Listen for hotel selection changes
    if (hotelSelect) {
        hotelSelect.addEventListener('change', updateSummary);
    }

    // Hide picker (hides the calendar UI)
    function hidePicker() {
        if (pickerInstance && typeof pickerInstance.hide === 'function') {
            try {
                pickerInstance.hide();
                console.log('[WP-MBB] Picker hidden');
            } catch (e) {
                console.warn('[WP-MBB] Error hiding picker:', e);
            }
        }
    }

    // Show picker (shows the calendar UI)
    function showPicker() {
        if (pickerInstance && typeof pickerInstance.show === 'function') {
            try {
                pickerInstance.show();
                console.log('[WP-MBB] Picker shown');
            } catch (e) {
                console.warn('[WP-MBB] Error showing picker:', e);
            }
        }
    }

    // Expose for debugging/external use
    window.wpMbbPicker = pickerInstance;
    window.wpMbbResetPicker = resetPicker;
    window.wpMbbHidePicker = hidePicker;
    window.wpMbbShowPicker = showPicker;
    
    // Expose function to open modal in single hotel mode
    window.wpMbbOpenSingleHotelModal = function(hotelName) {
        modal.setAttribute('data-single-hotel', 'true');
        initializeSingleHotelMode(hotelName);
        resetPicker();
        modal.classList.add('is-visible');
        showPicker();
    };
    
    // Expose function to open modal in multi-hotel mode
    window.wpMbbOpenMultiHotelModal = function() {
        modal.removeAttribute('data-single-hotel');
        const hotelField = document.getElementById('wp-mbb-hotel-field');
        if (hotelField) {
            hotelField.style.display = 'block';
        }
        resetPicker();
        modal.classList.add('is-visible');
        showPicker();
    };
})();
</script>