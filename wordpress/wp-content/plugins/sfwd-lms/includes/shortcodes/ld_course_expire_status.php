<?php
/**
 * Shortcode for ld_course_expire_status
 *
 * @since 2.1.0
 *
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `ld_course_expire_status` shortcode output.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @since 2.1.0
 *
 * @param array  $atts {
 *    An array of shortcode attributes.
 *
 *    @type int     $course_id    Optional. Course ID. Default current course ID.
 *    @type int     $user_id      Optional. User ID. Default current user ID.
 *    @type string  $format       Optional. The date format. Default value of date_format option.
 *    @type boolean $autop        Optional. Whether to replace linebreaks with paragraph elements. Default true.
 *    @type string  $label_before Optional. The content to print before label. Default a translatable string.
 *    @type string  $label_after  Optional. The content to print after label. Default a translatable string.
 * }
 * @param string $content The shortcode content.
 *
 * @return string The `ld_course_expire_status` shortcode output.
 */
function learndash_course_expire_status_shortcode( $atts, $content ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	$content_shortcode = '';

	$atts = shortcode_atts(
		array(
			'course_id'    => learndash_get_course_id(),
			'user_id'      => get_current_user_id(),
			// translators: placeholder: Course.
			'label_before' => sprintf( esc_html_x( '%s access will expire on:', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			// translators: placeholder: Course.
			'label_after'  => sprintf( esc_html_x( '%s access expired on:', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			'format'       => get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
			'autop'        => true,
		),
		$atts
	);

	if ( ( true === $atts['autop'] ) || ( 'true' === $atts['autop'] ) || ( '1' === $atts['autop'] ) ) {
		$atts['autop'] = true;
	} else {
		$atts['autop'] = false;
	}

	/**
	 * Filters `ld_course_expire_status` shortcode attributes.
	 *
	 * @param array $attributes An array of ld_course_expire_status shortcode attributes.
	 */
	$atts = apply_filters( 'learndash_ld_course_expire_status_shortcode_atts', $atts );

	if ( ( ! empty( $atts['course_id'] ) ) && ( ! empty( $atts['user_id'] ) ) ) {
		if ( sfwd_lms_has_access( $atts['course_id'], $atts['user_id'] ) ) {
			$course_meta = get_post_meta( $atts['course_id'], '_sfwd-courses', true );

			$courses_access_from = ld_course_access_from( $atts['course_id'], $atts['user_id'] );
			if ( empty( $courses_access_from ) ) {
				$courses_access_from = learndash_user_group_enrolled_to_course_from( $atts['user_id'], $atts['course_id'] );
			}

			if ( ! empty( $courses_access_from ) ) {

				$expire_on = ld_course_access_expires_on( $atts['course_id'], $atts['user_id'] );
				if ( ! empty( $expire_on ) ) {
					if ( $expire_on > time() ) {
						$content_shortcode .= $atts['label_before'];
					} else {
						$content_shortcode .= $atts['label_after'];
					}
					$content_shortcode .= ' ' . date( $atts['format'], $expire_on + ( get_option( 'gmt_offset' ) * 3600 ) );
				}
			}

			$atts['content'] = do_shortcode( $content_shortcode );
			return SFWD_LMS::get_template(
				'learndash_course_expire_status_message',
				array(
					'shortcode_atts' => $atts,
				),
				false
			);
		}
	}

	if ( ! empty( $content_shortcode ) ) {
		$content .= $content_shortcode;
	}
	return $content;
}

add_shortcode( 'ld_course_expire_status', 'learndash_course_expire_status_shortcode' );
