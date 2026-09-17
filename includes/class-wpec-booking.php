<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPEC_Booking {

    public static function init(): void {
        add_action( 'wp_ajax_wpec_submit_booking',        [ __CLASS__, 'ajax_submit_booking' ] );
        add_action( 'wp_ajax_nopriv_wpec_submit_booking', [ __CLASS__, 'ajax_submit_booking' ] );
        add_action( 'wp_ajax_wpec_get_bookings',          [ __CLASS__, 'ajax_get_bookings' ] );
        add_action( 'wp_ajax_wpec_update_booking_status', [ __CLASS__, 'ajax_update_booking_status' ] );
        add_action( 'wp_ajax_wpec_delete_booking',        [ __CLASS__, 'ajax_delete_booking' ] );
    }

    // ------------------------------------------------------------------
    // Submit booking (public AJAX)
    // ------------------------------------------------------------------
    public static function ajax_submit_booking(): void {
        if ( ! check_ajax_referer( 'wpec_booking_nonce', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed.', 'wp-events-calendar' ) ] );
        }

        $data = apply_filters( 'wpec_sanitize_booking', $_POST );

        // Validate required fields
        if ( ! $data['first_name'] || ! $data['last_name'] || ! is_email( $data['email'] ) ) {
            wp_send_json_error( [ 'message' => __( 'Please fill in all required fields with a valid email.', 'wp-events-calendar' ) ] );
        }

        $event_id = absint( $data['event_id'] );
        if ( ! $event_id || get_post_type( $event_id ) !== 'wpec_event' ) {
            wp_send_json_error( [ 'message' => __( 'Invalid event.', 'wp-events-calendar' ) ] );
        }

        $meta = WPEC_Helpers::get_event_meta( $event_id );
        if ( ! $meta['booking_enabled'] ) {
            wp_send_json_error( [ 'message' => __( 'Booking is not enabled for this event.', 'wp-events-calendar' ) ] );
        }

        // Calculate total
        $cost  = (float) ( $meta['cost'] ?: 0 );
        $total = $cost * $data['tickets'];

        global $wpdb;

        // FIX BUG #6: TOCTOU race — use a DB-level transaction with SELECT ... FOR UPDATE
        // so concurrent requests cannot both pass the capacity check and overbook.
        $capacity = (int) get_post_meta( $event_id, '_wpec_tickets_capacity', true );
        if ( $capacity > 0 ) {
            $wpdb->query( 'START TRANSACTION' );
            // Lock the rows for this event so concurrent requests wait
            $booked = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COALESCE(SUM(tickets),0) FROM {$wpdb->prefix}wpec_bookings
                 WHERE event_id = %d AND booking_status IN ('confirmed','pending')
                 FOR UPDATE",
                $event_id
            ) );
            if ( ( $booked + $data['tickets'] ) > $capacity ) {
                $wpdb->query( 'ROLLBACK' );
                wp_send_json_error( [ 'message' => __( 'Not enough tickets available.', 'wp-events-calendar' ) ] );
            }
        }

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'wpec_bookings',
            [
                'event_id'       => $event_id,
                'user_id'        => get_current_user_id(),
                'first_name'     => $data['first_name'],
                'last_name'      => $data['last_name'],
                'email'          => $data['email'],
                'phone'          => $data['phone'],
                'tickets'        => $data['tickets'],
                'total_amount'   => $total,
                'currency'       => $meta['currency'],
                'payment_method' => $meta['payment_method'],
                'payment_status' => $total > 0 ? 'pending' : 'free',
                'booking_status' => $total > 0 ? 'pending' : 'confirmed',
                'notes'          => $data['notes'],
            ],
            [ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%f', '%s', '%s', '%s', '%s', '%s' ]
        );

        if ( ! $inserted ) {
            if ( $capacity > 0 ) $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( [ 'message' => __( 'Could not create booking. Please try again.', 'wp-events-calendar' ) ] );
        }

        $booking_id = $wpdb->insert_id;
        if ( $capacity > 0 ) $wpdb->query( 'COMMIT' );

        // Send confirmation email
        self::send_confirmation_email( $booking_id );

        // If paid event, redirect to payment
        if ( $total > 0 && $meta['payment_method'] ) {
            $payment_url = WPEC_Payment::get_payment_url( $booking_id, $meta['payment_method'] );
            wp_send_json_success( [
                'booking_id'  => $booking_id,
                'redirect'    => $payment_url,
                'requires_payment' => true,
            ] );
        }

        wp_send_json_success( [
            'booking_id'      => $booking_id,
            'requires_payment' => false,
            'message'         => __( 'Your booking is confirmed! A confirmation email has been sent.', 'wp-events-calendar' ),
        ] );
    }

    // ------------------------------------------------------------------
    // Admin: get bookings list
    // ------------------------------------------------------------------
    public static function ajax_get_bookings(): void {
        check_ajax_referer( 'wpec_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden' );

        global $wpdb;
        $event_id = absint( $_POST['event_id'] ?? 0 );
        $where    = $event_id ? $wpdb->prepare( 'WHERE b.event_id = %d', $event_id ) : '';

        $bookings = $wpdb->get_results(
            "SELECT b.*, p.post_title as event_title
             FROM {$wpdb->prefix}wpec_bookings b
             LEFT JOIN {$wpdb->posts} p ON b.event_id = p.ID
             {$where}
             ORDER BY b.created_at DESC
             LIMIT 200"
        );

        wp_send_json_success( $bookings );
    }

    // ------------------------------------------------------------------
    // Admin: update booking status
    // ------------------------------------------------------------------
    public static function ajax_update_booking_status(): void {
        check_ajax_referer( 'wpec_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden' );

        global $wpdb;
        $booking_id     = absint( $_POST['booking_id'] ?? 0 );
        $booking_status = sanitize_text_field( $_POST['booking_status'] ?? '' );
        $payment_status = sanitize_text_field( $_POST['payment_status'] ?? '' );

        $allowed_booking  = [ 'pending', 'confirmed', 'cancelled', 'waitlist' ];
        $allowed_payment  = [ 'pending', 'paid', 'refunded', 'failed', 'free' ];

        if ( ! in_array( $booking_status, $allowed_booking, true ) && ! in_array( $payment_status, $allowed_payment, true ) ) {
            wp_send_json_error( 'Invalid status' );
        }

        $data   = [];
        $format = [];

        if ( in_array( $booking_status, $allowed_booking, true ) ) {
            $data['booking_status'] = $booking_status;
            $format[]               = '%s';
        }
        if ( in_array( $payment_status, $allowed_payment, true ) ) {
            $data['payment_status'] = $payment_status;
            $format[]               = '%s';
        }

        $updated = $wpdb->update(
            $wpdb->prefix . 'wpec_bookings',
            $data,
            [ 'id' => $booking_id ],
            $format,
            [ '%d' ]
        );

        $updated !== false
            ? wp_send_json_success( __( 'Booking updated.', 'wp-events-calendar' ) )
            : wp_send_json_error( __( 'Update failed.', 'wp-events-calendar' ) );
    }

    // ------------------------------------------------------------------
    // Admin: delete booking
    // ------------------------------------------------------------------
    public static function ajax_delete_booking(): void {
        check_ajax_referer( 'wpec_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden' );

        global $wpdb;
        $booking_id = absint( $_POST['booking_id'] ?? 0 );
        $deleted    = $wpdb->delete( $wpdb->prefix . 'wpec_bookings', [ 'id' => $booking_id ], [ '%d' ] );
        $deleted
            ? wp_send_json_success( __( 'Booking deleted.', 'wp-events-calendar' ) )
            : wp_send_json_error( __( 'Could not delete booking.', 'wp-events-calendar' ) );
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------
    public static function get_confirmed_ticket_count( int $event_id ): int {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(tickets),0) FROM {$wpdb->prefix}wpec_bookings
             WHERE event_id = %d AND booking_status IN ('confirmed','pending')",
            $event_id
        ) );
    }

    public static function get_available_tickets( int $event_id ): int|string {
        $capacity = (int) get_post_meta( $event_id, '_wpec_tickets_capacity', true );
        if ( ! $capacity ) return __( 'Unlimited', 'wp-events-calendar' );
        $booked = self::get_confirmed_ticket_count( $event_id );
        return max( 0, $capacity - $booked );
    }

    public static function send_confirmation_email( int $booking_id ): void {
        // FIX BUG #8: guard against duplicate emails if both PayPal IPN and
        // Stripe confirm handlers fire for the same booking.
        $already_sent = get_transient( 'wpec_email_sent_' . $booking_id );
        if ( $already_sent ) return;
        set_transient( 'wpec_email_sent_' . $booking_id, 1, HOUR_IN_SECONDS );

        global $wpdb;
        $booking = $wpdb->get_row( $wpdb->prepare(
            "SELECT b.*, p.post_title as event_title FROM {$wpdb->prefix}wpec_bookings b
             LEFT JOIN {$wpdb->posts} p ON b.event_id = p.ID
             WHERE b.id = %d", $booking_id
        ) );
        if ( ! $booking ) return;

        $to      = $booking->email;
        $subject = sprintf( __( 'Booking Confirmation — %s', 'wp-events-calendar' ), $booking->event_title );
        $message = sprintf(
            __( "Hello %s,\n\nThank you for registering for %s.\n\nBooking ID: #%d\nTickets: %d\nStatus: %s\n\nWe look forward to seeing you!\n\n%s", 'wp-events-calendar' ),
            $booking->first_name,
            $booking->event_title,
            $booking->id,
            $booking->tickets,
            ucfirst( $booking->booking_status ),
            get_bloginfo( 'name' )
        );

        // FIX BUG #8: strip newlines from site name to prevent email header injection
        $from_name  = str_replace( [ "\r", "\n", '"' ], '', get_bloginfo( 'name' ) );
        $from_email = sanitize_email( get_option( 'admin_email' ) );
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
        ];

        wp_mail( $to, $subject, $message, $headers );

        // Notify admin
        wp_mail(
            get_option( 'admin_email' ),
            sprintf( __( 'New Booking for %s', 'wp-events-calendar' ), $booking->event_title ),
            sprintf( __( "New booking received.\n\nName: %s %s\nEmail: %s\nTickets: %d\nTotal: %s\n\nManage bookings: %s", 'wp-events-calendar' ),
                $booking->first_name, $booking->last_name, $booking->email,
                $booking->tickets,
                WPEC_Helpers::format_price( (float) $booking->total_amount, $booking->currency ),
                admin_url( 'admin.php?page=wpec-bookings' )
            ),
            $headers
        );
    }
}
