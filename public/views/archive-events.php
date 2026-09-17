<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>
<div class="wpec-archive-wrap">
    <h1 class="wpec-archive-title"><?php esc_html_e( 'Events', 'wp-events-calendar' ); ?></h1>
    <?php echo do_shortcode( '[wpec_calendar]' ); ?>
</div>
<?php
get_footer();
