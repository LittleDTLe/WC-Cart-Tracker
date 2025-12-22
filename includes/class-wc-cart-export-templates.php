<?php
/**
 * Export Templates Database Handler
 *
 * Manages custom export column templates
 *
 * @package WC_All_Cart_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Cart_Tracker_Export_Templates
{
    const OPTION_PREFIX = 'wcat_export_template_';
    const META_KEY = 'wcat_user_export_templates';

    /**
     * Get all available columns for export
     */
    public static function get_available_columns()
    {
        return array(
            // Basic Info
            'id' => array(
                'label' => __('Cart ID', 'wc-all-cart-tracker'),
                'group' => __('Basic Information', 'wc-all-cart-tracker'),
                'default' => true,
                'description' => __('Unique cart identifier', 'wc-all-cart-tracker')
            ),
            'date' => array(
                'label' => __('Date/Time', 'wc-all-cart-tracker'),
                'group' => __('Basic Information', 'wc-all-cart-tracker'),
                'default' => true,
                'description' => __('Last updated timestamp', 'wc-all-cart-tracker')
            ),
            'status' => array(
                'label' => __('Cart Status', 'wc-all-cart-tracker'),
                'group' => __('Basic Information', 'wc-all-cart-tracker'),
                'default' => true,
                'description' => __('Active, Converted, Abandoned, or Deleted', 'wc-all-cart-tracker')
            ),

            // Customer Info
            'customer_name' => array(
                'label' => __('Customer Name', 'wc-all-cart-tracker'),
                'group' => __('Customer Information', 'wc-all-cart-tracker'),
                'default' => true,
                'description' => __('Full customer name', 'wc-all-cart-tracker')
            ),
            'customer_email' => array(
                'label' => __('Customer Email', 'wc-all-cart-tracker'),
                'group' => __('Customer Information', 'wc-all-cart-tracker'),
                'default' => true,
                'description' => __('Customer email address', 'wc-all-cart-tracker')
            ),
            'user_id' => array(
                'label' => __('User ID', 'wc-all-cart-tracker'),
                'group' => __('Customer Information', 'wc-all-cart-tracker'),
                'default' => false,
                'description' => __('WordPress user ID (0 for guests)', 'wc-all-cart-tracker')
            ),
            'session_id' => array(
                'label' => __('Session ID', 'wc-all-cart-tracker'),
                'group' => __('Customer Information', 'wc-all-cart-tracker'),
                'default' => false,
                'description' => __('WooCommerce session identifier', 'wc-all-cart-tracker')
            ),
            'past_purchases' => array(
                'label' => __('Past Purchases', 'wc-all-cart-tracker'),
                'group' => __('Customer Information', 'wc-all-cart-tracker'),
                'default' => true,
                'description' => __('Number of completed orders', 'wc-all-cart-tracker')
            ),

            // Cart Details
            'cart_total' => array(
                'label' => __('Cart Total', 'wc-all-cart-tracker'),
                'group' => __('Cart Details', 'wc-all-cart-tracker'),
                'default' => true,
                'description' => __('Total cart value', 'wc-all-cart-tracker')
            ),
            'item_count' => array(
                'label' => __('Item Count', 'wc-all-cart-tracker'),
                'group' => __('Cart Details', 'wc-all-cart-tracker'),
                'default' => true,
                'description' => __('Number of unique products', 'wc-all-cart-tracker')
            ),
            'cart_items' => array(
                'label' => __('Cart Items', 'wc-all-cart-tracker'),
                'group' => __('Cart Details', 'wc-all-cart-tracker'),
                'default' => true,
                'description' => __('Full list of products with quantities', 'wc-all-cart-tracker')
            ),
            'cart_items_summary' => array(
                'label' => __('Cart Items (Summary)', 'wc-all-cart-tracker'),
                'group' => __('Cart Details', 'wc-all-cart-tracker'),
                'default' => false,
                'description' => __('Product names only, no details', 'wc-all-cart-tracker')
            ),

            // Technical Details
            'is_active' => array(
                'label' => __('Is Active', 'wc-all-cart-tracker'),
                'group' => __('Technical Details', 'wc-all-cart-tracker'),
                'default' => false,
                'description' => __('Yes/No active status', 'wc-all-cart-tracker')
            ),
            'days_since_update' => array(
                'label' => __('Days Since Update', 'wc-all-cart-tracker'),
                'group' => __('Technical Details', 'wc-all-cart-tracker'),
                'default' => false,
                'description' => __('Number of days since last cart update', 'wc-all-cart-tracker')
            ),
            'cart_age_hours' => array(
                'label' => __('Cart Age (Hours)', 'wc-all-cart-tracker'),
                'group' => __('Technical Details', 'wc-all-cart-tracker'),
                'default' => false,
                'description' => __('Hours since cart was last updated', 'wc-all-cart-tracker')
            ),
        );
    }

    /**
     * Get default column selection
     */
    public static function get_default_columns()
    {
        $all_columns = self::get_available_columns();
        $default_columns = array();

        foreach ($all_columns as $key => $config) {
            if ($config['default']) {
                $default_columns[] = $key;
            }
        }

        return $default_columns;
    }

    /**
     * Save export template for current user
     */
    public static function save_template($template_name, $columns, $is_global = false)
    {
        $user_id = get_current_user_id();

        if ($user_id === 0) {
            return new WP_Error('no_user', __('Must be logged in to save templates', 'wc-all-cart-tracker'));
        }

        // Get available columns
        $available_columns = array_keys(self::get_available_columns());

        // Ensure $columns is an array
        if (!is_array($columns)) {
            return new WP_Error('invalid_columns', __('Invalid columns format', 'wc-all-cart-tracker'));
        }

        // Remove any empty values
        $columns = array_filter($columns);

        // Validate columns - keep only valid ones
        $columns = array_intersect($columns, $available_columns);

        if (empty($columns)) {
            return new WP_Error('no_columns', __('No valid columns selected', 'wc-all-cart-tracker'));
        }

        // Re-index the array to ensure it's a proper sequential array
        $columns = array_values($columns);

        $template = array(
            'name' => sanitize_text_field($template_name),
            'columns' => $columns,
            'created' => current_time('mysql'),
            'created_by' => $user_id,
            'is_global' => $is_global && current_user_can('manage_woocommerce'),
        );

        if ($template['is_global']) {
            // Save as site option for all users
            $global_templates = get_option('wcat_global_export_templates', array());

            $template_id = 'global_' . sanitize_title($template_name) . '_' . time();
            $global_templates[$template_id] = $template;

            $update_result = update_option('wcat_global_export_templates', $global_templates);

            // Verify what was actually saved
            $verify = get_option('wcat_global_export_templates');

            return $template_id;
        } else {
            // Save as user meta
            $user_templates = get_user_meta($user_id, self::META_KEY, true);

            if (!is_array($user_templates)) {
                $user_templates = array();
            }

            $template_id = 'user_' . sanitize_title($template_name) . '_' . time();
            $user_templates[$template_id] = $template;

            $update_result = update_user_meta($user_id, self::META_KEY, $user_templates);

            // Verify what was actually saved
            $verify = get_user_meta($user_id, self::META_KEY, true);

            return $template_id;
        }
    }

    /**
     * Get all templates available to current user
     */
    public static function get_user_templates($include_global = true)
    {

        $user_id = get_current_user_id();

        $templates = array();

        // Get user's personal templates
        if ($user_id > 0) {
            $user_templates = get_user_meta($user_id, self::META_KEY, true);

            if (is_array($user_templates)) {
                $templates = $user_templates;
            } else {
                error_log('User templates is not an array!');
            }
        }

        // Get global templates
        if ($include_global) {
            $global_templates = get_option('wcat_global_export_templates', array());

            if (is_array($global_templates)) {
                $templates = array_merge($templates, $global_templates);
            }
        }

        // Sort by creation date (newest first)
        uasort($templates, function ($a, $b) {
            $time_a = isset($a['created']) ? strtotime($a['created']) : 0;
            $time_b = isset($b['created']) ? strtotime($b['created']) : 0;
            return $time_b - $time_a;
        });

        return $templates;
    }
    /**
     * Get a specific template
     */
    public static function get_template($template_id)
    {
        $templates = self::get_user_templates(true);
        return isset($templates[$template_id]) ? $templates[$template_id] : null;
    }

    /**
     * Delete a template
     */
    public static function delete_template($template_id)
    {
        $user_id = get_current_user_id();

        // Check if it's a global template
        if (strpos($template_id, 'global_') === 0) {
            if (!current_user_can('manage_woocommerce')) {
                return new WP_Error('no_permission', __('Insufficient permissions', 'wc-all-cart-tracker'));
            }

            $global_templates = get_option('wcat_global_export_templates', array());
            if (isset($global_templates[$template_id])) {
                unset($global_templates[$template_id]);
                update_option('wcat_global_export_templates', $global_templates);
                return true;
            }
        } else {
            // User template
            $user_templates = get_user_meta($user_id, self::META_KEY, true);
            if (is_array($user_templates) && isset($user_templates[$template_id])) {
                unset($user_templates[$template_id]);
                update_user_meta($user_id, self::META_KEY, $user_templates);
                return true;
            }
        }

        return new WP_Error('not_found', __('Template not found', 'wc-all-cart-tracker'));
    }

    /**
     * Update an existing template
     */
    public static function update_template($template_id, $columns, $new_name = null)
    {
        $template = self::get_template($template_id);

        if (!$template) {
            return new WP_Error('not_found', __('Template not found', 'wc-all-cart-tracker'));
        }

        // Validate columns
        $available_columns = array_keys(self::get_available_columns());
        $columns = array_intersect($columns, $available_columns);

        if (empty($columns)) {
            return new WP_Error('no_columns', __('No valid columns selected', 'wc-all-cart-tracker'));
        }

        $template['columns'] = $columns;
        $template['updated'] = current_time('mysql');

        if ($new_name !== null) {
            $template['name'] = sanitize_text_field($new_name);
        }

        $user_id = get_current_user_id();

        if ($template['is_global']) {
            if (!current_user_can('manage_woocommerce')) {
                return new WP_Error('no_permission', __('Insufficient permissions', 'wc-all-cart-tracker'));
            }

            $global_templates = get_option('wcat_global_export_templates', array());
            $global_templates[$template_id] = $template;
            update_option('wcat_global_export_templates', $global_templates);
        } else {
            $user_templates = get_user_meta($user_id, self::META_KEY, true);
            if (is_array($user_templates) && isset($user_templates[$template_id])) {
                $user_templates[$template_id] = $template;
                update_user_meta($user_id, self::META_KEY, $user_templates);
            }
        }

        return true;
    }

    /**
     * Extract column data from cart object
     */
    public static function extract_column_data($cart, $column_key)
    {
        $clean_cart = WC_Cart_Tracker_Data_Sanitizer::prepare_cart_for_export($cart);

        switch ($column_key) {
            case 'id':
                return $clean_cart['id'];

            case 'date':
                return $clean_cart['date'];

            case 'status':
                return $clean_cart['status'];

            case 'customer_name':
                return !empty($cart->customer_name) ?
                    WC_Cart_Tracker_Data_Sanitizer::strip_html($cart->customer_name) : 'Guest';

            case 'customer_email':
                return $clean_cart['email'];

            case 'user_id':
                return $clean_cart['user_id'];

            case 'session_id':
                return substr($cart->session_id, 0, 20) . '...';

            case 'past_purchases':
                return $clean_cart['past_purchases'];

            case 'cart_total':
                return $clean_cart['cart_total'];

            case 'item_count':
                return $clean_cart['item_count'];

            case 'cart_items':
                return $clean_cart['cart_items'];

            case 'cart_items_summary':
                $items = json_decode($cart->cart_content, true);
                if (is_array($items) && !empty($items)) {
                    $names = array_map(function ($item) {
                        return WC_Cart_Tracker_Data_Sanitizer::strip_html($item['product_name']);
                    }, $items);
                    return implode(', ', $names);
                }
                return '';

            case 'is_active':
                return $cart->is_active ? 'Yes' : 'No';

            case 'days_since_update':
                $diff = time() - strtotime($cart->last_updated);
                return floor($diff / DAY_IN_SECONDS);

            case 'cart_age_hours':
                $diff = time() - strtotime($cart->last_updated);
                return round($diff / HOUR_IN_SECONDS, 1);

            default:
                return '';
        }
    }

    /**
     * Get column headers for selected columns
     */
    public static function get_column_headers($columns)
    {
        $all_columns = self::get_available_columns();
        $headers = array();

        foreach ($columns as $column_key) {
            if (isset($all_columns[$column_key])) {
                $headers[] = $all_columns[$column_key]['label'];
            }
        }

        return $headers;
    }
}