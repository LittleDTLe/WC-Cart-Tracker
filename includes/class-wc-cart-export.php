<?php
/**
 * Export Handler for WC All Cart Tracker
 *
 * Handles CSV, Excel, and Google Sheets export functionality with full data sanitization
 *
 * @package WC_All_Cart_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Cart_Tracker_Export
{
    public function __construct()
    {
        add_action('admin_init', array($this, 'handle_export_request'));
    }

    /**
     * Handle export requests
     */
    public function handle_export_request()
    {
        if (!isset($_GET['wcat_export']) || !current_user_can('manage_woocommerce')) {
            return;
        }

        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'wcat_export_nonce')) {
            wp_die(__('Security check failed', 'wc-all-cart-tracker'));
        }

        $export_type = sanitize_text_field($_GET['wcat_export']);
        $format = isset($_GET['format']) ? sanitize_text_field($_GET['format']) : 'csv';

        $columns = isset($_GET['columns']) ?
            explode(',', sanitize_text_field($_GET['columns'])) :
            WC_Cart_Tracker_Export_Templates::get_default_columns();

        // Validate format
        if (!in_array($format, array('csv', 'excel', 'google_sheets'))) {
            $format = 'csv';
        }

        switch ($export_type) {
            case 'active_carts':
                $this->export_active_carts($format, $columns);
                break;
            case 'cart_history':
                $this->export_cart_history($format, $columns);
                break;
        }
    }

    /**
     * Export active carts with analytics metrics
     */
    private function export_active_carts($format, $columns = null)
    {
        global $wpdb;
        $table_name = WC_Cart_Tracker_Database::get_table_name();

        // Get filter parameters
        $days = isset($_GET['days']) ? absint($_GET['days']) : 30;
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

        $using_custom_range = false;
        if (!empty($date_from) && !empty($date_to)) {
            $using_custom_range = true;
        }

        // Get analytics data
        if ($using_custom_range) {
            $analytics = WC_Cart_Tracker_Analytics::get_analytics_data_by_date_range($date_from, $date_to);
        } else {
            $analytics = WC_Cart_Tracker_Analytics::get_analytics_data($days);
        }

        // Sanitize analytics data
        $clean_analytics = WC_Cart_Tracker_Data_Sanitizer::prepare_analytics_for_export($analytics);

        // Calculate distribution percentages
        $total_carts_by_type = $analytics['registered_carts'] + $analytics['guest_carts'];
        $registered_distribution = $total_carts_by_type > 0 ?
            number_format(($analytics['registered_carts'] / $total_carts_by_type) * 100, 2, '.', '') : '0.00';
        $guest_distribution = $total_carts_by_type > 0 ?
            number_format(($analytics['guest_carts'] / $total_carts_by_type) * 100, 2, '.', '') : '0.00';

        $recent_date = date('Y-m-d H:i:s', strtotime('-24 hours'));

        // Get active carts
        $carts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
            WHERE is_active = %d AND last_updated >= %s 
            ORDER BY last_updated DESC",
            1,
            $recent_date
        ));

        if ($columns === null) {
            $columns = WC_Cart_Tracker_Export_Templates::get_default_columns();
        }

        // Prepare data with analytics section first
        $data = array();

        // === ANALYTICS OVERVIEW SECTION ===
        $data[] = array('=== ANALYTICS OVERVIEW ===');

        // Export Info
        $period_text = $using_custom_range ?
            sprintf('Custom Range: %s to %s', date('M d, Y', strtotime($date_from)), date('M d, Y', strtotime($date_to))) :
            sprintf('Last %d Days', $days);
        $data[] = array('Export Period:', $period_text);
        $data[] = array('Export Date:', date('F j, Y - g:i A'));
        $data[] = array('');

        // Key Metrics
        $data[] = array('=== KEY METRICS ===');
        $data[] = array('Metric', 'Value', 'Details');
        $data[] = array(
            'Conversion Rate',
            $clean_analytics['conversion_rate'],
            sprintf('%d / %d carts tracked', $analytics['converted_carts'], $analytics['total_carts'])
        );
        $data[] = array('Active Carts', $clean_analytics['active_carts'], 'Currently in cart');
        $data[] = array('Abandoned Carts', $clean_analytics['abandoned_carts'], 'Inactive > 24hrs');
        $data[] = array('Deleted Carts', $clean_analytics['deleted_carts'], 'Cleared by user');
        $data[] = array(
            'Overall Revenue Potential',
            $clean_analytics['revenue_potential'],
            'Total of active carts (last 7 days)'
        );
        $data[] = array('');

        // Average Cart Values
        $data[] = array('=== AVERAGE CART VALUES ===');
        $data[] = array('Type', 'Value');
        $data[] = array('Active Carts Average', $clean_analytics['avg_active_cart']);
        $data[] = array('Converted Carts Average', $clean_analytics['avg_converted_cart']);
        $data[] = array('');

        // Revenue Potential Breakdown
        $data[] = array('=== REVENUE POTENTIAL BREAKDOWN ===');
        $data[] = array('Category', 'Value', 'Description');
        $data[] = array(
            'Overall Revenue Potential',
            $clean_analytics['revenue_potential'],
            'Total potential from carts updated in last 7 days'
        );

        // Add active/abandoned potential if available
        if (isset($analytics['active_cart_potential'])) {
            $data[] = array(
                'Active Cart Potential',
                WC_Cart_Tracker_Data_Sanitizer::clean_price($analytics['active_cart_potential']),
                'Carts updated within last 24 hours'
            );
        }
        if (isset($analytics['abandoned_cart_potential'])) {
            $data[] = array(
                'Abandoned Cart Potential',
                WC_Cart_Tracker_Data_Sanitizer::clean_price($analytics['abandoned_cart_potential']),
                'Carts between 24 hours and 7 days old'
            );
        }
        if (isset($analytics['stale_carts']) && $analytics['stale_carts'] > 0) {
            $data[] = array(
                'Stale Carts (>7 days)',
                $analytics['stale_carts'],
                sprintf(
                    'Value: %s (excluded from revenue potential)',
                    WC_Cart_Tracker_Data_Sanitizer::clean_price($analytics['stale_cart_value'])
                )
            );
        }
        $data[] = array('');

        // Customer Type Analysis
        $data[] = array('=== BY CUSTOMER TYPE ===');
        $data[] = array('Customer Type', 'Cart Count', 'Distribution %', 'Conversion Rate', 'Converted Count');
        $data[] = array(
            'Registered Users',
            $clean_analytics['registered_carts'],
            $registered_distribution,
            $clean_analytics['registered_conversion_rate'],
            $analytics['registered_converted']
        );
        $data[] = array(
            'Guest Users',
            $clean_analytics['guest_carts'],
            $guest_distribution,
            $clean_analytics['guest_conversion_rate'],
            $analytics['guest_converted']
        );
        $data[] = array('');

        // Cart Summary
        $data[] = array('=== CART SUMMARY ===');
        $data[] = array('Summary Item', 'Count');
        $data[] = array('Total Carts Tracked', $clean_analytics['total_carts']);
        $data[] = array('Converted to Order', $clean_analytics['converted_carts']);
        $data[] = array('Overall Conversion Rate', $clean_analytics['conversion_rate']);
        $data[] = array('Abandonment Rate', $clean_analytics['abandonment_rate']);
        $data[] = array('');
        $data[] = array('');

        // === ACTIVE CARTS DATA SECTION ===
        $data[] = array('=== ACTIVE CARTS DATA ===');
        $data[] = array('');
        $data[] = WC_Cart_Tracker_Export_Templates::get_column_headers($columns);


        foreach ($carts as $cart) {
            $row = array();

            // ✅ Extract only selected columns
            foreach ($columns as $column_key) {
                $value = WC_Cart_Tracker_Export_Templates::extract_column_data($cart, $column_key);
                $row[] = WC_Cart_Tracker_Data_Sanitizer::escape_csv($value);
            }

            $data[] = $row;
        }

        $filename = 'active-carts-analytics-' . date('Y-m-d-His');
        $this->output_export($data, $filename, $format);
    }

    /**
     * Export cart history
     */
    private function export_cart_history($format, $columns = null)
    {
        global $wpdb;
        $table_name = WC_Cart_Tracker_Database::get_table_name();

        // Get filter parameters
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
        $days_filter = isset($_GET['days']) ? absint($_GET['days']) : 30;

        if (!in_array($days_filter, array(7, 30, 60, 90, 365))) {
            $days_filter = 30;
        }

        // Build query based on filters
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days_filter} days"));
        $where_clauses = array("last_updated >= %s");
        $query_params = array($date_from);

        if ($status_filter === 'converted') {
            $where_clauses[] = "cart_status = %s";
            $query_params[] = 'converted';
        } elseif ($status_filter === 'deleted') {
            $where_clauses[] = "cart_status = %s";
            $query_params[] = 'deleted';
        } elseif ($status_filter === 'abandoned') {
            $where_clauses[] = "is_active = 1";
            $where_clauses[] = "last_updated < %s";
            $query_params[] = date('Y-m-d H:i:s', strtotime('-24 hours'));
        } elseif ($status_filter === 'inactive') {
            $where_clauses[] = "is_active = 0";
        }

        $where_sql = implode(' AND ', $where_clauses);

        $carts_query = "SELECT * FROM {$table_name} WHERE {$where_sql} ORDER BY last_updated DESC";
        $carts = $wpdb->get_results($wpdb->prepare($carts_query, $query_params));

        if ($columns === null) {
            $columns = WC_Cart_Tracker_Export_Templates::get_default_columns();
        }

        // Prepare data
        $data = array();

        // Export Info
        $data[] = array('=== CART HISTORY EXPORT ===');
        $data[] = array('Export Period:', sprintf('Last %d Days', $days_filter));
        $data[] = array('Status Filter:', ucfirst($status_filter));
        $data[] = array('Export Date:', date('F j, Y - g:i A'));
        $data[] = array('Total Records:', count($carts));
        $data[] = array('');
        $data[] = array('');

        // Column headers
        $data[] = WC_Cart_Tracker_Export_Templates::get_column_headers($columns);

        foreach ($carts as $cart) {
            $row = array();

            // ✅ Extract only selected columns
            foreach ($columns as $column_key) {
                $value = WC_Cart_Tracker_Export_Templates::extract_column_data($cart, $column_key);
                $row[] = WC_Cart_Tracker_Data_Sanitizer::escape_csv($value);
            }

            $data[] = $row;
        }

        $filename = 'cart-history-' . date('Y-m-d-His');
        $this->output_export($data, $filename, $format);
    }

    /**
     * Output the export based on format
     */
    private function output_export($data, $filename, $format)
    {
        switch ($format) {
            case 'excel':
                $this->output_excel($data, $filename);
                break;
            case 'google_sheets':
                $this->output_google_sheets($data, $filename);
                break;
            case 'csv':
            default:
                $this->output_csv($data, $filename);
                break;
        }
    }

    /**
     * Output CSV format with UTF-8 BOM for Excel compatibility
     */
    private function output_csv($data, $filename)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Output Excel XML format for better compatibility
     * All data is already sanitized by the sanitizer class
     */
    private function output_excel($data, $filename)
    {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Excel XML format for better compatibility
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        echo ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        echo '<Worksheet ss:Name="Sheet1">' . "\n";
        echo '<Table>' . "\n";

        foreach ($data as $row) {
            echo '<Row>' . "\n";
            foreach ($row as $cell) {
                // Strip any remaining HTML and encode for XML
                $cell = WC_Cart_Tracker_Data_Sanitizer::strip_html($cell);
                $cell = htmlspecialchars($cell, ENT_XML1, 'UTF-8');
                echo '<Cell><Data ss:Type="String">' . $cell . '</Data></Cell>' . "\n";
            }
            echo '</Row>' . "\n";
        }

        echo '</Table>' . "\n";
        echo '</Worksheet>' . "\n";
        echo '</Workbook>';
        exit;
    }

    /**
     * Output Google Sheets compatible CSV
     */
    private function output_google_sheets($data, $filename)
    {
        // Google Sheets uses the same CSV format
        // Just add instructions in the filename
        $filename .= '-google-sheets';
        $this->output_csv($data, $filename);
    }

    /**
     * Render export buttons on admin pages
     */
    public static function render_export_buttons($page_type, $current_filters = array())
    {
        $nonce = wp_create_nonce('wcat_export_nonce');

        ?>
        <div class="wcat-export-section"
            style="margin: 15px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
            <h3 style="margin-top: 0; font-size: 14px; font-weight: 600;">
                <?php esc_html_e('Export Options', 'wc-all-cart-tracker'); ?>
            </h3>

            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <?php if ($page_type === 'dashboard'): ?>
                    <!-- Active Carts Export -->
                    <a href="<?php echo esc_url(add_query_arg(array_merge($current_filters, array(
                        'wcat_export' => 'active_carts',
                        'format' => 'csv',
                        '_wpnonce' => $nonce
                    )), admin_url('admin.php'))); ?>" class="button button-primary">
                        <span class="dashicons dashicons-download" style="vertical-align: middle; margin-top: 3px;"></span>
                        <?php esc_html_e('Export Active Carts (CSV)', 'wc-all-cart-tracker'); ?>
                    </a>

                    <a href="<?php echo esc_url(add_query_arg(array_merge($current_filters, array(
                        'wcat_export' => 'active_carts',
                        'format' => 'excel',
                        '_wpnonce' => $nonce
                    )), admin_url('admin.php'))); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-media-spreadsheet" style="vertical-align: middle; margin-top: 3px;"></span>
                        <?php esc_html_e('Excel Format', 'wc-all-cart-tracker'); ?>
                    </a>

                <?php elseif ($page_type === 'history'): ?>
                    <!-- Cart History Export -->
                    <a href="<?php echo esc_url(add_query_arg(array_merge($current_filters, array(
                        'wcat_export' => 'cart_history',
                        'format' => 'csv',
                        '_wpnonce' => $nonce
                    )), admin_url('admin.php'))); ?>" class="button button-primary">
                        <span class="dashicons dashicons-download" style="vertical-align: middle; margin-top: 3px;"></span>
                        <?php esc_html_e('Export History (CSV)', 'wc-all-cart-tracker'); ?>
                    </a>

                    <a href="<?php echo esc_url(add_query_arg(array_merge($current_filters, array(
                        'wcat_export' => 'cart_history',
                        'format' => 'excel',
                        '_wpnonce' => $nonce
                    )), admin_url('admin.php'))); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-media-spreadsheet" style="vertical-align: middle; margin-top: 3px;"></span>
                        <?php esc_html_e('Excel Format', 'wc-all-cart-tracker'); ?>
                    </a>
                <?php endif; ?>

                <span class="description" style="margin-left: 10px; color: #666;">
                    <span class="dashicons dashicons-yes-alt" style="color: #46b450; vertical-align: middle;"></span>
                    <?php esc_html_e('All exports are sanitized and Excel-ready', 'wc-all-cart-tracker'); ?>
                </span>
            </div>

            <p class="description" style="margin: 10px 0 0 0; font-size: 12px;">
                <?php esc_html_e('Exports include current filters and date ranges. All HTML is removed and data is formatted for spreadsheet compatibility.', 'wc-all-cart-tracker'); ?>
            </p>
        </div>
        <?php
    }
}

// Initialize export handler
new WC_Cart_Tracker_Export();