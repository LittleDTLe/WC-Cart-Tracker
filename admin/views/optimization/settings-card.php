<?php
/**
 * View: Automated Cleanup Settings Card
 * Variables available: $settings
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="card wc-cart-settings-card">
    <h2><?php echo esc_html__('Automated Cleanup Settings', 'wc-all-cart-tracker'); ?></h2>
    <form method="post" action="">
        <?php wp_nonce_field('wc_cart_optimize'); ?>
        <input type="hidden" name="optimize_action" value="save_settings">

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="cleanup_enabled">
                            <?php echo esc_html__('Enable Automatic Cleanup:', 'wc-all-cart-tracker'); ?>
                        </label>
                    </th>
                    <td>
                        <select name="cleanup_enabled" id="cleanup_enabled">
                            <option value="yes" <?php selected($settings['cleanup_enabled'], 'yes'); ?>>
                                <?php echo esc_html__('Yes', 'wc-all-cart-tracker'); ?>
                            </option>
                            <option value="no" <?php selected($settings['cleanup_enabled'], 'no'); ?>>
                                <?php echo esc_html__('No', 'wc-all-cart-tracker'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php echo esc_html__('Runs daily via WordPress cron', 'wc-all-cart-tracker'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="cleanup_days">
                            <?php echo esc_html__('Cleanup After (days):', 'wc-all-cart-tracker'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="cleanup_days" id="cleanup_days"
                            value="<?php echo esc_attr($settings['cleanup_days']); ?>" min="30" max="365"
                            style="width: 100px;">
                        <p class="description">
                            <?php echo esc_html__('Clean up inactive carts older than this many days', 'wc-all-cart-tracker'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="cleanup_method">
                            <?php echo esc_html__('Cleanup Method:', 'wc-all-cart-tracker'); ?>
                        </label>
                    </th>
                    <td>
                        <select name="cleanup_method" id="cleanup_method">
                            <option value="archive" <?php selected($settings['cleanup_method'], 'archive'); ?>>
                                <?php echo esc_html__('Archive (Safe - Recommended)', 'wc-all-cart-tracker'); ?>
                            </option>
                            <option value="delete" <?php selected($settings['cleanup_method'], 'delete'); ?>>
                                <?php echo esc_html__('Delete Permanently (Cannot be undone!)', 'wc-all-cart-tracker'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <strong><?php echo esc_html__('Archive:', 'wc-all-cart-tracker'); ?></strong>
                            <?php echo esc_html__('Moves old carts to separate archive table (can be restored)', 'wc-all-cart-tracker'); ?>
                            <br>
                            <strong style="color: #d63638;">
                                <?php echo esc_html__('Delete:', 'wc-all-cart-tracker'); ?>
                            </strong>
                            <?php echo esc_html__('Permanently removes old carts (cannot be recovered)', 'wc-all-cart-tracker'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="purge_archives">
                            <?php echo esc_html__('Purge Old Archives:', 'wc-all-cart-tracker'); ?>
                        </label>
                    </th>
                    <td>
                        <select name="purge_archives" id="purge_archives">
                            <option value="yes" <?php selected($settings['purge_archives'], 'yes'); ?>>
                                <?php echo esc_html__('Yes (1+ year old)', 'wc-all-cart-tracker'); ?>
                            </option>
                            <option value="no" <?php selected($settings['purge_archives'], 'no'); ?>>
                                <?php echo esc_html__('No (Keep all archives)', 'wc-all-cart-tracker'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php echo esc_html__('Permanently delete archived carts older than 1 year', 'wc-all-cart-tracker'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary">
                <?php echo esc_html__('Save Settings', 'wc-all-cart-tracker'); ?>
            </button>
        </p>
    </form>
</div>