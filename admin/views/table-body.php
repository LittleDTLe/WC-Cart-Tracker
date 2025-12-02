<?php
/**
 * AJAX Template Fragment: Active Carts Table Body
 *
 * This file renders the <tbody> content for the active carts table.
 * Variables available: $carts
 *
 * @package WC_All_Cart_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

if (empty($carts)): ?>
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