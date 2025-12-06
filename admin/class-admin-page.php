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
                            <p><?php esc_html_e('Add custom menu items with icons, links, and actions.', 'mobile-bottom-bar'); ?></p>

                            <!-- Custom Items List -->
                            <div class="wp-mbb-custom-items-section">
                                <h3><?php esc_html_e('Custom Menu Items', 'mobile-bottom-bar'); ?></h3>
                                <div id="wp-mbb-custom-items-list" class="wp-mbb-items-list">
                                    <?php 
                                    $custom_items = $active_bar['customItems'] ?? [];
                                    if (!empty($custom_items)):
                                        foreach ($custom_items as $item):
                                            $item_id = esc_attr($item['id']);
                                            $item_label = esc_attr($item['label'] ?? '');
                                            $item_icon = esc_attr($item['icon'] ?? 'home');
                                            $item_type = esc_attr($item['type'] ?? 'link');
                                    ?>
                                            <div class="wp-mbb-custom-item" data-item-id="<?php echo $item_id; ?>">
                                                <div class="wp-mbb-item-header">
                                                    <span class="wp-mbb-item-label"><?php echo $item_label ?: __('(Untitled)', 'mobile-bottom-bar'); ?></span>
                                                    <span class="wp-mbb-item-type"><?php echo $item_type; ?></span>
                                                    <button type="button" class="wp-mbb-item-delete" data-item-id="<?php echo $item_id; ?>">×</button>
                                                </div>
                                                <div class="wp-mbb-item-details" style="display: none;">
                                                    <input type="hidden" class="wp-mbb-item-id" value="<?php echo $item_id; ?>">
                                                    <table class="form-table">
                                                        <tr>
                                                            <th scope="row">
                                                                <label><?php esc_html_e('Label', 'mobile-bottom-bar'); ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="text" class="wp-mbb-item-label-input regular-text" value="<?php echo $item_label; ?>" placeholder="<?php esc_attr_e('Item label', 'mobile-bottom-bar'); ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">
                                                                <label><?php esc_html_e('Icon', 'mobile-bottom-bar'); ?></label>
                                                            </th>
                                                            <td>
                                                                <select class="wp-mbb-item-icon-select regular-text">
                                                                    <option value="home" <?php selected($item_icon, 'home'); ?>>Home</option>
                                                                    <option value="search" <?php selected($item_icon, 'search'); ?>>Search</option>
                                                                    <option value="shopping-bag" <?php selected($item_icon, 'shopping-bag'); ?>>Shopping Bag</option>
                                                                    <option value="user" <?php selected($item_icon, 'user'); ?>>User</option>
                                                                    <option value="heart" <?php selected($item_icon, 'heart'); ?>>Heart</option>
                                                                    <option value="bell" <?php selected($item_icon, 'bell'); ?>>Bell</option>
                                                                    <option value="settings" <?php selected($item_icon, 'settings'); ?>>Settings</option>
                                                                    <option value="bookmark" <?php selected($item_icon, 'bookmark'); ?>>Bookmark</option>
                                                                    <option value="phone" <?php selected($item_icon, 'phone'); ?>>Phone</option>
                                                                    <option value="gift" <?php selected($item_icon, 'gift'); ?>>Gift</option>
                                                                    <option value="mail" <?php selected($item_icon, 'mail'); ?>>Mail</option>
                                                                    <option value="map" <?php selected($item_icon, 'map'); ?>>Map</option>
                                                                    <option value="calendar" <?php selected($item_icon, 'calendar'); ?>>Calendar</option>
                                                                    <option value="hotel" <?php selected($item_icon, 'hotel'); ?>>Hotel</option>
                                                                </select>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">
                                                                <label><?php esc_html_e('Type', 'mobile-bottom-bar'); ?></label>
                                                            </th>
                                                            <td>
                                                                <select class="wp-mbb-item-type-select regular-text">
                                                                    <option value="link" <?php selected($item_type, 'link'); ?>>Link</option>
                                                                    <option value="phone" <?php selected($item_type, 'phone'); ?>>Phone</option>
                                                                    <option value="mail" <?php selected($item_type, 'mail'); ?>>Email</option>
                                                                    <option value="modal" <?php selected($item_type, 'modal'); ?>>Modal</option>
                                                                    <option value="wysiwyg" <?php selected($item_type, 'wysiwyg'); ?>>WYSIWYG</option>
                                                                </select>
                                                            </td>
                                                        </tr>

                                                        <!-- Link Type Fields -->
                                                        <tr class="wp-mbb-item-type-field wp-mbb-item-type-link" <?php echo $item_type !== 'link' ? 'style="display:none;"' : ''; ?>>
                                                            <th scope="row">
                                                                <label><?php esc_html_e('URL', 'mobile-bottom-bar'); ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="url" class="wp-mbb-item-href regular-text" value="<?php echo esc_url($item['href'] ?? ''); ?>" placeholder="https://example.com">
                                                            </td>
                                                        </tr>
                                                        <tr class="wp-mbb-item-type-field wp-mbb-item-type-link" <?php echo $item_type !== 'link' ? 'style="display:none;"' : ''; ?>>
                                                            <th scope="row">
                                                                <label><?php esc_html_e('Link Target', 'mobile-bottom-bar'); ?></label>
                                                            </th>
                                                            <td>
                                                                <select class="wp-mbb-item-link-target regular-text">
                                                                    <option value="self" <?php selected($item['linkTarget'] ?? 'self', 'self'); ?>>Same Window</option>
                                                                    <option value="blank" <?php selected($item['linkTarget'] ?? 'self', 'blank'); ?>>New Window</option>
                                                                </select>
                                                            </td>
                                                        </tr>

                                                        <!-- Phone Type Fields -->
                                                        <tr class="wp-mbb-item-type-field wp-mbb-item-type-phone" <?php echo $item_type !== 'phone' ? 'style="display:none;"' : ''; ?>>
                                                            <th scope="row">
                                                                <label><?php esc_html_e('Phone Number', 'mobile-bottom-bar'); ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="tel" class="wp-mbb-item-phone regular-text" value="<?php echo esc_attr($item['phoneNumber'] ?? ''); ?>" placeholder="+1-555-0000">
                                                            </td>
                                                        </tr>

                                                        <!-- Email Type Fields -->
                                                        <tr class="wp-mbb-item-type-field wp-mbb-item-type-mail" <?php echo $item_type !== 'mail' ? 'style="display:none;"' : ''; ?>>
                                                            <th scope="row">
                                                                <label><?php esc_html_e('Email Address', 'mobile-bottom-bar'); ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="email" class="wp-mbb-item-email regular-text" value="<?php echo esc_attr($item['emailAddress'] ?? ''); ?>" placeholder="email@example.com">
                                                            </td>
                                                        </tr>

                                                        <!-- Modal Type Fields -->
                                                        <tr class="wp-mbb-item-type-field wp-mbb-item-type-modal" <?php echo $item_type !== 'modal' ? 'style="display:none;"' : ''; ?>>
                                                            <th scope="row">
                                                                <label><?php esc_html_e('Modal Title', 'mobile-bottom-bar'); ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="text" class="wp-mbb-item-modal-title regular-text" value="<?php echo esc_attr($item['modalTitle'] ?? ''); ?>" placeholder="<?php esc_attr_e('Modal title', 'mobile-bottom-bar'); ?>">
                                                            </td>
                                                        </tr>
                                                        <tr class="wp-mbb-item-type-field wp-mbb-item-type-modal" <?php echo $item_type !== 'modal' ? 'style="display:none;"' : ''; ?>>
                                                            <th scope="row">
                                                                <label><?php esc_html_e('Modal Content', 'mobile-bottom-bar'); ?></label>
                                                            </th>
                                                            <td>
                                                                <textarea class="wp-mbb-item-modal-content large-text" rows="5" placeholder="<?php esc_attr_e('Enter modal content', 'mobile-bottom-bar'); ?>"><?php echo esc_textarea($item['modalContent'] ?? ''); ?></textarea>
                                                            </td>
                                                        </tr>

                                                        <!-- WYSIWYG Type Fields -->
                                                        <tr class="wp-mbb-item-type-field wp-mbb-item-type-wysiwyg" <?php echo $item_type !== 'wysiwyg' ? 'style="display:none;"' : ''; ?>>
                                                            <th scope="row">
                                                                <label><?php esc_html_e('Content', 'mobile-bottom-bar'); ?></label>
                                                            </th>
                                                            <td>
                                                                <textarea class="wp-mbb-item-wysiwyg-content large-text" rows="5" placeholder="<?php esc_attr_e('Enter content', 'mobile-bottom-bar'); ?>"><?php echo esc_textarea($item['wysiwygContent'] ?? ''); ?></textarea>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                    <?php 
                                        endforeach;
                                    endif;
                                    ?>
                                </div>

                                <!-- Add New Item Button -->
                                <button type="button" class="button button-secondary wp-mbb-btn-add-item" id="wp-mbb-add-item">
                                    + <?php esc_html_e('Add Custom Item', 'mobile-bottom-bar'); ?>
                                </button>

                                <!-- Hidden template for new items -->
                                <div id="wp-mbb-item-template" style="display: none;">
                                    <div class="wp-mbb-custom-item" data-item-id="__template__">
                                        <div class="wp-mbb-item-header">
                                            <span class="wp-mbb-item-label"><?php esc_html_e('(Untitled)', 'mobile-bottom-bar'); ?></span>
                                            <span class="wp-mbb-item-type">link</span>
                                            <button type="button" class="wp-mbb-item-delete">×</button>
                                        </div>
                                        <div class="wp-mbb-item-details" style="display: none;">
                                            <input type="hidden" class="wp-mbb-item-id" value="">
                                            <table class="form-table">
                                                <tr>
                                                    <th scope="row">
                                                        <label><?php esc_html_e('Label', 'mobile-bottom-bar'); ?></label>
                                                    </th>
                                                    <td>
                                                        <input type="text" class="wp-mbb-item-label-input regular-text" placeholder="<?php esc_attr_e('Item label', 'mobile-bottom-bar'); ?>">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">
                                                        <label><?php esc_html_e('Icon', 'mobile-bottom-bar'); ?></label>
                                                    </th>
                                                    <td>
                                                        <select class="wp-mbb-item-icon-select regular-text">
                                                            <option value="home">Home</option>
                                                            <option value="search">Search</option>
                                                            <option value="shopping-bag">Shopping Bag</option>
                                                            <option value="user">User</option>
                                                            <option value="heart">Heart</option>
                                                            <option value="bell">Bell</option>
                                                            <option value="settings">Settings</option>
                                                            <option value="bookmark">Bookmark</option>
                                                            <option value="phone">Phone</option>
                                                            <option value="gift">Gift</option>
                                                            <option value="mail">Mail</option>
                                                            <option value="map">Map</option>
                                                            <option value="calendar">Calendar</option>
                                                            <option value="hotel">Hotel</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">
                                                        <label><?php esc_html_e('Type', 'mobile-bottom-bar'); ?></label>
                                                    </th>
                                                    <td>
                                                        <select class="wp-mbb-item-type-select regular-text">
                                                            <option value="link">Link</option>
                                                            <option value="phone">Phone</option>
                                                            <option value="mail">Email</option>
                                                            <option value="modal">Modal</option>
                                                            <option value="wysiwyg">WYSIWYG</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                                <tr class="wp-mbb-item-type-field wp-mbb-item-type-link">
                                                    <th scope="row">
                                                        <label><?php esc_html_e('URL', 'mobile-bottom-bar'); ?></label>
                                                    </th>
                                                    <td>
                                                        <input type="url" class="wp-mbb-item-href regular-text" placeholder="https://example.com">
                                                    </td>
                                                </tr>
                                                <tr class="wp-mbb-item-type-field wp-mbb-item-type-link">
                                                    <th scope="row">
                                                        <label><?php esc_html_e('Link Target', 'mobile-bottom-bar'); ?></label>
                                                    </th>
                                                    <td>
                                                        <select class="wp-mbb-item-link-target regular-text">
                                                            <option value="self">Same Window</option>
                                                            <option value="blank">New Window</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                                <tr class="wp-mbb-item-type-field wp-mbb-item-type-phone" style="display:none;">
                                                    <th scope="row">
                                                        <label><?php esc_html_e('Phone Number', 'mobile-bottom-bar'); ?></label>
                                                    </th>
                                                    <td>
                                                        <input type="tel" class="wp-mbb-item-phone regular-text" placeholder="+1-555-0000">
                                                    </td>
                                                </tr>
                                                <tr class="wp-mbb-item-type-field wp-mbb-item-type-mail" style="display:none;">
                                                    <th scope="row">
                                                        <label><?php esc_html_e('Email Address', 'mobile-bottom-bar'); ?></label>
                                                    </th>
                                                    <td>
                                                        <input type="email" class="wp-mbb-item-email regular-text" placeholder="email@example.com">
                                                    </td>
                                                </tr>
                                                <tr class="wp-mbb-item-type-field wp-mbb-item-type-modal" style="display:none;">
                                                    <th scope="row">
                                                        <label><?php esc_html_e('Modal Title', 'mobile-bottom-bar'); ?></label>
                                                    </th>
                                                    <td>
                                                        <input type="text" class="wp-mbb-item-modal-title regular-text" placeholder="<?php esc_attr_e('Modal title', 'mobile-bottom-bar'); ?>">
                                                    </td>
                                                </tr>
                                                <tr class="wp-mbb-item-type-field wp-mbb-item-type-modal" style="display:none;">
                                                    <th scope="row">
                                                        <label><?php esc_html_e('Modal Content', 'mobile-bottom-bar'); ?></label>
                                                    </th>
                                                    <td>
                                                        <textarea class="wp-mbb-item-modal-content large-text" rows="5" placeholder="<?php esc_attr_e('Enter modal content', 'mobile-bottom-bar'); ?>"></textarea>
                                                    </td>
                                                </tr>
                                                <tr class="wp-mbb-item-type-field wp-mbb-item-type-wysiwyg" style="display:none;">
                                                    <th scope="row">
                                                        <label><?php esc_html_e('Content', 'mobile-bottom-bar'); ?></label>
                                                    </th>
                                                    <td>
                                                        <textarea class="wp-mbb-item-wysiwyg-content large-text" rows="5" placeholder="<?php esc_attr_e('Enter content', 'mobile-bottom-bar'); ?>"></textarea>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
