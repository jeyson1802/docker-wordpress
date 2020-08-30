<?php
/**
 * BuddyBoss Platform Pro Core Functions.
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Check if bb pro license is valid or not.
 *
 * @since 1.0.0
 *
 * @return bool License is valid then true otherwise true.
 */
function bbp_pro_is_license_valid() {
	$whitelist_addr = array(
		'127.0.0.1',
		'::1'
	);

	if ( in_array( $_SERVER['REMOTE_ADDR'], $whitelist_addr ) ) {
		return true;
	}

	$whitelist_domain = array(
		'.test',
		'.dev',
		'staging.',
	);

	$return = false;
	foreach ( $whitelist_domain as $domain ) {
		if ( false !== strpos( $_SERVER['SERVER_NAME'], $domain ) ) {
			$return = true;
		}
	}

	if ( $return ) {
		return true;
	}

	$saved_licenses = get_option( 'bboss_updater_saved_licenses' );
	if ( is_multisite() ) {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		if ( is_plugin_active_for_network( bb_platform_pro()->basename ) ) {
			$saved_licenses = get_site_option( 'bboss_updater_saved_licenses' );
		}
	}

	$license_exists = false;
	if ( ! empty( $saved_licenses ) ) {
		foreach ( $saved_licenses as $package_id => $license_details ) {
			if ( ! empty( $license_details['license_key'] ) && ! empty( $license_details['product_keys'] ) && is_array( $license_details['product_keys'] ) && ( in_array( 'BB_THEME', $license_details['product_keys'] ) || in_array( 'BB_PLATFORM_PRO', $license_details['product_keys'] ) ) ) {
				$license_exists = true;
				break;
			}
		}
	}

	return $license_exists;
}

/**
 * Output the BB Platform pro database version.
 *
 * @since 1.0.4
 */
function bbp_pro_db_version() {
	echo bbp_pro_get_db_version();
}
/**
 * Return the BB Platform pro database version.
 *
 * @since 1.0.4
 *
 * @return string The BB Platform pro database version.
 */
function bbp_pro_get_db_version() {
	return bb_platform_pro()->db_version;
}

/**
 * Output the BB Platform pro database version.
 *
 * @since 1.0.4
 */
function bbp_pro_db_version_raw() {
	echo bbp_pro_get_db_version_raw();
}

/**
 * Return the BB Platform pro database version.
 *
 * @since 1.0.4
 *
 * @return string The BB Platform pro version direct from the database.
 */
function bbp_pro_get_db_version_raw() {
	$bbp = bb_platform_pro();
	return ! empty( $bbp->db_version_raw ) ? $bbp->db_version_raw : 0;
}

