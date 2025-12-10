/**
 * Admin Scripts for WC All Cart Tracker with Pagination Support
 */
jQuery(document).ready(function($) {

    let refreshIntervalId = null; 

    // Variables localized from PHP object
    const ajaxUrl = wcat_ajax.ajax_url; 
    const nonce = wcat_ajax.nonce;
    const autoRefreshSettings = wcat_ajax.auto_refresh;
    const dashboardUrl = wcat_ajax.dashboard_url; 

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
        const totalItems = data.pagination.total_items;
        
        // Check if totalItems is 0
        if (totalItems <= 0) {
            $('.tablenav .displaying-num').text('No active carts');
            return;
        }
        const cartWord = totalItems === 1 ? 'cart' : 'carts';
        const displayText = totalItems + ' active ' + cartWord;
        
        $('.tablenav .displaying-num').text(displayText);
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
             if (analytics[key] !== undefined) {
                 $(this).text(analytics[key]);
             }
        });

        // 3. Update pagination display
        if (data.pagination) {
            updatePaginationDisplay(data);
        }
    }

    // --- Core AJAX Logic ---
    function refreshDashboard(manual_bypass = false) {
        refreshButton.prop('disabled', true).text('Refreshing...');

        const currentSortTh = $('table.wp-list-table th.sorted');
        const dateParams = getDateRangeParams();
        const paginationParams = getPaginationParams();
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'wcat_refresh_dashboard',
                security: nonce,
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
                    $(tableBodySelector).html(data.tableBody);

                    // 2. Call the stable function to update all metric cards
                    updateMetricCards(data); 
                    
                    // 3. Re-enable button with success state
                    refreshButton.prop('disabled', false).text('Refresh Data');
                    
                } else {
                    console.error('AJAX Refresh failed (PHP response error):', response);
                    refreshButton.prop('disabled', false).text('Refresh Failed - Try Again');
                    
                    // Reset button text after 3 seconds
                    setTimeout(function() {
                        refreshButton.text('Refresh Data');
                    }, 3000);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error (Network or Server):', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                
                refreshButton.prop('disabled', false).text('Refresh Failed - Try Again');
                
                // Reset button text after 3 seconds
                setTimeout(function() {
                    refreshButton.text('Refresh Data');
                }, 3000);
            },
            complete: function() {
                setTimeout(function() {
                    if (refreshButton.prop('disabled')) {
                        refreshButton.prop('disabled', false).text('Refresh Data');
                    }
                }, 100);
            }
        });
    }

    // --- Automatic Page Refresh Handler ---
    function startAutoRefresh() {
        if (refreshIntervalId !== null) {
            clearInterval(refreshIntervalId);
        }

        const pollingFunction = function () {
            if (!refreshButton.prop('disabled')) {
                refreshDashboard(false);
            }
        };

        pollingFunction();

        refreshIntervalId = setInterval(pollingFunction, autoRefreshSettings.interval);

        $(window).on('beforeunload', function () {
            clearInterval(refreshIntervalId);
        });
    }

    // Attach click handler for manual refresh
    refreshButton.on('click', function(e) {
        e.preventDefault();
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
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wcat_save_refresh_setting',
                    security: settingNonce,
                    enabled: isChecked ? 'yes' : 'no'
                },
                success: function(response) {
                    if (!response.success) {
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
    
    // ==========================================================
    // --- FILTER AND PAGINATION NAVIGATION HANDLERS ---
    // ==========================================================
    
    const perPageFilter = $('#per-page-filter');
    const daysFilter = $('#days-filter');
    const customDateRange = $('#custom-date-range');
    const applyCustomRange = $('#apply-custom-range');
    const dateFromInput = $('#date-from');
    const dateToInput = $('#date-to');
    
    // Handle days filter change (preset periods)
    daysFilter.on('change', function() {
        const value = $(this).val();
        if (value === 'custom') {
            customDateRange.show();
        } else {
            customDateRange.hide();
            // Navigate to preset period, preserving current per_page setting
            const currentPerPage = perPageFilter.val() || '50';
            window.location.href = `${dashboardUrl}&days=${value}&per_page=${currentPerPage}`;
        }
    });

    // Handle apply custom date range button
    applyCustomRange.on('click', function() {
        const dateFrom = dateFromInput.val();
        const dateTo = dateToInput.val();
        const currentPerPage = perPageFilter.val() || '50';
        
        if (!dateFrom || !dateTo) {
            alert('Please select both start and end dates.');
            return;
        }
        
        if (dateFrom > dateTo) {
            alert('Start date cannot be after end date.'); 
            return;
        }
        
        // Navigate to custom range, preserving current per_page setting
        window.location.href = `${dashboardUrl}&days=custom&date_from=${dateFrom}&date_to=${dateTo}&per_page=${currentPerPage}`;
    });

    // Handle per-page change
    perPageFilter.on('change', function() {
        const perPage = $(this).val();
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('per_page', perPage);
        currentUrl.searchParams.delete('paged'); // Reset to page 1
        window.location.href = currentUrl.toString();
    });
});