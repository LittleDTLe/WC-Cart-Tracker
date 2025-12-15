<?php
/**
 * Fixed Export AJAX Handlers
 * 
 *
 * @package WC_All_Cart_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Cart_Tracker_Export_AJAX
{
    public function __construct()
    {
        // Template management AJAX handlers
        add_action('wp_ajax_wcat_get_templates', array($this, 'ajax_get_templates'));
        add_action('wp_ajax_wcat_save_template', array($this, 'ajax_save_template'));
        add_action('wp_ajax_wcat_delete_template', array($this, 'ajax_delete_template'));
        add_action('wp_ajax_wcat_update_template', array($this, 'ajax_update_template'));
    }

    /**
     * AJAX: Get user templates
     */
    public function ajax_get_templates()
    {
        check_ajax_referer('wcat_export_ajax', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wc-all-cart-tracker')));
        }

        $templates = WC_Cart_Tracker_Export_Templates::get_user_templates(true);
        $columns = WC_Cart_Tracker_Export_Templates::get_available_columns();

        wp_send_json_success(array(
            'templates' => $templates,
            'columns' => $columns,
        ));
    }

    /**
     * AJAX: Save template
     */
    public function ajax_save_template()
    {
        check_ajax_referer('wcat_export_ajax', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wc-all-cart-tracker')));
        }

        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $columns = isset($_POST['columns']) ? (array) $_POST['columns'] : array();
        $is_global = isset($_POST['is_global']) && $_POST['is_global'] === 'true';

        if (empty($name)) {
            wp_send_json_error(array('message' => __('Template name is required', 'wc-all-cart-tracker')));
        }

        $result = WC_Cart_Tracker_Export_Templates::save_template($name, $columns, $is_global);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'template_id' => $result,
            'message' => __('Template saved successfully', 'wc-all-cart-tracker')
        ));
    }

    /**
     * AJAX: Delete template
     */
    public function ajax_delete_template()
    {
        check_ajax_referer('wcat_export_ajax', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wc-all-cart-tracker')));
        }

        $template_id = isset($_POST['template_id']) ? sanitize_text_field($_POST['template_id']) : '';

        if (empty($template_id)) {
            wp_send_json_error(array('message' => __('Template ID is required', 'wc-all-cart-tracker')));
        }

        $result = WC_Cart_Tracker_Export_Templates::delete_template($template_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Template deleted successfully', 'wc-all-cart-tracker')));
    }

    /**
     * AJAX: Update template
     */
    public function ajax_update_template()
    {
        check_ajax_referer('wcat_export_ajax', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wc-all-cart-tracker')));
        }

        $template_id = isset($_POST['template_id']) ? sanitize_text_field($_POST['template_id']) : '';
        $columns = isset($_POST['columns']) ? (array) $_POST['columns'] : array();
        $new_name = isset($_POST['new_name']) ? sanitize_text_field($_POST['new_name']) : null;

        if (empty($template_id)) {
            wp_send_json_error(array('message' => __('Template ID is required', 'wc-all-cart-tracker')));
        }

        $result = WC_Cart_Tracker_Export_Templates::update_template($template_id, $columns, $new_name);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Template updated successfully', 'wc-all-cart-tracker')));
    }
}

// Initialize AJAX handlers
new WC_Cart_Tracker_Export_AJAX();