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
    }

    public function enqueue_admin_assets($hook)
    {
        if ('woocommerce_page_wc-all-cart-tracker' !== $hook && 'woocommerce_page_wc-cart-history' !== $hook) {
            return;
        }

        wp_enqueue_style('wp-admin');
        wp_enqueue_style(
            'wc-cart-tracker-admin',
            WC_CART_TRACKER_PLUGIN_URL . 'admin/assets/admin-styles.css',
            array(),
            WC_CART_TRACKER_VERSION
        );

        wp_enqueue_script(
            'wc-cart-tracker-admin',
            WC_CART_TRACKER_PLUGIN_URL . 'admin/assets/admin-scripts.js',
            array('jquery'),
            WC_CART_TRACKER_VERSION,
            true
        );

        wp_localize_script(
            'wc-cart-tracker-admin',
            'wcat_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wcat_refresh_nonce'),
            )
        );
    }

    public function render_dashboard_page()
    {
        require_once WC_CART_TRACKER_PLUGIN_DIR . 'admin/views/admin-dashboard.php';
    }

    public function render_history_page()
    {
        require_once WC_CART_TRACKER_PLUGIN_DIR . 'admin/views/admin-history.php';
    }

    // --- AJAX Handler ---
    public function ajax_refresh_dashboard()
    {
        if (!isset($_POST['security']) || !check_ajax_referer('wcat_refresh_nonce', 'security', false)) {
            wp_send_json_error(array('message' => 'Security check failed or Nonce is invalid. Please refresh the dashboard page.'));
        }

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }

        global $wpdb;
        $table_name = WC_Cart_Tracker_Database::get_table_name();

        $days = 30;

        $orderby = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'last_updated';
        $order = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'DESC';

        $analytics = WC_Cart_Tracker_Analytics::get_analytics_data($days);

        $carts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE is_active = %d ORDER BY {$orderby} {$order}",
            1
        ));

        $total_carts_by_type = $analytics['registered_carts'] + $analytics['guest_carts'];
        $registered_distribution = $total_carts_by_type > 0 ? round(($analytics['registered_carts'] / $total_carts_by_type) * 100, 2) : 0;
        $guest_distribution = $total_carts_by_type > 0 ? round(($analytics['guest_carts'] / $total_carts_by_type) * 100, 2) : 0;

        // Render the Carts Table Body HTML fragment using output buffering
        ob_start();
        $template_path = WC_CART_TRACKER_PLUGIN_DIR . 'admin/views/table-body.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<tr><td colspan="6" style="text-align: center;">ERROR: AJAX Template Missing at ' . esc_html($template_path) . '</td></tr>';
        }
        $table_body_html = ob_get_clean();

        // Send back the successful JSON response 
        wp_send_json_success(array(
            'tableBody' => $table_body_html,
            'analytics' => $analytics,
            'registeredDistribution' => $registered_distribution,
            'guestDistribution' => $guest_distribution,

            'max_active_cart_html' => wc_price($analytics['max_active_cart']),
            'revenue_potential_html' => wc_price($analytics['revenue_potential']),
            'avg_active_cart_html' => wc_price($analytics['avg_active_cart']),
            'avg_converted_cart_html' => wc_price($analytics['avg_converted_cart']),
        ));
    }
}