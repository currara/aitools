/**
 * Admin JavaScript
 * Script for the Toolify.ai Admin Panel
 */

$(document).ready(function() {
    /**
     * Handle Submenu Toggle
     */
    $('.has-submenu').on('click', function(e) {
        e.preventDefault();
        $(this).toggleClass('active');
        $(this).next('.submenu').toggleClass('open');
    });

    /**
     * Alert Close Button
     */
    $('.alert-close').on('click', function() {
        $(this).closest('.alert').fadeOut(300, function() {
            $(this).remove();
        });
    });

    /**
     * Tabs Functionality
     */
    $('.admin-tab').on('click', function() {
        const tabId = $(this).data('tab');

        // Remove active class from all tabs and contents
        $('.admin-tab').removeClass('active');
        $('.admin-tab-content').removeClass('active');

        // Add active class to current tab and content
        $(this).addClass('active');
        $(`#${tabId}`).addClass('active');
    });

    /**
     * Language Tabs for Translation Forms
     */
    $('.language-tab').on('click', function() {
        const langCode = $(this).data('lang');

        // Remove active class from all tabs and contents
        $('.language-tab').removeClass('active');
        $('.language-content').removeClass('active');

        // Add active class to current tab and content
        $(this).addClass('active');
        $(`.language-content[data-lang="${langCode}"]`).addClass('active');
    });

    /**
     * Confirmation Dialog
     */
    $('.confirm-action').on('click', function(e) {
        if (!confirm($(this).data('confirm') || 'Czy na pewno chcesz wykonać tę akcję?')) {
            e.preventDefault();
        }
    });

    /**
     * Mobile Menu Toggle
     */
    $('.mobile-menu-toggle').on('click', function() {
        $('.admin-sidebar').toggleClass('open');
    });

    /**
     * Close sidebar when clicking outside on mobile
     */
    $(document).on('click', function(e) {
        if ($(window).width() <= 992) {
            if (!$(e.target).closest('.admin-sidebar').length &&
                !$(e.target).closest('.mobile-menu-toggle').length &&
                $('.admin-sidebar').hasClass('open')) {
                $('.admin-sidebar').removeClass('open');
            }
        }
    });

    /**
     * File Input Preview
     */
    $('.admin-form-file-input').on('change', function() {
        const file = this.files[0];
        const preview = $(this).siblings('.image-preview');

        if (file) {
            const reader = new FileReader();

            reader.onload = function(e) {
                preview.attr('src', e.target.result);
                preview.show();
            };

            reader.readAsDataURL(file);
        } else {
            preview.hide();
        }
    });

    /**
     * Slug Generation
     */
    $('.auto-slug-source').on('input', function() {
        const slugField = $($(this).data('slug-target'));

        // Only generate slug if the slug field is empty or hasn't been manually edited
        if (slugField.data('auto') !== false) {
            const slug = createSlug($(this).val());
            slugField.val(slug);
        }
    });

    // Mark slug field as manually edited if the user changes it
    $('.auto-slug').on('input', function() {
        $(this).data('auto', false);
    });

    /**
     * Create a slug from text
     */
    function createSlug(text) {
        return text
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }

    /**
     * AJAX Form Submission
     */
    $('.ajax-form').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const submitBtn = form.find('[type="submit"]');
        const originalText = submitBtn.text();
        const statusMessage = form.find('.status-message');

        // Disable button and show loading text
        submitBtn.prop('disabled', true).text('Przetwarzanie...');

        // Clear previous messages
        statusMessage.removeClass('success error').text('');

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    statusMessage.addClass('success').text(response.message);

                    // If there's a redirect URL in the response
                    if (response.redirect) {
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 1000);
                    }
                } else {
                    statusMessage.addClass('error').text(response.message || 'Wystąpił błąd. Spróbuj ponownie.');
                }
            },
            error: function() {
                statusMessage.addClass('error').text('Wystąpił błąd. Spróbuj ponownie.');
            },
            complete: function() {
                // Re-enable button and restore text
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    /**
     * Sortable Tables
     */
    if ($.fn.sortable) {
        $('.sortable-table tbody').sortable({
            handle: '.sort-handle',
            update: function(event, ui) {
                const items = [];

                // Collect the IDs and new positions
                $(this).find('tr').each(function(index) {
                    items.push({
                        id: $(this).data('id'),
                        position: index + 1
                    });
                });

                // Send the data to the server
                $.ajax({
                    url: $(this).closest('.sortable-table').data('update-url'),
                    type: 'POST',
                    data: { items: items },
                    dataType: 'json'
                });
            }
        });
    }

    /**
     * Search Filter for Tables
     */
    $('.table-search').on('input', function() {
        const searchText = $(this).val().toLowerCase();
        const tableRows = $($(this).data('table')).find('tbody tr');

        tableRows.each(function() {
            const rowText = $(this).text().toLowerCase();
            $(this).toggle(rowText.indexOf(searchText) > -1);
        });
    });

    /**
     * Bulk Actions
     */
    $('.bulk-action-form').on('submit', function(e) {
        const action = $(this).find('.bulk-action-select').val();

        if (!action) {
            e.preventDefault();
            return;
        }

        // Check if any items are selected
        const checkedItems = $(this).find('.bulk-checkbox:checked');
        if (checkedItems.length === 0) {
            e.preventDefault();
            alert('Proszę wybrać co najmniej jeden element.');
            return;
        }

        // Confirm destructive actions
        if (action === 'delete' && !confirm('Czy na pewno chcesz usunąć wybrane elementy?')) {
            e.preventDefault();
        }
    });

    // Toggle all checkboxes
    $('.bulk-checkbox-all').on('change', function() {
        const isChecked = $(this).is(':checked');
        $(this).closest('table').find('.bulk-checkbox').prop('checked', isChecked);
    });

    // Toggle "Select All" checkbox based on individual checkboxes
    $('.bulk-checkbox').on('change', function() {
        const table = $(this).closest('table');
        const allCheckboxes = table.find('.bulk-checkbox');
        const checkedCheckboxes = table.find('.bulk-checkbox:checked');

        table.find('.bulk-checkbox-all').prop(
            'checked',
            allCheckboxes.length === checkedCheckboxes.length
        );
    });
});
