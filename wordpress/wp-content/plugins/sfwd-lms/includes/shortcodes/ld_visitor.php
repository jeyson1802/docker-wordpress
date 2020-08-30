<?php
/**
 * Shortcode for visitor
 *
 * @since 2.1.0
 *
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `visitor` shortcode output.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @todo  function is duplicate of learndash_student_check_shortcode()
 *
 * @since 2.1.0
 *
 * @param array  $atts {
 *    An array of shortcode attributes.
 *
 *    @type int     $course_id Optional. Course ID. Default current course ID.
 *    @type string  $content   Optional. The shortcode content. Default empty
 *    @type boolean $autop     Optional. Whether to replace linebreaks with paragraph elements. Default true.
 * }
 * @param string $content Optional. The shortcode content. Default empty.
 *
 * @return string The `visitor` shortcode output.
 */
function learndash_visitor_check_shortcode( $atts, $content = '' ) {
	global $learndash_shortcode_used;

	if ( ! empty( $content ) ) {

		if ( ! is_array( $atts ) ) {
			if ( ! empty( $atts ) ) {
				$atts = array( $atts );
			} else {
				$atts = array();
			}
		}

		$defaults = array(
			'course_id' => learndash_get_course_id(),
			'content'   => $content,
			'autop'     => true,
		);
		$atts     = wp_parse_args( $atts, $defaults );

		if ( ( true === $atts['autop'] ) || ( 'true' === $atts['autop'] ) || ( '1' === $atts['autop'] ) ) {
			$atts['autop'] = true;
		} else {
			$atts['autop'] = false;
		}

		/**
		 * Filters visitor shortcode attributes.
		 *
		 * @param array $attributes An array of shortcode attributes.
		 */
		$atts = apply_filters( 'learndash_visitor_shortcode_atts', $atts );

		if ( ( ! is_user_logged_in() ) || ( ( ! empty( $atts['course_id'] ) ) && ( ! sfwd_lms_has_access( $atts['course_id'] ) ) ) ) {
			$learndash_shortcode_used = true;
			$atts['content']          = do_shortcode( $atts['content'] );
			return SFWD_LMS::get_template(
				'learndash_course_visitor_message',
				array(
					'shortcode_atts' => $atts,
				),
				false
			);

		} else {
			$content = '';
		}
	}

	return $content;
}

add_shortcode( 'visitor', 'learndash_visitor_check_shortcode' );
