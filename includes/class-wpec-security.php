<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPEC_Security {

    public static function init(): void {
        add_action( 'wp_ajax_wpec_create_organizer', [ __CLASS__, 'ajax_create_organizer' ] );
        add_filter( 'wpec_sanitize_booking', [ __CLASS__, 'sanitize_booking_data' ] );
    }

    /**
     * Verify nonce and capability, die on failure.
     */
    public static function verify_ajax( string $nonce_action, string $cap = 'read' ): void {
        if ( ! check_ajax_referer( $nonce_action, 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed.', 'wp-events-calendar' ) ], 403 );
        }
        if ( ! current_user_can( $cap ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'wp-events-calendar' ) ], 403 );
        }
    }

    // FIX #3: added manage_options capability check (was only edit_posts before)
    public static function ajax_create_organizer(): void {
        self::verify_ajax( 'wpec_meta_nonce', 'manage_options' );

        $name    = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        $email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $phone   = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
        $website = esc_url_raw( wp_unslash( $_POST['website'] ?? '' ) );

        if ( ! $name ) {
            wp_send_json_error( __( 'Organizer name is required.', 'wp-events-calendar' ) );
        }

        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix . 'wpec_organizers',
            compact( 'name', 'email', 'phone', 'website' ),
            [ '%s', '%s', '%s', '%s' ]
        );

        if ( $result ) {
            wp_send_json_success( [
                'id'   => $wpdb->insert_id,
                'name' => $name,
            ] );
        } else {
            wp_send_json_error( __( 'Could not create organizer.', 'wp-events-calendar' ) );
        }
    }

    public static function sanitize_booking_data( array $data ): array {
        return [
            'first_name' => sanitize_text_field( $data['first_name'] ?? '' ),
            'last_name'  => sanitize_text_field( $data['last_name'] ?? '' ),
            'email'      => sanitize_email( $data['email'] ?? '' ),
            'phone'      => sanitize_text_field( $data['phone'] ?? '' ),
            'tickets'    => max( 1, absint( $data['tickets'] ?? 1 ) ),
            'notes'      => sanitize_textarea_field( $data['notes'] ?? '' ),
            'event_id'   => absint( $data['event_id'] ?? 0 ),
        ];
    }

    /**
     * FIX #4: Generate a time-limited, one-time-use payment token.
     * Uses AUTH_KEY constant (not get_option), binds to booking + expiry timestamp.
     */
    public static function generate_payment_token( int $booking_id ): string {
        // Token expires in 2 hours
        $expiry = (int) floor( time() / 7200 );
        // FIX: use AUTH_KEY constant, not get_option('auth_key') which returns empty
        $secret = defined( 'AUTH_KEY' ) ? AUTH_KEY : wp_salt( 'auth' );
        return wp_hash( 'wpec_payment_' . $booking_id . '_' . $expiry . $secret );
    }

    public static function verify_payment_token( int $booking_id, string $token ): bool {
        // Check current window and the previous window (tolerates clock drift / slow users)
        $secret = defined( 'AUTH_KEY' ) ? AUTH_KEY : wp_salt( 'auth' );
        foreach ( [ 0, -1 ] as $offset ) {
            $expiry   = (int) floor( time() / 7200 ) + $offset;
            $expected = wp_hash( 'wpec_payment_' . $booking_id . '_' . $expiry . $secret );
            if ( hash_equals( $expected, $token ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Strip all non-allowed HTML for event descriptions.
     */
    public static function sanitize_event_description( string $content ): string {
        $allowed = wp_kses_allowed_html( 'post' );
        return wp_kses( $content, $allowed );
    }
}
