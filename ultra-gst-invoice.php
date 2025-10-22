<?php
/**
 * Plugin Name: Ultra GST Invoice & Inventory SaaS
 * Plugin URI: https://github.com/suraj2025du/ultra-mega-advanced-gst-invoice
 * Description: Complete GST Invoice & Inventory Management SaaS with Coupon System, Multi-tenant Architecture, and 100% SEO Optimization
 * Version: 1.0.0
 * Author: Suraj Majhi
 * Author URI: https://github.com/suraj2025du
 * Text Domain: ultra-gst-invoice
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('UGST_VERSION', '1.0.0');
define('UGST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UGST_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UGST_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('UGST_PLUGIN_FILE', __FILE__);

// Include required files
require_once UGST_PLUGIN_DIR . 'includes/class-activator.php';
require_once UGST_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once UGST_PLUGIN_DIR . 'includes/class-core.php';
require_once UGST_PLUGIN_DIR . 'includes/class-admin.php';
require_once UGST_PLUGIN_DIR . 'includes/class-public.php';
require_once UGST_PLUGIN_DIR . 'includes/class-customer.php';
require_once UGST_PLUGIN_DIR . 'includes/class-invoice.php';
require_once UGST_PLUGIN_DIR . 'includes/class-inventory.php';
require_once UGST_PLUGIN_DIR . 'includes/class-payments.php';
require_once UGST_PLUGIN_DIR . 'includes/class-reports.php';
require_once UGST_PLUGIN_DIR . 'includes/class-seo.php';
require_once UGST_PLUGIN_DIR . 'includes/class-api.php';
require_once UGST_PLUGIN_DIR . 'includes/class-coupons.php';
require_once UGST_PLUGIN_DIR . 'includes/class-subscriptions.php';

/**
 * Main Plugin Class
 */
class Ultra_GST_Invoice {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    public function init() {
        // Load text domain
        load_plugin_textdomain('ultra-gst-invoice', false, dirname(UGST_PLUGIN_BASENAME) . '/languages');
        
        // Initialize core
        $core = new UGST_Core();
        $core->init();
        
        // Initialize admin if in admin area
        if (is_admin()) {
            $admin = new UGST_Admin();
            $admin->init();
        }
        
        // Initialize public
        $public = new UGST_Public();
        $public->init();
        
        // Initialize API
        $api = new UGST_API();
        $api->init();
        
        // Initialize SEO
        $seo = new UGST_SEO();
        $seo->init();
    }
}

// Activation hook
register_activation_hook(__FILE__, array('UGST_Activator', 'activate'));

// Deactivation hook
register_deactivation_hook(__FILE__, array('UGST_Deactivator', 'deactivate'));

// Initialize the plugin
function run_ultra_gst_invoice() {
    return Ultra_GST_Invoice::get_instance();
}

// Start the plugin
run_ultra_gst_invoice();

// Add settings link
function ugst_add_settings_link($links) {
    $settings_link = '<a href="admin.php?page=ugst-dashboard">' . __('Dashboard', 'ultra-gst-invoice') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . UGST_PLUGIN_BASENAME, 'ugst_add_settings_link');