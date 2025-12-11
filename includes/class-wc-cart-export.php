<?php
/**
 * Export Handler for WC All Cart Tracker
 *
 * Handles CSV, Excel, and Google Sheets export functionality
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
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

        // Validate format
        if (!in_array($format, array('csv', 'excel', 'google_sheets'))) {
            $format = 'csv';
        }

        switch ($export_type) {
            case 'active_carts':
                $this->export_active_carts($format);
                break;
            case 'cart_history':
                $this->export_cart_history($format);
                break;
        }
    }

    /**
     * Export active carts with analytics metrics
     */
    private function export_active_carts($format)
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

        // Calculate distribution percentages
        $total_carts_by_type = $analytics['registered_carts'] + $analytics['guest_carts'];
        $registered_distribution = $total_carts_by_type > 0 ?
            round(($analytics['registered_carts'] / $total_carts_by_type) * 100, 2) : 0;
        $guest_distribution = $total_carts_by_type > 0 ?
            round(($analytics['guest_carts'] / $total_carts_by_type) * 100, 2) : 0;

        $recent_date = date('Y-m-d H:i:s', strtotime('-24 hours'));

        // Get active carts
        $carts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
            WHERE is_active = %d AND last_updated >= %s 
            ORDER BY last_updated DESC",
            1,
            $recent_date
        ));

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
            $analytics['conversion_rate'] . '%',
            sprintf('%d / %d carts tracked', $analytics['converted_carts'], $analytics['total_carts'])
        );
        $data[] = array('Active Carts', $analytics['active_carts'], 'Currently in cart');
        $data[] = array('Abandoned Carts', $analytics['abandoned_carts'], 'Inactive > 24hrs');
        $data[] = array('Deleted Carts', $analytics['deleted_carts'], 'Cleared by user');
        $data[] = array(
            'Overall Revenue Potential',
            $this->format_currency($analytics['overall_revenue_potential']),
            'Total of active carts (last 7 days)'
        );
        $data[] = array('');

        // Average Cart Values
        $data[] = array('=== AVERAGE CART VALUES ===');
        $data[] = array('Type', 'Value');
        $data[] = array('Active Carts Average', $this->format_currency($analytics['avg_active_cart']));
        $data[] = array('Converted Carts Average', $this->format_currency($analytics['avg_converted_cart']));
        $data[] = array('');

        // Revenue Potential Breakdown
        $data[] = array('=== REVENUE POTENTIAL BREAKDOWN ===');
        $data[] = array('Category', 'Value', 'Description');
        $data[] = array(
            'Overall Revenue Potential',
            $this->format_currency($analytics['overall_revenue_potential']),
            'Total potential from carts updated in last 7 days'
        );
        $data[] = array(
            'Active Cart Potential',
            $this->format_currency($analytics['active_cart_potential']),
            'Carts updated within last 24 hours'
        );
        $data[] = array(
            'Abandoned Cart Potential',
            $this->format_currency($analytics['abandoned_cart_potential']),
            'Carts between 24 hours and 7 days old'
        );
        if (isset($analytics['stale_carts']) && $analytics['stale_carts'] > 0) {
            $data[] = array(
                'Stale Carts (>7 days)',
                $analytics['stale_carts'],
                sprintf('Value: %s (excluded from revenue potential)', $this->format_currency($analytics['stale_cart_value']))
            );
        }
        $data[] = array('');

        // Customer Type Analysis
        $data[] = array('=== BY CUSTOMER TYPE ===');
        $data[] = array('Customer Type', 'Cart Count', 'Distribution', 'Conversion Rate', 'Converted Count');
        $data[] = array(
            'Registered Users',
            $analytics['registered_carts'],
            $registered_distribution . '%',
            $analytics['registered_conversion_rate'] . '%',
            $analytics['registered_converted']
        );
        $data[] = array(
            'Guest Users',
            $analytics['guest_carts'],
            $guest_distribution . '%',
            $analytics['guest_conversion_rate'] . '%',
            $analytics['guest_converted']
        );
        $data[] = array('');

        // Cart Summary
        $data[] = array('=== CART SUMMARY ===');
        $data[] = array('Summary Item', 'Count');
        $data[] = array('Total Carts Tracked', $analytics['total_carts']);
        $data[] = array('Converted to Order', $analytics['converted_carts']);
        $data[] = array('Overall Conversion Rate', $analytics['conversion_rate'] . '%');
        $data[] = array('Abandonment Rate', $analytics['abandonment_rate'] . '%');
        $data[] = array('');
        $data[] = array('');

        // === ACTIVE CARTS DATA SECTION ===
        $data[] = array('=== ACTIVE CARTS DATA ===');
        $data[] = array('');
        $data[] = array(
            'ID',
            'Last Updated',
            'Customer Name',
            'Customer Email',
            'User ID',
            'Session ID',
            'Past Purchases',
            'Cart Total',
            'Items Count',
            'Cart Status',
            'Cart Items'
        );

        foreach ($carts as $cart) {
            $cart_items = json_decode($cart->cart_content, true);
            $items_count = is_array($cart_items) ? count($cart_items) : 0;

            // Format cart items for export
            $items_text = '';
            if (!empty($cart_items) && is_array($cart_items)) {
                $items_array = array();
                foreach ($cart_items as $item) {
                    $items_array[] = sprintf(
                        '%s (Qty: %d, Price: %s)',
                        $item['product_name'],
                        $item['quantity'],
                        $this->format_currency($item['line_total'])
                    );
                }
                $items_text = implode('; ', $items_array);
            }

            $data[] = array(
                $cart->id,
                $cart->last_updated,
                $cart->customer_name ?: 'Guest',
                $cart->customer_email ?: 'N/A',
                $cart->user_id > 0 ? $cart->user_id : 'Guest',
                substr($cart->session_id, 0, 20) . '...',
                $cart->past_purchases,
                $this->format_currency($cart->cart_total),
                $items_count,
                ucfirst($cart->cart_status),
                $items_text
            );
        }

        $filename = 'active-carts-analytics-' . date('Y-m-d-His');
        $this->output_export($data, $filename, $format);
    }

    /**
     * Format currency for export (removes HTML)
     */
    private function format_currency($amount)
    {
        return html_entity_decode(strip_tags(wc_price($amount)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Export cart history
     */
    private function export_cart_history($format)
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
        $data[] = array(
            'ID',
            'Date',
            'Customer Name',
            'Customer Email',
            'User ID',
            'Session ID',
            'Status',
            'Cart Total',
            'Items Count',
            'Past Purchases',
            'Is Active',
            'Cart Items'
        );

        foreach ($carts as $cart) {
            $cart_items = json_decode($cart->cart_content, true);
            $items_count = is_array($cart_items) ? count($cart_items) : 0;

            // Determine status label
            $status_label = ucfirst($cart->cart_status);
            if ($cart->cart_status === 'active' && strtotime($cart->last_updated) < strtotime('-24 hours')) {
                $status_label = 'Abandoned';
            }

            // Format cart items for export
            $items_text = '';
            if (!empty($cart_items) && is_array($cart_items)) {
                $items_array = array();
                foreach ($cart_items as $item) {
                    $items_array[] = sprintf(
                        '%s (Qty: %d, Price: %s)',
                        $item['product_name'],
                        $item['quantity'],
                        $this->format_currency($item['line_total'])
                    );
                }
                $items_text = implode('; ', $items_array);
            }

            $data[] = array(
                $cart->id,
                $cart->last_updated,
                $cart->customer_name ?: 'Guest',
                $cart->customer_email ?: 'N/A',
                $cart->user_id > 0 ? $cart->user_id : 'Guest',
                substr($cart->session_id, 0, 20) . '...',
                $status_label,
                $this->format_currency($cart->cart_total),
                $items_count,
                $cart->past_purchases,
                $cart->is_active ? 'Yes' : 'No',
                $items_text
            );
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
     * Output CSV format
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
     * Output Excel format (using CSV with proper headers)
     * For true Excel format, consider using PHPSpreadsheet library
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
                $cell = htmlspecialchars($cell, ENT_XML1);
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
}

// Initialize export handler
new WC_Cart_Tracker_Export();