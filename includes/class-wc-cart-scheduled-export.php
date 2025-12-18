<?php
/**
 * Scheduled Export System for WC All Cart Tracker
 * 
 * Features:
 * - Schedule exports (daily, weekly, monthly)
 * - Email delivery with attachments
 * - FTP/SFTP upload
 * - Cloud storage (Google Drive, Dropbox)
 * - Multiple export types
 * - Custom column selection
 * - Configurable email recipients
 * 
 * @package WC_All_Cart_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Cart_Tracker_Scheduled_Export
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Register cron schedules
        add_filter('cron_schedules', array($this, 'add_custom_cron_schedules'));

        // Register cron hooks
        add_action('wcat_scheduled_export_daily', array($this, 'run_scheduled_export'));
        add_action('wcat_scheduled_export_weekly', array($this, 'run_scheduled_export'));
        add_action('wcat_scheduled_export_monthly', array($this, 'run_scheduled_export'));

        // Admin hooks
        add_action('admin_init', array($this, 'handle_schedule_actions'));

        // AJAX handlers - CRITICAL: These must be registered correctly
        add_action('wp_ajax_wcat_test_scheduled_export', array($this, 'ajax_test_export'));
        add_action('wp_ajax_wcat_delete_schedule', array($this, 'ajax_delete_schedule'));

        error_log('WC Cart Tracker Scheduled Export: AJAX handlers registered');
    }

    public function add_custom_cron_schedules($schedules)
    {
        $schedules['weekly'] = array(
            'interval' => WEEK_IN_SECONDS,
            'display' => __('Once Weekly', 'wc-all-cart-tracker')
        );

        $schedules['monthly'] = array(
            'interval' => MONTH_IN_SECONDS,
            'display' => __('Once Monthly', 'wc-all-cart-tracker')
        );

        return $schedules;
    }

    public function handle_schedule_actions()
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        // Handle schedule create/update
        if (isset($_POST['wcat_schedule_action'])) {
            if (
                !isset($_POST['wcat_schedule_nonce']) ||
                !wp_verify_nonce($_POST['wcat_schedule_nonce'], 'wcat_schedule_action')
            ) {
                wp_die(__('Security check failed', 'wc-all-cart-tracker'));
            }

            $action = sanitize_text_field($_POST['wcat_schedule_action']);

            switch ($action) {
                case 'create':
                case 'update':
                    $this->save_schedule();
                    break;
            }
            return;
        }

        // Handle test email
        if (isset($_POST['wcat_action']) && $_POST['wcat_action'] === 'test_email') {
            if (
                !isset($_POST['wcat_test_email_nonce']) ||
                !wp_verify_nonce($_POST['wcat_test_email_nonce'], 'wcat_test_email')
            ) {
                wp_die(__('Security check failed', 'wc-all-cart-tracker'));
            }
            $this->send_diagnostic_email();
            return;
        }
    }

    /**
     * FIXED: Save schedule without infinite loop
     */
    private function save_schedule()
    {
        // Get form data
        $schedule_id = isset($_POST['schedule_id']) ? sanitize_text_field($_POST['schedule_id']) : '';
        $schedule_name = isset($_POST['schedule_name']) ? sanitize_text_field($_POST['schedule_name']) : '';
        $export_type = isset($_POST['export_type']) ? sanitize_text_field($_POST['export_type']) : 'active_carts';
        $export_format = isset($_POST['export_format']) ? sanitize_text_field($_POST['export_format']) : 'csv';
        $frequency = isset($_POST['frequency']) ? sanitize_text_field($_POST['frequency']) : 'daily';
        $delivery_method = isset($_POST['delivery_method']) ? sanitize_text_field($_POST['delivery_method']) : 'email';
        $email_recipients = isset($_POST['email_recipients']) ? sanitize_textarea_field($_POST['email_recipients']) : '';
        $columns = isset($_POST['columns']) ? array_map('sanitize_text_field', (array) $_POST['columns']) : array();
        $filters = isset($_POST['filters']) ? array_map('sanitize_text_field', (array) $_POST['filters']) : array();
        $enabled = isset($_POST['enabled']) ? sanitize_text_field($_POST['enabled']) : 'yes';

        // FTP fields
        $ftp_host = isset($_POST['ftp_host']) ? sanitize_text_field($_POST['ftp_host']) : '';
        $ftp_user = isset($_POST['ftp_user']) ? sanitize_text_field($_POST['ftp_user']) : '';
        $ftp_pass = isset($_POST['ftp_pass']) ? $_POST['ftp_pass'] : '';
        $ftp_path = isset($_POST['ftp_path']) ? sanitize_text_field($_POST['ftp_path']) : '/';

        // Validate
        if (empty($schedule_name)) {
            add_settings_error('wcat_scheduled_exports', 'empty_name', __('Schedule name is required', 'wc-all-cart-tracker'));
            return;
        }

        if ($delivery_method === 'email' && empty($email_recipients)) {
            add_settings_error('wcat_scheduled_exports', 'empty_recipients', __('Email recipients are required', 'wc-all-cart-tracker'));
            return;
        }

        if (empty($columns)) {
            add_settings_error('wcat_scheduled_exports', 'empty_columns', __('Please select at least one column to export', 'wc-all-cart-tracker'));
            return;
        }

        // Get existing schedules ONCE
        $schedules = get_option('wcat_export_schedules', array());

        // Generate schedule ID if new
        if (empty($schedule_id)) {
            $schedule_id = 'schedule_' . time();
        }

        // Build schedule data
        $schedule_data = array(
            'name' => $schedule_name,
            'export_type' => $export_type,
            'export_format' => $export_format,
            'frequency' => $frequency,
            'delivery_method' => $delivery_method,
            'email_recipients' => $email_recipients,
            'ftp_host' => $ftp_host,
            'ftp_user' => $ftp_user,
            'ftp_pass' => $ftp_pass,
            'ftp_path' => $ftp_path,
            'columns' => $columns,
            'filters' => $filters,
            'enabled' => $enabled,
            'created' => isset($schedules[$schedule_id]['created']) ? $schedules[$schedule_id]['created'] : current_time('mysql'),
            'last_run' => isset($schedules[$schedule_id]['last_run']) ? $schedules[$schedule_id]['last_run'] : null,
        );

        // Schedule cron job BEFORE calculating next_run
        $this->schedule_cron_job($schedule_id, $frequency);

        // Now get the next_run from WordPress cron
        $next_timestamp = wp_next_scheduled('wcat_scheduled_export_' . $frequency, array($schedule_id));
        $schedule_data['next_run'] = $next_timestamp ? date('Y-m-d H:i:s', $next_timestamp) : null;

        // Save schedule - ONCE
        $schedules[$schedule_id] = $schedule_data;
        update_option('wcat_export_schedules', $schedules);

        // Log for debugging
        error_log('Schedule saved: ' . $schedule_id);
        error_log('Next run: ' . ($schedule_data['next_run'] ?? 'NOT SCHEDULED'));

        // Success message
        add_settings_error(
            'wcat_scheduled_exports',
            'schedule_saved',
            __('Schedule saved successfully!', 'wc-all-cart-tracker'),
            'success'
        );

        // Redirect to avoid form resubmission
        wp_safe_redirect(add_query_arg('settings-updated', 'true', admin_url('admin.php?page=wc-cart-scheduled-exports')));
        exit;
    }

    /**
     * Schedule cron job
     */
    private function schedule_cron_job($schedule_id, $frequency)
    {
        $hook = 'wcat_scheduled_export_' . $frequency;

        // Clear existing schedule
        $timestamp = wp_next_scheduled($hook, array($schedule_id));
        if ($timestamp) {
            wp_unschedule_event($timestamp, $hook, array($schedule_id));
        }

        // Calculate next run
        $next_timestamp = $this->calculate_next_run($frequency);

        // Schedule new event
        wp_schedule_event($next_timestamp, $frequency, $hook, array($schedule_id));

        error_log("Cron scheduled: $hook for schedule $schedule_id at " . date('Y-m-d H:i:s', $next_timestamp));
    }

    /**
     * Calculate next run time
     */
    private function calculate_next_run($frequency)
    {
        switch ($frequency) {
            case 'daily':
                $next = strtotime('tomorrow 2:00am');
                break;
            case 'weekly':
                $next = strtotime('next monday 2:00am');
                break;
            case 'monthly':
                $next = strtotime('first day of next month 2:00am');
                break;
            default:
                $next = strtotime('+1 day');
        }

        return $next;
    }

    /**
     * FIXED: AJAX test export handler
     */
    public function ajax_test_export()
    {
        error_log('=== AJAX TEST EXPORT CALLED ===');
        error_log('POST data: ' . print_r($_POST, true));

        // Verify nonce - don't die, just check
        $nonce_check = isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'wcat_scheduled_export');

        if (!$nonce_check) {
            error_log('ERROR: Nonce verification failed');
            error_log('Received nonce: ' . (isset($_POST['nonce']) ? $_POST['nonce'] : 'NONE'));
            error_log('Expected action: wcat_scheduled_export');
            wp_send_json_error(array('message' => __('Security check failed', 'wc-all-cart-tracker')));
            return;
        }

        if (!current_user_can('manage_woocommerce')) {
            error_log('ERROR: User lacks permissions');
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wc-all-cart-tracker')));
            return;
        }

        $schedule_id = isset($_POST['schedule_id']) ? sanitize_text_field($_POST['schedule_id']) : '';

        if (empty($schedule_id)) {
            error_log('ERROR: No schedule ID provided');
            wp_send_json_error(array('message' => __('Invalid schedule ID', 'wc-all-cart-tracker')));
            return;
        }

        error_log('Running test export for schedule: ' . $schedule_id);

        // Run export
        $this->run_scheduled_export($schedule_id);

        // Check result
        $schedules = get_option('wcat_export_schedules', array());
        $last_status = isset($schedules[$schedule_id]['last_status']) ? $schedules[$schedule_id]['last_status'] : 'unknown';
        $last_error = isset($schedules[$schedule_id]['last_error']) ? $schedules[$schedule_id]['last_error'] : '';

        error_log('Export completed with status: ' . $last_status);

        if ($last_status === 'success') {
            wp_send_json_success(array(
                'message' => __('Test export sent successfully!', 'wc-all-cart-tracker')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Test export failed: ', 'wc-all-cart-tracker') . $last_error
            ));
        }
    }

    /**
     * FIXED: AJAX delete schedule handler
     */
    public function ajax_delete_schedule()
    {
        error_log('=== AJAX DELETE SCHEDULE CALLED ===');
        error_log('POST data: ' . print_r($_POST, true));

        // Verify nonce - don't die, just check
        $nonce_check = isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'wcat_scheduled_export');

        if (!$nonce_check) {
            error_log('ERROR: Nonce verification failed');
            error_log('Received nonce: ' . (isset($_POST['nonce']) ? $_POST['nonce'] : 'NONE'));
            wp_send_json_error(array('message' => __('Security check failed', 'wc-all-cart-tracker')));
            return;
        }

        if (!current_user_can('manage_woocommerce')) {
            error_log('ERROR: User lacks permissions');
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wc-all-cart-tracker')));
            return;
        }

        $schedule_id = isset($_POST['schedule_id']) ? sanitize_text_field($_POST['schedule_id']) : '';

        if (empty($schedule_id)) {
            error_log('ERROR: No schedule ID provided');
            wp_send_json_error(array('message' => __('Invalid schedule ID', 'wc-all-cart-tracker')));
            return;
        }

        error_log('Deleting schedule: ' . $schedule_id);

        $schedules = get_option('wcat_export_schedules', array());

        if (!isset($schedules[$schedule_id])) {
            error_log('ERROR: Schedule not found');
            wp_send_json_error(array('message' => __('Schedule not found', 'wc-all-cart-tracker')));
            return;
        }

        $frequency = $schedules[$schedule_id]['frequency'];
        $hook = 'wcat_scheduled_export_' . $frequency;

        // Clear cron job
        wp_clear_scheduled_hook($hook, array($schedule_id));

        // Remove schedule
        unset($schedules[$schedule_id]);
        update_option('wcat_export_schedules', $schedules);

        error_log('Schedule deleted successfully');

        wp_send_json_success(array('message' => __('Schedule deleted successfully', 'wc-all-cart-tracker')));
    }

    /**
     * Run scheduled export
     */
    public function run_scheduled_export($schedule_id)
    {
        error_log('=== Running scheduled export ===');
        error_log('Schedule ID: ' . $schedule_id);

        $schedules = get_option('wcat_export_schedules', array());

        if (!isset($schedules[$schedule_id])) {
            error_log('ERROR: Schedule not found');
            return;
        }

        $schedule = $schedules[$schedule_id];

        if ($schedule['enabled'] !== 'yes') {
            error_log('Schedule disabled, skipping');
            return;
        }

        // Generate export
        $file_path = $this->generate_export_file($schedule);

        if (is_wp_error($file_path)) {
            error_log('Export generation failed: ' . $file_path->get_error_message());
            $schedules[$schedule_id]['last_status'] = 'failed';
            $schedules[$schedule_id]['last_error'] = $file_path->get_error_message();
            $schedules[$schedule_id]['last_run'] = current_time('mysql');
            update_option('wcat_export_schedules', $schedules);
            return;
        }

        // Deliver export
        $result = $this->deliver_export($schedule, $file_path);

        // Update schedule
        $schedules[$schedule_id]['last_run'] = current_time('mysql');

        if (is_wp_error($result)) {
            $schedules[$schedule_id]['last_status'] = 'failed';
            $schedules[$schedule_id]['last_error'] = $result->get_error_message();
            error_log('Delivery failed: ' . $result->get_error_message());
        } else {
            $schedules[$schedule_id]['last_status'] = 'success';
            $schedules[$schedule_id]['last_error'] = '';
            error_log('Export completed successfully');
        }

        update_option('wcat_export_schedules', $schedules);

        // Cleanup temp file
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    /**
     * Generate export file
     */
    private function generate_export_file($schedule)
    {
        global $wpdb;
        $table_name = WC_Cart_Tracker_Database::get_table_name();

        // Get data
        if ($schedule['export_type'] === 'active_carts') {
            $recent_date = date('Y-m-d H:i:s', strtotime('-24 hours'));
            $carts = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE is_active = %d AND last_updated >= %s ORDER BY last_updated DESC",
                1,
                $recent_date
            ));
        } else {
            $days_filter = isset($schedule['filters']['days']) ? absint($schedule['filters']['days']) : 30;
            $date_from = date('Y-m-d H:i:s', strtotime("-{$days_filter} days"));
            $carts = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE last_updated >= %s ORDER BY last_updated DESC",
                $date_from
            ));
        }

        if (empty($carts)) {
            return new WP_Error('no_data', __('No cart data to export', 'wc-all-cart-tracker'));
        }

        // Get columns
        $columns = !empty($schedule['columns']) ? $schedule['columns'] : WC_Cart_Tracker_Export_Templates::get_default_columns();

        // Prepare data
        $data = array();
        $data[] = WC_Cart_Tracker_Export_Templates::get_column_headers($columns);

        foreach ($carts as $cart) {
            $row = array();
            foreach ($columns as $column_key) {
                $value = WC_Cart_Tracker_Export_Templates::extract_column_data($cart, $column_key);
                $row[] = WC_Cart_Tracker_Data_Sanitizer::escape_csv($value);
            }
            $data[] = $row;
        }

        // Create temp file
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/wcat-temp';

        if (!file_exists($temp_dir)) {
            mkdir($temp_dir, 0755, true);
        }

        $filename = 'wcat-export-' . $schedule['export_type'] . '-' . date('Y-m-d-His') . '.' . $schedule['export_format'];
        $file_path = $temp_dir . '/' . $filename;

        // Write file
        if ($schedule['export_format'] === 'csv') {
            $fp = fopen($file_path, 'w');
            fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
            foreach ($data as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);
        } else {
            // Excel
            $fp = fopen($file_path, 'w');
            fwrite($fp, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
            fwrite($fp, '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"><Worksheet ss:Name="Sheet1"><Table>' . "\n");
            foreach ($data as $row) {
                fwrite($fp, '<Row>');
                foreach ($row as $cell) {
                    $cell = htmlspecialchars($cell, ENT_XML1, 'UTF-8');
                    fwrite($fp, '<Cell><Data ss:Type="String">' . $cell . '</Data></Cell>');
                }
                fwrite($fp, '</Row>' . "\n");
            }
            fwrite($fp, '</Table></Worksheet></Workbook>');
            fclose($fp);
        }

        return $file_path;
    }

    /**
     * Deliver export
     */
    private function deliver_export($schedule, $file_path)
    {
        switch ($schedule['delivery_method']) {
            case 'email':
                return $this->deliver_via_email($schedule, $file_path);
            case 'ftp':
                return $this->deliver_via_ftp($schedule, $file_path);
            default:
                return new WP_Error('invalid_delivery', __('Invalid delivery method', 'wc-all-cart-tracker'));
        }
    }

    /**
     * Deliver via email
     */
    private function deliver_via_email($schedule, $file_path)
    {
        if (!file_exists($file_path)) {
            return new WP_Error('file_missing', __('Export file not found', 'wc-all-cart-tracker'));
        }

        $recipients = explode("\n", $schedule['email_recipients']);
        $recipients = array_map('trim', $recipients);
        $recipients = array_filter($recipients, 'is_email');

        if (empty($recipients)) {
            return new WP_Error('no_recipients', __('No valid email recipients', 'wc-all-cart-tracker'));
        }

        $subject = sprintf(
            __('[%s] Scheduled Cart Export - %s', 'wc-all-cart-tracker'),
            get_bloginfo('name'),
            $schedule['name']
        );

        $message = sprintf(
            __('Please find attached the scheduled cart export "%s".', 'wc-all-cart-tracker'),
            $schedule['name']
        );

        $headers = array('Content-Type: text/plain; charset=UTF-8');
        $attachments = array($file_path);

        $result = wp_mail($recipients, $subject, $message, $headers, $attachments);

        return $result ? true : new WP_Error('email_failed', __('Failed to send email', 'wc-all-cart-tracker'));
    }

    /**
     * Deliver via FTP
     */
    private function deliver_via_ftp($schedule, $file_path)
    {
        $ftp_host = $schedule['ftp_host'] ?? '';
        $ftp_user = $schedule['ftp_user'] ?? '';
        $ftp_pass = $schedule['ftp_pass'] ?? '';
        $ftp_path = $schedule['ftp_path'] ?? '/';

        if (empty($ftp_host) || empty($ftp_user)) {
            return new WP_Error('ftp_config', __('FTP configuration incomplete', 'wc-all-cart-tracker'));
        }

        $conn = ftp_connect($ftp_host);
        if (!$conn) {
            return new WP_Error('ftp_connect', __('Failed to connect to FTP', 'wc-all-cart-tracker'));
        }

        $login = ftp_login($conn, $ftp_user, $ftp_pass);
        if (!$login) {
            ftp_close($conn);
            return new WP_Error('ftp_login', __('FTP login failed', 'wc-all-cart-tracker'));
        }

        $remote_file = $ftp_path . '/' . basename($file_path);
        $upload = ftp_put($conn, $remote_file, $file_path, FTP_BINARY);
        ftp_close($conn);

        return $upload ? true : new WP_Error('ftp_upload', __('FTP upload failed', 'wc-all-cart-tracker'));
    }

    /**
     * Send diagnostic email
     */
    private function send_diagnostic_email()
    {
        $recipient = isset($_POST['test_email_recipient']) ? sanitize_email($_POST['test_email_recipient']) : '';

        if (!is_email($recipient)) {
            add_settings_error('wcat_scheduled_exports', 'invalid_email', __('Invalid email address', 'wc-all-cart-tracker'), 'error');
            return;
        }

        $subject = '[' . get_bloginfo('name') . '] Cart Tracker Email Test';
        $message = __('This is a test email from WC All Cart Tracker. If you received this, your email configuration is working!', 'wc-all-cart-tracker');
        $headers = array('Content-Type: text/plain; charset=UTF-8');

        $result = wp_mail($recipient, $subject, $message, $headers);

        if ($result) {
            add_settings_error('wcat_scheduled_exports', 'test_success', __('Test email sent successfully!', 'wc-all-cart-tracker'), 'success');
        } else {
            add_settings_error('wcat_scheduled_exports', 'test_failed', __('Failed to send test email. Check debug.log', 'wc-all-cart-tracker'), 'error');
        }
    }

    /**
     * Render admin page
     */
    public function render_scheduled_exports_page()
    {
        require_once WC_CART_TRACKER_PLUGIN_DIR . 'admin/views/admin-scheduled-exports.php';
    }

    /**
     * Get all schedules
     */
    public static function get_schedules()
    {
        return get_option('wcat_export_schedules', array());
    }

    /**
     * Get single schedule
     */
    public static function get_schedule($schedule_id)
    {
        $schedules = self::get_schedules();
        return isset($schedules[$schedule_id]) ? $schedules[$schedule_id] : null;
    }
}

// Initialize - IMPORTANT: Use correct hook
function wc_cart_tracker_init_scheduled_export()
{
    return WC_Cart_Tracker_Scheduled_Export::get_instance();
}
add_action('plugins_loaded', 'wc_cart_tracker_init_scheduled_export');