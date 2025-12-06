<?php
/**
 * Plugin Name:       Mobby - Mobile Bottom Bar
 * Description:       Build your own dynamic mobile bottom navigation bar from the WordPress dashboard.
 * Version:           2.1.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Code045
 * Author URI:        https://code045.nl
 * Text Domain:       mobile-bottom-bar
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Mobile_Bottom_Bar_Plugin {
    public const OPTION_KEY = 'mobile_bottom_bar_settings';
    private const SCRIPT_HANDLE = 'mobile-bottom-bar-admin';
    private const VERSION = '0.1.0';
    private const ICON_SVGS = [
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
    private const SUPPORTED_LAYOUTS = ['standard', 'centered', 'floating', 'divided', 'compact', 'large'];
    private $mylighthouse_bootstrap = null;
    private $lighthouse_assets_enqueued = false;
    private $lighthouse_templates_printed = false;

    public static function instance(): self {
        static $instance = null;

        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('wp_footer', [$this, 'render_frontend_bar']);
        add_filter('body_class', [$this, 'filter_body_class']);
    }

    public function register_admin_page(): void {
        add_menu_page(
            __('Mobile Bottom Bar', 'mobile-bottom-bar'),
            __('Mobile Bottom Bar', 'mobile-bottom-bar'),
            'manage_options',
            'mobile-bottom-bar',
            [$this, 'render_app'],
            'dashicons-smartphone',
            58
        );
    }

    public function render_app(): void {
        echo '<div class="wrap"><div id="mobile-bottom-bar-root"></div></div>';
    }

    public function enqueue_assets(string $hook): void {
        if ('toplevel_page_mobile-bottom-bar' !== $hook) {
            return;
        }

        $entry = $this->get_manifest_entry();
        $asset_base = plugin_dir_url(__FILE__) . 'build/';

        if (!$entry) {
            wp_enqueue_script(self::SCRIPT_HANDLE, '', [], self::VERSION, true);
            wp_add_inline_script(self::SCRIPT_HANDLE, 'console.error("Mobile Bottom Bar assets missing. Run npm run build before loading the admin page.");');
            return;
        }

        wp_enqueue_script(
            self::SCRIPT_HANDLE,
            $asset_base . $entry['file'],
            [],
            self::VERSION,
            true
        );

        if (!empty($entry['css'])) {
            foreach ($entry['css'] as $index => $css_file) {
                wp_enqueue_style(
                    self::SCRIPT_HANDLE . ($index ? "-{$index}" : ''),
                    $asset_base . $css_file,
                    [],
                    self::VERSION
                );
            }
        }

        wp_localize_script(
            self::SCRIPT_HANDLE,
            'mobileBottomBarData',
            [
                'restUrl' => esc_url_raw(rest_url('mobile-bottom-bar/v1/settings')),
                'nonce' => wp_create_nonce('wp_rest'),
                'menus' => $this->get_menus(),
                'pages' => $this->get_page_options(),
                'settings' => $this->get_settings(),
                'mylighthouse' => $this->get_mylighthouse_bootstrap(),
            ]
        );
    }

    public function enqueue_frontend_assets(): void {
        if (is_admin()) {
            return;
        }

        $settings = $this->get_settings();
        $bar = $this->select_active_bar($settings['bars'] ?? []);

        if (!$bar || !$this->bar_has_frontend_items($bar)) {
            return;
        }

        $should_prepare_lighthouse = $this->should_render_lighthouse_button($bar);

        if ($should_prepare_lighthouse) {
            $this->ensure_lighthouse_assets_enqueued();
        }

        wp_enqueue_style(
            'mobile-bottom-bar-frontend',
            plugin_dir_url(__FILE__) . 'public/frontend.css',
            [],
            self::VERSION
        );

        wp_enqueue_script(
            'mobile-bottom-bar-frontend-js',
            plugin_dir_url(__FILE__) . 'public/frontend.js',
            [],
            self::VERSION,
            true
        );
    }

    public function register_rest_routes(): void {
        register_rest_route(
            'mobile-bottom-bar/v1',
            '/settings',
            [
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'rest_get_settings'],
                    'permission_callback' => [$this, 'permissions_check'],
                ],
                [
                    'methods' => 'POST',
                    'callback' => [$this, 'rest_save_settings'],
                    'permission_callback' => [$this, 'permissions_check'],
                    'args' => $this->get_rest_args(),
                ],
            ]
        );
    }

    public function rest_get_settings(): array {
        return $this->get_settings();
    }

    public function rest_save_settings(\WP_REST_Request $request): \WP_REST_Response {
        $data = $this->sanitize_settings((array) $request->get_json_params());

        update_option(self::OPTION_KEY, $data);

        return new \WP_REST_Response($data, 200);
    }

    public function permissions_check(): bool {
        return current_user_can('manage_options');
    }

    private function get_manifest_entry(): ?array {
        $base = plugin_dir_path(__FILE__) . 'build/';
        $candidates = [
            $base . '.vite/manifest.json',
            $base . 'manifest.json',
        ];

        foreach ($candidates as $manifest_path) {
            if (!file_exists($manifest_path)) {
                continue;
            }

            $manifest = json_decode((string) file_get_contents($manifest_path), true);

            if (is_array($manifest)) {
                return $manifest['src/main.tsx'] ?? null;
            }
        }

        return null;
    }

    private function get_menus(): array {
        $menus = wp_get_nav_menus();

        if (empty($menus)) {
            return [];
        }

        return array_map(
            static fn($menu) => [
                'id' => $menu->slug,
                'name' => $menu->name,
            ],
            $menus
        );
    }

    private function get_page_options(): array {
        $pages = get_pages([
            'sort_column' => 'menu_order',
            'post_status' => 'publish',
        ]);

        if (empty($pages)) {
            return [];
        }

        $children_count = [];

        foreach ($pages as $page) {
            $parent = (int) $page->post_parent;

            if ($parent > 0) {
                $children_count[$parent] = ($children_count[$parent] ?? 0) + 1;
            }
        }

        $options = [];

        foreach ($pages as $page) {
            $title = $page->post_title !== '' ? $page->post_title : sprintf(__('Page %d', 'mobile-bottom-bar'), $page->ID);

            $options[] = [
                'id' => (int) $page->ID,
                'title' => wp_strip_all_tags($title),
                'parentId' => $page->post_parent ? (int) $page->post_parent : null,
                'depth' => count(get_post_ancestors($page->ID)),
                'hasChildren' => !empty($children_count[$page->ID]),
            ];
        }

        return $options;
    }

    private function get_settings(): array {
        $stored = get_option(self::OPTION_KEY, []);
        $raw = is_array($stored) ? $stored : [];
        $bars = $raw['bars'] ?? $raw;

        return [
            'bars' => $this->ensure_valid_bars($bars),
            'globalStyle' => $this->sanitize_style($raw['globalStyle'] ?? null),
            'defaultCustomMenu' => $this->sanitize_custom_items($raw['defaultCustomMenu'] ?? []),
            'defaultModalStyle' => isset($raw['defaultModalStyle']) ? $this->sanitize_modal_style($raw['defaultModalStyle']) : $this->get_default_modal_style(),
        ];
    }

    private function sanitize_settings(array $data): array {
        return [
            'bars' => $this->ensure_valid_bars($data['bars'] ?? null),
            'globalStyle' => $this->sanitize_style($data['globalStyle'] ?? null),
            'defaultCustomMenu' => $this->sanitize_custom_items($data['defaultCustomMenu'] ?? []),
            'defaultModalStyle' => isset($data['defaultModalStyle']) ? $this->sanitize_modal_style($data['defaultModalStyle']) : $this->get_default_modal_style(),
        ];
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

    private function get_default_style(): array {
        return [
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
        ];
    }

    private function get_default_modal_style(): array {
        return [
            'overlayColor' => '#0f172a',
            'modalBackgroundColor' => '#ffffff',
            'modalTextColor' => '#0f172a',
            'modalAccentColor' => '#6366f1',
            'borderRadius' => 16,
            'maxWidth' => 420,
        ];
    }

    private function get_default_sidebar_corner_radius(): array {
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

    private function apply_global_style(array $bar, array $style): array {
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

    private function get_default_bar(): array {
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
                'allowMultipleHotels' => false,
                'selectedHotels' => [],
            ],
        ]);
    }

    private function sanitize_bar(array $bar, int $index = 0): array {
        $menu_mode = in_array($bar['menuMode'] ?? 'wordpress', ['wordpress', 'custom'], true)
            ? $bar['menuMode']
            : 'wordpress';

        $bar_style = in_array($bar['barStyle'] ?? 'dark', ['light', 'dark'], true) ? $bar['barStyle'] : 'dark';
        $name = sanitize_text_field($bar['name'] ?? sprintf(__('Bottom Bar %d', 'mobile-bottom-bar'), $index + 1));

        $accent_color = sanitize_hex_color($bar['accentColor'] ?? '#6366f1') ?: '#6366f1';
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

    private function sanitize_custom_items($items): array {
        if (!is_array($items)) {
            return [];
        }

        $allowed_icons = array_keys(self::ICON_SVGS);
        $allowed_types = ['link', 'phone', 'mail', 'modal', 'wysiwyg'];
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

    private function sanitize_lighthouse_integration($value): array {
        $defaults = [
            'enabled' => false,
            'hotelId' => '',
            'hotelName' => '',
            'allowMultipleHotels' => false,
            'selectedHotels' => [],
        ];

        if (!is_array($value)) {
            return $defaults;
        }

        $enabled = !empty($value['enabled']);
        $allow_multiple = !empty($value['allowMultipleHotels']);
        
        if (!$enabled) {
            return $defaults;
        }

        // Handle multiple hotels mode
        if ($allow_multiple) {
            $selected_hotels = [];
            if (is_array($value['selectedHotels'] ?? null)) {
                foreach ($value['selectedHotels'] as $hotel) {
                    if (is_array($hotel)) {
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
                'enabled' => true,
                'hotelId' => '', // No single hotel in multiple mode
                'hotelName' => '',
                'allowMultipleHotels' => true,
                'selectedHotels' => $selected_hotels,
            ];
        }

        // Handle single hotel mode
        $hotel_id = sanitize_text_field($value['hotelId'] ?? '');
        $hotel_name = sanitize_text_field($value['hotelName'] ?? '');

        return [
            'enabled' => true,
            'hotelId' => $hotel_id,
            'hotelName' => $hotel_name,
            'allowMultipleHotels' => false,
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

    private function get_rest_args(): array {
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

    public function render_frontend_bar(): void {
        if (is_admin()) {
            return;
        }

        $settings = $this->get_settings();
        $bar = $this->select_active_bar($settings['bars'] ?? []);

        if (!$bar || !$this->bar_has_frontend_items($bar)) {
            return;
        }

        $global_style = $settings['globalStyle'] ?? $this->get_default_style();
        $bar = $this->apply_global_style($bar, $global_style);

        $should_render_lighthouse = $this->should_render_lighthouse_button($bar);
        $items = $this->resolve_frontend_items($bar);

        if ($should_render_lighthouse) {
            $items = $this->inject_lighthouse_button($items, $bar);
        }

        if (empty($items)) {
            return;
        }

        $style_attribute = $this->build_style_attribute($bar);
        $layout = $this->normalize_layout($bar['layout'] ?? 'standard');
        $classes = ['wp-mbb'];
        $classes[] = $bar['barStyle'] === 'light' ? 'wp-mbb--light' : 'wp-mbb--dark';
        $classes[] = 'wp-mbb--layout-' . $layout;
        $is_desktop_sidebar = !empty($bar['showDesktopSidebar']);

        if ($is_desktop_sidebar) {
            $classes[] = 'wp-mbb--desktop-sidebar';
        }

        $data_attributes = ' data-layout="' . esc_attr($layout) . '" data-bar-id="' . esc_attr($bar['id']) . '"';

        if ($is_desktop_sidebar) {
            $data_attributes .= ' data-desktop-sidebar="true"';
            if (!empty($bar['desktopSidebarSlideLabel'])) {
                $data_attributes .= ' data-sidebar-slideout="true"';
            }
            $data_attributes .= ' data-sidebar-alignment="' . esc_attr($bar['desktopSidebarAlignment'] ?? 'center') . '"';
        }

        // Ensure the bar is initially hidden via inline style; JS below will reveal it on small viewports
        echo '<nav id="wp-mobile-bottom-bar" class="' . esc_attr(implode(' ', $classes)) . '" style="' . esc_attr($style_attribute) . ';display:none" aria-label="' . esc_attr__('Mobile bottom navigation', 'mobile-bottom-bar') . '"' . $data_attributes . '>';
        $this->render_layout($layout, $items, $bar);
        echo '</nav>';

        // Small runtime guard to show the bar only on mobile widths or when desktop-sidebar mode is enabled.
        echo "<script>(function(){var nav=document.getElementById('wp-mobile-bottom-bar');if(!nav)return;function update(){try{if(window.innerWidth<=767||nav.classList.contains('wp-mbb--desktop-sidebar')){nav.style.display='';document.body.classList.toggle('wp-mobile-bottom-bar-active', window.innerWidth<=767);}else{nav.style.display='none';document.body.classList.remove('wp-mobile-bottom-bar-active');}}catch(e){} }update();window.addEventListener('resize',function(){update();});})();</script>";

        if ($should_render_lighthouse) {
            $this->render_lighthouse_form($bar);
        }
    }

    private function normalize_layout(string $layout): string {
        return in_array($layout, self::SUPPORTED_LAYOUTS, true) ? $layout : 'standard';
    }

    private function render_layout(string $layout, array $items, array $bar): void {
        switch ($layout) {
            case 'centered':
                $this->render_centered_layout($items, $bar);
                break;
            case 'floating':
                $this->render_floating_layout($items, $bar);
                break;
            default:
                $this->render_linear_layout($items, $bar, $layout);
                break;
        }
    }

    private function render_linear_layout(array $items, array $bar, string $layout): void {
        $inner_classes = ['wp-mbb__inner', 'wp-mbb__inner--' . $layout];

        echo '<div class="' . esc_attr(implode(' ', array_filter($inner_classes))) . '">';

        foreach ($items as $item) {
            echo $this->render_menu_link($item, $bar);
        }

        echo '</div>';
    }

    private function render_centered_layout(array $items, array $bar): void {
        $left = array_slice($items, 0, 2);
        $right = array_slice($items, 2);

        echo '<div class="wp-mbb__inner wp-mbb__inner--centered">';
        echo '<div class="wp-mbb__group wp-mbb__group--left">';

        foreach ($left as $item) {
            echo $this->render_menu_link($item, $bar);
        }

        echo '</div>';
        echo '<span class="wp-mbb__fab" aria-hidden="true">+</span>';
        echo '<div class="wp-mbb__group wp-mbb__group--right">';

        foreach ($right as $item) {
            echo $this->render_menu_link($item, $bar);
        }

        echo '</div>';
        echo '</div>';
    }

    private function render_floating_layout(array $items, array $bar): void {
        echo '<div class="wp-mbb__floating-shell">';
        echo '<div class="wp-mbb__inner wp-mbb__inner--floating">';

        foreach ($items as $item) {
            echo $this->render_menu_link($item, $bar);
        }

        echo '</div>';
        echo '</div>';
    }

    private function render_menu_link(array $item, array $bar): string {
        $is_current = !empty($item['is_active']);
        $item_classes = ['wp-mbb__item'];

        if ($is_current) {
            $item_classes[] = 'is-active';
        }

        if (!empty($item['type']) && $item['type'] === 'mylighthouse') {
            $item_classes[] = 'wp-mbb__item--calendar';
        }

        $target = !empty($item['target']) && '_self' !== $item['target'] ? ' target="' . esc_attr($item['target']) . '"' : '';
        $rel = !empty($item['rel']) ? ' rel="' . esc_attr($item['rel']) . '"' : '';
        $data_attributes = '';

        if (!empty($item['type'])) {
            $data_attributes .= ' data-type="' . esc_attr($item['type']) . '"';
        }

        if (!empty($item['payload'])) {
            $encoded_payload = wp_json_encode($item['payload']);

            if ($encoded_payload) {
                $data_attributes .= ' data-payload="' . esc_attr($encoded_payload) . '"';
            }
        }

        if (!empty($item['linkTargetBehavior'])) {
            $data_attributes .= ' data-link-target="' . esc_attr($item['linkTargetBehavior']) . '"';
        }

        $label_markup = '';
        $label_text = esc_html($item['label'] ?? '');
        $should_show_mobile_label = !empty($bar['showLabels']);
        $should_show_slide_label = !empty($bar['showDesktopSidebar']) && !empty($bar['desktopSidebarSlideLabel']);

        if ($should_show_mobile_label) {
            $label_markup .= '<span class="wp-mbb__label wp-mbb__label--mobile">' . $label_text . '</span>';
        }

        if ($should_show_slide_label) {
            $label_markup .= '<span class="wp-mbb__label wp-mbb__label--slideout">' . $label_text . '</span>';
        }

        return '<a class="' . esc_attr(implode(' ', $item_classes)) . '" href="' . esc_url($item['href'] ?? '#') . '"' . $target . $rel . $data_attributes . '>'
            . '<span class="wp-mbb__icon" aria-hidden="true">' . $this->render_icon_markup($item['icon'] ?? null) . '</span>'
            . $label_markup
            . '</a>';
    }

    private function get_menu_items(string $menu_slug): array {
        $menu = wp_get_nav_menu_object($menu_slug);

        if (!$menu && is_numeric($menu_slug)) {
            $menu = wp_get_nav_menu_object((int) $menu_slug);
        }

        if (!$menu) {
            return [];
        }

        $items = wp_get_nav_menu_items($menu->term_id);

        return is_array($items) ? $items : [];
    }

    private function build_style_attribute(array $bar): string {
        $background = $bar['barStyle'] === 'light' ? '#ffffff' : '#0f172a';
        $text = $bar['barStyle'] === 'light' ? '#0f172a' : '#f8fafc';
        $bar_background = !empty($bar['barBackgroundColor']) ? $bar['barBackgroundColor'] : $background;
        $icon_background = !empty($bar['iconBackgroundColor']) ? $bar['iconBackgroundColor'] : ($bar['barStyle'] === 'light' ? '#e2e8f0' : 'rgba(255,255,255,0.08)');
        $icon_radius = isset($bar['iconBackgroundRadius']) ? (int) $bar['iconBackgroundRadius'] : 14;
        $border_color = $bar['barStyle'] === 'light' ? 'rgba(15, 23, 42, 0.12)' : 'rgba(255, 255, 255, 0.12)';
        $sidebar_width = isset($bar['desktopSidebarWidth']) ? (int) $bar['desktopSidebarWidth'] : 90;
        $corner_defaults = $this->get_default_sidebar_corner_radius();
        $corner_input = $bar['desktopSidebarCornerRadius'] ?? null;

        if (!is_array($corner_input) && isset($bar['desktopSidebarBorderRadius'])) {
            $corner_input = $this->expand_uniform_sidebar_radius(
                $bar['desktopSidebarBorderRadius'],
                $corner_defaults
            );
        }

        $sidebar_corners = $this->sanitize_sidebar_corner_radius($corner_input, $corner_defaults);
        $sidebar_alignment = ($bar['desktopSidebarAlignment'] ?? 'center') === 'spread' ? 'space-between' : 'center';
        $icon_border_width = isset($bar['iconBorderWidth']) ? (float) $bar['iconBorderWidth'] : 0;
        $calendar_icon_size = isset($bar['calendarIconSize']) ? (int) $bar['calendarIconSize'] : 56;
        $calendar_icon_color = sanitize_hex_color($bar['calendarIconColor'] ?? $bar['accentColor']) ?: $bar['accentColor'];

        $variables = [
            '--wp-mbb-accent' => $bar['accentColor'],
            '--wp-mbb-background' => $bar_background,
            '--wp-mbb-bar-background' => $bar_background,
            '--wp-mbb-bar-border' => $border_color,
            '--wp-mbb-text' => $text,
            '--wp-mbb-icon-color' => $bar['iconColor'],
            '--wp-mbb-icon-background' => $icon_background,
            '--wp-mbb-icon-radius' => $icon_radius . 'px',
            '--wp-mbb-icon-border-width' => $icon_border_width . 'px',
            '--wp-mbb-icon-border-color' => $bar['accentColor'],
            '--wp-mbb-text-color' => $bar['textColor'],
            '--wp-mbb-icon-size' => $bar['iconSize'] . 'px',
            '--wp-mbb-calendar-icon-size' => $calendar_icon_size . 'px',
            '--wp-mbb-calendar-icon-color' => $calendar_icon_color,
            '--wp-mbb-text-size' => $bar['textSize'] . 'px',
            '--wp-mbb-text-weight' => $bar['textWeight'],
            '--wp-mbb-sidebar-width' => $sidebar_width . 'px',
            '--wp-mbb-sidebar-radius-top-left' => $sidebar_corners['topLeft'] . 'px',
            '--wp-mbb-sidebar-radius-top-right' => $sidebar_corners['topRight'] . 'px',
            '--wp-mbb-sidebar-radius-bottom-right' => $sidebar_corners['bottomRight'] . 'px',
            '--wp-mbb-sidebar-radius-bottom-left' => $sidebar_corners['bottomLeft'] . 'px',
            '--wp-mbb-sidebar-justify' => $sidebar_alignment,
        ];

        $chunks = [];

        foreach ($variables as $key => $value) {
            $chunks[] = $key . ':' . $value;
        }

        return implode(';', $chunks);
    }

    public function filter_body_class(array $classes): array {
        if (is_admin()) {
            return $classes;
        }

        $settings = $this->get_settings();
        $bar = $this->select_active_bar($settings['bars'] ?? []);

        if ($bar && $this->bar_has_frontend_items($bar)) {
            $classes[] = 'wp-mobile-bottom-bar-active';

            if (!empty($bar['showDesktopSidebar'])) {
                $classes[] = 'wp-mobile-bottom-bar-sidebar-active';
            }
        }

        return $classes;
    }

    private function bar_has_frontend_items(array $bar): bool {
        if (empty($bar['enabled'])) {
            return false;
        }

        if (($bar['menuMode'] ?? 'wordpress') === 'custom') {
            return !empty($bar['customItems']);
        }

        return !empty($bar['selectedMenu']);
    }

    private function select_active_bar(array $bars): ?array {
        foreach ($bars as $bar) {
            if (!is_array($bar) || empty($bar['enabled'])) {
                continue;
            }

            if ($this->bar_matches_request($bar)) {
                return $bar;
            }
        }

        return null;
    }

    private function bar_matches_request(array $bar): bool {
        $assignments = $bar['assignedPages'] ?? [];

        if (empty($assignments)) {
            return true;
        }

        $current_id = get_queried_object_id();

        if (!$current_id) {
            return false;
        }

        $ancestors = get_post_ancestors($current_id);

        foreach ($assignments as $assignment) {
            if (!is_array($assignment)) {
                continue;
            }

            $page_id = (int) ($assignment['pageId'] ?? 0);

            if ($page_id <= 0) {
                continue;
            }

            if ($current_id === $page_id) {
                return true;
            }

            if (!empty($assignment['includeChildren']) && in_array($page_id, $ancestors, true)) {
                return true;
            }
        }

        return false;
    }

    private function resolve_frontend_items(array $bar): array {
        if (($bar['menuMode'] ?? 'wordpress') === 'custom') {
            $items = $bar['customItems'] ?? [];

            return array_values(array_filter(array_map(function ($item) {
                if (!is_array($item)) {
                    return null;
                }

                $type = $this->normalize_custom_item_type($item['type'] ?? 'link');
                $link_target = $type === 'link' ? $this->normalize_link_target($item['linkTarget'] ?? 'self') : 'self';
                $payload = $this->build_custom_item_payload($item);

                return [
                    'label' => $item['label'] ?? '',
                    'href' => $this->build_custom_item_href($item, $type),
                    'icon' => $item['icon'] ?? 'home',
                    'target' => $this->map_link_target_to_html($link_target),
                    'rel' => $link_target === 'blank' ? 'noopener noreferrer' : '',
                    'is_active' => false,
                    'type' => $type,
                    'payload' => $payload,
                    'linkTargetBehavior' => $link_target,
                ];
            }, $items)));
        }

        $posts = $this->get_menu_items($bar['selectedMenu'] ?? '');
        $resolved = [];

        foreach ($posts as $item) {
            if (!($item instanceof \WP_Post)) {
                continue;
            }

            $resolved[] = [
                'label' => $item->title ?? '',
                'href' => $item->url ?? '#',
                'icon' => null,
                'target' => $item->target ?: '_self',
                'rel' => $item->xfn ?: '',
                'is_active' => in_array('current-menu-item', (array) $item->classes, true),
                'type' => 'link',
                'payload' => [
                    'href' => $item->url ?? '#',
                    'phoneNumber' => '',
                    'emailAddress' => '',
                    'modalTitle' => '',
                    'modalContent' => '',
                    'wysiwygContent' => '',
                    'linkTarget' => $item->target === '_blank' ? 'blank' : 'self',
                ],
                'linkTargetBehavior' => $item->target === '_blank' ? 'blank' : 'self',
            ];
        }

        return $resolved;
    }

    private function normalize_custom_item_type($type): string {
        $allowed = ['link', 'phone', 'mail', 'modal', 'wysiwyg'];

        return in_array($type, $allowed, true) ? $type : 'link';
    }

    private function normalize_link_target($target): string {
        $allowed = ['self', 'blank', 'iframe'];
        $value = is_string($target) ? strtolower($target) : 'self';

        return in_array($value, $allowed, true) ? $value : 'self';
    }

    private function build_custom_item_payload(array $item): array {
        return [
            'href' => $item['href'] ?? '',
            'phoneNumber' => $item['phoneNumber'] ?? '',
            'emailAddress' => $item['emailAddress'] ?? '',
            'modalTitle' => $item['modalTitle'] ?? '',
            'modalContent' => $item['modalContent'] ?? '',
            'wysiwygContent' => $item['wysiwygContent'] ?? '',
            'linkTarget' => $this->normalize_link_target($item['linkTarget'] ?? 'self'),
        ];
    }

    private function build_custom_item_href(array $item, string $type): string {
        switch ($type) {
            case 'phone':
                $number = preg_replace('/[^0-9+]/', '', $item['phoneNumber'] ?? '');

                return $number ? 'tel:' . $number : '#';
            case 'mail':
                $email = $item['emailAddress'] ?? '';

                return $email ? 'mailto:' . $email : '#';
            case 'modal':
            case 'wysiwyg':
                return '#';
            case 'link':
            default:
                return !empty($item['href']) ? $item['href'] : '#';
        }
    }

    private function map_link_target_to_html(string $link_target): string {
        return $link_target === 'blank' ? '_blank' : '_self';
    }

    private function get_mylighthouse_bootstrap(): array {
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

    private function get_mylighthouse_hotels(): array {
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

    private function has_mylighthouse_plugin(): bool {
        return defined('MYLIGHTHOUSE_BOOKER_PLUGIN_FILE');
    }

    private function build_lighthouse_script_params(): array {
        $meta = $this->get_mylighthouse_bootstrap();

        return [
            'booking_page_url' => $meta['bookingPageUrl'] ?? '',
            'result_target' => $meta['displayMode'] ?? 'modal',
            'spinner_image_url' => $this->get_lighthouse_spinner_url(),
        ];
    }

    private function get_lighthouse_spinner_url(): string {
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

    private function ensure_lighthouse_assets_enqueued(): void {
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

    private function should_render_lighthouse_button(array $bar): bool {
        $config = $bar['lighthouseIntegration'] ?? null;

        if (!is_array($config)) {
            return false;
        }

        if (empty($config['enabled'])) {
            return false;
        }

        // Check if single hotel mode with hotel selected
        if (!empty($config['hotelId'])) {
            return true;
        }

        // Check if multiple hotels mode with hotels selected
        if (!empty($config['allowMultipleHotels']) && is_array($config['selectedHotels']) && count($config['selectedHotels']) > 0) {
            return true;
        }

        return false;
    }

    private function get_lighthouse_form_id(array $bar): string {
        return 'wp-mbb-mlb-form-' . sanitize_key($bar['id'] ?? uniqid('bar_', false));
    }

    private function inject_lighthouse_button(array $items, array $bar): array {
        $button = $this->build_lighthouse_item($bar);

        if (!$button) {
            return $items;
        }

        $insert_at = max(1, (int) floor(count($items) / 2));
        array_splice($items, $insert_at, 0, [$button]);

        return $items;
    }

    private function build_lighthouse_item(array $bar): ?array {
        $config = $bar['lighthouseIntegration'] ?? [];
        $booking_base_url = 'https://new.differenthotels.be/book-now/';

        // Handle multiple hotels mode
        if (!empty($config['allowMultipleHotels']) && is_array($config['selectedHotels']) && count($config['selectedHotels']) > 0) {
            $label = __('Book', 'mobile-bottom-bar');
            
            return [
                'label' => $label,
                'href' => '#',
                'icon' => 'calendar',
                'target' => '_self',
                'rel' => '',
                'is_active' => false,
                'type' => 'hotel-booking-multi',
                'payload' => [
                    'hotels' => $config['selectedHotels'],
                    'bookingUrl' => $booking_base_url,
                    'isMultiple' => true,
                ],
                'linkTargetBehavior' => 'self',
            ];
        }

        // Handle single hotel mode - direct redirect
        $hotel_id = $config['hotelId'] ?? '';

        if ($hotel_id === '') {
            return null;
        }

        $hotel_name = $config['hotelName'] ?? '';
        $label = is_string($hotel_name) && $hotel_name !== '' ? $hotel_name : __('Book', 'mobile-bottom-bar');

        return [
            'label' => $label,
            'href' => $booking_base_url . '?Arrival=&Departure=&hotel_id=' . urlencode($hotel_id),
            'icon' => 'calendar',
            'target' => '_self',
            'rel' => '',
            'is_active' => false,
            'type' => 'link',
            'payload' => [
                'href' => $booking_base_url . '?Arrival=&Departure=&hotel_id=' . urlencode($hotel_id),
                'linkTarget' => 'self',
            ],
            'linkTargetBehavior' => 'self',
        ];
    }

    private function render_lighthouse_form(array $bar): void {
        // No longer needed - hotel selection is handled entirely by JS modal and redirect
        return;
    }

    private function maybe_include_lighthouse_templates(): void {
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

    private function render_icon_markup(?string $icon): string {
        if (empty($icon) || empty(self::ICON_SVGS[$icon])) {
            return '<span class="wp-mbb__dot"></span>';
        }

        return '<svg class="wp-mbb__svg" viewBox="0 0 24 24" role="presentation" focusable="false" aria-hidden="true">'
            . self::ICON_SVGS[$icon]
            . '</svg>';
    }
}

register_activation_hook(
    __FILE__,
    static function (): void {
        if (false === get_option(Mobile_Bottom_Bar_Plugin::OPTION_KEY, false)) {
            update_option(Mobile_Bottom_Bar_Plugin::OPTION_KEY, []);
        }
    }
);

Mobile_Bottom_Bar_Plugin::instance();
