<?php
/**
 * View: Recommendations Card
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="card wc-cart-recommendations-card">
    <h2><?php echo esc_html__('Recommendations', 'wc-all-cart-tracker'); ?></h2>
    <ul style="line-height: 1.8;">
        <li>
            <strong><?php echo esc_html__('For stores with 10,000+ carts:', 'wc-all-cart-tracker'); ?></strong>
            <?php echo esc_html__('Use "Archive" method and run cleanup monthly', 'wc-all-cart-tracker'); ?>
        </li>
        <li>
            <strong><?php echo esc_html__('For stores with 50,000+ carts:', 'wc-all-cart-tracker'); ?></strong>
            <?php echo esc_html__('Archive old carts after 60 days, optimize table weekly', 'wc-all-cart-tracker'); ?>
        </li>
        <li>
            <strong><?php echo esc_html__('Archive vs Delete:', 'wc-all-cart-tracker'); ?></strong>
            <?php echo esc_html__('Always use Archive unless absolutely necessary. Archives can be restored or analyzed later.', 'wc-all-cart-tracker'); ?>
        </li>
        <li>
            <strong><?php echo esc_html__('Performance tip:', 'wc-all-cart-tracker'); ?></strong>
            <?php echo esc_html__('Keeping main table under 50,000 records ensures optimal query performance', 'wc-all-cart-tracker'); ?>
        </li>
        <li>
            <strong><?php echo esc_html__('Backup reminder:', 'wc-all-cart-tracker'); ?></strong>
            <?php echo esc_html__('Always backup your database before running permanent deletions', 'wc-all-cart-tracker'); ?>
        </li>
    </ul>
</div>