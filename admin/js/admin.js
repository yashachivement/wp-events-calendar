/* global wpecAdmin, jQuery */
(function ($) {
    'use strict';

    // ------------------------------------------------------------------
    // Clear cache button
    // ------------------------------------------------------------------
    $(document).on('click', '#wpec-clear-cache', function () {
        const btn = $(this);
        btn.prop('disabled', true).text('Clearing…');
        $.post(wpecAdmin.ajaxUrl, {
            action: 'wpec_clear_cache',
            nonce:  wpecAdmin.nonce,
        }, function (res) {
            btn.prop('disabled', false).text('Clear Cache');
            const msg = $('#wpec-cache-msg');
            msg.find('p').text(res.success ? wpecAdmin.i18n.cacheCleared : wpecAdmin.i18n.error);
            msg.show().delay(3000).fadeOut();
        });
    });

    // ------------------------------------------------------------------
    // Google Calendar disconnect
    // ------------------------------------------------------------------
    $(document).on('click', '#wpec-gcal-disconnect', function () {
        if (!confirm('Disconnect Google Calendar?')) return;
        $.post(wpecAdmin.ajaxUrl, {
            action: 'wpec_gcal_disconnect',
            nonce:  wpecAdmin.nonce,
        }, function () { location.reload(); });
    });

    // ------------------------------------------------------------------
    // Bookings page — load bookings
    // ------------------------------------------------------------------
    function loadBookings() {
        const eventId = $('#wpec-filter-event').val();
        const tbody   = $('#wpec-bookings-body');
        tbody.html('<tr><td colspan="10">Loading…</td></tr>');

        $.post(wpecAdmin.ajaxUrl, {
            action:   'wpec_get_bookings',
            nonce:    wpecAdmin.nonce,
            event_id: eventId,
        }, function (res) {
            if (!res.success || !res.data.length) {
                tbody.html('<tr><td colspan="10">No bookings found.</td></tr>');
                return;
            }
            const template = $('#wpec-booking-row-template').html();
            let html = '';
            res.data.forEach(function (b) {
                const statuses = ['pending','confirmed','cancelled','waitlist'];
                let row = template;
                Object.keys(b).forEach(function (key) {
                    row = row.split('{{' + key + '}}').join(b[key] || '');
                });
                statuses.forEach(function (s) {
                    row = row.split('{{' + s + '_selected}}').join(b.booking_status === s ? 'selected' : '');
                });
                html += row;
            });
            tbody.html(html);
        });
    }

    if ($('#wpec-bookings-body').length) loadBookings();
    $(document).on('click', '#wpec-load-bookings', loadBookings);

    // Booking status change
    $(document).on('change', '.wpec-booking-status-select', function () {
        const id     = $(this).data('id');
        const status = $(this).val();
        $.post(wpecAdmin.ajaxUrl, {
            action:         'wpec_update_booking_status',
            nonce:          wpecAdmin.nonce,
            booking_id:     id,
            booking_status: status,
        });
    });

    // Delete booking
    $(document).on('click', '.wpec-delete-booking', function () {
        if (!confirm(wpecAdmin.i18n.confirmDelete)) return;
        const id  = $(this).data('id');
        const row = $(this).closest('tr');
        $.post(wpecAdmin.ajaxUrl, {
            action:     'wpec_delete_booking',
            nonce:      wpecAdmin.nonce,
            booking_id: id,
        }, function (res) {
            if (res.success) row.fadeOut(300, function () { $(this).remove(); });
        });
    });

    // ------------------------------------------------------------------
    // Import / Export
    // ------------------------------------------------------------------
    function wireFileInput(inputId, btnId, action) {
        const fileInput = $('#' + inputId);
        const btn       = $('#' + btnId);

        fileInput.on('change', function () {
            btn.prop('disabled', !this.files.length);
        });

        btn.on('click', function () {
            const file = fileInput[0].files[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('action',      action);
            fd.append('nonce',       wpecAdmin.nonce);
            fd.append('import_file', file);
            btn.prop('disabled', true).text(wpecAdmin.i18n.importing);
            $.ajax({
                url:         wpecAdmin.ajaxUrl,
                type:        'POST',
                data:        fd,
                processData: false,
                contentType: false,
                success: function (res) {
                    btn.prop('disabled', false).text('Import');
                    const result = $('#wpec-import-result');
                    result.find('p').text(res.success ? res.data.message : (res.data || 'Error'));
                    result.removeClass('notice-success notice-error').addClass(res.success ? 'notice-success' : 'notice-error').show();
                    if (res.success) fileInput.val('');
                },
            });
        });
    }

    wireFileInput('wpec-csv-file',  'wpec-import-csv',  'wpec_import_csv');
    wireFileInput('wpec-ical-file', 'wpec-import-ical', 'wpec_import_ical');

    // Drag-and-drop zones
    $('.wpec-upload-zone').each(function () {
        const zone = $(this);
        zone.on('dragover', function (e) { e.preventDefault(); zone.css('border-color', '#3b82f6'); });
        zone.on('dragleave', function ()  { zone.css('border-color', ''); });
        zone.on('drop', function (e) {
            e.preventDefault();
            zone.css('border-color', '');
            const input = zone.find('input[type="file"]')[0];
            if (input && e.originalEvent.dataTransfer.files.length) {
                const dt   = new DataTransfer();
                dt.items.add(e.originalEvent.dataTransfer.files[0]);
                input.files = dt.files;
                $(input).trigger('change');
            }
        });
    });

    // Export buttons
    $('#wpec-export-csv').on('click', function () {
        window.location = wpecAdmin.ajaxUrl + '?action=wpec_export_csv&nonce=' + wpecAdmin.nonce;
    });
    $('#wpec-export-ical').on('click', function () {
        window.location = wpecAdmin.ajaxUrl + '?action=wpec_export_ical&nonce=' + wpecAdmin.nonce;
    });

})(jQuery);
