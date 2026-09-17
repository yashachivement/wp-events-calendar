<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPEC_Payment {

    public static function init(): void {
        add_action( 'init',                                  [ __CLASS__, 'register_endpoints' ] );
        add_action( 'template_redirect',                     [ __CLASS__, 'handle_payment_return' ] );
        add_action( 'template_redirect',                     [ __CLASS__, 'handle_paypal_ipn' ] );   // FIX #2: was never hooked
        add_action( 'wp_ajax_nopriv_wpec_stripe_intent',    [ __CLASS__, 'ajax_stripe_intent' ] );
        add_action( 'wp_ajax_wpec_stripe_intent',           [ __CLASS__, 'ajax_stripe_intent' ] );
        add_action( 'wp_ajax_nopriv_wpec_stripe_confirm',   [ __CLASS__, 'ajax_stripe_confirm' ] );
        add_action( 'wp_ajax_wpec_stripe_confirm',          [ __CLASS__, 'ajax_stripe_confirm' ] );
    }

    // ------------------------------------------------------------------
    // Gateway registry
    // ------------------------------------------------------------------
    public static function get_enabled_gateways(): array {
        $gateways = [];
        if ( get_option( 'wpec_paypal_enabled' ) && get_option( 'wpec_paypal_email' ) ) {
            $gateways['paypal'] = __( 'PayPal', 'wp-events-calendar' );
        }
        if ( get_option( 'wpec_stripe_enabled' ) && get_option( 'wpec_stripe_public_key' ) ) {
            $gateways['stripe'] = __( 'Credit/Debit Card (Stripe)', 'wp-events-calendar' );
        }
        return $gateways;
    }

    public static function get_payment_url( int $booking_id, string $method ): string {
        $token = WPEC_Security::generate_payment_token( $booking_id );
        return add_query_arg( [
            'wpec_pay'   => 1,
            'booking_id' => $booking_id,
            'method'     => $method,
            'token'      => $token,
        ], home_url() );
    }

    // ------------------------------------------------------------------
    // Endpoints
    // ------------------------------------------------------------------
    public static function register_endpoints(): void {
        // FIX BUG #4: also add query_vars so get_query_var() works if needed
        add_rewrite_rule( '^wpec-payment/?$',        'index.php?wpec_payment=1',        'top' );
        add_rewrite_rule( '^wpec-payment-return/?$', 'index.php?wpec_payment_return=1', 'top' );
        add_rewrite_tag( '%wpec_payment%',        '([0-9]+)' );
        add_rewrite_tag( '%wpec_payment_return%', '([0-9]+)' );
    }

    public static function handle_payment_return(): void {
        if ( ! isset( $_GET['wpec_pay'] ) ) return;

        $booking_id = absint( $_GET['booking_id'] ?? 0 );
        $method     = sanitize_text_field( $_GET['method'] ?? '' );
        $token      = sanitize_text_field( $_GET['token'] ?? '' );

        if ( ! $booking_id || ! WPEC_Security::verify_payment_token( $booking_id, $token ) ) {
            wp_die( esc_html__( 'Invalid payment link.', 'wp-events-calendar' ) );
        }

        global $wpdb;
        $booking = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpec_bookings WHERE id = %d", $booking_id
        ) );
        if ( ! $booking ) {
            wp_die( esc_html__( 'Booking not found.', 'wp-events-calendar' ) );
        }

        // FIX: Only allow payment if still pending
        if ( $booking->payment_status !== 'pending' ) {
            wp_die( esc_html__( 'This booking has already been processed.', 'wp-events-calendar' ) );
        }

        switch ( $method ) {
            case 'paypal':
                self::render_paypal_form( $booking );
                exit;
            case 'stripe':
                self::render_stripe_form( $booking );
                exit;
            default:
                wp_die( esc_html__( 'Invalid payment method.', 'wp-events-calendar' ) );
        }
    }

    // ------------------------------------------------------------------
    // PayPal Standard
    // ------------------------------------------------------------------
    private static function render_paypal_form( object $booking ): void {
        $sandbox    = get_option( 'wpec_paypal_sandbox' );
        $base_url   = $sandbox
            ? 'https://www.sandbox.paypal.com/cgi-bin/webscr'
            : 'https://www.paypal.com/cgi-bin/webscr';
        $notify_url = add_query_arg( [ 'wpec_ipn' => 1 ], home_url() );
        $return_url = add_query_arg( [ 'wpec_payment_return' => 1, 'booking_id' => $booking->id, 'gateway' => 'paypal' ], home_url() );
        $cancel_url = get_permalink( $booking->event_id ) ?: home_url();
        $event      = get_post( $booking->event_id );

        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . esc_html__( 'Redirecting to PayPal…', 'wp-events-calendar' ) . '</title></head><body>';
        echo '<p>' . esc_html__( 'Redirecting to PayPal, please wait…', 'wp-events-calendar' ) . '</p>';
        echo '<form action="' . esc_url( $base_url ) . '" method="post" id="wpec-paypal-form">';
        $fields = [
            'cmd'           => '_xclick',
            'business'      => get_option( 'wpec_paypal_email' ),
            'item_name'     => $event ? $event->post_title : 'Event Ticket',
            'item_number'   => $booking->id,
            'amount'        => number_format( (float) $booking->total_amount, 2, '.', '' ),
            'currency_code' => strtoupper( $booking->currency ),
            'quantity'      => $booking->tickets,
            'notify_url'    => $notify_url,
            'return'        => $return_url,
            'cancel_return' => $cancel_url,
            'custom'        => 'booking_' . $booking->id,
            'no_shipping'   => '1',
        ];
        foreach ( $fields as $name => $value ) {
            echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
        }
        echo '<noscript><input type="submit" value="' . esc_attr__( 'Pay with PayPal', 'wp-events-calendar' ) . '"></noscript>';
        echo '</form>';
        echo '<script>document.getElementById("wpec-paypal-form").submit();</script>';
        echo '</body></html>';
    }

    // ------------------------------------------------------------------
    // FIX #2: PayPal IPN — now properly hooked + full validation
    // ------------------------------------------------------------------
    public static function handle_paypal_ipn(): void {
        if ( ! isset( $_GET['wpec_ipn'] ) ) return;

        $raw_post = file_get_contents( 'php://input' );
        if ( empty( $raw_post ) ) {
            status_header( 400 );
            exit;
        }

        $sandbox    = get_option( 'wpec_paypal_sandbox' );
        $verify_url = $sandbox
            ? 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr'
            : 'https://ipnpb.paypal.com/cgi-bin/webscr';

        $response = wp_remote_post( $verify_url, [
            'body'    => 'cmd=_notify-validate&' . $raw_post,
            'timeout' => 30,
            'headers' => [ 'User-Agent' => 'WP-Events-Calendar/1.0' ],
        ] );

        if ( is_wp_error( $response ) || wp_remote_retrieve_body( $response ) !== 'VERIFIED' ) {
            status_header( 400 );
            exit;
        }

        parse_str( $raw_post, $ipn );

        // FIX: Validate receiver email matches our configured PayPal account
        $receiver_email = sanitize_email( $ipn['receiver_email'] ?? $ipn['business'] ?? '' );
        $our_email      = sanitize_email( get_option( 'wpec_paypal_email', '' ) );
        if ( strtolower( $receiver_email ) !== strtolower( $our_email ) ) {
            status_header( 200 ); // Always 200 to PayPal, but don't process
            exit;
        }

        $status     = sanitize_text_field( $ipn['payment_status'] ?? '' );
        $txn_id     = sanitize_text_field( $ipn['txn_id'] ?? '' );
        $mc_gross   = (float) ( $ipn['mc_gross'] ?? 0 );
        $mc_currency = strtoupper( sanitize_text_field( $ipn['mc_currency'] ?? '' ) );
        $booking_id  = absint( str_replace( 'booking_', '', $ipn['custom'] ?? '' ) );

        if ( ! $booking_id || $status !== 'Completed' || ! $txn_id ) {
            status_header( 200 );
            exit;
        }

        global $wpdb;

        // FIX: Txn ID uniqueness check — prevent replay attacks
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wpec_bookings WHERE transaction_id = %s LIMIT 1",
            $txn_id
        ) );
        if ( $existing ) {
            status_header( 200 );
            exit;
        }

        $booking = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpec_bookings WHERE id = %d AND payment_status = 'pending'",
            $booking_id
        ) );

        if ( ! $booking ) {
            status_header( 200 );
            exit;
        }

        // FIX: Validate amount and currency match the booking
        $expected_amount   = round( (float) $booking->total_amount, 2 );
        $expected_currency = strtoupper( $booking->currency );

        if ( round( $mc_gross, 2 ) < $expected_amount || $mc_currency !== $expected_currency ) {
            // Log mismatch for admin review but don't confirm
            error_log( sprintf(
                'WPEC PayPal IPN amount/currency mismatch for booking %d. Expected %s %s, got %s %s.',
                $booking_id, $expected_amount, $expected_currency, $mc_gross, $mc_currency
            ) );
            status_header( 200 );
            exit;
        }

        $wpdb->update(
            $wpdb->prefix . 'wpec_bookings',
            [
                'payment_status' => 'paid',
                'booking_status' => 'confirmed',
                'transaction_id' => $txn_id,
            ],
            [ 'id' => $booking_id ],
            [ '%s', '%s', '%s' ],
            [ '%d' ]
        );

        WPEC_Booking::send_confirmation_email( $booking_id );

        status_header( 200 );
        exit;
    }

    // ------------------------------------------------------------------
    // Stripe payment page
    // ------------------------------------------------------------------
    private static function render_stripe_form( object $booking ): void {
        $pub_key = get_option( 'wpec_stripe_public_key', '' );
        $event   = get_post( $booking->event_id );
        $token   = WPEC_Security::generate_payment_token( $booking->id );
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php esc_html_e( 'Complete Payment', 'wp-events-calendar' ); ?></title>
            <script src="https://js.stripe.com/v3/"></script>
            <style>
                body { font-family: -apple-system, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #f9fafb; }
                .wpec-stripe-wrap { background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.08); max-width: 460px; width: 100%; }
                h2 { margin: 0 0 1rem; font-size: 1.3rem; }
                #wpec-card-element { border: 1px solid #d1d5db; border-radius: 6px; padding: .75rem; margin-bottom: 1rem; }
                #wpec-stripe-submit { background: #3b82f6; color: #fff; border: none; padding: .75rem 1.5rem; border-radius: 6px; width: 100%; font-size: 1rem; cursor: pointer; }
                #wpec-stripe-submit:disabled { opacity: .6; cursor: not-allowed; }
                #wpec-card-errors { color: #dc2626; margin-bottom: .75rem; font-size: .875rem; min-height: 1.2em; }
                .wpec-amount { font-size: 1.5rem; font-weight: 700; color: #1d4ed8; margin-bottom: 1rem; }
            </style>
        </head>
        <body>
        <div class="wpec-stripe-wrap">
            <h2><?php echo esc_html( $event ? $event->post_title : '' ); ?></h2>
            <div class="wpec-amount"><?php echo esc_html( WPEC_Helpers::format_price( (float) $booking->total_amount, $booking->currency ) ); ?></div>
            <div id="wpec-card-element"></div>
            <div id="wpec-card-errors" role="alert"></div>
            <button id="wpec-stripe-submit"><?php esc_html_e( 'Pay Now', 'wp-events-calendar' ); ?></button>
        </div>
        <script>
        (function () {
            var stripe   = Stripe('<?php echo esc_js( $pub_key ); ?>');
            var elements = stripe.elements();
            var card     = elements.create('card');
            card.mount('#wpec-card-element');

            card.on('change', function (event) {
                document.getElementById('wpec-card-errors').textContent = event.error ? event.error.message : '';
            });

            document.getElementById('wpec-stripe-submit').addEventListener('click', async function () {
                var btn = document.getElementById('wpec-stripe-submit');
                var errEl = document.getElementById('wpec-card-errors');
                btn.disabled = true;
                btn.textContent = '<?php echo esc_js( __( 'Processing…', 'wp-events-calendar' ) ); ?>';
                errEl.textContent = '';

                // Step 1: create PaymentIntent on server (gated on pending status)
                var intentRes = await fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action:     'wpec_stripe_intent',
                        booking_id: '<?php echo esc_js( $booking->id ); ?>',
                        token:      '<?php echo esc_js( $token ); ?>',
                        nonce:      '<?php echo esc_js( wp_create_nonce( 'wpec_stripe_nonce' ) ); ?>',
                    })
                });
                var intentData = await intentRes.json();
                if ( ! intentData.success || ! intentData.data.client_secret ) {
                    errEl.textContent = intentData.data || '<?php echo esc_js( __( 'Could not initialize payment. Please try again.', 'wp-events-calendar' ) ); ?>';
                    btn.disabled = false;
                    btn.textContent = '<?php echo esc_js( __( 'Pay Now', 'wp-events-calendar' ) ); ?>';
                    return;
                }

                // Step 2: confirm card payment with Stripe.js
                var result = await stripe.confirmCardPayment(intentData.data.client_secret, {
                    payment_method: { card: card }
                });

                if ( result.error ) {
                    errEl.textContent = result.error.message;
                    btn.disabled = false;
                    btn.textContent = '<?php echo esc_js( __( 'Pay Now', 'wp-events-calendar' ) ); ?>';
                    return;
                }

                if ( result.paymentIntent && result.paymentIntent.status === 'succeeded' ) {
                    // Step 3: confirm server-side (server will re-verify with Stripe API)
                    var confirmRes = await fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action:            'wpec_stripe_confirm',
                            booking_id:        '<?php echo esc_js( $booking->id ); ?>',
                            payment_intent_id: result.paymentIntent.id,
                            token:             '<?php echo esc_js( $token ); ?>',
                            nonce:             '<?php echo esc_js( wp_create_nonce( 'wpec_stripe_nonce' ) ); ?>',
                        })
                    });
                    var confirmData = await confirmRes.json();
                    if ( confirmData.success ) {
                        window.location.href = '<?php echo esc_url( get_permalink( $booking->event_id ) . '?wpec_booked=1' ); ?>';
                    } else {
                        errEl.textContent = confirmData.data || '<?php echo esc_js( __( 'Payment verification failed. Please contact support.', 'wp-events-calendar' ) ); ?>';
                        btn.disabled = false;
                        btn.textContent = '<?php echo esc_js( __( 'Pay Now', 'wp-events-calendar' ) ); ?>';
                    }
                }
            });
        })();
        </script>
        </body>
        </html>
        <?php
    }

    // ------------------------------------------------------------------
    // FIX #1 (Stripe intent): gate on pending + handle Stripe API errors
    // ------------------------------------------------------------------
    public static function ajax_stripe_intent(): void {
        check_ajax_referer( 'wpec_stripe_nonce', 'nonce' );

        $booking_id = absint( $_POST['booking_id'] ?? 0 );
        $token      = sanitize_text_field( $_POST['token'] ?? '' );

        if ( ! $booking_id || ! WPEC_Security::verify_payment_token( $booking_id, $token ) ) {
            wp_send_json_error( __( 'Invalid payment token.', 'wp-events-calendar' ) );
        }

        global $wpdb;
        // FIX: Only allow intent creation when booking is still pending (prevents double-charge)
        $booking = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpec_bookings WHERE id = %d AND payment_status = 'pending'",
            $booking_id
        ) );
        if ( ! $booking ) {
            wp_send_json_error( __( 'Booking not found or already processed.', 'wp-events-calendar' ) );
        }

        $secret_key = get_option( 'wpec_stripe_secret_key', '' );
        if ( ! $secret_key ) {
            wp_send_json_error( __( 'Payment gateway not configured.', 'wp-events-calendar' ) );
        }

        $response = wp_remote_post( 'https://api.stripe.com/v1/payment_intents', [
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'amount'      => (int) round( (float) $booking->total_amount * 100 ),
                'currency'    => strtolower( $booking->currency ),
                'description' => sprintf( 'Booking #%d', $booking->id ),
                'metadata'    => [
                    'booking_id' => $booking_id,
                    'event_id'   => $booking->event_id,
                ],
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( __( 'Could not connect to payment gateway.', 'wp-events-calendar' ) );
        }

        $intent = json_decode( wp_remote_retrieve_body( $response ), true );

        // FIX: Check for Stripe API errors and missing client_secret
        if ( isset( $intent['error'] ) ) {
            wp_send_json_error( sanitize_text_field( $intent['error']['message'] ?? __( 'Stripe error.', 'wp-events-calendar' ) ) );
        }

        if ( empty( $intent['client_secret'] ) ) {
            wp_send_json_error( __( 'Invalid response from payment gateway.', 'wp-events-calendar' ) );
        }

        // Store intent ID on the booking for later verification
        $wpdb->update(
            $wpdb->prefix . 'wpec_bookings',
            [ 'transaction_id' => sanitize_text_field( $intent['id'] ) ],
            [ 'id' => $booking_id ],
            [ '%s' ],
            [ '%d' ]
        );

        wp_send_json_success( [ 'client_secret' => $intent['client_secret'] ] );
    }

    // ------------------------------------------------------------------
    // FIX #1 (Stripe confirm): server-side verify with Stripe API
    // ------------------------------------------------------------------
    public static function ajax_stripe_confirm(): void {
        check_ajax_referer( 'wpec_stripe_nonce', 'nonce' );

        $booking_id        = absint( $_POST['booking_id'] ?? 0 );
        $token             = sanitize_text_field( $_POST['token'] ?? '' );
        $payment_intent_id = sanitize_text_field( $_POST['payment_intent_id'] ?? '' );

        if ( ! $booking_id || ! WPEC_Security::verify_payment_token( $booking_id, $token ) ) {
            wp_send_json_error( __( 'Invalid payment token.', 'wp-events-calendar' ) );
        }

        if ( ! $payment_intent_id || ! str_starts_with( $payment_intent_id, 'pi_' ) ) {
            wp_send_json_error( __( 'Invalid payment reference.', 'wp-events-calendar' ) );
        }

        global $wpdb;
        // FIX: Only proceed if still pending
        $booking = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpec_bookings WHERE id = %d AND payment_status = 'pending'",
            $booking_id
        ) );
        if ( ! $booking ) {
            wp_send_json_error( __( 'Booking not found or already processed.', 'wp-events-calendar' ) );
        }

        // FIX: Retrieve the PaymentIntent from Stripe to verify it actually succeeded
        $secret_key = get_option( 'wpec_stripe_secret_key', '' );
        $response   = wp_remote_get(
            'https://api.stripe.com/v1/payment_intents/' . rawurlencode( $payment_intent_id ),
            [
                'headers' => [ 'Authorization' => 'Bearer ' . $secret_key ],
                'timeout' => 20,
            ]
        );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( __( 'Could not verify payment with Stripe.', 'wp-events-calendar' ) );
        }

        $intent = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $intent['error'] ) || empty( $intent['id'] ) ) {
            wp_send_json_error( __( 'Payment verification failed.', 'wp-events-calendar' ) );
        }

        // FIX: Verify status is succeeded
        if ( $intent['status'] !== 'succeeded' ) {
            wp_send_json_error( sprintf(
                __( 'Payment not completed. Status: %s', 'wp-events-calendar' ),
                sanitize_text_field( $intent['status'] )
            ) );
        }

        // FIX: Verify the amount and currency match the booking (prevent amount tampering)
        $expected_amount   = (int) round( (float) $booking->total_amount * 100 );
        $expected_currency = strtolower( $booking->currency );

        if ( (int) $intent['amount'] < $expected_amount || $intent['currency'] !== $expected_currency ) {
            error_log( sprintf(
                'WPEC Stripe amount/currency mismatch for booking %d. Expected %d %s, got %d %s.',
                $booking_id,
                $expected_amount, $expected_currency,
                $intent['amount'], $intent['currency']
            ) );
            wp_send_json_error( __( 'Payment amount mismatch. Please contact support.', 'wp-events-calendar' ) );
        }

        // FIX: Verify the intent belongs to this booking via metadata
        $meta_booking_id = absint( $intent['metadata']['booking_id'] ?? 0 );
        if ( $meta_booking_id && $meta_booking_id !== $booking_id ) {
            wp_send_json_error( __( 'Payment reference mismatch.', 'wp-events-calendar' ) );
        }

        // All checks passed — mark as paid
        $wpdb->update(
            $wpdb->prefix . 'wpec_bookings',
            [
                'payment_status' => 'paid',
                'booking_status' => 'confirmed',
                'transaction_id' => sanitize_text_field( $intent['id'] ),
            ],
            [ 'id' => $booking_id ],
            [ '%s', '%s', '%s' ],
            [ '%d' ]
        );

        WPEC_Booking::send_confirmation_email( $booking_id );
        wp_send_json_success( [ 'message' => __( 'Payment confirmed.', 'wp-events-calendar' ) ] );
    }
}
