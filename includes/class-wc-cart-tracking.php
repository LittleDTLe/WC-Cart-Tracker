<?php
/**
 * Enhanced Cart Tracking with Proper State Management
 *
 * @package WC_All_Cart_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Cart_Tracker_Tracking
{
    // Time thresholds (in hours)
    const ACTIVE_THRESHOLD = 24;        // 24 hours
    const RECOVERABLE_THRESHOLD = 168;  // 7 days  
    const ABANDONED_THRESHOLD = 360;    // 15 days

    public function __construct()
    {
        $this->init_hooks();
    }

    private function init_hooks()
    {
        add_action('woocommerce_add_to_cart', array($this, 'track_cart'), 10, 6);
        add_action('woocommerce_cart_item_removed', array($this, 'track_cart_update'), 10, 2);
        add_action('woocommerce_after_cart_item_quantity_update', array($this, 'track_cart_update'), 10, 4);
        add_action('woocommerce_cart_emptied', array($this, 'handle_cart_emptied_status'), 10, 0);
        add_action('woocommerce_thankyou', array($this, 'mark_cart_converted'), 10, 1);
        add_action('woocommerce_order_status_completed', array($this, 'mark_cart_converted_by_user'), 10, 1);
        add_action('woocommerce_checkout_update_order_meta', array($this, 'update_guest_data'), 10, 1);
    }

    public function track_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
    {
        $this->save_cart_data();
    }

    public function track_cart_update()
    {
        $this->save_cart_data();
    }

    private function save_cart_data()
    {
        if (!WC()->cart) {
            return;
        }

        $cart = WC()->cart;
        $cart_contents = $cart->get_cart();

        if (empty($cart_contents)) {
            $this->mark_cart_deleted();
            return;
        }

        $session_id = $this->get_session_id();
        $user_id = get_current_user_id();

        if (empty($session_id) && $user_id === 0) {
            return;
        }

        $cart_data = array();
        foreach ($cart_contents as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $product_price = floatval($product->get_price('edit'));
            $line_subtotal = $product_price * floatval($cart_item['quantity']);

            $cart_data[] = array(
                'product_id' => $cart_item['product_id'],
                'variation_id' => $cart_item['variation_id'],
                'quantity' => $cart_item['quantity'],
                'product_name' => $product->get_name(),
                'product_price' => $product_price,
                'line_total' => $line_subtotal,
            );
        }

        $cart->calculate_totals();
        $cart_total = floatval($cart->get_total('edit'));

        $customer_email = '';
        $customer_name = '';

        if ($user_id > 0) {
            $user = get_userdata($user_id);
            $customer_email = $user->user_email;
            $customer_name = $user->display_name;
        } elseif (WC()->customer) {
            $customer_email = WC()->customer->get_billing_email();
            $customer_name = trim(WC()->customer->get_billing_first_name() . ' ' . WC()->customer->get_billing_last_name());
        }

        $past_purchases = $this->get_past_purchases_count($user_id);

        // KEY CHANGE: Active carts have is_active = 1, status = 'active'
        $data = array(
            'session_id' => sanitize_text_field($session_id),
            'user_id' => absint($user_id),
            'cart_content' => wp_json_encode($cart_data),
            'cart_total' => $cart_total,
            'customer_email' => sanitize_email($customer_email),
            'customer_name' => sanitize_text_field($customer_name),
            'past_purchases' => absint($past_purchases),
            'last_updated' => current_time('mysql'),
            'is_active' => 1,           // Only active carts have this as 1
            'cart_status' => 'active',  // Will transition via cron
        );

        WC_Cart_Tracker_Database::save_cart($data);
    }

    private function get_session_id()
    {
        if (WC()->session) {
            $customer_id = WC()->session->get_customer_id();
            return !empty($customer_id) ? $customer_id : '';
        }
        return '';
    }

    private function get_past_purchases_count($user_id)
    {
        if ($user_id === 0) {
            return 0;
        }

        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'status' => array('wc-completed', 'wc-processing'),
            'return' => 'ids',
            'limit' => -1,
        ));

        return count($orders);
    }

    public function mark_cart_converted($order_id)
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

        // KEY CHANGE: Converted carts have is_active = 0
        WC_Cart_Tracker_Database::update_cart_status($session_id, $user_id, 'converted');
    }

    public function mark_cart_converted_by_user($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $user_id = $order->get_user_id();
        WC_Cart_Tracker_Database::update_cart_status('', $user_id, 'converted');
    }

    private function mark_cart_deleted()
    {
        if (is_admin() && isset($_GET['post']) && get_post_type($_GET['post']) === 'shop_order') {
            return;
        }

        if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received')) {
            return;
        }

        if (is_checkout() || is_order_received_page()) {
            return;
        }

        $session_id = $this->get_session_id();
        $user_id = get_current_user_id();

        WC_Cart_Tracker_Database::update_cart_status($session_id, $user_id, 'deleted');
    }

    public function handle_cart_emptied_status()
    {
        $this->mark_cart_deleted();
    }

    public function update_guest_data($order_id)
    {
        $order = wc_get_order($order_id);
        if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received')) {
            return;
        }

        $session_id = $this->get_session_id();
        $user_id = $order->get_user_id();

        if (empty($session_id) && $user_id === 0) {
            return;
        }

        global $wpdb;
        $table_name = WC_Cart_Tracker_Database::get_table_name();

        $update_data = array(
            'customer_email' => sanitize_email($order->get_billing_email()),
            'customer_name' => sanitize_text_field(trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name())),
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
     * Helper: Calculate cart state based on last_updated
     */
    public static function calculate_cart_state($last_updated, $current_status = null)
    {
        // Preserve converted/deleted status
        if (in_array($current_status, array('converted', 'deleted'))) {
            return array(
                'status' => $current_status,
                'is_active' => 0
            );
        }

        $hours_old = (current_time('timestamp') - strtotime($last_updated)) / 3600;

        if ($hours_old < self::ACTIVE_THRESHOLD) {
            return array('status' => 'active', 'is_active' => 1);
        } elseif ($hours_old < self::RECOVERABLE_THRESHOLD) {
            return array('status' => 'recoverable', 'is_active' => 0);
        } elseif ($hours_old < self::ABANDONED_THRESHOLD) {
            return array('status' => 'abandoned', 'is_active' => 0);
        } else {
            return array('status' => 'cleared', 'is_active' => 0);
        }
    }

    /**
     * Batch update all cart states
     */
    public static function update_all_cart_states()
    {
        global $wpdb;
        $table_name = WC_Cart_Tracker_Database::get_table_name();

        $active_cutoff = date('Y-m-d H:i:s', strtotime('-' . self::ACTIVE_THRESHOLD . ' hours'));
        $recoverable_cutoff = date('Y-m-d H:i:s', strtotime('-' . self::RECOVERABLE_THRESHOLD . ' hours'));
        $abandoned_cutoff = date('Y-m-d H:i:s', strtotime('-' . self::ABANDONED_THRESHOLD . ' hours'));

        // Update active -> recoverable
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name} 
            SET cart_status = 'recoverable', is_active = 0 
            WHERE cart_status = 'active' 
            AND last_updated < %s 
            AND last_updated >= %s",
            $active_cutoff,
            $recoverable_cutoff
        ));

        // Update recoverable -> abandoned
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name} 
            SET cart_status = 'abandoned', is_active = 0 
            WHERE cart_status = 'recoverable' 
            AND last_updated < %s 
            AND last_updated >= %s",
            $recoverable_cutoff,
            $abandoned_cutoff
        ));

        // Update abandoned -> cleared
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name} 
            SET cart_status = 'cleared', is_active = 0 
            WHERE cart_status = 'abandoned' 
            AND last_updated < %s",
            $abandoned_cutoff
        ));

        // Ensure converted/deleted have is_active = 0
        $wpdb->query(
            "UPDATE {$table_name} 
            SET is_active = 0 
            WHERE cart_status IN ('converted', 'deleted') 
            AND is_active = 1"
        );

        return true;
    }
}