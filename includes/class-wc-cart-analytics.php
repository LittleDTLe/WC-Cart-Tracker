<?php
/**
 * Analytics Calculations
 *
 * @package WC_All_Cart_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Cart_Tracker_Analytics
{
    private static $cache_group = 'wc_cart_analytics';
    private static $cache_expiration = 300; // 5 minutes

    /**
     * Get analytics data with caching and optimized queries
     */
    public static function get_analytics_data($days = 30)
    {
        $cache_key = self::$cache_group . '_' . $days;
        $cached_data = get_transient($cache_key);

        if (false !== $cached_data) {
            return $cached_data;
        }

        global $wpdb;
        $table_name = WC_Cart_Tracker_Database::get_table_name();

        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $recent_date = date('Y-m-d H:i:s', strtotime('-24 hours'));

        // Single optimized query for all counts and aggregations
        $analytics_query = $wpdb->prepare("
            SELECT 
                COUNT(*) as total_carts,
                SUM(CASE WHEN cart_status = 'converted' THEN 1 ELSE 0 END) as converted_carts,
                SUM(CASE WHEN cart_status = 'deleted' THEN 1 ELSE 0 END) as deleted_carts,
                SUM(CASE WHEN is_active = 1 AND last_updated >= %s THEN 1 ELSE 0 END) as active_carts,
                SUM(CASE WHEN is_active = 1 AND last_updated < %s THEN 1 ELSE 0 END) as abandoned_carts,
                
                SUM(CASE WHEN user_id > 0 THEN 1 ELSE 0 END) as registered_carts,
                SUM(CASE WHEN user_id = 0 THEN 1 ELSE 0 END) as guest_carts,
                SUM(CASE WHEN user_id > 0 AND cart_status = 'converted' THEN 1 ELSE 0 END) as registered_converted,
                SUM(CASE WHEN user_id = 0 AND cart_status = 'converted' THEN 1 ELSE 0 END) as guest_converted,
                
                AVG(CASE WHEN is_active = 1 THEN cart_total ELSE NULL END) as avg_active_cart,
                AVG(CASE WHEN cart_status = 'converted' THEN cart_total ELSE NULL END) as avg_converted_cart,
                
                SUM(CASE WHEN is_active = 1 THEN cart_total ELSE 0 END) as overall_revenue_potential,
                SUM(CASE WHEN is_active = 1 AND last_updated >= %s THEN cart_total ELSE 0 END) as active_cart_potential,
                SUM(CASE WHEN is_active = 1 AND last_updated < %s THEN cart_total ELSE 0 END) as abandoned_cart_potential
            FROM {$table_name}
            WHERE last_updated >= %s
        ", $recent_date, $recent_date, $recent_date, $recent_date, $date_from);

        $result = $wpdb->get_row($analytics_query);

        if (!$result) {
            return self::get_empty_analytics();
        }

        // Calculate rates
        $total_carts = intval($result->total_carts);
        $converted_carts = intval($result->converted_carts);
        $abandoned_carts = intval($result->abandoned_carts);
        $registered_carts = intval($result->registered_carts);
        $guest_carts = intval($result->guest_carts);
        $registered_converted = intval($result->registered_converted);
        $guest_converted = intval($result->guest_converted);

        $conversion_rate = $total_carts > 0 ? ($converted_carts / $total_carts) * 100 : 0;
        $abandonment_rate = $total_carts > 0 ? ($abandoned_carts / $total_carts) * 100 : 0;
        $registered_conversion_rate = $registered_carts > 0 ? ($registered_converted / $registered_carts) * 100 : 0;
        $guest_conversion_rate = $guest_carts > 0 ? ($guest_converted / $guest_carts) * 100 : 0;

        $analytics = array(
            'total_carts' => $total_carts,
            'converted_carts' => $converted_carts,
            'deleted_carts' => intval($result->deleted_carts),
            'active_carts' => intval($result->active_carts),
            'abandoned_carts' => $abandoned_carts,
            'avg_active_cart' => floatval($result->avg_active_cart) ?: 0,
            'avg_converted_cart' => floatval($result->avg_converted_cart) ?: 0,
            'overall_revenue_potential' => floatval($result->overall_revenue_potential) ?: 0,
            'active_cart_potential' => floatval($result->active_cart_potential) ?: 0,
            'abandoned_cart_potential' => floatval($result->abandoned_cart_potential) ?: 0,
            'conversion_rate' => round($conversion_rate, 2),
            'abandonment_rate' => round($abandonment_rate, 2),
            'registered_carts' => $registered_carts,
            'guest_carts' => $guest_carts,
            'registered_converted' => $registered_converted,
            'guest_converted' => $guest_converted,
            'registered_conversion_rate' => round($registered_conversion_rate, 2),
            'guest_conversion_rate' => round($guest_conversion_rate, 2),
        );

        // Cache the result
        set_transient($cache_key, $analytics, self::$cache_expiration);

        return $analytics;
    }

    /**
     * Clear analytics cache
     */
    public static function clear_cache($days = null)
    {
        if ($days === null) {
            // Clear all analytics caches
            foreach (array(7, 30, 60, 90) as $day) {
                delete_transient(self::$cache_group . '_' . $day);
            }
        } else {
            delete_transient(self::$cache_group . '_' . $days);
        }
    }

    /**
     * Return empty analytics structure
     */
    private static function get_empty_analytics()
    {
        return array(
            'total_carts' => 0,
            'converted_carts' => 0,
            'deleted_carts' => 0,
            'active_carts' => 0,
            'abandoned_carts' => 0,
            'avg_active_cart' => 0,
            'avg_converted_cart' => 0,
            'overall_revenue_potential' => 0,
            'active_cart_potential' => 0,
            'abandoned_cart_potential' => 0,
            'conversion_rate' => 0,
            'abandonment_rate' => 0,
            'registered_carts' => 0,
            'guest_carts' => 0,
            'registered_converted' => 0,
            'guest_converted' => 0,
            'registered_conversion_rate' => 0,
            'guest_conversion_rate' => 0,
        );
    }
}