<?php
/**
 * Zoom integration helpers
 *
 * @package BuddyBoss\Zoom
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Returns Zoom Integration path.
 *
 * @since 1.0.0
 */
function bp_zoom_integration_path( $path = '' ) {
	return trailingslashit( bb_platform_pro()->integration_dir ) . 'zoom/' . trim( $path, '/\\' );
}

/**
 * Returns Zoom Integration url.
 *
 * @since 1.0.0
 */
function bp_zoom_integration_url( $path = '' ) {
	return trailingslashit( bb_platform_pro()->integration_url ) . 'zoom/' . trim( $path, '/\\' );
}

/**
 * Enqueue scripts and styles.
 *
 * @since 1.0.0
 */
function bp_zoom_enqueue_scripts_and_styles() {
    global $wp;
	$rtl_css = is_rtl() ? '-rtl' : '';
	$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	wp_enqueue_style( 'bp-zoom-meeting', bp_zoom_integration_url( '/assets/css/bp-zoom-meeting' . $rtl_css . $min . '.css' ), array(), bb_platform_pro()->version );

	if ( ! wp_script_is( 'bp-nouveau-magnific-popup' ) ) {
		wp_enqueue_script( 'bp-nouveau-magnific-popup', buddypress()->plugin_url . 'bp-core/js/vendor/magnific-popup.js', array(), bp_get_version(), true );
	}
	wp_enqueue_script( 'bp-zoom-mask-js', trailingslashit( bb_platform_pro()->plugin_url ) . 'assets/js/vendor/jquery.mask.js', array(), '5.0.4', true );
	wp_enqueue_script( 'bp-zoom-meeting-js', bp_zoom_integration_url( '/assets/js/bp-zoom-meeting' . $min . '.js' ), array(), bb_platform_pro()->version, true );
	wp_enqueue_script( 'jquery-countdown', trailingslashit( bb_platform_pro()->plugin_url ) . 'assets/js/vendor/jquery.countdown' . $min . '.js', array(), '1.0', true );

	$meetings_url      = '';
	$past_meetings_url = '';
	$group_id          = false;
	if ( bp_is_group() ) {
		$group_id          = bp_get_current_group_id();
		$current_group     = groups_get_current_group();
		$group_link        = bp_get_group_permalink( $current_group );
		$meetings_url      = trailingslashit( $group_link . 'zoom' );
		$past_meetings_url = trailingslashit( $group_link . 'zoom/past-meetings' );
	}

	wp_localize_script(
		'bp-zoom-meeting-js',
		'bp_zoom_meeting_vars',
		array(
			'ajax_url'                => bp_core_ajax_url(),
			'bp_zoom_key'             => bp_zoom_api_key(),
			'bp_zoom_secret'          => bp_zoom_api_secret(),
			'home_url'                => home_url( $wp->request ),
			'is_single_meeting'       => bp_zoom_is_single_meeting(),
			'group_id'                => $group_id,
			'group_meetings_url'      => $meetings_url,
			'group_meetings_past_url' => $past_meetings_url,
			'meeting_delete_nonce'    => wp_create_nonce( 'bp_zoom_meeting_delete' ),
			'meeting_confirm_msg'     => __( 'Are you sure you want to delete this meeting?', 'buddyboss-pro' ),
			'user'                    => array(
				'name'  => bp_core_get_user_displayname( bp_loggedin_user_id() ),
				'email' => bp_core_get_user_email( bp_loggedin_user_id() )
			)
		)
	);

    $inline_js = 'var $ = jQuery;';
    $inline_js .= 'var daysStr = "' . esc_html__( 'Days', 'buddyboss-pro' ) . '";';
    $inline_js .= 'var hoursStr = "' . esc_html__( 'Hours', 'buddyboss-pro' ) . '";';
    $inline_js .= 'var minutesStr = "' . esc_html__( 'Minutes', 'buddyboss-pro' ) . '";';
    $inline_js .= 'var secondsStr = "' . esc_html__( 'Seconds', 'buddyboss-pro' ) . '";';
	wp_add_inline_script( 'bp-zoom-meeting-js', $inline_js, 'before' );
}

add_action( 'wp_enqueue_scripts', 'bp_zoom_enqueue_scripts_and_styles', 99 );

/**
 * Retrieve an meeting or meetings.
 *
 * The bp_zoom_meeting_get() function shares all arguments with BP_Zoom_Meeting::get().
 * The following is a list of bp_zoom_meeting_get() parameters that have different
 * default values from BP_Zoom_Meeting::get() (value in parentheses is
 * the default for the bp_zoom_meeting_get()).
 *   - 'per_page' (false)
 *
 * @since 1.0.0
 *
 * @see BP_Zoom_Meeting::get() For more information on accepted arguments
 *      and the format of the returned value.
 *
 * @param array|string $args See BP_Zoom_Meeting::get() for description.
 * @return array $meeting See BP_Zoom_Meeting::get() for description.
 */
function bp_zoom_meeting_get( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'max'           => false,        // Maximum number of results to return.
			'fields'        => 'all',
			'page'          => 1,            // Page 1 without a per_page will result in no pagination.
			'per_page'      => false,        // results per page
			'sort'          => 'DESC',       // sort ASC or DESC
			'order_by'      => false,       // order by

			// want to limit the query.
			'group_id'      => false,
			'meeting_id'    => false,
			'activity_id'   => false,
			'user_id'       => false,
			'parent'        => false,
			'since'         => false,
			'from'          => false,
			'recorded'      => false,
			'recurring'     => false,
			'meta_query'    => false,
			'search_terms'  => false,        // Pass search terms as a string
			'count_total'   => false,
			'hide_sitewide' => false,
			'zoom_type'     => false,
		),
		'meeting_get'
	);

	$meeting = BP_Zoom_Meeting::get(
		array(
			'page'          => $r['page'],
			'per_page'      => $r['per_page'],
			'group_id'      => $r['group_id'],
			'meeting_id'    => $r['meeting_id'],
			'activity_id'   => $r['activity_id'],
			'parent'        => $r['parent'],
			'user_id'       => $r['user_id'],
			'since'         => $r['since'],
			'from'          => $r['from'],
			'max'           => $r['max'],
			'sort'          => $r['sort'],
			'order_by'      => $r['order_by'],
			'search_terms'  => $r['search_terms'],
			'count_total'   => $r['count_total'],
			'fields'        => $r['fields'],
			'recorded'      => $r['recorded'],
			'recurring'     => $r['recurring'],
			'meta_query'    => $r['meta_query'],
			'hide_sitewide' => $r['hide_sitewide'],
			'zoom_type'     => $r['zoom_type'],
		)
	);

	/**
	 * Filters the requested meeting item(s).
	 *
	 * @since 1.0.0
	 *
	 * @param BP_Zoom_Meeting  $meeting Requested meeting object.
	 * @param array     $r     Arguments used for the meeting query.
	 */
	return apply_filters_ref_array( 'bp_zoom_meeting_get', array( &$meeting, &$r ) );
}

/**
 * Fetch specific meeting items.
 *
 * @since 1.0.0
 *
 * @see BP_Zoom_Meeting::get() For more information on accepted arguments.
 *
 * @param array|string $args {
 *     All arguments and defaults are shared with BP_Zoom_Meeting::get(),
 *     except for the following:
 *     @type string|int|array Single meeting ID, comma-separated list of IDs,
 *                            or array of IDs.
 * }
 * @return array $activity See BP_Zoom_Meeting::get() for description.
 */
function bp_zoom_meeting_get_specific( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'meeting_ids'   => false,      // A single meeting_id or array of IDs.
			'max'           => false,      // Maximum number of results to return.
			'page'          => 1,          // Page 1 without a per_page will result in no pagination.
			'per_page'      => false,      // Results per page.
			'sort'          => 'DESC',     // Sort ASC or DESC.
			'order_by'      => false,     // Order by.
			'group_id'      => false,     // Filter by group id.
			'meeting_id'    => false,     // Filter by meeting id.
			'since'         => false,     // Return item since date.
			'from'          => false,     // Return item from date.
			'recorded'      => false,     // Return only recorded items.
			'recurring'     => false,     // Return only recurring items.
			'hide_sitewide' => false,
			'zoom_type'     => false,
			'meta_query'    => false,     // Meta query.
		),
		'meeting_get_specific'
	);

	$get_args = array(
		'in'            => $r['meeting_ids'],
		'max'           => $r['max'],
		'page'          => $r['page'],
		'per_page'      => $r['per_page'],
		'sort'          => $r['sort'],
		'order_by'      => $r['order_by'],
		'group_id'      => $r['group_id'],
		'meeting_id'    => $r['meeting_id'],
		'since'         => $r['since'],
		'from'          => $r['from'],
		'recorded'      => $r['recorded'],
		'recurring'     => $r['recurring'],
		'meta_query'    => $r['meta_query'],
		'hide_sitewide' => $r['hide_sitewide'],
		'zoom_type'     => $r['zoom_type'],
	);

	/**
	 * Filters the requested specific meeting item.
	 *
	 * @since 1.0.0
	 *
	 * @param BP_Zoom_Meeting      $meeting    Requested meeting object.
	 * @param array         $args     Original passed in arguments.
	 * @param array         $get_args Constructed arguments used with request.
	 */
	return apply_filters( 'bp_zoom_meeting_get_specific', BP_Zoom_Meeting::get( $get_args ), $args, $get_args );
}

/**
 * Add an meeting item.
 *
 * @since 1.0.0
 *
 * @param array|string $args {
 *     An array of arguments.
 *     @type int|bool $id                Pass an meeting ID to update an existing item, or
 *                                       false to create a new item. Default: false.
 *     @type int|bool $group_id           ID of the blog Default: current group id.
 *     @type string   $title             Optional. The title of the meeting item.

 *     @type string   $error_type        Optional. Error type. Either 'bool' or 'wp_error'. Default: 'bool'.
 * }
 * @return WP_Error|bool|int The ID of the meeting on success. False on error.
 */
function bp_zoom_meeting_add( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'id'                     => false,
			'group_id'               => false,
			'activity_id'            => false,
			'user_id'                => bp_loggedin_user_id(),
			'host_id'                => '',
			'title'                  => '',
			'description'            => '',
			'start_date'             => bp_core_current_time(),
			'timezone'               => '',
			'duration'               => false,
			'meeting_authentication' => false,
			'password'               => false,
			'join_before_host'       => false,
			'waiting_room'           => false,
			'host_video'             => false,
			'participants_video'     => false,
			'mute_participants'      => false,
			'recurring'              => false,
			'hide_sitewide'          => false,
			'auto_recording'         => 'none',
			'alternative_host_ids'   => '',
			'meeting_id'             => '',
			'parent'                 => '',
			'zoom_type'              => 'meeting',
			'type'                   => 2,
			'start_date_utc'         => wp_date( 'mysql', null, new DateTimeZone( 'UTC' ) ),
			'error_type'             => 'bool',
		),
		'meeting_add'
	);

	// Setup meeting to be added.
	$meeting                         = new BP_Zoom_Meeting( $r['id'] );
	$meeting->user_id                = (int) $r['user_id'];
	$meeting->group_id               = (int) $r['group_id'];
	$meeting->activity_id            = (int) $r['activity_id'];
	$meeting->host_id                = $r['host_id'];
	$meeting->title                  = $r['title'];
	$meeting->description            = $r['description'];
	$meeting->start_date             = $r['start_date'];
	$meeting->timezone               = $r['timezone'];
	$meeting->duration               = (int) $r['duration'];
	$meeting->meeting_authentication = (bool) $r['meeting_authentication'];
	$meeting->waiting_room           = (bool) $r['waiting_room'];
	$meeting->recurring              = (bool) $r['recurring'];
	$meeting->join_before_host       = (bool) $r['join_before_host'];
	$meeting->host_video             = (bool) $r['host_video'];
	$meeting->participants_video     = (bool) $r['participants_video'];
	$meeting->mute_participants      = (bool) $r['mute_participants'];
	$meeting->auto_recording         = $r['auto_recording'];
	$meeting->password               = $r['password'];
	$meeting->hide_sitewide          = $r['hide_sitewide'];
	$meeting->alternative_host_ids   = $r['alternative_host_ids'];
	$meeting->meeting_id             = $r['meeting_id'];
	$meeting->start_date_utc         = $r['start_date_utc'];
	$meeting->parent                 = $r['parent'];
	$meeting->type                   = (int) $r['type'];
	$meeting->zoom_type              = $r['zoom_type'];
	$meeting->error_type             = $r['error_type'];

	// save meeting
	$save = $meeting->save();

	if ( 'wp_error' === $r['error_type'] && is_wp_error( $save ) ) {
		return $save;
	} elseif ( 'bool' === $r['error_type'] && false === $save ) {
		return false;
	}

	/**
	 * Fires at the end of the execution of adding a new meeting item, before returning the new meeting item ID.
	 *
	 * @since 1.0.0
	 *
	 * @param object $meeting Meeting object.
	 * @param array $r Meeting data before save.
	 */
	do_action( 'bp_zoom_meeting_add', $meeting, $r );

	return $meeting->id;
}

/**
 * Delete meeting.
 *
 * @since 1.0.0
 *
 * @param array|string $args To delete specific meeting items, use
 *                           $args = array( 'id' => $ids ); Otherwise, to use
 *                           filters for item deletion, the argument format is
 *                           the same as BP_Zoom_Meeting::get().
 *                           See that method for a description.
 *
 * @return bool|int The ID of the meeting on success. False on error.
 */
function bp_zoom_meeting_delete( $args = '' ) {

	// Pass one or more the of following variables to delete by those variables.
	$args = bp_parse_args(
		$args,
		array(
			'id'          => false,
			'meeting_id'  => false,
			'group_id'    => false,
			'activity_id' => false,
			'user_id'     => false,
			'parent'      => false,
		)
	);

	/**
	 * Fires before an meeting item proceeds to be deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Array of arguments to be used with the meeting deletion.
	 */
	do_action( 'bp_before_zoom_meeting_delete', $args );

	$meeting_ids_deleted = BP_Zoom_Meeting::delete( $args );
	if ( empty( $meeting_ids_deleted ) ) {
		return false;
	}

	// Delete meeting meta.
	foreach ( $meeting_ids_deleted as $id ) {
		bp_zoom_meeting_delete_meta( $id );
	}

	/**
	 * Fires after the meeting item has been deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Array of arguments used with the meeting deletion.
	 */
	do_action( 'bp_zoom_meeting_delete', $args );

	/**
	 * Fires after the meeting item has been deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param array $meeting_ids_deleted Array of affected meeting item IDs.
	 */
	do_action( 'bp_zoom_meeting_deleted_meetings', $meeting_ids_deleted );

	return true;
}

/** Meta *********************************************************************/

/**
 * Delete a meta entry from the DB for an meeting item.
 *
 * @since 1.0.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int    $meeting_id ID of the meeting item whose metadata is being deleted.
 * @param string $meta_key    Optional. The key of the metadata being deleted. If
 *                            omitted, all metadata associated with the meeting
 *                            item will be deleted.
 * @param string $meta_value  Optional. If present, the metadata will only be
 *                            deleted if the meta_value matches this parameter.
 * @param bool   $delete_all  Optional. If true, delete matching metadata entries
 *                            for all objects, ignoring the specified object_id. Otherwise,
 *                            only delete matching metadata entries for the specified
 *                            meeting item. Default: false.
 * @return bool True on success, false on failure.
 */
function bp_zoom_meeting_delete_meta( $meeting_id, $meta_key = '', $meta_value = '', $delete_all = false ) {

	// Legacy - if no meta_key is passed, delete all for the item.
	if ( empty( $meta_key ) ) {
		$all_meta = bp_zoom_meeting_get_meta( $meeting_id );
		$keys     = ! empty( $all_meta ) ? array_keys( $all_meta ) : array();

		// With no meta_key, ignore $delete_all.
		$delete_all = false;
	} else {
		$keys = array( $meta_key );
	}

	$retval = true;

	add_filter( 'query', 'bp_filter_metaid_column_name' );
	foreach ( $keys as $key ) {
		$retval = delete_metadata( 'meeting', $meeting_id, $key, $meta_value, $delete_all );
	}
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Get metadata for a given meeting item.
 *
 * @since 1.0.0
 *
 * @param int    $meeting_id ID of the meeting item whose metadata is being requested.
 * @param string $meta_key    Optional. If present, only the metadata matching
 *                            that meta key will be returned. Otherwise, all metadata for the
 *                            meeting item will be fetched.
 * @param bool   $single      Optional. If true, return only the first value of the
 *                            specified meta_key. This parameter has no effect if meta_key is not
 *                            specified. Default: true.
 * @return mixed The meta value(s) being requested.
 */
function bp_zoom_meeting_get_meta( $meeting_id = 0, $meta_key = '', $single = true ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = get_metadata( 'meeting', $meeting_id, $meta_key, $single );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	/**
	 * Filters the metadata for a specified meeting item.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $retval      The meta values for the meeting item.
	 * @param int    $meeting_id ID of the meeting item.
	 * @param string $meta_key    Meta key for the value being requested.
	 * @param bool   $single      Whether to return one matched meta key row or all.
	 */
	return apply_filters( 'bp_zoom_meeting_get_meta', $retval, $meeting_id, $meta_key, $single );
}

/**
 * Update a piece of meeting meta.
 *
 * @since 1.0.0
 *
 * @param int    $meeting_id ID of the meeting item whose metadata is being updated.
 * @param string $meta_key    Key of the metadata being updated.
 * @param mixed  $meta_value  Value to be set.
 * @param mixed  $prev_value  Optional. If specified, only update existing metadata entries
 *                            with the specified value. Otherwise, update all entries.
 * @return bool|int Returns false on failure. On successful update of existing
 *                  metadata, returns true. On successful creation of new metadata,
 *                  returns the integer ID of the new metadata row.
 */
function bp_zoom_meeting_update_meta( $meeting_id, $meta_key, $meta_value, $prev_value = '' ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = update_metadata( 'meeting', $meeting_id, $meta_key, $meta_value, $prev_value );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Add a piece of meeting metadata.
 *
 * @since 1.0.0
 *
 * @param int    $meeting_id ID of the meeting item.
 * @param string $meta_key    Metadata key.
 * @param mixed  $meta_value  Metadata value.
 * @param bool   $unique      Optional. Whether to enforce a single metadata value for the
 *                            given key. If true, and the object already has a value for
 *                            the key, no change will be made. Default: false.
 * @return int|bool The meta ID on successful update, false on failure.
 */
function bp_zoom_meeting_add_meta( $meeting_id, $meta_key, $meta_value, $unique = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = add_metadata( 'meeting', $meeting_id, $meta_key, $meta_value, $unique );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Update recording data for the meeting.
 *
 * @param int $meeting_id Meeting ID
 * @param object $meeting Meeting Object
 *
 * @return bool
 * @since 1.0.0
 */
function bp_zoom_meeting_update_recordings_data( $meeting_id, $meeting = false ) {

    if ( empty( $meeting_id ) ) {
        return false;
    }

    if ( empty( $meeting ) ) {
	    $meeting = new BP_Zoom_Meeting( $meeting_id );
    }

    if ( isset( $meeting->is_past ) && ! $meeting->is_past ) {
        return false;
    }

    // check count first.
	$recording_count = bp_zoom_meeting_get_meta( $meeting_id, 'zoom_recording_count', true );

    if ( ! empty( $recording_count ) ) {
        return $recording_count;
    }

    // check if checked first.
	$recording_checked = bp_zoom_meeting_get_meta( $meeting_id, 'zoom_recording_checked', true );

    if ( '1' === $recording_checked ) {
        return false;
    }

    if ( ! empty( $meeting->group_id ) ) {
	    $api_key    = groups_get_groupmeta( $meeting->group_id, 'bp-group-zoom-api-key', true );
	    $api_secret = groups_get_groupmeta( $meeting->group_id, 'bp-group-zoom-api-secret', true );

	    bp_zoom_conference()->zoom_api_key    = ! empty( $api_key ) ? $api_key : '';
	    bp_zoom_conference()->zoom_api_secret = ! empty( $api_secret ) ? $api_secret : '';
    }

	$recordings = bp_zoom_conference()->recordings_by_meeting( $meeting->meeting_id );

	if ( ! empty( $recordings['response'] ) ) {
		$recordings = $recordings['response'];

		if ( ! empty( $recordings->recording_count ) && $recordings->recording_count > 0 ) {
			bp_zoom_meeting_update_meta( $meeting_id, 'zoom_recording_count', $recordings->recording_count );
		}

		if ( ! empty( $recordings->recording_files ) ) {
			bp_zoom_meeting_update_meta( $meeting_id, 'zoom_recording_files', $recordings->recording_files );
        }

		bp_zoom_meeting_update_meta( $meeting_id, 'zoom_recording_checked', '1' );
	}
}

/**
 * Integration > Zoom Conference > Enable
 *
 * @since 1.0.0
 */
function bp_zoom_settings_callback_enable_field() {
	?>
	<input name="bp-zoom-enable"
		   id="bp-zoom-enable"
		   type="checkbox"
		   value="1"
		   <?php checked( bp_zoom_is_zoom_enabled() ); ?>
	/>
	<label for="bp-zoom-enable">
		<?php _e( 'Allow Zoom meetings on this site', 'buddyboss-pro' ); ?>
	</label>
	<?php
}

/**
 * Checks if zoom is enabled.
 *
 * @since 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is zoom enabled or not
 */
function bp_zoom_is_zoom_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_zoom_is_zoom_enabled', (bool) bp_get_option( 'bp-zoom-enable', $default ) );
}

function bp_zoom_is_zoom_setup() {
	$email  = bp_zoom_api_email();
	$key    = bp_zoom_api_key();
	$secret = bp_zoom_api_secret();
	$host   = bp_zoom_api_host();
	if ( ! bp_zoom_is_zoom_enabled() || empty( $host ) || empty( $email ) || empty( $key ) || empty( $secret ) ) {
		return false;
	}

	return true;
}

/**
 * Callback function for api key in zoom integration.
 *
 * @since 1.0.0
 */
function bp_zoom_settings_callback_api_key_field() {
	?>
	<input name="bp-zoom-api-key"
		   id="bp-zoom-api-key"
		   type="text"
		   value="<?php echo esc_html( bp_zoom_api_key() ); ?>"
		   placeholder="<?php _e( 'Zoom API Key', 'buddyboss-pro' ); ?>"
		   aria-label="<?php _e( 'Zoom API Key', 'buddyboss-pro' ); ?>"
	/>
    <p class="description"><?php _e( 'To find your Zoom API Key, you first need to create a JWT app in the <a href="https://marketplace.zoom.us/" target="_blank">Zoom Marketplace</a>.', 'buddyboss-pro' ); ?></p>
	<?php
}

/**
 * Get Zoom API Key
 *
 * @since 1.0.0
 * @param string $default
 *
 * @return mixed|void Zoom API Key
 */
function bp_zoom_api_key( $default = '' ) {
	return apply_filters( 'bp_zoom_api_key', bp_get_option( 'bp-zoom-api-key', $default ) );
}

/**
 * Callback function for api secret in zoom integration.
 *
 * @since 1.0.0
 */
function bp_zoom_settings_callback_api_secret_field() {
	?>
	<input name="bp-zoom-api-secret"
		   id="bp-zoom-api-secret"
		   type="text"
		   value="<?php echo esc_html( bp_zoom_api_secret() ); ?>"
		   placeholder="<?php _e( 'Zoom API Secret', 'buddyboss-pro' ); ?>"
		   aria-label="<?php _e( 'Zoom API Secret', 'buddyboss-pro' ); ?>"
	/>
    <p class="description"><?php _e( 'To find your Zoom API key, you first need to create a JWT app in the <a href="https://marketplace.zoom.us/" target="_blank">Zoom Marketplace</a>.', 'buddyboss-pro' ); ?></p>
	<?php
}

/**
 * Get Zoom API Secret
 *
 * @since 1.0.0
 * @param string $default
 *
 * @return mixed|void Zoom API Key
 */
function bp_zoom_api_secret( $default = '' ) {
	return apply_filters( 'bp_zoom_api_secret', bp_get_option( 'bp-zoom-api-secret', $default ) );
}

/**
 * Callback function for api email in zoom integration.
 *
 * @since 1.0.0
 */
function bp_zoom_settings_callback_api_email_field() {
	?>
    <input name="bp-zoom-api-email"
           id="bp-zoom-api-email"
           type="email"
           value="<?php echo esc_html( bp_zoom_api_email() ); ?>"
           placeholder="<?php _e( 'Zoom Account Email', 'buddyboss-pro' ); ?>"
           aria-label="<?php _e( 'Zoom Account Email', 'buddyboss-pro' ); ?>"
    />
    <p class="description"><?php _e( 'Enter an email from your Zoom account to be used as the default host in Gutenberg blocks.', 'buddyboss-pro' ); ?></p>
	<?php
}

/**
 * Get Zoom Account Email
 *
 * @since 1.0.0
 * @param string $default
 *
 * @return mixed|void Zoom Account Email
 */
function bp_zoom_api_email( $default = '' ) {
	return apply_filters( 'bp_zoom_api_email', bp_get_option( 'bp-zoom-api-email', $default ) );
}

/**
 * Callback function for api host in zoom integration.
 *
 * @since 1.0.0
 */
function bp_zoom_settings_callback_api_host_field() {
	?>
    <input name="bp-zoom-api-host"
           id="bp-zoom-api-host"
           type="hidden"
           value="<?php echo esc_html( bp_zoom_api_host() ); ?>"
    />
	<?php
}

/**
 * Integration > Zoom Conference > Enable Groups
 *
 * @since 1.0.0
 */
function bp_zoom_settings_callback_groups_enable_field() {
	?>
    <input name="bp-zoom-enable-groups"
           id="bp-zoom-enable-groups"
           type="checkbox"
           value="1"
		<?php checked( bp_zoom_is_zoom_groups_enabled() ); ?>
    />
    <label for="bp-zoom-enable-groups">
		<?php _e( 'Allow Zoom meetings in social groups', 'buddyboss-pro' ); ?>
    </label>
	<?php
}

/**
 * Checks if zoom is enabled in groups.
 *
 * @since 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is zoom enabled in groups or not
 */
function bp_zoom_is_zoom_groups_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_zoom_is_zoom_groups_enabled', (bool) bp_get_option( 'bp-zoom-enable-groups', $default ) );
}

/**
 * Integration > Zoom Conference > Enable Recordings
 *
 * @since 1.0.0
 */
function bp_zoom_settings_callback_recordings_enable_field() {
	?>
    <input name="bp-zoom-enable-recordings"
           id="bp-zoom-enable-recordings"
           type="checkbox"
           value="1"
		<?php checked( bp_zoom_is_zoom_recordings_enabled() ); ?>
    />
    <label for="bp-zoom-enable-recordings">
		<?php _e( 'Display Zoom recordings for past meetings', 'buddyboss-pro' ); ?>
    </label>
    <br/>
    <input name="bp-zoom-enable-recordings-links"
           id="bp-zoom-enable-recordings-links"
           type="checkbox"
           value="1"
	    <?php echo ! bp_zoom_is_zoom_recordings_enabled() ? 'disabled="disabled"' : ''; ?>
		<?php checked( bp_zoom_is_zoom_recordings_links_enabled() ); ?>
    />
    <label for="bp-zoom-enable-recordings-links">
		<?php _e( "Display buttons to 'Download' recording, and to 'Copy Link' to the recording", 'buddyboss-pro' ); ?>
    </label>
    <script type="application/javascript">
        jQuery(document).ready(function(){
	        jQuery( '#bp-zoom-enable-recordings' ).change(
		        function () {
			        if ( ! this.checked) {
				        jQuery( '#bp-zoom-enable-recordings-links' ).prop( 'disabled', true );
				        jQuery( '#bp-zoom-enable-recordings-links' ).attr( 'checked', false );
			        } else {
				        jQuery( '#bp-zoom-enable-recordings-links' ).prop( 'disabled', false );
			        }
		        }
	        );
        });
    </script>
	<?php
}

/**
 * Checks if zoom recordings are enabled.
 *
 * @since 1.0.0
 *
 * @param integer $default recordings enabled by default
 *
 * @return bool Is zoom recordings enabled or not
 */
function bp_zoom_is_zoom_recordings_enabled( $default = 1 ) {

	/**
	 * Filters zoom recordings enabled settings.
	 *
	 * @param bool $recording_enabled settings if recordings enabled or no.
	 *
	 * @since 1.0.0
	 */
	return (bool) apply_filters( 'bp_zoom_is_zoom_recordings_enabled', (bool) bp_get_option( 'bp-zoom-enable-recordings', $default ) );
}

/**
 * Checks if zoom recordings links are enabled.
 *
 * @since 1.0.2
 *
 * @param integer $default recordings links enabled by default
 *
 * @return bool Is zoom recordings links enabled or not
 */
function bp_zoom_is_zoom_recordings_links_enabled( $default = 1 ) {

	/**
	 * Filters zoom recordings links enabled settings.
	 *
	 * @param bool $recording_enabled settings if recording links enabled or no.
	 *
	 * @since 1.0.2
	 */
	return (bool) apply_filters( 'bp_zoom_is_zoom_recordings_links_enabled', (bool) bp_get_option( 'bp-zoom-enable-recordings-links', $default ) );
}

/**
 * Get Zoom API Host
 *
 * @since 1.0.0
 * @param string $default
 *
 * @return mixed|void Zoom API Host
 */
function bp_zoom_api_host( $default = '' ) {
	return apply_filters( 'bp_zoom_api_host', bp_get_option( 'bp-zoom-api-host', $default ) );
}

/**
 * Get Zoom API Host User
 *
 * @since 1.0.0
 * @param string $default
 *
 * @return mixed|void Zoom API Host User
 */
function bp_zoom_api_host_user( $default = '' ) {
	return apply_filters( 'bp_zoom_api_host_user', json_decode( bp_get_option( 'bp-zoom-api-host-user', $default ) ) );
}

/**
 * Get default group host's display data.
 *
 * @return string
 * @since 1.0.0
 */
function bp_zoom_api_host_show() {
	if ( ! bp_zoom_is_zoom_setup() ) {
		return '';
	}
	$api_host_user = bp_zoom_api_host_user();

	if ( ! empty( $api_host_user ) ) {

		$return = '';
		if ( ! empty( $api_host_user->first_name ) ) {
			$return .= $api_host_user->first_name;
		}
		if ( ! empty( $api_host_user->last_name ) ) {
			$return .= ' ' . $api_host_user->last_name;
		}

		if ( empty( $return ) && ! empty( $api_host_user->email ) ) {
			$return = $api_host_user->email;
		}

		return $return;
	}

	return '';
}

/**
 * Check connection to zoom conference button.
 *
 * @since 1.0.0
 */
function bp_zoom_api_check_connection_button() {
	?>
	<p>
		<a class="button-primary" href="#" id="bp-zoom-check-connection"><?php _e( 'Check Connection', 'buddyboss-pro' ); ?></a>
        <a class="button" href="
		<?php
		echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 88334,
				),
				'admin.php'
			)
		);
		?>
		"><?php _e( 'View Tutorial', 'buddyboss-pro' ); ?></a>
	</p>
	<?php
}

/**
 * Zoom settings tutorial.
 *
 * @since 1.0.0
 */
function bp_zoom_api_zoom_settings_tutorial() {
	?>
    <p>
        <a class="button" href="
		<?php
		echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 88334,
				),
				'admin.php'
			)
		);
		?>
		"><?php _e( 'View Tutorial', 'buddyboss-pro' ); ?></a>
    </p>
	<?php
}

/**
 * Link to Zoom Settings tutorial
 *
 * @since 1.0.0
 */
function bp_zoom_settings_tutorial() {
	?>

    <p>
        <a class="button" href="
		<?php
		echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 88334,
				),
				'admin.php'
			)
		);
		?>
		"><?php _e( 'View Tutorial', 'buddyboss-pro' ); ?></a>
    </p>

	<?php
}

/**
 * Group zoom meeting slug for sub nav items.
 *
 * @since 1.0.0
 * @param $slug
 *
 * @return string slug of nav
 */
function bp_zoom_nouveau_group_secondary_nav_parent_slug( $slug ) {
	if ( ! bp_is_group() ) {
		return $slug;
	}
	return bp_get_current_group_slug() . '_zoom';
}

/**
 * Selected and current class for current nav item in group zoom tabs.
 *
 * @since 1.0.0
 * @param $classes_str
 * @param $classes
 * @param $nav_item
 *
 * @return string classes for the nav items
 */
function bp_zoom_nouveau_group_secondary_nav_selected_classes( $classes_str, $classes, $nav_item ) {
	global $bp_zoom_current_meeting;
	if ( bp_is_current_action( 'zoom' ) ) {
		//$meeting = bp_zoom_get_current_meeting();
		if ( ! empty( $bp_zoom_current_meeting ) ) {
			if ( true === $bp_zoom_current_meeting->is_past ) {
				if ( 'past-meetings' === $nav_item->slug ) {
					$classes = array_merge( $classes, array( 'current', 'selected' ) );
				}
			} else if ( 'meetings' === $nav_item->slug ) {
				$classes = array_merge( $classes, array( 'current', 'selected' ) );
			}
		} else {
			if ( ( empty( bp_action_variable( 0 ) ) || 'meetings' === bp_action_variable( 0 ) ) && 'meetings' === $nav_item->slug ) {
				$classes = array_merge( $classes, array( 'current', 'selected' ) );
			} elseif ( 'create-meeting' === bp_action_variable( 0 ) && 'create-meeting' === $nav_item->slug ) {
				$classes = array_merge( $classes, array( 'current', 'selected' ) );
			} elseif ( 'past-meetings' === bp_action_variable( 0 ) && 'past-meetings' === $nav_item->slug ) {
				$classes = array_merge( $classes, array( 'current', 'selected' ) );
			}
		}

		if ( 'create-meeting' === $nav_item->slug ) {
			$classes = array_merge( $classes, array( 'bp-hide' ) );
		}

		if ( ( ! empty( bp_action_variable( 0 ) ) && 'create-meeting' === bp_action_variable( 0 ) ) && 'meetings' === $nav_item->slug ) {
			$classes = array_merge( $classes, array( 'current', 'selected' ) );
		}

		$classes = array_merge( $classes, array( $nav_item->slug ) );

		return join( ' ', $classes );
	}
	return $classes_str;
}

/**
 * Check if current request is groups zoom or not.
 *
 * @since 1.0.0
 * @return bool $is_zoom return true if group zoom page otherwise false
 */
function bp_zoom_is_groups_zoom() {
	$is_zoom = false;
	if ( bp_is_groups_component() && bp_is_group() && bp_is_current_action( 'zoom' ) ) {
		$is_zoom = true;
	}

	/**
	 * Filters the current group zoom page or not.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $is_zoom Current page is groups zoom page or not.
	 */
	return apply_filters( 'bp_zoom_is_groups_zoom', $is_zoom );
}

/**
 * Get timezones
 *
 * @since 1.0.0
 */
function bp_zoom_get_timezone_options() {
	$zones = array(
		'Pacific/Midway'                 => '(GMT-11:00) Midway Island, Samoa',
		'Pacific/Pago_Pago'              => '(GMT-11:00) Pago Pago',
		'Pacific/Honolulu'               => '(GMT-10:00) Hawaii',
		'America/Anchorage'              => '(GMT-8:00) Alaska',
		'America/Vancouver'              => '(GMT-7:00) Vancouver',
		'America/Los_Angeles'            => '(GMT-7:00) Pacific Time (US and Canada)',
		'America/Tijuana'                => '(GMT-7:00) Tijuana',
		'America/Phoenix'                => '(GMT-7:00) Arizona',
		'America/Edmonton'               => '(GMT-6:00) Edmonton',
		'America/Denver'                 => '(GMT-6:00) Mountain Time (US and Canada)',
		'America/Mazatlan'               => '(GMT-6:00) Mazatlan',
		'America/Regina'                 => '(GMT-6:00) Saskatchewan',
		'America/Guatemala'              => '(GMT-6:00) Guatemala',
		'America/El_Salvador'            => '(GMT-6:00) El Salvador',
		'America/Managua'                => '(GMT-6:00) Managua',
		'America/Costa_Rica'             => '(GMT-6:00) Costa Rica',
		'America/Tegucigalpa'            => '(GMT-6:00) Tegucigalpa',
		'America/Winnipeg'               => '(GMT-5:00) Winnipeg',
		'America/Chicago'                => '(GMT-5:00) Central Time (US and Canada)',
		'America/Mexico_City'            => '(GMT-5:00) Mexico City',
		'America/Panama'                 => '(GMT-5:00) Panama',
		'America/Bogota'                 => '(GMT-5:00) Bogota',
		'America/Lima'                   => '(GMT-5:00) Lima',
		'America/Caracas'                => '(GMT-4:30) Caracas',
		'America/Montreal'               => '(GMT-4:00) Montreal',
		'America/New_York'               => '(GMT-4:00) Eastern Time (US and Canada)',
		'America/Indianapolis'           => '(GMT-4:00) Indiana (East)',
		'America/Puerto_Rico'            => '(GMT-4:00) Puerto Rico',
		'America/Santiago'               => '(GMT-4:00) Santiago',
		'America/Halifax'                => '(GMT-3:00) Halifax',
		'America/Montevideo'             => '(GMT-3:00) Montevideo',
		'America/Araguaina'              => '(GMT-3:00) Brasilia',
		'America/Argentina/Buenos_Aires' => '(GMT-3:00) Buenos Aires, Georgetown',
		'America/Sao_Paulo'              => '(GMT-3:00) Sao Paulo',
		'Canada/Atlantic'                => '(GMT-3:00) Atlantic Time (Canada)',
		'America/St_Johns'               => '(GMT-2:30) Newfoundland and Labrador',
		'America/Godthab'                => '(GMT-2:00) Greenland',
		'Atlantic/Cape_Verde'            => '(GMT-1:00) Cape Verde Islands',
		'Atlantic/Azores'                => '(GMT+0:00) Azores',
		'UTC'                            => '(GMT+0:00) Universal Time UTC',
		'Etc/Greenwich'                  => '(GMT+0:00) Greenwich Mean Time',
		'Atlantic/Reykjavik'             => '(GMT+0:00) Reykjavik',
		'Africa/Nouakchott'              => '(GMT+0:00) Nouakchott',
		'Europe/Dublin'                  => '(GMT+1:00) Dublin',
		'Europe/London'                  => '(GMT+1:00) London',
		'Europe/Lisbon'                  => '(GMT+1:00) Lisbon',
		'Africa/Casablanca'              => '(GMT+1:00) Casablanca',
		'Africa/Bangui'                  => '(GMT+1:00) West Central Africa',
		'Africa/Algiers'                 => '(GMT+1:00) Algiers',
		'Africa/Tunis'                   => '(GMT+1:00) Tunis',
		'Europe/Belgrade'                => '(GMT+2:00) Belgrade, Bratislava, Ljubljana',
		'CET'                            => '(GMT+2:00) Sarajevo, Skopje, Zagreb',
		'Europe/Oslo'                    => '(GMT+2:00) Oslo',
		'Europe/Copenhagen'              => '(GMT+2:00) Copenhagen',
		'Europe/Brussels'                => '(GMT+2:00) Brussels',
		'Europe/Berlin'                  => '(GMT+2:00) Amsterdam, Berlin, Rome, Stockholm, Vienna',
		'Europe/Amsterdam'               => '(GMT+2:00) Amsterdam',
		'Europe/Rome'                    => '(GMT+2:00) Rome',
		'Europe/Stockholm'               => '(GMT+2:00) Stockholm',
		'Europe/Vienna'                  => '(GMT+2:00) Vienna',
		'Europe/Luxembourg'              => '(GMT+2:00) Luxembourg',
		'Europe/Paris'                   => '(GMT+2:00) Paris',
		'Europe/Zurich'                  => '(GMT+2:00) Zurich',
		'Europe/Madrid'                  => '(GMT+2:00) Madrid',
		'Africa/Harare'                  => '(GMT+2:00) Harare, Pretoria',
		'Europe/Warsaw'                  => '(GMT+2:00) Warsaw',
		'Europe/Prague'                  => '(GMT+2:00) Prague Bratislava',
		'Europe/Budapest'                => '(GMT+2:00) Budapest',
		'Africa/Tripoli'                 => '(GMT+2:00) Tripoli',
		'Africa/Cairo'                   => '(GMT+2:00) Cairo',
		'Africa/Johannesburg'            => '(GMT+2:00) Johannesburg',
		'Europe/Helsinki'                => '(GMT+3:00) Helsinki',
		'Africa/Nairobi'                 => '(GMT+3:00) Nairobi',
		'Europe/Sofia'                   => '(GMT+3:00) Sofia',
		'Europe/Istanbul'                => '(GMT+3:00) Istanbul',
		'Europe/Athens'                  => '(GMT+3:00) Athens',
		'Europe/Bucharest'               => '(GMT+3:00) Bucharest',
		'Asia/Nicosia'                   => '(GMT+3:00) Nicosia',
		'Asia/Beirut'                    => '(GMT+3:00) Beirut',
		'Asia/Damascus'                  => '(GMT+3:00) Damascus',
		'Asia/Jerusalem'                 => '(GMT+3:00) Jerusalem',
		'Asia/Amman'                     => '(GMT+3:00) Amman',
		'Europe/Moscow'                  => '(GMT+3:00) Moscow',
		'Asia/Baghdad'                   => '(GMT+3:00) Baghdad',
		'Asia/Kuwait'                    => '(GMT+3:00) Kuwait',
		'Asia/Riyadh'                    => '(GMT+3:00) Riyadh',
		'Asia/Bahrain'                   => '(GMT+3:00) Bahrain',
		'Asia/Qatar'                     => '(GMT+3:00) Qatar',
		'Asia/Aden'                      => '(GMT+3:00) Aden',
		'Africa/Khartoum'                => '(GMT+3:00) Khartoum',
		'Africa/Djibouti'                => '(GMT+3:00) Djibouti',
		'Africa/Mogadishu'               => '(GMT+3:00) Mogadishu',
		'Europe/Kiev'                    => '(GMT+3:00) Kiev',
		'Asia/Dubai'                     => '(GMT+4:00) Dubai',
		'Asia/Muscat'                    => '(GMT+4:00) Muscat',
		'Asia/Tehran'                    => '(GMT+4:30) Tehran',
		'Asia/Kabul'                     => '(GMT+4:30) Kabul',
		'Asia/Baku'                      => '(GMT+5:00) Baku, Tbilisi, Yerevan',
		'Asia/Yekaterinburg'             => '(GMT+5:00) Yekaterinburg',
		'Asia/Tashkent'                  => '(GMT+5:00) Islamabad, Karachi, Tashkent',
		'Asia/Kolkata'                   => '(GMT+5:30) Mumbai, Kolkata, New Delhi',
		'Asia/Kathmandu'                 => '(GMT+5:45) Kathmandu',
		'Asia/Novosibirsk'               => '(GMT+6:00) Novosibirsk',
		'Asia/Almaty'                    => '(GMT+6:00) Almaty',
		'Asia/Dacca'                     => '(GMT+6:00) Dacca',
		'Asia/Dhaka'                     => '(GMT+6:00) Astana, Dhaka',
		'Asia/Krasnoyarsk'               => '(GMT+7:00) Krasnoyarsk',
		'Asia/Bangkok'                   => '(GMT+7:00) Bangkok',
		'Asia/Saigon'                    => '(GMT+7:00) Vietnam',
		'Asia/Jakarta'                   => '(GMT+7:00) Jakarta',
		'Asia/Irkutsk'                   => '(GMT+8:00) Irkutsk, Ulaanbaatar',
		'Asia/Shanghai'                  => '(GMT+8:00) Beijing, Shanghai',
		'Asia/Hong_Kong'                 => '(GMT+8:00) Hong Kong',
		'Asia/Taipei'                    => '(GMT+8:00) Taipei',
		'Asia/Kuala_Lumpur'              => '(GMT+8:00) Kuala Lumpur',
		'Asia/Singapore'                 => '(GMT+8:00) Singapore',
		'Australia/Perth'                => '(GMT+8:00) Perth',
		'Asia/Yakutsk'                   => '(GMT+9:00) Yakutsk',
		'Asia/Seoul'                     => '(GMT+9:00) Seoul',
		'Asia/Tokyo'                     => '(GMT+9:00) Osaka, Sapporo, Tokyo',
		'Australia/Darwin'               => '(GMT+9:30) Darwin',
		'Australia/Adelaide'             => '(GMT+9:30) Adelaide',
		'Asia/Vladivostok'               => '(GMT+10:00) Vladivostok',
		'Pacific/Port_Moresby'           => '(GMT+10:00) Guam, Port Moresby',
		'Australia/Brisbane'             => '(GMT+10:00) Brisbane',
		'Australia/Sydney'               => '(GMT+10:00) Canberra, Melbourne, Sydney',
		'Australia/Hobart'               => '(GMT+10:00) Hobart',
		'Asia/Magadan'                   => '(GMT+10:00) Magadan',
		'SST'                            => '(GMT+11:00) Solomon Islands',
		'Pacific/Noumea'                 => '(GMT+11:00) New Caledonia',
		'Asia/Kamchatka'                 => '(GMT+12:00) Kamchatka',
		'Pacific/Fiji'                   => '(GMT+12:00) Fiji Islands, Marshall Islands',
		'Pacific/Auckland'               => '(GMT+12:00) Auckland, Wellington',
	);

	return apply_filters( 'bp_zoom_get_timezone_options', $zones );
}

/**
 * Get timezone label.
 *
 * @param string $timezone
 *
 * @since 1.0.0
 * @return string Timezone.
 */
function bp_zoom_get_timezone_label( $timezone = '' ) {
	$timezones          = bp_zoom_get_timezone_options();
	$selected_time_zone = $timezone;
	if ( empty( $timezone ) ) {
		$wp_timezone_str    = get_option( 'timezone_string' );
		if ( empty( $wp_timezone_str ) ) {
			$wp_timezone_str_offset = get_option( 'gmt_offset' );
		} else {
			$time                   = new DateTime( 'now', new DateTimeZone( $wp_timezone_str ) );
			$wp_timezone_str_offset = $time->getOffset() / 60 / 60;
		}

		if ( ! empty( $timezones ) ) {
			foreach ( $timezones as $key => $time_zone ) {
				if ( $key === $wp_timezone_str ) {
					$selected_time_zone = $key;
					break;
				}

				$date            = new DateTime( 'now', new DateTimeZone( $key ) );
				$offset_in_hours = $date->getOffset() / 60 / 60;

				if ( (float) $wp_timezone_str_offset === (float) $offset_in_hours ) {
					$selected_time_zone = $key;
				}
			}
		}
	}

	if ( empty( $selected_time_zone ) ) {
	    return '';
    }

	$timezone_label = substr( $timezones[ $selected_time_zone ], strpos( $timezones[ $selected_time_zone ], ' ' ), strlen( $timezones[ $selected_time_zone ] ) );
	return ltrim( $timezone_label );
}

function bp_zoom_nouveau_feedback_messages( $messages ) {
	$messages['meetings-loop-none'] = array(
		'type'    => 'info',
		'message' => __( 'Sorry, no meetings were found.', 'buddyboss-pro' ),
	);
	return $messages;
}
add_filter( 'bp_nouveau_feedback_messages', 'bp_zoom_nouveau_feedback_messages' );

/**
 * Get if group has zoom enabled or not.
 *
 * @since 1.0.0
 * @param int $group_id group ID.
 *
 * @return bool True if all details required are not empty otherwise false.
 */
function bp_zoom_group_is_zoom_enabled( $group_id ) {
	if ( ! bp_is_active( 'groups' ) ) {
		return false;
	}
	return groups_get_groupmeta( $group_id, 'bp-group-zoom', true );
}

/**
 * Check group zoom is setup or not.
 *
 * @since 1.0.0
 * @param $group_id
 *
 * @return bool Returns true if zoom is setup.
 */
function bp_zoom_is_group_setup( $group_id ) {
	if ( ! bp_is_active( 'groups' ) ) {
		return false;
	}

	$group_zoom = groups_get_groupmeta( $group_id, 'bp-group-zoom', true );
	$api_key    = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-key', true );
	$api_secret = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-secret', true );
	$api_email  = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-email', true );
	$api_host   = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-host', true );

	if ( ! $group_zoom || empty( $api_key ) || empty( $api_secret ) || empty( $api_email ) || empty( $api_host ) ) {
		return false;
	}

	return true;
}

/**
 * Get default group host's display data.
 *
 * @since 1.0.0
 * @return string
 */
function bp_zoom_groups_api_host_show( $group_id ) {
    if ( empty( $group_id ) || ! bp_zoom_is_group_setup( $group_id ) ) {
        return '';
    }
	$api_host_user   = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-host-user', true );

	if ( ! empty( $api_host_user ) ) {
		$api_host_user = json_decode( $api_host_user );

		$return = '';
		if ( ! empty( $api_host_user->first_name ) ) {
			$return .= $api_host_user->first_name;
        }
		if ( ! empty( $api_host_user->last_name ) ) {
			$return .= ' ' . $api_host_user->last_name;
		}

		if ( empty( $return ) && ! empty( $return->email ) ) {
		    $return = $return->email;
        }
		return $return;
	}
	return '';
}

/**
 * Output the 'checked' value, if needed, for a given status on the group admin screen
 *
 * @since 1.0.0
 *
 * @param string      $setting The setting you want to check against ('members',
 *                             'mods', or 'admins').
 * @param object|bool $group   Optional. Group object. Default: current group in loop.
 */
function bp_zoom_group_show_manager_setting( $setting, $group = false ) {
	$group_id = isset( $group->id ) ? $group->id : false;

	$status = bp_zoom_group_get_manager( $group_id );

	if ( $setting === $status ) {
		echo ' checked="checked"';
	}
}

/**
 * Get the zoom manager of a group.
 *
 * This function can be used either in or out of the loop.
 *
 * @since 1.0.0
 *
 * @param int|bool $group_id Optional. The ID of the group whose status you want to
 *                           check. Default: the displayed group, or the current group
 *                           in the loop.
 * @return bool|string Returns false when no group can be found. Otherwise
 *                     returns the group zoom manager, from among 'members',
 *                     'mods', and 'admins'.
 */
function bp_zoom_group_get_manager( $group_id = false ) {
	global $groups_template;

	if ( ! $group_id ) {
		$bp = buddypress();

		if ( isset( $bp->groups->current_group->id ) ) {
			// Default to the current group first.
			$group_id = $bp->groups->current_group->id;
		} elseif ( isset( $groups_template->group->id ) ) {
			// Then see if we're in the loop.
			$group_id = $groups_template->group->id;
		} else {
			return false;
		}
	}

	$manager = groups_get_groupmeta( $group_id, 'bp-group-zoom-manager', true );

	// Backward compatibility. When '$manager' is not set, fall back to a default value.
	if ( ! $manager ) {
		$manager = apply_filters( 'bp_zoom_group_manager_fallback', 'admins' );
	}

	/**
	 * Filters the album status of a group.
	 *
	 * @since 1.0.0
	 *
	 * @param string $manager Membership level needed to manage albums.
	 * @param int    $group_id      ID of the group whose manager is being checked.
	 */
	return apply_filters( 'bp_zoom_group_get_manager', $manager, $group_id );
}

/**
 * Check whether a user is allowed to manage zoom meetings in a given group.
 *
 * @since 1.0.0
 *
 * @param int $user_id ID of the user.
 * @param int $group_id ID of the group.
 * @return bool true if the user is allowed, otherwise false.
 */
function bp_zoom_groups_can_user_manage_zoom( $user_id, $group_id ) {
	$is_allowed = false;

	if ( ! is_user_logged_in() ) {
		return false;
	}

	// Site admins always have access.
	if ( bp_current_user_can( 'bp_moderate' ) ) {
		return true;
	}

	if ( ! groups_is_user_member( $user_id, $group_id ) ) {
		return false;
	}

	$manager  = bp_zoom_group_get_manager( $group_id );
	$is_admin = groups_is_user_admin( $user_id, $group_id );
	$is_mod   = groups_is_user_mod( $user_id, $group_id );

	if ( 'members' === $manager ) {
		$is_allowed = true;
	} elseif ( 'mods' === $manager && ( $is_mod || $is_admin ) ) {
		$is_allowed = true;
	} elseif ( 'admins' === $manager && $is_admin ) {
		$is_allowed = true;
	}

	return apply_filters( 'bp_zoom_groups_can_user_manage_zoom', $is_allowed );
}

/**
 * Check whether a user is allowed to manage zoom meetings in a given group.
 *
 * @since 1.0.0
 *
 * @param int $meeting_id ID of the Meeting.
 * @return bool true if the user is allowed, otherwise false.
 */
function bp_zoom_groups_can_user_manage_meeting( $meeting_id ) {
	if ( ! is_user_logged_in() || empty( $meeting_id ) ) {
		return false;
	}

	$meeting = new BP_Zoom_Meeting( $meeting_id );

	if ( empty( $meeting->id ) ) {
		return false;
	}

	// Site admins always have access.
	if ( bp_current_user_can( 'bp_moderate' ) ) {
		return true;
	}

	$group_id = bp_get_current_group_id();
	$user_id  = bp_loggedin_user_id();

	if ( ! groups_is_user_member( $user_id, $group_id ) ) {
		return false;
	}

	$manager  = bp_zoom_group_get_manager( $group_id );
	$is_admin = groups_is_user_admin( $user_id, $group_id );
	$is_mod   = groups_is_user_mod( $user_id, $group_id );

	if ( 'mods' === $manager && ( $is_mod || $is_admin ) ) {
		return true;
	} elseif ( 'admins' === $manager && $is_admin ) {
		return true;
	}

	if ( $user_id !== $meeting->user_id ) {
		return false;
	}

	return true;
}

/**
 * Check if single meeting page
 *
 * @since 1.0.0
 * @return bool true if single meeting page otherwise false.
 */
function bp_zoom_is_single_meeting() {
	return bp_zoom_is_groups_zoom() && is_numeric( bp_action_variable( 1 ) );
}

/**
 * Check if current request is create meeting.
 *
 * @since 1.0.0
 */
function bp_zoom_is_create_meeting() {
	if ( bp_zoom_is_groups_zoom() && 'create-meeting' === bp_action_variable( 0 ) ) {
		return true;
	}
	return false;
}

/**
 * Check if current request is create meeting.
 *
 * @since 1.0.0
 */
function bp_zoom_is_edit_meeting() {
	if ( bp_zoom_is_groups_zoom() && 'meetings' === bp_action_variable( 0 ) && 'edit' === bp_action_variable( 1 ) ) {
		return true;
	}
	return false;
}

function bp_zoom_get_edit_meeting_id() {
	if ( bp_zoom_is_edit_meeting() ) {
		return (int) bp_action_variable( 2 );
	}
	return false;
}

/**
 * Get edit meeting.
 *
 * @since 1.0.0
 * @return object|bool object of the meeting or false if not found.
 */
function bp_zoom_get_edit_meeting() {
	$meeting_id = bp_zoom_get_edit_meeting_id();
	if ( $meeting_id ) {
		$meeting = new BP_Zoom_Meeting( $meeting_id );

		if ( ! empty( $meeting->id ) ) {
			return $meeting;
		}
	}
	return false;
}

/**
 * Get single meeting.
 *
 * @since 1.0.0
 * @return object|bool object of the meeting or false if not found.
 */
function bp_zoom_get_current_meeting() {
	global $bp_zoom_current_meeting;
	if ( bp_zoom_is_single_meeting() && empty( $bp_zoom_current_meeting ) ) {
		$meeting_id = (int) bp_action_variable( 1 );
		$meeting    = new BP_Zoom_Meeting( $meeting_id );

		if ( ! empty( $meeting->id ) ) {
			$bp_zoom_current_meeting = $meeting;
			return $bp_zoom_current_meeting;
		}
	}

	return $bp_zoom_current_meeting;
}

/**
 * Get single meeting id.
 *
 * @since 1.0.0
 * @return int|bool ID of the meeting or false if not found.
 */
function bp_zoom_get_current_meeting_id() {
	if ( bp_zoom_is_single_meeting() ) {
		return (int) bp_action_variable( 1 );
	}
	return false;
}

/**
 * Check if current user has permission to start meeting.
 *
 * @since 1.0.0
 * @param $meeting_id
 *
 * @return bool true if user has permission otherwise false.
 */
function bp_zoom_can_current_user_start_meeting( $meeting_id ) {
	// check is user loggedin.
	if ( ! is_user_logged_in() ) {
		return false;
	}

	// get meeting exists.
	$meeting = new BP_Zoom_Meeting( $meeting_id );

	// check meeting exists.
	if ( empty( $meeting->id ) || empty( $meeting->group_id ) ) {
		return false;
	}

	$current_userdata = get_userdata( get_current_user_id() );

	if ( ! empty( $current_userdata ) ) {
		$userinfo  = groups_get_groupmeta( $meeting->group_id, 'bp-group-zoom-api-host-user', true );

		if ( ! empty( $userinfo ) ) {
		    $userinfo = json_decode( $userinfo );
			if ( $current_userdata->user_email === $userinfo->email ) {
				return true;
			}
			// check meeting alt user ids have current user's id or not.
			if ( in_array( $current_userdata->user_email, explode( ',', $meeting->alternative_host_ids ), true ) ) {
				return true;
			}
		}
	}

	// return false atleast.
	return false;
}

/**
 * Returns the current group meeting tab slug.
 *
 * @since 1.0.0
 *
 * @return bool|string $tab The current meeting tab's slug, false otherwise.
 */
function bp_zoom_group_current_meeting_tab() {
	$tab = false;
	if ( bp_is_groups_component() && bp_is_current_action( 'zoom' ) ) {
		if ( false !== bp_action_variable( 0 ) ) {
			$tab = bp_action_variable( 0 );
		} else {
		    $tab = 'zoom';
        }
	}

	/**
	 * Filters the current group meeting tab slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tab Current group meeting tab slug.
	 */
	return apply_filters( 'bp_zoom_get_group_current_meeting_tab', $tab );
}

/**
 * Delete activities when meeting deleted.
 *
 * @since 1.0.0
 * @param $meetings
 */
function bp_zoom_meeting_delete_meeting_activity( $meetings ) {
    if ( ! empty( $meetings ) && bp_is_active( 'activity' ) ) {
	    // Pluck the activity IDs out of the $meetings array.
	    $activity_ids = wp_parse_id_list( wp_list_pluck( $meetings, 'activity_id' ) );
        foreach ( $activity_ids as $activity_id ) {
	        bp_activity_delete( array( 'id' => $activity_id ) );
        }
    }
}
add_action( 'bp_zoom_meeting_after_delete', 'bp_zoom_meeting_delete_meeting_activity' );

/**
 * Get the recurrence label for a meeting
 *
 * @param int $meeting_id Meeting ID in the site.
 * @param object|bool $meeting_details Meeting object from zoom.
 *
 * @since 1.0.4
 * @return bool|string|void Recurrence label.
 * @throws Exception
 */
function bp_zoom_get_recurrence_label( $meeting_id, $meeting_details = false ) {
	if ( ! empty( $meeting_id ) && empty( $meeting_details ) ) {

	    $meeting = new BP_Zoom_Meeting( $meeting_id );
	    if ( 'meeting_occurrence' === $meeting->zoom_type ) {
		    $parent_meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting->parent );
		    if ( ! empty( $parent_meeting ) ) {
			    $meeting_id = $parent_meeting->id;
		    }
        }

		$meeting_details = json_decode( json_encode( bp_get_zoom_meeting_zoom_details( $meeting_id ) ) );
	}

	if ( empty( $meeting_id ) && empty( $meeting_details ) ) {
		return false;
	}

	$recurrence  = array();
	$occurrences = array();
	if ( ! empty( $meeting_details ) ) {
		if ( ! empty( $meeting_details->recurrence ) ) {
			$recurrence = $meeting_details->recurrence;
		}

		if ( ! empty( $meeting_details->occurrences ) ) {
			$occurrences = $meeting_details->occurrences;
		}
	}

	if ( empty( $recurrence ) || empty( $occurrences ) ) {
		return false;
	}

	foreach ( $occurrences as $occurrence_key => $occurrence ) {
	    if ( 'deleted' === $occurrence->status ) {
	        unset( $occurrences[$occurrence_key] );
        }
	}

	$meeting_date = false;
	$current_occurrence_offset = 0;
	foreach ( $occurrences as $occurrence_key => $occurrence ) {
		if ( wp_date( 'U', strtotime( 'now' ) ) < strtotime( $occurrence->start_time ) ) {
			$meeting_date = $occurrence->start_time;
			break;
		}
		$current_occurrence_offset++;
	}

	if ( empty( $meeting_date ) ) {
	    return;
    }

	$future_occurrences   = array_slice( $occurrences, $current_occurrence_offset, count( $occurrences ) );
	$no_of_occurrences    = count( $future_occurrences );
	$last_occurrence_date = end( $occurrences )->start_time;

	$return = '';
	switch ( $recurrence->type ) {
		case 1 :
			$return = __( 'Every', 'buddyboss-pro' );

			if ( 1 < $recurrence->repeat_interval ) {
				$return .= ' ' . $recurrence->repeat_interval;
				$return .= ' ' . __( 'days', 'buddyboss-pro' );
			} else {
				$return .= ' ' . __( 'day', 'buddyboss-pro' );
			}


			if ( ! empty( $recurrence->end_date_time ) ) {
				$return .= ' ' . __( 'until', 'buddyboss-pro' ) . ' ';
				$return .= wp_date( bp_core_date_format(), strtotime( $last_occurrence_date ) );
            }


			$return .= ', ' . sprintf( '%d %s', $no_of_occurrences, _n( 'occurrence', 'occurrences', $no_of_occurrences, 'buddyboss-pro' ) );
			break;
		case 2 :
			$return .= __( 'Every', 'buddyboss-pro' );

			if ( 1 < $recurrence->repeat_interval ) {
				$return .= ' ' . $recurrence->repeat_interval;
				$return .= ' ' . __( 'weeks on', 'buddyboss-pro' );
			} else {
				$return .= ' ' . __( 'week on', 'buddyboss-pro' );
			}

			if ( ! empty( $recurrence->weekly_days ) ) {
				$weekly_days = explode( ',', $recurrence->weekly_days );

				if ( in_array( 1, $weekly_days ) ) {
					$return .= __( ' Sun', 'buddyboss-pro' );
				}
				if ( in_array( 2, $weekly_days ) ) {
					$return .= __( ' Mon', 'buddyboss-pro' );
				}
				if ( in_array( 3, $weekly_days ) ) {
					$return .= __( ' Tue', 'buddyboss-pro' );
				}
				if ( in_array( 4, $weekly_days ) ) {
					$return .= __( ' Wed', 'buddyboss-pro' );
				}
				if ( in_array( 5, $weekly_days ) ) {
					$return .= __( ' Thu', 'buddyboss-pro' );
				}
				if ( in_array( 6, $weekly_days ) ) {
					$return .= __( ' Fri', 'buddyboss-pro' );
				}
				if ( in_array( 7, $weekly_days ) ) {
					$return .= __( ' Sat', 'buddyboss-pro' );
				}
			}


			if ( ! empty( $recurrence->end_date_time ) ) {
				$return .= ' ' . __( 'until', 'buddyboss-pro' ) . ' ';
				$return .= wp_date( bp_core_date_format(), strtotime( $last_occurrence_date ) );
            }

			$return .= ', ' . sprintf( '%d %s', $no_of_occurrences, _n( 'occurrence', 'occurrences', $no_of_occurrences, 'buddyboss-pro' ) );
			break;
		case 3 :
			$return .= __( 'Every', 'buddyboss-pro' );

			if ( 1 < $recurrence->repeat_interval ) {
				$return .= ' ' . $recurrence->repeat_interval;
				$return .= ' ' . __( 'months on the', 'buddyboss-pro' );
			} else {
				$return .= ' ' . __( 'month on the', 'buddyboss-pro' );
			}

			if ( ! empty( $recurrence->monthly_day ) ) {
				$return .= ' ' . $recurrence->monthly_day . ' ' . __( 'of the month', 'buddyboss-pro' );
			}

			if ( ! empty( $recurrence->monthly_week ) ) {
				$return .= ' ';
				if ( 1 === $recurrence->monthly_week ) {
					$return .= __( 'First', 'buddyboss-pro' );
				} else if ( 2 === $recurrence->monthly_week ) {
					$return .= __( 'Second', 'buddyboss-pro' );
				} else if ( 3 === $recurrence->monthly_week ) {
					$return .= __( 'Third', 'buddyboss-pro' );
				} else if ( 4 === $recurrence->monthly_week ) {
					$return .= __( 'Fourth', 'buddyboss-pro' );
				} else if ( - 1 === $recurrence->monthly_week ) {
					$return .= __( 'Last', 'buddyboss-pro' );
				}
			}

			if ( ! empty( $recurrence->monthly_week_day ) ) {
				$return .= ' ';
				if ( 1 === $recurrence->monthly_week_day ) {
					$return .= __( 'Sun', 'buddyboss-pro' );
				}
				if ( 2 === $recurrence->monthly_week_day ) {
					$return .= __( 'Mon', 'buddyboss-pro' );
				}
				if ( 3 === $recurrence->monthly_week_day ) {
					$return .= __( 'Tue', 'buddyboss-pro' );
				}
				if ( 4 === $recurrence->monthly_week_day ) {
					$return .= __( 'Wed', 'buddyboss-pro' );
				}
				if ( 5 === $recurrence->monthly_week_day ) {
					$return .= __( 'Thu', 'buddyboss-pro' );
				}
				if ( 6 === $recurrence->monthly_week_day ) {
					$return .= __( 'Fri', 'buddyboss-pro' );
				}
				if ( 7 === $recurrence->monthly_week_day ) {
					$return .= __( 'Sat', 'buddyboss-pro' );
				}
			}


			if ( ! empty( $recurrence->end_date_time ) ) {
				$return .= ' ' . __( 'until', 'buddyboss-pro' ) . ' ';
				$return .= wp_date( bp_core_date_format(), strtotime( $last_occurrence_date ) );
            }


			$return .= ', ' . sprintf( '%d %s', $no_of_occurrences, _n( 'occurrence', 'occurrences', $no_of_occurrences, 'buddyboss-pro' ) );
			break;
		default:
			break;
	}

	return apply_filters( 'bp_zoom_get_recurrence_label', $return, $meeting_id, $meeting_details );
}

/**
 * Add zoom 30 mins schedule to cron schedules.
 *
 * @param $schedules
 *
 * @return mixed
 * @since 1.0.4
 */
function bp_zoom_meeting_cron_schedules( $schedules ) {
	if ( ! isset( $schedules['bp_zoom_30min'] ) ) {
		$schedules['bp_zoom_30min'] = array(
			'interval' => 30 * MINUTE_IN_SECONDS,
			'display'  => __( 'Once in 30 minutes', 'buddyboss-pro' )
		);
	}

	return $schedules;
}

add_filter( 'cron_schedules', 'bp_zoom_meeting_cron_schedules' );

/**
 * Schedule cron for the meeting to check recordings.
 *
 * @since 1.0.4
 */
function bp_zoom_meeting_schedule_cron() {
	if ( ! wp_next_scheduled( 'bp_zoom_meeting_update_occurrence_activity_hook' ) ) {
		wp_schedule_event( time(), 'bp_zoom_30min', 'bp_zoom_meeting_update_occurrence_activity_hook' );
	}
}

add_action( 'bp_init', 'bp_zoom_meeting_schedule_cron' );

/**
 * Check zoom meeting recurring.
 *
 * @since 1.0.4
 */
function bp_zoom_meeting_update_occurrence_activity() {
	$from      = wp_date( 'Y-m-d H:i:s', strtotime( '+1 hour' ), new DateTimeZone( 'UTC' ) );
	$from_unix = wp_date( 'U', strtotime( '+1 hour' ), new DateTimeZone( 'UTC' ) );
	if ( bp_has_zoom_meetings(
		array(
			'per_page'  => 999999,
			'from'      => $from,
			'since'     => false,
			'zoom_type' => 'meeting_occurrence'
		)
	) ) {
		while ( bp_zoom_meeting() ) {
			bp_the_zoom_meeting();

			if ( ! empty( bp_get_zoom_meeting_activity_id() ) ) {
			    continue;
            }

			$meeting_date_unix = wp_date( 'U', strtotime( bp_get_zoom_meeting_start_date_utc() ) );
			if ( $from_unix >= $meeting_date_unix ) {
				$group = groups_get_group( bp_get_zoom_meeting_group_id() );

				if ( ! empty( $group->id ) ) {
					$action = sprintf( __( '%1$s scheduled a Zoom meeting in the group %2$s', 'buddyboss-pro' ), bp_core_get_userlink( bp_get_zoom_meeting_user_id() ), '<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( $group->name ) . '</a>' );

					$activity_id = groups_record_activity(
						array(
							'user_id'           => bp_get_zoom_meeting_user_id(),
							'action'            => $action,
							'content'           => '',
							'type'              => 'zoom_meeting_create',
							'item_id'           => bp_get_zoom_meeting_group_id(),
							'secondary_item_id' => bp_get_zoom_meeting_id(),
						)
					);

					if ( $activity_id ) {

						$meeting = new BP_Zoom_Meeting( bp_get_zoom_meeting_id() );
						// save activity id in meeting
						$meeting->activity_id = $activity_id;
						$meeting->save();

						// update activity meta
						bp_activity_update_meta( $activity_id, 'bp_meeting_id', $meeting->id );

						groups_update_groupmeta( $meeting->group_id, 'last_activity', bp_core_current_time() );
					}
				}
			}
		}
	}
}

add_action( 'bp_zoom_meeting_update_occurrence_activity_hook', 'bp_zoom_meeting_update_occurrence_activity' );

/**
 * Get converted date time.
 *
 * @param      $date_time
 * @param      $timezone
 * @param bool $is_utc_date
 *
 * @since 1.0.4
 * @return string
 * @throws Exception
 */
function bp_zoom_meeting_convert_date_time( $date_time, $timezone, $is_utc_date = false ) {
    if ( 'Asia/Calcutta' === $timezone ) {
        $timezone = 'Asia/Kolkata';
    }
    if ( $is_utc_date ) {
	    $date_time = new DateTime( $date_time, new DateTimeZone( 'UTC' ) );
    } else {
	    $date_time = new DateTime( $date_time );
    }

	$date_time->setTimezone( new DateTimeZone( $timezone ) );

    return $date_time->format( 'Y-m-d\TH:i:s' );
}
