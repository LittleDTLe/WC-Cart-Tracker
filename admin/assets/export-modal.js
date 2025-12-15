/**
 * Export Column Selection Modal Controller
 */

(function($) {
    'use strict';

    const ExportModal = {
        modal: null,
        exportType: null,
        format: null,
        filters: {},
        selectedColumns: [],
        availableColumns: {},
        templates: {},

        init() {
            this.modal = $('#wcat-export-modal');
            
            if (this.modal.length === 0) {
                console.warn('WC Cart Tracker: Export modal not found in DOM');
                return;
            }

            this.bindEvents();
            this.loadTemplates();
        },

        bindEvents() {
            // Open modal
            $(document).on('click', '[data-wcat-export]', (e) => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                this.exportType = $btn.data('wcat-export');
                this.format = $btn.data('format') || 'csv';
                
                // Parse filters from data attribute
                const filtersAttr = $btn.data('filters');
                this.filters = typeof filtersAttr === 'string' ? JSON.parse(filtersAttr) : (filtersAttr || {});
                
                this.open();
            });

            // Close modal
            $('.wcat-export-modal-close, [data-action="cancel"]').on('click', () => this.close());
            
            // Close on overlay click
            this.modal.on('click', (e) => {
                if ($(e.target).hasClass('wcat-export-modal-overlay')) {
                    this.close();
                }
            });

            // Quick actions
            $('[data-action="select-all"]').on('click', () => this.selectAll());
            $('[data-action="deselect-all"]').on('click', () => this.deselectAll());
            $('[data-action="reset-default"]').on('click', () => this.resetToDefault());

            // Column checkboxes
            $(document).on('change', '.wcat-column-checkbox', () => this.updateSelectedCount());

            // Group toggle
            $(document).on('click', '.wcat-column-group-toggle', (e) => {
                e.preventDefault();
                const $group = $(e.target).closest('.wcat-column-group');
                const $checkboxes = $group.find('.wcat-column-checkbox');
                const allChecked = $checkboxes.length === $checkboxes.filter(':checked').length;
                $checkboxes.prop('checked', !allChecked);
                this.updateSelectedCount();
            });

            // Template actions
            $('#wcat-load-template').on('click', () => this.loadTemplate());
            $('#wcat-save-template-btn').on('click', () => this.showSaveTemplateForm());
            $('#wcat-save-template-confirm').on('click', () => this.saveTemplate());
            $('#wcat-save-template-cancel').on('click', () => this.hideSaveTemplateForm());
            $('#wcat-delete-template').on('click', () => this.deleteTemplate());
            $('#wcat-template-select').on('change', () => this.onTemplateSelectChange());

            // Export confirm
            $('#wcat-export-confirm').on('click', () => this.confirmExport());

            // ESC key to close
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && this.modal.hasClass('active')) {
                    this.close();
                }
            });
        },

        open() {
            this.resetToDefault();
            this.modal.addClass('active');
            $('body').css('overflow', 'hidden');
        },

        close() {
            this.modal.removeClass('active');
            $('body').css('overflow', '');
            this.hideSaveTemplateForm();
        },

        loadTemplates() {
            if (typeof wcatExport === 'undefined') {
                console.error('WC Cart Tracker: wcatExport object not found');
                return;
            }

            $.ajax({
                url: wcatExport.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wcat_get_templates',
                    nonce: wcatExport.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.templates = response.data.templates || {};
                        this.availableColumns = response.data.columns || {};
                        this.populateTemplateSelect();
                        this.renderColumns();
                    } else {
                        console.error('Failed to load templates:', response);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error loading templates:', error);
                }
            });
        },

        populateTemplateSelect() {
            const $select = $('#wcat-template-select');
            const currentOptions = $select.find('option[value!=""][value!="_default"]');
            currentOptions.remove();

            $.each(this.templates, (id, template) => {
                const prefix = template.is_global ? '🌐 ' : '👤 ';
                $select.append(
                    $('<option>').val(id).text(prefix + template.name)
                );
            });
        },

        renderColumns() {
            const $container = $('#wcat-columns-container');
            $container.empty();

            if (Object.keys(this.availableColumns).length === 0) {
                $container.html('<p>No columns available. Please refresh the page.</p>');
                return;
            }

            // Group columns by category
            const groups = {};
            $.each(this.availableColumns, (key, config) => {
                const groupName = config.group || 'Other';
                if (!groups[groupName]) {
                    groups[groupName] = [];
                }
                groups[groupName].push({ key, ...config });
            });

            // Render each group
            $.each(groups, (groupName, columns) => {
                const $group = $('<div>').addClass('wcat-column-group');
                
                const $header = $('<div>').addClass('wcat-column-group-header');
                $header.append($('<span>').text(groupName));
                $header.append($('<a>').addClass('wcat-column-group-toggle').attr('href', '#').text('Toggle All'));
                
                const $body = $('<div>').addClass('wcat-column-group-body');
                
                $.each(columns, (i, col) => {
                    const $item = $('<div>').addClass('wcat-column-item');
                    const checkboxId = 'wcat-col-' + col.key;
                    
                    const $checkbox = $('<input>')
                        .attr('type', 'checkbox')
                        .attr('id', checkboxId)
                        .addClass('wcat-column-checkbox')
                        .val(col.key)
                        .prop('checked', col.default || false);
                    
                    const $label = $('<label>').attr('for', checkboxId);
                    $label.append($('<span>').addClass('wcat-column-label').text(col.label));
                    $label.append($('<span>').addClass('wcat-column-description').text(col.description));
                    
                    $item.append($checkbox).append($label);
                    $body.append($item);
                });
                
                $group.append($header).append($body);
                $container.append($group);
            });

            this.updateSelectedCount();
        },

        selectAll() {
            $('.wcat-column-checkbox').prop('checked', true);
            this.updateSelectedCount();
        },

        deselectAll() {
            $('.wcat-column-checkbox').prop('checked', false);
            this.updateSelectedCount();
        },

        resetToDefault() {
            const self = this;
            $('.wcat-column-checkbox').each(function() {
                const key = $(this).val();
                const config = self.availableColumns[key];
                $(this).prop('checked', config && config.default);
            });
            this.updateSelectedCount();
            $('#wcat-template-select').val('');
            $('#wcat-delete-template').hide();
        },

        updateSelectedCount() {
            const count = $('.wcat-column-checkbox:checked').length;
            $('#wcat-selected-count').text(count);
            $('#wcat-export-confirm').prop('disabled', count === 0);
        },

        getSelectedColumns() {
            const selected = [];
            $('.wcat-column-checkbox:checked').each(function() {
                selected.push($(this).val());
            });
            return selected;
        },

        loadTemplate() {
            const templateId = $('#wcat-template-select').val();
            if (!templateId) {
                return;
            }

            if (templateId === '_default') {
                this.resetToDefault();
                return;
            }

            const template = this.templates[templateId];
            if (template && template.columns) {
                $('.wcat-column-checkbox').prop('checked', false);
                $.each(template.columns, (i, colKey) => {
                    $('.wcat-column-checkbox[value="' + colKey + '"]').prop('checked', true);
                });
                this.updateSelectedCount();
            }
        },

        onTemplateSelectChange() {
            const templateId = $('#wcat-template-select').val();
            const hasTemplate = templateId && templateId !== '_default';
            $('#wcat-delete-template').toggle(hasTemplate);
        },

        showSaveTemplateForm() {
            $('#wcat-save-template-form').addClass('active');
            $('#wcat-template-name').focus();
        },

        hideSaveTemplateForm() {
            $('#wcat-save-template-form').removeClass('active');
            $('#wcat-template-name').val('');
            $('#wcat-template-global').prop('checked', false);
        },

        saveTemplate() {
            const name = $('#wcat-template-name').val().trim();
            if (!name) {
                alert('Please enter a template name');
                return;
            }

            const columns = this.getSelectedColumns();
            if (columns.length === 0) {
                alert('Please select at least one column');
                return;
            }

            const isGlobal = $('#wcat-template-global').is(':checked');

            $.ajax({
                url: wcatExport.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wcat_save_template',
                    nonce: wcatExport.nonce,
                    name: name,
                    columns: columns,
                    is_global: isGlobal
                },
                success: (response) => {
                    if (response.success) {
                        alert('Template saved successfully!');
                        this.loadTemplates();
                        this.hideSaveTemplateForm();
                    } else {
                        alert('Error: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error:', error);
                    alert('Error saving template. Please try again.');
                }
            });
        },

        deleteTemplate() {
            const templateId = $('#wcat-template-select').val();
            if (!templateId || templateId === '_default') {
                return;
            }

            if (!confirm('Are you sure you want to delete this template?')) {
                return;
            }

            $.ajax({
                url: wcatExport.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wcat_delete_template',
                    nonce: wcatExport.nonce,
                    template_id: templateId
                },
                success: (response) => {
                    if (response.success) {
                        alert('Template deleted successfully!');
                        this.loadTemplates();
                        this.resetToDefault();
                        $('#wcat-delete-template').hide();
                    } else {
                        alert('Error: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error:', error);
                    alert('Error deleting template. Please try again.');
                }
            });
        },

        confirmExport() {
            const columns = this.getSelectedColumns();
            if (columns.length === 0) {
                alert('Please select at least one column');
                return;
            }

            // Build export URL with selected columns
            const params = {
                wcat_export: this.exportType,
                format: this.format,
                columns: columns.join(','),
                _wpnonce: wcatExport.exportNonce,
                ...this.filters
            };

            const url = wcatExport.adminUrl + '?' + $.param(params);
            
            // Trigger download
            window.location.href = url;
            
            // Close modal after a short delay
            setTimeout(() => this.close(), 500);
        }
    };

    // Initialize when DOM is ready
    $(document).ready(() => {
        ExportModal.init();
    });

})(jQuery);