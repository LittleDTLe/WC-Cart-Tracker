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
    private static $cache = array();
    private static $cache_ttl = 300; // 5 minutes

    public static function activate()
    {
        self::create_table();
        self::migrate_existing_data();
        self::create_optimized_indexes();
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
            INDEX idx_last_updated (last_updated),
            INDEX idx_active_updated (is_active, last_updated),
            INDEX idx_status_updated (cart_status, last_updated),
            INDEX idx_user_active (user_id, is_active),
            INDEX idx_session_active (session_id, is_active)
        ) {$charset_collate} ENGINE=InnoDB;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create optimized composite indexes for common query patterns
     * Changed to public so it can be called from admin optimization page
     */
    public static function create_optimized_indexes()
    {
        global $wpdb;
        $table_name = self::get_table_name();

        // Check and add composite indexes if they don't exist
        $indexes = array(
            'idx_active_updated' => '(is_active, last_updated)',
            'idx_status_updated' => '(cart_status, last_updated)',
            'idx_user_active' => '(user_id, is_active)',
            'idx_session_active' => '(session_id, is_active)',
        );

        foreach ($indexes as $index_name => $columns) {
            $index_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_NAME = %s",
                DB_NAME,
                $table_name,
                $index_name
            ));

            if (!$index_exists) {
                $wpdb->query("ALTER TABLE {$table_name} ADD INDEX {$index_name} {$columns}");
            }
        }
    }

    /**
     * Optimized cart retrieval with caching
     */
    public static function get_cart_by_session($session_id, $user_id)
    {
        $cache_key = 'cart_' . ($user_id > 0 ? 'user_' . $user_id : 'session_' . md5($session_id));

        // Check cache first
        if (isset(self::$cache[$cache_key])) {
            $cached = self::$cache[$cache_key];
            if (time() - $cached['time'] < self::$cache_ttl) {
                return $cached['data'];
            }
        }

        global $wpdb;
        $table_name = self::get_table_name();

        $result = null;

        if ($user_id > 0) {
            $result = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} 
                WHERE user_id = %d AND is_active = 1 
                ORDER BY last_updated DESC LIMIT 1",
                $user_id
            ));
        } elseif (!empty($session_id)) {
            $result = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} 
                WHERE session_id = %s AND is_active = 1 
                ORDER BY last_updated DESC LIMIT 1",
                $session_id
            ));
        }

        // Cache the result
        self::$cache[$cache_key] = array(
            'data' => $result,
            'time' => time()
        );

        return $result;
    }

    /**
     * Optimized save with upsert logic
     */
    public static function save_cart($data)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        // Clear cache for this cart
        $cache_key = 'cart_' . ($data['user_id'] > 0 ? 'user_' . $data['user_id'] : 'session_' . md5($data['session_id']));
        unset(self::$cache[$cache_key]);

        // Use MySQL INSERT ... ON DUPLICATE KEY UPDATE for better performance
        // First, check if record exists
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

    /**
     * Batch update cart statuses - useful for cleanup operations
     */
    public static function batch_update_status($cart_ids, $status)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        if (empty($cart_ids) || !is_array($cart_ids)) {
            return false;
        }

        // Sanitize IDs
        $cart_ids = array_map('absint', $cart_ids);
        $ids_placeholder = implode(',', array_fill(0, count($cart_ids), '%d'));

        $query = $wpdb->prepare(
            "UPDATE {$table_name} 
            SET is_active = 0, cart_status = %s 
            WHERE id IN ({$ids_placeholder})",
            array_merge(array($status), $cart_ids)
        );

        return $wpdb->query($query);
    }

    /**
     * Optimized status update with index hints
     */
    public static function update_cart_status($session_id, $user_id, $status)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        // Clear cache
        $cache_key = 'cart_' . ($user_id > 0 ? 'user_' . $user_id : 'session_' . md5($session_id));
        unset(self::$cache[$cache_key]);

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

    /**
     * Cleanup old inactive carts (run via cron)
     * 
     * @param int $days Number of days to keep inactive carts (default: 90)
     * @param bool $hard_delete If true, permanently deletes. If false, archives to separate table
     * @return int Number of records affected
     */
    public static function cleanup_old_carts($days = 90, $hard_delete = false)
    {
        global $wpdb;
        $table_name = self::get_table_name();
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        if ($hard_delete) {
            // PERMANENT DELETION - Cannot be undone!
            return $wpdb->query($wpdb->prepare(
                "DELETE FROM {$table_name} 
                WHERE is_active = 0 AND last_updated < %s",
                $cutoff_date
            ));
        } else {
            // SOFT DELETE - Archive to backup table for potential recovery
            self::create_archive_table();

            // Move old records to archive
            $archived = $wpdb->query($wpdb->prepare(
                "INSERT INTO {$table_name}_archive 
                SELECT *, NOW() as archived_at 
                FROM {$table_name} 
                WHERE is_active = 0 AND last_updated < %s",
                $cutoff_date
            ));

            // Delete from main table
            if ($archived > 0) {
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$table_name} 
                    WHERE is_active = 0 AND last_updated < %s",
                    $cutoff_date
                ));
            }

            return $archived;
        }
    }

    /**
     * Create archive table for old carts (for safe cleanup)
     * Changed to public so it can be called if needed
     */
    public static function create_archive_table()
    {
        global $wpdb;
        $table_name = self::get_table_name();
        $archive_table = $table_name . '_archive';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$archive_table} (
            id BIGINT(20) UNSIGNED NOT NULL,
            session_id VARCHAR(191) NOT NULL DEFAULT '',
            user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            cart_content LONGTEXT NOT NULL,
            cart_total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            customer_email VARCHAR(100) NOT NULL DEFAULT '',
            customer_name VARCHAR(100) NOT NULL DEFAULT '',
            past_purchases INT(11) NOT NULL DEFAULT 0,
            last_updated DATETIME NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 0,
            cart_status VARCHAR(20) NOT NULL DEFAULT 'active',
            archived_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id, archived_at),
            INDEX idx_archived_date (archived_at),
            INDEX idx_session_id (session_id),
            INDEX idx_user_id (user_id)
        ) {$charset_collate} ENGINE=InnoDB;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Restore archived carts (if needed)
     */
    public static function restore_archived_carts($days_back = 7)
    {
        global $wpdb;
        $table_name = self::get_table_name();
        $archive_table = $table_name . '_archive';
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_back} days"));

        // Check if archive table exists
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $archive_table
        ));

        if (!$table_exists) {
            return 0;
        }

        return $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$table_name} 
            (id, session_id, user_id, cart_content, cart_total, customer_email, 
             customer_name, past_purchases, last_updated, is_active, cart_status)
            SELECT id, session_id, user_id, cart_content, cart_total, customer_email,
                   customer_name, past_purchases, last_updated, is_active, cart_status
            FROM {$archive_table}
            WHERE archived_at >= %s",
            $cutoff_date
        ));
    }

    /**
     * Permanently delete archived carts older than specified days
     */
    public static function purge_archive($days = 365)
    {
        global $wpdb;
        $table_name = self::get_table_name();
        $archive_table = $table_name . '_archive';
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$archive_table} WHERE archived_at < %s",
            $cutoff_date
        ));
    }

    /**
     * Clear cache manually if needed
     */
    public static function clear_cache()
    {
        self::$cache = array();
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
}
