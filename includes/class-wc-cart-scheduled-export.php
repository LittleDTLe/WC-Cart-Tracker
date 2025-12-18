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

        // AJAX handlers
        add_action('wp_ajax_wcat_test_scheduled_export', array($this, 'ajax_test_export'));
        add_action('wp_ajax_wcat_delete_schedule', array($this, 'ajax_delete_schedule'));
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

        // Handle schedule create/update/delete
        if (isset($_POST['wcat_schedule_action'])) {
            // Verify nonce
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
                case 'delete':
                    $this->delete_schedule();
                    break;
            }
            return;
        }

        // Handle test email (separate nonce)
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

    private function save_schedule()
    {
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

        // Additional FTP fields
        $ftp_host = isset($_POST['ftp_host']) ? sanitize_text_field($_POST['ftp_host']) : '';
        $ftp_user = isset($_POST['ftp_user']) ? sanitize_text_field($_POST['ftp_user']) : '';
        $ftp_pass = isset($_POST['ftp_pass']) ? $_POST['ftp_pass'] : ''; // Don't sanitize password
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

        // Create schedule data
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
            'last_run' => null,
            'next_run' => null,
        );

        // Get existing schedules
        $schedules = get_option('wcat_export_schedules', array());

        if (empty($schedule_id)) {
            // Create new schedule
            $schedule_id = 'schedule_' . time();
            $schedule_data['created'] = current_time('mysql');
        } else {
            // Update existing - preserve creation date and last_run
            if (isset($schedules[$schedule_id]['created'])) {
                $schedule_data['created'] = $schedules[$schedule_id]['created'];
            }
            if (isset($schedules[$schedule_id]['last_run'])) {
                $schedule_data['last_run'] = $schedules[$schedule_id]['last_run'];
            }
        }

        $schedules[$schedule_id] = $schedule_data;
        update_option('wcat_export_schedules', $schedules);

        // Schedule cron job - CRITICAL
        $this->schedule_cron_job($schedule_id, $frequency);

        // Update next_run time in the schedule data
        $next_timestamp = wp_next_scheduled('wcat_scheduled_export_' . $frequency, array($schedule_id));
        if ($next_timestamp) {
            $schedules[$schedule_id]['next_run'] = date('Y-m-d H:i:s', $next_timestamp);
        } else {
            error_log('WARNING: Cron job not found after scheduling!');
            $schedules[$schedule_id]['next_run'] = null;
        }

        // Save again with next_run updated
        update_option('wcat_export_schedules', $schedules);

        error_log('Schedule saved. Next run: ' . ($schedules[$schedule_id]['next_run'] ?? 'NOT SCHEDULED'));

        add_settings_error(
            'wcat_scheduled_exports',
            'schedule_saved',
            __('Schedule saved successfully!', 'wc-all-cart-tracker'),
            'success'
        );
    }

    private function schedule_cron_job($schedule_id, $frequency)
    {
        error_log('=== SCHEDULING CRON JOB ===');
        error_log('Schedule ID: ' . $schedule_id);
        error_log('Frequency: ' . $frequency);

        $hook = 'wcat_scheduled_export_' . $frequency;

        // Clear any existing schedule for this hook and schedule_id
        $timestamp = wp_next_scheduled($hook, array($schedule_id));
        if ($timestamp) {
            error_log('Clearing existing schedule at: ' . date('Y-m-d H:i:s', $timestamp));
            wp_unschedule_event($timestamp, $hook, array($schedule_id));
        }

        // Calculate next run time
        $next_timestamp = $this->calculate_next_run($frequency);
        error_log('Next run scheduled for: ' . date('Y-m-d H:i:s', $next_timestamp));

        // Schedule the event
        $result = wp_schedule_event($next_timestamp, $frequency, $hook, array($schedule_id));

        if ($result === false) {
            error_log('ERROR: Failed to schedule cron event');
        } else {
            // Verify it was scheduled
            $verify = wp_next_scheduled($hook, array($schedule_id));
            if ($verify) {
                error_log('SUCCESS: Cron scheduled and verified at: ' . date('Y-m-d H:i:s', $verify));
            } else {
                error_log('ERROR: Cron not found after scheduling');
            }
        }

        error_log('=== END SCHEDULING ===');
    }

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
     * Delete schedule
     */
    private function delete_schedule()
    {
        $schedule_id = isset($_POST['schedule_id']) ? sanitize_text_field($_POST['schedule_id']) : '';

        if (empty($schedule_id)) {
            return;
        }

        $schedules = get_option('wcat_export_schedules', array());

        if (isset($schedules[$schedule_id])) {
            $frequency = $schedules[$schedule_id]['frequency'];
            $hook = 'wcat_scheduled_export_' . $frequency;

            // Clear cron job
            wp_clear_scheduled_hook($hook, array($schedule_id));

            // Remove from schedules
            unset($schedules[$schedule_id]);
            update_option('wcat_export_schedules', $schedules);

            // Schedule cron job - CRITICAL
            $this->schedule_cron_job($schedule_id, $frequency);

            // Update next_run time in the schedule data
            $next_timestamp = wp_next_scheduled('wcat_scheduled_export_' . $frequency, array($schedule_id));
            if ($next_timestamp) {
                $schedules[$schedule_id]['next_run'] = date('Y-m-d H:i:s', $next_timestamp);
            } else {
                error_log('WARNING: Cron job not found after scheduling!');
                $schedules[$schedule_id]['next_run'] = null;
            }

            // Save again with next_run updated
            update_option('wcat_export_schedules', $schedules);

            error_log('Schedule saved. Next run: ' . ($schedules[$schedule_id]['next_run'] ?? 'NOT SCHEDULED'));

            add_settings_error(
                'wcat_scheduled_exports',
                'schedule_deleted',
                __('Schedule deleted successfully!', 'wc-all-cart-tracker'),
                'success'
            );
        }
    }

    /**
     * Run scheduled export
     */
    public function run_scheduled_export($schedule_id)
    {
        error_log('=== WC Cart Tracker: Starting scheduled export ===');
        error_log('Schedule ID: ' . $schedule_id);

        $schedules = get_option('wcat_export_schedules', array());

        if (!isset($schedules[$schedule_id])) {
            error_log('ERROR: Schedule not found - ' . $schedule_id);
            return;
        }

        $schedule = $schedules[$schedule_id];
        error_log('Schedule Name: ' . $schedule['name']);

        if ($schedule['enabled'] !== 'yes') {
            error_log('SKIPPED: Schedule is disabled');
            return;
        }

        error_log('Generating export file...');
        $file_path = $this->generate_export_file($schedule);

        if (is_wp_error($file_path)) {
            error_log('ERROR: Export generation failed - ' . $file_path->get_error_message());
            $this->log_export_failure($schedule_id, 'generation_failed', $file_path->get_error_message());
            return;
        }

        if (!file_exists($file_path)) {
            error_log('ERROR: Export file does not exist at: ' . $file_path);
            $this->log_export_failure($schedule_id, 'file_missing', 'Generated file not found');
            return;
        }

        $file_size = filesize($file_path);
        error_log('Export file created: ' . $file_path . ' (Size: ' . $file_size . ' bytes)');

        if ($file_size === 0) {
            error_log('ERROR: Export file is empty');
            $this->log_export_failure($schedule_id, 'file_empty', 'Generated file has no content');
            unlink($file_path);
            return;
        }

        error_log('Attempting delivery via: ' . $schedule['delivery_method']);
        $result = $this->deliver_export($schedule, $file_path);

        $schedules[$schedule_id]['last_run'] = current_time('mysql');
        $schedules[$schedule_id]['next_run'] = date('Y-m-d H:i:s', $this->calculate_next_run($schedule['frequency']));

        if (is_wp_error($result)) {
            $schedules[$schedule_id]['last_error'] = $result->get_error_message();
            $schedules[$schedule_id]['last_status'] = 'failed';
            error_log('ERROR: Export delivery failed - ' . $result->get_error_message());
        } else {
            $schedules[$schedule_id]['last_error'] = '';
            $schedules[$schedule_id]['last_status'] = 'success';
            error_log('SUCCESS: Scheduled export completed successfully');
        }

        update_option('wcat_export_schedules', $schedules);

        if (file_exists($file_path)) {
            unlink($file_path);
            error_log('Temp file cleaned up');
        }

        error_log('=== WC Cart Tracker: Export process complete ===');
    }

    /**
     * Generate export file
     */
    private function generate_export_file($schedule)
    {
        global $wpdb;
        $table_name = WC_Cart_Tracker_Database::get_table_name();

        // Get data based on export type
        if ($schedule['export_type'] === 'active_carts') {
            $recent_date = date('Y-m-d H:i:s', strtotime('-24 hours'));
            $carts = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE is_active = %d AND last_updated >= %s ORDER BY last_updated DESC",
                1,
                $recent_date
            ));
        } else {
            // Cart history with filters
            $days_filter = isset($schedule['filters']['days']) ? absint($schedule['filters']['days']) : 30;
            $date_from = date('Y-m-d H:i:s', strtotime("-{$days_filter} days"));

            $carts = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE last_updated >= %s ORDER BY last_updated DESC",
                $date_from
            ));
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

        // Generate file
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/wcat-temp';

        if (!file_exists($temp_dir)) {
            mkdir($temp_dir, 0755, true);
        }

        $filename = 'wcat-export-' . $schedule['export_type'] . '-' . date('Y-m-d-His') . '.' . $schedule['export_format'];
        $file_path = $temp_dir . '/' . $filename;

        if ($schedule['export_format'] === 'csv') {
            $fp = fopen($file_path, 'w');
            fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
            foreach ($data as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);
        } else {
            // Excel format
            $fp = fopen($file_path, 'w');
            fwrite($fp, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
            fwrite($fp, '<?mso-application progid="Excel.Sheet"?>' . "\n");
            fwrite($fp, '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet">' . "\n");
            fwrite($fp, '<Worksheet ss:Name="Sheet1"><Table>' . "\n");

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
     * Deliver export via configured method
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
        error_log('--- Email Delivery Process ---');

        // Validate file
        if (!file_exists($file_path)) {
            error_log('ERROR: File does not exist: ' . $file_path);
            return new WP_Error('file_missing', __('Export file not found', 'wc-all-cart-tracker'));
        }

        $file_size = filesize($file_path);
        error_log('File to attach: ' . basename($file_path) . ' (' . $file_size . ' bytes)');

        // Parse recipients
        $recipients = explode("\n", $schedule['email_recipients']);
        $recipients = array_map('trim', $recipients);
        $recipients = array_filter($recipients);
        $recipients = array_filter($recipients, 'is_email'); // Validate all emails

        if (empty($recipients)) {
            error_log('ERROR: No valid email recipients found');
            error_log('Raw recipients: ' . $schedule['email_recipients']);
            return new WP_Error('no_recipients', __('No valid email recipients', 'wc-all-cart-tracker'));
        }

        error_log('Valid recipients: ' . implode(', ', $recipients));

        // Build email
        $subject = sprintf(
            __('[%s] Scheduled Cart Export - %s', 'wc-all-cart-tracker'),
            get_bloginfo('name'),
            $schedule['name']
        );

        $message = sprintf(
            __('Hello,%1$s%1$sPlease find attached the scheduled cart export "%2$s".%1$s%1$sExport Details:%1$s- Type: %3$s%1$s- Format: %4$s%1$s- Generated: %5$s%1$s- File Size: %6$s%1$s%1$sThis is an automated email from WC All Cart Tracker.', 'wc-all-cart-tracker'),
            "\r\n",
            $schedule['name'],
            ucfirst(str_replace('_', ' ', $schedule['export_type'])),
            strtoupper($schedule['export_format']),
            current_time('F j, Y g:i A'),
            size_format($file_size)
        );

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );

        $attachments = array($file_path);

        error_log('Subject: ' . $subject);
        error_log('Message length: ' . strlen($message) . ' characters');
        error_log('Headers: ' . print_r($headers, true));
        error_log('Attachments: ' . print_r($attachments, true));

        // Enable detailed error logging for wp_mail
        add_action('wp_mail_failed', function ($error) {
            error_log('WP_MAIL ERROR: ' . $error->get_error_message());
        });

        // Send email
        error_log('Calling wp_mail()...');
        $result = wp_mail($recipients, $subject, $message, $headers, $attachments);

        if ($result) {
            error_log('SUCCESS: wp_mail() returned true');
            error_log('Email sent to: ' . implode(', ', $recipients));
            return true;
        } else {
            error_log('FAILURE: wp_mail() returned false');

            // Check if there's a global PHPMailer error
            global $phpmailer;
            if (isset($phpmailer)) {
                error_log('PHPMailer ErrorInfo: ' . $phpmailer->ErrorInfo);
            }

            return new WP_Error(
                'email_failed',
                __('Failed to send email. Check error logs for details.', 'wc-all-cart-tracker')
            );
        }
    }

    /**
     * Deliver via FTP
     */
    private function deliver_via_ftp($schedule, $file_path)
    {
        // FTP settings from schedule
        $ftp_host = isset($schedule['ftp_host']) ? $schedule['ftp_host'] : '';
        $ftp_user = isset($schedule['ftp_user']) ? $schedule['ftp_user'] : '';
        $ftp_pass = isset($schedule['ftp_pass']) ? $schedule['ftp_pass'] : '';
        $ftp_path = isset($schedule['ftp_path']) ? $schedule['ftp_path'] : '/';

        if (empty($ftp_host) || empty($ftp_user) || empty($ftp_pass)) {
            return new WP_Error('ftp_config', __('FTP configuration incomplete', 'wc-all-cart-tracker'));
        }

        // Connect to FTP
        $conn = ftp_connect($ftp_host);
        if (!$conn) {
            return new WP_Error('ftp_connect', __('Failed to connect to FTP server', 'wc-all-cart-tracker'));
        }

        // Login
        $login = ftp_login($conn, $ftp_user, $ftp_pass);
        if (!$login) {
            ftp_close($conn);
            return new WP_Error('ftp_login', __('FTP login failed', 'wc-all-cart-tracker'));
        }

        // Upload file
        $remote_file = $ftp_path . '/' . basename($file_path);
        $upload = ftp_put($conn, $remote_file, $file_path, FTP_BINARY);

        ftp_close($conn);

        return $upload ? true : new WP_Error('ftp_upload', __('FTP upload failed', 'wc-all-cart-tracker'));
    }

    /**
     * AJAX: Test scheduled export
     */
    public function ajax_test_export()
    {
        check_ajax_referer('wcat_scheduled_export', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wc-all-cart-tracker')));
        }

        $schedule_id = isset($_POST['schedule_id']) ? sanitize_text_field($_POST['schedule_id']) : '';

        if (empty($schedule_id)) {
            wp_send_json_error(array('message' => __('Invalid schedule ID', 'wc-all-cart-tracker')));
        }

        error_log('=== MANUAL TEST EXPORT TRIGGERED ===');
        error_log('Schedule ID: ' . $schedule_id);
        error_log('Triggered by user: ' . get_current_user_id());

        // Run the export
        $this->run_scheduled_export($schedule_id);

        // Check if it succeeded
        $schedules = get_option('wcat_export_schedules', array());
        $last_status = isset($schedules[$schedule_id]['last_status']) ? $schedules[$schedule_id]['last_status'] : 'unknown';
        $last_error = isset($schedules[$schedule_id]['last_error']) ? $schedules[$schedule_id]['last_error'] : '';

        if ($last_status === 'success') {
            wp_send_json_success(array(
                'message' => __('Test export sent successfully! Check your email.', 'wc-all-cart-tracker')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Test export failed. Error: ', 'wc-all-cart-tracker') . $last_error . ' ' . __('Check debug.log for details.', 'wc-all-cart-tracker')
            ));
        }
    }
    /**
     * AJAX: Delete schedule
     */
    public function ajax_delete_schedule()
    {
        check_ajax_referer('wcat_scheduled_export', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wc-all-cart-tracker')));
        }

        $schedule_id = isset($_POST['schedule_id']) ? sanitize_text_field($_POST['schedule_id']) : '';

        if (empty($schedule_id)) {
            wp_send_json_error(array('message' => __('Invalid schedule ID', 'wc-all-cart-tracker')));
        }

        $schedules = get_option('wcat_export_schedules', array());

        if (isset($schedules[$schedule_id])) {
            $frequency = $schedules[$schedule_id]['frequency'];
            $hook = 'wcat_scheduled_export_' . $frequency;

            wp_clear_scheduled_hook($hook, array($schedule_id));
            unset($schedules[$schedule_id]);
            update_option('wcat_export_schedules', $schedules);

            wp_send_json_success(array('message' => __('Schedule deleted', 'wc-all-cart-tracker')));
        }

        wp_send_json_error(array('message' => __('Schedule not found', 'wc-all-cart-tracker')));
    }

    /**
     * Log export failure for debugging
     */
    private function log_export_failure($schedule_id, $failure_type, $message)
    {
        $failures = get_option('wcat_export_failures', array());

        if (!isset($failures[$schedule_id])) {
            $failures[$schedule_id] = array();
        }

        $failures[$schedule_id][] = array(
            'time' => current_time('mysql'),
            'type' => $failure_type,
            'message' => $message
        );

        // Keep only last 10 failures per schedule
        if (count($failures[$schedule_id]) > 10) {
            $failures[$schedule_id] = array_slice($failures[$schedule_id], -10);
        }

        update_option('wcat_export_failures', $failures);
    }

    /**
     * Render scheduled exports admin page
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
     * Get schedule by ID
     */
    public static function get_schedule($schedule_id)
    {
        $schedules = self::get_schedules();
        return isset($schedules[$schedule_id]) ? $schedules[$schedule_id] : null;
    }

    private function send_diagnostic_email()
    {
        if (!isset($_POST['test_email_recipient']) || !current_user_can('manage_woocommerce')) {
            return;
        }

        check_admin_referer('wcat_test_email');

        $recipient = sanitize_email($_POST['test_email_recipient']);

        if (!is_email($recipient)) {
            add_settings_error(
                'wcat_scheduled_exports',
                'invalid_email',
                __('Invalid email address', 'wc-all-cart-tracker'),
                'error'
            );
            return;
        }

        error_log('=== EMAIL DIAGNOSTIC TEST ===');
        error_log('Recipient: ' . $recipient);
        error_log('Triggered by: User ID ' . get_current_user_id());

        $subject = '[' . get_bloginfo('name') . '] ' . __('Cart Tracker Email Test', 'wc-all-cart-tracker');

        $message = __('Hello,', 'wc-all-cart-tracker') . "\r\n\r\n";
        $message .= __('This is a test email from WC All Cart Tracker.', 'wc-all-cart-tracker') . "\r\n\r\n";
        $message .= __('If you received this email, your email configuration is working correctly!', 'wc-all-cart-tracker') . "\r\n\r\n";
        $message .= __('Configuration Details:', 'wc-all-cart-tracker') . "\r\n";
        $message .= '- ' . __('Site:', 'wc-all-cart-tracker') . ' ' . get_bloginfo('name') . "\r\n";
        $message .= '- ' . __('Admin Email:', 'wc-all-cart-tracker') . ' ' . get_option('admin_email') . "\r\n";
        $message .= '- ' . __('PHP Version:', 'wc-all-cart-tracker') . ' ' . PHP_VERSION . "\r\n";
        $message .= '- ' . __('WordPress Version:', 'wc-all-cart-tracker') . ' ' . get_bloginfo('version') . "\r\n";
        $message .= '- ' . __('Test Time:', 'wc-all-cart-tracker') . ' ' . current_time('F j, Y g:i:s A') . "\r\n\r\n";
        $message .= __('WC All Cart Tracker - Scheduled Exports', 'wc-all-cart-tracker');

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );

        error_log('Sending diagnostic email...');
        error_log('Subject: ' . $subject);
        error_log('Recipient: ' . $recipient);

        // Track errors
        add_action('wp_mail_failed', function ($error) {
            error_log('DIAGNOSTIC EMAIL ERROR: ' . $error->get_error_message());
        });

        $result = wp_mail($recipient, $subject, $message, $headers);

        if ($result) {
            error_log('SUCCESS: Diagnostic email sent');
            add_settings_error(
                'wcat_scheduled_exports',
                'test_email_success',
                sprintf(
                    __('✓ Test email sent successfully to %s. Check your inbox (and spam folder).', 'wc-all-cart-tracker'),
                    $recipient
                ),
                'success'
            );
        } else {
            error_log('FAILURE: Diagnostic email failed');

            global $phpmailer;
            $error_details = '';
            if (isset($phpmailer)) {
                $error_details = ' Error: ' . $phpmailer->ErrorInfo;
                error_log('PHPMailer Error: ' . $phpmailer->ErrorInfo);
            }

            add_settings_error(
                'wcat_scheduled_exports',
                'test_email_failed',
                __('✗ Failed to send test email.', 'wc-all-cart-tracker') . $error_details . ' ' . __('Check debug.log for details.', 'wc-all-cart-tracker'),
                'error'
            );
        }

        error_log('=== END EMAIL DIAGNOSTIC TEST ===');
    }
}

// Initialize
function wc_cart_tracker_init_scheduled_export()
{
    return WC_Cart_Tracker_Scheduled_Export::get_instance();
}
add_action('plugins_loaded', 'wc_cart_tracker_init_scheduled_export');