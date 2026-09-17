/* global wpecMeta, jQuery */
(function ($) {
    'use strict';

    // ------------------------------------------------------------------
    // All-day toggle
    // ------------------------------------------------------------------
    $('#wpec_all_day').on('change', function () {
        $('.wpec-time-fields').toggle(!this.checked);
    });

    // ------------------------------------------------------------------
    // Booking fields toggle
    // ------------------------------------------------------------------
    $('#wpec_booking_enabled').on('change', function () {
        $('.wpec-booking-fields').toggle(this.checked);
    });

    // ------------------------------------------------------------------
    // Organizers — add existing
    // ------------------------------------------------------------------
    $('#wpec-add-organizer').on('click', function () {
        const select = $('#wpec-organizer-select');
        const id     = select.val();
        const name   = select.find('option:selected').text();
        if (!id) return;
        if ($('#wpec-organizers-list .wpec-organizer-row[data-id="' + id + '"]').length) return;

        const row = $('<div class="wpec-organizer-row" />')
            .attr('data-id', id)
            .append($('<span class="wpec-organizer-name" />').text(name))
            .append($('<input type="hidden" name="wpec_organizers[]" />').val(id))
            .append($('<button type="button" class="wpec-remove-organizer button-link-delete" />').text(wpecMeta.removeText));

        $('#wpec-organizers-list').append(row);
        select.val('');
    });

    // Remove organizer
    $(document).on('click', '.wpec-remove-organizer', function () {
        $(this).closest('.wpec-organizer-row').remove();
    });

    // ------------------------------------------------------------------
    // Organizers — create new via AJAX
    // ------------------------------------------------------------------
    $('#wpec-create-organizer').on('click', function () {
        const btn     = $(this);
        const name    = $('#wpec_new_org_name').val().trim();
        const email   = $('#wpec_new_org_email').val().trim();
        const phone   = $('#wpec_new_org_phone').val().trim();
        const website = $('#wpec_new_org_website').val().trim();

        if (!name) {
            $('#wpec-org-status').css('color', '#dc2626').text('Name is required.');
            return;
        }

        btn.prop('disabled', true).text('Creating…');
        $.post(wpecMeta.ajaxUrl, {
            action:  'wpec_create_organizer',
            nonce:   wpecMeta.nonce,
            name, email, phone, website,
        }, function (res) {
            btn.prop('disabled', false).text(wpecMeta.addOrganizerText);
            if (res.success) {
                const { id, name: orgName } = res.data;
                // Add to list
                const row = $('<div class="wpec-organizer-row" />')
                    .attr('data-id', id)
                    .append($('<span class="wpec-organizer-name" />').text(orgName))
                    .append($('<input type="hidden" name="wpec_organizers[]" />').val(id))
                    .append($('<button type="button" class="wpec-remove-organizer button-link-delete" />').text(wpecMeta.removeText));
                $('#wpec-organizers-list').append(row);
                // Also add to the select
                $('#wpec-organizer-select').append($('<option />').val(id).text(orgName));
                // Clear fields
                $('.wpec-new-org-field').val('');
                $('#wpec-org-status').css('color', '#059669').text('✓ Organizer added!');
                setTimeout(() => $('#wpec-org-status').text(''), 3000);
            } else {
                $('#wpec-org-status').css('color', '#dc2626').text(res.data || 'Error.');
            }
        });
    });

    // ------------------------------------------------------------------
    // Date validation: end date must be >= start date
    // ------------------------------------------------------------------
    $('#wpec_end_date').on('change', function () {
        const start = $('#wpec_start_date').val();
        const end   = $(this).val();
        if (start && end && end < start) {
            alert('End date cannot be before start date.');
            $(this).val(start);
        }
    });

})(jQuery);
