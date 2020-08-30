<?php
/**
 * Course Functions
 * 
 * @since 2.1.0
 * 
 * @package LearnDash\Course
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gets the course ID for a resource.
 *
 * Determine the type of ID being passed. Should be the ID of
 * anything that belongs to a course (Lesson, Topic, Quiz, etc)
 *
 * @since 2.1.0
 * @since 2.5.0 Added the `$bypass_cb` parameter.
 *
 * @param  WP_Post|int|null $id        Optional. ID of the resource. Default null.
 * @param  boolean          $bypass_cb Optional. If true will bypass course_builder logic. Default false.
 *
 * @return string ID of the course.
 */
function learndash_get_course_id( $id = null, $bypass_cb = false ) {
	//global $post;

	if ( is_object( $id ) && $id->ID ) {
		$p = $id;
		$id = $p->ID;
	} else if ( is_numeric( $id ) ) {
		$p = get_post( $id );
	}

	if ( empty( $id ) ) {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			//return false;
		} else {
			if ( is_admin() ) {
				global $parent_file, $post_type, $pagenow;
				if ( ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) || ( ! in_array( $post_type, array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) ) ) {
					return false;
				}

			} else if ( ! is_single() || is_home() ) {
				return false;
			}
		}

		$post = get_post( get_the_id() );
		if ( ( $post ) && ( $post instanceof WP_Post ) ) {
			$id = $post->ID;
			$p = $post;
		} else {
			return false;
		}
	}

	if ( empty( $p->ID ) ) {
		return 0;
	}

	if ( $p->post_type == 'sfwd-courses' ) {
		return $p->ID;
	}

	// Somewhat a kludge. Here we try ans assume the course_id being handled. 
	if ( ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) && ( $bypass_cb === false ) ) {	
		if ( ! is_admin() ) {
			$course_slug = get_query_var( 'sfwd-courses' );
			if ( ! empty( $course_slug ) ) {
				//$course_post = get_page_by_path( $course_slug, OBJECT, 'sfwd-courses' );
				$course_post = learndash_get_page_by_path( $course_slug, 'sfwd-courses' );
				if ( ( $course_post ) && ( $course_post instanceof WP_Post ) ) {
					return $course_post->ID;
				}
			}
		}

		if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
			return intval( $_GET['course_id'] );
		} else if ( ( isset( $_GET['course'] ) ) && ( ! empty( $_GET['course'] ) ) ) {
			return intval( $_GET['course'] );
		} else if ( ( isset( $_POST['course_id'] ) ) && ( ! empty( $_POST['course_id'] ) ) ) {
			return intval( $_POST['course_id'] );
		} else if ( ( isset( $_POST['course'] ) ) && ( ! empty( $_POST['course'] ) ) ) {
			return intval( $_POST['course'] );
		} else if ( ( isset( $_GET['post'] ) ) && ( ! empty( $_GET['post'] ) ) ) {
			if ( get_post_type( intval( $_GET['post'] ) ) == 'sfwd-courses' ) {
				return intval( $_GET['post'] );
			}
		}
	}

	return (int)get_post_meta( $id, 'course_id', true );
}

/**
 * Get primary course_id for course step.
 *
 * @since 3.2
 * @param integer $step_id Course step post ID.
 * @return integer $course_id Primary Course ID if found.
 */
function learndash_get_primary_course_for_step( $step_id = 0 ) {
	$course_id = null;
	$step_id = absint( $step_id );
	if ( ! empty( $step_id ) ) {
		$course_id = get_post_meta( $step_id, 'course_id', true );
		if ( empty( $course_id ) ) {
			$step_courses = learndash_get_courses_for_step( $step_id );
			if ( ! isset( $step_courses['primary'] ) ) {
				$step_courses['primary'] = array();
			}
			$step_courses['primary'] = array_keys( $step_courses['primary'] );
			if ( ! empty( $step_courses['primary'] ) ) {
				$course_id = absint( $step_courses['primary'][0] );
			}
		}
	}

	return $course_id;
}

/**
 * Set primary course_id for course step.
 *
 * @since 3.2
 * @param integer $step_id Course step post ID.
 * @return integer $course_id Primary Course ID if found.
 */
function learndash_set_primary_course_for_step( $step_id = 0, $course_id = 0 ) {
	$step_id = absint( $step_id );
	$course_id = absint( $course_id );

	if ( ( ! empty( $step_id ) ) && ( ! empty( $course_id ) ) ) {
		$step_courses = learndash_get_courses_for_step( $step_id );

		if ( ( ! isset( $step_courses['primary'][ $course_id ] ) ) && ( isset( $step_courses['secondary'][ $course_id ] ) ) ) {
			learndash_update_setting( $step_id, 'course', $course_id );
		}
	}
}

/**
 * Gets the legacy course ID for a resource.
 *
 * Determine the type of ID is being passed in.  Should be the ID of
 * anything that belongs to a course (Lesson, Topic, Quiz, etc).
 *
 * @global wpdb    $wpdb WordPress database abstraction object.
 * @global WP_Post $post Global post object.
 *
 * @since 2.1.0
 *
 * @param  WP_Post|int|null $id Optional. ID of the resource. Default null.
 *
 * @return string ID of the course.
 */
function learndash_get_legacy_course_id( $id = null ){
	global $post;

	if ( empty( $id ) ) {
		if ( ! is_single() || is_home() ) {
			return false;
		}

		$id = $post->ID;
	}

	$terms = wp_get_post_terms( $id, 'courses' );

	if ( empty( $terms) || empty( $terms[0] ) || empty( $terms[0]->slug) ) {
		return 0;
	}

	$courseslug = $terms[0]->slug;

	global $wpdb;

	$term_taxonomy_id = $wpdb->get_var(
		$wpdb->prepare(
			"
		 SELECT `term_taxonomy_id` FROM $wpdb->term_taxonomy tt, $wpdb->terms t 
		 WHERE slug = %s 
		 AND t.term_id = tt.term_id
		 AND tt.taxonomy = 'courses'
		",
			$courseslug
		)
	);

	$course_id = $wpdb->get_var(
		$wpdb->prepare(
			"
		 SELECT `ID` FROM $wpdb->term_relationships, $wpdb->posts 
		 WHERE `ID` = `object_id`
		 AND `term_taxonomy_id` = %d
		 AND `post_type` = 'sfwd-courses'
		 AND `post_status` = 'publish' 
		",
			$term_taxonomy_id
		)
	);

	return $course_id;
}



/**
 * Gets the lesson ID of a resource.
 *
 * @global WP_Post $post Global post object.
 *
 * @since 2.1.0
 *
 * @param int|null $post_id   Optional. ID of the resource. Default null.
 * @param int|null $course_id Optional. ID of the course. Default null.
 *
 * @return string Lesson ID.
 */
function learndash_get_lesson_id( $post_id = null, $course_id = null ) {
	global $post;

	if ( empty( $post_id ) ) {
		if ( ! is_single() || is_home() ) {
			return false;
		}

		$post_id = $post->ID;
	}

	if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {	
		$lesson_slug = get_query_var( 'sfwd-lessons' );
		if ( !empty( $lesson_slug ) ) {
			//$lesson_post = get_page_by_path( $lesson_slug, OBJECT, 'sfwd-lessons' );
			$lesson_post = learndash_get_page_by_path( $lesson_slug, 'sfwd-lessons' );
			if ( ( $lesson_post ) && ( $lesson_post instanceof WP_Post ) ) {
				return $lesson_post->ID;
			}
		} else {
			if ( empty( $course_id ) ) {
				$course_id = learndash_get_course_id( $post_id );
			}
			
			if ( !empty( $course_id ) ) {
				return learndash_course_get_single_parent_step( $course_id, $post_id );
			} 
		}
	}
	
	return get_post_meta( $post_id, 'lesson_id', true );
}


/**
 * Gets the array of courses that can be accessed by the user.
 *
 * @since 2.1.0
 *
 * @param int|null $user_id User ID. Default null
 * @param array    $atts {
 *    Optional. An array of attributes. Default empty array.
 *
 *    @type string $order   Optional. Designates ascending ('ASC') or descending ('DESC') order. Default 'DESC.
 *    @type string $orderby Optional. The name of the field to order posts by. Default ''ID.
 *    @type string $s       Optional. The search string. Default empty.
 * }
 *
 * @return array An array of courses accessible to user.
 */
function ld_get_mycourses( $user_id = null, $atts = array() ) {

	$defaults = array(
		'order' 	=> 'DESC', 
		'orderby' => 'ID', 
		's'       => '',
	);
	$atts = wp_parse_args( $atts, $defaults );
	
	return learndash_user_get_enrolled_courses( 
		$user_id, 
		$atts, 
		true
   );
}


/**
 * Checks whether a user has access to a course.
 *
 * @since 2.1.0
 *
 * @param int      $post_id ID of the resource.
 * @param int|null $user_id Optional. ID of the user. Default null.
 *
 * @return bool Returns true if the user has access.
 */
function sfwd_lms_has_access( $post_id, $user_id = null ) {

	 /**
	 * Filters whether a user has access to the course.
	 *
	 * @since 2.1.0
	 *
	 * @param boolean $has_access Whether the user has access to the course or not.
	 * @param int     $post_id    Post ID.
	 * @param int     $user_id    User ID.
	 */
	return apply_filters( 'sfwd_lms_has_access', sfwd_lms_has_access_fn( $post_id, $user_id ), $post_id, $user_id );
}



/**
 * Checks whether a user has access to a course.
 *
 * @since 2.1.0
 *
 * @param int      $post_id ID of the resource.
 * @param int|null $user_id Optional. ID of the user. Default null.
 *
 * @return bool Returns true if the user has access.
 */
function sfwd_lms_has_access_fn( $post_id, $user_id = null ) {
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$course_id = learndash_get_course_id( $post_id );
	if ( empty( $course_id ) ) {
		return true;
	}

	if ( ( learndash_is_admin_user( $user_id ) ) || ( learndash_is_group_leader_user( $user_id ) ) ) {
		if ( learndash_is_admin_user( $user_id ) ) {
			$course_autoenroll = LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' );
		} elseif ( learndash_is_group_leader_user( $user_id ) ) {
			$course_autoenroll = '';
			if ( 'yes' === LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Groups_Group_Leader_User', 'bypass_course_limits' ) ) {
				$group_ids = learndash_get_administrators_group_ids( $user_id );
				if ( ! empty( $group_ids ) ) {
					$group_course_ids = learndash_get_groups_courses_ids( $user_id, $group_ids );
					if ( is_array( $group_course_ids ) ) {
						$group_course_ids = array_map( 'absint', $group_course_ids );
						if ( in_array( $course_id, $group_course_ids ) ) {
							$course_autoenroll = 'yes';
						}
					}
				}
			}
		}

		if ( $course_autoenroll == 'yes' ) {
			$course_autoenroll = true;
		} else {
			$course_autoenroll = false;
		}

		/** This filter is documented in includes/ld-users.php */
		if ( apply_filters('learndash_override_course_auto_enroll', $course_autoenroll, $user_id ) ) {
			return true;
		}
	}

	if ( ! empty( $post_id ) && learndash_is_sample( $post_id ) ) {
		return true;
	}

	$meta = get_post_meta( $course_id, '_sfwd-courses', true );
	
	if ( @$meta['sfwd-courses_course_price_type'] == 'open' || @$meta['sfwd-courses_course_price_type'] == 'paynow' && empty( $meta['sfwd-courses_course_join'] ) && empty( $meta['sfwd-courses_course_price'] ) ) {
		return true;
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	if ( true === learndash_use_legacy_course_access_list() ) {
		if ( ! empty( $meta['sfwd-courses_course_access_list'] ) ) {
			//$course_access_list = explode( ',', $meta['sfwd-courses_course_access_list'] );
			$course_access_list = learndash_convert_course_access_list( $meta['sfwd-courses_course_access_list'], true );
		} else {
			$course_access_list = array();
		}
		if ( ( in_array( $user_id, $course_access_list ) ) || ( learndash_user_group_enrolled_to_course( $user_id, $course_id ) ) ) {
			$expired = ld_course_access_expired( $course_id, $user_id );
			return ! $expired; //True if not expired.
		} else {
			return false;
		}
	} else {
		$course_user_meta = get_user_meta( $user_id, 'course_' . $course_id . '_access_from', true );
		if ( ( ! empty( $course_user_meta ) ) || ( learndash_user_group_enrolled_to_course( $user_id, $course_id ) ) ) {
			$expired = ld_course_access_expired( $course_id, $user_id );
			return ! $expired; //True if not expired.
		} else {
			return false;
		}
	}		
	
}



/**
 * Redirects a user to the course page if it does not have access.
 *
 * @since 2.1.0
 *
 * @param int $post_id The ID of the resource that belongs to a course.
 *
 * @return boolean|void Returns true if the user has access to the course.
 */
function sfwd_lms_access_redirect( $post_id ) {
	$access = sfwd_lms_has_access( $post_id );
	if ( $access === true ) {
		return true;
	}

	$link = get_permalink( learndash_get_course_id( $post_id ) );
	/**
	 * Filters the course redirect URL after checking access.
	 *
	 * @param string $link    The course URL a user is redirected to it has access.
	 * @param int    $post_id Post ID.
	 */
	$link = apply_filters( 'learndash_access_redirect' , $link, $post_id );
	if ( !empty( $link ) ) {
		wp_redirect( $link );
		exit();
	}
}



/**
 * Checks whether the user's access to the course is expired.
 *
 * @since 2.1.0
 *
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 *
 * @return bool Returns true if the access is expired otherwise false.
 */
function ld_course_access_expired( $course_id, $user_id ) {
	$course_access_upto = ld_course_access_expires_on( $course_id, $user_id );
	
	if ( empty( $course_access_upto ) ) {
		return false;
	} else {

		if ( time() >= $course_access_upto ) {
			/**
			 * Filters whether the course is expired for a user or not.
			 *
			 * @since 2.6.2
			 *
			 * @param boolean $expired            Whether the course is expired or not.
			 * @param int     $user_id            User ID.
			 * @param int     $course_id          Course ID.
			 * @param int     $course_access_upto Course expiration timestamp.
			 */
			if ( apply_filters( 'learndash_process_user_course_access_expire', true, $user_id, $course_id, $course_access_upto ) ) { 

				/**
				 * As of LearnDash 2.3.0.3 we store the GMT timestamp as the meta value. In prior versions we stored 1
				*/
				update_user_meta( $user_id, 'learndash_course_expired_' . $course_id, time() );
				ld_update_course_access( $user_id, $course_id, true );

				/**
				 * Fires when the user course access is expired.
				 *
				 * @since 2.6.2
				 *
				 * @param int $user_id   User ID.
				 * @param int $course_id Course ID.
				 */
				do_action( 'learndash_user_course_access_expired', $user_id, $course_id );

				$delete_course_progress = learndash_get_setting( $course_id, 'expire_access_delete_progress' );
				if ( ! empty( $delete_course_progress) ) {
					learndash_delete_course_progress( $course_id, $user_id );
				}
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}

	}	 
}



/**
 * Generates an alert in the header that a user's access to the course is expired.
 *
 * Fires on `wp_head` hook.
 *
 * @global WP_Post $post Global post object.
 *
 * @since 2.1.0
 */
function ld_course_access_expired_alert() {
	global $post;

	if ( ! is_singular() || empty( $post->ID ) || $post->post_type != 'sfwd-courses' ) {
		return;
	}

	$user_id = get_current_user_id();

	if ( empty( $user_id ) ) {
		return;
	}
	
	$expired = get_user_meta( $user_id, 'learndash_course_expired_'.$post->ID, true );
	
	if ( empty( $expired) ) {
		return;
	}

	$has_access = sfwd_lms_has_access( $post->ID, $user_id );

	if ( $has_access ) {
		delete_user_meta( $user_id, 'learndash_course_expired_'.$post->ID );
		return;
	} else	{
		?>
		<script>
			setTimeout(function() {
				alert("<?php echo sprintf( 
					// translators: placeholder: Course.
					esc_html_x( 'Your access to this %s has expired.', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' )); ?>")
			}, 2000);
		</script>
		<?php
	}
}

add_action( 'wp_head', 'ld_course_access_expired_alert', 1 );



/**
 * Gets the amount of time until the course access expires for a user.
 *
 * @since 2.1.0
 *
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 *
 * @return int The timestamp for course access expiration.
 */
function ld_course_access_expires_on( $course_id, $user_id ) {
	// Set a default return var. 
	$course_access_upto = 0;
	
	// Check access to course_id + user_id
	$courses_access_from = ld_course_access_from( $course_id, $user_id );

	// If the course_id + user_id is not set we check the group courses.
	if ( empty( $courses_access_from ) ) {
		$courses_access_from = learndash_user_group_enrolled_to_course_from( $user_id, $course_id );
	}
	
	// If we have a non-empty access from...
	if (  abs( intval( $courses_access_from ) ) ) {
		
		// Check the course is using expire access
		$expire_access = learndash_get_setting( $course_id, 'expire_access' );
		// The value stored in the post meta for 'expire_access' is 'on' not true/false 1 or 0. The string 'on'.
		if ( !empty( $expire_access) ) {
			$expire_access_days = learndash_get_setting( $course_id, 'expire_access_days' );
			if ( abs( intval( $expire_access_days ) )  > 0 ) {
				$course_access_upto = abs( intval( $courses_access_from ) ) + ( abs( intval( $expire_access_days ) ) * DAY_IN_SECONDS );
			}
		}
	}
	
	/**
	 * Filters the amount of time until the user's course access expires.
	 *
	 * @since 3.0.7
	 *
	 * @param int $course_access_upto Course expires on timestamp.
	 * @param int $course_id          Course ID.
	 * @param int $user_id            User ID.
	 */
	return apply_filters( 'ld_course_access_expires_on', $course_access_upto, $course_id, $user_id );
}



/**
 * Gets the amount of time when the lesson becomes available to a user.
 *
 * @since 2.1.0
 *
 * @param int $course_id Optional. Course ID to check. Default 0.
 * @param int $user_id   Optional. User ID to check. Default 0.
 *
 * @return int The timestamp of when the course can be accessed from.
 */
function ld_course_access_from( $course_id = 0, $user_id = 0 ) {
	static $courses = array();

	$course_id = absint( $course_id );
	$user_id = absint( $user_id );

	// If Shared Steps enabled we need to ensure both Course ID and User ID and not empty.
	if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
		if ( ( empty( $course_id ) ) || ( empty( $user_id ) ) ) {
			return false;
		}
	}

	if ( ! isset( $courses[ $course_id ][ $user_id ] ) ) {
		if ( ! isset( $courses[ $course_id ] ) ) {
			$courses[ $course_id ] = array();
		}
		$courses[ $course_id ][ $user_id ] = false;

		$courses[ $course_id ][ $user_id ] = (int) get_user_meta( $user_id, 'course_' . $course_id . '_access_from', true );
		if ( empty( $courses[ $course_id ][ $user_id ] ) ) {
			/**
			 * Filters whether to update user course access from value.
			 *
			 * @param boolean $update_access_from Whether to update user access from.
			 * @param int     $user_id            User ID.
			 * @param int     $course_id          Course ID.
			 */
			if ( ( 'open' === learndash_get_course_meta_setting( $course_id, 'course_price_type' ) ) && ( apply_filters( 'learndash_course_open_set_user_access_from', true, $user_id, $course_id ) ) ) {
				$enrolled_groups = learndash_user_group_enrolled_to_course_from( $user_id, $course_id );
				if ( ! empty( $enrolled_groups ) ) {
					$courses[ $course_id ][ $user_id ] = absint( $enrolled_groups );
				}
			}
		}
		if ( empty( $courses[ $course_id ][ $user_id ] ) ) {
			$course_activity_args = array(
				//'course_id'        => $course_id,
				'user_id'          => $user_id,
				'post_id'          => $course_id,
				'activity_type'    => 'access',
			);

			$course_activity = learndash_get_user_activity( $course_activity_args );
			if ( ( ! empty( $course_activity ) ) && ( is_object( $course_activity ) ) ) {
				if ( ( property_exists( $course_activity, 'activity_started' ) ) && ( ! empty( $course_activity->activity_started ) ) ) {
					$courses[ $course_id ][ $user_id ] = intval( $course_activity->activity_started );
					update_user_meta( $user_id, 'course_' . $course_id . '_access_from', $courses[ $course_id ][ $user_id ] );
				}
			}
		}
	}

	/**
	 * Filters the amount of time when a lesson becomes available to the user.
	 *
	 * @since 3.0.7
	 *
	 * @param int $access_from The timestamp of when the lesson wil become availble to user.
	 * @param int $course_id   Course ID.
	 * @param int $user_id     User ID.
	 */
	return apply_filters( 'ld_course_access_from', $courses[ $course_id ][ $user_id ], $course_id, $user_id );
}

/**
 * Updates the course access time for a user.
 *
 * @since 2.6.0
 *
 * @param int        $course_id Course ID for update.
 * @param int        $user_id   User ID for update.
 * @param string|int $access    Optional. Value can be a date string (YYYY-MM-DD hh:mm:ss or integer value. Default empty.
 * @param boolean    $is_gmt    Optional. True if the access value is GMT or false if it is relative to site timezone. Default false.
 *
 * @return boolean Returns true if the value is updated successfully.
 */
function ld_course_access_from_update( $course_id, $user_id, $access = '', $is_gmt = false ) {
	if ( ( ! empty( $course_id ) ) && ( ! empty( $user_id ) ) && ( ! empty( $access ) ) ) {

		if ( ! is_numeric( $access ) ) {
			// If we a non-numberic value like a date stamp Y-m-d hh:mm:ss we want to convert it to a GMT timestamp.
			$access_time = learndash_get_timestamp_from_date_string( $access, !$is_gmt );
		} elseif ( is_string( $access ) ) {
			if ( ! $is_gmt ) {
				$access = get_gmt_from_date( $access, 'Y-m-d H:i:s' );
			}
			$access_time = strtotime( $access );
		} else {
			return false;
		}

		if ( ( ! empty( $access_time ) ) && ( $access_time > 0 ) ) {
			// We don't allow dates greater than now.
			if ( $access_time > time() ) {
				$access_time = time();
			}
			
			$course_args = array(
				'course_id'     => $course_id,
				'post_id'       => $course_id,
				'activity_type' => 'course',
				'user_id'       => $user_id,
				'activity_started' => $access_time,
			);
			$activity_id = learndash_update_user_activity( $course_args ); 

			return update_user_meta( $user_id, 'course_' . $course_id . '_access_from', $access_time );
		}
	}
}

/**
 * Updates the list of courses a user can access.
 *
 * @since 2.1.0
 *
 * @param  int     $user_id   User ID.
 * @param  int     $course_id Course ID.
 * @param  boolean $remove    Optional. Whether to remove course access for the user. Default false.
 *
 * @return boolean|void Returns true if the user course access updation was successful otherwise false.
 */
function ld_update_course_access( $user_id, $course_id, $remove = false ) {
	$action_success = false;

	$user_id = absint( $user_id );
	$course_id = absint( $course_id );
	$course_access_list = null;

	if ( ( empty( $user_id ) ) || ( empty( $course_id ) ) ) {
		return;
	}

	if ( true === learndash_use_legacy_course_access_list() ) {
		$course_access_list = learndash_get_setting( $course_id, 'course_access_list' );
		$course_access_list = learndash_convert_course_access_list( $course_access_list, true );

		if ( empty( $remove ) ) {
			$course_access_list[] = $user_id;
			$course_access_list = array_unique( $course_access_list );
			$action_success = true;
		} else {
			$course_access_list = array_diff( $course_access_list, array( $user_id ) );
			$action_success = true;
		}
		$course_access_list = learndash_convert_course_access_list( $course_access_list );
		learndash_update_setting( $course_id, 'course_access_list', $course_access_list );
	}

	$user_course_access_time = 0;
	if ( empty( $remove ) ) {
		$user_course_access_time = get_user_meta( $user_id, 'course_' . $course_id . '_access_from', true );
		if ( empty( $user_course_access_time ) ) {
			$user_course_access_time = time();
			update_user_meta( $user_id, 'course_' . $course_id . '_access_from', $user_course_access_time );
			$action_success = true;
		}
	} else {
		delete_user_meta( $user_id, 'course_'. $course_id .'_access_from' );
		$action_success = true;
	}

	$course_activity_args = array(
		'activity_type'    => 'access',
		'user_id'          => $user_id,
		'post_id'          => $course_id,
		'course_id'        => $course_id,
	);
	$course_activity = learndash_get_user_activity( $course_activity_args );
	if ( is_null( $course_activity ) ) {
		$course_activity_args['course_id'] = 0;
		$course_activity = learndash_get_user_activity( $course_activity_args );
	}

	if ( is_object( $course_activity ) ) {
		$course_activity_args = json_decode( json_encode( $course_activity ), true );
		$course_activity_args['changed'] = false;
	} else {
		$course_activity_args['changed'] = true;
		$course_activity_args['activity_started'] = 0;
	}

	if ( ( empty( $course_activity_args['course_id'] ) ) || ( $course_activity_args['course_id'] !== $course_activity_args['post_id'] ) ) {
		$course_activity_args['course_id'] = $course_activity_args['post_id'];
		$course_activity_args['changed'] = true;
	}

	if ( empty( $remove ) ) {
		if ( $user_course_access_time !== absint( $course_activity_args['activity_started'] ) ) {
			$course_activity_args['activity_started'] = $user_course_access_time;
			$course_activity_args['changed'] = true;
		}
	} else {
		$course_activity_args['activity_started'] = $user_course_access_time;
		$course_activity_args['changed'] = true;
	}
	
	if ( true === $course_activity_args['changed'] ) {
		$skip = false;
		if ( ( ! empty( $remove ) ) && ( ! isset( $course_activity_args['activity_id'] ) ) ) {
			$skip = true;
		}
		if ( true !== $skip ) {
			$course_activity_args['data_upgrade'] = true;
			learndash_update_user_activity( $course_activity_args );
		}
	}

	/**
	 * Fires after a user's list of courses are updated.
	 *
	 * @since 2.1.0
	 *
	 * @param int     $user_id            User ID.
	 * @param int     $course_id          Course ID.
	 * @param string  $course_access_list A comma-separated list of user IDs used for the course_access_list field.
	 * @param boolean $remove             Whether to remove course access from the user.
	 */
	do_action( 'learndash_update_course_access', $user_id, $course_id, $course_access_list, $remove );
	
	return $action_success;
}


/**
 * Gets the timestamp of when a user can access the lesson.
 *
 * @since 2.1.0
 *
 * @param int      $lesson_id Lesson ID.
 * @param int      $user_id   User ID.
 * @param int|null $course_id Optional. Course ID. Default null.
 * @param boolean  $bypass_transient Optional. Whether to bypass transient cache. Default false.
 *
 * @return int|void The timestamp of when the user can access the lesson.
 */
function ld_lesson_access_from( $lesson_id, $user_id, $course_id = null, $bypass_transient = false ) {
	$return = null;

	if ( is_null( $course_id ) ) {
		$course_id = learndash_get_course_id( $lesson_id );
	}
	
	$courses_access_from = ld_course_access_from( $course_id, $user_id );
	if ( empty( $courses_access_from ) ) {
		$courses_access_from = learndash_user_group_enrolled_to_course_from( $user_id, $course_id, $bypass_transient );
	}

	$visible_after = learndash_get_setting( $lesson_id, 'visible_after' );
	if ( $visible_after > 0 ) {
		
		// Adjust the Course acces from by the number of days. Use abs() to ensure no negative days.
		$lesson_access_from = $courses_access_from + abs($visible_after) * 24 * 60 * 60;
		/**
		 * Filters the timestamp of when lesson will be visible after.
		 *
		 * @param int $lesson_access_from The timestamp of when the lesson will be available after a specific date.
		 * @param int $lesson_id          Lesson ID.
		 * @param int $user_id            User ID.
		 */
		$lesson_access_from = apply_filters( 'ld_lesson_access_from__visible_after', $lesson_access_from, $lesson_id, $user_id );

		$current_timestamp = time();
		if ( $current_timestamp < $lesson_access_from ) {
			$return = $lesson_access_from;
		}		

	} else {
		$visible_after_specific_date = learndash_get_setting( $lesson_id, 'visible_after_specific_date' );
		if ( !empty( $visible_after_specific_date ) ) {
			if ( !is_numeric( $visible_after_specific_date ) ) {
				// If we a non-numberic value like a date stamp Y-m-d hh:mm:ss we want to convert it to a GMT timestamp
				$visible_after_specific_date = learndash_get_timestamp_from_date_string( $visible_after_specific_date, true );
			} 

			$current_time = time();
			
			if ( $current_time < $visible_after_specific_date ) {
				/**
				 * Filters the timestamp of when lesson will be available after a specific date.
				 *
				 * @param int $visible_after_specific_date The timestamp of when the lesson will be available after a specific date.
				 * @param int $lesson_id                  Lesson ID.
				 * @param int $user_id                    User ID.
				 */
				$return = apply_filters( 'ld_lesson_access_from__visible_after_specific_date', $visible_after_specific_date, $lesson_id, $user_id );
			}
		}
	}

	/**
	 * Filters the timestamp of when the user will have access to the lesson.
	 *
	 * @param int $timestamp The timestamp of when the lesson can be accessed.
	 * @param int $lesson_id Lesson ID.
	 * @param int $user_id   User ID.
	 */
	return apply_filters( 'ld_lesson_access_from', $return, $lesson_id, $user_id );
}



/**
 * Gets when the lesson will be available.
 *
 * Fires on `learndash_content` hook.
 *
 * @since 2.1.0
 *
 * @param string  $content The content of lesson.
 * @param WP_Post $post    The `WP_Post` object.
 *
 * @return string The output of when the lesson will be available.
 */
function lesson_visible_after( $content, $post ) {	
	if ( empty( $post->post_type ) ) {
		return $content; 
	}

	if ( $post->post_type == 'sfwd-lessons' ) {
		$lesson_id = $post->ID; 
	} else {
		if ( $post->post_type == 'sfwd-topic' || $post->post_type == 'sfwd-quiz' ) {
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
				$course_id = learndash_get_course_id( $post );
				$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
			} else {
				$lesson_id = learndash_get_setting( $post, 'lesson' );	
			}
		} else {
			return $content; 
		}
	}

	if ( empty( $lesson_id ) ) {
		return $content; 
	}

	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();
	} else {
		return $content; 
	}

	$bypass_course_limits_admin_users = learndash_can_user_bypass( $user_id, 'learndash_course_lesson_not_available', $post->ID, $post );
		
	// For logged in users to allow an override filter. 
	/** This filter is documented in includes/class-ld-cpt-instance.php */
	$bypass_course_limits_admin_users = apply_filters( 'learndash_prerequities_bypass', $bypass_course_limits_admin_users, $user_id, $post->ID, $post );

	$lesson_access_from = ld_lesson_access_from( $lesson_id, get_current_user_id() );
	if ( ( empty( $lesson_access_from ) ) || ( $bypass_course_limits_admin_users ) ) {
		return $content; 
	} else {
		$content = SFWD_LMS::get_template( 
			'learndash_course_lesson_not_available', 
			array(
				'user_id'					=>	get_current_user_id(),
				'course_id'					=>	learndash_get_course_id( $lesson_id ),
				'lesson_id'					=>	$lesson_id,
				'lesson_access_from_int'	=>	$lesson_access_from,
				'lesson_access_from_date'	=>	learndash_adjust_date_time_display( $lesson_access_from ),
				'context'					=>	'lesson'
			), false
		);
		return $content;
	}

	return $content;
}

add_filter( 'learndash_content', 'lesson_visible_after', 1, 2 );



/**
 * Checks if the user's course prerequisites are completed for a given course.
 *
 * @since 2.1.0
 *
 * @param int $post_id Optional. The ID of the course. Default 0.
 *
 * @return boolean Returns true if the prerequisites are completed.
 */
function learndash_is_course_prerequities_completed( $post_id = 0 ) {
	$course_pre_complete = true;
	
	if ( !empty( $post_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
		if ( ( !empty( $course_id ) ) && ( learndash_get_course_prerequisite_enabled( $course_id ) ) ) {
		
			$course_pre = learndash_get_course_prerequisites( $course_id );
			if ( ! empty( $course_pre ) ) {
				$course_pre_compare = learndash_get_course_prerequisite_compare( $course_id );
				if ( $course_pre_compare == 'ANY' ) {
					$s_pre = array_search( true, $course_pre );
					if ( $s_pre !== false ) 
						$course_pre_complete = true;
					else
						$course_pre_complete = false;
				
				} else if ( $course_pre_compare == 'ALL' ) {
					$s_pre = array_search( false, $course_pre );
					if ( array_search( false, $course_pre ) === false ) 
						$course_pre_complete = true;
					else
						$course_pre_complete = false;
				}
			}
		}
	}

	return $course_pre_complete;
}

/**
 * Gets the list of course prerequisites and its status for a course.
 *
 * @since 2.4.0
 *
 * @param int $post_id Optional. The ID of the course. Default 0.
 *
 * @return array An array of course prerequisites.
 */
function learndash_get_course_prerequisites( $post_id = 0 ) {
	$courses_status_array = array();

	if ( !empty( $post_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
		if ( ( !empty( $course_id ) ) && ( learndash_get_course_prerequisite_enabled( $course_id ) ) ) {
		
			$course_pre = learndash_get_course_prerequisite( $course_id );
			if ( ! empty( $course_pre ) ) {
				$course_pre_compare = learndash_get_course_prerequisite_compare( $course_id );
			
				if ( is_string( $course_pre ) ) $course_pre = array( $course_pre );
			
				foreach( $course_pre as $c_id ) {
					//Now check if the prerequities course is completed by user or not
					$course_status = learndash_course_status( $c_id, null );
					if ( $course_status == esc_html__( 'Completed','learndash' ) ) { 
						$courses_status_array[$c_id] = true;
					} else {
						$courses_status_array[$c_id] = false;
					}
				}
			}
		}
	}
	return $courses_status_array;
}

/**
 * Gets the list of course prerequisites for a given course.
 *
 * @since 2.1.0
 *
 * @param int $course_id Optional. The ID if the course. Default 0.
 *
 * @return array An array of course prerequisite.
 */
function learndash_get_course_prerequisite( $course_id = 0 ) {
	$course_pre = learndash_get_setting( $course_id, 'course_prerequisite' );
	if ( empty( $course_pre ) ) $course_pre = array();
	
	return $course_pre;
}

/**
 * Sets new prerequisites for a course.
 *
 * @param int   $course_id            Optional. ID of the course. Default 0.
 * @param array $course_prerequisites Optional. An array of course prerequisites. Default empty array.
 *
 * @return boolean Returns true if update was successful otherwise false.
 */
function learndash_set_course_prerequisite( $course_id = 0, $course_prerequisites = array() ) {
	if ( !empty( $course_id ) ) {
		if ( ( !empty( $course_prerequisites ) ) && ( is_array( $course_prerequisites ) ) ) {
			$course_prerequisites = array_unique( $course_prerequisites );
		}
		
		return learndash_update_setting( $course_id, 'course_prerequisite', (array)$course_prerequisites );
	}
}



/**
 * Checks whether the prerequisites are enabled for a course.
 *
 * @since 2.4.0
 *
 * @param int $course_id The ID of the course.
 *
 * @return boolean Returns true if the prerequisites are enabled otherwise false.
 */
function learndash_get_course_prerequisite_enabled( $course_id ) {
	$course_pre_enabled = false;
	
	$course_id = learndash_get_course_id( $course_id );
	if (!empty( $course_id ) ) {
		$post_options = get_post_meta( $course_id, '_sfwd-courses', true );

		if ( ( isset( $post_options['sfwd-courses_course_prerequisite_enabled'] ) ) && ( $post_options['sfwd-courses_course_prerequisite_enabled'] == 'on' ) ) {
			$course_pre_enabled = true;
		} else if ( !isset( $post_options['sfwd-courses_course_prerequisite_enabled'] ) ) {
			// If the 'course_prerequisite_enabled' setting is not found we check the 'sfwd-courses_course_prerequisite'
			if ( ( isset( $post_options['sfwd-courses_course_prerequisite'] ) ) && ( !empty( $post_options['sfwd-courses_course_prerequisite'] ) ) ) {
				$course_pre_enabled = true;
				$post_options['sfwd-courses_course_prerequisite_enabled'] = 'on';
			} else {
				$post_options['sfwd-courses_course_prerequisite_enabled'] = '';
			}
			update_post_meta( $course_id, '_sfwd-courses', $post_options );
		}
	}
	
	return $course_pre_enabled;
}

/**
 * Sets the status of whether the course prerequisite is enabled or disabled.
 *
 * @param int     $course_id The ID of the course.
 * @param boolean $enabled   Optional. The value is true to enable course prerequisites. Any other
 *                           value will disable course prerequisites. Default true.
 *
 * @return boolean Returns true if the status was updated successfully otherwise false.
 */
function learndash_set_course_prerequisite_enabled( $course_id, $enabled = true ) {
	if ( $enabled === true ) 
		$enabled = 'on';
	
	if ( $enabled != 'on' )
		$enabled = '';
	
	return learndash_update_setting( $course_id, 'course_prerequisite_enabled', $enabled );
}

/**
 * Gets the prerequisites compare value for a course.
 *
 * @since 2.4.0
 *
 * @param int $post_id The ID of the course.
 *
 * @return string The compare value for the prerequisite. Value can be 'ALL' or 'ANY' by default.
 */
function learndash_get_course_prerequisite_compare( $post_id ) {

	$course_pre_compare = 'ANY';

	if ( !empty( $post_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
		if ( !empty( $course_id ) ) {
			$course_prerequisite_compare = learndash_get_setting( $course_id, 'course_prerequisite_compare' );
			if ( ( $course_prerequisite_compare == 'ANY') || ( $course_prerequisite_compare == 'ALL' ) )  {
				$course_pre_compare = $course_prerequisite_compare;
			}
		}
	}
	return $course_pre_compare;
}

/**
 * Checks if the course points are enabled for a course.
 *
 * @since 2.4.0
 *
 * @param int $post_id Optional. The course ID. Default 0.
 *
 * @return bool Returns true if the course points are enabled otherwise false.
 */
function learndash_get_course_points_enabled( $post_id = 0 ) {
	$course_points_enabled = false;

	if ( !empty( $post_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
		if ( !empty( $course_id ) ) {
			$course_points_enabled = learndash_get_setting( $course_id, 'course_points_enabled' );
			if ( $course_points_enabled == 'on' )
				$course_points_enabled = true;
		}
	}
	
	return $course_points_enabled;
}

/**
 * Gets the course points for a given course ID.
 *
 * @since 2.4.0
 *
 * @param int $post_id  Optional. Course Step or Course post ID. Default 0.
 * @param int $decimals Optional. Number of decimal places to round. Default 1.
 *
 * @return int|false Returns false if the course points are disabled otherwise returns course points.
 */
function learndash_get_course_points( $post_id = 0, $decimals = 1 ) {
	$course_points = false;

	if ( !empty( $post_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
		if ( !empty( $course_id ) ) {
			if ( learndash_get_course_points_enabled( $course_id ) ) {
				$course_points = 0;
		
				$course_points = learndash_get_setting( $course_id, 'course_points' );
				if ( !empty( $course_points ) ) {
					$course_points = learndash_format_course_points( $course_points, $decimals );
				}
			}
		}
	}
	
	return $course_points;
}

/**
 * Gets the course points access for a given course ID.
 *
 * @since 2.4.0
 *
 * @param int $post_id Optional. The ID of the course. Default 0.
 *
 * @return int|false Returns false if the course points are disabled otherwise returns course points.
 */
function learndash_get_course_points_access( $post_id = 0 ) {
	$course_points_access = false;

	if ( !empty( $post_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
		if ( !empty( $course_id ) ) {
			if ( learndash_get_course_points_enabled( $course_id ) ) {
				$course_points_access = 0;

				$course_points_access = learndash_format_course_points( learndash_get_setting( $course_id, 'course_points_access' ) );
			}
		}
	}
	
	return $course_points_access;
}

/**
 * Checks if a user can access course points.
 *
 * @param int $post_id The ID of the post.
 * @param int $user_id Optional. The ID of the user. Default 0.
 *
 * @return boolean Whether a user can access course points.
 */
function learndash_check_user_course_points_access( $post_id, $user_id = 0 ) {
	$user_can_access = true;

	if ( empty( $user_id ) ) {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			return false;
		}
	}

	if ( !empty( $post_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
		if ( ( !empty( $course_id ) ) && ( !empty( $user_id ) ) ) {
			if ( learndash_get_course_points_enabled( $course_id ) ) {
				$course_access_points = learndash_get_course_points_access( $course_id );

				if ( !empty( $course_access_points ) ) {
					$user_course_points = learndash_get_user_course_points( $user_id );
					
					if ( floatval( $user_course_points ) >= floatval( $course_access_points ) ) 
						return true;
					else
					    return false;
				}
			}
		}
	}
	
	return true;
}

/**
 * Handles the actions to be made when the user joins a course.
 *
 * Fires on `wp` hook.
 * Redirects user to login URL, adds course access to user.
 *
 * @since 2.1.0
 */
function learndash_process_course_join() {
	$user_id = get_current_user_id();

	if ( ( isset( $_POST['course_join'] ) ) && ( isset( $_POST['course_id'] ) ) ) {
		$post_label_prefix = 'course';
		$post_id = intval( $_POST['course_id'] );
		$post = get_post( $post_id );
		if ( ( ! $post ) || ( ! is_a( $post, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'course' ) !== $post->post_type ) ) {
			return;
		}
	} elseif ( ( isset( $_POST['group_join'] ) ) && ( isset( $_POST['group_id'] ) ) ) {
		$post_label_prefix = 'group';
		$post_id = intval( $_POST['group_id'] );
		$post = get_post( $post_id );
		if ( ( ! $post ) || ( ! is_a( $post, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'group' ) !== $post->post_type ) ) {
			return;
		}
	} else {
		return;
	}

	if ( empty( $user_id ) ) {
		$login_url = wp_login_url( get_permalink( $post_id ) );

		/**
		 * Filters URL that a user should be redirected to after joining a course.
		 *
		 * @since 2.1.0
		 *
		 * @param string $login_url Redirect URL.
		 * @param int    $post_id Course or Group ID.
		 */
		$login_url = apply_filters( 'learndash_' . $post_label_prefix . '_join_redirect', $login_url, $post_id );
		wp_safe_redirect( $login_url );
		exit;
	}

	/**
	 * Verify the form is valid
	 * @since 2.2.1.2
	 */
	if ( ! wp_verify_nonce( $_POST[ $post_label_prefix . '_join' ], $post_label_prefix . '_join_'. $user_id . '_' . $post_id ) ) {
		return;
	}
	
	$settings = learndash_get_setting( $post_id );

	if ( learndash_get_post_type_slug( 'group' ) === get_post_type( $post_id ) ) {
		if ( @$settings['group_price_type'] == 'free' || @$settings['group_price_type'] == 'paynow' && empty( $settings['group_price'] ) && ! empty( $settings['group_join'] ) || learndash_is_user_in_group( $user_id, $post_id ) ) {
			ld_update_group_access( $user_id, $post_id );
		}
	} elseif ( learndash_get_post_type_slug( 'course' ) === get_post_type( $post_id ) ) {
		if ( @$settings['course_price_type'] == 'free' || @$settings['course_price_type'] == 'paynow' && empty( $settings['course_price'] ) && ! empty( $settings['course_join'] ) || sfwd_lms_has_access( $post_id, $user_id ) ) {
			ld_update_course_access( $user_id, $post_id );
		}
	}
}

add_action( 'wp', 'learndash_process_course_join' );

/**
 * Updates the user activity.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param array $args {
 *    An array of user activity arguments. Default empty array.
 *
 *    @type int    $activity_id        Optional. Activity ID. Default 0.
 *    @type int    $course_id          Optional. Course ID. Default 0.
 *    @type int    $post_id            Optional. Post ID. Default 0.
 *    @type int    $user_id            Optional. User ID. Default 0.
 *    @type string $activity_type      Optional. Type of the activity. Default empty.
 *    @type string $activity_status    Optional. The status of the activity. Default empty.
 *    @type string $activity_started   Optional. The timestamp of when the activity started. Default empty.
 *    @type string $activity_completed Optional. The timestamp of when the activity got completed. Default empty.
 *    @type string $activity_updated   Optional. The timestamp of when the activity was last updated. Default empty.
 *    @type string $activity_action    Optional. The action of the activity. Value can be 'update', 'insert', or 'delete'. Default 'update'.
 *    @type string $activity_meta      Optional. The activity meta. Default empty.
 * }
 *
 * @return int The ID of the updated activity.
 */
function learndash_update_user_activity( $args = array() ) {

	global $wpdb;

	$default_args = array(
		// Can be passed in if we are updating a specific existing activity row.
		'activity_id'						=>	0,
		
		// Required. This is the ID of the Course. Unique key part 1/4
		'course_id'							=>	0,

		// Required. This is the ID of the Course, Lesson, Topic, Quiz item. Unique key part 2/4
		'post_id'							=>	0,
		
		// Optional. Will use get_current_user_id() if left 0. Unique key part 3/4
		'user_id'							=>	0,

		// Will be the token stats that described the status_times array (next argument) Can be most anything. 
		// From 'course', 'lesson', 'topic', 'access' or 'expired'. Unique key part 4/4.
		'activity_type'						=>	'',

		// true if the lesson, topic, course, quiz is complete. False if not complete. null if not started
		'activity_status'					=>	'',

		// Should be the timstamp when the 'status' started
		'activity_started'					=>	'',

		// Should be the timstamp when the 'status' completed
		'activity_completed'				=>	'',

		// Should be the timstamp when the activity record was last updated. Used as a sort column for ProPanel and other queries
		'activity_updated'					=>	'',
		
		// Flag to indicate what we are 'update', 'insert', 'delete'. The default action 'update' will cause this function
		// to check for an existing record to update (if found) 
		'activity_action'					=>	'update',	
		
		'activity_meta'						=>	''
	);
	
	$args = wp_parse_args( $args, $default_args );
	if ( empty( $args['activity_id'] ) ) {
		if ( ( empty( $args['post_id'] ) ) || ( empty( $args['activity_type'] ) ) ) {
			//error_log('ABORT #1');
			return;
		}
	}
	
	//if ( empty( $args['course_id'] ) ) {
	//	error_log('here');
	//}
	
	if ( empty( $args['user_id'] ) ) {
		// If we don't have a user_id passed via args
		if ( !is_user_logged_in() ) 
			return; // If not logged in, abort
		 
		// Else use the logged in user ID as the args user_id 
		$args['user_id'] = get_current_user_id();
	} 
	
	// End of args processing. Finally after we have applied all the logic we go out for filters. 
	/**
	 * Filters user activity arguments.
	 *
	 * @param array $args An array of user activity arguments.
	 */
	$args = apply_filters('learndash_update_user_activity_args', $args);
	if ( empty( $args ) ) return;
	
	$values_array = array(
		'user_id' 			=> 	$args['user_id'],
		'course_id' 		=> 	$args['course_id'], 
		'post_id' 			=> 	$args['post_id'],
		'activity_type'		=>	$args['activity_type'],
	);
	
	$types_array = array(
		'%d', // user_id
		'%d', // course_id
		'%d', // post_id
		'%s', // activity_type
	);

	if ( ( $args['activity_status'] === true ) || ( $args['activity_status'] === false ) ) {
		$values_array['activity_status'] = $args['activity_status'];
		$types_array[] = '%d';
	}
	
	//if ( ( $args['activity_status'] == true ) && ( !empty( $args['activity_completed'] ) ) ) {
	if ( $args['activity_completed'] !== '' ) {
		$values_array['activity_completed'] = $args['activity_completed'];
		$types_array[] = '%d';
	}

	if ( $args['activity_started'] !== '' ) {
		$values_array['activity_started'] = $args['activity_started'];
		$types_array[] = '%d';
	}

	if ( $args['activity_updated'] !== '' ) {
		$values_array['activity_updated'] = $args['activity_updated'];
		$types_array[] = '%d';
	} else {
		if ( ( empty( $args['activity_started'] ) ) && ( empty( $args['activity_completed'] ) ) ) {
			if ( !isset( $args['data_upgrade'] ) ) {
				$values_array['activity_updated'] = time();
				$types_array[] = '%d';
			} 
		} else if ( $args['activity_started'] == $args['activity_completed'] ) {
			$values_array['activity_updated'] = $args['activity_completed'];
			$types_array[] = '%d';
		} else {
			if ( $args['activity_started'] > $args['activity_completed'] ) {
				$values_array['activity_updated'] = $args['activity_started'];
				$types_array[] = '%d';
			} else if ( $args['activity_completed'] > $args['activity_started'] ) {
				$values_array['activity_updated'] = $args['activity_completed'];
				$types_array[] = '%d';
			}
		}
	}
		
	$update_ret = false;
	
	if ( $args['activity_action'] == 'update' ) {

		if ( empty( $args['activity_id'] ) ) {
			$activity = learndash_get_user_activity( $args );
			if ( null !== $activity ) {
			
				$args['activity_id'] = $activity->activity_id;
			}
		}
		
		if ( !empty( $args['activity_id'] ) ) {
			
			$update_values_array = $values_array;
			$update_types_array = $types_array;

			$update_ret = $wpdb->update( 
				LDLMS_DB::get_table_name( 'user_activity' ), 
				$update_values_array,
				array(
					'activity_id' => $args['activity_id']
				),
				$update_types_array,
				array( 
					'%d' // activity_id
				)
			);
			
		} else {
			$args['activity_action'] = 'insert';
		}
	}
	
	if ( $args['activity_action'] == 'insert' ) {
			
		$values_array['activity_updated'] = time();
		$types_array[] = '%d';
				
		$insert_ret = $wpdb->insert( 
			LDLMS_DB::get_table_name( 'user_activity' ), 
			$values_array,
			$types_array
		);
		
		if ( $insert_ret !== false) {
			$args['activity_id'] = $wpdb->insert_id;
		}
	}

	// Finally for the course we update the activity meta
	if ( ( !empty( $args['activity_id'] ) ) && ( !empty( $args['activity_meta'] ) ) )  {
		foreach( $args['activity_meta'] as $meta_key => $meta_value ) {
			learndash_update_user_activity_meta( $args['activity_id'], $meta_key, $meta_value);
		}
	}

	/**
	 * Fires after updating user activity.
	 *
	 * @param array $args An array of activity arguments.
	 */
	do_action( 'learndash_update_user_activity', $args );
	
	return $args['activity_id'];
}

/**
 * Gets the user activity.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param array $args {
 *    An array of user activity arguments. Default empty array.
 *
 *    @type int    Optional. $course_id     Course ID. Default 0.
 *    @type string $activity_type Type of the activity.
 * }
 *
 * @return object Activity object.
 */
function learndash_get_user_activity( $args = array() ) {
	global $wpdb;
	
	if ( !isset( $args['course_id'] ) )
		$args['course_id'] = 0;
	
	if ( $args['activity_type'] == 'quiz' ) {
		$data_settings_quizzes = learndash_data_upgrades_setting('user-meta-quizzes');
		if ( version_compare( $data_settings_quizzes['version'], '2.5', '>=') ) {
			$sql_str = $wpdb->prepare("SELECT * FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE user_id=%d AND course_id=%d AND post_id=%d AND activity_type=%s AND activity_completed=%d LIMIT 1", $args['user_id'], $args['course_id'], $args['post_id'], $args['activity_type'], $args['activity_completed'] );
		} else {
			$sql_str = $wpdb->prepare("SELECT * FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE user_id=%d AND post_id=%d AND activity_type=%s AND activity_completed=%d LIMIT 1", $args['user_id'], $args['post_id'], $args['activity_type'], $args['activity_completed'] );
		}
	} else {
		$data_settings_courses = learndash_data_upgrades_setting('user-meta-courses');
		if ( version_compare( $data_settings_courses['version'], '2.5', '>=') ) {
			$sql_str = $wpdb->prepare("SELECT * FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE user_id=%d AND course_id=%d AND post_id=%d AND activity_type=%s LIMIT 1", $args['user_id'], $args['course_id'], $args['post_id'], $args['activity_type'] );
		} else {
			$sql_str = $wpdb->prepare("SELECT * FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE user_id=%d AND post_id=%d AND activity_type=%s LIMIT 1", $args['user_id'], $args['post_id'], $args['activity_type'] );
		}
	}
	//error_log('sql_str['. $sql_str .']');
	$activity = $wpdb->get_row( $sql_str );		
	if ( $activity ) {
		//error_log('activity<pre>'. print_r($activity, true) .'</pre>');
		if ( property_exists( $activity, 'activity_status' ) ) {
			if ( $activity->activity_status == true )
				$activity->activity_status = true;
			else if ( $activity->activity_status == false )
				$activity->activity_status = false;
		}
	}
	return $activity;	
}

/**
 * Gets the user activity meta.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int     $activity_id                     Optional. Activity ID. Default 0.
 * @param string  $activity_meta_key               Optional. The activity meta key to get. Default empty.
 * @param boolean $return_activity_meta_value_only Optional. Whether to return only activity meta value. Default true.
 *
 * @return object Activity meta object.
 */
function learndash_get_user_activity_meta( $activity_id = 0, $activity_meta_key = '', $return_activity_meta_value_only = true ) {

	global $wpdb;

	if ( empty( $activity_id ) )
		return;
	
	if ( !empty( $activity_meta_key ) ) {
	
		$meta_sql_str = $wpdb->prepare("SELECT * FROM " . LDLMS_DB::get_table_name( 'user_activity_meta' ) . " WHERE activity_id=%d AND activity_meta_key=%s", $activity_id, $activity_meta_key);
		$activity_meta = $wpdb->get_row( $meta_sql_str );
		if ( !empty($activity_meta ) ) {
			if ( $return_activity_meta_value_only == true ) {
				if ( property_exists( $activity_meta, 'activity_meta_value' ) ) {
					return $activity_meta->activity_meta_value;
				}
			} 
		}
		return $activity_meta;
	} else {
		// Here we return ALL meta for the given activity_id
		$meta_sql_str = $wpdb->prepare( "SELECT * FROM " . LDLMS_DB::get_table_name( 'user_activity_meta' ) . " WHERE activity_id=%d", $activity_id);
		return $wpdb->get_results( $meta_sql_str );
	}
}

/**
 * Updates the user activity meta.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int         $activity_id Optional. Activity ID. Default 0.
 * @param string      $meta_key    Optional. The activity meta key to get. Default empty.
 * @param string|null $meta_value  Optional. Activity meta value. Default null.
 */
function learndash_update_user_activity_meta( $activity_id = 0, $meta_key = '', $meta_value = null) {
	global $wpdb;

	if ( ( empty( $activity_id ) ) || ( empty( $meta_key ) ) || ( $meta_value === null ) )
		return;
	
	$activity = learndash_get_user_activity_meta( $activity_id, $meta_key, false);	
	if ( null !== $activity ) {
		$wpdb->update( 
			LDLMS_DB::get_table_name( 'user_activity_meta' ),
			array(
				'activity_id'			=>	$activity_id,
				'activity_meta_key'		=>	$meta_key,
				'activity_meta_value'	=>	maybe_serialize( $meta_value )
			),
			array(
				'activity_meta_id'			=>	$activity->activity_meta_id
			),
			array(
				'%d',	// activity_id
				'%s',	// meta_key
				'%s'	// meta_value	
			),
			array(
				'%d'	// activity_meta_id
			)
		);
		
	} else {
		$wpdb->insert( 
			LDLMS_DB::get_table_name( 'user_activity_meta' ),
			array(
				'activity_id'			=>	$activity_id,
				'activity_meta_key'		=>	$meta_key,
				'activity_meta_value'	=>	maybe_serialize( $meta_value )
			),
			array(
				'%d',	// activity_id
				'%s',	// meta_key
				'%s'	// meta_value	
			)
		);
	}
}

/**
 * Deletes the user activity.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int $activity_id Optional. Activity ID. Default 0.
 */
function learndash_delete_user_activity( $activity_id = 0 ) {
	global $wpdb;
	
	if ( !empty( $activity_id ) ) {
		$wpdb->delete( 
			LDLMS_DB::get_table_name( 'user_activity' ),
			array( 'activity_id' => $activity_id ),
			array( '%d' )
		);

		$wpdb->delete( 
			LDLMS_DB::get_table_name( 'user_activity_meta' ),
			array( 'activity_id' => $activity_id ),
			array( '%d' )
		);
	}
}

/**
 * Gets all the courses with the price type open.
 *
 * Logic for this query was taken from the `sfwd_lms_has_access_fn()` function
 *
 * @since 2.3.0
 *
 * @param boolean $bypass_transient Optional. Whether to bypass transient cache. Default false.
 *
 * @return array An array of course IDs.
 */
function learndash_get_open_courses( $bypass_transient = false ) {
	global $wpdb;
	
	$transient_key = "learndash_open_courses";

	if (!$bypass_transient) {
		$courses_ids_transient = LDLMS_Transients::get( $transient_key );
	} else {
		$courses_ids_transient = false;
	}
	
	if ( $courses_ids_transient === false ) {
	
		$sql_str = "SELECT postmeta.post_id as post_id FROM ". $wpdb->postmeta ." as postmeta INNER JOIN ". $wpdb->posts ." as posts ON posts.ID = postmeta.post_id WHERE posts.post_status='publish' AND posts.post_type='sfwd-courses' AND postmeta.meta_key='_sfwd-courses' AND ( postmeta.meta_value REGEXP '\"sfwd-courses_course_price_type\";s:4:\"open\";' )";
		$course_ids = $wpdb->get_col( $sql_str );
	
		LDLMS_Transients::set( $transient_key, $course_ids, MINUTE_IN_SECONDS );
	
	} else {
		$course_ids = $courses_ids_transient;
	}
	return $course_ids;
}

/**
 * Gets all the courses with the price type paynow.
 *
 * Logic for this query was taken from the `sfwd_lms_has_access_fn()` function.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.3.0
 *
 * @param boolean $bypass_transient Optional. Whether to bypass the transient cache. Default false.
 *
 * @return array An array of course IDs.
 */
function learndash_get_paynow_courses( $bypass_transient = false ) {
	global $wpdb;
	
	$transient_key = "learndash_paynow_courses";

	if (!$bypass_transient) {
		$courses_ids_transient = LDLMS_Transients::get( $transient_key );
	} else {
		$courses_ids_transient = false;
	}
	
	if ( $courses_ids_transient === false ) {
	
		$sql_str = "SELECT postmeta.post_id FROM ". $wpdb->postmeta ." as postmeta INNER JOIN ". $wpdb->posts ." as posts ON posts.ID = postmeta.post_id WHERE posts.post_status='publish' AND posts.post_type='sfwd-courses' AND postmeta.meta_key='_sfwd-courses' AND (( postmeta.meta_value REGEXP 's:30:\"sfwd-courses_course_price_type\";s:6:\"paynow\";' ) AND ( postmeta.meta_value REGEXP 's:25:\"sfwd-courses_course_price\";s:0:\"\";' ))";
		//error_log('sql_str['. $sql_str .']');
		$course_ids = $wpdb->get_col( $sql_str );
		LDLMS_Transients::set( $transient_key, $course_ids, MINUTE_IN_SECONDS );
	
	} else {
		$course_ids = $courses_ids_transient;
	}
	return $course_ids;
}

/**
 * Gets the list of users who has access to the given course.
 *
 * @param int     $course_id     Optional. The ID of the course. Default 0.
 * @param array   $query_args    Optional. An array of `WP_User_query` arguments. Default empty array.
 * @param boolean $exclude_admin Optional. Whether to exclude admins from the user list. Default true.
 *
 * @return WP_User_Query The `WP_User_Query` object.
 */
function learndash_get_users_for_course( $course_id = 0, $query_args = array(), $exclude_admin = true ) {
	$course_user_ids = array();
	
	if ( empty( $course_id ) ) return $course_user_ids;

	$defaults = array(
		// By default WP_User_Query will return ALL users. Strange.
		'fields'		=>	'ID',
	);
	
	$query_args = wp_parse_args( $query_args, $defaults );
	
	if ( $exclude_admin == true ) {
		$query_args['role__not_in'] = array('administrator');
	}
	
	$course_price_type = learndash_get_course_meta_setting( $course_id, 'course_price_type' );
	
	if ($course_price_type == 'open') {
		
		$user_query = new WP_User_Query( $query_args );
		return $user_query;
		
	} else {
	
		$course_access_list = learndash_get_course_meta_setting( $course_id, 'course_access_list');
		$course_user_ids = array_merge( $course_user_ids, $course_access_list );

		$course_access_users = learndash_get_course_users_access_from_meta( $course_id );
		$course_user_ids = array_merge( $course_user_ids, $course_access_users );
		
		$course_groups_users = get_course_groups_users_access( $course_id );
		$course_user_ids = array_merge( $course_user_ids, $course_groups_users );

		if ( !empty( $course_user_ids ) )
			$course_user_ids = array_unique( $course_user_ids );

		$course_expired_access_users = learndash_get_course_expired_access_from_meta( $course_id );
		if ( !empty( $course_expired_access_users ) ) {
			$course_user_ids = array_diff( $course_user_ids, $course_expired_access_users );
		}

		if ( !empty( $course_user_ids ) ) {
			$query_args['include'] = $course_user_ids;
			
			$user_query = new WP_User_Query( $query_args );
			
			//$course_user_ids = $user_query->get_results();
			return $user_query;
		}
	}
	
	/*
	if ( !empty( $course_user_ids ) ) {
		
		// Finally we spin through this list of user_ids and check for expired access. 
		$course_expire_access = learndash_get_course_meta_setting( $course_id, 'expire_access' );
		if ( !empty( $course_expire_access ) ) {
		
			$expired_user_ids = array();
			foreach( $course_user_ids as $user_id ) {
				if ( ld_course_access_expired( $course_id, $user_id ) )
					$expired_user_ids[] = $user_id;
				
			}
			
			if ( !empty( $expired_user_ids ) ) {
				$course_user_ids = array_diff( $course_user_ids, $expired_user_ids );
			}
		}
	}
	*/
		
	return $course_user_ids;
}

/**
 * Sets new users to the course access list.
 *
 * @param int   $course_id        Optional. The ID of the course. Default 0.
 * @param array $course_users_new Optional. An array of user IDs to set course access. Default empty array.
 */
function learndash_set_users_for_course( $course_id = 0, $course_users_new = array() ) {

	if (!empty( $course_id ) ) {

		if ( ! empty( $course_users_new ) ) {
			$course_users_new = learndash_convert_course_access_list( $course_users_new, true );
		} else {
			$course_users_new = array();
		}

		$course_users_old = learndash_get_course_users_access_from_meta( $course_id );
		if ( ! empty( $course_users_old ) ) {
			$course_users_old = learndash_convert_course_access_list( $course_users_old, true );
		} else {
			$course_users_old = array();
		}


		$course_users_intersect = array_intersect( $course_users_new, $course_users_old );

		$course_users_add = array_diff( $course_users_new, $course_users_intersect );
		if ( ! empty( $course_users_add ) ) {
			foreach ( $course_users_add as $user_id ) {
				ld_update_course_access( $user_id, $course_id, false );
			}
		}
		
		$course_users_remove = array_diff( $course_users_old, $course_users_intersect );
		if ( ! empty( $course_users_remove ) ) {
			foreach ( $course_users_remove as $user_id ) {
				ld_update_course_access( $user_id, $course_id, true );
			}
		}
				
		// Finally clear our cache for other services 
		//$transient_key = "learndash_group_courses_" . $group_id;
		//delete_transient( $transient_key );
	}
}


/**
 * Gets the users with course access from the user meta.
 *
 * @param int $course_id Optional. The ID of the course. Default 0.
 *
 * @return array An array of user IDs that have access to course.
 */
function learndash_get_course_users_access_from_meta( $course_id = 0 ) {
	global $wpdb;
	
	$course_user_ids = array();
	
	if ( !empty( $course_id ) ) {
		// We have to do it this was because WP_User_Query cannot handle on meta EXISTS and another 'NOT EXISTS' in the same query. 
		$sql_str = $wpdb->prepare( "SELECT user_id FROM ". $wpdb->usermeta ." as usermeta WHERE meta_key = %s", 'course_'. $course_id .'_access_from');
	
		$course_user_ids = $wpdb->get_col( $sql_str );
	}
	return $course_user_ids;
}

/**
 * Gets the list of users with expired course access from the user meta.
 *
 * @param int $course_id Optional. The ID of the course. Default 0.
 *
 * @return array An array of users with expired course access.
 */
function learndash_get_course_expired_access_from_meta( $course_id = 0 ) {
	global $wpdb;
	
	$expired_user_ids = array();
	
	if ( !empty( $course_id ) ) {
		$sql_str = $wpdb->prepare( "SELECT user_id FROM ". $wpdb->usermeta ." as usermeta WHERE meta_key = %s", 'learndash_course_expired_'. $course_id);
	
		$expired_user_ids = $wpdb->get_col( $sql_str );
	}
	
	return $expired_user_ids;
}


/**
 * Gets the course settings from the course meta.
 *
 * @TODO Need to convert all references to get_post_meta for '_sfwd-courses' to use this function.
 *
 * @param int    $course_id   Optional. The ID of the course. Default 0.
 * @param string $setting_key Optional. The slug of the setting to get. Default empty.
 *
 * @return mixed Returns course settings. Passing empty setting key gets all the settings.
 */
function learndash_get_course_meta_setting( $course_id = 0, $setting_key = '' ) {
	$course_settings = array();
	
	if ( empty( $course_id ) ) return $course_settings;
	
	$meta = get_post_meta( $course_id, '_sfwd-courses', true );
	if ( ( is_null( $meta ) ) || ( !is_array( $meta ) ) ) $meta = array();
	
	// we only want/need to reformat the access list of we are returning ALL setting or just the access list
	if ( ( empty( $setting_key ) ) || ( $setting_key == 'course_access_list' ) ) {
		if ( !isset( $meta['sfwd-courses_course_access_list'] ) ) {
			$meta['sfwd-courses_course_access_list'] = '';
		}

		if ( ! empty( $meta['sfwd-courses_course_access_list'] ) ) {
			if ( is_string( $meta['sfwd-courses_course_access_list'] ) ) {
				$meta['sfwd-courses_course_access_list'] = array_map( 'absint', explode( ',', $meta['sfwd-courses_course_access_list'] ) );
			} elseif ( is_array( $meta['sfwd-courses_course_access_list'] ) ) {
				$meta['sfwd-courses_course_access_list'] = array_map( 'absint', $meta['sfwd-courses_course_access_list'] );
			} else {
				// Not sure how we can get here. Just in case.
				$meta['sfwd-courses_course_access_list'] = array();
			}
		} else {
			$meta['sfwd-courses_course_access_list'] = array();
		}

		// Need to remove the empty '0' items
		$meta['sfwd-courses_course_access_list'] = array_diff($meta['sfwd-courses_course_access_list'], array(0, ''));
	}

	if ( empty( $setting_key ) ) {
		return $meta;
	} else if ( isset( $meta['sfwd-courses_'. $setting_key] ) ) {
		return $meta['sfwd-courses_'. $setting_key];
	}
}

/**
 * Gets the list of course step IDs.
 *
 * @param int   $course_id          Optional. The ID of the course. Default 0.
 * @param array $include_post_types Optional. An array of post types to include in course steps. Default array contains 'sfwd-lessons' and 'sfwd-topic'.
 *
 * @return array An array of course step IDs.
 */
function learndash_get_course_steps_ORG( $course_id = 0, $include_post_types = array( 'sfwd-lessons', 'sfwd-topic' ) ) {
	$steps = array();
	
	if ( ( !empty( $course_id ) ) && ( !empty( $include_post_types) ) ) {
	
		$steps_query_args = array(
			'post_type' 		=> $include_post_types, 
			'posts_per_page' 	=> 	-1, 
			'post_status' 		=> 	'publish',
			'fields'			=>	'ids',
			'meta_query' 		=> 	array(
										array(
											'key'     	=> 'course_id',
											'value'   	=> intval($course_id),
											'compare' 	=> '=',
											'type'		=>	'NUMERIC'
										)
									)
		);

		//error_log('steps_query_args<pre>'. print_r($steps_query_args, true) .'</pre>');
		
		$steps_query = new WP_Query( $steps_query_args );
		if ($steps_query->have_posts())
			$steps = $steps_query->posts;
	}
	
	return $steps;
}

/**
 * Gets all the lessons and topics for a given course ID.
 *
 * For now excludes quizzes at lesson and topic level.
 *
 * @param int   $course_id          Optional. The ID of the course. Default 0.
 * @param array $include_post_types Optional. An array of post types to include in course steps. Default array contains 'sfwd-lessons' and 'sfwd-topic'.
 *
 * @return array An array of all course steps.
 */
function learndash_get_course_steps( $course_id = 0, $include_post_types = array( 'sfwd-lessons', 'sfwd-topic' ) ) {

	// The steps array will hold all the individual step counts for each post_type.
	$steps = array();
	
	// This will hold the combined steps post ids once we have run all queries. 
	$steps_all = array();
	
	if ( !empty( $course_id ) ) {
		if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			foreach( $include_post_types as $post_type ) {
				$steps[$post_type] = learndash_course_get_steps_by_type( $course_id, $post_type );
			}
		} else {
			if ( ( in_array( 'sfwd-lessons', $include_post_types ) ) || ( in_array( 'sfwd-topic', $include_post_types ) ) ) {
				$lesson_steps_query_args = array(
					'post_type' 		=> 'sfwd-lessons',
					'posts_per_page' 	=> 	-1,
					'post_status' 		=> 	'publish',
					'fields'			=>	'ids',
					'meta_query' 		=> 	array(
						array(
							'key'     	=> 'course_id',
							'value'   	=> intval($course_id),
							'compare' 	=> '=',
							'type'		=>	'NUMERIC'
						)
					)
				);

				$lesson_steps_query = new WP_Query( $lesson_steps_query_args );
				if ($lesson_steps_query->have_posts()) {
					$steps['sfwd-lessons'] = $lesson_steps_query->posts;
				}
			} 

			// For Topics we still require the parent lessons items
			if ( in_array( 'sfwd-topic', $include_post_types ) ) {
			
				if ( !empty( $steps['sfwd-lessons'] ) ) {
					$topic_steps_query_args = array(
						'post_type' 		=> 'sfwd-topic',
						'posts_per_page' 	=> 	-1,
						'post_status' 		=> 	'publish',
						'fields'			=>	'ids',
						'meta_query' 		=> 	array(
							array(
								'key'     	=> 'course_id',
								'value'   	=> intval($course_id),
								'compare' 	=> '=',
								'type'		=>	'NUMERIC'
							)
						)
					);

					if ( ( isset( $steps['sfwd-lessons'] ) ) && ( !empty( $steps['sfwd-lessons'] ) ) ) {
						$topic_steps_query_args['meta_query'][] = array(
							'key'     	=> 'lesson_id',
							'value'   	=> $steps['sfwd-lessons'],
							'compare' 	=> 'IN',
							'type'		=>	'NUMERIC'
						);
					}

					$topic_steps_query = new WP_Query( $topic_steps_query_args );
					if ($topic_steps_query->have_posts()) {
						$steps['sfwd-topic'] = $topic_steps_query->posts;
					}
				} else {
					$steps['sfwd-topic'] = array();
				}
			}
		}
	}
	
	foreach( $include_post_types as $post_type ) {
		if ( ( isset( $steps[$post_type] ) ) && ( !empty( $steps[$post_type] ) ) ) {
			$steps_all = array_merge( $steps_all, $steps[$post_type] );
		}
	}
	
	return $steps_all;
}

/**
 * Gets the total count of lessons and topics for a given course ID.
 *
 * @param int $course_id Optional. The ID of the course. Default 0.
 *
 * @return int The count of the course steps.
 */
function learndash_get_course_steps_count( $course_id = 0 ) {

	$course_steps_count = 0;
	$course_steps = learndash_get_course_steps( $course_id );
	if ( !empty( $course_steps ) )
		$course_steps_count = count( $course_steps );

	if ( has_global_quizzes( $course_id ) )
		$course_steps_count += 1;	
		
	return $course_steps_count;
}

// Get total completed steps for a given course_progress array structure. 
/**
 * Gets the total completed steps for a given course progress array.
 *
 * @param int   $user_id         Optional. The ID of the user. Default 0.
 * @param int   $course_id       Optional. The ID of the course. Default 0.
 * @param array $course_progress Optional. An array of course progress data. Default empty array.
 *
 * @return int The count of completed course steps.
 */
function learndash_course_get_completed_steps( $user_id = 0, $course_id = 0, $course_progress = array() ) {
	$steps_completed_count = 0;

	if ( ( !empty( $user_id ) ) && ( !empty( $course_id ) ) ) {
		
		if ( empty( $course_progress ) ) {
			$course_progress_all = get_user_meta( $user_id, '_sfwd-course_progress', true );
			if ( isset( $course_progress_all[$course_id] ) ) $course_progress = $course_progress_all[$course_id];
		}

		$course_lessons = learndash_course_get_steps_by_type( $course_id, 'sfwd-lessons' );
		if ( !empty( $course_lessons ) ) {
			if ( isset( $course_progress['lessons'] ) ) {
				foreach( $course_progress['lessons'] as $lesson_id => $lesson_completed ) {
					if ( in_array( $lesson_id, $course_lessons ) ) {
						$steps_completed_count += intval($lesson_completed);
					}
				}
			}
		} 
		
		$course_topics = learndash_course_get_steps_by_type( $course_id, 'sfwd-topic' );
		if ( isset( $course_progress['topics'] ) ) {
			foreach( $course_progress['topics'] as $lesson_id => $lesson_topics ) {
				if ( in_array( $lesson_id, $course_lessons ) ) {
					if ( ( is_array( $lesson_topics ) ) && ( !empty( $lesson_topics ) ) ) {
						foreach( $lesson_topics as $topic_id => $topic_completed ) {
							if ( in_array( $topic_id, $course_topics ) ) {
								$steps_completed_count += intval($topic_completed);
							}
						}
					}
				}
			}
		}

		if ( has_global_quizzes( $course_id ) ) {
			if ( is_all_global_quizzes_complete( $user_id, $course_id ) ) {
				$steps_completed_count += 1;
			} 
		}
	}
	
	return $steps_completed_count;
}

add_filter('sfwd-courses_display_options', function( $options, $location ) {
	if ( ( !isset( $options[$location.'_course_prerequisite_enabled'] ) ) || ( empty( $options[$location.'_course_prerequisite_enabled'] ) )) {
		global $post;
		if ( $post instanceof WP_Post ) {
			$settings = get_post_meta( $post->ID, '_sfwd-courses', true);
			
			if ( ( isset( $settings[$location .'_course_prerequisite'] ) ) && ( !empty( $settings[$location .'_course_prerequisite'] ) ) ) {
				$options[$location.'_course_prerequisite_enabled'] = 'on';
				$settings[$location.'_course_prerequisite_enabled'] = 'on';
				update_post_meta( $post->ID, '_sfwd-courses', $settings);
			}
		}
	}
	
	return $options;
}, 1, 2);

/**
 * Updates the users group course access.
 *
 * Fires on `learndash_update_course_access` hook.
 *
 * @param int     $user_id     The ID of the user.
 * @param int     $course_id   The ID of the course.
 * @param array   $access_list An array of course access list.
 * @param boolean $remove      Whether to user group from course access.
 */
function learndash_update_course_users_groups( $user_id, $course_id, $access_list, $remove ) {
	if ( ( !empty( $user_id ) ) && ( !empty( $course_id ) ) && ( $remove !== true ) ) {
		
		$groups = learndash_get_course_groups( $course_id, true );
		if ( !empty( $groups ) ) {
			foreach( $groups as $group_id ) {
				$ld_auto_enroll_group_courses = get_post_meta( $group_id, 'ld_auto_enroll_group_courses', true );
				/**
				 * See settings in includes/settings/settings-metaboxes/class-ld-settings-metabox-group-courses-enroll.php
				 * If the checkbox is set then ALL courses can be used to enroll into group. 
				 */
				if ( $ld_auto_enroll_group_courses == 'yes' ) {
					/**
					 * Filters whether to enroll into group for the course.
					 *
					 * @since 3.2.0
					 *
					 * @param boolean $enroll_in_group Whether to enroll the user into the group.
					 * @param integer $group_id        The Group ID.
					 * @param integer $course_id       The Course ID.
					 */
					if ( apply_filters( 'learndash_group_course_auto_enroll', true, $group_id, $course_id ) ) {
						ld_update_group_access( $user_id, $group_id );
					}
				} else {
					/**
					 * Else if the checkbox is not set and there are entries for the selective course enroll. Use those.
					 */
					$ld_auto_enroll_group_course_ids = get_post_meta( $group_id, 'ld_auto_enroll_group_course_ids', true );
					if ( ( is_array( $ld_auto_enroll_group_course_ids ) ) && ( ! empty( $ld_auto_enroll_group_course_ids ) ) ) {
						$ld_auto_enroll_group_course_ids = array_map( 'absint', $ld_auto_enroll_group_course_ids );

						if ( in_array( $course_id, $ld_auto_enroll_group_course_ids, true ) ) {
							/** This filter is documented in includes/course/ld-course-functions.php */
							if ( apply_filters( 'learndash_group_course_auto_enroll', true, $group_id, $course_id ) ) {
								ld_update_group_access( $user_id, $group_id );
							}
						}
					}
				}
			}
		}
	}
}
add_action( 'learndash_update_course_access', 'learndash_update_course_users_groups', 50, 4 );

/**
 * Gets the course completion date for a user.
 *
 * @param int $user_id   Optional. The ID of the user. Default 0.
 * @param int $course_id Optional. The ID of the course. Default 0.
 *
 * @return int The timestamp of when the course was completed. The value is 0 if the course is not completed.
 */
function learndash_user_get_course_completed_date( $user_id = 0, $course_id = 0 ) {
	$completed_on_timestamp = 0;
	if ( ( ! empty( $user_id ) ) && ( !empty( $course_id ) ) ) {
		$completed_on_timestamp = get_user_meta( $user_id, 'course_completed_' . $course_id, true );

		if ( empty( $completed_on_timestamp ) ) {
			$activity_query_args = array(
				'post_ids'		=>	$course_id,
				'user_ids'		=>	$user_id,
				'activity_type'	=>	'course',
				'per_page'		=>	1,
			);
			
			$activity = learndash_reports_get_activity( $activity_query_args );
			if ( ! empty( $activity['results'] ) ) {
				foreach( $activity['results'] as $activity_item ) {
					if ( property_exists( $activity_item, 'activity_completed' ) ) {
						$completed_on_timestamp = $activity_item->activity_completed;

						// To make the next check easier we update the user meta.
						update_user_meta( $user_id, 'course_completed_' . $course_id, $completed_on_timestamp );
						break;
					}
				}
			}
		}
	}
	
	return $completed_on_timestamp;
}

/**
 * Gets the parent step IDs for a step in a course.
 *
 * @param int $course_id Optional. The ID of the course. Default 0.
 * @param int $step_id   Optional. The ID of the step to get parent steps. Default 0.
 *
 * @return array An array of step IDs.
 */
function learndash_course_get_all_parent_step_ids( $course_id = 0, $step_id = 0 ) {
	$step_parents = array();
	
	if ( ( !empty( $course_id ) ) && ( !empty( $step_id ) ) ) {
		if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			$ld_course_steps_object = LDLMS_Factory_Post::course_steps( intval( $course_id ) );
			if ( $ld_course_steps_object ) {
				$step_parents = $ld_course_steps_object->get_item_parent_steps( $step_id );
				if ( !empty( $step_parents ) ) {
					$step_parents_2 = array();
					foreach( $step_parents as $step_parent ) {
						list( $parent_post_type, $parent_post_id ) = explode(':', $step_parent );
						$step_parents_2[] = intval( $parent_post_id );
					}
					$step_parents = array_reverse($step_parents_2);
				}
			}
		} else {
			$parent_step_id	= get_post_meta( $step_id, 'lesson_id', true );
			if ( ! empty( $parent_step_id ) ) {
				$step_parents[] = $parent_step_id;
				if ( 'sfwd-topic' === get_post_type( $parent_step_id ) ) {
					$parent_step_id	= get_post_meta( $parent_step_id, 'lesson_id', true );
					if ( ! empty( $parent_step_id ) ) {
						$step_parents[] = $parent_step_id;
					}
				} 
			}
			if ( ! empty( $step_parents ) ) {
				$step_parents = array_reverse( $step_parents );
			}
		} 
	}
	
	if ( ! empty( $step_parents ) ) {
		$step_parents = array_map( 'intval', $step_parents );
	}
	
	return $step_parents;
}

/**
 * Gets the single parent step ID for a given step ID in a course.
 *
 * @param int    $course_id Optional. Course ID. Default 0.
 * @param int    $step_id   Optional. Step ID. Default 0.
 * @param string $step_type Optional. The type of the step. Default empty.
 *
 * @return int The parent step ID.
 */
function learndash_course_get_single_parent_step( $course_id = 0, $step_id = 0, $step_type = '' ) {
	$parent_step_id = 0;
	
	if ( ( !empty( $course_id ) ) && ( !empty( $step_id ) ) ) {
		if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			$ld_course_steps_object = LDLMS_Factory_Post::course_steps( intval( $course_id ) );
			if ( $ld_course_steps_object ) {
				$parent_step_id = $ld_course_steps_object->get_parent_step_id( $step_id, $step_type );
			}
		} else {
			if ( empty( $step_type ) ) {
				$parent_step_id	= get_post_meta( $step_id, 'lesson_id', true );
			} else {
				// We only have two nested post types: Topics and quizzes. 
				$step_id_post_type = get_post_type( $step_id );
			
				// A topic only has one parent, a lesson.
				if ( $step_id_post_type == 'sfwd-topic' ) {
					$parent_step_id	= get_post_meta( $step_id, 'lesson_id', true );
					
				} else if ( $step_id_post_type == 'sfwd-quiz' ) {
					$lesson_id = $topic_id = 0;
					$parent_step_id = get_post_meta( $step_id, 'lesson_id', true );
					if ( !empty( $parent_step_id ) ) {
						$parent_step_id_post_type = get_post_type( $parent_step_id );
						if ( $parent_step_id_post_type == 'sfwd-topic' ) {
							$topic_id = $parent_step_id;
							$lesson_id = get_post_meta( $topic_id, 'lesson_id', true );
						} else if ( $parent_step_id_post_type == 'sfwd-lessons' ) {
							$lesson_id = $parent_step_id;
						}

						if ( $step_type == 'sfwd-lessons' ) {
							$parent_step_id = $lesson_id;
						} else if ( $step_type == 'sfwd-topic' ) {
							$parent_step_id = $topic_id;
						} else {
							$parent_step_id = 0;
						}
					} 
				}
			}
		}
	}
	
	return $parent_step_id;
}

/**
 * Gets the course steps by type.
 *
 * @param int    $course_id Optional. Course ID. Default 0.
 * @param string $step_type Optional. The type of the step. Default empty.
 *
 * @return array An array of course step IDs.
 */
function learndash_course_get_steps_by_type_ORG1( $course_id = 0, $step_type = '' ) {
	$course_steps_return = array();
	
	if ( ( !empty( $course_id ) ) && ( !empty( $step_type ) ) ) {
		$ld_course_steps_object = LDLMS_Factory_Post::course_steps( intval( $course_id ) );
		if ( $ld_course_steps_object ) {
			$course_steps_t = $ld_course_steps_object->get_steps('t');
			if ( ( isset( $course_steps_t[$step_type] ) ) && ( !empty( $course_steps_t[$step_type] ) ) ) {
				$course_steps_return = $course_steps_t[$step_type];
			}
		}
	}
		
	return $course_steps_return;	
}

/**
 * Gets the course steps by type.
 *
 * @param int    $course_id Optional. Course ID. Default 0.
 * @param string $step_type Optional. The type of the step. Default empty.
 *
 * @return array An array of course step IDs.
 */
function learndash_course_get_steps_by_type( $course_id = 0, $step_type = '' ) {
	$course_steps_return = array();
	
	if ( ( !empty( $course_id ) ) && ( !empty( $step_type ) ) ) {
		if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
		
			$ld_course_steps_object = LDLMS_Factory_Post::course_steps( intval( $course_id ) );
			if ( $ld_course_steps_object ) {
				$course_steps_t = $ld_course_steps_object->get_steps('t');
				if ( ( isset( $course_steps_t[$step_type] ) ) && ( !empty( $course_steps_t[$step_type] ) ) ) {
					$course_steps_return = $course_steps_t[$step_type];
				}
			}
		} else {
			$transient_key = "learndash_course_". $course_id .'_'. $step_type;
			$course_steps_return = LDLMS_Transients::get( $transient_key );
			if ( $course_steps_return === false ) {
				$lesson_order = learndash_get_course_lessons_order( $course_id );
				$steps_query_args = array(
					'post_type' 		=> $step_type, 
					'posts_per_page' 	=> 	-1, 
					'post_status' 		=> 	'publish',
					'fields'			=>	'ids',
					'order'             =>  isset( $lesson_order['order'] ) ? $lesson_order['order'] : false,
					'orderby'           =>  isset( $lesson_order['orderby'] ) ? $lesson_order['orderby'] : false,
					'meta_query' 		=> 	array(
												array(
													'key'     	=> 'course_id',
													'value'   	=> intval( $course_id ),
													'compare' 	=> '=',
												)
											)
				);
				/**
				 * Filters course steps by type query arguments.
				 *
				 * @since 2.6.0
				 *
				 * @param array  $steps_query_args An array steps query arguments.
				 * @param int    $course_id        Course ID to get steps for.
				 * @param string $step_type        Steps post type. Could be 'sfwd-lessons', 'sfwd-topics' etc.
				 */
				$steps_query_args = apply_filters( 'learndash_course_steps_by_type', $steps_query_args, $course_id, $step_type );
				if ( ! empty( $steps_query_args ) ) {
					$steps_query = new WP_Query( $steps_query_args );

					if ( $steps_query->have_posts() ) {
						$course_steps_return = $steps_query->posts;
					} else {
						$course_steps_return = array();
					}
					LDLMS_Transients::set( $transient_key, $course_steps_return, MINUTE_IN_SECONDS );
				}
			}
		}
	}
		
	return $course_steps_return;
}

/**
 * Gets the list of children steps for a given step ID.
 *
 * @param int    $course_id  Optional. Course ID. Default 0.
 * @param int    $step_id    Optional. The ID of step to get child steps. Default 0.
 * @param string $child_type Optional. The type of the child steps to get. Default empty.
 *
 * @return array An array of child step IDs.
 */
function learndash_course_get_children_of_step( $course_id = 0, $step_id = 0, $child_type = '' ) {
	$children_steps = array();
	
	if ( ( !empty( $course_id ) ) && ( !empty( $step_id ) ) ) {
		$ld_course_steps_object = LDLMS_Factory_Post::course_steps( intval( $course_id ) );
		if ( $ld_course_steps_object ) {
			$children_steps = $ld_course_steps_object->get_children_steps( $step_id, $child_type );
		}
	}
	
	return $children_steps;
	
}

/**
 * Gets the list of courses associated with a step.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int     $step_id           Optional. The ID of the step to get course list. Default 0.
 * @param boolean $return_flat_array  Optional. Whether to return single dimensional array. Default false.
 *
 * @return array An array of course list for a step. Returns an multidimesional array
 *               of course list sorted in primary and secondary course list if the
 *               `$return_flat_array` parameter is false.
 */
function learndash_get_courses_for_step( $step_id = 0, $return_flat_array = false ) {
	global $wpdb;
	
	$course_ids = array();
	$course_ids['primary'] = array();
	$course_ids['secondary'] = array();
	
	if ( ! empty( $step_id ) ) {
		$post_post_meta = get_post_meta( $step_id );
		foreach( $post_post_meta as $meta_key => $meta_values ) {
			if ( 'course_id' === $meta_key ) {
				foreach( $meta_values as $course_id ) {
					$course_id = absint( $course_id );
					if ( ! isset( $course_ids['primary'][ $course_id ] ) ) {
						$course_post = get_post( $course_id );
						if ( ( $course_post ) && ( is_a( $course_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'course' ) === $course_post->post_type ) ) {
							$course_ids['primary'][ $course_id ] = get_the_title( $course_id );
						}
					}
				}
			} else if ( substr( $meta_key, 0, strlen( 'ld_course_' ) ) === 'ld_course_' ) {
				foreach( $meta_values as $course_id ) {
					$course_id = absint( $course_id );
					if ( ! isset( $course_ids['secondary'][ $course_id ] ) ) {
						$course_post = get_post( $course_id );
						if ( ( $course_post ) && ( is_a( $course_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'course' ) === $course_post->post_type ) ) {
							$course_ids['secondary'][ $course_id ] = get_the_title( $course_id );
						}
					}
				}
			}
		}
		
		// Ensure the primary course is also part of the secondary courses.
		if ( ! empty( $course_ids['primary'] ) ) {
			foreach( $course_ids['primary'] as $p_course_id => $p_course_title ) {
				if ( ! isset( $course_ids['secondary'][ $p_course_id ] ) ) {
					update_post_meta( $step_id, 'ld_course_'. $p_course_id, $p_course_id );
				}
			}
		} else  {
			foreach( $course_ids['secondary'] as $s_course_id => $s_course_title ) {
				$course_ids['primary'][ $s_course_id ] = $s_course_title;
				learndash_update_setting( $step_id, 'course', $s_course_id );
				//update_post_meta( $step_id, 'course_id', $s_course_id );
				break;
			}
		}

		// Now ensure the primary course IDs are not included in the secondary listing.
		foreach( $course_ids['primary'] as $p_course_id => $p_course_title ) {
			if ( isset( $course_ids['secondary'][ $p_course_id ] ) ) {
				unset( $course_ids['secondary'][ $p_course_id ] );
			}
		}

		if ( $return_flat_array === true ) {
			$course_ids_flat = array();
			foreach( $course_ids['primary'] as $course_id => $course_title ) {
				if ( ! isset( $course_ids_flat[ $course_id ] ) ) {
					$course_ids_flat[ $course_id ] = $course_title;
				}
			}

			foreach( $course_ids['secondary'] as $course_id => $course_title ) {
				if ( ! isset( $course_ids_flat[ $course_id ] ) ) {
					$course_ids_flat[ $course_id ] = $course_title;
				}
			}
			
			$course_ids = $course_ids_flat;
		}

		return $course_ids;
	}
}

/**
 * Updates the filter lesson options.
 *
 * @param array  $options  Setting options.
 * @param string $location Location index.
 * @param array  $values   Current options stored for a location.
 *
 * @return array An array of lesson options.
 */
function learndash_filter_lesson_options( $options, $location, $values ) {
	//error_log('options<pre>'. print_r($options, true) .'</pre>');
	//error_log('location<pre>'. print_r($location, true) .'</pre>');
	//error_log('values<pre>'. print_r($values, true) .'</pre>');
	
	if ( ( isset( $_GET['course_id'] ) ) && ( !empty( $_GET['course_id'] ) ) ) {
		$viewed_course_id = intval( $_GET['course_id'] );
		
		if ( ( isset( $values[$location .'_course' ] ) ) && ( !empty( $values[$location .'_course' ] ) ) && ( intval( $values[$location .'_course' ] ) !== intval( $_GET['course_id'] ) ) ) {
			if ( isset( $options[$location .'_course'] ) ) 
				unset( $options[$location .'_course'] );
			if ( isset( $options[$location .'_lesson'] ) )
				unset( $options[$location .'_lesson'] );
		}
	} 
	
	return $options;
}
//add_filter( 'sfwd-lessons_display_settings', 'learndash_filter_lesson_options', 10, 3 );
//add_filter( 'sfwd-topic_display_settings', 'learndash_filter_lesson_options', 10, 3 );
//add_filter( 'sfwd-quiz_display_settings', 'learndash_filter_lesson_options', 10, 3 );

/**
 * Updates the course step post status when a post is trashed or untrashed.
 *
 * Fires on `transition_post_status` hook.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.5.0
 *
 * @param string  $new_status New post status.
 * @param string  $old_status Old post status.
 * @param WP_Post $post       The `WP_Post` object.
 */
function  learndash_transition_course_step_post_status( $new_status, $old_status, $post ) {
	global $wpdb;
	
	if ( $new_status !== $old_status ) {
		if ( ( !empty( $post ) ) && ( is_a( $post, 'WP_Post' ) ) && ( in_array( $post->post_type, array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) ) === true ) {
			$sql_str = "SELECT meta_value FROM " . $wpdb->postmeta . " WHERE post_id = " . $post->ID . " AND (meta_key = 'course_id' OR meta_key LIKE 'ld_course_%')";

			$course_ids = $wpdb->get_col( $sql_str );
			if ( !empty( $course_ids ) ) {
				$course_ids = array_unique( $course_ids );
				foreach( $course_ids as $course_id ) {
					$course_steps_object = LDLMS_Factory_Post::course_steps( $course_id );
					if ( ( is_object( $course_steps_object ) ) && (is_a( $course_steps_object, 'LDLMS_Course_Steps' ) ) ) {
						$course_steps_object->set_steps_dirty();
					}
				}
			}
		}
	}
}
add_action( 'transition_post_status', 'learndash_transition_course_step_post_status', 10, 3 ); 


/**
 * Validates the URL requests when nested URL permalinks are used.
 *
 * Fires on `wp` hook.
 *
 * @global WP_Post  $post     Global post object.
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @since 2.5.0
 *
 * @param WP $wp The `WP` instance.
 */
function learndash_check_course_step( $wp ) {	
	if ( is_single() ) {
		global $post;
		if ( ( in_array( $post->post_type, array('sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) === true ) 
		  && ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', 'nested_urls' ) == 'yes' ) ) {
			$course_slug = get_query_var('sfwd-courses');
			
			// Check first if there is an existing course part of the URL. Maybe the student is trying to user a lesson URL part
			// for a differen course. 
			if ( ! empty( $course_slug ) ) {
				$course_post = learndash_get_page_by_path( $course_slug, 'sfwd-courses' );
				if ( ( ! empty( $course_post ) ) && ( is_a( $course_post, 'WP_Post' ) ) && ( 'sfwd-courses' === $course_post->post_type ) ) {
					$step_courses = learndash_get_courses_for_step( $post->ID, true );
					if ( ( !empty( $step_courses ) ) && ( isset( $step_courses[$course_post->ID] ) ) ) {

						if ( in_array( $post->post_type, array( 'sfwd-topic', 'sfwd-quiz' ) ) === true ) {

							$parent_steps = learndash_course_get_all_parent_step_ids( $course_post->ID, $post->ID );

							if ( 'sfwd-quiz' === $post->post_type ) {
								$topic_slug = get_query_var( 'sfwd-topic' );
								if ( ! empty( $topic_slug ) ) {
									$topic_post = learndash_get_page_by_path( $topic_slug, 'sfwd-topic' );
									if ( ( ! empty( $topic_post ) ) && ( is_a( $topic_post, 'WP_Post' ) ) && ( 'sfwd-topic' === $topic_post->post_type ) ) {
										if ( ! in_array( $topic_post->ID, $parent_steps ) ) {
											$course_link = get_permalink( $course_post->ID );
											wp_redirect( $course_link );
											die();				
										}
									} else {
										$course_link = get_permalink( $course_post->ID );
											wp_redirect( $course_link );
											die();
									}
								}
								$lesson_slug = get_query_var( 'sfwd-lessons' );
								if ( ! empty( $lesson_slug ) ) {
									$lesson_post = learndash_get_page_by_path( $lesson_slug, 'sfwd-lessons' );
									if ( ( ! empty( $lesson_post ) ) && ( is_a( $lesson_post, 'WP_Post' ) ) && ( 'sfwd-lessons' === $lesson_post->post_type ) ) {
										if ( ! in_array( $lesson_post->ID, $parent_steps ) ) {
											$course_link = get_permalink( $course_post->ID );
											wp_redirect( $course_link );
											die();				
										}
									} else {
										$course_link = get_permalink( $course_post->ID );
										wp_redirect( $course_link );
										die();
									}
								}
							} else if ( 'sfwd-topic' === $post->post_type ) {
								$lesson_slug = get_query_var( 'sfwd-lessons' );
								if ( ! empty( $lesson_slug ) ) {
									$lesson_post = learndash_get_page_by_path( $lesson_slug, 'sfwd-lessons' );
									if ( ( ! empty( $lesson_post ) ) && ( is_a( $lesson_post, 'WP_Post' ) ) && ( 'sfwd-lessons' === $lesson_post->post_type ) ) {
										if ( ! in_array( $lesson_post->ID, $parent_steps ) ) {
											$course_link = get_permalink( $course_post->ID );
											wp_redirect( $course_link );
											die();
										}
									} else {
										$course_link = get_permalink( $course_post->ID );
										wp_redirect( $course_link );
										die();
									}
								}
							}
						} 

						// All is ok to return.
						return;
					} else {
						//global $wp_query;
						//$wp_query->is_404 = true;
						$course_link = get_permalink( $course_post->ID );
						wp_redirect( $course_link );
						die();
					}
				} else {
					// If we don't have a valid Course post 
			    global $wp_query;
    			$wp_query->set_404();
  
			    // 3. Throw 404
    			//status_header( 404 );
    			//nocache_headers();
 
    			// 4. Show 404 template
    			require get_404_template();
 
    			// 5. Stop execution
    			exit;
				}
			} else {
				if ( learndash_is_admin_user() ) {
					return;
				} else {
					// If we don't have a course part of the URL then we check if the step has a primary (legacy) course
					$step_courses = learndash_get_courses_for_step( $post->ID, false );
				
					// If we do have a primary (legacy) then we redirect the user there. 
					if ( !empty( $step_courses['primary'] ) ) {
						$primary_courses = array_keys($step_courses['primary'] );
						$step_permalink = learndash_get_step_permalink( $post->ID, $primary_courses[0] );
						if ( !empty( $step_permalink ) ) {
							wp_redirect( $step_permalink );
							die();
						} else {
							//global $wp_query;
							//$wp_query->is_404 = true;
							$courses_archive_link = get_post_type_archive_link( 'sfwd-courses' );
							wp_redirect( $courses_archive_link );
							die();
						}
					} else {
						if ( learndash_is_admin_user() ) {
							// Alow the admin to view the lesson/topic before it is added to a course
							return;
						} else if ( ( $post->post_type == 'sfwd-quiz' ) && ( empty( $step_courses['secondary'] ) ) ) {
							// If here we have a quiz with no primary or secondary courses. So it is standalone and allowed. 
							return;
						} else {
							//global $wp_query;
							//$wp_query->is_404 = true;
							$courses_archive_link = get_post_type_archive_link( 'sfwd-courses' );
							wp_redirect( $courses_archive_link );
							die();
						}
					}
				}
			}
		}
	}
}

add_action( 'wp', 'learndash_check_course_step' );

/**
 * Gets the page data by page path.
 *
 * @param string $slug      Optional. The slug of the page. Default empty.
 * @param string $post_type Optional. The post type slug. Default empty.
 *
 * @return WP_Post|array|null `WP_Post` object or array on success, null on failure.
 */
function learndash_get_page_by_path( $slug = '', $post_type = '' ) {
	$course_post = null;
	
	if ( ( !empty( $slug ) ) && ( !empty( $post_type ) ) ) {
		
		$course_post = get_page_by_path( $slug, OBJECT, $post_type );
		
		if ( ( defined( 'ICL_LANGUAGE_CODE' ) ) && ( ICL_LANGUAGE_CODE != '' ) ) {
			if ( function_exists( 'icl_object_id' ) ) {
				$course_post = get_page( icl_object_id( $course_post->ID, $post_type, true, ICL_LANGUAGE_CODE ) );
			}
		}
	}
	
	return $course_post;
}

/**
 * Gets the course lessons per page setting.
 *
 * This function will initially source the per_page from the course. But if we are using the
 * default lesson options setting we will use that. Then if the lessons options
 * is not set for some reason we use the default system option 'posts_per_page'.
 *
 * @since 2.5.4
 *
 * @param int $course_id Optional. The ID of the course. Default 0.
 *
 * @return int The number of lessons per page or 0.
 */
function learndash_get_course_lessons_per_page( $course_id = 0 ) {
	$course_lessons_per_page = 0;
	
	$lessons_options = learndash_get_option( 'sfwd-lessons' );
	if ( isset( $lessons_options['posts_per_page'] ) ) {
		$course_lessons_per_page = intval( $lessons_options['posts_per_page'] );
	}
	
	if ( !empty( $course_id ) ) {
		$course_settings = learndash_get_setting( intval( $course_id ) );
		
		if ( ( isset( $course_settings['course_lesson_per_page'] ) ) && ( $course_settings['course_lesson_per_page'] == 'CUSTOM' ) && ( isset( $course_settings['course_lesson_per_page_custom'] ) ) ) {
			$course_lessons_per_page = intval( $course_settings['course_lesson_per_page_custom'] );
		} else {
			if ( ( ! isset( $lessons_options['posts_per_page'] ) ) || ( is_null( $lessons_options['posts_per_page'] ) ) ) {
				$course_lessons_per_page = get_option( 'posts_per_page' );
			} else {
				$course_lessons_per_page = intval( $lessons_options['posts_per_page'] ) ;
			}
		}
	}
	
	return $course_lessons_per_page;
}


/**
 * Redirects users to the next available lesson page when course lesson pagination is enabled.
 *
 * For example, we have a course with 100 lessons and the course has per page set to 10. The student can complete
 * up to lesson 73. When the student returns to the course we don't want to default to show the first page 
 * (lessons 1-10). Instead, we want to redirect the user to page 7 showing lessons 71-80.
 *
 * @since 2.5.4
 */
function learndash_course_set_lessons_start_page( ) {
	// Last minute change to not use this for the v2.5.5 release. 
	return;
	if ( ( !is_admin() ) && ( is_single() ) ) {
		$queried_object = get_queried_object();
		if ( ( is_a( $queried_object, 'WP_Post' ) ) && ( is_user_logged_in() ) && ( !isset( $_GET['ld-lesson-page'] ) ) ) {
			if ( $queried_object->post_type == 'sfwd-courses' ) {
				/**
				 * Filters whether to redirect the user to the next available lesson page in the course.
				 *
				 * @param boolean $advance_progress Whether to redirect user to next available lesson page.
				 * @param int     $post_id          Queried object post ID.
				 * @param int     $user_id          User ID.
				 */
				if ( apply_filters( 'learndash_course_lessons_advance_progress_page', true, $queried_object->ID, get_current_user_id() ) ) {
					$course_lessons_per_page = learndash_get_course_lessons_per_page( $queried_object->ID );
					if ( $course_lessons_per_page > 0 ) {
						$user_courses = get_user_meta( get_current_user_id(), '_sfwd-course_progress', true );
						if ( ( isset( $user_courses[$queried_object->ID]['lessons'] ) ) && ( !empty( $user_courses[$queried_object->ID]['lessons'] ) ) ) {
							$lesson_paged = ceil( ( count( $user_courses[$queried_object->ID]['lessons'] ) + 1 ) / $course_lessons_per_page );
							if ( $lesson_paged > 1 ) {
								$redirect_url = add_query_arg( 'ld-lesson-page', $lesson_paged );
								wp_redirect( $redirect_url );
								die();
							}
						}
					}
				}
			} 
		}
	} 
}
//add_action( 'wp', 'learndash_course_set_lessons_start_page', 1 );

/**
 * Called from within the Coure Lessons List processing query SFWD_CPT::loop_shortcode.
 * This action will setup a global pager array to be used in templates.  
 */

$course_pager_results = array( 'pager' => array( ) );
global $course_pager_results;

/**
 * Handles the course lessons list pager.
 *
 * Fires on `learndash_course_lessons_list_pager` hook.
 *
 * @global array $course_pager_results
 *
 * @param WP_Query|null $query_result  Optional. Course lesson list `WP_Query` object. Default null.
 * @param string        $pager_context Optional. The context where pagination is shown. Default empty.
 */
function learndash_course_lessons_list_pager( $query_result = null, $pager_context = '' ) {
	global $course_pager_results;

	$course_pager_results['pager']['paged'] = 1;
	if ( ( isset( $query_result->query_vars['paged'] ) ) && ( $query_result->query_vars['paged'] > 1 ) ) {
		$course_pager_results['pager']['paged'] = $query_result->query_vars['paged'];
	}
	
	$course_pager_results['pager']['total_items'] = absint( $query_result->found_posts );
	$course_pager_results['pager']['total_pages'] = absint( $query_result->max_num_pages );
}
add_action( 'learndash_course_lessons_list_pager', 'learndash_course_lessons_list_pager', 10, 2 );

/**
 * Gets the lesson topic pagination values from HTTP get global array.
 *
 * @return array An array of lesson topic pagination values.
 */
function learndash_get_lesson_topic_paged_values() {
	$paged_values = array(
		'lesson' => 0,
		'paged' => 1
	);
	if ( ( isset( $_GET['ld-topic-page'] ) ) && ( ! empty( $_GET['ld-topic-page'] ) ) ) {
		list( $paged_values['lesson'], $paged_values['paged'] ) = explode( '-', $_GET['ld-topic-page'] );
		$paged_values['lesson'] = absint( $paged_values['lesson'] );
		$paged_values['paged'] = absint( $paged_values['paged'] );
		if ( $paged_values['paged'] < 1 ) {
			$paged_values['paged'] = 1;
		}
		if ( ( empty( $paged_values['lesson'] ) ) || ( empty( $paged_values['paged'] ) ) ) {
			$paged_values = array(
				'lesson' => 0,
				'paged' => 1
			);
		}
	}

	return $paged_values;
}

/**
 * Processes the lesson topics pagination.
 *
 * @global array $course_pager_results
 *
 * @param array $topics Optional. An array of topics. Default empty array.
 * @param array $args {
 *    An array of lesson topic pager arguments. Default empty array.
 *
 *    @type int $course_id Course ID.
 *    @type int $lesson_id Lesson ID.
 * }
 *
 * @return array An array of paged topics.
 */
function learndash_process_lesson_topics_pager( $topics = array(), $args = array() ) {
	global $course_pager_results;

	$paged_values = learndash_get_lesson_topic_paged_values();

	if ( ! empty( $topics ) ) {
		$topics_per_page = learndash_get_course_topics_per_page( $args['course_id'], $args['lesson_id'] );
		if ( ( $topics_per_page > 0 ) && ( count( $topics ) > $topics_per_page ) ) {
			$topics_chunks = array_chunk( $topics, $topics_per_page );

			$course_pager_results[ $args['lesson_id'] ] = array();
			$course_pager_results[ $args['lesson_id'] ]['pager'] = array();

			$topics_paged = 1;

			if ( ( ! empty($paged_values['lesson'] ) ) && ( $paged_values['lesson'] == $args['lesson_id'] ) ) {
				$topics_paged = $paged_values['paged'];
			} else if ( get_post_type() === learndash_get_post_type_slug( 'topic' ) ) {
				/**
				 * If we are viewing a Topic and the page is empty we load the 
				 * paged set to show the current topic item.
				 */
				foreach( $topics_chunks as $topics_chunk_page => $topics_chunk_set ) {
					$topics_ids = array_values( wp_list_pluck( $topics_chunk_set, 'ID' ) );
					if ( ( ! empty( $topics_ids ) ) && ( in_array( get_the_ID(), $topics_ids ) ) ) {
						$topics_paged = ++$topics_chunk_page;
						break;
					}
				}
			} else if ( get_post_type() === learndash_get_post_type_slug( 'quiz' ) ) {
				$parent_step_ids = learndash_course_get_all_parent_step_ids( $args['course_id'], get_the_ID() );
				if ( ! empty( $parent_step_ids ) ) {
					$parent_step_ids = array_map( 'absint', $parent_step_ids );
					$parent_step_ids = array_reverse( $parent_step_ids );
					
					if ( get_post_type( $parent_step_ids[0] ) === learndash_get_post_type_slug( 'topic' ) ) {
						// If the Quiz has a Topic parent we loop through the topic chunks to find the parent.
						foreach( $topics_chunks as $topics_chunk_page => $topics_chunk_set ) {
							$topics_ids = array_values( wp_list_pluck( $topics_chunk_set, 'ID' ) );
							if ( ( ! empty( $topics_ids ) ) && ( in_array( $parent_step_ids[0], $topics_ids ) ) ) {
								$topics_paged = ++$topics_chunk_page;
								break;
							}
						}
					} elseif ( get_post_type( $parent_step_ids[0] ) === learndash_get_post_type_slug( 'lesson' ) ) {
						/**
						 * If the Quiz has a LEsson parent we just set the last Topic chunk set because
						 * Lesson Quizzes are shown at the end.
						 */
						$topics_paged = count( $topics_chunks );
					}
				}
			}
	
			$course_pager_results[ $args['lesson_id'] ]['pager']['paged'] = $topics_paged;

			$course_pager_results[ $args['lesson_id'] ]['pager']['total_items'] = count( $topics );
			$course_pager_results[ $args['lesson_id'] ]['pager']['total_pages'] = count( $topics_chunks );

			$topics = $topics_chunks[ $topics_paged - 1 ];
		}
	}

	return $topics;
}

/**
 * Gets the course lessons order query arguments.
 *
 * The course lessons order can be set in the course or globally defined in
 * the lesson options. This function will check all logic and return the
 * correct setting.
 *
 * @since 2.5.4
 *
 * @param int $course_id Optional. The ID of the course. Default 0.
 *
 * @return array An array of course lessons order query arguments.
 */
function learndash_get_course_lessons_order( $course_id = 0 ) {
	$course_lessons_args = array( 'order' => '', 'orderby' => '' );
	
	if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {	
		$course_lessons_args['orderby'] = 'post__in';
		return $course_lessons_args;
		
	} else {
		$lessons_options = learndash_get_option( 'sfwd-lessons' );
		if ( ( isset( $lessons_options['order'] ) ) && ( !empty( $lessons_options['order'] ) ) ) 
			$course_lessons_args['order'] = $lessons_options['order'];

		if ( ( isset( $lessons_options['orderby'] ) ) && ( !empty( $lessons_options['orderby'] ) ) ) 
			$course_lessons_args['orderby'] = $lessons_options['orderby'];
	}

	if ( !empty( $course_id ) ) {
		$course_settings = learndash_get_setting( $course_id );
		if ( ( isset( $course_settings['course_lesson_order'] ) ) && ( !empty( $course_settings['course_lesson_order'] ) ) ) 
			$course_lessons_args['order'] = $course_settings['course_lesson_order'];

		if ( ( isset( $course_settings['course_lesson_orderby'] ) ) && ( !empty( $course_settings['course_lesson_orderby'] ) ) ) 
			$course_lessons_args['orderby'] = $course_settings['course_lesson_orderby'];
	}	
	
	/**
	 * Filters course lessons order query arguments.
	 *
	 * @param array $course_lesson_args An arry of course lesson order arguments.
	 * @param int   $course_id          Course ID.
	 */
	return apply_filters( 'learndash_course_lessons_order', $course_lessons_args, $course_id );
}

/**
 * Converts and gets the course access list.
 *
 * The function converts the standard comma-separated list of user IDs
 * used for the course_access_list field. The conversion is to trim and ensure
 * the values are integer and not empty.
 *
 * @since 2.5.9
 *
 * @param string  $course_access_list Optional. String of comma separated user IDs. Default empty.
 * @param boolean $return_array       Optional. Whether to return array. True to return array and false to return string. Default false.
 *
 * @return string|array The list of user IDs.
 */
function learndash_convert_course_access_list( $course_access_list = '', $return_array = false ) {
	if ( ! empty( $course_access_list ) ) {
		
		// Convert the comma separated list into an array.
		if ( is_string( $course_access_list ) ) {
			$course_access_list = explode( ',', $course_access_list );
		} 

		// Now normalize the array elements.
		if ( is_array( $course_access_list ) ) {
			$course_access_list = array_map( 'absint', $course_access_list );
			$course_access_list = array_unique( $course_access_list, SORT_NUMERIC );
			$course_access_list = array_diff( $course_access_list, array( 0 ) );
		}

		// Prepare the return value.
		if ( true !== $return_array ) {
			$course_access_list = implode( ',', $course_access_list );
		}
	} else if ( true === $return_array ) {
		$course_access_list = array();
	}

	return $course_access_list;
}

/**
 * Determines the number of lesson topics to display per page.
 *
 * @since 3.0.0
 *
 * @param int $course_id Optional. Parent Course ID. Default 0.
 * @param int $lesson_id Optional. Parent Lesson ID. Default 0.
 *
 * @return int The number of lesson topics per page.
 */
function learndash_get_course_topics_per_page( $course_id = 0, $lesson_id = 0 ) {
	$course_topics_per_page = 0;
	
	$lessons_options = learndash_get_option( 'sfwd-lessons' );
	if ( isset( $lessons_options['posts_per_page'] ) ) {
		$course_topics_per_page = intval( $lessons_options['posts_per_page'] );
	}
	
	if ( !empty( $course_id ) ) {
		$course_settings = learndash_get_setting( intval( $course_id ) );
		
		if ( ( isset( $course_settings['course_lesson_per_page'] ) ) && ( $course_settings['course_lesson_per_page'] == 'CUSTOM' ) && ( isset( $course_settings['course_topic_per_page_custom'] ) ) ) {
			$course_topics_per_page = intval( $course_settings['course_topic_per_page_custom'] );
		} 
	}
	
	return $course_topics_per_page;
}

/**
 * Transitions the course steps logic from using shared steps to legacy.
 *
 * @since 3.0.0
 *
 * @param int $course_id Optional. Course ID to process. Default 0.
 */
function learndash_transition_course_shared_steps( $course_id = 0 ) {
	if ( ! empty( $course_id ) ) {
		if ( 'yes' !== LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' )  ) {	
					$course_steps = get_post_meta( $course_id, 'ld_course_steps', true );
					if ( isset( $course_steps['h'] ) ) {
						// If here then Shared Steps was enabled 

						$ld_course_steps_object = LDLMS_Factory_Post::course_steps( $course_id );
						$ld_course_steps_object->set_steps( $course_steps['h'] );
					}
				}
	}
}

/**
 * Checks whether to use the legacy course access list.
 *
 * @return boolean Returns true to use legacy course access list otherwise false.
 */
function learndash_use_legacy_course_access_list() {
	$use_legacy_course_access_list = true;

	$data_course_access_convert = learndash_data_upgrades_setting( 'course-access-lists-convert' );
	if ( $data_course_access_convert ) {
		$use_legacy_course_access_list = false;

	}
	/**
	 * Filters whether to use legacy course access list or not.
	 *
	 * @param boolean $use_legacy_course_access_list Whether to use legacy course access list.
	 */
	return apply_filters( 'learndash_use_legacy_course_access_list', $use_legacy_course_access_list );
}

/**
 * Gets the user's last active (last updated) course ID.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.1.3
 *
 * @param int $user_id Optional. User ID. Default 0.
 *
 * @return int The last active course ID.
 */
function learndash_get_last_active_course( $user_id = 0 ) {
	global $wpdb;

	$last_course_id = 0;

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	if ( ! empty( $user_id ) ) {

		$query_str = $wpdb->prepare( "SELECT post_id FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " 
		WHERE user_id=%d 
		AND activity_type='course' 
		AND activity_status = 0 
		AND activity_completed = ''
		ORDER BY activity_updated DESC",
		$user_id);

		$query_result = $wpdb->get_var( $query_str );
		$last_course_id = absint( $query_result );
	}

	return $last_course_id;
}


/**
 * Gets the user's last active step for a course.
 *
 * @since 2.1.3
 *
 * @param int $user_id   Optional. User ID. Default 0.
 * @param int $course_id Optional. Course ID. Default 0.
 *
 * @return int The last active course step ID.
 */
function learndash_user_course_last_step( $user_id = 0, $course_id = 0 ) {
	global $wpdb;

	$last_course_step_id = 0;

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	if ( ! empty( $user_id ) ) {
		if ( empty( $course_id ) ) {
			$course_id = learndash_get_last_active_course( $user_id );
		}
		if ( ! empty( $course_id ) ) {
			$query_str = $wpdb->prepare( "SELECT user_activity_meta.activity_meta_value FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " as user_activity
			INNER JOIN " . LDLMS_DB::get_table_name( 'user_activity_meta' ) . " as user_activity_meta
			ON user_activity.activity_id = user_activity_meta.activity_id
			WHERE user_activity.user_id=%d 
			AND user_activity.post_id=%d
			AND user_activity.activity_type='course' 
			AND user_activity_meta.activity_meta_key= 'steps_last_id'
		
			ORDER BY activity_updated DESC",
			$user_id, $course_id );

			$query_result = $wpdb->get_var( $query_str );
			$last_course_step_id = absint( $query_result );
		}
	}

	return $last_course_step_id;
}


/**
 * Check if user can bypass action ($context).
 *
 * @since 3.1.7
 *
 * @param int    $user_id User ID.
 * @param string $context The specific action to check for.
 * @param array  $args Optional array of args related to the 
 * context. Typically starting with an step ID, Course ID, etc.
 * @return bool True if user can bypass. Otherwise fale.
 */
function learndash_can_user_bypass( $user_id = 0, $context = 'learndash_course_progression', $args = array() ) {
	if ( empty( $user_id ) ) {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}
	}

	$can_bypass = false;
	if ( ! empty( $user_id ) ) {
		if ( ( learndash_is_admin_user( $user_id ) ) && ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_General_Admin_User', 'bypass_course_limits_admin_users' ) ) ) {
			$can_bypass = true;
		} elseif ( ( learndash_is_group_leader_user( $user_id ) ) && ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Groups_Group_Leader_User', 'bypass_course_limits' ) ) ) {
			$can_bypass = true;
		}
	}

	/**
	 * Filters user can bypass logic.
	 *
	 * @since 3.2.0
	 *
	 * @param boolean $can_bypass Whether the user can bypass $context.
	 * @param int     $user_id    User ID.
	 * @param string  $context The specific action to check for.
	 * @param array  $args Optional array of args related to the 
 	 * context. Typically starting with an step ID, Course ID, etc.
	 */
	$can_bypass = apply_filters( 'learndash_user_can_bypass', $can_bypass, $user_id, $context, $args );
	
	return $can_bypass;
}

/**
 * Checks if the user has access to a course.
 *
 * @todo  duplicate function, exists in other places
 *        check it's use and consolidate
 *
 * @since 2.1.0
 *
 * @param int      $course_id Course ID.
 * @param int|null $user_id   Optional. User ID. Default null.
 *
 * @return boolean Returns true if the user has access otherwise false.
 */
function ld_course_check_user_access( $course_id, $user_id = null ) {
	return sfwd_lms_has_access( $course_id, $user_id );
}
