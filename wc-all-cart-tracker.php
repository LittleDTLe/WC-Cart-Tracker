<?php
/**
 * Plugin Name: WC All Cart Tracker
 * Plugin URI: https://github.com/LittleDTLe/WC-Cart-Tracker/issues/new
 * Description: Tracks all active WooCommerce carts in real-time, including guest and registered user carts.
 * Version: 1.0.0
 * Author: Panagiotis Drougas
 * Author URI: https://github.com/LittleDTLe
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wc-all-cart-tracker
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * 
 * @package WC_All_Cart_Tracker
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_CART_TRACKER_VERSION', '1.0.0');
define('WC_CART_TRACKER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_CART_TRACKER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_CART_TRACKER_PLUGIN_FILE', __FILE__);

// Declare HPOS compatibility
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Autoloader for classes
spl_autoload_register(function ($class) {
    $prefix = 'WC_Cart_Tracker_';
    $base_dir = WC_CART_TRACKER_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . 'class-' . str_replace('_', '-', strtolower($relative_class)) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Autoloader for admin classes
spl_autoload_register(function ($class) {
    if (strpos($class, 'WC_Cart_Tracker_Admin') === 0) {
        $file = WC_CART_TRACKER_PLUGIN_DIR . 'admin/class-' . str_replace('_', '-', strtolower($class)) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

// Initialize the plugin
function wc_cart_tracker_init()
{
    require_once WC_CART_TRACKER_PLUGIN_DIR . 'includes/class-wc-cart-tracker.php';
    WC_Cart_Tracker::get_instance();
}
add_action('plugins_loaded', 'wc_cart_tracker_init');

// Activation hook
register_activation_hook(__FILE__, function () {
    require_once WC_CART_TRACKER_PLUGIN_DIR . 'includes/class-wc-cart-database.php';
    WC_Cart_Tracker_Database::activate();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});