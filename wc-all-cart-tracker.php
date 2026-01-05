<?php
/**
 * Plugin Name: WC All Cart Tracker
 * Description: Tracks all active WooCommerce carts in real-time
 * Version: 1.0.1
 * Author: Panagiotis Drougas
 * Text Domain: wc-all-cart-tracker
 * 
 * @package WC_All_Cart_Tracker
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_CART_TRACKER_VERSION', '1.0.1');
define('WC_CART_TRACKER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_CART_TRACKER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_CART_TRACKER_PLUGIN_FILE', __FILE__);

// Declare HPOS compatibility
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Initialize the plugin
function wc_cart_tracker_init()
{
    error_log('=== WC CART TRACKER PLUGIN INIT ===');

    // Load main tracker class
    require_once WC_CART_TRACKER_PLUGIN_DIR . 'includes/class-wc-cart-tracker.php';
    WC_Cart_Tracker::get_instance();
    error_log('Main tracker class loaded');

    // Load scheduled exports
    require_once WC_CART_TRACKER_PLUGIN_DIR . 'includes/class-wc-cart-scheduled-export.php';
    $scheduled_export = WC_Cart_Tracker_Scheduled_Export::get_instance();
    error_log('Scheduled Export loaded');

    // Load abandoned email class 
    require_once WC_CART_TRACKER_PLUGIN_DIR . 'includes/class-wc-cart-abandoned-email.php';
    $abandoned_email = WC_Cart_Tracker_Abandoned_Email::get_instance();
    error_log('Abandoned Email loaded: ' . (is_object($abandoned_email) ? 'YES' : 'NO'));

    // Verify AJAX handlers
    global $wp_filter;
    error_log('Scheduled export AJAX: ' . (isset($wp_filter['wp_ajax_wcat_test_scheduled_export']) ? 'YES' : 'NO'));
    error_log('Abandoned email AJAX: ' . (isset($wp_filter['wp_ajax_wcat_test_abandoned_email']) ? 'YES' : 'NO'));
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

/**
 * Register cleanup cron job
 */
function wc_cart_tracker_schedule_cleanup()
{
    if (!wp_next_scheduled('wc_cart_tracker_cleanup')) {
        wp_schedule_event(time(), 'daily', 'wc_cart_tracker_cleanup');
    }
}
add_action('wp', 'wc_cart_tracker_schedule_cleanup');

/**
 * Cleanup old carts (runs daily)
 */
function wc_cart_tracker_run_cleanup()
{
    $cleanup_enabled = get_option('wcat_cleanup_enabled', 'yes');
    $cleanup_days = get_option('wcat_cleanup_days', 90);
    $cleanup_method = get_option('wcat_cleanup_method', 'archive');

    if ($cleanup_enabled !== 'yes') {
        return;
    }

    if ($cleanup_method === 'delete') {
        WC_Cart_Tracker_Database::cleanup_old_carts($cleanup_days, true);
    } else {
        WC_Cart_Tracker_Database::cleanup_old_carts($cleanup_days, false);

        $purge_archives = get_option('wcat_purge_archives', 'no');
        if ($purge_archives === 'yes') {
            WC_Cart_Tracker_Database::purge_archive(365);
        }
    }

    WC_Cart_Tracker_Analytics::clear_cache();
}
add_action('wc_cart_tracker_cleanup', 'wc_cart_tracker_run_cleanup');

/**
 * Deactivation cleanup
 */
function wc_cart_tracker_deactivate_cleanup()
{
    wp_clear_scheduled_hook('wc_cart_tracker_cleanup');
    wp_clear_scheduled_hook('wc_cart_tracker_update_states');
    wp_clear_scheduled_hook('wcat_send_abandoned_cart_emails');
}
register_deactivation_hook(__FILE__, 'wc_cart_tracker_deactivate_cleanup');

/**
 * Add debug admin notice for AJAX testing
 */
add_action('admin_notices', function () {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'woocommerce_page_wc-cart-scheduled-exports') {
        // Check if AJAX handlers are registered
        global $wp_filter;
        $test_registered = isset($wp_filter['wp_ajax_wcat_test_scheduled_export']);
        $delete_registered = isset($wp_filter['wp_ajax_wcat_delete_schedule']);

        if (!$test_registered || !$delete_registered) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>WC Cart Tracker Debug:</strong> AJAX handlers NOT registered! ';
            echo 'Test: ' . ($test_registered ? 'OK' : 'MISSING') . ' | ';
            echo 'Delete: ' . ($delete_registered ? 'OK' : 'MISSING');
            echo '</p></div>';
        }
    }
});