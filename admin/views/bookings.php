<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap wpec-admin-wrap">
    <h1><?php esc_html_e( 'Bookings', 'wp-events-calendar' ); ?></h1>

    <div class="wpec-bookings-toolbar">
        <select id="wpec-filter-event">
            <option value=""><?php esc_html_e( 'All Events', 'wp-events-calendar' ); ?></option>
            <?php
            $events = get_posts( [ 'post_type' => 'wpec_event', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ] );
            foreach ( $events as $ev ) :
            ?>
                <option value="<?php echo esc_attr( $ev->ID ); ?>"><?php echo esc_html( $ev->post_title ); ?></option>
            <?php endforeach; ?>
        </select>
        <button class="button" id="wpec-load-bookings"><?php esc_html_e( 'Filter', 'wp-events-calendar' ); ?></button>
        <a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=wpec_export_csv&nonce=' . wp_create_nonce( 'wpec_admin_nonce' ) ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Export CSV', 'wp-events-calendar' ); ?></a>
    </div>

    <div id="wpec-bookings-table-wrap">
        <table class="wp-list-table widefat fixed striped" id="wpec-bookings-table">
            <thead>
                <tr>
                    <th style="width:50px"><?php esc_html_e( 'ID', 'wp-events-calendar' ); ?></th>
                    <th><?php esc_html_e( 'Name', 'wp-events-calendar' ); ?></th>
                    <th><?php esc_html_e( 'Email', 'wp-events-calendar' ); ?></th>
                    <th><?php esc_html_e( 'Event', 'wp-events-calendar' ); ?></th>
                    <th><?php esc_html_e( 'Tickets', 'wp-events-calendar' ); ?></th>
                    <th><?php esc_html_e( 'Total', 'wp-events-calendar' ); ?></th>
                    <th><?php esc_html_e( 'Payment', 'wp-events-calendar' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'wp-events-calendar' ); ?></th>
                    <th><?php esc_html_e( 'Date', 'wp-events-calendar' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'wp-events-calendar' ); ?></th>
                </tr>
            </thead>
            <tbody id="wpec-bookings-body">
                <tr><td colspan="10"><?php esc_html_e( 'Loading bookings…', 'wp-events-calendar' ); ?></td></tr>
            </tbody>
        </table>
    </div>
</div>

<script type="text/html" id="wpec-booking-row-template">
    <tr data-id="{{id}}">
        <td>#{{id}}</td>
        <td>{{first_name}} {{last_name}}</td>
        <td><a href="mailto:{{email}}">{{email}}</a></td>
        <td>{{event_title}}</td>
        <td>{{tickets}}</td>
        <td>{{total_amount}} {{currency}}</td>
        <td><span class="wpec-status-badge wpec-payment-{{payment_status}}">{{payment_status}}</span></td>
        <td>
            <select class="wpec-booking-status-select" data-id="{{id}}">
                <option value="pending" {{pending_selected}}>Pending</option>
                <option value="confirmed" {{confirmed_selected}}>Confirmed</option>
                <option value="cancelled" {{cancelled_selected}}>Cancelled</option>
                <option value="waitlist" {{waitlist_selected}}>Waitlist</option>
            </select>
        </td>
        <td>{{created_at}}</td>
        <td>
            <button class="button button-small wpec-delete-booking" data-id="{{id}}">Delete</button>
        </td>
    </tr>
</script>
