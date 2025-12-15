<?php
/**
 * Export AJAX Handlers
 *
 * Handles AJAX requests for export templates
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

        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

        // Add modal HTML to footer
        add_action('admin_footer', array($this, 'render_modal_html'));
    }

    /**
     * Enqueue modal assets
     */
    public function enqueue_assets($hook)
    {
        // Only on cart tracker pages
        if ('woocommerce_page_wc-all-cart-tracker' !== $hook && 'woocommerce_page_wc-cart-history' !== $hook) {
            return;
        }

        // Enqueue modal styles (inline for now, can be moved to separate file)
        wp_add_inline_style('wc-cart-tracker-admin', $this->get_modal_styles());

        // Localize script for AJAX
        wp_localize_script('wc-cart-tracker-admin', 'wcatExport', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'adminUrl' => admin_url('admin.php'),
            'nonce' => wp_create_nonce('wcat_export_ajax'),
            'exportNonce' => wp_create_nonce('wcat_export_nonce'),
        ));
    }

    /**
     * Get modal CSS styles
     */
    private function get_modal_styles()
    {
        ob_start();
        ?>
        /* Export Modal Styles */
        .wcat-export-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 100000;
        align-items: center;
        justify-content: center;
        }

        .wcat-export-modal-overlay.active {
        display: flex;
        }

        .wcat-export-modal {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        max-width: 900px;
        width: 90%;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        }

        .wcat-export-modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
        }

        .wcat-export-modal-header h2 {
        margin: 0;
        font-size: 20px;
        font-weight: 600;
        }

        .wcat-export-modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #666;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        }

        .wcat-export-modal-close:hover {
        background: #f0f0f0;
        color: #000;
        }

        .wcat-export-modal-body {
        padding: 24px;
        overflow-y: auto;
        flex: 1;
        }

        .wcat-template-section {
        margin-bottom: 24px;
        padding: 16px;
        background: #f9f9f9;
        border-radius: 4px;
        border: 1px solid #ddd;
        }

        .wcat-template-section h3 {
        margin: 0 0 12px 0;
        font-size: 14px;
        font-weight: 600;
        }

        .wcat-template-controls {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
        }

        .wcat-template-select {
        flex: 1;
        min-width: 200px;
        }

        .wcat-columns-grid {
        display: grid;
        gap: 20px;
        }

        .wcat-column-group {
        border: 1px solid #ddd;
        border-radius: 4px;
        overflow: hidden;
        }

        .wcat-column-group-header {
        background: #f0f0f0;
        padding: 12px 16px;
        font-weight: 600;
        font-size: 13px;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
        }

        .wcat-column-group-toggle {
        font-size: 11px;
        color: #2271b1;
        cursor: pointer;
        text-decoration: none;
        }

        .wcat-column-group-toggle:hover {
        text-decoration: underline;
        }

        .wcat-column-group-body {
        padding: 16px;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 12px;
        }

        .wcat-column-item {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        }

        .wcat-column-item input[type="checkbox"] {
        margin-top: 3px;
        }

        .wcat-column-item label {
        cursor: pointer;
        flex: 1;
        }

        .wcat-column-label {
        font-weight: 500;
        display: block;
        }

        .wcat-column-description {
        font-size: 11px;
        color: #666;
        margin-top: 2px;
        display: block;
        }

        .wcat-quick-actions {
        margin-bottom: 20px;
        padding: 12px;
        background: #e7f5fe;
        border-left: 4px solid #2271b1;
        display: flex;
        gap: 10px;
        align-items: center;
        }

        .wcat-quick-actions button {
        font-size: 12px;
        padding: 4px 12px;
        height: auto;
        }

        .wcat-export-modal-footer {
        padding: 16px 24px;
        border-top: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        }

        .wcat-footer-left {
        display: flex;
        gap: 8px;
        align-items: center;
        }

        .wcat-footer-right {
        display: flex;
        gap: 8px;
        }

        .wcat-selected-count {
        font-size: 13px;
        color: #666;
        }

        .wcat-selected-count strong {
        color: #2271b1;
        }

        .wcat-save-template-form {
        display: none;
        padding: 12px;
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 16px;
        }

        .wcat-save-template-form.active {
        display: block;
        }

        .wcat-save-template-form input[type="text"] {
        width: 100%;
        margin-bottom: 8px;
        }

        .wcat-save-template-form label {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 8px;
        font-size: 13px;
        }

        .wcat-save-template-actions {
        display: flex;
        gap: 8px;
        }

        @media (max-width: 782px) {
        .wcat-export-modal {
        width: 95%;
        max-height: 95vh;
        }

        .wcat-column-group-body {
        grid-template-columns: 1fr;
        }

        .wcat-export-modal-footer {
        flex-direction: column;
        align-items: stretch;
        }

        .wcat-footer-left,
        .wcat-footer-right {
        width: 100%;
        justify-content: space-between;
        }
        }
        <?php
        return ob_get_clean();
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

    /**
     * Render modal HTML in footer
     */
    public function render_modal_html()
    {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->id, array('woocommerce_page_wc-all-cart-tracker', 'woocommerce_page_wc-cart-history'))) {
            return;
        }

        require_once WC_CART_TRACKER_PLUGIN_DIR . 'admin/views/export-modal.php';
    }
}

// Initialize AJAX handlers
new WC_Cart_Tracker_Export_AJAX();