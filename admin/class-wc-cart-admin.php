<?php
/**
 * Admin Interface
 *
 * @package WC_All_Cart_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Cart_Tracker_Admin
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'), 60);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_wcat_refresh_dashboard', array($this, 'ajax_refresh_dashboard'));
        add_action('wp_ajax_wcat_save_refresh_setting', array($this, 'ajax_save_refresh_setting'));
        add_action('admin_footer', array($this, 'render_export_modal'));


        if (file_exists(WC_CART_TRACKER_PLUGIN_DIR . 'admin/class-wc-cart-optimization-admin.php')) {
            require_once WC_CART_TRACKER_PLUGIN_DIR . 'admin/class-wc-cart-optimization-admin.php';
            new WC_Cart_Tracker_Optimization_Admin();
        }
    }

    public function add_admin_menu()
    {
        add_submenu_page(
            'woocommerce',
            __('All Cart Tracker', 'wc-all-cart-tracker'),
            __('Cart Tracker', 'wc-all-cart-tracker'),
            'manage_woocommerce',
            'wc-all-cart-tracker',
            array($this, 'render_dashboard_page')
        );

        add_submenu_page(
            'woocommerce',
            __('Cart History', 'wc-all-cart-tracker'),
            __('Cart History', 'wc-all-cart-tracker'),
            'manage_woocommerce',
            'wc-cart-history',
            array($this, 'render_history_page')
        );

        // Remove Comments when View has been created! 
        // add_submenu_page(
        //     'woocommerce',
        //     __('Scheduled Exports', 'wc-all-cart-tracker'),
        //     __('Scheduled Exports', 'wc-all-cart-tracker'),
        //     'manage_woocommerce',
        //     'wc-cart-scheduled-exports',
        //     array($this, 'render_scheduled_exports_page')
        // );
    }

    public function enqueue_admin_assets($hook)
    {
        // Only on cart tracker pages
        if ('woocommerce_page_wc-all-cart-tracker' !== $hook && 'woocommerce_page_wc-cart-history' !== $hook) {
            return;
        }

        wp_enqueue_style('wp-admin');

        // Enqueue main admin styles
        wp_enqueue_style(
            'wc-cart-tracker-admin',
            WC_CART_TRACKER_PLUGIN_URL . 'admin/assets/css/admin-styles.css',
            array(),
            WC_CART_TRACKER_VERSION
        );

        // Enqueue export modal styles
        wp_enqueue_style(
            'wc-cart-tracker-export-modal',
            WC_CART_TRACKER_PLUGIN_URL . 'admin/assets/css/export-modal.css',
            array(),
            WC_CART_TRACKER_VERSION
        );

        // Enqueue main admin scripts
        wp_enqueue_script(
            'wc-cart-tracker-admin',
            WC_CART_TRACKER_PLUGIN_URL . 'admin/assets/js/admin-scripts.js',
            array('jquery'),
            WC_CART_TRACKER_VERSION,
            true
        );

        // Enqueue export modal scripts
        wp_enqueue_script(
            'wc-cart-tracker-export-modal',
            WC_CART_TRACKER_PLUGIN_URL . 'admin/assets/js/export-modal.js',
            array('jquery', 'wc-cart-tracker-admin'), // Depends on main admin script
            WC_CART_TRACKER_VERSION,
            true
        );

        // Enqueue Scripts for Scheduled Exports Page
        wp_enqueue_style(
            'wc-cart-tracker-scheduled-exports',
            WC_CART_TRACKER_PLUGIN_URL . 'admin/assets/css/scheduled-exports.css',
            array(),
            WC_CART_TRACKER_VERSION
        );

        // Enqueue Styles for Scheduled Exports Page
        wp_enqueue_script(
            'wc-cart-tracker-scheduled-exports',
            WC_CART_TRACKER_PLUGIN_URL . 'admin/assets/js/scheduled-exports.js',
            array('jquery'),
            WC_CART_TRACKER_VERSION,
            true
        );

        // Localize script for main admin functionality
        wp_localize_script('wc-cart-tracker-admin', 'wcat_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wcat_ajax_nonce'),
            'days' => isset($_GET['days']) ? absint($_GET['days']) : 30,
            'auto_refresh' => array(
                'enabled' => get_option('wcat_auto_refresh_enabled', 'no'),
                'interval' => 306000,
            ),
            'dashboard_url' => admin_url('admin.php?page=wc-all-cart-tracker')
        ));

        // Localize script for export modal
        wp_localize_script('wc-cart-tracker-export-modal', 'wcatExport', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'adminUrl' => admin_url('admin.php'),
            'nonce' => wp_create_nonce('wcat_export_ajax'),
            'exportNonce' => wp_create_nonce('wcat_export_nonce'),
        ));

        // Localisze script for Scheduled Exports
        wp_localize_script('wc-cart-tracker-scheduled-exports', 'wcatScheduledExport', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wcat_scheduled_export'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this schedule?', 'wc-all-cart-tracker'),
                'testing' => __('Testing...', 'wc-all-cart-tracker'),
                'test_success' => __('Test export sent successfully!', 'wc-all-cart-tracker'),
                'test_failed' => __('Test export failed. Check the logs.', 'wc-all-cart-tracker'),
            )
        ));
    }

    public function render_dashboard_page()
    {
        require_once WC_CART_TRACKER_PLUGIN_DIR . 'admin/views/admin-dashboard.php';
    }

    public function render_history_page()
    {
        require_once WC_CART_TRACKER_PLUGIN_DIR . 'admin/views/admin-history.php';
    }

    public function render_export_modal()
    {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->id, array('woocommerce_page_wc-all-cart-tracker', 'woocommerce_page_wc-cart-history'))) {
            return;
        }

        require_once WC_CART_TRACKER_PLUGIN_DIR . 'admin/views/export-modal.php';
    }

    // --- AJAX Handler ---
    public function ajax_refresh_dashboard()
    {
        check_ajax_referer('wcat_ajax_nonce', 'security');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }

        global $wpdb;
        $table_name = WC_Cart_Tracker_Database::get_table_name();

        $days = isset($_POST['days']) ? sanitize_text_field($_POST['days']) : '30';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        $orderby = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'last_updated';
        $order = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'DESC';

        // Pagination parameters
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 50;
        $current_page = isset($_POST['paged']) ? max(1, absint($_POST['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;

        // Validate per_page value
        $per_page_options = array(10, 25, 50, 75, 100);
        if (!in_array($per_page, $per_page_options)) {
            $per_page = 50;
        }

        // Bypass flag
        $bypass_cache = isset($_POST['bypass_cache']) && $_POST['bypass_cache'] === 'true';

        //Check if Analytics class is available and Clear Cache
        if ($bypass_cache && class_exists('WC_Cart_Tracker_Analytics')) {
            // Clear the cache for the specific time period being viewed
            if ($days === 'custom' && !empty($date_from) && !empty($date_to)) {
                WC_Cart_Tracker_Analytics::clear_cache(); // Clear all custom caches
            } else {
                WC_Cart_Tracker_Analytics::clear_cache(absint($days));
            }
        }

        // Get fresh analytics based on date range type
        if ($days === 'custom' && !empty($date_from) && !empty($date_to)) {
            $analytics = WC_Cart_Tracker_Analytics::get_analytics_data_by_date_range($date_from, $date_to);
        } else {
            $analytics = WC_Cart_Tracker_Analytics::get_analytics_data(absint($days));
        }

        // Calculate distribution
        $total_carts_by_type = $analytics['registered_carts'] + $analytics['guest_carts'];
        $analytics['registered_distribution'] = $total_carts_by_type > 0 ?
            round(($analytics['registered_carts'] / $total_carts_by_type) * 100, 2) : 0;
        $analytics['guest_distribution'] = $total_carts_by_type > 0 ?
            round(($analytics['guest_carts'] / $total_carts_by_type) * 100, 2) : 0;

        // Get fresh carts for table with pagination
        $recent_date = date('Y-m-d H:i:s', strtotime('-24 hours'));

        // Get total count for pagination info
        $total_active_carts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE is_active = %d AND last_updated >= %s",
            1,
            $recent_date
        ));

        $carts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
        WHERE is_active = %d AND last_updated >= %s 
        ORDER BY {$orderby} {$order}
        LIMIT %d OFFSET %d",
            1,
            $recent_date,
            $per_page,
            $offset
        ));

        // Generate table body HTML
        ob_start();
        require WC_CART_TRACKER_PLUGIN_DIR . 'admin/views/table-body.php';
        $tableBody = ob_get_clean();

        // Get max cart total efficiently
        $max_cart_total = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(cart_total) FROM {$table_name} WHERE is_active = 1 AND last_updated >= %s",
            $recent_date
        ));

        // Calculate pagination info
        $total_pages = ceil($total_active_carts / $per_page);
        $start_item = ($current_page - 1) * $per_page + 1;
        $end_item = min($current_page * $per_page, $total_active_carts);

        wp_send_json_success(array(
            'analytics' => $analytics,
            'tableBody' => $tableBody,
            'avg_active_cart_html' => wc_price($analytics['avg_active_cart']),
            'avg_converted_cart_html' => wc_price($analytics['avg_converted_cart']),
            'overall_revenue_potential_html' => wc_price($analytics['overall_revenue_potential']),
            'active_cart_potential_html' => wc_price($analytics['active_cart_potential']),
            'abandoned_cart_potential_html' => wc_price($analytics['abandoned_cart_potential']),
            'max_cart_total_html' => wc_price($max_cart_total ?: 0),
            'pagination' => array(
                'total_items' => $total_active_carts,
                'total_pages' => $total_pages,
                'current_page' => $current_page,
                'per_page' => $per_page,
                'start_item' => $start_item,
                'end_item' => $end_item
            )
        ));
    }

    public function ajax_save_refresh_setting()
    {
        // 1. Security Check (using the specific nonce for this setting)
        check_ajax_referer('wcat_save_settings_nonce', 'security');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }

        $enabled_state = isset($_POST['enabled']) ? sanitize_text_field($_POST['enabled']) : 'no';

        // 2. Update the WordPress option
        update_option('wcat_auto_refresh_enabled', $enabled_state);

        // 3. Success
        wp_send_json_success(array(
            'status' => $enabled_state,
            'message' => 'Auto-refresh setting saved.'
        ));
    }

}