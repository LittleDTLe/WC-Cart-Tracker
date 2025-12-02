<?php
/**
 * Database Operations
 *
 * @package WC_All_Cart_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Cart_Tracker_Database
{

    const TABLE_NAME = 'all_carts_tracker';

    public static function activate()
    {
        self::create_table();
        self::migrate_existing_data();
        update_option('wc_all_cart_tracker_version', WC_CART_TRACKER_VERSION);
        flush_rewrite_rules();
    }

    public static function get_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME;
    }

    private static function create_table()
    {
        global $wpdb;

        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(191) NOT NULL DEFAULT '',
            user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            cart_content LONGTEXT NOT NULL,
            cart_total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            customer_email VARCHAR(100) NOT NULL DEFAULT '',
            customer_name VARCHAR(100) NOT NULL DEFAULT '',
            past_purchases INT(11) NOT NULL DEFAULT 0,
            last_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            cart_status VARCHAR(20) NOT NULL DEFAULT 'active',
            PRIMARY KEY (id),
            INDEX idx_session_id (session_id),
            INDEX idx_user_id (user_id),
            INDEX idx_is_active (is_active),
            INDEX idx_cart_status (cart_status),
            INDEX idx_last_updated (last_updated)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private static function migrate_existing_data()
    {
        global $wpdb;
        $table_name = self::get_table_name();

        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'cart_status'",
            DB_NAME,
            $table_name
        ));

        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN cart_status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER is_active");
            $wpdb->query("ALTER TABLE {$table_name} ADD INDEX idx_cart_status (cart_status)");
            $wpdb->query("UPDATE {$table_name} SET cart_status = 'active' WHERE is_active = 1");
            $wpdb->query("UPDATE {$table_name} SET cart_status = 'converted' WHERE is_active = 0");
        }
    }

    public static function drop_table()
    {
        global $wpdb;
        $table_name = self::get_table_name();
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    }

    public static function get_cart_by_session($session_id, $user_id)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        if ($user_id > 0) {
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE user_id = %d AND is_active = 1 ORDER BY last_updated DESC LIMIT 1",
                $user_id
            ));
        }

        if (!empty($session_id)) {
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE session_id = %s AND is_active = 1 ORDER BY last_updated DESC LIMIT 1",
                $session_id
            ));
        }

        return null;
    }

    public static function save_cart($data)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        $existing = self::get_cart_by_session($data['session_id'], $data['user_id']);

        if ($existing) {
            return $wpdb->update(
                $table_name,
                $data,
                array('id' => $existing->id),
                array('%s', '%d', '%s', '%f', '%s', '%s', '%d', '%s', '%d', '%s'),
                array('%d')
            );
        } else {
            return $wpdb->insert(
                $table_name,
                $data,
                array('%s', '%d', '%s', '%f', '%s', '%s', '%d', '%s', '%d', '%s')
            );
        }
    }

    public static function update_cart_status($session_id, $user_id, $status)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        $update_data = array(
            'is_active' => 0,
            'cart_status' => $status
        );

        if ($user_id > 0) {
            return $wpdb->update(
                $table_name,
                $update_data,
                array('user_id' => $user_id, 'is_active' => 1),
                array('%d', '%s'),
                array('%d', '%d')
            );
        }

        if (!empty($session_id)) {
            return $wpdb->update(
                $table_name,
                $update_data,
                array('session_id' => $session_id, 'is_active' => 1),
                array('%d', '%s'),
                array('%s', '%d')
            );
        }

        return false;
    }
}