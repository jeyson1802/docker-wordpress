<?php
/**
 * Deprecated functions from past LearnDash versions. You shouldn't use these
 * functions and look for the alternatives instead. The functions will be
 * removed in a later version.
 *
 * @package LearnDash
 * @subpackage Deprecated
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Deprecated functions come here to die.
 */


if ( ! function_exists( 'is_group_leader' ) ) {
	/**
	 * Checks if a user is a group leader
	 *
	 * @since 2.1.0
	 *
	 * @deprecated 2.3.0 Use learndash_is_group_leader_user()
	 *
	 * @param int|WP_User $user `WP_User` instance or user ID.
	 *
	 * @return boolean
	 */
	function is_group_leader( $user ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '2.3.0', 'learndash_is_group_leader_user()' );
		}

		return learndash_is_group_leader_user( $user );
	}
}

if ( ! function_exists( 'learndash_group_updated_messages' ) ) {
	/**
	 * Set 'updated' admin messages for Groups post type
	 *
	 * @since 2.1.0
	 * 
	 * @deprecated 2.6.4 Use learndash_post_updated_messages()
	 *
	 * @param  array $messages Messages.
	 *
	 * @return array $messages Messages.
	 */
	function learndash_group_updated_messages( $messages ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '2.6.4', 'learndash_post_updated_messages()' );
		}

		return learndash_post_updated_messages( $messages );
	}
}

// Get all users with explicit 'course_XX_access_from' access
if ( ! function_exists( 'get_course_users_access_from_meta' ) ) {
	/**
	 * Gets the user course access from the meta.
	 *
	 * @deprecated 2.6.4 Use learndash_get_course_users_access_from_meta()
	 *
	 * @param int $course_id Optional. Course ID. Default 0.
	 *
	 * @return array
	 */
	function get_course_users_access_from_meta( $course_id = 0 ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '2.6.4', 'learndash_get_course_users_access_from_meta()' );
		}

		return learndash_get_course_users_access_from_meta( $course_id );
	}
}


// Get all the users for a given course_id that have 'learndash_course_expired_XX' user meta records.
if ( ! function_exists( 'get_course_expired_access_from_meta' ) ) {
	/**
	 * Gets the user expired course access from the meta.
	 *
	 * @deprecated 2.6.4 Use learndash_get_course_expired_access_from_meta()
	 *
	 * @param int $couese_id Optional. Course ID. Default 0.
	 *
	 * @return array
	 */
	function get_course_expired_access_from_meta( $couese_id = 0 ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '2.6.4', 'learndash_get_course_expired_access_from_meta()' );
		}

		return learndash_get_course_expired_access_from_meta( $course_id );
	}
}

// Utility function to att the course settings in meta. Better than having this over inline over and over again.
if ( ! function_exists( 'get_course_meta_setting' ) ) {

	/**
	 * Gets the course settings from the meta.
	 *
	 * @deprecated 2.6.4 Use learndash_get_course_meta_setting()
	 *
	 * @param int    $course_id   Optional. Course ID. Default 0.
	 * @param string $setting_key Optional. Settings key. Default empty.
	 *
	 * @return array|void
	 */
	function get_course_meta_setting( $course_id = 0, $setting_key = '' ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '2.6.4', 'learndash_get_course_meta_setting()' );
		}

		return learndash_get_course_meta_setting( $course_id, $setting_key );
	}
}


if ( ! function_exists( 'leandash_redirect_post_location' ) ) {
	/**
	 * Used when editing Lesson, Topic, Quiz or Question post items. This filter is needed to add
	 * the 'course_id' parameter back to the edit URL after the post is submitted (saved).
	 *
	 * @deprecated 2.6.4 Use learndash_redirect_post_location()
	 *
	 * @since 2.5.0
	 * 
	 * @param string $location Optional. Location.  Default empty.
	 */
	function leandash_redirect_post_location( $location = '' ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '2.6.4', 'learndash_redirect_post_location()' );
		}

		return learndash_redirect_post_location( $location );
	}
}


if ( ! function_exists( 'ld_course_access_update' ) ) {
	/**
	 * Updates the course access time for a user.
	 *
	 * @since 2.6.0
	 *
	 * @deprecated Use ld_course_access_from_update()
	 *
	 * @param int     $course_id Course ID for update.
	 * @param int     $user_id User ID for update.
	 * @param mixed   $access Optional. Value can be a date string (YYYY-MM-DD hh:mm:ss or integer value. Default empty.
	 * @param boolean $is_gmt Optional. If $access value is GMT (true) or relative to site timezone (false). Default false.
	 *
	 * @return boolean Returns true if success.
	 */
	function ld_course_access_update( $course_id, $user_id, $access = '', $is_gmt = false ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '.0', 'ld_course_access_from_update()' );
		}

		return ld_course_access_from_update( $course_id, $user_id, $access, $is_gmt );
	}
}

if ( ( ! class_exists( 'Learndash_Admin_Settings_Data_Upgrades' ) ) && ( class_exists( 'Learndash_Admin_Data_Upgrades' ) ) ) {
	class Learndash_Admin_Settings_Data_Upgrades {
		public static function get_instance( $instance_key = '' ) {
			if ( function_exists( '_deprecated_function' ) ) {
				_deprecated_function( 'Learndash_Admin_Settings_Data_Upgrades::get_instance()', '2.6.0', 'Learndash_Admin_Data_Upgrades::get_instance()' );
			}

			return Learndash_Admin_Data_Upgrades::get_instance();
		}
	}
}

if ( ! function_exists( 'learndash_get_valid_transient' ) ) {
	/**
	 * Gets the valid transient.
	 *
	 * @deprecated 3.1.0 Use LDLMS_Transients::get()
	 *
	 * @param string $transient_key Optional. Transient key. Default empty.
	 *
	 * @return mixed
	 */
	function learndash_get_valid_transient( $transient_key = '' ) {
		//if ( function_exists( '_deprecated_function' ) ) {
		//	_deprecated_function( __FUNCTION__, '3.1', 'LDLMS_Transients::get' );
		//}

		return LDLMS_Transients::get( $transient_key );
	}
}

if ( ! function_exists( 'learndash_set_transient' ) ) {

	/**
	 * Sets the transient data.
	 *
	 * @deprecated 3.1.0 Use LDLMS_Transients::set()
	 *
	 * @param string $transient_key    Optional. Transient key. Default empty
	 * @param string $transient_data   Optional. Transient data. Default empty
	 * @param int    $transient_expire Optional. Transient expiry time in seconds. Default 60.
	 *
	 * @return boolean
	 */
	function learndash_set_transient( $transient_key = '', $transient_data = '', $transient_expire = MINUTE_IN_SECONDS ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.1', 'LDLMS_Transients::set()' );
		}
		
		return LDLMS_Transients::set( $transient_key, $transient_data, $transient_expire );
	}
}

if ( ! function_exists( 'learndash_purge_transients' ) ) {
	/**
	 * Purges all the transients.
	 *
	 * @deprecated 3.1.0 Use LDLMS_Transients::purge_all()
	 */
	function learndash_purge_transients() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.1', 'LDLMS_Transients::purge_all()' );
		}

		return LDLMS_Transients::purge_all();
	}
}

if ( ! function_exists( 'learndash_get_prior_installed_version' ) ) {
	/**
	 * Gets the prior installed version.
	 *
	 * @deprecated 3.1.2 Use LDLMS_Transients::purge_all()
	 *
	 * @return mixed
	 */
	function learndash_get_prior_installed_version() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.1.2', 'learndash_data_upgrades_setting()' );
		}

		return learndash_data_upgrades_setting( 'prior_version' );
	}
}

if ( ! function_exists( 'post2pdf_conv_post_to_pdf' ) ) {
	/**
	 * Converts post data to pdf.
	 *
	 * @deprecated 3.2 Use learndash_certificate_post_shortcode()
	 */
	function post2pdf_conv_post_to_pdf() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.0', 'learndash_certificate_post_shortcode' );
		}

		return learndash_certificate_post_shortcode();
	}
}


if ( ! function_exists( 'learndash_user_can_bypass_course_limits' ) ) {
	/**
	 * LearnDash user can bypass course limits
	 *
	 * @deprecated 3.1.7 Use learndash_can_user_bypass()
	 */
	function learndash_user_can_bypass_course_limits( $user_id = null ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.0', 'learndash_can_user_bypass' );
		}

		return learndash_can_user_bypass( $user_id );
	}
}

if ( ! function_exists( 'is_course_prerequities_completed' ) ) {
	/**
	 * Is course prerequities completed
	 *
	 * @deprecated 3.1.7 Use learndash_is_course_prerequities_completed()
	 */
	function is_course_prerequities_completed( $course_id = null ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.0', 'learndash_is_course_prerequities_completed' );
		}

		return learndash_is_course_prerequities_completed( $course_id );
	}
}

