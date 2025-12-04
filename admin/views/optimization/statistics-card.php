<?php
/**
 * View: Database Statistics Card
 * Variables available: $stats
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="card wc-cart-stats-card">
    <h2><?php echo esc_html__('Database Statistics', 'wc-all-cart-tracker'); ?></h2>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><?php echo esc_html__('Total Carts (Main Table):', 'wc-all-cart-tracker'); ?></th>
                <td><strong><?php echo number_format($stats['total_rows']); ?></strong></td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Active Carts:', 'wc-all-cart-tracker'); ?></th>
                <td>
                    <span style="color: #00a32a;">
                        <strong><?php echo number_format($stats['active_rows']); ?></strong>
                    </span>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Inactive Carts:', 'wc-all-cart-tracker'); ?></th>
                <td>
                    <span style="color: #646970;">
                        <strong><?php echo number_format($stats['inactive_rows']); ?></strong>
                    </span>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Main Table Size:', 'wc-all-cart-tracker'); ?></th>
                <td><strong><?php echo esc_html($stats['table_size']); ?> MB</strong></td>
            </tr>

            <?php if ($stats['archive_exists']): ?>
                <tr>
                    <th scope="row"><?php echo esc_html__('Archived Carts:', 'wc-all-cart-tracker'); ?></th>
                    <td>
                        <span style="color: #f0b849;">
                            <strong><?php echo number_format($stats['archive_rows']); ?></strong>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Archive Table Size:', 'wc-all-cart-tracker'); ?></th>
                    <td><strong><?php echo esc_html($stats['archive_size']); ?> MB</strong></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>