<?php
/**
 * Uninstall file for WC All Cart Tracker.
 *
 * This file is executed when the user chooses to uninstall the plugin completely.
 * It removes all traces of the plugin, including the custom database table.
 *
 * @package WC_All_Cart_Tracker
 */

// If uninstall.php is not called directly, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// 1. Ensure the database class is available to call static methods
// We can't use the regular loader, so we manually include the file if it exists.
if (!class_exists('WC_Cart_Tracker_Database') && file_exists(dirname(__FILE__) . '/includes/class-wc-cart-database.php')) {
    require_once dirname(__FILE__) . '/includes/class-wc-cart-database.php';
}

// 2. Drop the custom database table
if (class_exists('WC_Cart_Tracker_Database')) {
    WC_Cart_Tracker_Database::drop_table();
}

// 3. Delete all plugin options
$options_to_delete = array(
    'wc_all_cart_tracker_version',
);

foreach ($options_to_delete as $option) {
    delete_option($option);
    delete_site_option($option); // For multisite compatibility
}

// Note: No custom transients were defined, so no further cleanup is needed.