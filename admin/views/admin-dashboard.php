<?php
/**
 * View: Active Carts Dashboard
 *
 * This file is included by WC_Cart_Tracker_Admin::render_dashboard_page()
 *
 * @package WC_All_Cart_Tracker
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = WC_Cart_Tracker_Database::get_table_name();

// --- Data Retrieval ---
$days = isset($_GET['days']) ? absint($_GET['days']) : 30;
if (!in_array($days, array(7, 30, 60, 90))) {
    $days = 30;
}

// Get analytics data using the dedicated class
$analytics = WC_Cart_Tracker_Analytics::get_analytics_data($days);

// --- Distribution Calculation (Needed for By Customer Type Card) ---
$total_carts_by_type = $analytics['registered_carts'] + $analytics['guest_carts'];
$registered_distribution = $total_carts_by_type > 0 ? round(($analytics['registered_carts'] / $total_carts_by_type) * 100, 2) : 0;
$guest_distribution = $total_carts_by_type > 0 ? round(($analytics['guest_carts'] / $total_carts_by_type) * 100, 2) : 0;

// Sorting parameters for the active cart list
$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'last_updated';
$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

$allowed_orderby = array('last_updated', 'customer_email', 'past_purchases', 'cart_total');
if (!in_array($orderby, $allowed_orderby)) {
    $orderby = 'last_updated';
}
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Get active carts data
$carts = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE is_active = %d ORDER BY {$orderby} {$order}",
    1
));
// --- End Data Retrieval ---

?>
<div class="wrap">
    <h1><?php echo esc_html__('Active Carts', 'wc-all-cart-tracker'); ?></h1>

    <div class="wc-cart-analytics-dashboard">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h2 style="margin: 0;"><?php echo esc_html__('Analytics Overview', 'wc-all-cart-tracker'); ?></h2>
            <div>
                <label for="days-filter"><?php echo esc_html__('Time Period:', 'wc-all-cart-tracker'); ?></label>
                <select id="days-filter"
                    onchange="window.location.href='<?php echo esc_url(admin_url('admin.php?page=wc-all-cart-tracker')); ?>&days=' + this.value">
                    <option value="7" <?php selected($days, 7); ?>>
                        <?php echo esc_html__('Last 7 Days', 'wc-all-cart-tracker'); ?>
                    </option>
                    <option value="30" <?php selected($days, 30); ?>>
                        <?php echo esc_html__('Last 30 Days', 'wc-all-cart-tracker'); ?>
                    </option>
                    <option value="60" <?php selected($days, 60); ?>>
                        <?php echo esc_html__('Last 60 Days', 'wc-all-cart-tracker'); ?>
                    </option>
                    <option value="90" <?php selected($days, 90); ?>>
                        <?php echo esc_html__('Last 90 Days', 'wc-all-cart-tracker'); ?>
                    </option>
                </select>
            </div>
        </div>

        <div class="wc-cart-metrics">
            <div class="metric-card" style="border-left: 4px solid #2271b1;">
                <div style="font-size: 13px; color: #646970; margin-bottom: 5px;">
                    <?php echo esc_html__('Conversion Rate', 'wc-all-cart-tracker'); ?>
                </div>
                <div style="font-size: 28px; font-weight: 600; color: #2271b1;">
                    <?php echo esc_html($analytics['conversion_rate']); ?>%
                </div>
                <div style="font-size: 12px; color: #646970; margin-top: 5px;">
                    <?php echo esc_html($analytics['converted_carts']); ?> /
                    <?php echo esc_html($analytics['total_carts']); ?>
                    <?php echo esc_html__('carts tracked', 'wc-all-cart-tracker'); ?>
                </div>
            </div>

            <div class="metric-card" style="border-left: 4px solid #00a32a;">
                <div style="font-size: 13px; color: #646970; margin-bottom: 5px;">
                    <?php echo esc_html__('Active Carts', 'wc-all-cart-tracker'); ?>
                </div>
                <div style="font-size: 28px; font-weight: 600; color: #00a32a;">
                    <?php echo esc_html($analytics['active_carts']); ?>
                </div>
                <div style="font-size: 12px; color: #646970; margin-top: 5px;">
                    <?php echo esc_html__('Currently in cart', 'wc-all-cart-tracker'); ?>
                </div>
            </div>

            <div class="metric-card" style="border-left: 4px solid #d63638;">
                <div style="font-size: 13px; color: #646970; margin-bottom: 5px;">
                    <?php echo esc_html__('Abandoned Carts', 'wc-all-cart-tracker'); ?>
                </div>
                <div style="font-size: 28px; font-weight: 600; color: #d63638;">
                    <?php echo esc_html($analytics['abandoned_carts']); ?>
                </div>
                <div style="font-size: 12px; color: #646970; margin-top: 5px;">
                    <?php echo esc_html__('Inactive > 24hrs', 'wc-all-cart-tracker'); ?>
                </div>
            </div>

            <div class="metric-card" style="border-left: 4px solid #f0b849;">
                <div style="font-size: 13px; color: #646970; margin-bottom: 5px;">
                    <?php echo esc_html__('Deleted Carts', 'wc-all-cart-tracker'); ?>
                </div>
                <div style="font-size: 28px; font-weight: 600; color: #f0b849;">
                    <?php echo esc_html($analytics['deleted_carts']); ?>
                </div>
                <div style="font-size: 12px; color: #646970; margin-top: 5px;">
                    <?php echo esc_html__('Cleared by user', 'wc-all-cart-tracker'); ?>
                </div>
            </div>

            <div class="metric-card" style="border-left: 4px solid #7e3bd0;">
                <div style="font-size: 13px; color: #646970; margin-bottom: 5px;">
                    <?php echo esc_html__('Revenue Potential', 'wc-all-cart-tracker'); ?>
                </div>
                <div style="font-size: 28px; font-weight: 600; color: #7e3bd0;">
                    <?php echo wc_price($analytics['revenue_potential']); ?>
                </div>
                <div style="font-size: 12px; color: #646970; margin-top: 5px;">
                    <?php echo esc_html__('Total of active carts', 'wc-all-cart-tracker'); ?>
                </div>
            </div>
        </div>

        <div class="wc-cart-metrics-detailed">
            <div class="metric-card">
                <h3 style="margin: 0 0 10px 0; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 5px;">
                    <?php echo esc_html__('Average Cart Value', 'wc-all-cart-tracker'); ?>
                </h3>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span
                        style="color: #646970;"><?php echo esc_html__('Active Carts:', 'wc-all-cart-tracker'); ?></span>
                    <strong><?php echo wc_price($analytics['avg_active_cart']); ?></strong>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span
                        style="color: #646970;"><?php echo esc_html__('Converted Carts:', 'wc-all-cart-tracker'); ?></span>
                    <strong><?php echo wc_price($analytics['avg_converted_cart']); ?></strong>
                </div>

                <h4
                    style="margin: 10px 0 5px 0; font-size: 13px; border-bottom: 1px solid #eee; padding-bottom: 5px; border-top: 1px solid #eee; padding-top: 10px; color: #2271b1;">
                    <?php echo esc_html__('Total Carts Value', 'wc-all-cart-tracker'); ?>
                </h4>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span
                        style="color: #646970;"><?php echo esc_html__('Highest Potential:', 'wc-all-cart-tracker'); ?></span>
                    <strong>
                        <?php
                        // Fallback if MAX(cart_total) isn't explicitly calculated in analytics class
                        $max_cart_total = $wpdb->get_var("SELECT MAX(cart_total) FROM {$table_name} WHERE is_active = 1");
                        echo wc_price($max_cart_total ? $max_cart_total : 0);
                        ?>
                    </strong>
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <span
                        style="color: #646970;"><?php echo esc_html__('Revenue Potential:', 'wc-all-cart-tracker'); ?></span>
                    <strong><?php echo wc_price($analytics['revenue_potential']); ?></strong>
                </div>
            </div>
            <div class="metric-card">
                <h3 style="margin: 0 0 10px 0; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 5px;">
                    <?php echo esc_html__('By Customer Type', 'wc-all-cart-tracker'); ?>
                </h3>

                <h4
                    style="margin: 10px 0 5px 0; font-size: 13px; border-bottom: 1px solid #eee; padding-bottom: 5px; color: #2271b1;">
                    <?php echo esc_html__('Cart Distribution', 'wc-all-cart-tracker'); ?>
                </h4>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span
                        style="color: #646970;"><?php echo esc_html__('Registered Users:', 'wc-all-cart-tracker'); ?></span>
                    <strong>
                        <?php echo esc_html($analytics['registered_carts']); ?>
                        (<?php echo esc_html($registered_distribution); ?>%)
                    </strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span
                        style="color: #646970;"><?php echo esc_html__('Guest Users:', 'wc-all-cart-tracker'); ?></span>
                    <strong>
                        <?php echo esc_html($analytics['guest_carts']); ?>
                        (<?php echo esc_html($guest_distribution); ?>%)
                    </strong>
                </div>

                <h4
                    style="margin: 10px 0 5px 0; font-size: 13px; border-bottom: 1px solid #eee; padding-bottom: 5px; border-top: 1px solid #eee; padding-top: 10px; color: #2271b1;">
                    <?php echo esc_html__('Conversion Rates', 'wc-all-cart-tracker'); ?>
                </h4>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span
                        style="color: #646970;"><?php echo esc_html__('Registered CR:', 'wc-all-cart-tracker'); ?></span>
                    <strong
                        style="color: <?php echo $analytics['registered_conversion_rate'] > 0 ? '#00a32a' : '#d63638'; ?>;">
                        <?php echo esc_html($analytics['registered_conversion_rate']); ?>%
                    </strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #646970;"><?php echo esc_html__('Guest CR:', 'wc-all-cart-tracker'); ?></span>
                    <strong
                        style="color: <?php echo $analytics['guest_conversion_rate'] > 0 ? '#00a32a' : '#d63638'; ?>;">
                        <?php echo esc_html($analytics['guest_conversion_rate']); ?>%
                    </strong>
                </div>
            </div>

            <div class="metric-card">
                <h3 style="margin: 0 0 10px 0; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 5px;">
                    <?php echo esc_html__('Cart Summary', 'wc-all-cart-tracker'); ?>
                </h3>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span
                        style="color: #646970;"><?php echo esc_html__('Total Carts Tracked:', 'wc-all-cart-tracker'); ?></span>
                    <strong><?php echo esc_html($analytics['total_carts']); ?></strong>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span
                        style="color: #646970;"><?php echo esc_html__('Converted to Order:', 'wc-all-cart-tracker'); ?></span>
                    <strong style="color: #00a32a;"><?php echo esc_html($analytics['converted_carts']); ?></strong>
                </div>

                <h4
                    style="margin: 10px 0 5px 0; font-size: 13px; border-bottom: 1px solid #eee; padding-bottom: 5px; border-top: 1px solid #eee; padding-top: 10px; color: #2271b1;">
                    <?php echo esc_html__('Rate Overview', 'wc-all-cart-tracker'); ?>
                </h4>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #646970;"><?php echo esc_html__('Overall CR:', 'wc-all-cart-tracker'); ?></span>
                    <strong style="color: #2271b1;"><?php echo esc_html($analytics['conversion_rate']); ?>%</strong>
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <span
                        style="color: #646970;"><?php echo esc_html__('Abandonment Rate:', 'wc-all-cart-tracker'); ?></span>
                    <strong style="color: #d63638;"><?php echo esc_html($analytics['abandonment_rate']); ?>%</strong>
                </div>
            </div>
        </div>
    </div>

    <hr style="margin: 30px 0;">

    <h2><?php echo esc_html__('Active Carts', 'wc-all-cart-tracker'); ?></h2>

    <div class="tablenav top">
        <div class="alignleft actions">
            <span
                class="displaying-num"><?php printf(esc_html__('%d active carts', 'wc-all-cart-tracker'), count($carts)); ?></span>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('ID', 'wc-all-cart-tracker'); ?></th>
                <th
                    class="sortable <?php echo $orderby === 'last_updated' ? 'sorted' : ''; ?> <?php echo strtolower($order) === 'asc' ? 'asc' : 'desc'; ?>">
                    <a
                        href="<?php echo esc_url(add_query_arg(array('orderby' => 'last_updated', 'order' => $orderby === 'last_updated' && $order === 'DESC' ? 'ASC' : 'DESC', 'days' => $days, 'paged' => false))); ?>">
                        <?php echo esc_html__('Last Updated', 'wc-all-cart-tracker'); ?>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th
                    class="sortable <?php echo $orderby === 'customer_email' ? 'sorted' : ''; ?> <?php echo strtolower($order) === 'asc' ? 'asc' : 'desc'; ?>">
                    <a
                        href="<?php echo esc_url(add_query_arg(array('orderby' => 'customer_email', 'order' => $orderby === 'customer_email' && $order === 'DESC' ? 'ASC' : 'DESC', 'days' => $days, 'paged' => false))); ?>">
                        <?php echo esc_html__('Customer', 'wc-all-cart-tracker'); ?>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th
                    class="sortable <?php echo $orderby === 'past_purchases' ? 'sorted' : ''; ?> <?php echo strtolower($order) === 'asc' ? 'asc' : 'desc'; ?>">
                    <a
                        href="<?php echo esc_url(add_query_arg(array('orderby' => 'past_purchases', 'order' => $orderby === 'past_purchases' && $order === 'DESC' ? 'ASC' : 'DESC', 'days' => $days, 'paged' => false))); ?>">
                        <?php echo esc_html__('Past Purchases', 'wc-all-cart-tracker'); ?>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th
                    class="sortable <?php echo $orderby === 'cart_total' ? 'sorted' : ''; ?> <?php echo strtolower($order) === 'asc' ? 'asc' : 'desc'; ?>">
                    <a
                        href="<?php echo esc_url(add_query_arg(array('orderby' => 'cart_total', 'order' => $orderby === 'cart_total' && $order === 'DESC' ? 'ASC' : 'DESC', 'days' => $days, 'paged' => false))); ?>">
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
                                echo '<ul style="padding-left: 20px;">';
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
</div>