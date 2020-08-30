<?php
/**
 * Shortcode for usermeta
 *
 * @since 2.1.0
 *
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [usermeta] shortcode
 *
 * This shortcode takes a parameter named field, which is the name of the user meta data field to be displayed.
 * Example: [usermeta field="display_name"] would display the user's Display Name.
 *
 * @since 2.1.0
 *
 * @param  array  $attr    shortcode attributes
 * @param  string $content content of shortcode
 * @return string            output of shortcode
 */
function learndash_usermeta_shortcode( $attr, $content = '' ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	// We clear out content because there is no reason to retain it.
	$content = '';

	$attr = shortcode_atts(
		array(
			'field'   => '',
			'user_id' => get_current_user_id(),
		),
		$attr
	);

	/**
	 * Added logic to allow admin and group_leader to view certificate from other users. Should proably be somewhere else
	 *
	 * @since 2.3
	 */
	// $post_type = '';
	// if ( get_query_var( 'post_type' ) ) {
	// $post_type = get_query_var( 'post_type' );
	// if ( $post_type == 'sfwd-certificates' ) {
	// if ( ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) )
	// && ( ( isset( $_GET['user'] ) ) && (!empty( $_GET['user'] ) ) ) ) {
	// $attr['user_id'] = intval( $_GET['user'] );
	// }
	// }
	// }

	if ( ( ! empty( $attr['user_id'] ) ) && ( ! empty( $attr['field'] ) ) ) {

		if ( ( learndash_is_admin_user() ) || ( $attr['user_id'] == get_current_user_id() ) ) {
			$usermeta_available_fields = array( $attr['field'] => $attr['field'] );
		} else {
			$usermeta_available_fields = learndash_get_usermeta_shortcode_available_fields( $attr );
		}

		if ( ! is_array( $usermeta_available_fields ) ) {
			$usermeta_available_fields = array( $usermeta_available_fields );
		}

		if ( array_key_exists( $attr['field'], $usermeta_available_fields ) === true ) {
			$value = '';

			// First check the userdata fields
			$userdata = get_userdata( intval( $attr['user_id'] ) );
			if ( ( ( $userdata ) && ( $userdata instanceof WP_User ) ) ) {
				$value = $userdata->{$attr['field']};
			}

			/**
			 * Filters usermeta shortcode field attribute value.
			 *
			 * @since 2.4.0
			 *
			 * @param string $value                    Usermeta field attribute value.
			 * @param array  $attributes               An array of shortocode attributes.
			 * @param array  $usermeta_available_fields An array of available user meta fields.
			 */
			$content = apply_filters( 'learndash_usermeta_shortcode_field_value_display', $value, $attr, $usermeta_available_fields );
		}
	}

	return $content;
}

add_shortcode( 'usermeta', 'learndash_usermeta_shortcode' );
