<?php
function render_admin_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . self::TABLE_NAME;

    // Get date range filter
    $days = isset($_GET['days']) ? absint($_GET['days']) : 30;
    if (!in_array($days, array(7, 30, 60, 90))) {
        $days = 30;
    }

    // Get analytics data
    $analytics = get_analytics_data($days);

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

            <!-- Key Metrics Cards -->
            <div class="wc-cart-metrics"
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <!-- Conversion Rate -->
                <div class="metric-card"
                    style="background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #2271b1; padding: 15px; border-radius: 4px;">
                    <div style="font-size: 13px; color: #646970; margin-bottom: 5px;">
                        <?php echo esc_html__('Conversion Rate', 'wc-all-cart-tracker'); ?>
                    </div>
                    <div style="font-size: 28px; font-weight: 600; color: #2271b1;">
                        <?php echo esc_html($analytics['conversion_rate']); ?>%
                    </div>
                    <div style="font-size: 12px; color: #646970; margin-top: 5px;">
                        <?php echo esc_html($analytics['converted_carts']); ?> /
                        <?php echo esc_html($analytics['total_carts']); ?>
                        <?php echo esc_html__('carts', 'wc-all-cart-tracker'); ?>
                    </div>
                </div>

                <!-- Active Carts -->
                <div class="metric-card"
                    style="background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #00a32a; padding: 15px; border-radius: 4px;">
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

                <!-- Abandoned Carts -->
                <div class="metric-card"
                    style="background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #d63638; padding: 15px; border-radius: 4px;">
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

                <!-- Deleted Carts -->
                <div class="metric-card"
                    style="background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #f0b849; padding: 15px; border-radius: 4px;">
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

                <!-- Revenue Potential -->
                <div class="metric-card"
                    style="background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #7e3bd0; padding: 15px; border-radius: 4px;">
                    <div style="font-size: 13px; color: #646970; margin-bottom: 5px;">
                        <?php echo esc_html__('Revenue Potential', 'wc-all-cart-tracker'); ?>
                    </div>
                    <div style="font-size: 28px; font-weight: 600; color: #7e3bd0;">
                        <?php echo wc_price($analytics['revenue_potential']); ?>
                    </div>
                    <div style="font-size: 12px; color: #646970; margin-top: 5px;">
                        <?php echo esc_html__('Active carts total', 'wc-all-cart-tracker'); ?>
                    </div>
                </div>
            </div>

            <!-- Additional Metrics -->
            <div class="wc-cart-metrics-detailed"
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                <!-- Average Cart Values -->
                <div class="metric-card"
                    style="background: #fff; border: 1px solid #c3c4c7; padding: 15px; border-radius: 4px;">
                    <h3 style="margin: 0 0 15px 0; font-size: 14px;">
                        <?php echo esc_html__('Average Cart Value', 'wc-all-cart-tracker'); ?>
                    </h3>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span
                            style="color: #646970;"><?php echo esc_html__('Active Carts:', 'wc-all-cart-tracker'); ?></span>
                        <strong><?php echo wc_price($analytics['avg_active_cart']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span
                            style="color: #646970;"><?php echo esc_html__('Converted Carts:', 'wc-all-cart-tracker'); ?></span>
                        <strong><?php echo wc_price($analytics['avg_converted_cart']); ?></strong>
                    </div>
                </div>

                <!-- Customer Type Breakdown -->
                <div class="metric-card"
                    style="background: #fff; border: 1px solid #c3c4c7; padding: 15px; border-radius: 4px;">
                    <h3 style="margin: 0 0 15px 0; font-size: 14px;">
                        <?php echo esc_html__('By Customer Type', 'wc-all-cart-tracker'); ?>
                    </h3>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span
                            style="color: #646970;"><?php echo esc_html__('Registered Users:', 'wc-all-cart-tracker'); ?></span>
                        <strong><?php echo esc_html($analytics['registered_carts']); ?>
                            (<?php echo esc_html($analytics['registered_conversion_rate']); ?>%)</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span
                            style="color: #646970;"><?php echo esc_html__('Guest Users:', 'wc-all-cart-tracker'); ?></span>
                        <strong><?php echo esc_html($analytics['guest_carts']); ?>
                            (<?php echo esc_html($analytics['guest_conversion_rate']); ?>%)</strong>
                    </div>
                </div>

                <!-- Summary Stats -->
                <div class="metric-card"
                    style="background: #fff; border: 1px solid #c3c4c7; padding: 15px; border-radius: 4px;">
                    <h3 style="margin: 0 0 15px 0; font-size: 14px;">
                        <?php echo esc_html__('Summary', 'wc-all-cart-tracker'); ?>
                    </h3>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span
                            style="color: #646970;"><?php echo esc_html__('Total Carts:', 'wc-all-cart-tracker'); ?></span>
                        <strong><?php echo esc_html($analytics['total_carts']); ?></strong>
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
                        class="sortable <?php echo $orderby === 'last_updated' ? 'sorted' : ''; ?> <?php echo strtolower($order); ?>">
                        <a
                            href="<?php echo esc_url(add_query_arg(array('orderby' => 'last_updated', 'order' => $orderby === 'last_updated' && $order === 'DESC' ? 'ASC' : 'DESC'))); ?>">
                            <?php echo esc_html__('Last Updated', 'wc-all-cart-tracker'); ?>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th
                        class="sortable <?php echo $orderby === 'customer_email' ? 'sorted' : ''; ?> <?php echo strtolower($order); ?>">
                        <a
                            href="<?php echo esc_url(add_query_arg(array('orderby' => 'customer_email', 'order' => $orderby === 'customer_email' && $order === 'DESC' ? 'ASC' : 'DESC'))); ?>">
                            <?php echo esc_html__('Customer', 'wc-all-cart-tracker'); ?>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th
                        class="sortable <?php echo $orderby === 'past_purchases' ? 'sorted' : ''; ?> <?php echo strtolower($order); ?>">
                        <a
                            href="<?php echo esc_url(add_query_arg(array('orderby' => 'past_purchases', 'order' => $orderby === 'past_purchases' && $order === 'DESC' ? 'ASC' : 'DESC'))); ?>">
                            <?php echo esc_html__('Past Purchases', 'wc-all-cart-tracker'); ?>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th
                        class="sortable <?php echo $orderby === 'cart_total' ? 'sorted' : ''; ?> <?php echo strtolower($order); ?>">
                        <a
                            href="<?php echo esc_url(add_query_arg(array('orderby' => 'cart_total', 'order' => $orderby === 'cart_total' && $order === 'DESC' ? 'ASC' : 'DESC'))); ?>">
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
            .wp-list-table th {
                white-space: nowrap;
            }

            .wp-list-table td {
                vertical-align: top;
                padding: 12px 10px;
            }

            .wp-list-table td ul {
                margin: 0;
            }

            .wp-list-table .sortable a {
                text-decoration: none;
                color: inherit;
            }

            .wp-list-table .sorted a {
                color: #2271b1;
            }
        </style>
    </div>
    <?php
}
?>