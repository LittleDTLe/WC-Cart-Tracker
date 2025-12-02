/**
 * Admin Scripts for WC All Cart Tracker.
 */
jQuery(document).ready(function($) {
    // Variables localized from PHP object
    const ajaxUrl = wcat_ajax.ajax_url; 
    const nonce = wcat_ajax.nonce;

    const tableBodySelector = '#wcat-active-carts-body';
    const refreshButton = $('#wcat-manual-refresh');
    
    // Helper function to update the metric cards
    function updateMetricCards(data) {
        const analytics = data.analytics;

        // Helper to find the correct value in the AJAX response
        function getValue(key) {
            // Priority: Check analytics object first
            return analytics[key] !== undefined ? analytics[key] : data[key];
        }

        // 1. Update Top 5 Key Metrics (using the stable data-key attribute)
        $('.wc-cart-metrics .wcat-value').each(function() {
            const key = $(this).data('key');
            let value = getValue(key);

            if (key) {
                if (key.includes('potential') || key.includes('avg')) {
                    // Cost values are output as formatted HTML currency strings from PHP
                    // We assume the AJAX handler is correctly returning wc_price() HTML
                    $(this).html(value); 
                } else if (key.includes('rate')) {
                    $(this).text(value + '%');
                } else {
                    $(this).text(value);
                }
            }
        });
        
        // 2. Update the helper numbers (e.g., "2 / 5 carts tracked")
        $('.wc-cart-metrics .wcat-meta-value').each(function() {
             const key = $(this).data('key');
             $(this).text(getValue(key));
        });

        
        // Update Customer Type Card (Distribution half)
        const distributionCard = $('.wc-cart-metrics-detailed .metric-card:nth-child(2)');
        distributionCard.find('div:contains("Registered Users:") strong').html(
            `${analytics.registered_carts} (${data.registeredDistribution}%)`
        );
        distributionCard.find('div:contains("Guest Users:") strong').html(
            `${analytics.guest_carts} (${data.guestDistribution}%)`
        );
        
        // Update Customer Type Card (Conversion half)
        distributionCard.find('div:contains("Registered CR:") strong').text(analytics.registered_conversion_rate + '%');
        distributionCard.find('div:contains("Guest CR:") strong').text(analytics.guest_conversion_rate + '%');

        // Update Summary Card
        const summaryCard = $('.wc-cart-metrics-detailed .metric-card:nth-child(3)');
        summaryCard.find('div:contains("Total Carts Tracked:") strong').text(analytics.total_carts);
        summaryCard.find('div:contains("Converted to Order:") strong').text(analytics.converted_carts);
        summaryCard.find('div:contains("Overall CR:") strong').text(analytics.conversion_rate + '%');
        summaryCard.find('div:contains("Abandonment Rate:") strong').text(analytics.abandonment_rate + '%');
        
        // Reset button state and provide visual feedback
        refreshButton.prop('disabled', false).text('Refresh Data');
    }

    function refreshDashboard() {
        refreshButton.prop('disabled', true).text('Refreshing...');

        // Get current sorting parameters from the table headers
        const currentSortTh = $('table.wp-list-table th.sorted');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'wcat_refresh_dashboard',
                security: nonce,
                orderby: currentSortTh.find('a').attr('href').match(/orderby=([^&]+)/)?.[1] || 'last_updated',
                order: currentSortTh.hasClass('asc') ? 'ASC' : 'DESC'
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // 1. Update Table Body
                    $(tableBodySelector).html(data.tableBody);

                    // 2. Call the stable function to update all metric cards
                    updateMetricCards(data); 
                    
                } else {
                    console.error('AJAX Refresh failed:', response);
                    refreshButton.prop('disabled', false).text('Refresh Failed');
                }
            },
            error: function(xhr) {
                console.error('AJAX Error:', xhr.responseText);
                refreshButton.prop('disabled', false).text('Refresh Failed');
            }
        });
    }

    // Attach click handler
    refreshButton.on('click', refreshDashboard);
});