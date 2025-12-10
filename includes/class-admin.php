<?php
/**
 * Admin Controller
 *
 * Handles admin page, assets, and REST API
 *
 * @package Mobile_Bottom_Bar
 */

if (!defined('ABSPATH')) {
    exit;
}

class MBB_Admin {
    
    private const SCRIPT_HANDLE = 'mobile-bottom-bar-admin';
    private const VERSION = '0.1.0';
    
    private $settings;
    private $lighthouse;

    public function __construct(MBB_Settings $settings, MBB_Lighthouse $lighthouse) {
        $this->settings = $settings;
        $this->lighthouse = $lighthouse;
    }

    public function init(): void {
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
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

        wp_register_script(
            'easepick-datetime',
            'https://cdn.jsdelivr.net/npm/@easepick/datetime@1.2.1/dist/index.umd.js',
            array(),
            '1.2.1',
            false
        );

        wp_register_script(
            'easepick-base-plugin',
            'https://cdn.jsdelivr.net/npm/@easepick/base-plugin@1.2.1/dist/index.umd.js',
            array('easepick-datetime'),
            '1.2.1',
            false
        );

        wp_register_script(
            'easepick-core',
            plugins_url('/assets/vendor/easepick/easepick.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
            array('easepick-datetime', 'easepick-base-plugin'),
            '1.2.1',
            false
        );

        wp_register_script(
            'easepick-range',
            plugins_url('/assets/vendor/easepick/easepick-range.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
            array('easepick-core', 'easepick-base-plugin'),
            '1.2.1',
            false
        );

        wp_register_script(
            'easepick-lock',
            'https://cdn.jsdelivr.net/npm/@easepick/lock-plugin@1.2.1/dist/index.umd.min.js',
            array('easepick-core', 'easepick-base-plugin'),
            '1.2.1',
            true
        );

        wp_register_script(
            'easepick-wrapper',
            plugins_url('/assets/vendor/easepick/easepick-wrapper.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
            array('easepick-core', 'easepick-range', 'easepick-lock'),
            '1.0.0',
            true
        );

        $settings = $this->settings->get_settings();
        $api_key = $settings['contactFormSettings']['googleApiKey'] ?? '';
        
        wp_enqueue_script(
            'google-maps-places',
            'https://maps.googleapis.com/maps/api/js?key=' . urlencode($api_key) . '&libraries=places',
            [],
            null,
            false
        );

        $entry = $this->get_manifest_entry();
        $asset_base = plugin_dir_url(dirname(__FILE__)) . 'assets/js/';

        if (!$entry) {
            wp_enqueue_script(self::SCRIPT_HANDLE, '', [], self::VERSION, true);
            wp_add_inline_script(self::SCRIPT_HANDLE, 'console.error("Mobile Bottom Bar assets missing. Run npm run build before loading the admin page.");');
            return;
        }

        wp_enqueue_script(
            self::SCRIPT_HANDLE,
            $asset_base . $entry['file'],
            ['google-maps-places'],
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
                'settings' => $settings,
                'mylighthouse' => $this->lighthouse->get_mylighthouse_bootstrap(),
            ]
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
                    'args' => $this->settings->get_rest_args(),
                ],
            ]
        );
    }

    public function rest_get_settings(): array {
        return $this->settings->get_settings();
    }

    public function rest_save_settings(\WP_REST_Request $request): \WP_REST_Response {
        $raw_data = (array) $request->get_json_params();
        
        error_log('Mobile Bottom Bar - Raw data received: ' . print_r($raw_data, true));
        
        $data = $this->settings->save_settings($raw_data);
        
        error_log('Mobile Bottom Bar - Sanitized data: ' . print_r($data, true));

        return new \WP_REST_Response($data, 200);
    }

    public function permissions_check(): bool {
        return current_user_can('manage_options');
    }

    private function get_manifest_entry(): ?array {
        $base = plugin_dir_path(dirname(__FILE__)) . 'assets/js/';
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
}
