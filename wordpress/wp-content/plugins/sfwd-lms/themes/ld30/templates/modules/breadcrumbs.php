<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fires before the breadcrumbs.
 */
do_action( 'learndash-breadcrumbs-before' ); ?>

<div class="ld-breadcrumbs-segments">
	<?php
	$breadcrumbs = learndash_get_breadcrumbs();

	/** This filter is documented in themes/ld30/includes/helpers.php */
	$keys = apply_filters(
		'learndash_breadcrumbs_keys',
		array(
			'course',
			'lesson',
			'topic',
			'current',
		)
	);

	foreach ( $keys as $key ) :
		if ( isset( $breadcrumbs[ $key ] ) ) :
			?>
			<span><a href="<?php echo esc_url( $breadcrumbs[ $key ]['permalink'] ); ?>"><?php echo esc_html( wp_strip_all_tags( $breadcrumbs[ $key ]['title'] ) ); ?></a> </span>
			<?php
		endif;
	endforeach;
	?>
</div> <!--/.ld-breadcrumbs-segments-->

<?php
/**
 * Fires after the breadcrumbs.
 */
do_action( 'learndash-breadcrumbs-after' ); ?>
