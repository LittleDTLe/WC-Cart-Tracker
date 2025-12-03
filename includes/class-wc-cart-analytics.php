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

    public static function get_analytics_data($days = 30)
    {
        global $wpdb;
        $table_name = WC_Cart_Tracker_Database::get_table_name();

        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Total carts
        $total_carts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE last_updated >= %s",
            $date_from
        ));

        // Converted carts
        $converted_carts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE last_updated >= %s AND cart_status = 'converted'",
            $date_from
        ));

        // Deleted carts
        $deleted_carts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE last_updated >= %s AND cart_status = 'deleted'",
            $date_from
        ));

        $recent_date = date('Y-m-d H:i:s', strtotime('-24 hours'));

        // 1. Truly Active Carts (Carts updated recently)
        $active_carts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE is_active = 1 AND last_updated >= %s",
            $recent_date // Only counts carts touched within the last 24 hours
        ));

        // 2. Abandoned Carts (Logic remains: > 24 hours old)
        $abandoned_carts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE is_active = 1 AND last_updated < %s",
            $recent_date
        ));

        // Average values
        $avg_active_cart = $wpdb->get_var("SELECT AVG(cart_total) FROM {$table_name} WHERE is_active = 1");
        $avg_converted_cart = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(cart_total) FROM {$table_name} WHERE last_updated >= %s AND cart_status = 'converted'",
            $date_from
        ));

        // Revenue potential
        $overall_revenue_potential = $wpdb->get_var("SELECT SUM(cart_total) FROM {$table_name} WHERE is_active = 1");

        // Active Cart Potential (Carts updated recently, e.g., last 24 hours)
        $recent_date = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $active_cart_potential = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(cart_total) FROM {$table_name} WHERE is_active = 1 AND last_updated >= %s",
            $recent_date
        ));

        // Abandoned Cart Potential (Carts active but older than 24 hours)
        $abandoned_cart_potential = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(cart_total) FROM {$table_name} WHERE is_active = 1 AND last_updated < %s",
            $recent_date
        ));

        // Conversion rate
        $conversion_rate = $total_carts > 0 ? ($converted_carts / $total_carts) * 100 : 0;

        // Abandonment rate
        $abandonment_rate = $total_carts > 0 ? ($abandoned_carts / $total_carts) * 100 : 0;

        // By customer type
        $registered_carts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE last_updated >= %s AND user_id > 0",
            $date_from
        ));

        $guest_carts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE last_updated >= %s AND user_id = 0",
            $date_from
        ));

        $registered_converted = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE last_updated >= %s AND user_id > 0 AND cart_status = 'converted'",
            $date_from
        ));

        $guest_converted = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE last_updated >= %s AND user_id = 0 AND cart_status = 'converted'",
            $date_from
        ));

        return array(
            'total_carts' => $total_carts,
            'converted_carts' => $converted_carts,
            'deleted_carts' => $deleted_carts,
            'active_carts' => $active_carts,
            'abandoned_carts' => $abandoned_carts,
            'avg_active_cart' => $avg_active_cart ? floatval($avg_active_cart) : 0,
            'avg_converted_cart' => $avg_converted_cart ? floatval($avg_converted_cart) : 0,
            'overall_revenue_potential' => $overall_revenue_potential ? floatval($overall_revenue_potential) : 0,
            'active_cart_potential' => $active_cart_potential ? floatval($active_cart_potential) : 0,
            'abandoned_cart_potential' => $abandoned_cart_potential ? floatval($abandoned_cart_potential) : 0,
            'conversion_rate' => round($conversion_rate, 2),
            'abandonment_rate' => round($abandonment_rate, 2),
            'registered_carts' => $registered_carts,
            'guest_carts' => $guest_carts,
            'registered_converted' => $registered_converted,
            'guest_converted' => $guest_converted,
            'registered_conversion_rate' => $registered_carts > 0 ? round(($registered_converted / $registered_carts) * 100, 2) : 0,
            'guest_conversion_rate' => $guest_carts > 0 ? round(($guest_converted / $guest_carts) * 100, 2) : 0,
        );
    }
}