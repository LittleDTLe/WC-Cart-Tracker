jQuery(document).ready(function ($) {
    'use strict';
    $(document).on('click', '.wcat-test-export', function (e) {
        e.preventDefault();
        
        const $button = $(this);
        const scheduleId = $button.data('schedule-id');
        const originalText = $button.text();


        if (!confirm(wcatScheduledExport.strings.confirm_test || 'Send a test export now?')) {
            return;
        }

        $button.prop('disabled', true).text(wcatScheduledExport.strings.testing || 'Testing...');

        $.ajax({
            url: wcatScheduledExport.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wcat_test_scheduled_export',
                nonce: wcatScheduledExport.nonce,
                schedule_id: scheduleId
            },
            success: function (response) {
                console.log('Test export response:', response);
                
                if (response.success) {
                    alert(response.data.message || wcatScheduledExport.strings.test_success);
                } else {
                    const message = response.data && response.data.message ? 
                        response.data.message : 
                        wcatScheduledExport.strings.test_failed;
                    alert(message);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert(wcatScheduledExport.strings.test_failed || 'Test export failed');
            },
            complete: function () {
                $button.prop('disabled', false).text(originalText);
            }
        });
    });

    // Delete schedule button - Using event delegation
    $(document).on('click', '.wcat-delete-schedule', function (e) {
        e.preventDefault();
        
        const $button = $(this);
        const scheduleId = $button.data('schedule-id');

        if (!confirm(wcatScheduledExport.strings.confirm_delete || 'Are you sure you want to delete this schedule?')) {
            return;
        }

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
                    alert(response.data.message || 'Schedule deleted successfully');
                    location.reload();
                } else {
                    const message = response.data && response.data.message ? 
                        response.data.message : 
                        'Failed to delete schedule';
                    alert(message);
                    $button.prop('disabled', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert('Failed to delete schedule. Check console for details.');
                $button.prop('disabled', false);
            }
        });
    });

    // Delivery method toggle
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
            const isDefault = $(this).data('default') === 'yes';
            $(this).prop('checked', isDefault);
        });
    });

    // Form validation
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

        console.log('Form validation passed');
        return true;
    });

    // Hover effects for column checkboxes
    $('.column-checkbox-label').hover(
        function() {
            $(this).css('background-color', '#f0f0f0');
        },
        function() {
            $(this).css('background-color', '#fff');
        }
    );

});