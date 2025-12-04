<?php
/**
 * Optimization Admin Controller
 *
 * Handles all optimization page logic, actions, and data retrieval
 */
class WC_Cart_Tracker_Optimization_Admin
{
    /**
     * Initialize optimization admin
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_optimization_menu'), 70);
        add_action('admin_init', array($this, 'handle_optimization_actions'));
    }

    /**
     * Add optimization submenu
     */
    public function add_optimization_menu()
    {
        add_submenu_page(
            'woocommerce',
            __('Cart Tracker Optimization', 'wc-all-cart-tracker'),
            __('Optimization', 'wc-all-cart-tracker'),
            'manage_woocommerce',
            'wc-cart-optimization',
            array($this, 'render_optimization_page')
        );
    }

    /**
     * Handle optimization actions (POST requests)
     */
    public function handle_optimization_actions()
    {
        if (!isset($_POST['optimize_action']) || !check_admin_referer('wc_cart_optimize')) {
            return;
        }

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions', 'wc-all-cart-tracker'));
        }

        $action = sanitize_text_field($_POST['optimize_action']);
        $message = '';
        $type = 'success';

        switch ($action) {
            case 'optimize_table':
                $message = $this->optimize_table();
                break;

            case 'rebuild_indexes':
                $message = $this->rebuild_indexes();
                break;

            case 'clear_cache':
                $message = $this->clear_cache();
                break;

            case 'archive_old':
                $message = $this->archive_old_carts();
                break;

            case 'restore_archived':
                $message = $this->restore_archived_carts();
                break;

            case 'delete_old':
                $result = $this->delete_old_carts();
                $message = $result['message'];
                $type = $result['type'];
                break;

            case 'purge_archive':
                $result = $this->purge_archive();
                $message = $result['message'];
                $type = $result['type'];
                break;

            case 'save_settings':
                $message = $this->save_settings();
                break;
        }

        if (!empty($message)) {
            add_settings_error('wc_cart_optimization', 'optimization_message', $message, $type);
        }
    }

    /**
     * Optimize database table
     */
    private function optimize_table()
    {
        global $wpdb;
        $table_name = WC_Cart_Tracker_Database::get_table_name();
        $wpdb->query("OPTIMIZE TABLE {$table_name}");
        return __('Table optimized successfully!', 'wc-all-cart-tracker');
    }

    /**
     * Rebuild database indexes
     */
    private function rebuild_indexes()
    {
        WC_Cart_Tracker_Database::create_optimized_indexes();
        return __('Indexes rebuilt successfully!', 'wc-all-cart-tracker');
    }

    /**
     * Clear all caches
     */
    private function clear_cache()
    {
        WC_Cart_Tracker_Database::clear_cache();
        WC_Cart_Tracker_Analytics::clear_cache();
        return __('All caches cleared successfully!', 'wc-all-cart-tracker');
    }

    /**
     * Archive old carts
     */
    private function archive_old_carts()
    {
        $days = isset($_POST['cleanup_days']) ? absint($_POST['cleanup_days']) : 90;
        $archived = WC_Cart_Tracker_Database::cleanup_old_carts($days, false);
        return sprintf(
            __('Archived %d old carts (moved to archive table)!', 'wc-all-cart-tracker'),
            $archived
        );
    }

    /**
     * Restore archived carts
     */
    private function restore_archived_carts()
    {
        $restored = WC_Cart_Tracker_Database::restore_archived_carts(7);
        return sprintf(
            __('Restored %d recently archived carts!', 'wc-all-cart-tracker'),
            $restored
        );
    }

    /**
     * Permanently delete old carts
     */
    private function delete_old_carts()
    {
        if (!isset($_POST['confirm_delete']) || $_POST['confirm_delete'] !== 'yes') {
            return array(
                'message' => __('Deletion cancelled. Please check the confirmation box.', 'wc-all-cart-tracker'),
                'type' => 'error'
            );
        }

        $days = isset($_POST['cleanup_days']) ? absint($_POST['cleanup_days']) : 90;
        $deleted = WC_Cart_Tracker_Database::cleanup_old_carts($days, true);

        return array(
            'message' => sprintf(
                __('Permanently deleted %d old carts!', 'wc-all-cart-tracker'),
                $deleted
            ),
            'type' => 'success'
        );
    }

    /**
     * Purge old archives
     */
    private function purge_archive()
    {
        if (!isset($_POST['confirm_purge']) || $_POST['confirm_purge'] !== 'yes') {
            return array(
                'message' => __('Purge cancelled. Please check the confirmation box.', 'wc-all-cart-tracker'),
                'type' => 'error'
            );
        }

        $purged = WC_Cart_Tracker_Database::purge_archive(365);

        return array(
            'message' => sprintf(
                __('Purged %d archived carts older than 1 year!', 'wc-all-cart-tracker'),
                $purged
            ),
            'type' => 'success'
        );
    }

    /**
     * Save optimization settings
     */
    private function save_settings()
    {
        update_option('wcat_cleanup_enabled', sanitize_text_field($_POST['cleanup_enabled']));
        update_option('wcat_cleanup_days', absint($_POST['cleanup_days']));
        update_option('wcat_cleanup_method', sanitize_text_field($_POST['cleanup_method']));
        update_option('wcat_purge_archives', sanitize_text_field($_POST['purge_archives']));

        return __('Settings saved successfully!', 'wc-all-cart-tracker');
    }

    /**
     * Get database statistics
     */
    public static function get_database_stats()
    {
        global $wpdb;
        $table_name = WC_Cart_Tracker_Database::get_table_name();
        $archive_table = $table_name . '_archive';

        $stats = array(
            'total_rows' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}"),
            'active_rows' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE is_active = 1"),
            'table_size' => $wpdb->get_var($wpdb->prepare(
                "SELECT ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as size_mb
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s",
                DB_NAME,
                $table_name
            )),
            'archive_exists' => (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $archive_table)),
            'archive_rows' => 0,
            'archive_size' => 0
        );

        $stats['inactive_rows'] = $stats['total_rows'] - $stats['active_rows'];

        if ($stats['archive_exists']) {
            $stats['archive_rows'] = $wpdb->get_var("SELECT COUNT(*) FROM {$archive_table}");
            $stats['archive_size'] = $wpdb->get_var($wpdb->prepare(
                "SELECT ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as size_mb
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s",
                DB_NAME,
                $archive_table
            ));
        }

        return $stats;
    }

    /**
     * Get current settings
     */
    public static function get_settings()
    {
        return array(
            'cleanup_enabled' => get_option('wcat_cleanup_enabled', 'yes'),
            'cleanup_days' => get_option('wcat_cleanup_days', 90),
            'cleanup_method' => get_option('wcat_cleanup_method', 'archive'),
            'purge_archives' => get_option('wcat_purge_archives', 'no')
        );
    }

    /**
     * Render optimization page
     */
    public function render_optimization_page()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions', 'wc-all-cart-tracker'));
        }

        // Get data for the page
        $stats = self::get_database_stats();
        $settings = self::get_settings();

        // Load the main view
        require_once WC_CART_TRACKER_PLUGIN_DIR . 'admin/views/admin-optimization.php';
    }
}
