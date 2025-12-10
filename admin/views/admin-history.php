<?php
/**
 * View: Cart History Page with Custom Pagination
 *
 * This file is included by WC_Cart_Tracker_Admin::render_history_page()
 *
 * @package WC_All_Cart_Tracker
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = WC_Cart_Tracker_Database::get_table_name();

// --- Data Retrieval for History ---
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
$days_filter = isset($_GET['days']) ? absint($_GET['days']) : 30;

if (!in_array($days_filter, array(7, 30, 60, 90, 365))) {
    $days_filter = 30;
}

$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'last_updated';
$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

$allowed_orderby = array('last_updated', 'customer_email', 'cart_total', 'cart_status');
if (!in_array($orderby, $allowed_orderby)) {
    $orderby = 'last_updated';
}
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Build query based on filters
$date_from = date('Y-m-d H:i:s', strtotime("-{$days_filter} days"));
$where_clauses = array("last_updated >= %s");
$query_params = array($date_from);

if ($status_filter === 'converted') {
    $where_clauses[] = "cart_status = %s";
    $query_params[] = 'converted';
} elseif ($status_filter === 'deleted') {
    $where_clauses[] = "cart_status = %s";
    $query_params[] = 'deleted';
} elseif ($status_filter === 'abandoned') {
    $where_clauses[] = "is_active = 1";
    $where_clauses[] = "last_updated < %s";
    $query_params[] = date('Y-m-d H:i:s', strtotime('-24 hours'));
} elseif ($status_filter === 'inactive') {
    $where_clauses[] = "is_active = 0";
}

$where_sql = implode(' AND ', $where_clauses);

// Get counts for filters
$converted_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table_name} WHERE last_updated >= %s AND cart_status = 'converted'",
    $date_from
));
$deleted_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table_name} WHERE last_updated >= %s AND cart_status = 'deleted'",
    $date_from
));
$abandoned_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table_name} WHERE is_active = 1 AND last_updated < %s",
    date('Y-m-d H:i:s', strtotime('-24 hours'))
));
$all_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table_name} WHERE last_updated >= %s",
    $date_from
));

// Custom Pagination - Get user preference or default to 50
$per_page_options = array(5, 10, 25, 50, 75, 100);
$per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 50;

// Validate per_page value
if (!in_array($per_page, $per_page_options)) {
    $per_page = 50;
}

$current_page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Get total count (for pagination)
$total_query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_sql}";
$total_items = $wpdb->get_var($wpdb->prepare($total_query, $query_params));
$total_pages = ceil($total_items / $per_page);

// Get carts (paginated)
$carts_query = "SELECT * FROM {$table_name} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
$query_params[] = $per_page;
$query_params[] = $offset;
$carts = $wpdb->get_results($wpdb->prepare($carts_query, $query_params));
// --- End Data Retrieval ---
?>
<div class="wrap">
    <h1><?php echo esc_html__('Cart History', 'wc-all-cart-tracker'); ?></h1>

    <div style="background: #fff; border: 1px solid #c3c4c7; padding: 15px; margin: 20px 0; border-radius: 4px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                <div>
                    <label for="days-filter" style="margin-right: 10px; font-weight: 600;">
                        <?php echo esc_html__('Time Period:', 'wc-all-cart-tracker'); ?>
                    </label>
                    <select id="days-filter" onchange="this.form.submit()" name="days" form="filter-form">
                        <option value="7" <?php selected($days_filter, 7); ?>>
                            <?php echo esc_html__('Last 7 Days', 'wc-all-cart-tracker'); ?>
                        </option>
                        <option value="30" <?php selected($days_filter, 30); ?>>
                            <?php echo esc_html__('Last 30 Days', 'wc-all-cart-tracker'); ?>
                        </option>
                        <option value="60" <?php selected($days_filter, 60); ?>>
                            <?php echo esc_html__('Last 60 Days', 'wc-all-cart-tracker'); ?>
                        </option>
                        <option value="90" <?php selected($days_filter, 90); ?>>
                            <?php echo esc_html__('Last 90 Days', 'wc-all-cart-tracker'); ?>
                        </option>
                        <option value="365" <?php selected($days_filter, 365); ?>>
                            <?php echo esc_html__('Last Year', 'wc-all-cart-tracker'); ?>
                        </option>
                    </select>
                </div>

                <div>
                    <label for="status-filter" style="margin-right: 10px; font-weight: 600;">
                        <?php echo esc_html__('Status:', 'wc-all-cart-tracker'); ?>
                    </label>
                    <select id="status-filter" onchange="this.form.submit()" name="status" form="filter-form">
                        <option value="all" <?php selected($status_filter, 'all'); ?>>
                            <?php echo esc_html__('All', 'wc-all-cart-tracker'); ?>
                            (<?php echo esc_html($all_count); ?>)
                        </option>
                        <option value="converted" <?php selected($status_filter, 'converted'); ?>>
                            <?php echo esc_html__('Converted', 'wc-all-cart-tracker'); ?>
                            (<?php echo esc_html($converted_count); ?>)
                        </option>
                        <option value="deleted" <?php selected($status_filter, 'deleted'); ?>>
                            <?php echo esc_html__('Deleted', 'wc-all-cart-tracker'); ?>
                            (<?php echo esc_html($deleted_count); ?>)
                        </option>
                        <option value="abandoned" <?php selected($status_filter, 'abandoned'); ?>>
                            <?php echo esc_html__('Abandoned', 'wc-all-cart-tracker'); ?>
                            (<?php echo esc_html($abandoned_count); ?>)
                        </option>
                        <option value="inactive" <?php selected($status_filter, 'inactive'); ?>>
                            <?php echo esc_html__('All Inactive', 'wc-all-cart-tracker'); ?>
                        </option>
                    </select>
                </div>

                <div>
                    <label for="per-page-filter" style="margin-right: 10px; font-weight: 600;">
                        <?php echo esc_html__('Show per page:', 'wc-all-cart-tracker'); ?>
                    </label>
                    <select id="per-page-filter" onchange="this.form.submit()" name="per_page" form="filter-form">
                        <?php foreach ($per_page_options as $option): ?>
                            <option value="<?php echo esc_attr($option); ?>" <?php selected($per_page, $option); ?>>
                                <?php echo esc_html($option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Export Dropdown Button -->
            <div class="wcat-export-dropdown" style="position: relative; display: inline-block;">
                <button class="button button-primary wcat-export-trigger" type="button">
                    <span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
                    <?php esc_html_e('Export History', 'wc-all-cart-tracker'); ?> ▾
                </button>
                <div class="wcat-export-menu"
                    style="display: none; position: absolute; right: 0; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); z-index: 1000; min-width: 200px; margin-top: 5px;">
                    <div
                        style="padding: 10px 15px; border-bottom: 1px solid #f0f0f1; background: #f6f7f7; font-weight: 600; font-size: 12px; color: #646970;">
                        <?php esc_html_e('Export Format', 'wc-all-cart-tracker'); ?>
                    </div>
                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('wcat_export' => 'cart_history', 'format' => 'csv', 'days' => $days_filter, 'status' => $status_filter)), 'wcat_export_nonce')); ?>"
                        class="wcat-export-option"
                        style="display: block; padding: 10px 15px; text-decoration: none; color: #2271b1; border-bottom: 1px solid #f0f0f1;">
                        <span class="dashicons dashicons-media-spreadsheet"
                            style="margin-right: 8px; color: #00a32a;"></span>
                        <strong><?php esc_html_e('CSV', 'wc-all-cart-tracker'); ?></strong>
                        <br>
                        <small
                            style="color: #646970;"><?php esc_html_e('Compatible with Excel, Numbers', 'wc-all-cart-tracker'); ?></small>
                    </a>
                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('wcat_export' => 'cart_history', 'format' => 'excel', 'days' => $days_filter, 'status' => $status_filter)), 'wcat_export_nonce')); ?>"
                        class="wcat-export-option"
                        style="display: block; padding: 10px 15px; text-decoration: none; color: #2271b1; border-bottom: 1px solid #f0f0f1;">
                        <span class="dashicons dashicons-media-document"
                            style="margin-right: 8px; color: #2271b1;"></span>
                        <strong><?php esc_html_e('Excel', 'wc-all-cart-tracker'); ?></strong>
                        <br>
                        <small
                            style="color: #646970;"><?php esc_html_e('Microsoft Excel format', 'wc-all-cart-tracker'); ?></small>
                    </a>
                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('wcat_export' => 'cart_history', 'format' => 'google_sheets', 'days' => $days_filter, 'status' => $status_filter)), 'wcat_export_nonce')); ?>"
                        class="wcat-export-option"
                        style="display: block; padding: 10px 15px; text-decoration: none; color: #2271b1;">
                        <span class="dashicons dashicons-cloud" style="margin-right: 8px; color: #f0b849;"></span>
                        <strong><?php esc_html_e('Google Sheets', 'wc-all-cart-tracker'); ?></strong>
                        <br>
                        <small
                            style="color: #646970;"><?php esc_html_e('Import to Google Sheets', 'wc-all-cart-tracker'); ?></small>
                    </a>
                </div>
            </div>
        </div>

        <form id="filter-form" method="get" style="display: none;">
            <input type="hidden" name="page" value="wc-cart-history">
            <input type="hidden" name="orderby" value="<?php echo esc_attr($orderby); ?>">
            <input type="hidden" name="order" value="<?php echo esc_attr($order); ?>">
        </form>
    </div>

    <div class="tablenav top">
        <div class="alignleft actions">
            <span class="displaying-num">
                <?php
                $start_item = ($current_page - 1) * $per_page + 1;
                $end_item = min($current_page * $per_page, $total_items);

                printf(
                    esc_html__('Showing %1$s-%2$s of %3$s carts', 'wc-all-cart-tracker'),
                    number_format_i18n($start_item),
                    number_format_i18n($end_item),
                    number_format_i18n($total_items)
                );
                ?>
            </span>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="tablenav-pages">
                <span class="pagination-links">
                    <?php
                    $base_url = add_query_arg(array(
                        'page' => 'wc-cart-history',
                        'days' => $days_filter,
                        'status' => $status_filter,
                        'orderby' => $orderby,
                        'order' => $order,
                        'per_page' => $per_page
                    ), admin_url('admin.php'));

                    if ($current_page > 1) {
                        echo '<a class="first-page button" href="' . esc_url(add_query_arg('paged', 1, $base_url)) . '">«</a> ';
                        echo '<a class="prev-page button" href="' . esc_url(add_query_arg('paged', $current_page - 1, $base_url)) . '">‹</a> ';
                    }

                    echo '<span class="paging-input">';
                    echo sprintf(
                        esc_html__('Page %1$s of %2$s', 'wc-all-cart-tracker'),
                        number_format_i18n($current_page),
                        number_format_i18n($total_pages)
                    );
                    echo '</span> ';

                    if ($current_page < $total_pages) {
                        echo '<a class="next-page button" href="' . esc_url(add_query_arg('paged', $current_page + 1, $base_url)) . '">›</a> ';
                        echo '<a class="last-page button" href="' . esc_url(add_query_arg('paged', $total_pages, $base_url)) . '">»</a>';
                    }
                    ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 60px;"><?php echo esc_html__('ID', 'wc-all-cart-tracker'); ?></th>
                <th
                    class="sortable <?php echo $orderby === 'last_updated' ? 'sorted' : ''; ?> <?php echo strtolower($order) === 'asc' ? 'asc' : 'desc'; ?>">
                    <a
                        href="<?php echo esc_url(add_query_arg(array('orderby' => 'last_updated', 'order' => $orderby === 'last_updated' && $order === 'DESC' ? 'ASC' : 'DESC', 'days' => $days_filter, 'status' => $status_filter, 'per_page' => $per_page, 'paged' => false))); ?>">
                        <?php echo esc_html__('Date', 'wc-all-cart-tracker'); ?>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th
                    class="sortable <?php echo $orderby === 'customer_email' ? 'sorted' : ''; ?> <?php echo strtolower($order) === 'asc' ? 'asc' : 'desc'; ?>">
                    <a
                        href="<?php echo esc_url(add_query_arg(array('orderby' => 'customer_email', 'order' => $orderby === 'customer_email' && $order === 'DESC' ? 'ASC' : 'DESC', 'days' => $days_filter, 'status' => $status_filter, 'per_page' => $per_page, 'paged' => false))); ?>">
                        <?php echo esc_html__('Customer', 'wc-all-cart-tracker'); ?>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th
                    class="sortable <?php echo $orderby === 'cart_status' ? 'sorted' : ''; ?> <?php echo strtolower($order) === 'asc' ? 'asc' : 'desc'; ?>">
                    <a
                        href="<?php echo esc_url(add_query_arg(array('orderby' => 'cart_status', 'order' => $orderby === 'cart_status' && $order === 'DESC' ? 'ASC' : 'DESC', 'days' => $days_filter, 'status' => $status_filter, 'per_page' => $per_page, 'paged' => false))); ?>">
                        <?php echo esc_html__('Status', 'wc-all-cart-tracker'); ?>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th
                    class="sortable <?php echo $orderby === 'cart_total' ? 'sorted' : ''; ?> <?php echo strtolower($order) === 'asc' ? 'asc' : 'desc'; ?>">
                    <a
                        href="<?php echo esc_url(add_query_arg(array('orderby' => 'cart_total', 'order' => $orderby === 'cart_total' && $order === 'DESC' ? 'ASC' : 'DESC', 'days' => $days_filter, 'status' => $status_filter, 'per_page' => $per_page, 'paged' => false))); ?>">
                        <?php echo esc_html__('Cart Total', 'wc-all-cart-tracker'); ?>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th><?php echo esc_html__('Items', 'wc-all-cart-tracker'); ?></th>
                <th><?php echo esc_html__('Cart Contents', 'wc-all-cart-tracker'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($carts)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px;">
                        <?php echo esc_html__('No carts found for the selected criteria.', 'wc-all-cart-tracker'); ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($carts as $cart): ?>
                    <tr>
                        <td><?php echo esc_html($cart->id); ?></td>
                        <td>
                            <?php
                            $datetime = new DateTime($cart->last_updated);
                            echo esc_html($datetime->format('Y-m-d H:i'));
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
                            <?php
                            $status_label = '';
                            $status_color = '';

                            switch ($cart->cart_status) {
                                case 'converted':
                                    $status_label = __('Converted', 'wc-all-cart-tracker');
                                    $status_color = '#00a32a';
                                    break;
                                case 'deleted':
                                    $status_label = __('Deleted', 'wc-all-cart-tracker');
                                    $status_color = '#f0b849';
                                    break;
                                case 'active':
                                    if (strtotime($cart->last_updated) < strtotime('-24 hours')) {
                                        $status_label = __('Abandoned', 'wc-all-cart-tracker');
                                        $status_color = '#d63638';
                                    } else {
                                        $status_label = __('Active', 'wc-all-cart-tracker');
                                        $status_color = '#2271b1';
                                    }
                                    break;
                                default:
                                    $status_label = ucfirst($cart->cart_status);
                                    $status_color = '#646970';
                            }
                            ?>
                            <span
                                style="display: inline-block; padding: 3px 8px; background: <?php echo esc_attr($status_color); ?>; color: #fff; border-radius: 3px; font-size: 11px; font-weight: 600;">
                                <?php echo esc_html($status_label); ?>
                            </span>
                        </td>
                        <td>
                            <strong><?php echo wc_price($cart->cart_total); ?></strong>
                        </td>
                        <td>
                            <?php
                            $cart_items = json_decode($cart->cart_content, true);
                            $item_count = is_array($cart_items) ? count($cart_items) : 0;
                            echo '<strong>' . esc_html($item_count) . '</strong> ' . esc_html(_n('item', 'items', $item_count, 'wc-all-cart-tracker'));
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($cart_items) && is_array($cart_items)):
                                echo '<details>';
                                echo '<summary style="cursor: pointer; color: #2271b1; font-weight: 600;">' . esc_html__('View Items', 'wc-all-cart-tracker') . '</summary>';
                                echo '<ul style="margin: 10px 0 0 0; padding-left: 20px;">';
                                foreach ($cart_items as $item):
                                    echo '<li>';
                                    echo esc_html($item['product_name']);
                                    echo ' × ' . esc_html($item['quantity']);
                                    echo ' (' . wc_price($item['line_total']) . ')';
                                    echo '</li>';
                                endforeach;
                                echo '</ul>';
                                echo '</details>';
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

    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="pagination-links">
                    <?php
                    // Reusing $base_url defined above for pagination links
                    if ($current_page > 1) {
                        echo '<a class="first-page button" href="' . esc_url(add_query_arg('paged', 1, $base_url)) . '">«</a> ';
                        echo '<a class="prev-page button" href="' . esc_url(add_query_arg('paged', $current_page - 1, $base_url)) . '">‹</a> ';
                    }

                    echo '<span class="paging-input">';
                    echo sprintf(
                        esc_html__('Page %1$s of %2$s', 'wc-all-cart-tracker'),
                        number_format_i18n($current_page),
                        number_format_i18n($total_pages)
                    );
                    echo '</span> ';

                    if ($current_page < $total_pages) {
                        echo '<a class="next-page button" href="' . esc_url(add_query_arg('paged', $current_page + 1, $base_url)) . '">›</a> ';
                        echo '<a class="last-page button" href="' . esc_url(add_query_arg('paged', $total_pages, $base_url)) . '">»</a>';
                    }
                    ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>