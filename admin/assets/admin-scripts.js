/**
 * Admin Scripts for WC All Cart Tracker.
 */
jQuery(document).ready(function($) {
    // FIX: Get variables from the localized PHP object (wcat_ajax)
    const ajaxUrl = wcat_ajax.ajax_url; 
    const nonce = wcat_ajax.nonce;

    const tableBodySelector = '#wcat-active-carts-body';
    const refreshButton = $('#wcat-manual-refresh');
    
    // Function to update individual card metrics (omitted for brevity, assume it's here)

    function refreshDashboard() {
        refreshButton.prop('disabled', true).text('Refreshing...');

        $.ajax({
            url: ajaxUrl, // Now correctly pulls the URL
            type: 'POST',
            data: {
                action: 'wcat_refresh_dashboard',
                security: nonce, // Now correctly pulls the Nonce
                // Send current sorting parameters back to the server
                orderby: $('table.wp-list-table th.sorted').data('orderby'),
                order: $('table.wp-list-table th.sorted').hasClass('asc') ? 'ASC' : 'DESC'
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // 1. Update Table Body
                    $(tableBodySelector).html(data.tableBody);

                    // 2. Update Analytic Cards (You would call updateMetric function here)
                    // Example: updateMetric('conversion_rate', data.analytics.conversion_rate);
                    
                } else {
                    console.error('AJAX Refresh failed:', response);
                }
            },
            error: function(xhr) {
                console.error('AJAX Error:', xhr.responseText);
            },
            complete: function() {
                refreshButton.prop('disabled', false).text('Refresh Data');
            }
        });
    }

    // Attach click handler
    refreshButton.on('click', refreshDashboard);
});