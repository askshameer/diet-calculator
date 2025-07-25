/**
 * Admin JavaScript for Diet Calculator Plugin
 */
jQuery(document).ready(function($) {
    // Initialize admin features
    initAdminFeatures();

    function initAdminFeatures() {
        bindEvents();
        initCharts();
        initBulkActions();
    }

    function bindEvents() {
        // Settings form validation
        $('#diet-calculator-settings-form').on('submit', validateSettingsForm);
        
        // API key toggle
        $('#show-api-key').on('click', toggleApiKeyVisibility);
        
        // Test API connection
        $('#test-api-connection').on('click', testApiConnection);
        
        // Bulk actions
        $('#doaction, #doaction2').on('click', handleBulkAction);
        
        // Select all checkbox
        $('#cb-select-all-1, #cb-select-all-2').on('change', toggleSelectAll);
        
        // Individual checkboxes
        $('input[name="plan_ids[]"]').on('change', updateBulkActionButtons);
        
        // Quick stats refresh
        $('#refresh-stats').on('click', refreshDashboardStats);
    }

    function validateSettingsForm(e) {
        const apiKey = $('input[name="huggingface_api_key"]').val();
        const enableAI = $('input[name="enable_ai"]').is(':checked');
        
        if (enableAI && !apiKey) {
            e.preventDefault();
            showAdminNotification('API key is required when AI features are enabled.', 'error');
            $('input[name="huggingface_api_key"]').focus();
            return false;
        }
        
        return true;
    }

    function toggleApiKeyVisibility() {
        const apiKeyField = $('input[name="huggingface_api_key"]');
        const currentType = apiKeyField.attr('type');
        const newType = currentType === 'password' ? 'text' : 'password';
        const buttonText = newType === 'password' ? 'Show' : 'Hide';
        
        apiKeyField.attr('type', newType);
        $(this).text(buttonText);
    }

    function testApiConnection() {
        const button = $(this);
        const originalText = button.text();
        const apiKey = $('input[name="huggingface_api_key"]').val();
        
        if (!apiKey) {
            showAdminNotification('Please enter an API key first.', 'error');
            return;
        }
        
        button.text('Testing...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'diet_calculator_test_api',
                nonce: dietCalculatorAdmin.nonce,
                api_key: apiKey
            },
            success: function(response) {
                if (response.success) {
                    showAdminNotification('API connection successful!', 'success');
                } else {
                    showAdminNotification(response.data.message || 'API connection failed.', 'error');
                }
            },
            error: function() {
                showAdminNotification('Network error. Please try again.', 'error');
            },
            complete: function() {
                button.text(originalText).prop('disabled', false);
            }
        });
    }

    function handleBulkAction(e) {
        const action = $(this).siblings('select[name="action"]').val();
        const selectedItems = $('input[name="plan_ids[]"]:checked');
        
        if (action === '-1') {
            e.preventDefault();
            showAdminNotification('Please select an action.', 'error');
            return false;
        }
        
        if (selectedItems.length === 0) {
            e.preventDefault();
            showAdminNotification('Please select at least one item.', 'error');
            return false;
        }
        
        if (action === 'delete') {
            const confirmMessage = `Are you sure you want to delete ${selectedItems.length} plan(s)? This action cannot be undone.`;
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
        }
        
        return true;
    }

    function toggleSelectAll() {
        const isChecked = $(this).is(':checked');
        $('input[name="plan_ids[]"]').prop('checked', isChecked);
        updateBulkActionButtons();
    }

    function updateBulkActionButtons() {
        const selectedCount = $('input[name="plan_ids[]"]:checked').length;
        const bulkActionButtons = $('#doaction, #doaction2');
        
        if (selectedCount > 0) {
            bulkActionButtons.prop('disabled', false);
            $('.selected-count').text(`${selectedCount} selected`);
        } else {
            bulkActionButtons.prop('disabled', true);
            $('.selected-count').text('');
        }
    }

    function refreshDashboardStats() {
        const button = $(this);
        const originalText = button.text();
        
        button.text('Refreshing...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'diet_calculator_refresh_stats',
                nonce: dietCalculatorAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateDashboardStats(response.data);
                    showAdminNotification('Stats refreshed successfully!', 'success');
                } else {
                    showAdminNotification('Failed to refresh stats.', 'error');
                }
            },
            error: function() {
                showAdminNotification('Network error. Please try again.', 'error');
            },
            complete: function() {
                button.text(originalText).prop('disabled', false);
            }
        });
    }

    function updateDashboardStats(data) {
        $('.stat-box h3').each(function() {
            const statType = $(this).parent().data('stat');
            if (data[statType]) {
                $(this).text(data[statType]);
            }
        });
    }

    function initCharts() {
        // Initialize usage charts if Chart.js is available
        if (typeof Chart !== 'undefined' && $('#usage-chart').length) {
            const ctx = $('#usage-chart')[0].getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dietCalculatorAdmin.chartData.labels,
                    datasets: [{
                        label: 'Plans Generated',
                        data: dietCalculatorAdmin.chartData.data,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 2,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    }

    function initBulkActions() {
        // Add selected count display
        if ($('.tablenav .actions').length && !$('.selected-count').length) {
            $('.tablenav .actions').append('<span class="selected-count"></span>');
        }
        
        updateBulkActionButtons();
    }

    function showAdminNotification(message, type = 'info') {
        const notification = $(`
            <div class="notice notice-${type} is-dismissible">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `);
        
        $('.wrap h1').after(notification);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            notification.fadeOut(300, () => notification.remove());
        }, 5000);
        
        // Manual dismiss
        notification.find('.notice-dismiss').on('click', () => {
            notification.fadeOut(300, () => notification.remove());
        });
    }

    // Export functionality
    $('#export-plans').on('click', function() {
        const button = $(this);
        const originalText = button.text();
        
        button.text('Exporting...').prop('disabled', true);
        
        window.location.href = ajaxurl + '?action=diet_calculator_export_plans&nonce=' + dietCalculatorAdmin.nonce;
        
        setTimeout(() => {
            button.text(originalText).prop('disabled', false);
        }, 2000);
    });

    // Auto-save settings
    let settingsTimeout;
    $('#diet-calculator-settings-form input, #diet-calculator-settings-form select').on('change', function() {
        clearTimeout(settingsTimeout);
        settingsTimeout = setTimeout(() => {
            const formData = $('#diet-calculator-settings-form').serialize();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData + '&action=diet_calculator_auto_save_settings&nonce=' + dietCalculatorAdmin.nonce,
                success: function(response) {
                    if (response.success) {
                        showAdminNotification('Settings auto-saved.', 'success');
                    }
                }
            });
        }, 1000);
    });

    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + S to save settings
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            $('#submit').click();
        }
        
        // Ctrl/Cmd + A to select all (when in plans table)
        if ((e.ctrlKey || e.metaKey) && e.key === 'a' && $('.wp-list-table').length) {
            e.preventDefault();
            $('#cb-select-all-1').prop('checked', true).trigger('change');
        }
    });

    // Plan preview modal
    $('.plan-preview').on('click', function(e) {
        e.preventDefault();
        const planId = $(this).data('plan-id');
        showPlanPreview(planId);
    });

    function showPlanPreview(planId) {
        const modal = $(`
            <div id="plan-preview-modal" class="diet-calculator-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Plan Preview</h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="loading">Loading plan details...</div>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        modal.fadeIn(300);
        
        // Load plan details
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'diet_calculator_get_plan_preview',
                plan_id: planId,
                nonce: dietCalculatorAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    modal.find('.modal-body').html(response.data.html);
                } else {
                    modal.find('.modal-body').html('<p>Error loading plan details.</p>');
                }
            },
            error: function() {
                modal.find('.modal-body').html('<p>Network error. Please try again.</p>');
            }
        });
        
        // Close modal
        modal.find('.modal-close, .modal').on('click', function(e) {
            if (e.target === this) {
                modal.fadeOut(300, () => modal.remove());
            }
        });
    }
});