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

    // Revenue potential cutoff (in days) - carts older than this won't count toward revenue potential
    const REVENUE_CUTOFF_DAYS = 7;

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
        $revenue_cutoff_date = date('Y-m-d H:i:s', strtotime('-' . self::REVENUE_CUTOFF_DAYS . ' days'));

        $analytics = self::calculate_analytics($table_name, $date_from, $recent_date, null, $revenue_cutoff_date);

        // Cache the result
        set_transient($cache_key, $analytics, self::$cache_expiration);

        return $analytics;
    }

    /**
     * Get analytics data for a custom date range
     *
     * @param string $date_from Start date (Y-m-d format)
     * @param string $date_to End date (Y-m-d format)
     * @return array Analytics data
     */
    public static function get_analytics_data_by_date_range($date_from, $date_to)
    {
        // Validate dates
        $date_from = date('Y-m-d', strtotime($date_from)) . ' 00:00:00';
        $date_to = date('Y-m-d', strtotime($date_to)) . ' 23:59:59';

        $cache_key = self::$cache_group . '_custom_' . md5($date_from . $date_to);
        $cached_data = get_transient($cache_key);

        if (false !== $cached_data) {
            return $cached_data;
        }

        global $wpdb;
        $table_name = WC_Cart_Tracker_Database::get_table_name();

        $recent_date = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $revenue_cutoff_date = date('Y-m-d H:i:s', strtotime('-' . self::REVENUE_CUTOFF_DAYS . ' days'));

        // Use the date_to as the upper bound for queries
        $analytics = self::calculate_analytics($table_name, $date_from, $recent_date, $date_to, $revenue_cutoff_date);

        // Cache the result
        set_transient($cache_key, $analytics, self::$cache_expiration);

        return $analytics;
    }

    /**
     * Calculate analytics from database queries
     *
     * @param string $table_name Database table name
     * @param string $date_from Start date
     * @param string $recent_date Date for active cart threshold (24 hours ago)
     * @param string|null $date_to Optional end date for custom ranges
     * @param string $revenue_cutoff_date Cutoff date for revenue potential (7 days ago)
     * @return array Analytics data
     */
    private static function calculate_analytics($table_name, $date_from, $recent_date, $date_to = null, $revenue_cutoff_date)
    {
        global $wpdb;

        // Build WHERE clause for date range
        if ($date_to !== null) {
            $date_where = $wpdb->prepare(
                "last_updated BETWEEN %s AND %s",
                $date_from,
                $date_to
            );
        } else {
            $date_where = $wpdb->prepare(
                "last_updated >= %s",
                $date_from
            );
        }

        // Single optimized query for all counts and aggregations
        $analytics_query = "
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
                
                -- Overall revenue potential: Only count carts updated within last 7 days
                SUM(CASE WHEN is_active = 1 AND last_updated >= %s THEN cart_total ELSE 0 END) as overall_revenue_potential,
                
                -- Active cart potential: Carts updated within last 24 hours
                SUM(CASE WHEN is_active = 1 AND last_updated >= %s THEN cart_total ELSE 0 END) as active_cart_potential,
                
                -- Abandoned cart potential: Carts between 24 hours and 7 days old
                SUM(CASE WHEN is_active = 1 AND last_updated < %s AND last_updated >= %s THEN cart_total ELSE 0 END) as abandoned_cart_potential,
                
                -- Count of stale carts (older than 7 days but still active)
                SUM(CASE WHEN is_active = 1 AND last_updated < %s THEN 1 ELSE 0 END) as stale_carts,
                
                -- Total value of stale carts (excluded from revenue potential)
                SUM(CASE WHEN is_active = 1 AND last_updated < %s THEN cart_total ELSE 0 END) as stale_cart_value
                
            FROM {$table_name}
            WHERE {$date_where}
        ";

        $result = $wpdb->get_row($wpdb->prepare(
            $analytics_query,
            $recent_date,              // active_carts threshold
            $recent_date,              // abandoned_carts threshold
            $revenue_cutoff_date,      // overall_revenue_potential cutoff
            $recent_date,              // active_cart_potential threshold
            $recent_date,              // abandoned_cart_potential lower bound
            $revenue_cutoff_date,      // abandoned_cart_potential upper bound
            $revenue_cutoff_date,      // stale_carts threshold
            $revenue_cutoff_date       // stale_cart_value threshold
        ));

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
            'stale_carts' => intval($result->stale_carts),
            'avg_active_cart' => floatval($result->avg_active_cart) ?: 0,
            'avg_converted_cart' => floatval($result->avg_converted_cart) ?: 0,
            'overall_revenue_potential' => floatval($result->overall_revenue_potential) ?: 0,
            'active_cart_potential' => floatval($result->active_cart_potential) ?: 0,
            'abandoned_cart_potential' => floatval($result->abandoned_cart_potential) ?: 0,
            'stale_cart_value' => floatval($result->stale_cart_value) ?: 0,
            'conversion_rate' => round($conversion_rate, 2),
            'abandonment_rate' => round($abandonment_rate, 2),
            'registered_carts' => $registered_carts,
            'guest_carts' => $guest_carts,
            'registered_converted' => $registered_converted,
            'guest_converted' => $guest_converted,
            'registered_conversion_rate' => round($registered_conversion_rate, 2),
            'guest_conversion_rate' => round($guest_conversion_rate, 2),
        );

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

            // Clear custom date range caches (pattern match)
            global $wpdb;
            $pattern = $wpdb->esc_like('_transient_' . self::$cache_group . '_custom_') . '%';
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $pattern
            ));
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
            'stale_carts' => 0,
            'avg_active_cart' => 0,
            'avg_converted_cart' => 0,
            'overall_revenue_potential' => 0,
            'active_cart_potential' => 0,
            'abandoned_cart_potential' => 0,
            'stale_cart_value' => 0,
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