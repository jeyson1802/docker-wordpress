<?php
/**
 * Zoom Recordings helpers
 *
 * @package BuddyBoss\Zoom
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Add recording for meeting.
 *
 * @since 1.0.0
 *
 * @param array $args
 *
 * @return int Inserted Recording ID.
 */
function bp_zoom_recording_add( $args = array() ) {
	global $wpdb;

	$bp_prefix = bp_core_get_table_prefix();

	$r = bp_parse_args(
		$args,
		array(
			'id'           => '',
			'recording_id' => '',
			'meeting_id'   => '',
			'uuid'         => '',
			'details'      => '',
			'password'     => '',
			'file_type'    => '',
			'start_time'   => bp_core_current_time(),
		)
	);

	$wpdb->insert(
		$bp_prefix . 'bp_zoom_recordings',
		array(
			'id'           => $r['id'],
			'recording_id' => $r['recording_id'],
			'meeting_id'   => $r['meeting_id'],
			'uuid'         => $r['uuid'],
			'details'      => is_array( $r['details'] ) || is_object( $r['details'] ) ? json_encode( $r['details'] ) : $r['details'],
			'password'     => $r['password'],
			'file_type'    => $r['file_type'],
			'start_time'   => $r['start_time'],
		),
		array(
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
		)
	);

	return $wpdb->insert_id;
}

/**
 * Update recording.
 *
 * @since 1.0.0
 *
 * @param array $args
 * @param array $where
 *
 * @return bool|false|int
 */
function bp_zoom_recording_update( $args = array(), $where = array() ) {
	global $wpdb;

	$bp_prefix = bp_core_get_table_prefix();

	$r = bp_parse_args(
		$args,
		array(
			'id'           => '',
			'recording_id' => '',
			'meeting_id'   => '',
			'uuid'         => '',
			'details'      => '',
			'password'     => '',
			'file_type'    => '',
			'start_time'   => '',
		)
	);

	$w = bp_parse_args(
		$where,
		array(
			'id'           => '',
			'recording_id' => '',
			'meeting_id'   => '',
			'uuid'         => '',
			'file_type'    => '',
			'start_time'   => '',
		)
	);

	$value      = array();
	$value_args = array();

	if ( ! empty( $r['id'] ) ) {
		$value['id']  = $r['id'];
		$value_args[] = '%d';
	}

	if ( ! empty( $r['recording_id'] ) ) {
		$value['recording_id'] = $r['recording_id'];
		$value_args[]          = '%s';
	}

	if ( ! empty( $r['meeting_id'] ) ) {
		$value['meeting_id'] = $r['meeting_id'];
		$value_args[]        = '%s';
	}

	if ( ! empty( $r['uuid'] ) ) {
		$value['uuid'] = $r['uuid'];
		$value_args[]  = '%s';
	}

	if ( ! empty( $r['details'] ) ) {
		$value['details'] = is_array( $r['details'] ) || is_object( $r['details'] ) ? json_encode( $r['details'] ) : $r['details'];
		$value_args[]     = '%s';
	}

	if ( ! empty( $r['password'] ) ) {
		$value['password'] = $r['password'];
		$value_args[]      = '%s';
	}

	if ( ! empty( $r['file_type'] ) ) {
		$value['file_type'] = $r['file_type'];
		$value_args[]       = '%s';
	}

	if ( ! empty( $r['start_time'] ) ) {
		$value['start_time'] = $r['start_time'];
		$value_args[]        = '%s';
	}

	$where_value      = array();
	$where_value_args = array();

	if ( ! empty( $w['id'] ) ) {
		$where_value['id']  = $w['id'];
		$where_value_args[] = '%d';
	}

	if ( ! empty( $w['recording_id'] ) ) {
		$where_value['recording_id'] = $w['recording_id'];
		$where_value_args[]          = '%s';
	}

	if ( ! empty( $w['meeting_id'] ) ) {
		$where_value['meeting_id'] = $w['meeting_id'];
		$where_value_args[]        = '%s';
	}

	if ( ! empty( $w['uuid'] ) ) {
		$where_value['uuid'] = $w['uuid'];
		$where_value_args[]  = '%s';
	}

	if ( ! empty( $r['file_type'] ) ) {
		$where_value['file_type'] = $r['file_type'];
		$where_value_args[]       = '%s';
	}

	if ( ! empty( $r['start_time'] ) ) {
		$where_value['start_time'] = $r['start_time'];
		$where_value_args[]        = '%s';
	}

	return $wpdb->update(
		$bp_prefix . 'bp_zoom_recordings',
		$value,
		$where_value,
		$value_args,
		$where_value_args
	);
}

/**
 * Delete meeting recording.
 *
 * @since 1.0.0
 *
 * @param array $where
 *
 * @return bool|false|int True if deleted, false otherwise.
 */
function bp_zoom_recording_delete( $where = array() ) {
	global $wpdb;

	$bp_prefix = bp_core_get_table_prefix();

	$w = bp_parse_args(
		$where,
		array(
			'id'           => '',
			'recording_id' => '',
			'meeting_id'   => '',
			'uuid'         => '',
			'file_type'    => ''
		)
	);

	$where_value      = array();
	$where_value_args = array();

	if ( ! empty( $w['id'] ) ) {
		$where_value['id']  = $w['id'];
		$where_value_args[] = '%d';
	}

	if ( ! empty( $w['recording_id'] ) ) {
		$where_value['recording_id'] = $w['recording_id'];
		$where_value_args[]          = '%s';
	}

	if ( ! empty( $w['meeting_id'] ) ) {
		$where_value['meeting_id'] = $w['meeting_id'];
		$where_value_args[]        = '%s';
	}

	if ( ! empty( $w['uuid'] ) ) {
		$where_value['uuid'] = $w['uuid'];
		$where_value_args[]  = '%s';
	}

	if ( ! empty( $w['file_type'] ) ) {
		$where_value['file_type'] = $w['file_type'];
		$where_value_args[]       = '%s';
	}

	return $wpdb->delete(
		$bp_prefix . 'bp_zoom_recordings',
		$where_value,
		$where_value_args
	);
}

/**
 * Get recordings.
 *
 * @since 1.0.0
 *
 * @param array $col
 * @param array $where
 *
 * @return array|object Recording results.
 */
function bp_zoom_recording_get( $col = array(), $where = array() ) {
	global $wpdb;
	$bp_prefix = bp_core_get_table_prefix();

	$r = bp_parse_args(
		$col,
		array(
			'id'           => '',
			'recording_id' => '',
			'meeting_id'   => '',
			'uuid'         => '',
			'details'      => '',
			'password'     => '',
			'file_type'    => '',
		)
	);

	$w = bp_parse_args(
		$where,
		array(
			'id'           => '',
			'recording_id' => '',
			'meeting_id'   => '',
			'uuid'         => '',
			'file_type'    => '',
		)
	);

	$value      = array();
	$value_args = array();

	if ( ! empty( $r['id'] ) ) {
		$value['id']  = $r['id'];
		$value_args[] = '%d';
	}

	if ( ! empty( $r['recording_id'] ) ) {
		$value['recording_id'] = $r['recording_id'];
		$value_args[]          = '%s';
	}

	if ( ! empty( $r['meeting_id'] ) ) {
		$value['meeting_id'] = $r['meeting_id'];
		$value_args[]        = '%s';
	}

	if ( ! empty( $r['uuid'] ) ) {
		$value['uuid'] = $r['uuid'];
		$value_args[]  = '%s';
	}

	if ( ! empty( $r['details'] ) ) {
		$value['details'] = is_array( $r['details'] ) || is_object( $r['details'] ) ? json_encode( $r['details'] ) : $r['details'];
		$value_args[]     = '%s';
	}

	if ( ! empty( $r['password'] ) ) {
		$value['password'] = $r['password'];
		$value_args[]      = '%s';
	}

	if ( ! empty( $r['file_type'] ) ) {
		$value['file_type'] = $r['file_type'];
		$value_args[]       = '%s';
	}

	$where_value      = array();
	$where_value_args = array();

	if ( ! empty( $w['id'] ) ) {
		$where_value['id']  = $w['id'];
		$where_value_args[] = '%d';
	}

	if ( ! empty( $w['recording_id'] ) ) {
		$where_value['recording_id'] = $w['recording_id'];
		$where_value_args[]          = '%s';
	}

	if ( ! empty( $w['meeting_id'] ) ) {
		$where_value['meeting_id'] = $w['meeting_id'];
		$where_value_args[]        = '%s';
	}

	if ( ! empty( $w['uuid'] ) ) {
		$where_value['uuid'] = $w['uuid'];
		$where_value_args[]  = '%s';
	}

	if ( ! empty( $w['file_type'] ) ) {
		$where_value['file_type'] = $w['file_type'];
		$where_value_args[]       = '%s';
	}

	$where_conditions = array();
	foreach ( $where_value as $w_key => $w_value ) {
		$where_conditions[] = $w_key . ' = "' . $w_value . '"';
	}

	$where_conditions[] = 'file_type != "TIMELINE"';

	$query =
		"SELECT " . ( empty( $value ) ? "*" : implode( ',', $value ) ) . " 
						FROM {$bp_prefix}bp_zoom_recordings " . ( ! empty( $where_conditions ) ? "WHERE " . implode( ' AND ', $where_conditions ) : "" ) . ' ORDER BY start_time DESC';

	if ( count( $value ) > 1 || empty( $value ) ) {
		return $wpdb->get_results(
			$query
		);
	} else {
		return $wpdb->get_col(
			$query
		);
	}
}
