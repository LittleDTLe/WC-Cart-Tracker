<?php if (!defined('ABSPATH'))
    exit; ?>

<style>
    /* ========================================================= */
    /*  Cart Tracker – Professional Responsive Card Grid        */
    /* ========================================================= */

    .wcat-optimization-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(620px, 1fr));
        gap: 24px;
        margin-top: 28px;
    }

    .wcat-full-width {
        grid-column: 1 / -1;
    }

    /* WordPress-Native Card Style */
    .wcat-card {
        background: #fff;
        border: 1px solid #c3c4c7;
        border-radius: 6px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .wcat-card h2 {
        margin: 0;
        padding: 16px 24px;
        font-size: 15px;
        font-weight: 600;
        background: #f6f7f7;
        border-bottom: 1px solid #c3c4c7;
        color: #1d2327;
    }

    .wcat-card .inside {
        padding: 20px 24px;
    }

    /* Form Tables – Clean spacing */
    .wcat-card table.form-table {
        margin: 0;
    }

    .wcat-card table.form-table th {
        width: 240px;
        padding: 12px 0;
    }

    .wcat-card table.form-table td {
        padding: 12px 0;
    }

    /* Buttons */
    .wcat-card .button {
        height: 36px;
        padding: 0 16px;
        border-radius: 4px;
    }

    /* Full-width buttons in tools */
    .wcat-card .button-block {
        display: flex;
        width: 100%;
        justify-content: flex-start;
        text-align: left;
    }

    /* Mobile Responsiveness */
    @media (max-width: 782px) {
        .wcat-optimization-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .wcat-card h2 {
            padding: 14px 20px;
            font-size: 14px;
        }

        .wcat-card .inside {
            padding: 18px 20px;
        }

        .wcat-card table.form-table th,
        .wcat-card table.form-table td {
            display: block;
            width: 100%;
        }

        .wcat-card table.form-table th {
            padding-bottom: 6px;
            font-weight: 600;
        }
    }
</style>

<div class="wrap wc-cart-optimization-page">
    <h1><?php esc_html_e('Cart Tracker Optimization & Maintenance', 'wc-all-cart-tracker'); ?></h1>

    <?php settings_errors('wc_cart_optimization'); ?>

    <div class="wcat-optimization-grid">

        <!-- Statistics Card -->
        <div class="wcat-grid-item">
            <?php require WC_CART_TRACKER_PLUGIN_DIR . 'admin/views/optimization/statistics-card.php'; ?>
        </div>

        <!-- Recommendations / Danger Zone -->
        <div class="wcat-grid-item">
            <?php require WC_CART_TRACKER_PLUGIN_DIR . 'admin/views/optimization/suggestions-card.php'; ?>
        </div>

        <!-- Settings Card -->
        <div class="wcat-grid-item">
            <?php require WC_CART_TRACKER_PLUGIN_DIR . 'admin/views/optimization/settings-card.php'; ?>
        </div>

        <!-- Tools Card -->
        <div class="wcat-grid-item">
            <?php require WC_CART_TRACKER_PLUGIN_DIR . 'admin/views/optimization/tools-card.php'; ?>
        </div>
    </div>
</div>