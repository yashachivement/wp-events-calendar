<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPEC_Activator {

    public static function activate(): void {
        self::create_tables();
        self::set_default_options();
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    private static function create_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $sql = [];

        // Organizers table
        $sql[] = "CREATE TABLE {$wpdb->prefix}wpec_organizers (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          name VARCHAR(255) NOT NULL,
          email VARCHAR(255) DEFAULT '',
          phone VARCHAR(100) DEFAULT '',
          website VARCHAR(255) DEFAULT '',
          description TEXT DEFAULT '',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY  (id)
        ) $charset;";

        // Event-Organizer pivot
        $sql[] = "CREATE TABLE {$wpdb->prefix}wpec_event_organizers (
          event_id BIGINT UNSIGNED NOT NULL,
          organizer_id BIGINT UNSIGNED NOT NULL,
          PRIMARY KEY  (event_id, organizer_id)
        ) $charset;";

        // Bookings table
        // FIX BUG #9: dbDelta requires:
        //   1. No "IF NOT EXISTS" — use dbDelta's own diffing
        //   2. Two spaces before KEY definitions
        //   3. PRIMARY KEY on its own line with two leading spaces
        //   4. No "ON UPDATE CURRENT_TIMESTAMP" (not supported by dbDelta parser)
        $sql[] = "CREATE TABLE {$wpdb->prefix}wpec_bookings (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          event_id BIGINT UNSIGNED NOT NULL,
          user_id BIGINT UNSIGNED DEFAULT 0,
          first_name VARCHAR(100) NOT NULL,
          last_name VARCHAR(100) NOT NULL,
          email VARCHAR(255) NOT NULL,
          phone VARCHAR(50) DEFAULT '',
          tickets INT UNSIGNED DEFAULT 1,
          total_amount DECIMAL(10,2) DEFAULT 0.00,
          currency VARCHAR(10) DEFAULT 'USD',
          payment_method VARCHAR(50) DEFAULT '',
          payment_status VARCHAR(50) DEFAULT 'pending',
          booking_status VARCHAR(50) DEFAULT 'pending',
          transaction_id VARCHAR(255) DEFAULT '',
          notes TEXT DEFAULT '',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY  (id),
          KEY event_id (event_id),
          KEY email (email)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ( $sql as $query ) {
            dbDelta( $query );
        }

        update_option( 'wpec_db_version', WPEC_VERSION );
    }

    private static function set_default_options(): void {
        $defaults = [
            'wpec_calendar_template'     => 'classic',
            'wpec_default_view'          => 'month',
            'wpec_events_per_page'       => 10,
            'wpec_date_format'           => 'F j, Y',
            'wpec_time_format'           => 'g:i a',
            'wpec_timezone'              => wp_timezone_string(),
            'wpec_week_starts_on'        => 0,
            'wpec_currency'              => 'USD',
            'wpec_currency_position'     => 'before',
            'wpec_currency_symbol'       => '$',
            'wpec_default_map_provider'  => 'google',
            'wpec_google_maps_api_key'   => '',
            'wpec_google_calendar_id'    => '',
            'wpec_google_client_id'      => '',
            'wpec_google_client_secret'  => '',
            'wpec_booking_enabled'       => false,
            'wpec_payment_enabled'       => false,
            'wpec_paypal_enabled'        => false,
            'wpec_paypal_email'          => '',
            'wpec_paypal_sandbox'        => true,
            'wpec_stripe_enabled'        => false,
            'wpec_stripe_public_key'     => '',
            'wpec_stripe_secret_key'     => '',
            'wpec_theme_primary_color'   => '#3b82f6',
            'wpec_theme_secondary_color' => '#1e40af',
            'wpec_theme_text_color'      => '#1f2937',
            'wpec_theme_bg_color'        => '#ffffff',
            'wpec_additional_content'    => '',
            'wpec_show_map_by_default'   => false,
        ];

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value );
            }
        }
    }
}
