<?php
/**
 * Settings Manager
 *
 * Handles all settings retrieval, sanitization, and validation
 *
 * @package Mobile_Bottom_Bar
 */

if (!defined('ABSPATH')) {
    exit;
}

class MBB_Settings {
    
    const OPTION_KEY = 'mobile_bottom_bar_settings';
    
    const ICON_SVGS = [
        'home' => '<path d="M3 10.5 12 3l9 7.5V20a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><path d="M9 22v-8h6v8"/>' ,
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>' ,
        'shopping-bag' => '<path d="M6 3 3 7v13a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-3-4Z"/><path d="M3 7h18"/><path d="M16 11a4 4 0 0 1-8 0"/>' ,
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M6 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>' ,
        'heart' => '<path d="M20.5 8.5a5 5 0 0 0-8.5-3.5A5 5 0 0 0 3.5 8.5c0 3 2.5 5.5 8.5 11.5 6-6 8.5-8.5 8.5-11.5Z"/>' ,
        'bell' => '<path d="M6 9a6 6 0 0 1 12 0c0 5 2 7 2 7H4s2-2 2-7"/><path d="M10 22h4"/>' ,
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.05.05a2 2 0 0 1-2.83 2.83l-.05-.05a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V22a2 2 0 0 1-4 0v-.12a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.05.05a2 2 0 1 1-2.83-2.83l.05-.05a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H2a2 2 0 0 1 0-4h.12a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.05-.05a2 2 0 1 1 2.83-2.83l.05.05a1.65 1.65 0 0 0 1.82.33H8a1.65 1.65 0 0 0 1-1.51V2a2 2 0 0 1 4 0v.12a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.05-.05a2 2 0 1 1 2.83 2.83l-.05.05a1.65 1.65 0 0 0-.33 1.82V8a1.65 1.65 0 0 0 1.51 1H22a2 2 0 0 1 0 4h-.12a1.65 1.65 0 0 0-1.51 1Z"/>' ,
        'bookmark' => '<path d="M6 3h12v19l-6-4-6 4Z"/>' ,
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11 17a19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3-8.63A2 2 0 0 1 4.11 1h3a2 2 0 0 1 2 1.72c.12 1 .38 2 .8 3a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.52 12.52 0 0 0 2.81.7A2 2 0 0 1 22 16.92Z"/>' ,
        'gift' => '<polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5" rx="1"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7c0-2.8-2.2-5-5-5a3 3 0 0 0 0 6h5"/><path d="M12 7c0-2.8 2.2-5 5-5a3 3 0 0 1 0 6h-5"/>' ,
        'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a2 2 0 0 1-2.06 0L2 7"/>' ,
        'map' => '<path d="M3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21 3 6"/><path d="M9 3v15"/><path d="M15 6v15"/>' ,
        'calendar' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/><path d="m9 16 2 2 4-4"/>' ,
    ];
    
    const SUPPORTED_LAYOUTS = ['standard', 'centered', 'floating', 'divided', 'compact', 'large'];

    public function get_settings(): array {
        $stored = get_option(self::OPTION_KEY, []);
        $raw = is_array($stored) ? $stored : [];
        $bars = $raw['bars'] ?? $raw;

        return [
            'bars' => $this->ensure_valid_bars($bars),
            'globalStyle' => $this->sanitize_style($raw['globalStyle'] ?? null),
            'defaultCustomMenu' => $this->sanitize_custom_items($raw['defaultCustomMenu'] ?? []),
            'defaultModalStyle' => isset($raw['defaultModalStyle']) ? $this->sanitize_modal_style($raw['defaultModalStyle']) : $this->get_default_modal_style(),
            'contactFormSettings' => isset($raw['contactFormSettings']) ? $this->sanitize_contact_form_settings($raw['contactFormSettings']) : $this->get_default_contact_form_settings(),
        ];
    }

    public function sanitize_settings(array $data): array {
        return [
            'bars' => $this->ensure_valid_bars($data['bars'] ?? null),
            'globalStyle' => $this->sanitize_style($data['globalStyle'] ?? null),
            'defaultCustomMenu' => $this->sanitize_custom_items($data['defaultCustomMenu'] ?? []),
            'defaultModalStyle' => isset($data['defaultModalStyle']) ? $this->sanitize_modal_style($data['defaultModalStyle']) : $this->get_default_modal_style(),
            'contactFormSettings' => isset($data['contactFormSettings']) ? $this->sanitize_contact_form_settings($data['contactFormSettings']) : $this->get_default_contact_form_settings(),
        ];
    }

    public function save_settings(array $data): array {
        $sanitized = $this->sanitize_settings($data);
        update_option(self::OPTION_KEY, $sanitized);
        return $sanitized;
    }

    private function ensure_valid_bars($bars): array {
        if (!is_array($bars)) {
            return [$this->get_default_bar()];
        }

        $is_associative = array_keys($bars) !== range(0, count($bars) - 1);

        if ($is_associative && (isset($bars['enabled']) || isset($bars['selectedMenu']))) {
            $bars = [$bars];
        }

        $sanitized = [];

        foreach ($bars as $index => $bar) {
            if (!is_array($bar)) {
                continue;
            }

            $sanitized[] = $this->sanitize_bar($bar, (int) $index);
        }

        if (empty($sanitized)) {
            $sanitized[] = $this->get_default_bar();
        }

        return $sanitized;
    }

    public function get_default_style(): array {
        return [
            'barStyle' => 'dark',
            'accentColor' => '#fb304b',
            'barBackgroundColor' => '#0f172a',
            'iconBackgroundColor' => '#1f2937',
            'iconBackgroundRadius' => 14,
            'iconBorderWidth' => 0,
            'desktopSidebarWidth' => 90,
            'desktopSidebarCornerRadius' => $this->get_default_sidebar_corner_radius(),
            'desktopSidebarAlignment' => 'center',
            'desktopSidebarSlideLabel' => true,
            'showLabels' => true,
            'layout' => 'standard',
            'iconSize' => 20,
            'iconColor' => '#9ca3af',
            'calendarIconSize' => 56,
            'calendarIconColor' => '#fb304b',
            'textSize' => 12,
            'textWeight' => '400',
            'textFont' => 'system-ui',
            'textColor' => '#6b7280',
        ];
    }

    public function get_default_modal_style(): array {
        return [
            'overlayColor' => '#0f172a',
            'modalBackgroundColor' => '#ffffff',
            'modalTextColor' => '#0f172a',
            'modalAccentColor' => '#fb304b',
            'borderRadius' => 16,
            'maxWidth' => 420,
        ];
    }

    public function get_default_sidebar_corner_radius(): array {
        return [
            'topLeft' => 32,
            'topRight' => 32,
            'bottomRight' => 32,
            'bottomLeft' => 32,
        ];
    }

    private function sanitize_sidebar_corner_radius($value, array $defaults): array {
        $corners = is_array($value) ? $value : [];

        return [
            'topLeft' => $this->clamp_corner_radius($corners['topLeft'] ?? $defaults['topLeft'] ?? 32),
            'topRight' => $this->clamp_corner_radius($corners['topRight'] ?? $defaults['topRight'] ?? 32),
            'bottomRight' => $this->clamp_corner_radius($corners['bottomRight'] ?? $defaults['bottomRight'] ?? 32),
            'bottomLeft' => $this->clamp_corner_radius($corners['bottomLeft'] ?? $defaults['bottomLeft'] ?? 32),
        ];
    }

    private function clamp_corner_radius($value): int {
        $number = is_numeric($value) ? (int) $value : 0;
        return max(0, min(120, $number));
    }

    private function expand_uniform_sidebar_radius($value, array $defaults): array {
        $uniform = $this->clamp_corner_radius($value ?? $defaults['topLeft'] ?? 32);

        return [
            'topLeft' => $uniform,
            'topRight' => $uniform,
            'bottomRight' => $uniform,
            'bottomLeft' => $uniform,
        ];
    }

    private function sanitize_style($style): array {
        $style = is_array($style) ? $style : [];
        $defaults = $this->get_default_style();

        $bar_style = in_array($style['barStyle'] ?? $defaults['barStyle'], ['light', 'dark'], true)
            ? ($style['barStyle'] ?? $defaults['barStyle'])
            : $defaults['barStyle'];
        $alignment = in_array($style['desktopSidebarAlignment'] ?? $defaults['desktopSidebarAlignment'], ['center', 'spread'], true)
            ? ($style['desktopSidebarAlignment'] ?? $defaults['desktopSidebarAlignment'])
            : $defaults['desktopSidebarAlignment'];
        $accent_color = sanitize_hex_color($style['accentColor'] ?? $defaults['accentColor']) ?: $defaults['accentColor'];
        $calendar_color_input = $style['calendarIconColor'] ?? $accent_color;
        $calendar_icon_color = sanitize_hex_color($calendar_color_input) ?: $accent_color;
        $corner_input = $style['desktopSidebarCornerRadius'] ?? null;

        if (!is_array($corner_input) && isset($style['desktopSidebarBorderRadius'])) {
            $corner_input = $this->expand_uniform_sidebar_radius(
                $style['desktopSidebarBorderRadius'],
                $defaults['desktopSidebarCornerRadius']
            );
        }

        $corner_radius = $this->sanitize_sidebar_corner_radius(
            $corner_input,
            $defaults['desktopSidebarCornerRadius']
        );

        return [
            'barStyle' => $bar_style,
            'accentColor' => $accent_color,
            'barBackgroundColor' => sanitize_hex_color($style['barBackgroundColor'] ?? $defaults['barBackgroundColor']) ?: $defaults['barBackgroundColor'],
            'iconBackgroundColor' => sanitize_hex_color($style['iconBackgroundColor'] ?? $defaults['iconBackgroundColor']) ?: $defaults['iconBackgroundColor'],
            'iconBackgroundRadius' => max(0, min(48, (int) ($style['iconBackgroundRadius'] ?? $defaults['iconBackgroundRadius']))),
            'iconBorderWidth' => max(0, min(8, (float) ($style['iconBorderWidth'] ?? $defaults['iconBorderWidth']))),
            'desktopSidebarWidth' => max(60, min(160, (int) ($style['desktopSidebarWidth'] ?? $defaults['desktopSidebarWidth']))),
            'desktopSidebarCornerRadius' => $corner_radius,
            'desktopSidebarAlignment' => $alignment,
            'desktopSidebarSlideLabel' => !empty($style['desktopSidebarSlideLabel']),
            'showLabels' => (bool) ($style['showLabels'] ?? $defaults['showLabels']),
            'layout' => $this->normalize_layout(sanitize_text_field($style['layout'] ?? $defaults['layout'])),
            'iconSize' => (int) ($style['iconSize'] ?? $defaults['iconSize']),
            'iconColor' => sanitize_hex_color($style['iconColor'] ?? $defaults['iconColor']) ?: $defaults['iconColor'],
            'calendarIconSize' => max(44, min(96, (int) ($style['calendarIconSize'] ?? $defaults['calendarIconSize']))),
            'calendarIconColor' => $calendar_icon_color,
            'textSize' => (int) ($style['textSize'] ?? $defaults['textSize']),
            'textWeight' => sanitize_text_field($style['textWeight'] ?? $defaults['textWeight']),
            'textFont' => sanitize_text_field($style['textFont'] ?? $defaults['textFont']),
            'textColor' => sanitize_hex_color($style['textColor'] ?? $defaults['textColor']) ?: $defaults['textColor'],
        ];
    }

    private function sanitize_modal_style($style): array {
        $style = is_array($style) ? $style : [];
        $defaults = $this->get_default_modal_style();

        return [
            'overlayColor' => sanitize_hex_color($style['overlayColor'] ?? $defaults['overlayColor']) ?: $defaults['overlayColor'],
            'modalBackgroundColor' => sanitize_hex_color($style['modalBackgroundColor'] ?? $defaults['modalBackgroundColor']) ?: $defaults['modalBackgroundColor'],
            'modalTextColor' => sanitize_hex_color($style['modalTextColor'] ?? $defaults['modalTextColor']) ?: $defaults['modalTextColor'],
            'modalAccentColor' => sanitize_hex_color($style['modalAccentColor'] ?? $defaults['modalAccentColor']) ?: $defaults['modalAccentColor'],
            'borderRadius' => max(0, min(48, (int) ($style['borderRadius'] ?? $defaults['borderRadius']))),
            'maxWidth' => max(320, min(640, (int) ($style['maxWidth'] ?? $defaults['maxWidth']))),
        ];
    }

    public function get_default_contact_form_settings(): array {
        return [
            'fromEmail' => get_option('admin_email'),
            'fromName' => get_bloginfo('name'),
            'subject' => 'New Contact Form Submission from {name}',
            'successMessage' => 'Thank you! Your message has been sent.',
            'errorMessage' => 'Sorry, there was an error sending your message. Please try again.',
            'googleApiKey' => '',
            'smtpEnabled' => false,
            'smtpHost' => '',
            'smtpPort' => 587,
            'smtpUsername' => '',
            'smtpPassword' => '',
            'smtpSecure' => 'tls',
        ];
    }

    private function sanitize_contact_form_settings($settings): array {
        $settings = is_array($settings) ? $settings : [];
        $defaults = $this->get_default_contact_form_settings();

        return [
            'fromEmail' => sanitize_email($settings['fromEmail'] ?? $defaults['fromEmail']) ?: $defaults['fromEmail'],
            'fromName' => sanitize_text_field($settings['fromName'] ?? $defaults['fromName']),
            'subject' => sanitize_text_field($settings['subject'] ?? $defaults['subject']),
            'successMessage' => sanitize_text_field($settings['successMessage'] ?? $defaults['successMessage']),
            'errorMessage' => sanitize_text_field($settings['errorMessage'] ?? $defaults['errorMessage']),
            'googleApiKey' => sanitize_text_field($settings['googleApiKey'] ?? $defaults['googleApiKey']),
            'smtpEnabled' => (bool) ($settings['smtpEnabled'] ?? $defaults['smtpEnabled']),
            'smtpHost' => sanitize_text_field($settings['smtpHost'] ?? $defaults['smtpHost']),
            'smtpPort' => (int) ($settings['smtpPort'] ?? $defaults['smtpPort']),
            'smtpUsername' => sanitize_text_field($settings['smtpUsername'] ?? $defaults['smtpUsername']),
            'smtpPassword' => sanitize_text_field($settings['smtpPassword'] ?? $defaults['smtpPassword']),
            'smtpSecure' => sanitize_text_field($settings['smtpSecure'] ?? $defaults['smtpSecure']),
        ];
    }

    public function apply_global_style(array $bar, array $style): array {
        if (empty($bar['useGlobalStyle'])) {
            return $bar;
        }

        $keys = [
            'barStyle',
            'accentColor',
            'barBackgroundColor',
            'iconBackgroundColor',
            'iconBackgroundRadius',
            'iconBorderWidth',
            'desktopSidebarWidth',
            'desktopSidebarCornerRadius',
            'desktopSidebarAlignment',
            'desktopSidebarSlideLabel',
            'showLabels',
            'layout',
            'iconSize',
            'iconColor',
            'calendarIconSize',
            'calendarIconColor',
            'textSize',
            'textWeight',
            'textFont',
            'textColor',
        ];

        foreach ($keys as $key) {
            if (array_key_exists($key, $style)) {
                $bar[$key] = $style[$key];
            }
        }

        return $bar;
    }

    public function get_default_bar(): array {
        return $this->sanitize_bar([
            'id' => uniqid('mbb_', false),
            'name' => __('Default Bar', 'mobile-bottom-bar'),
            'enabled' => false,
            'menuMode' => 'wordpress',
            'selectedMenu' => '',
            'barStyle' => 'dark',
            'accentColor' => '#6366f1',
            'barBackgroundColor' => '#0f172a',
            'iconBackgroundColor' => '#1f2937',
            'iconBackgroundRadius' => 14,
            'iconBorderWidth' => 0,
            'desktopSidebarWidth' => 90,
            'desktopSidebarCornerRadius' => $this->get_default_sidebar_corner_radius(),
            'desktopSidebarAlignment' => 'center',
            'desktopSidebarSlideLabel' => true,
            'showLabels' => true,
            'layout' => 'standard',
            'iconSize' => 20,
            'iconColor' => '#9ca3af',
            'calendarIconSize' => 56,
            'calendarIconColor' => '#6366f1',
            'textSize' => 12,
            'textWeight' => '400',
            'textFont' => 'system-ui',
            'textColor' => '#6b7280',
            'customItems' => [],
            'assignedPages' => [],
            'useGlobalStyle' => false,
            'showDesktopSidebar' => false,
            'lighthouseIntegration' => [
                'enabled' => false,
                'hotelId' => '',
                'hotelName' => '',
                'enableMultiHotel' => false,
                'selectedHotels' => [],
            ],
        ]);
    }

    public function sanitize_bar(array $bar, int $index = 0): array {
        $menu_mode = in_array($bar['menuMode'] ?? 'wordpress', ['wordpress', 'custom'], true)
            ? $bar['menuMode']
            : 'wordpress';

        $bar_style = in_array($bar['barStyle'] ?? 'dark', ['light', 'dark'], true) ? $bar['barStyle'] : 'dark';
        $name = sanitize_text_field($bar['name'] ?? sprintf(__('Bottom Bar %d', 'mobile-bottom-bar'), $index + 1));

        $accent_color = sanitize_hex_color($bar['accentColor'] ?? '#fb304b') ?: '#fb304b';
        $corner_defaults = $this->get_default_sidebar_corner_radius();
        $corner_input = $bar['desktopSidebarCornerRadius'] ?? null;

        if (!is_array($corner_input) && isset($bar['desktopSidebarBorderRadius'])) {
            $corner_input = $this->expand_uniform_sidebar_radius(
                $bar['desktopSidebarBorderRadius'],
                $corner_defaults
            );
        }

        $corner_radius = $this->sanitize_sidebar_corner_radius(
            $corner_input,
            $corner_defaults
        );

        return [
            'id' => sanitize_key($bar['id'] ?? uniqid('mbb_', false)),
            'name' => $name ?: sprintf(__('Bottom Bar %d', 'mobile-bottom-bar'), $index + 1),
            'enabled' => (bool) ($bar['enabled'] ?? false),
            'menuMode' => $menu_mode,
            'selectedMenu' => sanitize_key($bar['selectedMenu'] ?? ''),
            'barStyle' => $bar_style,
            'accentColor' => $accent_color,
            'barBackgroundColor' => sanitize_hex_color($bar['barBackgroundColor'] ?? '#0f172a') ?: '#0f172a',
            'iconBackgroundColor' => sanitize_hex_color($bar['iconBackgroundColor'] ?? '#1f2937') ?: '#1f2937',
            'iconBackgroundRadius' => max(0, min(48, (int) ($bar['iconBackgroundRadius'] ?? 14))),
            'iconBorderWidth' => max(0, min(8, (float) ($bar['iconBorderWidth'] ?? 0))),
            'desktopSidebarWidth' => max(60, min(160, (int) ($bar['desktopSidebarWidth'] ?? 90))),
            'desktopSidebarCornerRadius' => $corner_radius,
            'desktopSidebarAlignment' => in_array($bar['desktopSidebarAlignment'] ?? 'center', ['center', 'spread'], true)
                ? $bar['desktopSidebarAlignment']
                : 'center',
            'desktopSidebarSlideLabel' => !empty($bar['desktopSidebarSlideLabel']),
            'showLabels' => (bool) ($bar['showLabels'] ?? true),
            'layout' => $this->normalize_layout(sanitize_text_field($bar['layout'] ?? 'standard')),
            'iconSize' => (int) ($bar['iconSize'] ?? 20),
            'iconColor' => sanitize_hex_color($bar['iconColor'] ?? '#9ca3af') ?: '#9ca3af',
            'calendarIconSize' => max(44, min(96, (int) ($bar['calendarIconSize'] ?? 56))),
            'calendarIconColor' => sanitize_hex_color($bar['calendarIconColor'] ?? $accent_color) ?: $accent_color,
            'textSize' => (int) ($bar['textSize'] ?? 12),
            'textWeight' => sanitize_text_field($bar['textWeight'] ?? '400'),
            'textFont' => sanitize_text_field($bar['textFont'] ?? 'system-ui'),
            'textColor' => sanitize_hex_color($bar['textColor'] ?? '#6b7280') ?: '#6b7280',
            'customItems' => $this->sanitize_custom_items($bar['customItems'] ?? []),
            'assignedPages' => $this->sanitize_assigned_pages($bar['assignedPages'] ?? []),
            'useGlobalStyle' => !empty($bar['useGlobalStyle']),
            'showDesktopSidebar' => !empty($bar['showDesktopSidebar']),
            'lighthouseIntegration' => $this->sanitize_lighthouse_integration($bar['lighthouseIntegration'] ?? []),
        ];
    }

    private function sanitize_assigned_pages($assigned): array {
        if (!is_array($assigned)) {
            return [];
        }

        $sanitized = [];

        foreach ($assigned as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $page_id = (int) ($entry['pageId'] ?? 0);

            if ($page_id <= 0) {
                continue;
            }

            $sanitized[] = [
                'pageId' => $page_id,
                'includeChildren' => !empty($entry['includeChildren']),
            ];
        }

        return $sanitized;
    }

    public function sanitize_custom_items($items): array {
        if (!is_array($items)) {
            return [];
        }

        $allowed_icons = array_keys(self::ICON_SVGS);
        $allowed_types = ['link', 'phone', 'mail', 'modal', 'wysiwyg', 'map'];
        $sanitized = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $type = in_array($item['type'] ?? 'link', $allowed_types, true) ? $item['type'] : 'link';

            $sanitized_item = [
                'id' => sanitize_key($item['id'] ?? uniqid('mbb_', false)),
                'label' => sanitize_text_field($item['label'] ?? ''),
                'icon' => in_array($item['icon'] ?? '', $allowed_icons, true) ? $item['icon'] : 'home',
                'type' => $type,
                'href' => '',
                'phoneNumber' => '',
                'emailAddress' => '',
                'modalTitle' => '',
                'modalContent' => '',
                'wysiwygContent' => '',
                'linkTarget' => 'self',
                'mapAddress' => '',
            ];

            switch ($type) {
                case 'phone':
                    $sanitized_item['phoneNumber'] = sanitize_text_field($item['phoneNumber'] ?? '');
                    break;
                case 'mail':
                    $email = sanitize_email($item['emailAddress'] ?? '');
                    $sanitized_item['emailAddress'] = $email ?: '';
                    break;
                case 'modal':
                    $sanitized_item['modalTitle'] = sanitize_text_field($item['modalTitle'] ?? '');
                    $sanitized_item['modalContent'] = $this->sanitize_rich_content($item['modalContent'] ?? '');
                    break;
                case 'wysiwyg':
                    $sanitized_item['wysiwygContent'] = $this->sanitize_rich_content($item['wysiwygContent'] ?? '');
                    break;
                case 'map':
                    $sanitized_item['mapAddress'] = sanitize_text_field($item['mapAddress'] ?? '');
                    break;
                case 'link':
                default:
                    $sanitized_item['href'] = esc_url_raw($item['href'] ?? '');
                    $sanitized_item['linkTarget'] = $this->normalize_link_target($item['linkTarget'] ?? 'self');
                    break;
            }

            $sanitized[] = $sanitized_item;
        }

        return $sanitized;
    }

    public function sanitize_lighthouse_integration($value): array {
        $defaults = [
            'enabled' => false,
            'hotelId' => '',
            'hotelName' => '',
            'enableMultiHotel' => false,
            'selectedHotels' => [],
        ];

        if (!is_array($value)) {
            return $defaults;
        }

        $enabled = !empty($value['enabled']);
        $enable_multi_hotel = !empty($value['enableMultiHotel']) || !empty($value['allowMultipleHotels']);
        
        if ($enable_multi_hotel) {
            $selected_hotels = [];
            if (is_array($value['selectedHotels'] ?? null)) {
                foreach ($value['selectedHotels'] as $hotel) {
                    if (is_string($hotel)) {
                        $hotel_id = sanitize_text_field($hotel);
                        if ($hotel_id !== '') {
                            $selected_hotels[] = $hotel_id;
                        }
                    } elseif (is_array($hotel)) {
                        $hotel_id = sanitize_text_field($hotel['id'] ?? '');
                        $hotel_name = sanitize_text_field($hotel['name'] ?? '');
                        if ($hotel_id !== '') {
                            $selected_hotels[] = [
                                'id' => $hotel_id,
                                'name' => $hotel_name ?: $hotel_id,
                            ];
                        }
                    }
                }
            }

            return [
                'enabled' => $enabled,
                'hotelId' => '',
                'hotelName' => '',
                'enableMultiHotel' => true,
                'selectedHotels' => $selected_hotels,
            ];
        }

        $hotel_id = sanitize_text_field($value['hotelId'] ?? '');
        $hotel_name = sanitize_text_field($value['hotelName'] ?? '');

        return [
            'enabled' => $enabled,
            'hotelId' => $hotel_id,
            'hotelName' => $hotel_name,
            'enableMultiHotel' => false,
            'selectedHotels' => [],
        ];
    }

    private function sanitize_rich_content($content): string {
        if (!is_string($content)) {
            return '';
        }

        $allowed = wp_kses_allowed_html('post');
        $allowed['iframe'] = [
            'src' => true,
            'width' => true,
            'height' => true,
            'title' => true,
            'frameborder' => true,
            'allow' => true,
            'allowfullscreen' => true,
            'loading' => true,
            'referrerpolicy' => true,
            'sandbox' => true,
        ];

        return wp_kses($content, $allowed);
    }

    private function normalize_layout(string $layout): string {
        return in_array($layout, self::SUPPORTED_LAYOUTS, true) ? $layout : 'standard';
    }

    private function normalize_link_target($target): string {
        $allowed = ['self', 'blank', 'iframe'];
        $value = is_string($target) ? strtolower($target) : 'self';

        return in_array($value, $allowed, true) ? $value : 'self';
    }

    public function get_rest_args(): array {
        return [
            'bars' => [
                'type' => 'array',
                'required' => true,
                'items' => [
                    'type' => 'object',
                ],
            ],
            'globalStyle' => [
                'type' => 'object',
                'required' => false,
            ],
            'defaultCustomMenu' => [
                'type' => 'array',
                'required' => false,
                'items' => [
                    'type' => 'object',
                ],
            ],
            'defaultModalStyle' => [
                'type' => 'object',
                'required' => false,
            ],
        ];
    }
}
