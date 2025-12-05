/**
 * Admin Scripts for WC All Cart Tracker with Pagination Support
 * Replace the existing admin-scripts.js file
 */
jQuery(document).ready(function($) {

    let refreshIntervalId = null; 

    // Variables localized from PHP object
    const ajaxUrl = wcat_ajax.ajax_url; 
    const nonce = wcat_ajax.nonce;
    const autoRefreshSettings = wcat_ajax.auto_refresh;

    const tableBodySelector = '#wcat-active-carts-body';
    const refreshButton = $('#wcat-manual-refresh');
    
    // Get current date range parameters
    function getDateRangeParams() {
        const urlParams = new URLSearchParams(window.location.search);
        const days = urlParams.get('days') || '30';
        const dateFrom = urlParams.get('date_from') || '';
        const dateTo = urlParams.get('date_to') || '';
        
        return {
            days: days,
            date_from: dateFrom,
            date_to: dateTo
        };
    }
    
    // Get current pagination parameters
    function getPaginationParams() {
        const urlParams = new URLSearchParams(window.location.search);
        const perPage = urlParams.get('per_page') || '50';
        const paged = urlParams.get('paged') || '1';
        
        return {
            per_page: perPage,
            paged: paged
        };
    }
    
    // Update pagination display
    function updatePaginationDisplay(data) {
        const pagination = data.pagination;
        
        if (pagination.total_items > 0) {
            const displayText = 'Showing ' + pagination.start_item + '-' + pagination.end_item + 
                              ' of ' + pagination.total_items + ' active carts';
            $('.tablenav .displaying-num').text(displayText);
        } else {
            $('.tablenav .displaying-num').text('No active carts');
        }
        
        // Update pagination links if they exist
        // Note: Full pagination HTML update would require more complex logic
        // For now, if pagination changes significantly, a full page reload might be better
    }
    
    // --- Stable Update Function (Used by AJAX) ---
    function updateMetricCards(data) {
        const analytics = data.analytics;

        // 1. Update ALL Metric Values using the stable data-key attribute
        $('.wcat-value').each(function() {
            const key = $(this).data('key');
            if (!key) return; 

            let value;

            // Determine source: If key has '_html', value comes from data object (formatted string)
            if (key.includes('_html')) {
                value = data[key]; 
            } else {
                // Otherwise, value comes from the raw analytics object (counts/rates)
                value = analytics[key];
            }

            if (value !== undefined) {
                if (key.includes('_html')) {
                    // Inject the formatted HTML currency string
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
        $('.wc-cart-metrics .wcat-meta-value').each(function() {
             const key = $(this).data('key');
             $(this).text(analytics[key]);
        });

        // 3. Update pagination display
        if (data.pagination) {
            updatePaginationDisplay(data);
        }

        // Reset button state and provide visual feedback
        refreshButton.prop('disabled', false).text('Refresh Data');
    }

    // --- Core AJAX Logic ---
    function refreshDashboard(manual_bypass = false) {
        refreshButton.prop('disabled', true).text('Refreshing...');

        const currentSortTh = $('table.wp-list-table th.sorted');
        const dateParams = getDateRangeParams();
        const paginationParams = getPaginationParams();
        
        $.ajax({
            url: wcat_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wcat_refresh_dashboard',
                security: wcat_ajax.nonce,
                orderby: currentSortTh.find('a').attr('href')?.match(/orderby=([^&]+)/)?.[1] || 'last_updated',
                order: currentSortTh.hasClass('asc') ? 'ASC' : 'DESC',
                bypass_cache: manual_bypass,
                days: dateParams.days,
                date_from: dateParams.date_from,
                date_to: dateParams.date_to,
                per_page: paginationParams.per_page,
                paged: paginationParams.paged
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

    refreshButton.on('click', function() {
        refreshDashboard(true); 
    });

    const pollingFunction = function () {
        if (!refreshButton.prop('disabled')) {
            refreshDashboard(false); // Pass false flag
        }
    };

    // --- Automatic Page Refresh Handler ---
    function startAutoRefresh() {
        if (refreshIntervalId !== null) {
            clearInterval(refreshIntervalId);
        }

        const pollingFunction = function () {
            if (!refreshButton.prop('disabled')) {
                refreshDashboard();
            }
        };

        pollingFunction();

        refreshIntervalId = setInterval(pollingFunction, autoRefreshSettings.interval);

        $(window).on('beforeunload', function () {
            clearInterval(refreshIntervalId);
        });
    }

    // Attach click handler for manual refresh
    refreshButton.on('click', function() {
        refreshDashboard(true);
    });

    const autoRefreshToggle = $('#wcat-auto-refresh-toggle');

    if (autoRefreshToggle.length) {
        // Function to start or stop the auto-refresh timer
        function updateRefreshState(isEnabled) {
            if (isEnabled) {
                startAutoRefresh(); 
                console.log('Auto-refresh ENABLED.');
            } else {
                clearInterval(refreshIntervalId); 
                refreshIntervalId = null;
                console.log('Auto-refresh DISABLED.');
            }
        }
        
        // Handle saving the setting via AJAX
        autoRefreshToggle.on('change', function() {
            const isChecked = $(this).is(':checked');
            const settingNonce = $(this).data('nonce');
            
            updateRefreshState(isChecked);

            $.ajax({
                url: wcat_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcat_save_refresh_setting',
                    security: settingNonce,
                    enabled: isChecked ? 'yes' : 'no'
                },
                success: function(response) {
                    if (response.success) {
                        // Success saved!
                    } else {
                        alert('Failed to save auto-refresh setting. Please check permissions.');
                        autoRefreshToggle.prop('checked', !isChecked);
                        updateRefreshState(!isChecked);
                    }
                },
                error: function() {
                    alert('Error communicating with server. Setting not saved.');
                    autoRefreshToggle.prop('checked', !isChecked);
                    updateRefreshState(!isChecked);
                }
            });
        });
    }
    
    if (autoRefreshSettings.enabled === 'yes') {
        startAutoRefresh();
    }
});