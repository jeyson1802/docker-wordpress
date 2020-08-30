<?php
/**
 * Shortcode for ld_profile
 *
 * @since 2.1.0
 *
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `ld_profile` shortcode output.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @since 2.1.0
 *
 * @param array $atts {
 *    An array of shortcode attributes.
 *
 *    @type int       $user_id            Optional. User ID. Defaults to current user ID.
 *    @type false|int $per_page           Optional. Number of profiles per page. Default false.
 *    @type string    $order              Optional. Designates ascending ('ASC') or descending ('DESC') order. Default 'DESC'.
 *    @type string    $orderby            Optional. The name of the field to order posts by. Default 'ID'.
 *    @type int       $course_points_user Optional. Whether to show user course points. Default 'yes'.
 *    @type boolean   $expand_all         Optional. Whether to expand all. Default False.
 *    @type string    $profile_link        Optional. User profile link. Default 'yes'.
 *    @type string    $show_header        Optional. Whether to show header. Default 'yes'.
 *    @type string    $show_quizzes       Optional. Whether to show quizzes. Default 'yes'.
 *    @type string    $show_search        Optional. Whether to allow search. Default 'yes'.
 *    @type string    $search             Optional. Serch query string. Default empty.
 * }
 *
 * @return string The `ld_profile` shortcode ouput.
 */
function learndash_profile( $atts ) {
	global $learndash_shortcode_used;

	// Add check to ensure user it logged in
	if ( ! is_user_logged_in() ) {
		return '';
	}

	$defaults = array(
		'user_id'            => get_current_user_id(),
		'per_page'           => false,
		'order'              => 'DESC',
		'orderby'            => 'ID',
		'course_points_user' => 'yes',
		'expand_all'         => false,
		'profile_link'       => 'yes',
		'show_header'        => 'yes',
		'show_quizzes'       => 'yes',
		'show_search'        => 'yes',
		'search'             => '',
	);
	$atts     = wp_parse_args( $atts, $defaults );

	if ( ( strtolower( $atts['expand_all'] ) == 'yes' ) || ( $atts['expand_all'] == 'true' ) || ( $atts['expand_all'] == '1' ) ) {
		$atts['expand_all'] = true;
	} else {
		$atts['expand_all'] = false;
	}

	if ( ( strtolower( $atts['show_header'] ) == 'yes' ) || ( $atts['show_header'] == 'true' ) || ( $atts['show_header'] == '1' ) ) {
		$atts['show_header'] = 'yes';
	} else {
		$atts['show_header'] = false;
	}

	if ( ( strtolower( $atts['show_search'] ) == 'yes' ) || ( $atts['show_search'] == 'true' ) || ( $atts['show_search'] == '1' ) ) {
		$atts['show_search'] = 'yes';
	} else {
		$atts['show_search'] = false;
	}

	if ( ( strtolower( $atts['course_points_user'] ) == 'yes' ) || ( $atts['course_points_user'] == 'true' ) || ( $atts['course_points_user'] == '1' ) ) {
		$atts['course_points_user'] = 'yes';
	} else {
		$atts['course_points_user'] = false;
	}

	if ( $atts['per_page'] === false ) {
		$atts['per_page'] = $atts['quiz_num'] = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' );
	} else {
		$atts['per_page'] = intval( $atts['per_page'] );
	}

	if ( $atts['per_page'] > 0 ) {
		$atts['paged'] = 1;
	} else {
		unset( $atts['paged'] );
		$atts['nopaging'] = true;
	}

	if ( ( strtolower( $atts['profile_link'] ) == 'yes' ) || ( $atts['profile_link'] == 'true' ) || ( $atts['profile_link'] == '1' ) ) {
		$atts['profile_link'] = true;
	} else {
		$atts['profile_link'] = false;
	}

	if ( ( strtolower( $atts['show_quizzes'] ) == 'yes' ) || ( $atts['show_quizzes'] == 'true' ) || ( $atts['show_quizzes'] == '1' ) ) {
		$atts['show_quizzes'] = true;
	} else {
		$atts['show_quizzes'] = false;
	}

	if ( ( isset( $_GET['ld-profile-search'] ) ) && ( ! empty( $_GET['ld-profile-search'] ) ) ) {
		$atts['search'] = esc_attr( $_GET['ld-profile-search'] );
	}
	/**
	 * Filters profile shortcode attributes.
	 *
	 * @param array $attributes An array of shortcode attributes.
	 */
	$atts = apply_filters( 'learndash_profile_shortcode_atts', $atts );

	if ( isset( $atts['search'] ) ) {
		$atts['s'] = $atts['search'];
		unset( $atts['search'] );
	}

	if ( empty( $atts['user_id'] ) ) {
		return;
	}

	$current_user = get_user_by( 'id', $atts['user_id'] );
	$user_courses = ld_get_mycourses( $atts['user_id'], $atts );

	$usermeta           = get_user_meta( $atts['user_id'], '_sfwd-quizzes', true );
	$quiz_attempts_meta = empty( $usermeta ) ? false : $usermeta;
	$quiz_attempts      = array();

	if ( ! empty( $quiz_attempts_meta ) ) {

		foreach ( $quiz_attempts_meta as $quiz_attempt ) {
			$c                          = learndash_certificate_details( $quiz_attempt['quiz'], $atts['user_id'] );
			$quiz_attempt['post']       = get_post( $quiz_attempt['quiz'] );
			$quiz_attempt['percentage'] = ! empty( $quiz_attempt['percentage'] ) ? $quiz_attempt['percentage'] : ( ! empty( $quiz_attempt['count'] ) ? $quiz_attempt['score'] * 100 / $quiz_attempt['count'] : 0 );

			if ( $atts['user_id'] == get_current_user_id() && ! empty( $c['certificateLink'] ) && ( ( isset( $quiz_attempt['percentage'] ) && $quiz_attempt['percentage'] >= $c['certificate_threshold'] * 100 ) ) ) {
				$quiz_attempt['certificate'] = $c;
			}

			if ( ! isset( $quiz_attempt['course'] ) ) {
				$quiz_attempt['course'] = learndash_get_course_id( $quiz_attempt['quiz'] );
			}
			$course_id = intval( $quiz_attempt['course'] );

			$quiz_attempts[ $course_id ][] = $quiz_attempt;
		}
	}

	$profile_pager = array();

	if ( ( isset( $atts['per_page'] ) ) && ( intval( $atts['per_page'] ) > 0 ) ) {
		$atts['per_page'] = intval( $atts['per_page'] );

		if ( ( isset( $_GET['ld-profile-page'] ) ) && ( ! empty( $_GET['ld-profile-page'] ) ) ) {
			$profile_pager['paged'] = intval( $_GET['ld-profile-page'] );
		} else {
			$profile_pager['paged'] = 1;
		}

		$profile_pager['total_items'] = count( $user_courses );
		$profile_pager['total_pages'] = ceil( count( $user_courses ) / $atts['per_page'] );

		$user_courses = array_slice( $user_courses, ( $profile_pager['paged'] * $atts['per_page'] ) - $atts['per_page'], $atts['per_page'], false );
	}

	$learndash_shortcode_used = true;

	return SFWD_LMS::get_template(
		'profile',
		array(
			'user_id'        => $atts['user_id'],
			'quiz_attempts'  => $quiz_attempts,
			'current_user'   => $current_user,
			'user_courses'   => $user_courses,
			'shortcode_atts' => $atts,
			'profile_pager'  => $profile_pager,
		)
	);
}

add_shortcode( 'ld_profile', 'learndash_profile' );
