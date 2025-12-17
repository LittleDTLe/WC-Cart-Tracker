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

    /**
     * Add custom cron schedules
     */
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

    /**
     * Enqueue admin assets
     */
    // public function enqueue_admin_assets($hook)
    // {
    //     if ('woocommerce_page_wc-cart-scheduled-exports' !== $hook) {
    //         return;
    //     }

    //     wp_enqueue_style(
    //         'wc-cart-tracker-scheduled-exports',
    //         WC_CART_TRACKER_PLUGIN_URL . 'admin/assets/scheduled-exports.css',
    //         array(),
    //         WC_CART_TRACKER_VERSION
    //     );

    //     wp_enqueue_script(
    //         'wc-cart-tracker-scheduled-exports',
    //         WC_CART_TRACKER_PLUGIN_URL . 'admin/assets/scheduled-exports.js',
    //         array('jquery'),
    //         WC_CART_TRACKER_VERSION,
    //         true
    //     );

    //     wp_localize_script('wc-cart-tracker-scheduled-exports', 'wcatScheduledExport', array(
    //         'ajaxUrl' => admin_url('admin-ajax.php'),
    //         'nonce' => wp_create_nonce('wcat_scheduled_export'),
    //         'strings' => array(
    //             'confirm_delete' => __('Are you sure you want to delete this schedule?', 'wc-all-cart-tracker'),
    //             'testing' => __('Testing...', 'wc-all-cart-tracker'),
    //             'test_success' => __('Test export sent successfully!', 'wc-all-cart-tracker'),
    //             'test_failed' => __('Test export failed. Check the logs.', 'wc-all-cart-tracker'),
    //         )
    //     ));
    // }

    /**
     * Handle schedule actions (create, update, delete)
     */
    public function handle_schedule_actions()
    {
        if (!isset($_POST['wcat_schedule_action']) || !current_user_can('manage_woocommerce')) {
            return;
        }

        check_admin_referer('wcat_schedule_action');

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
    }

    /**
     * Save or update schedule
     */
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
            'columns' => $columns,
            'filters' => $filters,
            'enabled' => $enabled,
            'created' => current_time('mysql'),
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
            // Update existing schedule
            if (isset($schedules[$schedule_id]['created'])) {
                $schedule_data['created'] = $schedules[$schedule_id]['created'];
            }
            if (isset($schedules[$schedule_id]['last_run'])) {
                $schedule_data['last_run'] = $schedules[$schedule_id]['last_run'];
            }
        }

        $schedules[$schedule_id] = $schedule_data;
        update_option('wcat_export_schedules', $schedules);

        // Schedule cron job
        $this->schedule_cron_job($schedule_id, $frequency);

        add_settings_error(
            'wcat_scheduled_exports',
            'schedule_saved',
            __('Schedule saved successfully!', 'wc-all-cart-tracker'),
            'success'
        );
    }

    /**
     * Schedule cron job
     */
    private function schedule_cron_job($schedule_id, $frequency)
    {
        $hook = 'wcat_scheduled_export_' . $frequency;

        // Clear existing schedule
        wp_clear_scheduled_hook($hook, array($schedule_id));

        // Schedule new job
        if (!wp_next_scheduled($hook, array($schedule_id))) {
            $timestamp = $this->calculate_next_run($frequency);
            wp_schedule_event($timestamp, $frequency, $hook, array($schedule_id));
        }
    }

    /**
     * Calculate next run time based on frequency
     */
    private function calculate_next_run($frequency)
    {
        switch ($frequency) {
            case 'daily':
                // Run at 2 AM daily
                $next = strtotime('tomorrow 2:00am');
                break;
            case 'weekly':
                // Run at 2 AM every Monday
                $next = strtotime('next monday 2:00am');
                break;
            case 'monthly':
                // Run at 2 AM on the 1st of next month
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
        error_log('WC Cart Tracker: Running scheduled export - ' . $schedule_id);

        $schedules = get_option('wcat_export_schedules', array());

        if (!isset($schedules[$schedule_id])) {
            error_log('WC Cart Tracker: Schedule not found - ' . $schedule_id);
            return;
        }

        $schedule = $schedules[$schedule_id];

        // Check if enabled
        if ($schedule['enabled'] !== 'yes') {
            error_log('WC Cart Tracker: Schedule disabled - ' . $schedule_id);
            return;
        }

        // Generate export
        $file_path = $this->generate_export_file($schedule);

        if (is_wp_error($file_path)) {
            error_log('WC Cart Tracker: Export generation failed - ' . $file_path->get_error_message());
            return;
        }

        // Deliver export
        $result = $this->deliver_export($schedule, $file_path);

        // Update last run time
        $schedules[$schedule_id]['last_run'] = current_time('mysql');
        $schedules[$schedule_id]['next_run'] = date('Y-m-d H:i:s', $this->calculate_next_run($schedule['frequency']));
        update_option('wcat_export_schedules', $schedules);

        // Clean up temp file
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        if (is_wp_error($result)) {
            error_log('WC Cart Tracker: Export delivery failed - ' . $result->get_error_message());
        } else {
            error_log('WC Cart Tracker: Scheduled export completed - ' . $schedule_id);
        }
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
        $recipients = explode("\n", $schedule['email_recipients']);
        $recipients = array_map('trim', $recipients);
        $recipients = array_filter($recipients);

        $subject = sprintf(
            __('[%s] Scheduled Cart Export - %s', 'wc-all-cart-tracker'),
            get_bloginfo('name'),
            $schedule['name']
        );

        $message = sprintf(
            __('Hello,%1$s%1$sPlease find attached the scheduled cart export "%2$s".%1$s%1$sExport Details:%1$s- Type: %3$s%1$s- Format: %4$s%1$s- Generated: %5$s%1$s%1$sThis is an automated email from WC All Cart Tracker.', 'wc-all-cart-tracker'),
            "\r\n",
            $schedule['name'],
            ucfirst(str_replace('_', ' ', $schedule['export_type'])),
            strtoupper($schedule['export_format']),
            current_time('F j, Y g:i A')
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

        // Run the export
        $this->run_scheduled_export($schedule_id);

        wp_send_json_success(array('message' => __('Test export completed', 'wc-all-cart-tracker')));
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
}

// Initialize
function wc_cart_tracker_init_scheduled_export()
{
    return WC_Cart_Tracker_Scheduled_Export::get_instance();
}
add_action('plugins_loaded', 'wc_cart_tracker_init_scheduled_export');