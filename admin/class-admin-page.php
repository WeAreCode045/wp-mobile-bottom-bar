<?php
/**
 * Admin interface for Mobile Bottom Bar plugin
 * Pure PHP implementation without React
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get settings and data
$settings = isset($this) ? $this->get_settings() : [];
$bars = $settings['bars'] ?? [];
$menus = isset($this) ? $this->get_menus() : [];
$mylighthouse = isset($this) ? $this->get_mylighthouse_bootstrap() : [];

// Get active bar from URL parameter
$active_bar_id = isset($_GET['bar']) ? sanitize_key($_GET['bar']) : (!empty($bars) ? key($bars) : null);
$active_bar = $active_bar_id && isset($bars[$active_bar_id]) ? $bars[$active_bar_id] : null;
?>

<div class="wrap wp-mbb-admin">
    <h1><?php esc_html_e('Mobile Bottom Bar', 'mobile-bottom-bar'); ?></h1>

    <div class="wp-mbb-admin-layout">
        <!-- Sidebar -->
        <aside class="wp-mbb-sidebar">
            <div class="wp-mbb-sidebar-section">
                <h3><?php esc_html_e('Bars', 'mobile-bottom-bar'); ?></h3>
                <div class="wp-mbb-bars-list" id="wp-mbb-bars-list">
                    <?php if (!empty($bars)): ?>
                        <?php foreach ($bars as $bar_id => $bar): ?>
                            <div class="wp-mbb-bar-item <?php echo $bar_id === $active_bar_id ? 'active' : ''; ?>" data-bar-id="<?php echo esc_attr($bar_id); ?>">
                                <a href="<?php echo esc_url(add_query_arg('bar', $bar_id)); ?>" class="wp-mbb-bar-link">
                                    <strong><?php echo esc_html($bar['name'] ?? __('Unnamed Bar', 'mobile-bottom-bar')); ?></strong>
                                    <small><?php echo esc_html($bar['position'] ?? 'bottom'); ?></small>
                                </a>
                                <div class="wp-mbb-bar-actions">
                                    <button type="button" class="wp-mbb-btn-delete" data-bar-id="<?php echo esc_attr($bar_id); ?>">×</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="button button-primary wp-mbb-btn-add-bar" id="wp-mbb-add-bar">
                    + <?php esc_html_e('Add Bar', 'mobile-bottom-bar'); ?>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="wp-mbb-main">
            <?php if ($active_bar): ?>
                <form class="wp-mbb-form" id="wp-mbb-bar-form" method="post" action="">
                    <input type="hidden" name="bar_id" value="<?php echo esc_attr($active_bar_id); ?>">
                    <?php wp_nonce_field('wp_mbb_save_bar', 'wp_mbb_nonce'); ?>

                    <!-- Navigation Tabs -->
                    <div class="wp-mbb-tabs-wrapper">
                        <ul class="wp-mbb-tabs-nav">
                            <li><a href="#tab-general" class="wp-mbb-tab-link active"><?php esc_html_e('General', 'mobile-bottom-bar'); ?></a></li>
                            <li><a href="#tab-styling" class="wp-mbb-tab-link"><?php esc_html_e('Styling', 'mobile-bottom-bar'); ?></a></li>
                            <li><a href="#tab-content" class="wp-mbb-tab-link"><?php esc_html_e('Content', 'mobile-bottom-bar'); ?></a></li>
                        </ul>

                        <!-- General Tab -->
                        <div id="tab-general" class="wp-mbb-tab-panel active">
                            <h2><?php esc_html_e('Bar Settings', 'mobile-bottom-bar'); ?></h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="bar-name"><?php esc_html_e('Bar Name', 'mobile-bottom-bar'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="bar-name" name="bar_name" class="regular-text" 
                                            value="<?php echo esc_attr($active_bar['name'] ?? ''); ?>" required>
                                        <p class="description"><?php esc_html_e('Internal name for organization', 'mobile-bottom-bar'); ?></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="bar-menu"><?php esc_html_e('Menu', 'mobile-bottom-bar'); ?></label>
                                    </th>
                                    <td>
                                        <select id="bar-menu" name="bar_menu" class="regular-text">
                                            <option value="">-- <?php esc_html_e('Select Menu', 'mobile-bottom-bar'); ?> --</option>
                                            <?php foreach ($menus as $menu): ?>
                                                <option value="<?php echo esc_attr($menu['id']); ?>" 
                                                    <?php selected($active_bar['menu'] ?? '', $menu['id']); ?>>
                                                    <?php echo esc_html($menu['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="bar-position"><?php esc_html_e('Position', 'mobile-bottom-bar'); ?></label>
                                    </th>
                                    <td>
                                        <select id="bar-position" name="bar_position" class="regular-text">
                                            <option value="bottom" <?php selected($active_bar['position'] ?? 'bottom', 'bottom'); ?>>
                                                <?php esc_html_e('Bottom', 'mobile-bottom-bar'); ?>
                                            </option>
                                            <option value="top" <?php selected($active_bar['position'] ?? 'bottom', 'top'); ?>>
                                                <?php esc_html_e('Top', 'mobile-bottom-bar'); ?>
                                            </option>
                                        </select>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php esc_html_e('Display', 'mobile-bottom-bar'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="show_labels" value="1"
                                                <?php checked($active_bar['showLabels'] ?? false); ?>>
                                            <?php esc_html_e('Show Labels', 'mobile-bottom-bar'); ?>
                                        </label>
                                    </td>
                                </tr>
                            </table>

                            <!-- Lighthouse Integration -->
                            <?php if (!empty($mylighthouse['hotels'])): ?>
                                <h3><?php esc_html_e('MyLighthouse Booking Calendar', 'mobile-bottom-bar'); ?></h3>
                                <table class="form-table">
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Enable', 'mobile-bottom-bar'); ?></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="lighthouse_enabled" value="1" class="wp-mbb-lighthouse-toggle"
                                                    <?php checked($active_bar['lighthouseIntegration']['enabled'] ?? false); ?>>
                                                <?php esc_html_e('Enable Booking Calendar', 'mobile-bottom-bar'); ?>
                                            </label>
                                        </td>
                                    </tr>

                                    <tr class="wp-mbb-lighthouse-row" style="display: <?php echo ($active_bar['lighthouseIntegration']['enabled'] ?? false) ? 'table-row' : 'none'; ?>;">
                                        <th scope="row">
                                            <label for="lighthouse-hotel"><?php esc_html_e('Hotel Selection', 'mobile-bottom-bar'); ?></label>
                                        </th>
                                        <td>
                                            <select id="lighthouse-hotel" name="lighthouse_hotel" class="regular-text wp-mbb-lighthouse-hotel">
                                                <option value="">-- <?php esc_html_e('Select Hotel', 'mobile-bottom-bar'); ?> --</option>
                                                <?php if ($active_bar['lighthouseIntegration']['enableMultiHotel'] ?? false): ?>
                                                    <option value="__multi-hotel__" <?php selected($active_bar['lighthouseIntegration']['hotelId'] ?? '', '__multi-hotel__'); ?>>
                                                        <?php esc_html_e('Multi-Hotel (User Selection)', 'mobile-bottom-bar'); ?>
                                                    </option>
                                                <?php endif; ?>
                                                <?php foreach ($mylighthouse['hotels'] as $hotel): ?>
                                                    <option value="<?php echo esc_attr($hotel['id']); ?>" 
                                                        <?php selected($active_bar['lighthouseIntegration']['hotelId'] ?? '', $hotel['id']); ?>>
                                                        <?php echo esc_html($hotel['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>

                                    <tr class="wp-mbb-lighthouse-row" style="display: <?php echo ($active_bar['lighthouseIntegration']['enabled'] ?? false) ? 'table-row' : 'none'; ?>;">
                                        <th scope="row"><?php esc_html_e('Multi-Hotel', 'mobile-bottom-bar'); ?></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="lighthouse_multi_hotel" value="1" class="wp-mbb-lighthouse-multi-toggle"
                                                    <?php checked($active_bar['lighthouseIntegration']['enableMultiHotel'] ?? false); ?>>
                                                <?php esc_html_e('Allow users to select hotel', 'mobile-bottom-bar'); ?>
                                            </label>
                                        </td>
                                    </tr>

                                    <tr class="wp-mbb-lighthouse-hotels" style="display: <?php echo ($active_bar['lighthouseIntegration']['enableMultiHotel'] ?? false) ? 'table-row' : 'none'; ?>;">
                                        <th scope="row"><?php esc_html_e('Available Hotels', 'mobile-bottom-bar'); ?></th>
                                        <td>
                                            <fieldset>
                                                <?php
                                                $selected_hotels = $active_bar['lighthouseIntegration']['selectedHotels'] ?? [];
                                                foreach ($mylighthouse['hotels'] as $hotel):
                                                    $hotel_id = esc_attr($hotel['id']);
                                                    ?>
                                                    <label style="display: block; margin: 5px 0;">
                                                        <input type="checkbox" name="lighthouse_selected_hotels[]" value="<?php echo $hotel_id; ?>"
                                                            <?php checked(in_array($hotel_id, $selected_hotels, true)); ?>>
                                                        <?php echo esc_html($hotel['name']); ?>
                                                    </label>
                                                <?php endforeach; ?>
                                            </fieldset>
                                        </td>
                                    </tr>
                                </table>
                            <?php endif; ?>
                        </div>

                        <!-- Styling Tab -->
                        <div id="tab-styling" class="wp-mbb-tab-panel">
                            <h2><?php esc_html_e('Styling', 'mobile-bottom-bar'); ?></h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="bg-color"><?php esc_html_e('Background Color', 'mobile-bottom-bar'); ?></label>
                                    </th>
                                    <td>
                                        <input type="color" id="bg-color" name="bg_color" 
                                            value="<?php echo esc_attr($active_bar['styling']['backgroundColor'] ?? '#ffffff'); ?>">
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="text-color"><?php esc_html_e('Text Color', 'mobile-bottom-bar'); ?></label>
                                    </th>
                                    <td>
                                        <input type="color" id="text-color" name="text_color" 
                                            value="<?php echo esc_attr($active_bar['styling']['textColor'] ?? '#000000'); ?>">
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="icon-color"><?php esc_html_e('Icon Color', 'mobile-bottom-bar'); ?></label>
                                    </th>
                                    <td>
                                        <input type="color" id="icon-color" name="icon_color" 
                                            value="<?php echo esc_attr($active_bar['styling']['iconColor'] ?? '#000000'); ?>">
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="height"><?php esc_html_e('Height (px)', 'mobile-bottom-bar'); ?></label>
                                    </th>
                                    <td>
                                        <input type="number" id="height" name="height" class="small-text"
                                            value="<?php echo esc_attr($active_bar['styling']['height'] ?? 60); ?>" min="40" max="200">
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Content Tab -->
                        <div id="tab-content" class="wp-mbb-tab-panel">
                            <h2><?php esc_html_e('Content & Menu', 'mobile-bottom-bar'); ?></h2>
                            <p><?php esc_html_e('Configure menu items and custom content here.', 'mobile-bottom-bar'); ?></p>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="wp-mbb-form-footer">
                        <?php submit_button(__('Save Settings', 'mobile-bottom-bar'), 'primary', 'submit', true); ?>
                        <span class="wp-mbb-status-message" id="wp-mbb-status"></span>
                    </div>
                </form>
            <?php else: ?>
                <div class="notice notice-info">
                    <p><?php esc_html_e('No bars found. Click "Add Bar" to get started.', 'mobile-bottom-bar'); ?></p>
                </div>
            <?php endif; ?>
        </main>

        <!-- Preview Sidebar -->
        <aside class="wp-mbb-preview">
            <h3><?php esc_html_e('Preview', 'mobile-bottom-bar'); ?></h3>
            <div class="wp-mbb-preview-content" id="wp-mbb-preview">
                <p><?php esc_html_e('Mobile bar preview will appear here', 'mobile-bottom-bar'); ?></p>
            </div>
        </aside>
    </div>
</div>
