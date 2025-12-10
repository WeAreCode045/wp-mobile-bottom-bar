<?php
/**
 * Lighthouse Integration
 *
 * Handles MyLighthouse booking system integration
 *
 * @package Mobile_Bottom_Bar
 */

if (!defined('ABSPATH')) {
    exit;
}

class MBB_Lighthouse {
    
    private $mylighthouse_bootstrap = null;
    private $lighthouse_assets_enqueued = false;
    private $lighthouse_templates_printed = false;

    public function get_mylighthouse_bootstrap(): array {
        if (null !== $this->mylighthouse_bootstrap) {
            return $this->mylighthouse_bootstrap;
        }

        $inactive = [
            'isActive' => false,
            'hotels' => [],
            'bookingPageUrl' => '',
            'displayMode' => 'modal',
        ];

        if (!$this->has_mylighthouse_plugin()) {
            $this->mylighthouse_bootstrap = $inactive;
            return $this->mylighthouse_bootstrap;
        }

        $hotels = $this->get_mylighthouse_hotels();
        $booking_url = get_option('mlb_booking_page_url', '');
        $display_mode = get_option('mlb_display_mode', 'modal') === 'modal' ? 'modal' : 'booking_page';

        $this->mylighthouse_bootstrap = [
            'isActive' => true,
            'hotels' => $hotels,
            'bookingPageUrl' => $booking_url,
            'displayMode' => $display_mode,
        ];

        return $this->mylighthouse_bootstrap;
    }

    public function get_mylighthouse_hotels(): array {
        $records = [];

        if (class_exists('Mylighthouse_Booker_Hotel')) {
            $records = Mylighthouse_Booker_Hotel::get_all(['status' => 'active']);
        } else {
            $legacy = get_option('mlb_hotels', []);
            if (empty($legacy)) {
                $legacy = get_option('mlb_hotels_backup', []);
            }
            if (!empty($legacy)) {
                $records = $legacy;
            }
        }

        if (empty($records) || !is_array($records)) {
            return [];
        }

        $hotels = [];

        foreach ($records as $record) {
            if (is_array($record)) {
                $id = $record['hotel_id'] ?? ($record['id'] ?? '');
                $name = $record['name'] ?? ($record['title'] ?? $id);
            } elseif (is_object($record)) {
                $id = $record->hotel_id ?? ($record->id ?? '');
                $name = $record->name ?? $id;
            } else {
                continue;
            }

            $id = (string) $id;

            if ($id === '') {
                continue;
            }

            $hotels[] = [
                'id' => $id,
                'name' => is_string($name) && $name !== '' ? $name : $id,
            ];
        }

        return $hotels;
    }

    public function has_mylighthouse_plugin(): bool {
        return defined('MYLIGHTHOUSE_BOOKER_PLUGIN_FILE');
    }

    public function build_lighthouse_script_params(): array {
        $meta = $this->get_mylighthouse_bootstrap();

        return [
            'booking_page_url' => $meta['bookingPageUrl'] ?? '',
            'result_target' => $meta['displayMode'] ?? 'modal',
            'spinner_image_url' => $this->get_lighthouse_spinner_url(),
        ];
    }

    public function get_lighthouse_spinner_url(): string {
        $spinner_url = esc_url(get_option('mlb_spinner_image_url', ''));

        if ($spinner_url) {
            return $spinner_url;
        }

        $attachment_id = (int) get_option('mlb_spinner_image_id', 0);
        if ($attachment_id > 0) {
            $attachment_url = wp_get_attachment_image_url($attachment_id, 'full');
            if ($attachment_url) {
                return esc_url($attachment_url);
            }
        }

        return '';
    }

    public function ensure_lighthouse_assets_enqueued(): void {
        if ($this->lighthouse_assets_enqueued || !$this->has_mylighthouse_plugin()) {
            return;
        }

        $style_handles = ['fontawesome', 'easepick', 'mylighthouse-booker-frontend', 'mylighthouse-booker-modal'];
        foreach ($style_handles as $handle) {
            if (!wp_style_is($handle, 'enqueued')) {
                wp_enqueue_style($handle);
            }
        }

        $script_handles = [
            'easepick-datetime',
            'easepick-base-plugin',
            'easepick-core',
            'easepick-range',
            'easepick-lock',
            'easepick-wrapper',
            'mylighthouse-booker-booking-modal',
            'mylighthouse-booker-form',
            'mylighthouse-booker-room-form',
            'mylighthouse-booker-room-booking',
        ];

        foreach ($script_handles as $handle) {
            if (!wp_script_is($handle, 'enqueued')) {
                wp_enqueue_script($handle);
            }
        }

        wp_localize_script('mylighthouse-booker-room-form', 'cqb_params', $this->build_lighthouse_script_params());
        $this->lighthouse_assets_enqueued = true;
    }

    public function should_render_lighthouse_button(array $bar): bool {
        if (!$this->has_mylighthouse_plugin()) {
            return false;
        }

        $config = $bar['lighthouseIntegration'] ?? null;

        if (!is_array($config)) {
            return false;
        }

        if (empty($config['enabled'])) {
            return false;
        }

        if (!empty($config['hotelId'])) {
            $meta = $this->get_mylighthouse_bootstrap();
            return !empty($meta['bookingPageUrl']);
        }

        if ((!empty($config['enableMultiHotel']) || !empty($config['allowMultipleHotels'])) && is_array($config['selectedHotels']) && count($config['selectedHotels']) > 0) {
            $meta = $this->get_mylighthouse_bootstrap();
            return !empty($meta['bookingPageUrl']);
        }

        return false;
    }

    public function normalize_selected_hotels(array $selected_hotels): array {
        $available_hotels_map = [];
        $available_hotels = $this->get_mylighthouse_hotels();
        
        foreach ($available_hotels as $hotel) {
            if (!empty($hotel['id'])) {
                $available_hotels_map[$hotel['id']] = $hotel['name'] ?? $hotel['id'];
            }
        }
        
        $normalized = [];
        
        foreach ($selected_hotels as $hotel) {
            if (is_string($hotel)) {
                $hotel_id = sanitize_text_field($hotel);
                $hotel_name = $available_hotels_map[$hotel_id] ?? $hotel_id;
                $normalized[] = [
                    'id' => $hotel_id,
                    'name' => $hotel_name,
                ];
            } elseif (is_array($hotel) && !empty($hotel['id'])) {
                $hotel_id = sanitize_text_field($hotel['id']);
                $hotel_name = sanitize_text_field($hotel['name'] ?? '');
                
                if ($hotel_name === '') {
                    $hotel_name = $available_hotels_map[$hotel_id] ?? $hotel_id;
                }
                
                $normalized[] = [
                    'id' => $hotel_id,
                    'name' => $hotel_name,
                ];
            }
        }
        
        return $normalized;
    }

    public function get_lighthouse_form_id(array $bar): string {
        return 'wp-mbb-mlb-form-' . sanitize_key($bar['id'] ?? uniqid('bar_', false));
    }

    public function inject_lighthouse_button(array $items, array $bar): array {
        $button = $this->build_lighthouse_item($bar);

        if (!$button) {
            return $items;
        }

        $insert_at = max(1, (int) floor(count($items) / 2));
        array_splice($items, $insert_at, 0, [$button]);

        return $items;
    }

    public function build_lighthouse_item(array $bar): ?array {
        $config = $bar['lighthouseIntegration'] ?? [];
        $form_id = $this->get_lighthouse_form_id($bar);
        $meta = $this->get_mylighthouse_bootstrap();
        $booking_url = $meta['bookingPageUrl'] ?? '';

        if ((!empty($config['enableMultiHotel']) || !empty($config['allowMultipleHotels'])) && is_array($config['selectedHotels']) && count($config['selectedHotels']) > 0) {
            $label = __('Book', 'mobile-bottom-bar');
            $normalized_hotels = $this->normalize_selected_hotels($config['selectedHotels']);
            
            return [
                'label' => $label,
                'href' => '#',
                'icon' => 'calendar',
                'target' => '_self',
                'rel' => '',
                'is_active' => false,
                'type' => 'mylighthouse-multi',
                'payload' => [
                    'formId' => $form_id,
                    'hotels' => $normalized_hotels,
                    'isMultiple' => true,
                    'bookingUrl' => $booking_url,
                ],
                'linkTargetBehavior' => 'self',
            ];
        }

        $hotel_id = $config['hotelId'] ?? '';

        if ($hotel_id === '') {
            return null;
        }

        $hotel_name = $config['hotelName'] ?? '';
        $label = is_string($hotel_name) && $hotel_name !== '' ? $hotel_name : __('Book', 'mobile-bottom-bar');

        return [
            'label' => $label,
            'href' => '#',
            'icon' => 'calendar',
            'target' => '_self',
            'rel' => '',
            'is_active' => false,
            'type' => 'mylighthouse',
            'payload' => [
                'formId' => $form_id,
                'hotelId' => $hotel_id,
            ],
            'linkTargetBehavior' => 'self',
        ];
    }

    public function render_lighthouse_form(array $bar): void {
        if (!$this->should_render_lighthouse_button($bar)) {
            return;
        }

        $meta = $this->get_mylighthouse_bootstrap();
        $booking_url = $meta['bookingPageUrl'] ?? '';
        $config = $bar['lighthouseIntegration'] ?? [];

        if ($booking_url === '') {
            return;
        }

        if (!$this->lighthouse_templates_printed) {
            $this->maybe_include_lighthouse_templates();
        }

        $form_id = $this->get_lighthouse_form_id($bar);

        if ((!empty($config['enableMultiHotel']) || !empty($config['allowMultipleHotels'])) && is_array($config['selectedHotels']) && count($config['selectedHotels']) > 0) {
            foreach ($config['selectedHotels'] as $hotel) {
                $hotel_id = $hotel['id'] ?? '';
                $hotel_name = $hotel['name'] ?? '';
                
                if ($hotel_id === '') {
                    continue;
                }

                $single_form_id = $form_id . '-hotel-' . sanitize_key($hotel_id);
                $hotel_name_safe = is_string($hotel_name) && $hotel_name !== '' ? $hotel_name : __('Hotel', 'mobile-bottom-bar');

                echo '<div class="wp-mbb__mylighthouse-scaffold wp-mbb__mylighthouse-scaffold--multi" aria-hidden="true" data-bar-id="' . esc_attr($bar['id']) . '" data-hotel-id="' . esc_attr($hotel_id) . '">';
                echo '<div class="mlb-booking-form mlb-room-form" data-single-button="true">';
                echo '<form id="' . esc_attr($single_form_id) . '" class="mlb-form mlb-room-form-type" method="GET" action="' . esc_url($booking_url) . '" data-hotel-id="' . esc_attr($hotel_id) . '" data-room-id="" data-hotel-name="' . esc_attr($hotel_name_safe) . '" data-room-name="">';
                echo '<input type="hidden" name="hotel_id" value="' . esc_attr($hotel_id) . '" />';
                echo '<input type="hidden" name="room_id" value="" />';
                echo '<input type="hidden" name="hotel_name" value="' . esc_attr($hotel_name_safe) . '" />';
                echo '<input type="hidden" name="room_name" value="" />';
                echo '<input type="hidden" class="mlb-checkin" name="Arrival" />';
                echo '<input type="hidden" class="mlb-checkout" name="Departure" />';
                echo '<div class="form-actions">';
                echo '<button type="button" class="mlb-submit-btn mlb-book-room-btn mlb-btn-primary" data-trigger-modal="true">' . esc_html__('Check availability', 'mobile-bottom-bar') . '</button>';
                echo '</div>';
                echo '</form>';
                echo '</div>';
                echo '</div>';
            }
            return;
        }

        $hotel_id = $config['hotelId'] ?? '';

        if ($hotel_id === '') {
            return;
        }

        $hotel_name = $config['hotelName'] ?? '';
        $hotel_name = is_string($hotel_name) && $hotel_name !== '' ? $hotel_name : __('Selected hotel', 'mobile-bottom-bar');

        echo '<div class="wp-mbb__mylighthouse-scaffold" aria-hidden="true" data-bar-id="' . esc_attr($bar['id']) . '">';
        echo '<div class="mlb-booking-form mlb-room-form" data-single-button="true">';
        echo '<form id="' . esc_attr($form_id) . '" class="mlb-form mlb-room-form-type" method="GET" action="' . esc_url($booking_url) . '" data-hotel-id="' . esc_attr($hotel_id) . '" data-room-id="" data-hotel-name="' . esc_attr($hotel_name) . '" data-room-name="">';
        echo '<input type="hidden" name="hotel_id" value="' . esc_attr($hotel_id) . '" />';
        echo '<input type="hidden" name="room_id" value="" />';
        echo '<input type="hidden" name="hotel_name" value="' . esc_attr($hotel_name) . '" />';
        echo '<input type="hidden" name="room_name" value="" />';
        echo '<input type="hidden" class="mlb-checkin" name="Arrival" />';
        echo '<input type="hidden" class="mlb-checkout" name="Departure" />';
        echo '<div class="form-actions">';
        echo '<button type="button" class="mlb-submit-btn mlb-book-room-btn mlb-btn-primary" data-trigger-modal="true">' . esc_html__('Check availability', 'mobile-bottom-bar') . '</button>';
        echo '</div>';
        echo '</form>';
        echo '</div>';
        echo '</div>';
    }

    public function maybe_include_lighthouse_templates(): void {
        if ($this->lighthouse_templates_printed || !$this->has_mylighthouse_plugin()) {
            return;
        }

        $templates = [
            'templates/modals/modal-room.php',
            'templates/modals/modal-special.php',
            'templates/modals/modal-calendar-template.php',
            'templates/modals/modal-fragments.php',
        ];

        foreach ($templates as $relative_path) {
            $path = trailingslashit(MYLIGHTHOUSE_BOOKER_ABSPATH) . ltrim($relative_path, '/');
            if (file_exists($path)) {
                include $path;
            }
        }

        $this->lighthouse_templates_printed = true;
    }

    public function render_multi_hotel_modal_template(): void {
        static $rendered = false;
        
        if ($rendered) {
            return;
        }
        
        $template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/multi-hotel-modal.php';
        
        if (file_exists($template_path)) {
            include $template_path;
            $rendered = true;
        }
    }
}
