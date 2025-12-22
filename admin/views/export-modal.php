<?php
/**
 * Export Column Selection Modal View
 *
 * @package WC_All_Cart_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Export Column Selection Modal -->
<div id="wcat-export-modal" class="wcat-export-modal-overlay">
    <div class="wcat-export-modal">
        <!-- Header -->
        <div class="wcat-export-modal-header">
            <h2><?php esc_html_e('Customize Export Columns', 'wc-all-cart-tracker'); ?></h2>
            <button type="button" class="wcat-export-modal-close"
                aria-label="<?php esc_attr_e('Close', 'wc-all-cart-tracker'); ?>">×</button>
        </div>

        <!-- Body -->
        <div class="wcat-export-modal-body">
            <!-- Template Section -->
            <div class="wcat-template-section">
                <h3><?php esc_html_e('Export Templates', 'wc-all-cart-tracker'); ?></h3>
                <div class="wcat-template-controls">
                    <select id="wcat-template-select" class="wcat-template-select">
                        <option value=""><?php esc_html_e('-- Custom Selection --', 'wc-all-cart-tracker'); ?></option>
                        <option value="_default"><?php esc_html_e('Default Columns', 'wc-all-cart-tracker'); ?></option>
                    </select>

                    <button type="button" id="wcat-load-template" class="button button-secondary">
                        <?php esc_html_e('Load', 'wc-all-cart-tracker'); ?>
                    </button>

                    <button type="button" id="wcat-save-template-btn" class="button button-secondary">
                        <?php esc_html_e('Save as Template', 'wc-all-cart-tracker'); ?>
                    </button>

                    <button type="button" id="wcat-delete-template" class="button button-secondary"
                        style="display: none;">
                        <?php esc_html_e('Delete', 'wc-all-cart-tracker'); ?>
                    </button>
                </div>
            </div>

            <!-- Save Template Form (Hidden by default) -->
            <div id="wcat-save-template-form" class="wcat-save-template-form">
                <input type="text" id="wcat-template-name"
                    placeholder="<?php esc_attr_e('Template name...', 'wc-all-cart-tracker'); ?>">

                <label>
                    <input type="checkbox" id="wcat-template-global">
                    <?php esc_html_e('Make available to all users (requires admin permissions)', 'wc-all-cart-tracker'); ?>
                </label>

                <div class="wcat-save-template-actions">
                    <button type="button" id="wcat-save-template-confirm" class="button button-primary">
                        <?php esc_html_e('Save Template', 'wc-all-cart-tracker'); ?>
                    </button>
                    <button type="button" id="wcat-save-template-cancel" class="button button-secondary">
                        <?php esc_html_e('Cancel', 'wc-all-cart-tracker'); ?>
                    </button>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="wcat-quick-actions">
                <strong><?php esc_html_e('Quick Actions:', 'wc-all-cart-tracker'); ?></strong>
                <button type="button" class="button button-small" data-action="select-all">
                    <?php esc_html_e('Select All', 'wc-all-cart-tracker'); ?>
                </button>
                <button type="button" class="button button-small" data-action="deselect-all">
                    <?php esc_html_e('Deselect All', 'wc-all-cart-tracker'); ?>
                </button>
                <button type="button" class="button button-small" data-action="reset-default">
                    <?php esc_html_e('Reset to Default', 'wc-all-cart-tracker'); ?>
                </button>
            </div>

            <!-- Column Groups Container (Populated by JavaScript) -->
            <div class="wcat-columns-grid" id="wcat-columns-container">
                <!-- Columns will be rendered here by JavaScript -->
            </div>
        </div>

        <!-- Footer -->
        <div class="wcat-export-modal-footer">
            <div class="wcat-footer-left">
                <span class="wcat-selected-count">
                    <?php esc_html_e('Selected:', 'wc-all-cart-tracker'); ?>
                    <strong id="wcat-selected-count">0</strong>
                    <?php esc_html_e('columns', 'wc-all-cart-tracker'); ?>
                </span>
            </div>

            <div class="wcat-footer-right">
                <button type="button" class="button button-secondary" data-action="cancel">
                    <?php esc_html_e('Cancel', 'wc-all-cart-tracker'); ?>
                </button>

                <button type="button" class="button button-primary" id="wcat-export-confirm" disabled>
                    <?php esc_html_e('Export with Selected Columns', 'wc-all-cart-tracker'); ?>
                </button>
            </div>
        </div>
    </div>
</div>