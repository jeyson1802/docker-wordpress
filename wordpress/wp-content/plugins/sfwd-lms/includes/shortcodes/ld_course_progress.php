<?php
/**
 * Shortcode for learndash_course_progress
 *
 * @since 2.1.0
 *
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `learndash_course_progress` shortcode output.
 *
 * @since 2.1.0
 *
 * @global boolean $learndash_shortcode_used
 *
 * @param array $atts {
 *    An array of shortcode attributes.
 *
 *    @type int     $course_id Optional. Course ID. Default 0.
 *    @type int     $user_id   Optional. User ID. Default 0.
 *    @type boolean $array     Optional. Whether to return array. Default false.
 * }
 *
 * @return string|array The `learndash_course_progress` shortcode output.
 */
function learndash_course_progress( $atts ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	extract(
		shortcode_atts(
			array(
				'course_id' => 0,
				'user_id'   => 0,
				'array'     => false,
			),
			$atts
		)
	);

	if ( empty( $user_id ) ) {
		// $current_user = wp_get_current_user();
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			$user_id = 0;
		}
	}

	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id();
	}

	if ( empty( $course_id ) ) {
		return '';
	}

	$completed = 0;
	$total     = false;

	if ( ! empty( $user_id ) ) {

		$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

		$percentage = 0;
		$message    = '';

		if ( ( ! empty( $course_progress ) ) && ( isset( $course_progress[ $course_id ] ) ) && ( ! empty( $course_progress[ $course_id ] ) ) ) {
			if ( isset( $course_progress[ $course_id ]['completed'] ) ) {
				$completed = absint( $course_progress[ $course_id ]['completed'] );
			}

			if ( isset( $course_progress[ $course_id ]['total'] ) ) {
				$total = absint( $course_progress[ $course_id ]['total'] );
			}
		}
	}

	// If $total is still false we calculate the total from course steps.
	if ( false === $total ) {
		$total = learndash_get_course_steps_count( $course_id );
	}

	if ( $total > 0 ) {
		$percentage = intval( $completed * 100 / $total );
		$percentage = ( $percentage > 100 ) ? 100 : $percentage;
	} else {
		$percentage = 0;
	}
	// translators: placeholder: completed steps, total steps.
	$message = sprintf( esc_html_x( '%1$d out of %2$d steps completed', 'placeholder: completed steps, total steps', 'learndash' ), $completed, $total );

	if ( $array ) {
		return array(
			'percentage' => isset( $percentage ) ? $percentage : 0,
			'completed'  => isset( $completed ) ? $completed : 0,
			'total'      => isset( $total ) ? $total : 0,
		);
	}

	return SFWD_LMS::get_template(
		'course_progress_widget',
		array(
			'user_id'    => $user_id,
			'course_id'  => $course_id,
			'message'    => $message,
			'percentage' => isset( $percentage ) ? $percentage : 0,
			'completed'  => isset( $completed ) ? $completed : 0,
			'total'      => isset( $total ) ? $total : 0,
		)
	);
}

add_shortcode( 'learndash_course_progress', 'learndash_course_progress' );
