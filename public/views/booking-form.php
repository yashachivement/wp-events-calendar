<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$post_id         = get_the_ID();
$meta            = WPEC_Helpers::get_event_meta( $post_id );
$available       = WPEC_Booking::get_available_tickets( $post_id );
$payment_method  = $meta['payment_method'];
$cost            = (float) $meta['cost'];
$currency        = $meta['currency'];
$gateways        = WPEC_Payment::get_enabled_gateways();
?>
<div class="wpec-booking-section" id="wpec-booking-form">
    <h2 class="wpec-booking-heading"><?php esc_html_e( 'Book Your Ticket', 'wp-events-calendar' ); ?></h2>

    <?php if ( $available === 0 ) : ?>
        <div class="wpec-notice wpec-notice-error"><?php esc_html_e( 'Sorry, this event is sold out.', 'wp-events-calendar' ); ?></div>
    <?php else : ?>

    <?php if ( isset( $_GET['wpec_booked'] ) ) : ?>
        <div class="wpec-notice wpec-notice-success"><?php esc_html_e( 'Your booking is confirmed! Check your email for details.', 'wp-events-calendar' ); ?></div>
    <?php endif; ?>

    <div id="wpec-booking-response" class="wpec-notice" style="display:none;"></div>

    <form id="wpec-booking-form-el" class="wpec-booking-form" novalidate>
        <?php wp_nonce_field( 'wpec_booking_nonce', 'wpec_booking_nonce_field' ); ?>
        <input type="hidden" name="action"   value="wpec_submit_booking" />
        <input type="hidden" name="event_id" value="<?php echo esc_attr( $post_id ); ?>" />

        <div class="wpec-form-row wpec-form-row-2col">
            <div class="wpec-form-group">
                <label for="wpec_first_name"><?php esc_html_e( 'First Name', 'wp-events-calendar' ); ?> <span class="req">*</span></label>
                <input type="text" id="wpec_first_name" name="first_name" required
                    value="<?php echo is_user_logged_in() ? esc_attr( wp_get_current_user()->first_name ) : ''; ?>" />
            </div>
            <div class="wpec-form-group">
                <label for="wpec_last_name"><?php esc_html_e( 'Last Name', 'wp-events-calendar' ); ?> <span class="req">*</span></label>
                <input type="text" id="wpec_last_name" name="last_name" required
                    value="<?php echo is_user_logged_in() ? esc_attr( wp_get_current_user()->last_name ) : ''; ?>" />
            </div>
        </div>

        <div class="wpec-form-row wpec-form-row-2col">
            <div class="wpec-form-group">
                <label for="wpec_email"><?php esc_html_e( 'Email Address', 'wp-events-calendar' ); ?> <span class="req">*</span></label>
                <input type="email" id="wpec_email" name="email" required
                    value="<?php echo is_user_logged_in() ? esc_attr( wp_get_current_user()->user_email ) : ''; ?>" />
            </div>
            <div class="wpec-form-group">
                <label for="wpec_phone"><?php esc_html_e( 'Phone (Optional)', 'wp-events-calendar' ); ?></label>
                <input type="tel" id="wpec_phone" name="phone" />
            </div>
        </div>

        <div class="wpec-form-row">
            <div class="wpec-form-group">
                <label for="wpec_tickets"><?php esc_html_e( 'Number of Tickets', 'wp-events-calendar' ); ?> <span class="req">*</span></label>
                <input type="number" id="wpec_tickets" name="tickets" value="1" min="1"
                    <?php echo is_int( $available ) ? 'max="' . esc_attr( $available ) . '"' : ''; ?>
                    class="wpec-tickets-input" />
                <?php if ( is_int( $available ) ) : ?>
                    <small class="wpec-avail-note">
                        <?php printf( _n( '%d ticket available', '%d tickets available', $available, 'wp-events-calendar' ), $available ); ?>
                    </small>
                <?php endif; ?>
            </div>

            <?php if ( $cost > 0 ) : ?>
            <div class="wpec-form-group wpec-total-wrap">
                <label><?php esc_html_e( 'Total', 'wp-events-calendar' ); ?></label>
                <div class="wpec-total-display">
                    <span id="wpec-total-amount">
                        <?php echo esc_html( WPEC_Helpers::format_price( $cost, $currency ) ); ?>
                    </span>
                    <small><?php echo esc_html( sprintf( __( '%s per ticket', 'wp-events-calendar' ), WPEC_Helpers::format_price( $cost, $currency ) ) ); ?></small>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="wpec-form-row">
            <div class="wpec-form-group">
                <label for="wpec_notes"><?php esc_html_e( 'Notes / Special Requests (Optional)', 'wp-events-calendar' ); ?></label>
                <textarea id="wpec_notes" name="notes" rows="3"></textarea>
            </div>
        </div>

        <?php if ( $cost > 0 && ! empty( $gateways ) ) : ?>
        <div class="wpec-form-row">
            <div class="wpec-form-group">
                <label><?php esc_html_e( 'Payment Method', 'wp-events-calendar' ); ?> <span class="req">*</span></label>
                <div class="wpec-gateway-options">
                    <?php foreach ( $gateways as $gw_id => $gw_label ) : ?>
                    <label class="wpec-gateway-label">
                        <input type="radio" name="payment_method" value="<?php echo esc_attr( $gw_id ); ?>"
                            <?php echo $payment_method === $gw_id ? 'checked' : ( ! $payment_method && array_key_first( $gateways ) === $gw_id ? 'checked' : '' ); ?> />
                        <span class="wpec-gateway-name"><?php echo esc_html( $gw_label ); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="wpec-form-row">
            <button type="submit" id="wpec-submit-booking" class="wpec-btn wpec-btn-primary wpec-btn-large">
                <span class="wpec-btn-text">
                    <?php echo $cost > 0
                        ? esc_html__( 'Proceed to Payment', 'wp-events-calendar' )
                        : esc_html__( 'Confirm Booking', 'wp-events-calendar' ); ?>
                </span>
                <span class="wpec-btn-spinner" style="display:none;">⏳ <?php esc_html_e( 'Processing…', 'wp-events-calendar' ); ?></span>
            </button>
        </div>

        <p class="wpec-form-privacy">
            <?php esc_html_e( 'Your information is kept private and will only be used to manage your booking.', 'wp-events-calendar' ); ?>
        </p>
    </form>

    <?php endif; ?>
</div>

<script>
(function ($) {
    var costPerTicket = <?php echo (float) $cost; ?>;
    var currency      = '<?php echo esc_js( $currency ); ?>';

    // Update total when ticket count changes
    $('#wpec_tickets').on('input change', function () {
        var qty   = parseInt($(this).val(), 10) || 1;
        var total = costPerTicket * qty;
        if (costPerTicket > 0) {
            $('#wpec-total-amount').text(formatPrice(total, currency));
        }
    });

    function formatPrice(amount, cur) {
        return cur + ' ' + amount.toFixed(2);
    }

    // Booking form submit
    $('#wpec-booking-form-el').on('submit', function (e) {
        e.preventDefault();

        var $form   = $(this);
        var $btn    = $('#wpec-submit-booking');
        var $res    = $('#wpec-booking-response');

        // Basic validation
        var valid = true;
        $form.find('[required]').each(function () {
            if (!$(this).val().trim()) {
                $(this).addClass('wpec-field-error');
                valid = false;
            } else {
                $(this).removeClass('wpec-field-error');
            }
        });
        if (!valid) {
            $res.removeClass('wpec-notice-success').addClass('wpec-notice-error')
                .text('<?php echo esc_js( __( 'Please fill in all required fields.', 'wp-events-calendar' ) ); ?>')
                .show();
            return;
        }

        $btn.prop('disabled', true).find('.wpec-btn-text').hide();
        $btn.find('.wpec-btn-spinner').show();
        $res.hide();

        var data = $form.serializeArray().reduce(function (obj, item) {
            obj[item.name] = item.value;
            return obj;
        }, {});
        data.nonce = '<?php echo esc_js( wp_create_nonce( 'wpec_booking_nonce' ) ); ?>';

        $.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', data, function (res) {
            $btn.prop('disabled', false).find('.wpec-btn-text').show();
            $btn.find('.wpec-btn-spinner').hide();

            if (res.success) {
                if (res.data.requires_payment && res.data.redirect) {
                    window.location.href = res.data.redirect;
                } else {
                    $res.removeClass('wpec-notice-error').addClass('wpec-notice-success')
                        .text(res.data.message).show();
                    $form[0].reset();
                }
            } else {
                $res.removeClass('wpec-notice-success').addClass('wpec-notice-error')
                    .text(res.data.message || '<?php echo esc_js( __( 'An error occurred. Please try again.', 'wp-events-calendar' ) ); ?>')
                    .show();
            }
        }).fail(function () {
            $btn.prop('disabled', false).find('.wpec-btn-text').show();
            $btn.find('.wpec-btn-spinner').hide();
            $res.removeClass('wpec-notice-success').addClass('wpec-notice-error')
                .text('<?php echo esc_js( __( 'Connection error. Please try again.', 'wp-events-calendar' ) ); ?>').show();
        });
    });
})(jQuery);
</script>
