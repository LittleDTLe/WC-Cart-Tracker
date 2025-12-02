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

        // FIX: Enqueue the default WordPress admin stylesheet to load core styling,
        // including the CSS sprites for the sorting arrows.
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
    }

    public function render_dashboard_page()
    {
        require_once WC_CART_TRACKER_PLUGIN_DIR . 'admin/views/admin-dashboard.php';
    }

    public function render_history_page()
    {
        require_once WC_CART_TRACKER_PLUGIN_DIR . 'admin/views/admin-history.php';
    }
}