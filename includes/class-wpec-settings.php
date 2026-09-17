<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPEC_Settings {

    public static function init(): void {
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function register_settings(): void {
        $options = [
            'wpec_event_slug', 'wpec_events_page_id', 'wpec_show_past_events', 'wpec_enable_comments',
            'wpec_calendar_template', 'wpec_default_view', 'wpec_events_per_page',
            'wpec_show_view_switcher', 'wpec_show_cat_filter', 'wpec_show_venue', 'wpec_show_cost', 'wpec_show_add_to_calendar',
            'wpec_date_format', 'wpec_time_format', 'wpec_timezone', 'wpec_week_starts_on',
            'wpec_currency', 'wpec_currency_position', 'wpec_default_map_provider',
            'wpec_google_maps_api_key', 'wpec_google_calendar_id',
            'wpec_google_client_id', 'wpec_google_client_secret',
            'wpec_booking_enabled', 'wpec_payment_enabled',
            'wpec_paypal_enabled', 'wpec_paypal_email', 'wpec_paypal_sandbox',
            'wpec_stripe_enabled', 'wpec_stripe_public_key', 'wpec_stripe_secret_key',
            'wpec_theme_primary_color', 'wpec_theme_secondary_color',
            'wpec_theme_text_color', 'wpec_theme_bg_color',
            'wpec_additional_content', 'wpec_show_map_by_default',
        ];
        foreach ( $options as $opt ) {
            register_setting( 'wpec_settings_group', $opt, [ 'sanitize_callback' => [ __CLASS__, 'sanitize_option_' . $opt ] ] );
        }
    }

    public static function get( string $key, mixed $default = false ): mixed {
        return get_option( $key, $default );
    }

    public static function get_all(): array {
        return [
            'event_slug'           => get_option( 'wpec_event_slug', 'event' ),
            'events_page_id'       => (int) get_option( 'wpec_events_page_id', 0 ),
            'show_past_events'     => (bool) get_option( 'wpec_show_past_events', true ),
            'enable_comments'      => (bool) get_option( 'wpec_enable_comments', false ),
            'calendar_template'    => get_option( 'wpec_calendar_template', 'classic' ),
            'default_view'         => get_option( 'wpec_default_view', 'month' ),
            'events_per_page'      => (int) get_option( 'wpec_events_per_page', 10 ),
            'show_view_switcher'   => (bool) get_option( 'wpec_show_view_switcher', true ),
            'show_cat_filter'      => (bool) get_option( 'wpec_show_cat_filter', true ),
            'show_venue'           => (bool) get_option( 'wpec_show_venue', true ),
            'show_cost'            => (bool) get_option( 'wpec_show_cost', true ),
            'show_add_to_calendar' => (bool) get_option( 'wpec_show_add_to_calendar', true ),
            'date_format'          => get_option( 'wpec_date_format', 'F j, Y' ),
            'time_format'          => get_option( 'wpec_time_format', 'g:i a' ),
            'timezone'             => get_option( 'wpec_timezone', wp_timezone_string() ),
            'week_starts_on'       => (int) get_option( 'wpec_week_starts_on', 0 ),
            'currency'             => get_option( 'wpec_currency', 'USD' ),
            'currency_position'    => get_option( 'wpec_currency_position', 'before' ),
            'default_map_provider' => get_option( 'wpec_default_map_provider', 'google' ),
            'google_maps_api_key'  => get_option( 'wpec_google_maps_api_key', '' ),
            'booking_enabled'      => (bool) get_option( 'wpec_booking_enabled', false ),
            'payment_enabled'      => (bool) get_option( 'wpec_payment_enabled', false ),
            'paypal_enabled'       => (bool) get_option( 'wpec_paypal_enabled', false ),
            'stripe_enabled'       => (bool) get_option( 'wpec_stripe_enabled', false ),
            'theme_primary_color'  => get_option( 'wpec_theme_primary_color', '#3b82f6' ),
            'theme_secondary_color'=> get_option( 'wpec_theme_secondary_color', '#1e40af' ),
            'theme_text_color'     => get_option( 'wpec_theme_text_color', '#1f2937' ),
            'theme_bg_color'       => get_option( 'wpec_theme_bg_color', '#ffffff' ),
            'additional_content'   => get_option( 'wpec_additional_content', '' ),
            'show_map_by_default'  => (bool) get_option( 'wpec_show_map_by_default', false ),
        ];
    }

    // Generic sanitizer fallback — individual sanitizers below
    public static function __callStatic( string $name, array $args ): mixed {
        if ( str_starts_with( $name, 'sanitize_option_' ) ) {
            $value = $args[0] ?? '';
            // Color fields
            if ( str_contains( $name, '_color' ) ) {
                return sanitize_hex_color( $value ) ?: '#000000';
            }
            // URL fields
            if ( str_contains( $name, '_key' ) || str_contains( $name, '_secret' ) ) {
                return sanitize_text_field( $value );
            }
            if ( str_contains( $name, 'content' ) ) {
                return wp_kses_post( $value );
            }
            return sanitize_text_field( $value );
        }
        return null;
    }
}
