/**
 * Scheduled Exports JavaScript
 */

jQuery(document).ready(function ($) {
    'use strict';

    // Check if wcatScheduledExport is defined
    if (typeof wcatScheduledExport === 'undefined') {
        console.error('WC Cart Tracker: wcatScheduledExport object not found');
        return;
    }

    // Test export button
    $(document).on('click', '.wcat-test-export', function () {
        const scheduleId = $(this).data('schedule-id');
        const $button = $(this);
        const originalText = $button.text();

        if (!confirm('Send a test export now?')) {
            return;
        }

        $button.prop('disabled', true).text(wcatScheduledExport.strings.testing);

        $.ajax({
            url: wcatScheduledExport.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wcat_test_scheduled_export',
                nonce: wcatScheduledExport.nonce,
                schedule_id: scheduleId
            },
            success: function (response) {
                if (response.success) {
                    alert(wcatScheduledExport.strings.test_success);
                } else {
                    const message = response.data && response.data.message ? 
                        response.data.message : 
                        wcatScheduledExport.strings.test_failed;
                    alert(message);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
                alert(wcatScheduledExport.strings.test_failed);
            },
            complete: function () {
                $button.prop('disabled', false).text(originalText);
            }
        });
    });

    // Delete schedule button
    $(document).on('click', '.wcat-delete-schedule', function () {
        const scheduleId = $(this).data('schedule-id');

        if (!confirm(wcatScheduledExport.strings.confirm_delete)) {
            return;
        }

        const $button = $(this);
        $button.prop('disabled', true);

        $.ajax({
            url: wcatScheduledExport.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wcat_delete_schedule',
                nonce: wcatScheduledExport.nonce,
                schedule_id: scheduleId
            },
            success: function (response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to delete schedule');
                    $button.prop('disabled', false);
                }
            },
            error: function () {
                alert('Failed to delete schedule');
                $button.prop('disabled', false);
            }
        });
    });

    // Show/hide delivery method settings
    $('#delivery_method').on('change', function () {
        const method = $(this).val();

        if (method === 'email') {
            $('.email-settings').show();
            $('.ftp-settings').hide();
        } else if (method === 'ftp') {
            $('.email-settings').hide();
            $('.ftp-settings').show();
        }
    }).trigger('change');

    // Column selection quick actions
    $('#select-all-columns').on('click', function (e) {
        e.preventDefault();
        $('.wcat-columns-grid input[type="checkbox"]').prop('checked', true);
    });

    $('#deselect-all-columns').on('click', function (e) {
        e.preventDefault();
        $('.wcat-columns-grid input[type="checkbox"]').prop('checked', false);
    });

    $('#select-default-columns').on('click', function (e) {
        e.preventDefault();
        $('.wcat-columns-grid input[type="checkbox"]').each(function () {
            $(this).prop('checked', $(this).data('default') === 'yes');
        });
    });

    // Form validation before submit
    $('.wcat-schedule-form').on('submit', function (e) {
        const scheduleName = $('#schedule_name').val().trim();
        const deliveryMethod = $('#delivery_method').val();
        const emailRecipients = $('#email_recipients').val().trim();
        const selectedColumns = $('input[name="columns[]"]:checked').length;

        if (!scheduleName) {
            alert('Please enter a schedule name');
            e.preventDefault();
            return false;
        }

        if (deliveryMethod === 'email' && !emailRecipients) {
            alert('Please enter at least one email recipient');
            e.preventDefault();
            return false;
        }

        if (selectedColumns === 0) {
            alert('Please select at least one column to export');
            e.preventDefault();
            return false;
        }

        return true;
    });

    console.log('WC Cart Tracker: Scheduled Exports JS loaded successfully');
});