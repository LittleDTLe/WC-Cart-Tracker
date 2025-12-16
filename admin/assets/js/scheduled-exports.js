/**
 * Scheduled Exports JavaScript
 */

jQuery(document).ready(function($) {
    
    // Test export button
    $('.wcat-test-export').on('click', function() {
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
            success: function(response) {
                if (response.success) {
                    alert(wcatScheduledExport.strings.test_success);
                } else {
                    alert(wcatScheduledExport.strings.test_failed);
                }
            },
            error: function() {
                alert(wcatScheduledExport.strings.test_failed);
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Delete schedule button
    $('.wcat-delete-schedule').on('click', function() {
        const scheduleId = $(this).data('schedule-id');
        
        if (!confirm(wcatScheduledExport.strings.confirm_delete)) {
            return;
        }
        
        $.ajax({
            url: wcatScheduledExport.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wcat_delete_schedule',
                nonce: wcatScheduledExport.nonce,
                schedule_id: scheduleId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to delete schedule');
                }
            },
            error: function() {
                alert('Failed to delete schedule');
            }
        });
    });
    
    // Show/hide delivery method settings
    $('#delivery_method').on('change', function() {
        const method = $(this).val();
        
        if (method === 'email') {
            $('.email-settings').show();
            $('.ftp-settings').hide();
        } else if (method === 'ftp') {
            $('.email-settings').hide();
            $('.ftp-settings').show();
        }
    }).trigger('change');
    
});