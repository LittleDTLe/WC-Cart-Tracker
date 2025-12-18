<?php
/**
 * Admin View: Scheduled Exports Page
 * 
 * This is the complete admin interface for managing scheduled exports
 * 
 * @package WC_All_Cart_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get existing schedules
$schedules = WC_Cart_Tracker_Scheduled_Export::get_schedules();
$editing_schedule = null;
$edit_id = null;

// Check if editing an existing schedule
if (isset($_GET['edit'])) {
    $edit_id = sanitize_text_field($_GET['edit']);
    $editing_schedule = WC_Cart_Tracker_Scheduled_Export::get_schedule($edit_id);
}

// Get available columns for selection
$available_columns = WC_Cart_Tracker_Export_Templates::get_available_columns();
?>


<div class="wrap wcat-scheduled-exports-wrap">
    <h1 class="wp-heading-inline">
        <?php echo esc_html__('Scheduled Cart Exports', 'wc-all-cart-tracker'); ?>
    </h1>

    <?php if ($editing_schedule): ?>
        <a href="<?php echo admin_url('admin.php?page=wc-cart-scheduled-exports'); ?>" class="page-title-action">
            <?php esc_html_e('Add New Schedule', 'wc-all-cart-tracker'); ?>
        </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <?php settings_errors('wcat_scheduled_exports'); ?>

    <div class="wcat-scheduled-exports-container">

        <!-- Create/Edit Schedule Form -->
        <div class="postbox wcat-schedule-form-card">
            <div class="postbox-header">
                <h2 class="hndle">
                    <?php
                    if ($editing_schedule) {
                        echo esc_html__('Edit Schedule', 'wc-all-cart-tracker');
                    } else {
                        echo esc_html__('Create New Schedule', 'wc-all-cart-tracker');
                    }
                    ?>
                </h2>
            </div>
            <div class="inside">
                <form method="post" action="" class="wcat-schedule-form">
                    <?php wp_nonce_field('wcat_schedule_action', 'wcat_schedule_nonce'); ?>
                    <input type="hidden" name="wcat_schedule_action"
                        value="<?php echo $editing_schedule ? 'update' : 'create'; ?>">

                    <?php if ($editing_schedule): ?>
                        <input type="hidden" name="schedule_id" value="<?php echo esc_attr($edit_id); ?>">
                    <?php endif; ?>

                    <table class="form-table" role="presentation">
                        <tbody>
                            <!-- Schedule Name -->
                            <tr>
                                <th scope="row">
                                    <label for="schedule_name">
                                        <?php esc_html_e('Schedule Name', 'wc-all-cart-tracker'); ?>
                                        <span class="required">*</span>
                                    </label>
                                </th>
                                <td>
                                    <input type="text" id="schedule_name" name="schedule_name"
                                        value="<?php echo $editing_schedule ? esc_attr($editing_schedule['name']) : ''; ?>"
                                        class="regular-text" required>
                                    <p class="description">
                                        <?php esc_html_e('A descriptive name for this export schedule (e.g., "Daily Active Carts Report")', 'wc-all-cart-tracker'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Export Type -->
                            <tr>
                                <th scope="row">
                                    <label
                                        for="export_type"><?php esc_html_e('Export Type', 'wc-all-cart-tracker'); ?></label>
                                </th>
                                <td>
                                    <select id="export_type" name="export_type" class="regular-text">
                                        <option value="active_carts" <?php selected($editing_schedule['export_type'] ?? 'active_carts', 'active_carts'); ?>>
                                            <?php esc_html_e('Active Carts (Last 24 Hours)', 'wc-all-cart-tracker'); ?>
                                        </option>
                                        <option value="cart_history" <?php selected($editing_schedule['export_type'] ?? '', 'cart_history'); ?>>
                                            <?php esc_html_e('Cart History (With Filters)', 'wc-all-cart-tracker'); ?>
                                        </option>
                                    </select>
                                    <p class="description">
                                        <?php esc_html_e('Choose what data to export', 'wc-all-cart-tracker'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Export Format -->
                            <tr>
                                <th scope="row">
                                    <label
                                        for="export_format"><?php esc_html_e('Export Format', 'wc-all-cart-tracker'); ?></label>
                                </th>
                                <td>
                                    <select id="export_format" name="export_format">
                                        <option value="csv" <?php selected($editing_schedule['export_format'] ?? 'csv', 'csv'); ?>>
                                            <?php esc_html_e('CSV (Comma Separated Values)', 'wc-all-cart-tracker'); ?>
                                        </option>
                                        <option value="excel" <?php selected($editing_schedule['export_format'] ?? '', 'excel'); ?>>
                                            <?php esc_html_e('Excel (Microsoft Excel)', 'wc-all-cart-tracker'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>

                            <!-- Frequency -->
                            <tr>
                                <th scope="row">
                                    <label
                                        for="frequency"><?php esc_html_e('Frequency', 'wc-all-cart-tracker'); ?></label>
                                </th>
                                <td>
                                    <select id="frequency" name="frequency" class="regular-text">
                                        <option value="daily" <?php selected($editing_schedule['frequency'] ?? 'daily', 'daily'); ?>>
                                            <?php esc_html_e('Daily at 2:00 AM', 'wc-all-cart-tracker'); ?>
                                        </option>
                                        <option value="weekly" <?php selected($editing_schedule['frequency'] ?? '', 'weekly'); ?>>
                                            <?php esc_html_e('Weekly (Every Monday at 2:00 AM)', 'wc-all-cart-tracker'); ?>
                                        </option>
                                        <option value="monthly" <?php selected($editing_schedule['frequency'] ?? '', 'monthly'); ?>>
                                            <?php esc_html_e('Monthly (1st of Month at 2:00 AM)', 'wc-all-cart-tracker'); ?>
                                        </option>
                                    </select>
                                    <p class="description">
                                        <?php esc_html_e('How often to run this export', 'wc-all-cart-tracker'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Delivery Method -->
                            <tr>
                                <th scope="row">
                                    <label
                                        for="delivery_method"><?php esc_html_e('Delivery Method', 'wc-all-cart-tracker'); ?></label>
                                </th>
                                <td>
                                    <select id="delivery_method" name="delivery_method">
                                        <option value="email" <?php selected($editing_schedule['delivery_method'] ?? 'email', 'email'); ?>>
                                            <?php esc_html_e('Email with Attachment', 'wc-all-cart-tracker'); ?>
                                        </option>
                                        <option value="ftp" <?php selected($editing_schedule['delivery_method'] ?? '', 'ftp'); ?>>
                                            <?php esc_html_e('FTP/SFTP Upload', 'wc-all-cart-tracker'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>

                            <!-- Email Recipients (shown when delivery = email) -->
                            <tr class="email-settings">
                                <th scope="row">
                                    <label for="email_recipients">
                                        <?php esc_html_e('Email Recipients', 'wc-all-cart-tracker'); ?>
                                        <span class="required">*</span>
                                    </label>
                                </th>
                                <td>
                                    <textarea id="email_recipients" name="email_recipients" rows="4"
                                        class="large-text code"><?php echo $editing_schedule ? esc_textarea($editing_schedule['email_recipients']) : get_option('admin_email'); ?></textarea>
                                    <p class="description">
                                        <?php esc_html_e('Enter one email address per line. These addresses will receive the export as an attachment.', 'wc-all-cart-tracker'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- FTP Settings (shown when delivery = ftp) -->
                            <tr class="ftp-settings" style="display: none;">
                                <th scope="row">
                                    <label><?php esc_html_e('FTP Settings', 'wc-all-cart-tracker'); ?></label>
                                </th>
                                <td>
                                    <p class="description" style="color: #d63638; font-weight: 600;">
                                        <?php esc_html_e('⚠️ FTP delivery is currently a basic implementation. For production use, consider using a secure connection method.', 'wc-all-cart-tracker'); ?>
                                    </p>
                                    <p>
                                        <label
                                            for="ftp_host"><?php esc_html_e('FTP Host:', 'wc-all-cart-tracker'); ?></label><br>
                                        <input type="text" id="ftp_host" name="ftp_host"
                                            value="<?php echo $editing_schedule['ftp_host'] ?? ''; ?>"
                                            class="regular-text">
                                    </p>
                                    <p>
                                        <label
                                            for="ftp_user"><?php esc_html_e('FTP Username:', 'wc-all-cart-tracker'); ?></label><br>
                                        <input type="text" id="ftp_user" name="ftp_user"
                                            value="<?php echo $editing_schedule['ftp_user'] ?? ''; ?>"
                                            class="regular-text">
                                    </p>
                                    <p>
                                        <label
                                            for="ftp_pass"><?php esc_html_e('FTP Password:', 'wc-all-cart-tracker'); ?></label><br>
                                        <input type="password" id="ftp_pass" name="ftp_pass"
                                            value="<?php echo $editing_schedule['ftp_pass'] ?? ''; ?>"
                                            class="regular-text">
                                    </p>
                                    <p>
                                        <label
                                            for="ftp_path"><?php esc_html_e('FTP Path:', 'wc-all-cart-tracker'); ?></label><br>
                                        <input type="text" id="ftp_path" name="ftp_path"
                                            value="<?php echo $editing_schedule['ftp_path'] ?? '/'; ?>"
                                            class="regular-text">
                                    </p>
                                </td>
                            </tr>

                            <!-- Column Selection -->
                            <tr>
                                <th scope="row">
                                    <label><?php esc_html_e('Export Columns', 'wc-all-cart-tracker'); ?></label>
                                </th>
                                <td>
                                    <div class="wcat-columns-selection-container">
                                        <div class="wcat-columns-actions" style="margin-bottom: 10px;">
                                            <button type="button" class="button button-small" id="select-all-columns">
                                                <?php esc_html_e('Select All', 'wc-all-cart-tracker'); ?>
                                            </button>
                                            <button type="button" class="button button-small" id="deselect-all-columns">
                                                <?php esc_html_e('Deselect All', 'wc-all-cart-tracker'); ?>
                                            </button>
                                            <button type="button" class="button button-small"
                                                id="select-default-columns">
                                                <?php esc_html_e('Select Default', 'wc-all-cart-tracker'); ?>
                                            </button>
                                        </div>

                                        <div class="wcat-columns-grid"
                                            style="max-height: 400px; overflow-y: auto; border: 1px solid #c3c4c7; padding: 15px; background: #f9f9f9; border-radius: 4px;">
                                            <?php
                                            $selected_columns = $editing_schedule['columns'] ?? WC_Cart_Tracker_Export_Templates::get_default_columns();

                                            // Group columns by category
                                            $grouped_columns = array();
                                            foreach ($available_columns as $key => $config) {
                                                $group = $config['group'] ?? 'Other';
                                                if (!isset($grouped_columns[$group])) {
                                                    $grouped_columns[$group] = array();
                                                }
                                                $grouped_columns[$group][$key] = $config;
                                            }

                                            // Render each group
                                            foreach ($grouped_columns as $group_name => $columns):
                                                ?>
                                                <div class="column-group" style="margin-bottom: 20px;">
                                                    <h4
                                                        style="margin: 0 0 10px 0; padding: 8px 10px; background: #fff; border-left: 4px solid #2271b1; font-size: 13px;">
                                                        <?php echo esc_html($group_name); ?>
                                                    </h4>
                                                    <div
                                                        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 10px; padding-left: 15px;">
                                                        <?php foreach ($columns as $col_key => $col_config): ?>
                                                            <label
                                                                style="display: flex; align-items: start; gap: 8px; padding: 8px; background: #fff; border: 1px solid #ddd; border-radius: 3px; cursor: pointer; transition: all 0.2s;"
                                                                class="column-checkbox-label">
                                                                <input type="checkbox" name="columns[]"
                                                                    value="<?php echo esc_attr($col_key); ?>"
                                                                    data-default="<?php echo $col_config['default'] ? 'yes' : 'no'; ?>"
                                                                    <?php checked(in_array($col_key, $selected_columns)); ?>>
                                                                <span style="flex: 1;">
                                                                    <strong
                                                                        style="display: block; font-size: 13px; color: #1d2327;">
                                                                        <?php echo esc_html($col_config['label']); ?>
                                                                    </strong>
                                                                    <small style="color: #646970; font-size: 12px;">
                                                                        <?php echo esc_html($col_config['description']); ?>
                                                                    </small>
                                                                </span>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <p class="description" style="margin-top: 10px;">
                                            <?php esc_html_e('Select which columns to include in the export. You can select individual columns or use the quick action buttons above.', 'wc-all-cart-tracker'); ?>
                                        </p>
                                    </div>
                                </td>
                            </tr>

                            <!-- Status -->
                            <tr>
                                <th scope="row">
                                    <label for="enabled"><?php esc_html_e('Status', 'wc-all-cart-tracker'); ?></label>
                                </th>
                                <td>
                                    <select id="enabled" name="enabled">
                                        <option value="yes" <?php selected($editing_schedule['enabled'] ?? 'yes', 'yes'); ?>>
                                            <?php esc_html_e('Enabled (Will run automatically)', 'wc-all-cart-tracker'); ?>
                                        </option>
                                        <option value="no" <?php selected($editing_schedule['enabled'] ?? '', 'no'); ?>>
                                            <?php esc_html_e('Disabled (Paused, will not run)', 'wc-all-cart-tracker'); ?>
                                        </option>
                                    </select>
                                    <p class="description">
                                        <?php esc_html_e('Enable or disable this schedule. Disabled schedules will not run automatically.', 'wc-all-cart-tracker'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">
                            <?php
                            if ($editing_schedule) {
                                esc_html_e('Update Schedule', 'wc-all-cart-tracker');
                            } else {
                                esc_html_e('Create Schedule', 'wc-all-cart-tracker');
                            }
                            ?>
                        </button>

                        <?php if ($editing_schedule): ?>
                            <a href="<?php echo admin_url('admin.php?page=wc-cart-scheduled-exports'); ?>"
                                class="button button-large">
                                <?php esc_html_e('Cancel', 'wc-all-cart-tracker'); ?>
                            </a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>
        </div>

        <!-- Existing Schedules List -->
        <?php if (!$editing_schedule): ?>
            <div class="postbox wcat-schedules-list-card">
                <div class="postbox-header">
                    <h2 class="hndle"><?php esc_html_e('Existing Schedules', 'wc-all-cart-tracker'); ?></h2>
                </div>
                <div class="inside">
                    <?php if (empty($schedules)): ?>
                        <div class="wcat-empty-state" style="text-align: center; padding: 40px 20px;">
                            <span class="dashicons dashicons-calendar-alt" style="font-size: 64px; color: #c3c4c7;"></span>
                            <p style="font-size: 16px; color: #646970;">
                                <?php esc_html_e('No scheduled exports configured yet.', 'wc-all-cart-tracker'); ?>
                            </p>
                            <p>
                                <?php esc_html_e('Create your first schedule using the form above to automatically export cart data.', 'wc-all-cart-tracker'); ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width: 20%;"><?php esc_html_e('Name', 'wc-all-cart-tracker'); ?></th>
                                    <th><?php esc_html_e('Type', 'wc-all-cart-tracker'); ?></th>
                                    <th><?php esc_html_e('Format', 'wc-all-cart-tracker'); ?></th>
                                    <th><?php esc_html_e('Frequency', 'wc-all-cart-tracker'); ?></th>
                                    <th><?php esc_html_e('Delivery', 'wc-all-cart-tracker'); ?></th>
                                    <th><?php esc_html_e('Status', 'wc-all-cart-tracker'); ?></th>
                                    <th><?php esc_html_e('Last Run', 'wc-all-cart-tracker'); ?></th>
                                    <th><?php esc_html_e('Next Run', 'wc-all-cart-tracker'); ?></th>
                                    <th><?php esc_html_e('Actions', 'wc-all-cart-tracker'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $schedule_id => $schedule): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($schedule['name']); ?></strong>
                                        </td>
                                        <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $schedule['export_type']))); ?></td>
                                        <td><?php echo esc_html(strtoupper($schedule['export_format'])); ?></td>
                                        <td><?php echo esc_html(ucfirst($schedule['frequency'])); ?></td>
                                        <td><?php echo esc_html(ucfirst($schedule['delivery_method'])); ?></td>
                                        <td>
                                            <span
                                                class="wcat-status-badge <?php echo $schedule['enabled'] === 'yes' ? 'enabled' : 'disabled'; ?>">
                                                <?php echo $schedule['enabled'] === 'yes' ? esc_html__('Enabled', 'wc-all-cart-tracker') : esc_html__('Disabled', 'wc-all-cart-tracker'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            if (!empty($schedule['last_run'])) {
                                                echo '<span title="' . esc_attr($schedule['last_run']) . '">';
                                                echo esc_html(date('M d, Y H:i', strtotime($schedule['last_run'])));
                                                echo '</span>';
                                            } else {
                                                echo '<span style="color: #999;">—</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $next_run = wp_next_scheduled('wcat_scheduled_export_' . $schedule['frequency'], array($schedule_id));
                                            if ($next_run) {
                                                echo '<span title="' . esc_attr(date('Y-m-d H:i:s', $next_run)) . '">';
                                                echo esc_html(date('M d, Y H:i', $next_run));
                                                echo '</span>';
                                            } else {
                                                echo '<span style="color: #d63638;">Not scheduled</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo admin_url('admin.php?page=wc-cart-scheduled-exports&edit=' . urlencode($schedule_id)); ?>"
                                                class="button button-small">
                                                <?php esc_html_e('Edit', 'wc-all-cart-tracker'); ?>
                                            </a>
                                            <button type="button" class="button button-small wcat-test-export"
                                                data-schedule-id="<?php echo esc_attr($schedule_id); ?>">
                                                <?php esc_html_e('Test', 'wc-all-cart-tracker'); ?>
                                            </button>
                                            <button type="button"
                                                class="button button-small button-link-delete wcat-delete-schedule"
                                                data-schedule-id="<?php echo esc_attr($schedule_id); ?>">
                                                <?php esc_html_e('Delete', 'wc-all-cart-tracker'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Information Box -->
        <div class="postbox wcat-info-box">
            <div class="postbox-header">
                <h2 class="hndle">
                    <span class="dashicons dashicons-info-outline" style="margin-right: 5px;"></span>
                    <?php esc_html_e('How Scheduled Exports Work', 'wc-all-cart-tracker'); ?>
                </h2>
            </div>
            <div class="inside">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div>
                        <h3 style="margin-top: 0;"><?php esc_html_e('📅 Scheduling', 'wc-all-cart-tracker'); ?></h3>
                        <ul style="list-style: disc; padding-left: 20px; line-height: 1.8;">
                            <li><?php esc_html_e('Daily exports run every day at 2:00 AM', 'wc-all-cart-tracker'); ?>
                            </li>
                            <li><?php esc_html_e('Weekly exports run every Monday at 2:00 AM', 'wc-all-cart-tracker'); ?>
                            </li>
                            <li><?php esc_html_e('Monthly exports run on the 1st at 2:00 AM', 'wc-all-cart-tracker'); ?>
                            </li>
                            <li><?php esc_html_e('Times are based on WordPress timezone', 'wc-all-cart-tracker'); ?>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h3 style="margin-top: 0;"><?php esc_html_e('📧 Delivery', 'wc-all-cart-tracker'); ?></h3>
                        <ul style="list-style: disc; padding-left: 20px; line-height: 1.8;">
                            <li><?php esc_html_e('Email delivery sends file as attachment', 'wc-all-cart-tracker'); ?>
                            </li>
                            <li><?php esc_html_e('Supports multiple recipients', 'wc-all-cart-tracker'); ?></li>
                            <li><?php esc_html_e('FTP uploads to specified server', 'wc-all-cart-tracker'); ?></li>
                            <li><?php esc_html_e('Test button sends export immediately', 'wc-all-cart-tracker'); ?></li>
                        </ul>
                    </div>

                    <div>
                        <h3 style="margin-top: 0;"><?php esc_html_e('⚙️ Requirements', 'wc-all-cart-tracker'); ?></h3>
                        <ul style="list-style: disc; padding-left: 20px; line-height: 1.8;">
                            <li><?php esc_html_e('WordPress cron must be enabled', 'wc-all-cart-tracker'); ?></li>
                            <li><?php esc_html_e('Site needs regular traffic OR external cron', 'wc-all-cart-tracker'); ?>
                            </li>
                            <li><?php esc_html_e('wp_mail() must be working for email', 'wc-all-cart-tracker'); ?></li>
                            <li><?php esc_html_e('Check debug.log for export logs', 'wc-all-cart-tracker'); ?></li>
                        </ul>
                    </div>
                </div>

                <div
                    style="margin-top: 20px; padding: 15px; background: #e7f5fe; border-left: 4px solid #2271b1; border-radius: 4px;">
                    <strong><?php esc_html_e('💡 Pro Tip:', 'wc-all-cart-tracker'); ?></strong>
                    <?php esc_html_e('For production sites, set up a server cron job to ensure reliable execution:', 'wc-all-cart-tracker'); ?>
                    <code style="display: block; margin-top: 8px; padding: 8px; background: #fff; border-radius: 3px;">
                        */15 * * * * curl <?php echo site_url('/wp-cron.php'); ?>
                    </code>
                </div>
            </div>
        </div>

    </div>
    <?php
    /**
     * Add this to the Scheduled Exports admin page
     * Place it in a new section for debugging
     */

    // Add to admin/views/admin-scheduled-exports.php before closing </div>
    ?>

    <!-- Email Diagnostic Section -->
    <div class="postbox wcat-diagnostic-card" style="margin-top: 20px;">
        <div class="postbox-header">
            <h2 class="hndle">
                <span class="dashicons dashicons-admin-tools" style="margin-right: 5px;"></span>
                <?php esc_html_e('Email Diagnostic Tool', 'wc-all-cart-tracker'); ?>
            </h2>
        </div>
        <div class="inside">
            <p><?php esc_html_e('Test your email configuration before scheduling exports.', 'wc-all-cart-tracker'); ?>
            </p>

            <form method="post" action="" id="wcat-test-email-form">
                <?php wp_nonce_field('wcat_test_email', 'wcat_test_email_nonce'); ?>
                <input type="hidden" name="wcat_action" value="test_email">

                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="test_email_recipient">
                                    <?php esc_html_e('Test Email Recipient:', 'wc-all-cart-tracker'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="email" id="test_email_recipient" name="test_email_recipient"
                                    value="<?php echo esc_attr(get_option('admin_email')); ?>" class="regular-text"
                                    required>
                                <p class="description">
                                    <?php esc_html_e('Email address to receive the test message', 'wc-all-cart-tracker'); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Send Test Email', 'wc-all-cart-tracker'); ?>
                    </button>
                </p>
            </form>

            <hr style="margin: 20px 0;">

            <h3><?php esc_html_e('Current Configuration', 'wc-all-cart-tracker'); ?></h3>
            <table class="widefat" style="max-width: 600px;">
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e('Admin Email:', 'wc-all-cart-tracker'); ?></strong></td>
                        <td><?php echo esc_html(get_option('admin_email')); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('SMTP Configured:', 'wc-all-cart-tracker'); ?></strong></td>
                        <td>
                            <?php
                            $smtp_configured = defined('WPMS_ON') && WPMS_ON ||
                                defined('WPMS_SMTP_PASS') ||
                                get_option('wpmailsmtp_smtp') ||
                                get_option('smtp_settings');
                            echo $smtp_configured ?
                                '<span style="color: green;">✓ Yes</span>' :
                                '<span style="color: orange;">⚠ Using default (may be unreliable)</span>';
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('WP Cron:', 'wc-all-cart-tracker'); ?></strong></td>
                        <td>
                            <?php
                            echo defined('DISABLE_WP_CRON') && DISABLE_WP_CRON ?
                                '<span style="color: red;">✗ Disabled</span>' :
                                '<span style="color: green;">✓ Enabled</span>';
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Debug Mode:', 'wc-all-cart-tracker'); ?></strong></td>
                        <td>
                            <?php
                            echo defined('WP_DEBUG') && WP_DEBUG ?
                                '<span style="color: green;">✓ Enabled</span>' :
                                '<span style="color: orange;">⚠ Disabled</span>';
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div
                style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                <strong><?php esc_html_e('📌 Troubleshooting Tips:', 'wc-all-cart-tracker'); ?></strong>
                <ul style="margin: 10px 0 0 20px;">
                    <li><?php esc_html_e('Enable WP_DEBUG to see error logs in wp-content/debug.log', 'wc-all-cart-tracker'); ?>
                    </li>
                    <li><?php esc_html_e('Install an SMTP plugin (WP Mail SMTP, Easy WP SMTP) for reliable delivery', 'wc-all-cart-tracker'); ?>
                    </li>
                    <li><?php esc_html_e('Check your server\'s PHP mail() function is working', 'wc-all-cart-tracker'); ?>
                    </li>
                    <li><?php esc_html_e('Verify email recipients are correct (no typos)', 'wc-all-cart-tracker'); ?>
                    </li>
                    <li><?php esc_html_e('Check spam/junk folders', 'wc-all-cart-tracker'); ?></li>
                </ul>
            </div>
        </div>
    </div>
    <!-- 
    <script>
        jQuery(document).ready(function ($) {
            console.log('=== WCAT DEBUGGING CHECKER ===');

            // Check wcatScheduledExport
            if (typeof wcatScheduledExport !== 'undefined') {
                console.log('✓ wcatScheduledExport exists');
                console.log('AJAX URL:', wcatScheduledExport.ajaxUrl);
                console.log('Nonce:', wcatScheduledExport.nonce);
            } else {
                console.error('✗ wcatScheduledExport NOT FOUND - Scripts not loading!');
            }

            // Check buttons
            console.log('Test buttons:', $('.wcat-test-export').length);
            console.log('Delete buttons:', $('.wcat-delete-schedule').length);

            // Manual test
            $(document).on('click', '.wcat-test-export', function () {
                console.log('TEST CLICKED:', $(this).data('schedule-id'));
            });

            $(document).on('click', '.wcat-delete-schedule', function () {
                console.log('DELETE CLICKED:', $(this).data('schedule-id'));
            });
        });
    </script> -->
</div>