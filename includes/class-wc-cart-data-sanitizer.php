<?php
/**
 * Data Sanitization Helper
 * 
 * Cleans and standardizes data for Excel/CSV export
 * Removes HTML, currency symbols, and formatting
 *
 * @package WC_All_Cart_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Cart_Tracker_Data_Sanitizer
{
    /**
     * Remove all HTML tags and decode entities
     */
    public static function strip_html($value)
    {
        if (empty($value)) {
            return '';
        }

        // Remove all HTML tags
        $value = wp_strip_all_tags($value);

        // Decode HTML entities
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remove any remaining special characters
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);

        return trim($value);
    }

    /**
     * Extract numeric value from price string
     */
    public static function clean_price($price_string)
    {
        if (empty($price_string)) {
            return 0.00;
        }

        // If it's already a number, return it formatted
        if (is_numeric($price_string)) {
            return number_format((float) $price_string, 2, '.', '');
        }

        // Remove currency symbols and formatting
        $clean = preg_replace('/[^\d.,]/', '', $price_string);

        // Handle different decimal separators
        // If there are both comma and period, assume last one is decimal
        if (strpos($clean, ',') !== false && strpos($clean, '.') !== false) {
            // European format: 1.234,56 or US format: 1,234.56
            $last_comma = strrpos($clean, ',');
            $last_period = strrpos($clean, '.');

            if ($last_comma > $last_period) {
                // European format
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
                // US format
                $clean = str_replace(',', '', $clean);
            }
        } elseif (strpos($clean, ',') !== false) {
            // Only comma - could be thousands separator or decimal
            $comma_count = substr_count($clean, ',');
            if ($comma_count === 1 && strlen(substr($clean, strrpos($clean, ',') + 1)) === 2) {
                // Likely decimal separator
                $clean = str_replace(',', '.', $clean);
            } else {
                // Likely thousands separator
                $clean = str_replace(',', '', $clean);
            }
        }

        // Convert to float and format
        $value = floatval($clean);
        return number_format($value, 2, '.', '');
    }

    /**
     * Clean cart contents for export
     */
    public static function clean_cart_contents($cart_content)
    {
        if (empty($cart_content)) {
            return '';
        }

        $items = json_decode($cart_content, true);
        if (!is_array($items) || empty($items)) {
            return '';
        }

        $cleaned_items = array();
        foreach ($items as $item) {
            $product_name = self::strip_html($item['product_name'] ?? '');
            $quantity = absint($item['quantity'] ?? 0);
            $price = self::clean_price($item['line_total'] ?? 0);

            $cleaned_items[] = "{$product_name} (Qty: {$quantity}, Total: {$price})";
        }

        return implode(' | ', $cleaned_items);
    }

    /**
     * Clean status for export
     */
    public static function clean_status($status, $is_active, $last_updated)
    {
        if ($status === 'converted') {
            return 'Converted';
        } elseif ($status === 'deleted') {
            return 'Deleted';
        } elseif ($status === 'active') {
            if (strtotime($last_updated) < strtotime('-24 hours')) {
                return 'Abandoned';
            } else {
                return 'Active';
            }
        }
        return ucfirst($status);
    }

    /**
     * Format date consistently
     */
    public static function format_date($datetime, $format = 'Y-m-d H:i:s')
    {
        if (empty($datetime)) {
            return '';
        }

        $date = new DateTime($datetime);
        return $date->format($format);
    }

    /**
     * Clean customer info for export
     */
    public static function clean_customer_info($name, $email, $user_id)
    {
        $name = self::strip_html($name);
        $email = sanitize_email($email);

        if (empty($name) && empty($email)) {
            return 'Guest';
        }

        $info = array();
        if (!empty($name)) {
            $info[] = $name;
        }
        if (!empty($email)) {
            $info[] = $email;
        }

        return implode(' - ', $info);
    }

    /**
     * Prepare complete cart record for export
     */
    public static function prepare_cart_for_export($cart)
    {
        return array(
            'id' => absint($cart->id),
            'date' => self::format_date($cart->last_updated),
            'customer' => self::clean_customer_info(
                $cart->customer_name,
                $cart->customer_email,
                $cart->user_id
            ),
            'email' => sanitize_email($cart->customer_email),
            'user_id' => absint($cart->user_id) > 0 ? absint($cart->user_id) : '',
            'status' => self::clean_status(
                $cart->cart_status,
                $cart->is_active,
                $cart->last_updated
            ),
            'past_purchases' => absint($cart->past_purchases),
            'cart_total' => self::clean_price($cart->cart_total),
            'item_count' => self::get_item_count($cart->cart_content),
            'cart_items' => self::clean_cart_contents($cart->cart_content),
        );
    }

    /**
     * Get item count from cart content
     */
    private static function get_item_count($cart_content)
    {
        if (empty($cart_content)) {
            return 0;
        }

        $items = json_decode($cart_content, true);
        return is_array($items) ? count($items) : 0;
    }

    /**
     * Prepare analytics data for export
     */
    public static function prepare_analytics_for_export($analytics)
    {
        return array(
            'total_carts' => absint($analytics['total_carts']),
            'converted_carts' => absint($analytics['converted_carts']),
            'deleted_carts' => absint($analytics['deleted_carts']),
            'active_carts' => absint($analytics['active_carts']),
            'abandoned_carts' => absint($analytics['abandoned_carts']),
            'conversion_rate' => number_format($analytics['conversion_rate'], 2, '.', '') . '%',
            'abandonment_rate' => number_format($analytics['abandonment_rate'], 2, '.', '') . '%',
            'avg_active_cart' => self::clean_price($analytics['avg_active_cart']),
            'avg_converted_cart' => self::clean_price($analytics['avg_converted_cart']),
            'revenue_potential' => self::clean_price($analytics['overall_revenue_potential']),
            'registered_carts' => absint($analytics['registered_carts']),
            'guest_carts' => absint($analytics['guest_carts']),
            'registered_conversion_rate' => number_format($analytics['registered_conversion_rate'], 2, '.', '') . '%',
            'guest_conversion_rate' => number_format($analytics['guest_conversion_rate'], 2, '.', '') . '%',
        );
    }

    /**
     * Escape value for CSV (prevent formula injection)
     */
    public static function escape_csv($value)
    {
        // Prevent CSV injection by escaping formulas
        if (is_string($value) && strlen($value) > 0) {
            $first_char = substr($value, 0, 1);
            if (in_array($first_char, array('=', '+', '-', '@', "\t", "\r"))) {
                $value = "'" . $value;
            }
        }

        return $value;
    }
}