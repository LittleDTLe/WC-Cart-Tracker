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

        // Load exporter
        require_once WC_CART_TRACKER_PLUGIN_DIR . 'includes/class-wc-cart-export.php';

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