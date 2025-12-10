<?php
/**
 * Main Plugin Class
 *
 * Coordinates all plugin components
 *
 * @package Mobile_Bottom_Bar
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Mobile_Bottom_Bar_Plugin {
    
    private static $instance = null;
    
    private $settings;
    private $lighthouse;
    private $admin;
    private $frontend;
    private $ajax;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_components();
    }

    private function load_dependencies(): void {
        require_once plugin_dir_path(__FILE__) . 'class-settings.php';
        require_once plugin_dir_path(__FILE__) . 'class-lighthouse.php';
        require_once plugin_dir_path(__FILE__) . 'class-admin.php';
        require_once plugin_dir_path(__FILE__) . 'class-frontend.php';
        require_once plugin_dir_path(__FILE__) . 'class-ajax.php';
    }

    private function init_components(): void {
        $this->settings = new MBB_Settings();
        $this->lighthouse = new MBB_Lighthouse();
        $this->admin = new MBB_Admin($this->settings, $this->lighthouse);
        $this->frontend = new MBB_Frontend($this->settings, $this->lighthouse);
        $this->ajax = new MBB_Ajax($this->settings);

        $this->admin->init();
        $this->frontend->init();
        $this->ajax->init();
    }

    public function get_settings(): MBB_Settings {
        return $this->settings;
    }

    public function get_lighthouse(): MBB_Lighthouse {
        return $this->lighthouse;
    }

    public function get_admin(): MBB_Admin {
        return $this->admin;
    }

    public function get_frontend(): MBB_Frontend {
        return $this->frontend;
    }

    public function get_ajax(): MBB_Ajax {
        return $this->ajax;
    }
}
