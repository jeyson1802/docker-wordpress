<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<span class="ld-status ld-status-waiting ld-tertiary-background">
	<?php
	printf(
		// translators: placeholder: Date when lesson will be available.
		esc_html_x( 'Available on %s', 'placeholder: Date when lesson will be available', 'learndash' ),
		esc_html( $lesson_access_from_date )
	);
	?>
</span>
