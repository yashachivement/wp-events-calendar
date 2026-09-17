<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

while ( have_posts() ) : the_post();
    $post_id    = get_the_ID();
    $meta       = WPEC_Helpers::get_event_meta( $post_id );
    $thumb_url  = get_the_post_thumbnail_url( $post_id, 'large' );
    $thumb_full = get_the_post_thumbnail_url( $post_id, 'full' );
    $maps_key   = get_option( 'wpec_google_maps_api_key', '' );
    $map_provider = get_option( 'wpec_default_map_provider', 'google' );
?>
<article id="wpec-event-<?php echo esc_attr( $post_id ); ?>" class="wpec-single-event" itemscope itemtype="https://schema.org/Event">

    <!-- Feature Image -->
    <?php if ( $thumb_url ) : ?>
    <div class="wpec-event-hero">
        <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" class="wpec-event-hero-img" itemprop="image" />
    </div>
    <?php endif; ?>

    <div class="wpec-event-wrap">

        <!-- Main Content -->
        <div class="wpec-event-main">
            <h1 class="wpec-event-title" itemprop="name"><?php the_title(); ?></h1>

            <?php
            $cats = get_the_terms( $post_id, 'wpec_event_cat' );
            if ( $cats && ! is_wp_error( $cats ) ) :
            ?>
            <div class="wpec-event-cats">
                <?php foreach ( $cats as $cat ) : ?>
                    <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="wpec-cat-badge"><?php echo esc_html( $cat->name ); ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="wpec-event-content" itemprop="description">
                <?php the_content(); ?>
            </div>

            <!-- Map -->
            <?php if ( $meta['show_map'] || get_option( 'wpec_show_map_by_default' ) ) : ?>
            <div class="wpec-event-map-section">
                <h3><?php esc_html_e( 'Location Map', 'wp-events-calendar' ); ?></h3>
                <?php if ( $meta['map_link'] ) : ?>
                    <a href="<?php echo esc_url( $meta['map_link'] ); ?>" target="_blank" rel="noopener" class="wpec-map-link-btn">
                        🗺 <?php esc_html_e( 'View on Google Maps', 'wp-events-calendar' ); ?>
                    </a>
                <?php endif; ?>
                <?php
                $full_address = implode( ', ', array_filter( [
                    $meta['venue_name'], $meta['address'], $meta['city'], $meta['state'], $meta['country']
                ] ) );
                if ( $maps_key && $full_address ) :
                    $encoded = urlencode( $full_address );
                ?>
                <div class="wpec-map-embed" id="wpec-map-<?php echo esc_attr( $post_id ); ?>" data-address="<?php echo esc_attr( $full_address ); ?>">
                    <iframe
                        src="https://www.google.com/maps/embed/v1/place?key=<?php echo esc_attr( $maps_key ); ?>&q=<?php echo $encoded; ?>"
                        width="100%" height="350" style="border:0; border-radius:8px;"
                        allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                        title="<?php esc_attr_e( 'Event location', 'wp-events-calendar' ); ?>">
                    </iframe>
                </div>
                <?php elseif ( $full_address && $map_provider === 'openstreet' ) : ?>
                <div class="wpec-map-embed">
                    <iframe
                        src="https://www.openstreetmap.org/export/embed.html?bbox=&layer=mapnik&marker=&query=<?php echo urlencode( $full_address ); ?>"
                        width="100%" height="350" style="border:0; border-radius:8px;"
                        title="<?php esc_attr_e( 'Event location', 'wp-events-calendar' ); ?>">
                    </iframe>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div><!-- .wpec-event-main -->

        <!-- Sidebar -->
        <aside class="wpec-event-sidebar">

            <!-- Date & Time -->
            <div class="wpec-sidebar-card wpec-sidebar-datetime">
                <h3><?php esc_html_e( 'Date & Time', 'wp-events-calendar' ); ?></h3>
                <div class="wpec-datetime-block">
                    <div class="wpec-icon-row">
                        <span class="wpec-icon">📅</span>
                        <div>
                            <strong><?php esc_html_e( 'Start', 'wp-events-calendar' ); ?></strong>
                            <span itemprop="startDate" content="<?php echo esc_attr( $meta['start_date'] . ( $meta['start_time'] ? 'T' . $meta['start_time'] : '' ) ); ?>">
                                <?php echo esc_html( $meta['start_formatted'] ); ?>
                            </span>
                        </div>
                    </div>
                    <?php if ( $meta['end_date'] && $meta['end_date'] !== $meta['start_date'] || $meta['end_time'] ) : ?>
                    <div class="wpec-icon-row">
                        <span class="wpec-icon">🏁</span>
                        <div>
                            <strong><?php esc_html_e( 'End', 'wp-events-calendar' ); ?></strong>
                            <span itemprop="endDate" content="<?php echo esc_attr( $meta['end_date'] . ( $meta['end_time'] ? 'T' . $meta['end_time'] : '' ) ); ?>">
                                <?php echo esc_html( $meta['end_formatted'] ); ?>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ( $meta['all_day'] ) : ?>
                    <div class="wpec-all-day-badge"><?php esc_html_e( 'All Day Event', 'wp-events-calendar' ); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Venue -->
            <?php if ( $meta['venue_name'] || $meta['address'] || $meta['city'] ) : ?>
            <div class="wpec-sidebar-card wpec-sidebar-venue" itemprop="location" itemscope itemtype="https://schema.org/Place">
                <h3><?php esc_html_e( 'Venue', 'wp-events-calendar' ); ?></h3>
                <?php if ( $meta['venue_name'] ) : ?>
                    <strong itemprop="name"><?php echo esc_html( $meta['venue_name'] ); ?></strong><br>
                <?php endif; ?>
                <div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
                    <?php if ( $meta['address'] ) : ?>
                        <span itemprop="streetAddress"><?php echo esc_html( $meta['address'] ); ?></span><br>
                    <?php endif; ?>
                    <?php
                    $city_line = array_filter( [ $meta['city'], $meta['state'], $meta['country'] ] );
                    if ( $city_line ) echo esc_html( implode( ', ', $city_line ) );
                    ?>
                </div>
                <?php if ( $meta['phone'] ) : ?>
                    <div class="wpec-icon-row" style="margin-top:.5rem;">
                        <span class="wpec-icon">📞</span>
                        <a href="tel:<?php echo esc_attr( $meta['phone'] ); ?>"><?php echo esc_html( $meta['phone'] ); ?></a>
                    </div>
                <?php endif; ?>
                <?php if ( $meta['map_link'] ) : ?>
                    <a href="<?php echo esc_url( $meta['map_link'] ); ?>" target="_blank" rel="noopener" class="wpec-directions-link">
                        🗺 <?php esc_html_e( 'Get Directions', 'wp-events-calendar' ); ?>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Organizer(s) -->
            <?php if ( ! empty( $meta['organizers'] ) ) : ?>
            <div class="wpec-sidebar-card wpec-sidebar-organizers">
                <h3><?php echo count( $meta['organizers'] ) > 1 ? esc_html__( 'Organizers', 'wp-events-calendar' ) : esc_html__( 'Organizer', 'wp-events-calendar' ); ?></h3>
                <?php foreach ( $meta['organizers'] as $org ) : ?>
                <div class="wpec-organizer-block" itemprop="organizer" itemscope itemtype="https://schema.org/Organization">
                    <strong itemprop="name"><?php echo esc_html( $org->name ); ?></strong>
                    <?php if ( $org->email ) : ?>
                        <br><a href="mailto:<?php echo esc_attr( $org->email ); ?>" itemprop="email"><?php echo esc_html( $org->email ); ?></a>
                    <?php endif; ?>
                    <?php if ( $org->phone ) : ?>
                        <br><a href="tel:<?php echo esc_attr( $org->phone ); ?>"><?php echo esc_html( $org->phone ); ?></a>
                    <?php endif; ?>
                    <?php if ( $org->website ) : ?>
                        <br><a href="<?php echo esc_url( $org->website ); ?>" target="_blank" rel="noopener" itemprop="url"><?php echo esc_html( $org->website ); ?></a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Cost -->
            <?php if ( $meta['cost'] !== '' && $meta['cost'] !== false ) : ?>
            <div class="wpec-sidebar-card wpec-sidebar-cost">
                <h3><?php esc_html_e( 'Cost', 'wp-events-calendar' ); ?></h3>
                <?php if ( (float) $meta['cost'] > 0 ) : ?>
                    <div class="wpec-cost-amount"><?php echo esc_html( WPEC_Helpers::format_price( (float) $meta['cost'], $meta['currency'] ) ); ?></div>
                <?php else : ?>
                    <div class="wpec-cost-free"><?php esc_html_e( 'Free', 'wp-events-calendar' ); ?></div>
                <?php endif; ?>
                <?php if ( $meta['cost_description'] ) : ?>
                    <small><?php echo esc_html( $meta['cost_description'] ); ?></small>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Website -->
            <?php if ( $meta['website'] ) : ?>
            <div class="wpec-sidebar-card">
                <h3><?php esc_html_e( 'Website', 'wp-events-calendar' ); ?></h3>
                <a href="<?php echo esc_url( $meta['website'] ); ?>" target="_blank" rel="noopener" class="wpec-website-link">
                    🌐 <?php echo esc_html( $meta['website'] ); ?>
                </a>
            </div>
            <?php endif; ?>

            <!-- Add to Calendar -->
            <div class="wpec-sidebar-card wpec-add-to-cal">
                <h3><?php esc_html_e( 'Add to Calendar', 'wp-events-calendar' ); ?></h3>
                <?php
                $gcal_url = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
                    . '&text='   . urlencode( get_the_title() )
                    . '&dates='  . ( $meta['start_date'] ? str_replace('-','',$meta['start_date']) . ( $meta['start_time'] ? 'T' . str_replace(':','', $meta['start_time']) . '00' : '' ) : '' )
                    . '/' . ( $meta['end_date'] ? str_replace('-','',$meta['end_date']) . ( $meta['end_time'] ? 'T' . str_replace(':','', $meta['end_time']) . '00' : '' ) : '' )
                    . '&details=' . urlencode( get_the_excerpt() )
                    . '&location=' . urlencode( implode(', ', array_filter([$meta['venue_name'], $meta['city'], $meta['country']])) );
                ?>
                <div class="wpec-cal-links">
                    <a href="<?php echo esc_url( $gcal_url ); ?>" target="_blank" rel="noopener" class="wpec-cal-link wpec-cal-google">
                        <img src="https://www.google.com/favicon.ico" width="16" height="16" alt=""> Google Calendar
                    </a>
                    <?php
                    // FIX: Use a public iCal endpoint (no admin nonce on public pages)
                    $ical_url = add_query_arg( [ 'wpec_event_ical' => $post_id, 'nonce' => wp_create_nonce( 'wpec_event_ical_' . $post_id ) ], home_url() );
                    ?>
                    <a href="<?php echo esc_url( $ical_url ); ?>" class="wpec-cal-link wpec-cal-ical">
                        📅 <?php esc_html_e( 'iCal / Outlook', 'wp-events-calendar' ); ?>
                    </a>
                </div>
            </div>

            <!-- Tickets available -->
            <?php
            $booking_global = get_option( 'wpec_booking_enabled' );
            if ( $booking_global && $meta['booking_enabled'] ) :
                $available = WPEC_Booking::get_available_tickets( $post_id );
            ?>
            <div class="wpec-sidebar-card wpec-sidebar-tickets">
                <h3><?php esc_html_e( 'Tickets', 'wp-events-calendar' ); ?></h3>
                <div class="wpec-tickets-available">
                    <?php if ( $available === 0 ) : ?>
                        <span class="wpec-sold-out"><?php esc_html_e( 'Sold Out', 'wp-events-calendar' ); ?></span>
                    <?php elseif ( is_int( $available ) ) : ?>
                        <span class="wpec-avail-count"><?php printf( _n( '%d ticket left', '%d tickets left', $available, 'wp-events-calendar' ), $available ); ?></span>
                    <?php else : ?>
                        <span class="wpec-avail-unlimited"><?php esc_html_e( 'Unlimited availability', 'wp-events-calendar' ); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ( $available !== 0 ) : ?>
                    <a href="#wpec-booking-form" class="wpec-btn wpec-btn-primary wpec-btn-block"><?php esc_html_e( 'Book Now', 'wp-events-calendar' ); ?></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </aside><!-- .wpec-event-sidebar -->

    </div><!-- .wpec-event-wrap -->

</article>
<?php
endwhile;
get_footer();
