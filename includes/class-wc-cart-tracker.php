<?php
/**
 * Main Plugin Class
 *
 * @package WC_All_Cart_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Cart_Tracker
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->init();
    }

    public function init()
    {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Load text domain
        load_plugin_textdomain('wc-all-cart-tracker', false, dirname(plugin_basename(WC_CART_TRACKER_PLUGIN_FILE)) . '/languages');

        // Initialize components
        $this->load_dependencies();
    }

    private function load_dependencies()
    {
        // Load database handler
        require_once WC_CART_TRACKER_PLUGIN_DIR . 'includes/class-wc-cart-database.php';

        // Load tracking handler
        require_once WC_CART_TRACKER_PLUGIN_DIR . 'includes/class-wc-cart-tracking.php';
        new WC_Cart_Tracker_Tracking();

        // Load analytics handler
        require_once WC_CART_TRACKER_PLUGIN_DIR . 'includes/class-wc-cart-analytics.php';

        // Load sanitization class
        require_once WC_CART_TRACKER_PLUGIN_DIR . 'includes/class-wc-cart-data-sanitizer.php';

        // Load export template system
        require_once WC_CART_TRACKER_PLUGIN_DIR . 'includes/class-wc-cart-export-templates.php';

        // Load export AJAX handler
        require_once WC_CART_TRACKER_PLUGIN_DIR . 'includes/class-wc-cart-export-ajax.php';

        // Load export handler
        require_once WC_CART_TRACKER_PLUGIN_DIR . 'includes/class-wc-cart-export.php';

        // Load scheduled export handler
        require_once WC_CART_TRACKER_PLUGIN_DIR . 'includes/class-wc-cart-scheduled-export.php';

        // Load abandoned cart email handler
        require_once WC_CART_TRACKER_PLUGIN_DIR . 'includes/class-wc-cart-abandoned-email.php';

        // Load admin if in admin area
        if (is_admin()) {
            require_once WC_CART_TRACKER_PLUGIN_DIR . 'admin/class-wc-cart-admin.php';
            new WC_Cart_Tracker_Admin();
        }
    }

    public function woocommerce_missing_notice()
    {
        ?>
        <div class="notice notice-error">
            <p>
                <?php echo esc_html__('WC All Cart Tracker requires WooCommerce to be installed and active.', 'wc-all-cart-tracker'); ?>
            </p>
        </div>
        <?php
    }
}

/**
 * Schedule cart state updates (every 6 hours)
 */
function wc_cart_tracker_schedule_state_updates()
{
    if (!wp_next_scheduled('wc_cart_tracker_update_states')) {
        wp_schedule_event(time(), 'sixhourly', 'wc_cart_tracker_update_states');
    }
}
add_action('wp', 'wc_cart_tracker_schedule_state_updates');

/**
 * Add custom cron schedule for 6-hour intervals
 */
add_filter('cron_schedules', function ($schedules) {
    $schedules['sixhourly'] = array(
        'interval' => 6 * HOUR_IN_SECONDS,
        'display' => __('Every 6 Hours', 'wc-all-cart-tracker')
    );
    return $schedules;
});

/**
 * Run cart state updates
 */
function wc_cart_tracker_run_state_updates()
{
    if (class_exists('WC_Cart_Tracker_Tracking')) {
        WC_Cart_Tracker_Tracking::update_all_cart_states();

        // Clear analytics cache
        if (class_exists('WC_Cart_Tracker_Analytics')) {
            WC_Cart_Tracker_Analytics::clear_cache();
        }
    }
}
add_action('wc_cart_tracker_update_states', 'wc_cart_tracker_run_state_updates');