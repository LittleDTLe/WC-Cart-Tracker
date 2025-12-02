/**
 * Admin Scripts for WC All Cart Tracker.
 */
jQuery(document).ready(function($) {
    // Variables localized from PHP object
    const ajaxUrl = wcat_ajax.ajax_url; 
    const nonce = wcat_ajax.nonce;

    const tableBodySelector = '#wcat-active-carts-body';
    const refreshButton = $('#wcat-manual-refresh');
    
    // --- Stable Update Function ---
    function updateMetricCards(data) {
        const analytics = data.analytics;

        // 1. Update ALL Metric Values using the stable data-key attribute
        $('.wcat-value').each(function() {
            const key = $(this).data('key');
            if (!key) return; 

            let value;

            // CRITICAL FIX: Determine if the value is raw data or formatted HTML
            if (key.includes('_html')) {
                // Currency fields: Get the formatted HTML string directly from the data object
                value = data[key]; 
            } else {
                // Raw data (counts or unformatted rates)
                value = analytics[key];
            }

            if (value !== undefined) {
                if (key.includes('_html')) {
                    // FIX: Use .html() method for formatted currency strings
                    $(this).html(value); 
                } else if (key.includes('rate') || key.includes('distribution')) {
                    // Inject percentage values
                    $(this).text(value + '%');
                    
                    if (key.includes('_conversion_rate')) {
                        const color = parseFloat(value) > 0 ? '#00a32a' : '#d63638';
                        $(this).css('color', color);
                    }
                } else {
                    // Inject raw counts
                    $(this).text(value);
                }
            }
        });
        
        // 2. Update the helper numbers (e.g., "2 / 5 carts tracked")
        // This is generally for raw counts and should use .text()
        $('.wc-cart-metrics .wcat-meta-value').each(function() {
             const key = $(this).data('key');
             $(this).text(analytics[key]);
        });

        // Reset button state and provide visual feedback
        refreshButton.prop('disabled', false).text('Refresh Data');
    }

    // --- Core AJAX Logic (Remains unchanged) ---
    function refreshDashboard() {
        refreshButton.prop('disabled', true).text('Refreshing...');

        const currentSortTh = $('table.wp-list-table th.sorted');
        
        $.ajax({
            url: wcat_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wcat_refresh_dashboard',
                security: wcat_ajax.nonce,
                // Extract orderby/order from the current sorted header
                orderby: currentSortTh.find('a').attr('href').match(/orderby=([^&]+)/)?.[1] || 'last_updated',
                order: currentSortTh.hasClass('asc') ? 'ASC' : 'DESC'
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // 1. Update Table Body
                    $('#wcat-active-carts-body').html(data.tableBody);

                    // 2. Call the stable function to update all metric cards
                    updateMetricCards(data); 
                    
                } else {
                    console.error('AJAX Refresh failed (PHP response error):', response);
                    refreshButton.prop('disabled', false).text('Refresh Failed');
                }
            },
            error: function(xhr) {
                console.error('AJAX Error (Network or Server):', xhr.responseText);
                refreshButton.prop('disabled', false).text('Refresh Failed');
            }
        });
    }

    // Attach click handler
    refreshButton.on('click', refreshDashboard);
});