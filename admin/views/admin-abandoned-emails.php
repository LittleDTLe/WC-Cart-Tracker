<?php
/**
 * Admin View: Abandoned Cart Email Settings
 *
 * @package WC_All_Cart_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$enabled = get_option('wcat_abandoned_email_enabled', 'no');
$wait_time = get_option('wcat_abandoned_email_wait_time', 1);
$send_to_guests = get_option('wcat_abandoned_email_guests', 'yes');
$email_subject = get_option('wcat_abandoned_email_subject', 'You left items in your cart!');
$email_heading = get_option('wcat_abandoned_email_heading', 'Complete Your Purchase');
$email_message = get_option('wcat_abandoned_email_message', "Hi {customer_name},\n\nYou have items waiting in your cart at {store_name}. Complete your purchase now and don't miss out!");

// Get email statistics
$stats = WC_Cart_Tracker_Abandoned_Email::get_email_stats();
?>

<div class="wrap wcat-abandoned-email-settings">
    <h1><?php echo esc_html__('Abandoned Cart Emails', 'wc-all-cart-tracker'); ?></h1>

    <?php settings_errors('wcat_abandoned_emails'); ?>

    <div style="max-width: 1200px;">

        <!-- Statistics Cards -->
        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin: 20px 0;">
            <div class="postbox">
                <div class="inside" style="padding: 20px; text-align: center;">
                    <div style="font-size: 36px; font-weight: 700; color: #2271b1; margin-bottom: 10px;">
                        <?php echo number_format($stats['total_sent']); ?>
                    </div>
                    <div style="color: #646970; font-size: 14px;">
                        <?php echo esc_html__('Total Emails Sent', 'wc-all-cart-tracker'); ?>
                    </div>
                </div>
            </div>

            <div class="postbox">
                <div class="inside" style="padding: 20px; text-align: center;">
                    <div style="font-size: 36px; font-weight: 700; color: #00a32a; margin-bottom: 10px;">
                        <?php echo number_format($stats['sent_today']); ?>
                    </div>
                    <div style="color: #646970; font-size: 14px;">
                        <?php echo esc_html__('Sent Today', 'wc-all-cart-tracker'); ?>
                    </div>
                </div>
            </div>

            <div class="postbox">
                <div class="inside" style="padding: 20px; text-align: center;">
                    <div style="font-size: 36px; font-weight: 700; color: #f0b849; margin-bottom: 10px;">
                        <?php echo number_format($stats['sent_week']); ?>
                    </div>
                    <div style="color: #646970; font-size: 14px;">
                        <?php echo esc_html__('Last 7 Days', 'wc-all-cart-tracker'); ?>
                    </div>
                </div>
            </div>

            <div class="postbox">
                <div class="inside" style="padding: 20px; text-align: center;">
                    <div style="font-size: 36px; font-weight: 700; color: #d63638; margin-bottom: 10px;">
                        <?php echo number_format($stats['sent_month']); ?>
                    </div>
                    <div style="color: #646970; font-size: 14px;">
                        <?php echo esc_html__('Last 30 Days', 'wc-all-cart-tracker'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Settings Form -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php echo esc_html__('Email Settings', 'wc-all-cart-tracker'); ?></h2>
            </div>
            <div class="inside">
                <form method="post" action="">
                    <?php wp_nonce_field('wcat_email_settings', 'wcat_email_nonce'); ?>
                    <input type="hidden" name="wcat_abandoned_email_action" value="save_settings">

                    <table class="form-table" role="presentation">
                        <tbody>
                            <!-- Enable/Disable -->
                            <tr>
                                <th scope="row">
                                    <label for="enabled">
                                        <?php echo esc_html__('Enable Abandoned Cart Emails', 'wc-all-cart-tracker'); ?>
                                    </label>
                                </th>
                                <td>
                                    <select name="enabled" id="enabled">
                                        <option value="yes" <?php selected($enabled, 'yes'); ?>>
                                            <?php echo esc_html__('Yes', 'wc-all-cart-tracker'); ?>
                                        </option>
                                        <option value="no" <?php selected($enabled, 'no'); ?>>
                                            <?php echo esc_html__('No', 'wc-all-cart-tracker'); ?>
                                        </option>
                                    </select>
                                    <p class="description">
                                        <?php echo esc_html__('Enable automatic abandoned cart recovery emails', 'wc-all-cart-tracker'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Wait Time -->
                            <tr>
                                <th scope="row">
                                    <label for="wait_time">
                                        <?php echo esc_html__('Wait Time (Hours)', 'wc-all-cart-tracker'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="number" name="wait_time" id="wait_time"
                                        value="<?php echo esc_attr($wait_time); ?>" min="1" max="168"
                                        class="small-text">
                                    <p class="description">
                                        <?php echo esc_html__('Send email after cart has been abandoned for this many hours (1-168)', 'wc-all-cart-tracker'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Send to Guests -->
                            <tr>
                                <th scope="row">
                                    <label for="send_to_guests">
                                        <?php echo esc_html__('Send to Guest Users', 'wc-all-cart-tracker'); ?>
                                    </label>
                                </th>
                                <td>
                                    <select name="send_to_guests" id="send_to_guests">
                                        <option value="yes" <?php selected($send_to_guests, 'yes'); ?>>
                                            <?php echo esc_html__('Yes', 'wc-all-cart-tracker'); ?>
                                        </option>
                                        <option value="no" <?php selected($send_to_guests, 'no'); ?>>
                                            <?php echo esc_html__('No', 'wc-all-cart-tracker'); ?>
                                        </option>
                                    </select>
                                    <p class="description">
                                        <?php echo esc_html__('Send emails to guest users who provided email at checkout', 'wc-all-cart-tracker'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Email Subject -->
                            <tr>
                                <th scope="row">
                                    <label for="email_subject">
                                        <?php echo esc_html__('Email Subject', 'wc-all-cart-tracker'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="text" name="email_subject" id="email_subject"
                                        value="<?php echo esc_attr($email_subject); ?>" class="large-text">
                                    <p class="description">
                                        <?php echo esc_html__('Available placeholders: {customer_name}, {store_name}, {cart_total}', 'wc-all-cart-tracker'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Email Heading -->
                            <tr>
                                <th scope="row">
                                    <label for="email_heading">
                                        <?php echo esc_html__('Email Heading', 'wc-all-cart-tracker'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="text" name="email_heading" id="email_heading"
                                        value="<?php echo esc_attr($email_heading); ?>" class="large-text">
                                    <p class="description">
                                        <?php echo esc_html__('Main heading displayed in the email', 'wc-all-cart-tracker'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Email Message -->
                            <tr>
                                <th scope="row">
                                    <label for="email_message">
                                        <?php echo esc_html__('Email Message', 'wc-all-cart-tracker'); ?>
                                    </label>
                                </th>
                                <td>
                                    <textarea name="email_message" id="email_message" rows="6"
                                        class="large-text"><?php echo esc_textarea($email_message); ?></textarea>
                                    <p class="description">
                                        <?php echo esc_html__('Main message content. Available placeholders: {customer_name}, {store_name}, {cart_total}, {cart_url}', 'wc-all-cart-tracker'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">
                            <?php echo esc_html__('Save Settings', 'wc-all-cart-tracker'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>

        <!-- Test Email Section -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php echo esc_html__('Send Test Email', 'wc-all-cart-tracker'); ?></h2>
            </div>
            <div class="inside">
                <form method="post" action="">
                    <?php wp_nonce_field('wcat_email_settings', 'wcat_email_nonce'); ?>
                    <input type="hidden" name="wcat_abandoned_email_action" value="send_test">

                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="test_email">
                                        <?php echo esc_html__('Test Email Address', 'wc-all-cart-tracker'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="email" name="test_email" id="test_email"
                                        value="<?php echo esc_attr(get_option('admin_email')); ?>" class="regular-text"
                                        required>
                                    <p class="description">
                                        <?php echo esc_html__('Send a test email with sample cart data', 'wc-all-cart-tracker'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-secondary">
                            <?php echo esc_html__('Send Test Email', 'wc-all-cart-tracker'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>

        <!-- Information Box -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle">
                    <span class="dashicons dashicons-info-outline" style="margin-right: 5px;"></span>
                    <?php echo esc_html__('How It Works', 'wc-all-cart-tracker'); ?>
                </h2>
            </div>
            <div class="inside">
                <div style="padding: 20px;">
                    <h3 style="margin-top: 0;">
                        <?php echo esc_html__('📧 Abandoned Cart Email System', 'wc-all-cart-tracker'); ?>
                    </h3>
                    <ul style="line-height: 1.8;">
                        <li><strong><?php echo esc_html__('Automatic Detection:', 'wc-all-cart-tracker'); ?></strong>
                            <?php echo esc_html__('The system checks for abandoned carts every hour', 'wc-all-cart-tracker'); ?>
                        </li>
                        <li><strong><?php echo esc_html__('Smart Timing:', 'wc-all-cart-tracker'); ?></strong>
                            <?php echo esc_html__('Emails are sent based on your configured wait time', 'wc-all-cart-tracker'); ?>
                        </li>
                        <li><strong><?php echo esc_html__('No Duplicates:', 'wc-all-cart-tracker'); ?></strong>
                            <?php echo esc_html__('Customers won\'t receive multiple emails for the same cart within 7 days', 'wc-all-cart-tracker'); ?>
                        </li>
                        <li><strong><?php echo esc_html__('Professional Design:', 'wc-all-cart-tracker'); ?></strong>
                            <?php echo esc_html__('Emails use a clean, responsive HTML template', 'wc-all-cart-tracker'); ?>
                        </li>
                        <li><strong><?php echo esc_html__('Cart Preview:', 'wc-all-cart-tracker'); ?></strong>
                            <?php echo esc_html__('Emails include a summary of abandoned products and total', 'wc-all-cart-tracker'); ?>
                        </li>
                    </ul>

                    <hr style="margin: 20px 0;">

                    <h3><?php echo esc_html__('📝 Best Practices', 'wc-all-cart-tracker'); ?></h3>
                    <ul style="line-height: 1.8;">
                        <li><?php echo esc_html__('Start with 1-2 hour wait time for maximum recovery', 'wc-all-cart-tracker'); ?>
                        </li>
                        <li><?php echo esc_html__('Use personalization placeholders in your message', 'wc-all-cart-tracker'); ?>
                        </li>
                        <li><?php echo esc_html__('Keep the subject line clear and action-oriented', 'wc-all-cart-tracker'); ?>
                        </li>
                        <li><?php echo esc_html__('Test your emails before enabling for customers', 'wc-all-cart-tracker'); ?>
                        </li>
                        <li><?php echo esc_html__('Monitor statistics to track email effectiveness', 'wc-all-cart-tracker'); ?>
                        </li>
                    </ul>

                    <hr style="margin: 20px 0;">

                    <h3><?php echo esc_html__('⚙️ Technical Requirements', 'wc-all-cart-tracker'); ?></h3>
                    <ul style="line-height: 1.8;">
                        <li><?php echo esc_html__('WordPress Cron must be enabled', 'wc-all-cart-tracker'); ?></li>
                        <li><?php echo esc_html__('wp_mail() function must be working', 'wc-all-cart-tracker'); ?></li>
                        <li><?php echo esc_html__('SMTP plugin recommended for reliable delivery', 'wc-all-cart-tracker'); ?>
                        </li>
                        <li><?php echo esc_html__('Customers must have valid email addresses', 'wc-all-cart-tracker'); ?>
                        </li>
                    </ul>

                    <div
                        style="margin-top: 20px; padding: 15px; background: #e7f5fe; border-left: 4px solid #2271b1; border-radius: 4px;">
                        <strong><?php echo esc_html__('💡 Pro Tip:', 'wc-all-cart-tracker'); ?></strong>
                        <?php echo esc_html__('Use the test email feature regularly to preview how your emails will appear to customers. This helps you optimize your message for better conversion rates.', 'wc-all-cart-tracker'); ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    .wcat-abandoned-email-settings .postbox {
        margin-bottom: 20px;
    }

    .wcat-abandoned-email-settings .form-table th {
        width: 250px;
    }

    .wcat-abandoned-email-settings .description {
        font-size: 13px;
        font-style: italic;
    }
</style>