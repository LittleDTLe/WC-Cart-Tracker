<?php
/**
 * View: Manual Optimization Tools Card
 * Variables available: $stats
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="card wc-cart-tools-card">
    <h2><?php echo esc_html__('Manual Optimization Tools', 'wc-all-cart-tracker'); ?></h2>

    <!-- Performance Optimization Section -->
    <h3><?php echo esc_html__('Performance Optimization', 'wc-all-cart-tracker'); ?></h3>
    <form method="post" action="" style="margin-bottom: 30px;">
        <?php wp_nonce_field('wc_cart_optimize'); ?>

        <p>
            <button type="submit" name="optimize_action" value="optimize_table" class="button button-primary">
                <?php echo esc_html__('Optimize Table', 'wc-all-cart-tracker'); ?>
            </button>
            <span class="description">
                <?php echo esc_html__('Defragment and optimize the database table for better performance.', 'wc-all-cart-tracker'); ?>
            </span>
        </p>

        <p>
            <button type="submit" name="optimize_action" value="rebuild_indexes" class="button button-primary">
                <?php echo esc_html__('Rebuild Indexes', 'wc-all-cart-tracker'); ?>
            </button>
            <span class="description">
                <?php echo esc_html__('Recreate all database indexes for better query performance.', 'wc-all-cart-tracker'); ?>
            </span>
        </p>

        <p>
            <button type="submit" name="optimize_action" value="clear_cache" class="button button-secondary">
                <?php echo esc_html__('Clear All Caches', 'wc-all-cart-tracker'); ?>
            </button>
            <span class="description">
                <?php echo esc_html__('Clear all cached analytics and cart data (forces fresh queries).', 'wc-all-cart-tracker'); ?>
            </span>
        </p>
    </form>

    <hr>

    <!-- Data Management (Safe) Section -->
    <h3><?php echo esc_html__('Data Management (Safe)', 'wc-all-cart-tracker'); ?></h3>
    <form method="post" action="" style="margin-bottom: 30px;">
        <?php wp_nonce_field('wc_cart_optimize'); ?>

        <p>
            <label>
                <?php echo esc_html__('Cleanup carts older than:', 'wc-all-cart-tracker'); ?>
                <input type="number" name="cleanup_days" value="90" min="30" max="365" style="width: 80px;">
                <?php echo esc_html__('days', 'wc-all-cart-tracker'); ?>
            </label>
        </p>

        <p>
            <button type="submit" name="optimize_action" value="archive_old" class="button button-secondary">
                <?php echo esc_html__('Archive Old Carts', 'wc-all-cart-tracker'); ?>
            </button>
            <span class="description" style="color: #00a32a;">
                <?php echo esc_html__('✓ Safe: Moves to archive table (can be restored)', 'wc-all-cart-tracker'); ?>
            </span>
        </p>

        <?php if ($stats['archive_exists'] && $stats['archive_rows'] > 0): ?>
            <p>
                <button type="submit" name="optimize_action" value="restore_archived" class="button button-secondary">
                    <?php echo esc_html__('Restore Recently Archived (Last 7 Days)', 'wc-all-cart-tracker'); ?>
                </button>
                <span class="description">
                    <?php echo esc_html__('Restore recently archived carts back to main table.', 'wc-all-cart-tracker'); ?>
                </span>
            </p>
        <?php endif; ?>
    </form>

    <hr>

    <!-- Permanent Deletion Section -->
    <h3 style="color: #d63638;">
        <?php echo esc_html__('⚠️ Permanent Deletion (Cannot be undone!)', 'wc-all-cart-tracker'); ?>
    </h3>
    <form method="post" action="">
        <?php wp_nonce_field('wc_cart_optimize'); ?>

        <p>
            <label>
                <?php echo esc_html__('Cleanup carts older than:', 'wc-all-cart-tracker'); ?>
                <input type="number" name="cleanup_days" value="90" min="30" max="365" style="width: 80px;">
                <?php echo esc_html__('days', 'wc-all-cart-tracker'); ?>
            </label>
        </p>

        <p>
            <label>
                <input type="checkbox" name="confirm_delete" value="yes">
                <strong><?php echo esc_html__('I understand this will PERMANENTLY delete data', 'wc-all-cart-tracker'); ?></strong>
            </label>
        </p>

        <p>
            <button type="submit" name="optimize_action" value="delete_old" class="button"
                style="background: #d63638; border-color: #d63638; color: #fff;">
                <?php echo esc_html__('⚠️ Permanently Delete Old Carts', 'wc-all-cart-tracker'); ?>
            </button>
            <span class="description" style="color: #d63638;">
                <?php echo esc_html__('⚠️ WARNING: This cannot be undone!', 'wc-all-cart-tracker'); ?>
            </span>
        </p>

        <?php if ($stats['archive_exists'] && $stats['archive_rows'] > 0): ?>
            <p>
                <label>
                    <input type="checkbox" name="confirm_purge" value="yes">
                    <strong>
                        <?php echo esc_html__('I understand this will PERMANENTLY delete archived data older than 1 year', 'wc-all-cart-tracker'); ?>
                    </strong>
                </label>
            </p>

            <p>
                <button type="submit" name="optimize_action" value="purge_archive" class="button"
                    style="background: #d63638; border-color: #d63638; color: #fff;">
                    <?php echo esc_html__('⚠️ Purge Old Archives (1+ year)', 'wc-all-cart-tracker'); ?>
                </button>
            </p>
        <?php endif; ?>
    </form>
</div>