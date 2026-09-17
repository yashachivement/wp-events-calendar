<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPEC_Google_Calendar {

    private static string $api_base = 'https://www.googleapis.com/calendar/v3';

    public static function init(): void {
        add_action( 'wp_ajax_wpec_gcal_auth',         [ __CLASS__, 'ajax_auth' ] );
        add_action( 'wp_ajax_wpec_gcal_disconnect',   [ __CLASS__, 'ajax_disconnect' ] );
        add_action( 'admin_action_wpec_gcal_callback', [ __CLASS__, 'handle_oauth_callback' ] );
    }

    // ------------------------------------------------------------------
    // OAuth2
    // ------------------------------------------------------------------
    public static function get_auth_url(): string {
        $client_id    = get_option( 'wpec_google_client_id', '' );
        $redirect_uri = admin_url( 'admin.php?action=wpec_gcal_callback' );
        $params = http_build_query( [
            'client_id'     => $client_id,
            'redirect_uri'  => $redirect_uri,
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/calendar',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => wp_create_nonce( 'wpec_gcal_state' ),
        ] );
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    public static function handle_oauth_callback(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );
        if ( ! isset( $_GET['state'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['state'] ) ), 'wpec_gcal_state' ) ) {
            wp_die( 'Invalid state' );
        }
        if ( isset( $_GET['error'] ) ) {
            wp_redirect( admin_url( 'admin.php?page=wpec-settings&tab=google&gcal_error=1' ) );
            exit;
        }
        $code = sanitize_text_field( wp_unslash( $_GET['code'] ?? '' ) );
        $tokens = self::exchange_code( $code );
        if ( $tokens && isset( $tokens['access_token'] ) ) {
            update_option( 'wpec_gcal_access_token',  $tokens['access_token'] );
            update_option( 'wpec_gcal_refresh_token', $tokens['refresh_token'] ?? '' );
            update_option( 'wpec_gcal_token_expires',  time() + ( $tokens['expires_in'] ?? 3600 ) );
            wp_redirect( admin_url( 'admin.php?page=wpec-settings&tab=google&gcal_connected=1' ) );
        } else {
            wp_redirect( admin_url( 'admin.php?page=wpec-settings&tab=google&gcal_error=1' ) );
        }
        exit;
    }

    private static function exchange_code( string $code ): ?array {
        $response = wp_remote_post( 'https://oauth2.googleapis.com/token', [
            'body' => [
                'code'          => $code,
                'client_id'     => get_option( 'wpec_google_client_id' ),
                'client_secret' => get_option( 'wpec_google_client_secret' ),
                'redirect_uri'  => admin_url( 'admin.php?action=wpec_gcal_callback' ),
                'grant_type'    => 'authorization_code',
            ],
        ] );
        if ( is_wp_error( $response ) ) return null;
        return json_decode( wp_remote_retrieve_body( $response ), true );
    }

    private static function get_access_token(): ?string {
        $token   = get_option( 'wpec_gcal_access_token', '' );
        $expires = (int) get_option( 'wpec_gcal_token_expires', 0 );
        if ( ! $token ) return null;
        if ( time() > $expires - 60 ) {
            $token = self::refresh_token();
        }
        return $token ?: null;
    }

    private static function refresh_token(): ?string {
        $refresh = get_option( 'wpec_gcal_refresh_token', '' );
        if ( ! $refresh ) return null;
        $response = wp_remote_post( 'https://oauth2.googleapis.com/token', [
            'body' => [
                'refresh_token' => $refresh,
                'client_id'     => get_option( 'wpec_google_client_id' ),
                'client_secret' => get_option( 'wpec_google_client_secret' ),
                'grant_type'    => 'refresh_token',
            ],
        ] );
        if ( is_wp_error( $response ) ) return null;
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $data['access_token'] ) ) {
            update_option( 'wpec_gcal_access_token', $data['access_token'] );
            update_option( 'wpec_gcal_token_expires', time() + ( $data['expires_in'] ?? 3600 ) );
            return $data['access_token'];
        }
        return null;
    }

    public static function ajax_disconnect(): void {
        check_ajax_referer( 'wpec_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
        delete_option( 'wpec_gcal_access_token' );
        delete_option( 'wpec_gcal_refresh_token' );
        delete_option( 'wpec_gcal_token_expires' );
        wp_send_json_success();
    }

    public static function ajax_auth(): void {
        check_ajax_referer( 'wpec_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
        wp_send_json_success( [ 'url' => self::get_auth_url() ] );
    }

    public static function is_connected(): bool {
        return (bool) get_option( 'wpec_gcal_access_token', '' );
    }

    // ------------------------------------------------------------------
    // Sync event to Google Calendar
    // ------------------------------------------------------------------
    public static function sync_event( int $post_id ): bool {
        $token = self::get_access_token();
        if ( ! $token ) return false;

        $cal_id = get_option( 'wpec_google_calendar_id', 'primary' );
        $meta   = WPEC_Helpers::get_event_meta( $post_id );
        $post   = get_post( $post_id );
        if ( ! $post ) return false;

        $body = self::build_gcal_event_body( $post, $meta );

        $existing_gcal_id = get_post_meta( $post_id, '_wpec_gcal_event_id', true );

        if ( $existing_gcal_id ) {
            // Update existing
            $url      = self::$api_base . '/calendars/' . rawurlencode( $cal_id ) . '/events/' . rawurlencode( $existing_gcal_id );
            $response = wp_remote_request( $url, [
                'method'  => 'PUT',
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'body' => wp_json_encode( $body ),
            ] );
        } else {
            // Create new
            $url      = self::$api_base . '/calendars/' . rawurlencode( $cal_id ) . '/events';
            $response = wp_remote_post( $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'body' => wp_json_encode( $body ),
            ] );
        }

        if ( is_wp_error( $response ) ) return false;

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $data['id'] ) ) {
            update_post_meta( $post_id, '_wpec_gcal_event_id', sanitize_text_field( $data['id'] ) );
            return true;
        }
        return false;
    }

    private static function build_gcal_event_body( \WP_Post $post, array $meta ): array {
        $tz    = get_option( 'wpec_timezone', wp_timezone_string() );
        $body  = [
            'summary'     => $post->post_title,
            'description' => wp_strip_all_tags( $post->post_content ),
            'location'    => implode( ', ', array_filter( [
                $meta['venue_name'], $meta['address'], $meta['city'], $meta['state'], $meta['country'],
            ] ) ),
        ];

        if ( $meta['all_day'] ) {
            $body['start'] = [ 'date' => $meta['start_date'] ];
            $body['end']   = [ 'date' => $meta['end_date'] ?: $meta['start_date'] ];
        } else {
            $start_dt = $meta['start_date'] . 'T' . ( $meta['start_time'] ?: '00:00' ) . ':00';
            $end_dt   = ( $meta['end_date'] ?: $meta['start_date'] ) . 'T' . ( $meta['end_time'] ?: '23:59' ) . ':00';
            $body['start'] = [ 'dateTime' => $start_dt, 'timeZone' => $tz ];
            $body['end']   = [ 'dateTime' => $end_dt,   'timeZone' => $tz ];
        }

        return $body;
    }

    // ------------------------------------------------------------------
    // Delete from Google Calendar
    // ------------------------------------------------------------------
    public static function delete_event( int $post_id ): void {
        $gcal_id = get_post_meta( $post_id, '_wpec_gcal_event_id', true );
        if ( ! $gcal_id ) return;
        $token = self::get_access_token();
        if ( ! $token ) return;
        $cal_id = get_option( 'wpec_google_calendar_id', 'primary' );
        wp_remote_request(
            self::$api_base . '/calendars/' . rawurlencode( $cal_id ) . '/events/' . rawurlencode( $gcal_id ),
            [
                'method'  => 'DELETE',
                'headers' => [ 'Authorization' => 'Bearer ' . $token ],
            ]
        );
    }
}
