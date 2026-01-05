<?php
/**
 * Abandoned Cart Email System
 *
 * Handles automated email sending for abandoned carts
 *
 * @package WC_All_Cart_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Cart_Tracker_Abandoned_Email
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
        // Register cron hook for abandoned cart emails
        add_action('wcat_send_abandoned_cart_emails', array($this, 'send_abandoned_cart_emails'));

        // Admin hooks
        add_action('admin_init', array($this, 'handle_email_settings'));

        // AJAX handlers for test email
        add_action('wp_ajax_wcat_test_abandoned_email', array($this, 'ajax_test_email'));

        // Schedule cron if enabled
        add_action('wp', array($this, 'schedule_email_cron'));
    }

    /**
     * Schedule the cron job for abandoned cart emails
     */
    public function schedule_email_cron()
    {
        $enabled = get_option('wcat_abandoned_email_enabled', 'no');

        if ($enabled === 'yes') {
            if (!wp_next_scheduled('wcat_send_abandoned_cart_emails')) {
                wp_schedule_event(time(), 'hourly', 'wcat_send_abandoned_cart_emails');
            }
        } else {
            wp_clear_scheduled_hook('wcat_send_abandoned_cart_emails');
        }
    }

    /**
     * Main function to send abandoned cart emails
     */
    public function send_abandoned_cart_emails()
    {
        global $wpdb;
        $table_name = WC_Cart_Tracker_Database::get_table_name();

        $enabled = get_option('wcat_abandoned_email_enabled', 'no');
        if ($enabled !== 'yes') {
            return;
        }

        // Get settings
        $wait_time = absint(get_option('wcat_abandoned_email_wait_time', 1));
        $send_to_guests = get_option('wcat_abandoned_email_guests', 'yes');

        // Calculate cutoff times
        $abandoned_after = date('Y-m-d H:i:s', strtotime("-{$wait_time} hours"));
        $not_too_old = date('Y-m-d H:i:s', strtotime('-7 days')); // Don't email carts older than 7 days

        // Build query conditions
        $conditions = array(
            "is_active = 1",
            "cart_status = 'active'",
            "last_updated < %s",
            "last_updated > %s",
            "customer_email != ''"
        );

        // Exclude guests if setting is disabled
        if ($send_to_guests !== 'yes') {
            $conditions[] = "user_id > 0";
        }

        // Get carts that haven't been emailed yet
        $conditions[] = "id NOT IN (SELECT cart_id FROM {$wpdb->prefix}wcat_abandoned_emails WHERE sent_at > DATE_SUB(NOW(), INTERVAL 7 DAY))";

        $where_clause = implode(' AND ', $conditions);

        $carts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
            WHERE {$where_clause}
            ORDER BY last_updated DESC 
            LIMIT 50",
            $abandoned_after,
            $not_too_old
        ));

        if (empty($carts)) {
            return;
        }

        $sent_count = 0;

        foreach ($carts as $cart) {
            // Skip if no email
            if (empty($cart->customer_email)) {
                continue;
            }

            // Send email
            $result = $this->send_single_abandoned_email($cart);

            if ($result) {
                $this->log_email_sent($cart->id, $cart->customer_email);
                $sent_count++;
            }
        }

        // Log to WordPress debug
        if ($sent_count > 0) {
            error_log(sprintf('WC Cart Tracker: Sent %d abandoned cart emails', $sent_count));
        }
    }

    /**
     * Send a single abandoned cart email
     */
    private function send_single_abandoned_email($cart)
    {
        $customer_email = sanitize_email($cart->customer_email);
        $customer_name = !empty($cart->customer_name) ? $cart->customer_name : 'Customer';

        // Get email settings
        $subject = get_option('wcat_abandoned_email_subject', 'You left items in your cart!');
        $heading = get_option('wcat_abandoned_email_heading', 'Complete Your Purchase');
        $message = get_option('wcat_abandoned_email_message', 'You have items waiting in your cart. Complete your purchase now!');

        // Replace placeholders
        $subject = $this->replace_placeholders($subject, $cart, $customer_name);
        $heading = $this->replace_placeholders($heading, $cart, $customer_name);
        $message = $this->replace_placeholders($message, $cart, $customer_name);

        // Build HTML email
        $email_html = $this->build_email_html($heading, $message, $cart);

        // Email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );

        // Send email
        return wp_mail($customer_email, $subject, $email_html, $headers);
    }

    /**
     * Replace placeholders in email content
     */
    private function replace_placeholders($content, $cart, $customer_name)
    {
        $cart_url = wc_get_cart_url();
        $store_name = get_bloginfo('name');

        $placeholders = array(
            '{customer_name}' => esc_html($customer_name),
            '{store_name}' => esc_html($store_name),
            '{cart_total}' => wc_price($cart->cart_total),
            '{cart_url}' => esc_url($cart_url),
        );

        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
    }

    /**
     * Build HTML email template
     */
    private function build_email_html($heading, $message, $cart)
    {
        $cart_url = wc_get_cart_url();
        $cart_items = json_decode($cart->cart_content, true);

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($heading); ?></title>
        </head>

        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f7f7f7;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f7f7f7; padding: 20px 0;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0"
                            style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <!-- Header -->
                            <tr>
                                <td style="background-color: #2271b1; padding: 30px 40px; text-align: center;">
                                    <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">
                                        <?php echo esc_html($heading); ?>
                                    </h1>
                                </td>
                            </tr>

                            <!-- Message -->
                            <tr>
                                <td style="padding: 40px;">
                                    <p style="margin: 0 0 20px 0; color: #333; font-size: 16px; line-height: 1.6;">
                                        <?php echo nl2br(esc_html($message)); ?>
                                    </p>

                                    <!-- Cart Items -->
                                    <?php if (!empty($cart_items) && is_array($cart_items)): ?>
                                        <h2
                                            style="margin: 30px 0 20px 0; color: #2271b1; font-size: 18px; border-bottom: 2px solid #2271b1; padding-bottom: 10px;">
                                            Your Cart Items
                                        </h2>
                                        <table width="100%" cellpadding="10" cellspacing="0"
                                            style="border: 1px solid #e0e0e0; border-radius: 4px;">
                                            <?php foreach ($cart_items as $item): ?>
                                                <tr style="border-bottom: 1px solid #f0f0f0;">
                                                    <td style="color: #333; font-size: 14px;">
                                                        <strong><?php echo esc_html($item['product_name']); ?></strong>
                                                        <br>
                                                        <small style="color: #666;">Quantity:
                                                            <?php echo esc_html($item['quantity']); ?></small>
                                                    </td>
                                                    <td style="text-align: right; color: #2271b1; font-weight: 600; font-size: 14px;">
                                                        <?php echo wc_price($item['line_total']); ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr style="background-color: #f9f9f9;">
                                                <td style="padding: 15px 10px; font-weight: 600; color: #333; font-size: 16px;">
                                                    Total:
                                                </td>
                                                <td
                                                    style="padding: 15px 10px; text-align: right; font-weight: 700; color: #2271b1; font-size: 18px;">
                                                    <?php echo wc_price($cart->cart_total); ?>
                                                </td>
                                            </tr>
                                        </table>
                                    <?php endif; ?>

                                    <!-- CTA Button -->
                                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 30px;">
                                        <tr>
                                            <td align="center">
                                                <a href="<?php echo esc_url($cart_url); ?>"
                                                    style="display: inline-block; padding: 15px 40px; background-color: #2271b1; color: #ffffff; text-decoration: none; border-radius: 4px; font-size: 16px; font-weight: 600;">
                                                    Complete Your Purchase
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td
                                    style="background-color: #f9f9f9; padding: 20px 40px; text-align: center; border-top: 1px solid #e0e0e0;">
                                    <p style="margin: 0; color: #666; font-size: 12px;">
                                        &copy; <?php echo date('Y'); ?>         <?php echo esc_html(get_bloginfo('name')); ?>. All
                                        rights reserved.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>

        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Log sent email
     */
    private function log_email_sent($cart_id, $email)
    {
        global $wpdb;
        $log_table = $wpdb->prefix . 'wcat_abandoned_emails';

        // Create log table if it doesn't exist
        $this->create_log_table();

        $wpdb->insert(
            $log_table,
            array(
                'cart_id' => $cart_id,
                'recipient_email' => $email,
                'sent_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s')
        );
    }

    /**
     * Create email log table
     */
    private function create_log_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wcat_abandoned_emails';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            cart_id BIGINT(20) UNSIGNED NOT NULL,
            recipient_email VARCHAR(100) NOT NULL,
            sent_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            INDEX idx_cart_id (cart_id),
            INDEX idx_sent_at (sent_at)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Handle email settings save
     */
    public function handle_email_settings()
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        if (!isset($_POST['wcat_abandoned_email_action'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['wcat_email_nonce'], 'wcat_email_settings')) {
            wp_die(__('Security check failed', 'wc-all-cart-tracker'));
        }

        $action = sanitize_text_field($_POST['wcat_abandoned_email_action']);

        if ($action === 'save_settings') {
            $this->save_email_settings();

            // Redirect to avoid form resubmission
            wp_safe_redirect(add_query_arg('settings-updated', 'true', admin_url('admin.php?page=wc-cart-abandoned-emails')));
            exit;
        }
    }

    /**
     * AJAX: Test email handler
     */
    public function ajax_test_email()
    {
        error_log('=== AJAX TEST ABANDONED EMAIL CALLED ===');

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wcat_abandoned_email_test')) {
            error_log('ERROR: Nonce verification failed');
            wp_send_json_error(array('message' => __('Security check failed', 'wc-all-cart-tracker')));
            return;
        }

        if (!current_user_can('manage_woocommerce')) {
            error_log('ERROR: User lacks permissions');
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wc-all-cart-tracker')));
            return;
        }

        $test_email = isset($_POST['test_email']) ? sanitize_email($_POST['test_email']) : '';

        if (!is_email($test_email)) {
            wp_send_json_error(array('message' => __('Invalid email address', 'wc-all-cart-tracker')));
            return;
        }

        error_log('Sending test email to: ' . $test_email);

        // Create fake cart data for testing
        $fake_cart = (object) array(
            'customer_name' => 'Test Customer',
            'customer_email' => $test_email,
            'cart_total' => 99.99,
            'cart_content' => wp_json_encode(array(
                array(
                    'product_name' => 'Test Product 1',
                    'quantity' => 2,
                    'line_total' => 59.98
                ),
                array(
                    'product_name' => 'Test Product 2',
                    'quantity' => 1,
                    'line_total' => 39.99
                ),
            ))
        );

        $result = $this->send_single_abandoned_email($fake_cart);

        error_log('Email send result: ' . ($result ? 'SUCCESS' : 'FAILED'));

        if ($result) {
            wp_send_json_success(array(
                'message' => sprintf(__('Test email sent successfully to %s', 'wc-all-cart-tracker'), $test_email)
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to send test email. Check debug.log for details.', 'wc-all-cart-tracker')
            ));
        }
    }

    /**
     * Save email settings
     */
    private function save_email_settings()
    {
        update_option('wcat_abandoned_email_enabled', sanitize_text_field($_POST['enabled']));
        update_option('wcat_abandoned_email_wait_time', absint($_POST['wait_time']));
        update_option('wcat_abandoned_email_guests', sanitize_text_field($_POST['send_to_guests']));
        update_option('wcat_abandoned_email_subject', sanitize_text_field($_POST['email_subject']));
        update_option('wcat_abandoned_email_heading', sanitize_text_field($_POST['email_heading']));
        update_option('wcat_abandoned_email_message', sanitize_textarea_field($_POST['email_message']));

        add_settings_error(
            'wcat_abandoned_emails',
            'settings_saved',
            __('Settings saved successfully!', 'wc-all-cart-tracker'),
            'success'
        );

        // Reschedule cron if needed
        wp_clear_scheduled_hook('wcat_send_abandoned_cart_emails');
        $this->schedule_email_cron();
    }

    /**
     * Get email statistics
     */
    public static function get_email_stats()
    {
        global $wpdb;
        $log_table = $wpdb->prefix . 'wcat_abandoned_emails';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$log_table}'");

        if (!$table_exists) {
            return array(
                'total_sent' => 0,
                'sent_today' => 0,
                'sent_week' => 0,
                'sent_month' => 0,
            );
        }

        return array(
            'total_sent' => $wpdb->get_var("SELECT COUNT(*) FROM {$log_table}"),
            'sent_today' => $wpdb->get_var("SELECT COUNT(*) FROM {$log_table} WHERE DATE(sent_at) = CURDATE()"),
            'sent_week' => $wpdb->get_var("SELECT COUNT(*) FROM {$log_table} WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
            'sent_month' => $wpdb->get_var("SELECT COUNT(*) FROM {$log_table} WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"),
        );
    }
}

// Initialize
function wc_cart_tracker_init_abandoned_email()
{
    return WC_Cart_Tracker_Abandoned_Email::get_instance();
}
add_action('plugins_loaded', 'wc_cart_tracker_init_abandoned_email');