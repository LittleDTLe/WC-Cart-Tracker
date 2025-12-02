<?php
/**
 * Plugin Name: WC All Cart Tracker
 * Plugin URI: https://github.com/LittleDTLe/WC-Cart-Tracker/issues/new
 * Description: Tracks all active WooCommerce carts in real-time, including guest and registered user carts.
 * Version: 1.0.0
 * Author: Panagiotis Drougas
 * Author URI: https://github.com/LittleDTLe
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wc-all-cart-tracker
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Declare HPOS compatibility
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Plugin Class
 */
class WC_All_Cart_Tracker
{

    /**
     * Plugin version
     */
    const VERSION = '1.0.0';

    /**
     * Database table name (without prefix)
     */
    const TABLE_NAME = 'all_carts_tracker';

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Initialize plugin
     */
    public function init()
    {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Load text domain for translations
        load_plugin_textdomain('wc-all-cart-tracker', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Core tracking hooks
        add_action('woocommerce_add_to_cart', array($this, 'track_cart'), 10, 6);
        add_action('woocommerce_cart_item_removed', array($this, 'track_cart_update'), 10, 2);
        add_action('woocommerce_after_cart_item_quantity_update', array($this, 'track_cart_update'), 10, 4);
        add_action('woocommerce_cart_emptied', array($this, 'track_cart_update'));

        // Order completion hook
        add_action('woocommerce_thankyou', array($this, 'mark_cart_inactive'), 10, 1);
        add_action('woocommerce_order_status_completed', array($this, 'mark_cart_inactive_by_user'), 10, 1);

        // Checkout data capture
        add_action('woocommerce_checkout_update_order_meta', array($this, 'update_guest_data'), 10, 1);

        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'), 60);

        // Admin styles and scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        $this->create_table();
        $this->migrate_existing_data();

        // Set plugin version
        update_option('wc_all_cart_tracker_version', self::VERSION);

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        // Optionally clean up (not dropping table on deactivation for data safety)
        flush_rewrite_rules();
    }

    /**
     * Create custom database table
     */
    private function create_table()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;
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

    /**
     * Migrate existing data to add cart_status column
     */
    private function migrate_existing_data()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        // Check if cart_status column exists
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'cart_status'",
            DB_NAME,
            $table_name
        ));

        if (empty($column_exists)) {
            // Add cart_status column if it doesn't exist
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN cart_status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER is_active");
            $wpdb->query("ALTER TABLE {$table_name} ADD INDEX idx_cart_status (cart_status)");

            // Update existing records: active carts should have status 'active', inactive should be 'converted'
            $wpdb->query("UPDATE {$table_name} SET cart_status = 'active' WHERE is_active = 1");
            $wpdb->query("UPDATE {$table_name} SET cart_status = 'converted' WHERE is_active = 0");
        }
    }

    /**
     * Drop custom database table (called manually if needed)
     */
    public function drop_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    }

    /**
     * Track cart when items are added
     */
    public function track_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
    {
        $this->save_cart_data();
    }

    /**
     * Track cart updates (remove, quantity change, empty)
     */
    public function track_cart_update()
    {
        $this->save_cart_data();
    }

    /**
     * Save cart data to database
     */
    private function save_cart_data()
    {
        if (!WC()->cart) {
            return;
        }

        global $wpdb;

        // Get cart data
        $cart = WC()->cart;
        $cart_contents = $cart->get_cart();

        // If cart is empty, mark as inactive
        if (empty($cart_contents)) {
            $this->mark_cart_inactive_by_session();
            return;
        }

        // Get session and user data
        $session_id = $this->get_session_id();
        $user_id = get_current_user_id();

        if (empty($session_id) && $user_id === 0) {
            return; // No valid identifier
        }

        // Prepare cart content for storage
        $cart_data = array();
        foreach ($cart_contents as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];

            // Get proper product price
            $product_price = $product->get_price('edit');

            // Calculate line total (price * quantity)
            $line_subtotal = floatval($product_price) * floatval($cart_item['quantity']);

            $cart_data[] = array(
                'product_id' => $cart_item['product_id'],
                'variation_id' => $cart_item['variation_id'],
                'quantity' => $cart_item['quantity'],
                'product_name' => $product->get_name(),
                'product_price' => floatval($product_price),
                'line_total' => $line_subtotal,
            );
        }

        // Get cart total (including all fees, shipping if calculated, and taxes)
        $cart->calculate_totals();
        $cart_total = $cart->get_total('edit');

        // Get customer data
        $customer_email = '';
        $customer_name = '';

        if ($user_id > 0) {
            $user = get_userdata($user_id);
            $customer_email = $user->user_email;
            $customer_name = $user->display_name;
        } elseif (WC()->customer) {
            $customer_email = WC()->customer->get_billing_email();
            $customer_name = WC()->customer->get_billing_first_name() . ' ' . WC()->customer->get_billing_last_name();
            $customer_name = trim($customer_name);
        }

        // Get past purchases count
        $past_purchases = 0;
        if ($user_id > 0) {
            $past_purchases = $this->get_past_purchases_count($user_id);
        }

        // Prepare data for database
        $data = array(
            'session_id' => sanitize_text_field($session_id),
            'user_id' => absint($user_id),
            'cart_content' => wp_json_encode($cart_data),
            'cart_total' => floatval($cart_total),
            'customer_email' => sanitize_email($customer_email),
            'customer_name' => sanitize_text_field($customer_name),
            'past_purchases' => absint($past_purchases),
            'last_updated' => current_time('mysql'),
            'is_active' => 1,
            'cart_status' => 'active',
        );

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        // Check if entry exists
        $existing = $this->get_existing_cart_entry($session_id, $user_id);

        if ($existing) {
            // Update existing entry
            $wpdb->update(
                $table_name,
                $data,
                array('id' => $existing->id),
                array('%s', '%d', '%s', '%f', '%s', '%s', '%d', '%s', '%d', '%s'),
                array('%d')
            );
        } else {
            // Insert new entry
            $wpdb->insert(
                $table_name,
                $data,
                array('%s', '%d', '%s', '%f', '%s', '%s', '%d', '%s', '%d', '%s')
            );
        }
    }

    /**
     * Get existing cart entry
     */
    private function get_existing_cart_entry($session_id, $user_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        // Try to find by user_id first if logged in
        if ($user_id > 0) {
            $entry = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE user_id = %d AND is_active = 1 ORDER BY last_updated DESC LIMIT 1",
                $user_id
            ));

            if ($entry) {
                return $entry;
            }
        }

        // Try to find by session_id
        if (!empty($session_id)) {
            $entry = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE session_id = %s AND is_active = 1 ORDER BY last_updated DESC LIMIT 1",
                $session_id
            ));

            return $entry;
        }

        return null;
    }

    /**
     * Get WooCommerce session ID
     */
    private function get_session_id()
    {
        if (WC()->session) {
            $customer_id = WC()->session->get_customer_id();
            return !empty($customer_id) ? $customer_id : '';
        }
        return '';
    }

    /**
     * Get past purchases count for user
     */
    private function get_past_purchases_count($user_id)
    {
        if ($user_id === 0) {
            return 0;
        }

        $args = array(
            'customer_id' => $user_id,
            'status' => array('wc-completed', 'wc-processing'),
            'return' => 'ids',
            'limit' => -1,
        );

        $orders = wc_get_orders($args);
        return count($orders);
    }

    /**
     * Mark cart as inactive when order is completed
     */
    public function mark_cart_inactive($order_id)
    {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $user_id = $order->get_user_id();
        $session_id = $this->get_session_id();

        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        // Mark cart as converted (not deleted)
        $update_data = array(
            'is_active' => 0,
            'cart_status' => 'converted'
        );

        // Mark cart as inactive
        if ($user_id > 0) {
            $wpdb->update(
                $table_name,
                $update_data,
                array('user_id' => $user_id, 'is_active' => 1),
                array('%d', '%s'),
                array('%d', '%d')
            );
        }

        if (!empty($session_id)) {
            $wpdb->update(
                $table_name,
                $update_data,
                array('session_id' => $session_id, 'is_active' => 1),
                array('%d', '%s'),
                array('%s', '%d')
            );
        }
    }

    /**
     * Mark cart as inactive by user (alternative method)
     */
    public function mark_cart_inactive_by_user($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $user_id = $order->get_user_id();

        if ($user_id > 0) {
            global $wpdb;
            $table_name = $wpdb->prefix . self::TABLE_NAME;

            $wpdb->update(
                $table_name,
                array('is_active' => 0, 'cart_status' => 'converted'),
                array('user_id' => $user_id, 'is_active' => 1),
                array('%d', '%s'),
                array('%d', '%d')
            );
        }
    }

    /**
     * Mark cart as inactive by current session
     */
    private function mark_cart_inactive_by_session()
    {
        $session_id = $this->get_session_id();
        $user_id = get_current_user_id();

        if (empty($session_id) && $user_id === 0) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        // Mark as deleted/cleared instead of converted
        $update_data = array(
            'is_active' => 0,
            'cart_status' => 'deleted'
        );

        if ($user_id > 0) {
            $wpdb->update(
                $table_name,
                $update_data,
                array('user_id' => $user_id, 'is_active' => 1),
                array('%d', '%s'),
                array('%d', '%d')
            );
        } elseif (!empty($session_id)) {
            $wpdb->update(
                $table_name,
                $update_data,
                array('session_id' => $session_id, 'is_active' => 1),
                array('%d', '%s'),
                array('%s', '%d')
            );
        }
    }

    /**
     * Update guest data from checkout form
     */
    public function update_guest_data($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $session_id = $this->get_session_id();
        $user_id = $order->get_user_id();

        if (empty($session_id) && $user_id === 0) {
            return;
        }

        $customer_email = $order->get_billing_email();
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $customer_name = trim($customer_name);

        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $update_data = array(
            'customer_email' => sanitize_email($customer_email),
            'customer_name' => sanitize_text_field($customer_name),
        );

        if ($user_id > 0) {
            $wpdb->update(
                $table_name,
                $update_data,
                array('user_id' => $user_id, 'is_active' => 1),
                array('%s', '%s'),
                array('%d', '%d')
            );
        } elseif (!empty($session_id)) {
            $wpdb->update(
                $table_name,
                $update_data,
                array('session_id' => $session_id, 'is_active' => 1),
                array('%s', '%s'),
                array('%s', '%d')
            );
        }
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'woocommerce',
            __('All Cart Tracker', 'wc-all-cart-tracker'),
            __('Cart Tracker', 'wc-all-cart-tracker'),
            'manage_woocommerce',
            'wc-all-cart-tracker',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Get analytics data
     */
    private function get_analytics_data($days = 30)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Total carts created in period
        $total_carts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE last_updated >= %s",
            $date_from
        ));

        // Converted carts (completed orders) in period
        $converted_carts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE last_updated >= %s AND cart_status = 'converted'",
            $date_from
        ));

        // Deleted/cleared carts in period
        $deleted_carts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE last_updated >= %s AND cart_status = 'deleted'",
            $date_from
        ));

        // Active carts
        $active_carts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE is_active = 1"
        ));

        // Abandoned carts (older than 24 hours and still active)
        $abandoned_carts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE is_active = 1 AND last_updated < %s",
            date('Y-m-d H:i:s', strtotime('-24 hours'))
        ));

        // Average cart value (active)
        $avg_active_cart = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(cart_total) FROM {$table_name} WHERE is_active = 1"
        ));

        // Average cart value (converted)
        $avg_converted_cart = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(cart_total) FROM {$table_name} WHERE last_updated >= %s AND cart_status = 'converted'",
            $date_from
        ));

        // Total revenue potential (active carts)
        $revenue_potential = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(cart_total) FROM {$table_name} WHERE is_active = 1"
        ));

        // Conversion rate (only counting converted vs total, excluding deleted)
        $conversion_rate = $total_carts > 0 ? ($converted_carts / $total_carts) * 100 : 0;

        // Abandonment rate
        $abandonment_rate = $total_carts > 0 ? (($abandoned_carts + $active_carts - $converted_carts) / $total_carts) * 100 : 0;

        // Carts by customer type
        $registered_carts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE last_updated >= %s AND user_id > 0",
            $date_from
        ));

        $guest_carts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE last_updated >= %s AND user_id = 0",
            $date_from
        ));

        // Conversion by customer type
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
            'revenue_potential' => $revenue_potential ? floatval($revenue_potential) : 0,
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

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook)
    {
        if ('woocommerce_page_wc-all-cart-tracker' !== $hook) {
            return;
        }

        // Enqueue WordPress default table styles
        wp_enqueue_style('wp-admin');
    }

    /**
     * Render admin page
     */
    public function render_admin_page()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        // Get date range filter
        $days = isset($_GET['days']) ? absint($_GET['days']) : 30;
        if (!in_array($days, array(7, 30, 60, 90))) {
            $days = 30;
        }

        // Get analytics data
        $analytics = $this->get_analytics_data($days);

        // Get sorting parameters
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'last_updated';
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

        // Validate orderby
        $allowed_orderby = array('last_updated', 'customer_email', 'past_purchases', 'cart_total');
        if (!in_array($orderby, $allowed_orderby)) {
            $orderby = 'last_updated';
        }

        // Validate order
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        // Get active carts
        $carts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE is_active = %d ORDER BY {$orderby} {$order}",
            1
        ));

        ?>
                <div class="wrap">
                    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
                    <!-- Analytics Dashboard -->
                    <div class="wc-cart-analytics-dashboard" style="margin: 20px 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <h2 style="margin: 0;"><?php echo esc_html__('Analytics Overview', 'wc-all-cart-tracker'); ?></h2>
                            <div>
                                <label for="days-filter"><?php echo esc_html__('Time Period:', 'wc-all-cart-tracker'); ?></label>
                                <select id="days-filter" onchange="window.location.href='<?php echo esc_url(admin_url('admin.php?page=wc-all-cart-tracker')); ?>&days=' + this.value">
                                    <option value="7" <?php selected($days, 7); ?>><?php echo esc_html__('Last 7 Days', 'wc-all-cart-tracker'); ?></option>
                                    <option value="30" <?php selected($days, 30); ?>><?php echo esc_html__('Last 30 Days', 'wc-all-cart-tracker'); ?></option>
                                    <option value="60" <?php selected($days, 60); ?>><?php echo esc_html__('Last 60 Days', 'wc-all-cart-tracker'); ?></option>
                                    <option value="90" <?php selected($days, 90); ?>><?php echo esc_html__('Last 90 Days', 'wc-all-cart-tracker'); ?></option>
                                </select>
                            </div>
                        </div>
                
                        <!-- Key Metrics Cards -->
                        <div class="wc-cart-metrics" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                            <!-- Conversion Rate -->
                            <div class="metric-card" style="background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #2271b1; padding: 15px; border-radius: 4px;">
                                <div style="font-size: 13px; color: #646970; margin-bottom: 5px;"><?php echo esc_html__('Conversion Rate', 'wc-all-cart-tracker'); ?></div>
                                <div style="font-size: 28px; font-weight: 600; color: #2271b1;">
                                    <?php echo esc_html($analytics['conversion_rate']); ?>%
                                </div>
                                <div style="font-size: 12px; color: #646970; margin-top: 5px;">
                                    <?php echo esc_html($analytics['converted_carts']); ?> / <?php echo esc_html($analytics['total_carts']); ?>         <?php echo esc_html__('carts', 'wc-all-cart-tracker'); ?>
                                </div>
                            </div>
                    
                            <!-- Active Carts -->
                            <div class="metric-card" style="background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #00a32a; padding: 15px; border-radius: 4px;">
                                <div style="font-size: 13px; color: #646970; margin-bottom: 5px;"><?php echo esc_html__('Active Carts', 'wc-all-cart-tracker'); ?></div>
                                <div style="font-size: 28px; font-weight: 600; color: #00a32a;">
                                    <?php echo esc_html($analytics['active_carts']); ?>
                                </div>
                                <div style="font-size: 12px; color: #646970; margin-top: 5px;">
                                    <?php echo esc_html__('Currently in cart', 'wc-all-cart-tracker'); ?>
                                </div>
                            </div>
                    
                            <!-- Abandoned Carts -->
                            <div class="metric-card" style="background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #d63638; padding: 15px; border-radius: 4px;">
                                <div style="font-size: 13px; color: #646970; margin-bottom: 5px;"><?php echo esc_html__('Abandoned Carts', 'wc-all-cart-tracker'); ?></div>
                                <div style="font-size: 28px; font-weight: 600; color: #d63638;">
                                    <?php echo esc_html($analytics['abandoned_carts']); ?>
                                </div>
                                <div style="font-size: 12px; color: #646970; margin-top: 5px;">
                                    <?php echo esc_html__('Inactive > 24hrs', 'wc-all-cart-tracker'); ?>
                                </div>
                            </div>
                    
                            <!-- Deleted Carts -->
                            <div class="metric-card" style="background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #f0b849; padding: 15px; border-radius: 4px;">
                                <div style="font-size: 13px; color: #646970; margin-bottom: 5px;"><?php echo esc_html__('Deleted Carts', 'wc-all-cart-tracker'); ?></div>
                                <div style="font-size: 28px; font-weight: 600; color: #f0b849;">
                                    <?php echo esc_html($analytics['deleted_carts']); ?>
                                </div>
                                <div style="font-size: 12px; color: #646970; margin-top: 5px;">
                                    <?php echo esc_html__('Cleared by user', 'wc-all-cart-tracker'); ?>
                                </div>
                            </div>
                    
                            <!-- Revenue Potential -->
                            <div class="metric-card" style="background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #7e3bd0; padding: 15px; border-radius: 4px;">
                                <div style="font-size: 13px; color: #646970; margin-bottom: 5px;"><?php echo esc_html__('Revenue Potential', 'wc-all-cart-tracker'); ?></div>
                                <div style="font-size: 28px; font-weight: 600; color: #7e3bd0;">
                                    <?php echo wc_price($analytics['revenue_potential']); ?>
                                </div>
                                <div style="font-size: 12px; color: #646970; margin-top: 5px;">
                                    <?php echo esc_html__('Active carts total', 'wc-all-cart-tracker'); ?>
                                </div>
                            </div>
                        </div>
                
                        <!-- Additional Metrics -->
                        <div class="wc-cart-metrics-detailed" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                            <!-- Average Cart Values -->
                            <div class="metric-card" style="background: #fff; border: 1px solid #c3c4c7; padding: 15px; border-radius: 4px;">
                                <h3 style="margin: 0 0 15px 0; font-size: 14px;"><?php echo esc_html__('Average Cart Value', 'wc-all-cart-tracker'); ?></h3>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span style="color: #646970;"><?php echo esc_html__('Active Carts:', 'wc-all-cart-tracker'); ?></span>
                                    <strong><?php echo wc_price($analytics['avg_active_cart']); ?></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #646970;"><?php echo esc_html__('Converted Carts:', 'wc-all-cart-tracker'); ?></span>
                                    <strong><?php echo wc_price($analytics['avg_converted_cart']); ?></strong>
                                </div>
                            </div>
                    
                            <!-- Customer Type Breakdown -->
                            <div class="metric-card" style="background: #fff; border: 1px solid #c3c4c7; padding: 15px; border-radius: 4px;">
                                <h3 style="margin: 0 0 15px 0; font-size: 14px;"><?php echo esc_html__('By Customer Type', 'wc-all-cart-tracker'); ?></h3>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span style="color: #646970;"><?php echo esc_html__('Registered Users:', 'wc-all-cart-tracker'); ?></span>
                                    <strong><?php echo esc_html($analytics['registered_carts']); ?> (<?php echo esc_html($analytics['registered_conversion_rate']); ?>%)</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #646970;"><?php echo esc_html__('Guest Users:', 'wc-all-cart-tracker'); ?></span>
                                    <strong><?php echo esc_html($analytics['guest_carts']); ?> (<?php echo esc_html($analytics['guest_conversion_rate']); ?>%)</strong>
                                </div>
                            </div>
                    
                            <!-- Summary Stats -->
                            <div class="metric-card" style="background: #fff; border: 1px solid #c3c4c7; padding: 15px; border-radius: 4px;">
                                <h3 style="margin: 0 0 15px 0; font-size: 14px;"><?php echo esc_html__('Summary', 'wc-all-cart-tracker'); ?></h3>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span style="color: #646970;"><?php echo esc_html__('Total Carts:', 'wc-all-cart-tracker'); ?></span>
                                    <strong><?php echo esc_html($analytics['total_carts']); ?></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #646970;"><?php echo esc_html__('Abandonment Rate:', 'wc-all-cart-tracker'); ?></span>
                                    <strong style="color: #d63638;"><?php echo esc_html($analytics['abandonment_rate']); ?>%</strong>
                                </div>
                            </div>
                        </div>
                    </div>
            
                    <hr style="margin: 30px 0;">
            
                    <h2><?php echo esc_html__('Active Carts', 'wc-all-cart-tracker'); ?></h2>
            
                    <div class="tablenav top">
                        <div class="alignleft actions">
                            <span class="displaying-num"><?php printf(esc_html__('%d active carts', 'wc-all-cart-tracker'), count($carts)); ?></span>
                        </div>
                    </div>
            
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('ID', 'wc-all-cart-tracker'); ?></th>
                                <th class="sortable <?php echo $orderby === 'last_updated' ? 'sorted' : ''; ?> <?php echo strtolower($order); ?>">
                                    <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'last_updated', 'order' => $orderby === 'last_updated' && $order === 'DESC' ? 'ASC' : 'DESC'))); ?>">
                                        <?php echo esc_html__('Last Updated', 'wc-all-cart-tracker'); ?>
                                        <span class="sorting-indicator"></span>
                                    </a>
                                </th>
                                <th class="sortable <?php echo $orderby === 'customer_email' ? 'sorted' : ''; ?> <?php echo strtolower($order); ?>">
                                    <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'customer_email', 'order' => $orderby === 'customer_email' && $order === 'DESC' ? 'ASC' : 'DESC'))); ?>">
                                        <?php echo esc_html__('Customer', 'wc-all-cart-tracker'); ?>
                                        <span class="sorting-indicator"></span>
                                    </a>
                                </th>
                                <th class="sortable <?php echo $orderby === 'past_purchases' ? 'sorted' : ''; ?> <?php echo strtolower($order); ?>">
                                    <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'past_purchases', 'order' => $orderby === 'past_purchases' && $order === 'DESC' ? 'ASC' : 'DESC'))); ?>">
                                        <?php echo esc_html__('Past Purchases', 'wc-all-cart-tracker'); ?>
                                        <span class="sorting-indicator"></span>
                                    </a>
                                </th>
                                <th class="sortable <?php echo $orderby === 'cart_total' ? 'sorted' : ''; ?> <?php echo strtolower($order); ?>">
                                    <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'cart_total', 'order' => $orderby === 'cart_total' && $order === 'DESC' ? 'ASC' : 'DESC'))); ?>">
                                        <?php echo esc_html__('Cart Total', 'wc-all-cart-tracker'); ?>
                                        <span class="sorting-indicator"></span>
                                    </a>
                                </th>
                                <th><?php echo esc_html__('Cart Contents', 'wc-all-cart-tracker'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($carts)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center;">
                                            <?php echo esc_html__('No active carts found.', 'wc-all-cart-tracker'); ?>
                                        </td>
                                    </tr>
                            <?php else: ?>
                                    <?php foreach ($carts as $cart): ?>
                                            <tr>
                                                <td><?php echo esc_html($cart->id); ?></td>
                                                <td>
                                                    <?php
                                                    $datetime = new DateTime($cart->last_updated);
                                                    echo esc_html($datetime->format('Y-m-d H:i:s'));
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($cart->customer_email)): ?>
                                                            <strong><?php echo esc_html($cart->customer_name); ?></strong><br>
                                                            <a href="mailto:<?php echo esc_attr($cart->customer_email); ?>">
                                                                <?php echo esc_html($cart->customer_email); ?>
                                                            </a>
                                                            <?php if ($cart->user_id > 0): ?>
                                                                    <br><small>(User ID: <?php echo esc_html($cart->user_id); ?>)</small>
                                                            <?php endif; ?>
                                                    <?php else: ?>
                                                            <em><?php echo esc_html__('Guest', 'wc-all-cart-tracker'); ?></em><br>
                                                            <small><?php echo esc_html(substr($cart->session_id, 0, 20)) . '...'; ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo esc_html($cart->past_purchases); ?></strong>
                                                </td>
                                                <td>
                                                    <strong><?php echo wc_price($cart->cart_total); ?></strong>
                                                </td>
                                                <td>
                                                    <?php
                                                    $cart_items = json_decode($cart->cart_content, true);
                                                    if (!empty($cart_items) && is_array($cart_items)):
                                                        echo '<ul style="margin: 0; padding-left: 20px;">';
                                                        foreach ($cart_items as $item):
                                                            echo '<li>';
                                                            echo esc_html($item['product_name']);
                                                            echo ' × ' . esc_html($item['quantity']);
                                                            echo ' (' . wc_price($item['line_total']) . ')';
                                                            echo '</li>';
                                                        endforeach;
                                                        echo '</ul>';
                                                    else:
                                                        echo esc_html__('No items', 'wc-all-cart-tracker');
                                                    endif;
                                                    ?>
                                                </td>
                                            </tr>
                                    <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
            
                    <style>
                        .wp-list-table th { white-space: nowrap; }
                        .wp-list-table td { vertical-align: top; padding: 12px 10px; }
                        .wp-list-table td ul { margin: 0; }
                        .wp-list-table .sortable a { text-decoration: none; color: inherit; }
                        .wp-list-table .sorted a { color: #2271b1; }
                    </style>
                </div>
                <?php
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice()
    {
        ?>
                <div class="notice notice-error">
                    <p>
                        <?php
                        echo esc_html__('WC All Cart Tracker requires WooCommerce to be installed and active.', 'wc-all-cart-tracker');
                        ?>
                    </p>
                </div>
                <?php
    }
}

// Initialize plugin
WC_All_Cart_Tracker::get_instance();
