<?php
/**
 * Shortcode for ld_user_course_points
 *
 * @since 2.1.0
 *
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `ld_user_course_points` shortcode output.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @param array  $atts {
 *    An array of shortcode attributes.
 *
 *    @type int    $user_id Optional. User ID. Default to current user ID.
 *    @type string $context Optional. The shortcode context. Default empty.
 * }
 * @param string $content Optional. Shortcode content. Default empty.
 *
 * @return void|string The `ld_user_course_points` shortcode output.
 */
function learndash_user_course_points_shortcode( $atts, $content = '' ) {
	global $learndash_shortcode_used;

	$defaults = array(
		'user_id' => get_current_user_id(),
		'context' => 'ld_user_course_points',
	);
	$atts     = wp_parse_args( $atts, $defaults );

	if ( ! isset( $atts['user_id'] ) ) {
		return;
	}

	$learndash_shortcode_used = true;

	$user_couse_points = learndash_get_user_course_points( $atts['user_id'] );

	$content = SFWD_LMS::get_template(
		'learndash_course_points_user_message',
		array(
			'user_course_points' => $user_couse_points,
			'user_id'            => $atts['user_id'],
			'shortcode_atts'     => $atts,
		),
		false
	);
	return $content;
}
add_shortcode( 'ld_user_course_points', 'learndash_user_course_points_shortcode' );
