<?php
/**
 * Zoom integration filters
 *
 * @package BuddyBoss\Zoom
 * @since 1.0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'bbp_pro_core_install', 'bp_zoom_pro_core_install_zoom_integration' );
add_filter( 'bbp_pro_update_to_1_0_4', 'bp_zoom_pro_update_to_1_0_4' );

/**
 * Install or upgrade zoom integration.
 *
 * @since 1.0.4
 */
function bp_zoom_pro_core_install_zoom_integration() {
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$switched_to_root_blog = false;

	// Make sure the current blog is set to the root blog.
	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
		$switched_to_root_blog = true;
	}

	$sql             = array();
	$charset_collate = $GLOBALS['wpdb']->get_charset_collate();
	$bp_prefix       = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$bp_prefix}bp_zoom_meetings (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				group_id bigint(20) NOT NULL,
				activity_id bigint(20) NOT NULL,
				user_id bigint(20) NOT NULL,
				host_id varchar(150) NOT NULL,
				type int(10) NOT NULL DEFAULT 2,
				title varchar(300) NOT NULL,
				description varchar(800) NULL,
				start_date datetime NOT NULL,
				start_date_utc datetime NOT NULL,
				timezone varchar(150) NOT NULL,
				password varchar(150) NOT NULL,
				duration int(11) NOT NULL,
				join_before_host bool DEFAULT 0,
				host_video bool DEFAULT 0,
				participants_video bool DEFAULT 0,
				mute_participants bool DEFAULT 0,
				waiting_room bool DEFAULT 0,
				meeting_authentication bool DEFAULT 0,
				recurring bool DEFAULT 0,
				auto_recording varchar(75) DEFAULT 'none',
				alternative_host_ids text NULL,
				meeting_id varchar(150) NOT NULL,
				hide_sitewide bool DEFAULT 0,
				parent varchar(150) DEFAULT 0,
				zoom_type varchar(150) DEFAULT 'meeting',
				KEY group_id (group_id),
				KEY activity_id (activity_id),
				KEY meeting_id (meeting_id)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_zoom_meeting_meta (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				meeting_id bigint(20) NOT NULL,
				meta_key varchar(255) DEFAULT NULL,
				meta_value longtext DEFAULT NULL,
				KEY meeting_id (meeting_id),
				KEY meta_key (meta_key(191))
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_zoom_recordings (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				recording_id varchar(255) NOT NULL,
				meeting_id bigint(20) NOT NULL,
				uuid varchar(255) NOT NULL,
				details varchar(800) NULL,
				file_type varchar(800) NULL,
				password varchar(150) NOT NULL,
				start_time datetime NOT NULL,
				KEY recording_id (recording_id),
				KEY meeting_id (meeting_id)
			) {$charset_collate};";

	dbDelta( $sql );

	if ( $switched_to_root_blog ) {
		restore_current_blog();
	}
}

/**
 * BuddyBoss Pro zoom update to 1.0.4
 *
 * @since 1.0.4
 */
function bp_zoom_pro_update_to_1_0_4() {
	global $wpdb;
	$bp_prefix       = bp_core_get_table_prefix();

	$zoom_meeting_query = "DELETE FROM {$bp_prefix}bp_zoom_recordings WHERE file_type = 'TIMELINE'";
	$wpdb->query( $zoom_meeting_query );
}
