<?php

/**
 * View: Optimization Page (Main Container)
 * 
 * This is the main optimization page that includes all card components
 * Variables available: $stats, $settings
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap wc-cart-optimization-page">
    <h1><?php echo esc_html__('Cart Tracker Optimization & Maintenance', 'wc-all-cart-tracker'); ?></h1>

    <?php settings_errors('wc_cart_optimization'); ?>

    <?php
    // Include statistics card
    require_once WC_CART_TRACKER_PLUGIN_DIR . 'admin/views/optimization/statistics-card.php';

    // Include settings card
    require_once WC_CART_TRACKER_PLUGIN_DIR . 'admin/views/optimization/settings-card.php';

    // Include tools card
    require_once WC_CART_TRACKER_PLUGIN_DIR . 'admin/views/optimization/tools-card.php';

    // Include recommendations card
    require_once WC_CART_TRACKER_PLUGIN_DIR . 'admin/views/optimization/suggestions-card.php';
    ?>
</div>