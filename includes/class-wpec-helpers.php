<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPEC_Helpers {

    public static function get_currencies(): array {
        return [
            'USD' => 'USD — US Dollar ($)',
            'EUR' => 'EUR — Euro (€)',
            'GBP' => 'GBP — British Pound (£)',
            'INR' => 'INR — Indian Rupee (₹)',
            'AUD' => 'AUD — Australian Dollar (A$)',
            'CAD' => 'CAD — Canadian Dollar (C$)',
            'SGD' => 'SGD — Singapore Dollar (S$)',
            'CHF' => 'CHF — Swiss Franc (CHF)',
            'JPY' => 'JPY — Japanese Yen (¥)',
            'CNY' => 'CNY — Chinese Yuan (¥)',
            'BRL' => 'BRL — Brazilian Real (R$)',
            'MXN' => 'MXN — Mexican Peso (MX$)',
            'SEK' => 'SEK — Swedish Krona (kr)',
            'NOK' => 'NOK — Norwegian Krone (kr)',
            'DKK' => 'DKK — Danish Krone (kr)',
            'NZD' => 'NZD — New Zealand Dollar (NZ$)',
            'ZAR' => 'ZAR — South African Rand (R)',
            'AED' => 'AED — UAE Dirham (د.إ)',
            'SAR' => 'SAR — Saudi Riyal (ر.س)',
            'HKD' => 'HKD — Hong Kong Dollar (HK$)',
            'KRW' => 'KRW — South Korean Won (₩)',
            'IDR' => 'IDR — Indonesian Rupiah (Rp)',
            'MYR' => 'MYR — Malaysian Ringgit (RM)',
            'THB' => 'THB — Thai Baht (฿)',
            'PHP' => 'PHP — Philippine Peso (₱)',
            'TRY' => 'TRY — Turkish Lira (₺)',
            'RUB' => 'RUB — Russian Ruble (₽)',
            'PLN' => 'PLN — Polish Złoty (zł)',
            'CZK' => 'CZK — Czech Koruna (Kč)',
            'HUF' => 'HUF — Hungarian Forint (Ft)',
        ];
    }

    public static function get_currency_symbol( string $code ): string {
        $symbols = [
            'USD' => '$',  'EUR' => '€', 'GBP' => '£', 'INR' => '₹',
            'AUD' => 'A$', 'CAD' => 'C$','SGD' => 'S$','CHF' => 'CHF',
            'JPY' => '¥',  'CNY' => '¥', 'BRL' => 'R$','MXN' => 'MX$',
            'SEK' => 'kr', 'NOK' => 'kr','DKK' => 'kr','NZD' => 'NZ$',
            'ZAR' => 'R',  'AED' => 'د.إ','SAR' => 'ر.س','HKD' => 'HK$',
            'KRW' => '₩',  'IDR' => 'Rp','MYR' => 'RM','THB' => '฿',
            'PHP' => '₱',  'TRY' => '₺','RUB' => '₽','PLN' => 'zł',
            'CZK' => 'Kč', 'HUF' => 'Ft',
        ];
        return $symbols[ $code ] ?? $code;
    }

    public static function format_price( float $amount, string $currency = '' ): string {
        if ( ! $currency ) {
            $currency = get_option( 'wpec_currency', 'USD' );
        }
        $symbol   = self::get_currency_symbol( $currency );
        $position = get_option( 'wpec_currency_position', 'before' );
        $decimals = in_array( $currency, [ 'JPY', 'KRW', 'HUF' ], true ) ? 0 : 2;
        $formatted = number_format( $amount, $decimals );
        return $position === 'before' ? $symbol . $formatted : $formatted . ' ' . $symbol;
    }

    public static function format_event_date( string $date, string $time = '', bool $all_day = false ): string {
        if ( ! $date ) return '';
        $date_format = get_option( 'wpec_date_format', 'F j, Y' );
        $time_format = get_option( 'wpec_time_format', 'g:i a' );
        $ts = strtotime( $date . ( $time ? ' ' . $time : '' ) );
        $out = date_i18n( $date_format, $ts );
        if ( ! $all_day && $time ) {
            $out .= ' ' . date_i18n( $time_format, $ts );
        }
        return $out;
    }

    public static function get_event_meta( int $event_id ): array {
        $all_day    = (bool) get_post_meta( $event_id, '_wpec_all_day', true );
        $start_date = get_post_meta( $event_id, '_wpec_start_date', true );
        $start_time = get_post_meta( $event_id, '_wpec_start_time', true );
        $end_date   = get_post_meta( $event_id, '_wpec_end_date', true );
        $end_time   = get_post_meta( $event_id, '_wpec_end_time', true );

        return [
            'start_date'        => $start_date,
            'start_time'        => $start_time,
            'end_date'          => $end_date,
            'end_time'          => $end_time,
            'all_day'           => $all_day,
            'start_formatted'   => self::format_event_date( $start_date, $start_time, $all_day ),
            'end_formatted'     => self::format_event_date( $end_date, $end_time, $all_day ),
            'venue_name'        => get_post_meta( $event_id, '_wpec_venue_name', true ),
            'address'           => get_post_meta( $event_id, '_wpec_address', true ),
            'city'              => get_post_meta( $event_id, '_wpec_city', true ),
            'state'             => get_post_meta( $event_id, '_wpec_state', true ),
            'country'           => get_post_meta( $event_id, '_wpec_country', true ),
            'show_map'          => (bool) get_post_meta( $event_id, '_wpec_show_map', true ),
            'map_link'          => get_post_meta( $event_id, '_wpec_map_link', true ),
            'website'           => get_post_meta( $event_id, '_wpec_website', true ),
            'phone'             => get_post_meta( $event_id, '_wpec_phone', true ),
            'cost'              => get_post_meta( $event_id, '_wpec_cost', true ),
            'cost_description'  => get_post_meta( $event_id, '_wpec_cost_description', true ),
            'currency'          => get_post_meta( $event_id, '_wpec_currency', true ) ?: get_option( 'wpec_currency', 'USD' ),
            'booking_enabled'   => (bool) get_post_meta( $event_id, '_wpec_booking_enabled', true ),
            'payment_method'    => get_post_meta( $event_id, '_wpec_payment_method', true ),
            'recurrence'        => get_post_meta( $event_id, '_wpec_recurrence', true ),
            'organizers'        => self::get_event_organizers( $event_id ),
        ];
    }

    public static function get_event_organizers( int $event_id ): array {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT o.* FROM {$wpdb->prefix}wpec_organizers o
             INNER JOIN {$wpdb->prefix}wpec_event_organizers eo ON o.id = eo.organizer_id
             WHERE eo.event_id = %d",
            $event_id
        ) );
    }

    public static function get_countries(): array {
        return [
            'AF' => 'Afghanistan', 'AL' => 'Albania', 'DZ' => 'Algeria', 'AR' => 'Argentina',
            'AU' => 'Australia', 'AT' => 'Austria', 'BD' => 'Bangladesh', 'BE' => 'Belgium',
            'BR' => 'Brazil', 'CA' => 'Canada', 'CL' => 'Chile', 'CN' => 'China',
            'CO' => 'Colombia', 'CZ' => 'Czech Republic', 'DK' => 'Denmark', 'EG' => 'Egypt',
            'FI' => 'Finland', 'FR' => 'France', 'DE' => 'Germany', 'GH' => 'Ghana',
            'GR' => 'Greece', 'HK' => 'Hong Kong', 'HU' => 'Hungary', 'IN' => 'India',
            'ID' => 'Indonesia', 'IR' => 'Iran', 'IQ' => 'Iraq', 'IE' => 'Ireland',
            'IL' => 'Israel', 'IT' => 'Italy', 'JP' => 'Japan', 'JO' => 'Jordan',
            'KE' => 'Kenya', 'KR' => 'South Korea', 'KW' => 'Kuwait', 'LB' => 'Lebanon',
            'MY' => 'Malaysia', 'MX' => 'Mexico', 'MA' => 'Morocco', 'NL' => 'Netherlands',
            'NZ' => 'New Zealand', 'NG' => 'Nigeria', 'NO' => 'Norway', 'PK' => 'Pakistan',
            'PE' => 'Peru', 'PH' => 'Philippines', 'PL' => 'Poland', 'PT' => 'Portugal',
            'QA' => 'Qatar', 'RO' => 'Romania', 'RU' => 'Russia', 'SA' => 'Saudi Arabia',
            'SG' => 'Singapore', 'ZA' => 'South Africa', 'ES' => 'Spain', 'SE' => 'Sweden',
            'CH' => 'Switzerland', 'TW' => 'Taiwan', 'TH' => 'Thailand', 'TR' => 'Turkey',
            'UA' => 'Ukraine', 'AE' => 'United Arab Emirates', 'GB' => 'United Kingdom',
            'US' => 'United States', 'VN' => 'Vietnam', 'YE' => 'Yemen',
        ];
    }
}
