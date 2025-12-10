<?php
/**
 * Frontend Controller
 *
 * Handles frontend rendering and bar display
 *
 * @package Mobile_Bottom_Bar
 */

if (!defined('ABSPATH')) {
    exit;
}

class MBB_Frontend {
    
    private $settings;
    private $lighthouse;

    public function __construct(MBB_Settings $settings, MBB_Lighthouse $lighthouse) {
        $this->settings = $settings;
        $this->lighthouse = $lighthouse;
    }

    public function init(): void {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('wp_footer', [$this, 'render_frontend_bar']);
        add_filter('body_class', [$this, 'filter_body_class']);
    }

    public function enqueue_frontend_assets(): void {
        if (is_admin()) {
            return;
        }

        $all_settings = $this->settings->get_settings();
        $bar = $this->select_active_bar($all_settings['bars'] ?? []);

        if (!$bar || !$this->bar_has_frontend_items($bar)) {
            return;
        }

        $should_prepare_lighthouse = $this->lighthouse->should_render_lighthouse_button($bar);

        if ($should_prepare_lighthouse) {
            $this->lighthouse->ensure_lighthouse_assets_enqueued();
        }

        $api_key = $all_settings['contactFormSettings']['googleApiKey'] ?? '';
        if (!empty($api_key)) {
            wp_enqueue_script(
                'google-maps-api-frontend',
                'https://maps.googleapis.com/maps/api/js?key=' . urlencode($api_key) . '&libraries=places',
                [],
                null,
                false
            );
        }

        wp_enqueue_style(
            'mobile-bottom-bar-frontend',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/frontend.css',
            [],
            '0.1.0'
        );

        wp_enqueue_script(
            'mobile-bottom-bar-frontend-js',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/frontend.js',
            !empty($api_key) ? ['google-maps-api-frontend'] : [],
            '0.1.0',
            true
        );

        wp_localize_script(
            'mobile-bottom-bar-frontend-js',
            'wpMbbConfig',
            [
                'pluginUrl' => plugin_dir_url(dirname(__FILE__)),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_rest'),
                'googleApiKey' => $api_key
            ]
        );
    }

    public function render_frontend_bar(): void {
        if (is_admin()) {
            return;
        }

        $all_settings = $this->settings->get_settings();
        $bar = $this->select_active_bar($all_settings['bars'] ?? []);

        if (!$bar || !$this->bar_has_frontend_items($bar)) {
            return;
        }

        $global_style = $all_settings['globalStyle'] ?? $this->settings->get_default_style();
        $bar = $this->settings->apply_global_style($bar, $global_style);

        $should_render_lighthouse = $this->lighthouse->should_render_lighthouse_button($bar);
        $items = $this->resolve_frontend_items($bar);

        if ($should_render_lighthouse) {
            $items = $this->lighthouse->inject_lighthouse_button($items, $bar);
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

        echo '<nav id="wp-mobile-bottom-bar" class="' . esc_attr(implode(' ', $classes)) . '" style="' . esc_attr($style_attribute) . ';display:none" aria-label="' . esc_attr__('Mobile bottom navigation', 'mobile-bottom-bar') . '"' . $data_attributes . '>';
        $this->render_layout($layout, $items, $bar);
        echo '</nav>';

        echo "<script>(function(){var nav=document.getElementById('wp-mobile-bottom-bar');if(!nav)return;function update(){try{if(window.innerWidth<=767||nav.classList.contains('wp-mbb--desktop-sidebar')){nav.style.display='';document.body.classList.toggle('wp-mobile-bottom-bar-active', window.innerWidth<=767);}else{nav.style.display='none';document.body.classList.remove('wp-mobile-bottom-bar-active');}}catch(e){} }update();window.addEventListener('resize',function(){update();});})();</script>";

        if ($should_render_lighthouse) {
            $this->lighthouse->render_lighthouse_form($bar);
            $this->lighthouse->render_multi_hotel_modal_template();
        }

        if ($this->has_mail_item($items)) {
            $this->render_contact_form_modal();
        }
    }

    private function has_mail_item(array $items): bool {
        foreach ($items as $item) {
            if (isset($item['type']) && $item['type'] === 'mail') {
                return true;
            }
        }
        return false;
    }

    private function render_contact_form_modal(): void {
        static $rendered = false;
        
        if ($rendered) {
            return;
        }
        
        $template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/contact-form-modal.php';
        
        if (file_exists($template_path)) {
            include $template_path;
            $rendered = true;
        }
    }

    private function normalize_layout(string $layout): string {
        return in_array($layout, MBB_Settings::SUPPORTED_LAYOUTS, true) ? $layout : 'standard';
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

        if (!empty($item['type']) && in_array($item['type'], ['mylighthouse', 'mylighthouse-multi'], true)) {
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

        $is_calendar = !empty($item['type']) && in_array($item['type'], ['mylighthouse', 'mylighthouse-multi'], true);
        $book_label_markup = $is_calendar ? '' : '';

        return '<a class="' . esc_attr(implode(' ', $item_classes)) . '" href="' . esc_url($item['href'] ?? '#') . '"' . $target . $rel . $data_attributes . '>'
            . '<span class="wp-mbb__icon" aria-hidden="true">' . $this->render_icon_markup($item['icon'] ?? null) . '</span>'
            . $label_markup
            . $book_label_markup
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
        $corner_defaults = $this->settings->get_default_sidebar_corner_radius();
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

    public function filter_body_class(array $classes): array {
        if (is_admin()) {
            return $classes;
        }

        $all_settings = $this->settings->get_settings();
        $bar = $this->select_active_bar($all_settings['bars'] ?? []);

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

    public function select_active_bar(array $bars): ?array {
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
        $allowed = ['link', 'phone', 'mail', 'modal', 'wysiwyg', 'map'];

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
            'mapAddress' => $item['mapAddress'] ?? '',
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
            case 'map':
                return '#';
            case 'link':
            default:
                return !empty($item['href']) ? $item['href'] : '#';
        }
    }

    private function map_link_target_to_html(string $link_target): string {
        return $link_target === 'blank' ? '_blank' : '_self';
    }

    private function render_icon_markup(?string $icon): string {
        if (empty($icon) || empty(MBB_Settings::ICON_SVGS[$icon])) {
            return '<span class="wp-mbb__dot"></span>';
        }

        return '<svg class="wp-mbb__svg" viewBox="0 0 24 24" role="presentation" focusable="false" aria-hidden="true">'
            . MBB_Settings::ICON_SVGS[$icon]
            . '</svg>';
    }
}
