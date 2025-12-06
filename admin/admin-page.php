<?php
/**
 * Admin page template for Mobile Bottom Bar plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$settings = $this->get_settings();
$bars = $settings['bars'] ?? [];
$global_style = $settings['globalStyle'] ?? [];
$default_modal_style = $settings['defaultModalStyle'] ?? [];
$default_custom_menu = $settings['defaultCustomMenu'] ?? [];
$menus = $this->get_menus();
$pages = $this->get_pages();
$mylighthouse_meta = $this->get_mylighthouse_bootstrap();
$has_bars = !empty($bars);
$active_bar_id = $has_bars ? key($bars) : '';
$active_bar = $has_bars ? reset($bars) : null;
?>

<div class="wp-mbb-admin-container">
    <div class="wp-mbb-admin-header">
        <h1><?php esc_html_e('Mobile Bottom Bar', 'mobile-bottom-bar'); ?></h1>
        <p class="description"><?php esc_html_e('Configure your mobile bottom bar with menus, styles, and more', 'mobile-bottom-bar'); ?></p>
    </div>

    <div class="wp-mbb-admin-wrapper">
        <!-- Sidebar Navigation -->
        <div class="wp-mbb-admin-sidebar">
            <div class="wp-mbb-bars-list">
                <h3><?php esc_html_e('Bars', 'mobile-bottom-bar'); ?></h3>
                <ul class="wp-mbb-bars-nav" id="wp-mbb-bars-nav">
                    <?php foreach ($bars as $bar_id => $bar): ?>
                        <li class="<?php echo $bar_id === $active_bar_id ? 'active' : ''; ?>">
                            <a href="#" class="wp-mbb-bar-link" data-bar-id="<?php echo esc_attr($bar_id); ?>">
                                <?php echo esc_html($bar['name'] ?? 'Bar'); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <button class="button button-secondary wp-mbb-add-bar" id="wp-mbb-add-bar">
                    <?php esc_html_e('Add Bar', 'mobile-bottom-bar'); ?>
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="wp-mbb-admin-content">
            <?php if ($active_bar): ?>
                <div class="wp-mbb-bar-editor" data-bar-id="<?php echo esc_attr($active_bar_id); ?>">
                    <!-- Tabs -->
                    <div class="wp-mbb-tabs">
                        <ul class="wp-mbb-tabs-list">
                            <li><a href="#" class="wp-mbb-tab-link active" data-tab="configuration">Configuration</a></li>
                            <li><a href="#" class="wp-mbb-tab-link" data-tab="styling">Styling</a></li>
                            <li><a href="#" class="wp-mbb-tab-link" data-tab="advanced">Advanced</a></li>
                        </ul>

                        <!-- Configuration Tab -->
                        <div class="wp-mbb-tab-content active" id="tab-configuration">
                            <h2><?php esc_html_e('Bar Configuration', 'mobile-bottom-bar'); ?></h2>

                            <!-- Bar Name -->
                            <div class="wp-mbb-form-group">
                                <label><?php esc_html_e('Bar Name', 'mobile-bottom-bar'); ?></label>
                                <input type="text" class="regular-text wp-mbb-bar-name" value="<?php echo esc_attr($active_bar['name'] ?? ''); ?>">
                            </div>

                            <!-- Menu Selection -->
                            <div class="wp-mbb-form-group">
                                <label><?php esc_html_e('Menu', 'mobile-bottom-bar'); ?></label>
                                <select class="regular-text wp-mbb-bar-menu">
                                    <option value="">-- Select a menu --</option>
                                    <?php foreach ($menus as $menu): ?>
                                        <option value="<?php echo esc_attr($menu['id']); ?>" <?php selected($active_bar['menu'] ?? '', $menu['id']); ?>>
                                            <?php echo esc_html($menu['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Position -->
                            <div class="wp-mbb-form-group">
                                <label><?php esc_html_e('Position', 'mobile-bottom-bar'); ?></label>
                                <select class="regular-text wp-mbb-bar-position">
                                    <option value="bottom" <?php selected($active_bar['position'] ?? 'bottom', 'bottom'); ?>>Bottom</option>
                                    <option value="top" <?php selected($active_bar['position'] ?? 'bottom', 'top'); ?>>Top</option>
                                </select>
                            </div>

                            <!-- Show Labels -->
                            <div class="wp-mbb-form-group">
                                <label>
                                    <input type="checkbox" class="wp-mbb-show-labels" <?php checked($active_bar['showLabels'] ?? false); ?>>
                                    <?php esc_html_e('Show Labels', 'mobile-bottom-bar'); ?>
                                </label>
                            </div>

                            <!-- Lighthouse Integration -->
                            <div class="wp-mbb-form-section">
                                <h3><?php esc_html_e('Booking Calendar (MyLighthouse)', 'mobile-bottom-bar'); ?></h3>
                                
                                <div class="wp-mbb-form-group">
                                    <label>
                                        <input type="checkbox" class="wp-mbb-lighthouse-enabled" <?php checked($active_bar['lighthouseIntegration']['enabled'] ?? false); ?>>
                                        <?php esc_html_e('Enable Booking Calendar', 'mobile-bottom-bar'); ?>
                                    </label>
                                </div>

                                <?php if (!empty($mylighthouse_meta['hotels'])): ?>
                                    <div class="wp-mbb-form-group wp-mbb-lighthouse-settings" style="display: <?php echo $active_bar['lighthouseIntegration']['enabled'] ?? false ? 'block' : 'none'; ?>;">
                                        <label><?php esc_html_e('Hotel to Book', 'mobile-bottom-bar'); ?></label>
                                        <select class="regular-text wp-mbb-lighthouse-hotel">
                                            <option value="">-- Select a hotel --</option>
                                            <?php if ($active_bar['lighthouseIntegration']['enableMultiHotel'] ?? false): ?>
                                                <option value="__multi-hotel__" <?php selected($active_bar['lighthouseIntegration']['hotelId'] ?? '', '__multi-hotel__'); ?>>
                                                    Multi-Hotel (User Selection)
                                                </option>
                                            <?php endif; ?>
                                            <?php foreach ($mylighthouse_meta['hotels'] as $hotel): ?>
                                                <option value="<?php echo esc_attr($hotel['id']); ?>" <?php selected($active_bar['lighthouseIntegration']['hotelId'] ?? '', $hotel['id']); ?>>
                                                    <?php echo esc_html($hotel['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="wp-mbb-form-group wp-mbb-lighthouse-settings" style="display: <?php echo $active_bar['lighthouseIntegration']['enabled'] ?? false ? 'block' : 'none'; ?>;">
                                        <label>
                                            <input type="checkbox" class="wp-mbb-lighthouse-multi-hotel" <?php checked($active_bar['lighthouseIntegration']['enableMultiHotel'] ?? false); ?>>
                                            <?php esc_html_e('Allow user to select hotel', 'mobile-bottom-bar'); ?>
                                        </label>
                                    </div>

                                    <!-- Hotel Selection Checkboxes -->
                                    <div class="wp-mbb-form-group wp-mbb-lighthouse-hotels" style="display: <?php echo ($active_bar['lighthouseIntegration']['enableMultiHotel'] ?? false) ? 'block' : 'none'; ?>;">
                                        <label><?php esc_html_e('Available Hotels', 'mobile-bottom-bar'); ?></label>
                                        <div class="wp-mbb-hotels-list">
                                            <?php
                                            $selected_hotels = $active_bar['lighthouseIntegration']['selectedHotels'] ?? [];
                                            foreach ($mylighthouse_meta['hotels'] as $hotel):
                                                ?>
                                                <label class="wp-mbb-hotel-checkbox">
                                                    <input type="checkbox" class="wp-mbb-hotel-select" value="<?php echo esc_attr($hotel['id']); ?>" 
                                                        <?php checked(in_array($hotel['id'], $selected_hotels, true)); ?>>
                                                    <?php echo esc_html($hotel['name']); ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="description">
                                        <?php esc_html_e('Install and activate the MyLighthouse Booker plugin to enable booking calendar integration.', 'mobile-bottom-bar'); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Styling Tab -->
                        <div class="wp-mbb-tab-content" id="tab-styling">
                            <h2><?php esc_html_e('Styling', 'mobile-bottom-bar'); ?></h2>

                            <!-- Background Color -->
                            <div class="wp-mbb-form-group">
                                <label><?php esc_html_e('Background Color', 'mobile-bottom-bar'); ?></label>
                                <input type="color" class="wp-mbb-bar-bg-color" value="<?php echo esc_attr($active_bar['styling']['backgroundColor'] ?? '#ffffff'); ?>">
                            </div>

                            <!-- Text Color -->
                            <div class="wp-mbb-form-group">
                                <label><?php esc_html_e('Text Color', 'mobile-bottom-bar'); ?></label>
                                <input type="color" class="wp-mbb-bar-text-color" value="<?php echo esc_attr($active_bar['styling']['textColor'] ?? '#000000'); ?>">
                            </div>

                            <!-- Icon Color -->
                            <div class="wp-mbb-form-group">
                                <label><?php esc_html_e('Icon Color', 'mobile-bottom-bar'); ?></label>
                                <input type="color" class="wp-mbb-bar-icon-color" value="<?php echo esc_attr($active_bar['styling']['iconColor'] ?? '#000000'); ?>">
                            </div>

                            <!-- Height -->
                            <div class="wp-mbb-form-group">
                                <label><?php esc_html_e('Height (px)', 'mobile-bottom-bar'); ?></label>
                                <input type="number" class="small-text wp-mbb-bar-height" value="<?php echo esc_attr($active_bar['styling']['height'] ?? 60); ?>" min="40" max="200">
                            </div>
                        </div>

                        <!-- Advanced Tab -->
                        <div class="wp-mbb-tab-content" id="tab-advanced">
                            <h2><?php esc_html_e('Advanced', 'mobile-bottom-bar'); ?></h2>
                            <p><?php esc_html_e('Advanced settings coming soon...', 'mobile-bottom-bar'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="wp-mbb-admin-footer">
                    <button class="button button-primary wp-mbb-save-settings" id="wp-mbb-save-settings">
                        <?php esc_html_e('Save Settings', 'mobile-bottom-bar'); ?>
                    </button>
                    <span class="wp-mbb-save-status" id="wp-mbb-save-status"></span>
                </div>
            <?php else: ?>
                <p><?php esc_html_e('No bars created yet. Click "Add Bar" to get started.', 'mobile-bottom-bar'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Preview -->
        <div class="wp-mbb-admin-preview">
            <div class="wp-mbb-preview-header">
                <h3><?php esc_html_e('Mobile Preview', 'mobile-bottom-bar'); ?></h3>
            </div>
            <div class="wp-mbb-preview-frame" id="wp-mbb-preview-frame">
                <!-- Preview will be rendered here -->
            </div>
        </div>
    </div>
</div>
