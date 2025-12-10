<?php
/**
 * Plugin Name:       Mobby - Mobile Bottom Bar
 * Description:       Build your own dynamic mobile bottom navigation bar from the WordPress dashboard.
 * Version:           2.3.4
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Code045
 * Author URI:        https://code045.nl
 * Text Domain:       mobile-bottom-bar
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load main plugin class
require_once plugin_dir_path(__FILE__) . 'includes/class-plugin.php';

// Activation hook
register_activation_hook(
    __FILE__,
    static function (): void {
        if (false === get_option(MBB_Settings::OPTION_KEY, false)) {
            update_option(MBB_Settings::OPTION_KEY, []);
        }
    }
);

// Initialize plugin
Mobile_Bottom_Bar_Plugin::instance();
