<?php
/**
 * BuddyBoss - Meeting Block Front end
 *
 * @since 1.0.0
 */
?>
<?php
global $bp_zoom_meeting_block;

if ( empty( $bp_zoom_meeting_block ) ) {
	return;
}

$meeting_id             = ! empty( $bp_zoom_meeting_block->id ) ? $bp_zoom_meeting_block->id : '';
$topic                  = ! empty( $bp_zoom_meeting_block->topic ) ? $bp_zoom_meeting_block->topic : '';
$agenda                 = ! empty( $bp_zoom_meeting_block->agenda ) ? $bp_zoom_meeting_block->agenda : '';
$duration               = ! empty( $bp_zoom_meeting_block->duration ) ? $bp_zoom_meeting_block->duration : '0';
$host_id                = ! empty( $bp_zoom_meeting_block->host_id ) ? $bp_zoom_meeting_block->host_id : '';
$alt_hosts              = ! empty( $bp_zoom_meeting_block->settings->alternative_hosts ) ? $bp_zoom_meeting_block->settings->alternative_hosts : '';
$password               = ! empty( $bp_zoom_meeting_block->password ) ? $bp_zoom_meeting_block->password : '';
$start_time             = ! empty( $bp_zoom_meeting_block->start_time ) ? $bp_zoom_meeting_block->start_time : 'now';
$timezone               = ! empty( $bp_zoom_meeting_block->timezone ) ? $bp_zoom_meeting_block->timezone : 'UTC';
$start_url              = ! empty( $bp_zoom_meeting_block->start_url ) ? $bp_zoom_meeting_block->start_url : '';
$join_url               = ! empty( $bp_zoom_meeting_block->join_url ) ? $bp_zoom_meeting_block->join_url : '';
$registration_url       = ! empty( $bp_zoom_meeting_block->registration_url ) ? $bp_zoom_meeting_block->registration_url : '';
$host_video             = ! empty( $bp_zoom_meeting_block->settings->host_video ) ? $bp_zoom_meeting_block->settings->host_video : false;
$participants_video     = ! empty( $bp_zoom_meeting_block->settings->participant_video ) ? $bp_zoom_meeting_block->settings->participant_video : false;
$join_before_host       = ! empty( $bp_zoom_meeting_block->settings->join_before_host ) ? $bp_zoom_meeting_block->settings->join_before_host : false;
$mute_participants      = ! empty( $bp_zoom_meeting_block->settings->mute_upon_entry ) ? $bp_zoom_meeting_block->settings->mute_upon_entry : false;
$waiting_room           = ! empty( $bp_zoom_meeting_block->settings->waiting_room ) ? $bp_zoom_meeting_block->settings->waiting_room : false;
$meeting_authentication = ! empty( $bp_zoom_meeting_block->settings->meeting_authentication ) ? $bp_zoom_meeting_block->settings->meeting_authentication : false;
$auto_recording         = ! empty( $bp_zoom_meeting_block->settings->auto_recording ) ? $bp_zoom_meeting_block->settings->auto_recording : 'none';
$can_start_meeting      = false;
$occurrences            = ! empty( $bp_zoom_meeting_block->occurrences ) ? $bp_zoom_meeting_block->occurrences : array();
$recurring              = isset( $bp_zoom_meeting_block->type ) && 8 === $bp_zoom_meeting_block->type;
$recurrence             = ! empty( $bp_zoom_meeting_block->recurrence ) ? $bp_zoom_meeting_block->recurrence : false;
$status                 = ! empty( $bp_zoom_meeting_block->status ) ? $bp_zoom_meeting_block->status : '';

if ( is_user_logged_in() ) {
	$current_userdata = get_userdata( get_current_user_id() );

	if ( ! empty( $current_userdata ) ) {
		$api_email = bp_zoom_api_email();

		if ( $api_email === $current_userdata->user_email ) {
			$can_start_meeting = true;
		} else if ( in_array( $current_userdata->user_email, explode( ',', $alt_hosts ), true ) ) {
			$can_start_meeting = true;
		} else {
			$userinfo = get_transient( 'bp_zoom_user_info_' . $host_id );

			if ( empty( $userinfo ) ) {
				$userinfo = bp_zoom_conference()->get_user_info( $host_id );
				if ( 200 === $userinfo['code'] && ! empty( $userinfo['response'] ) ) {
					set_transient( 'bp_zoom_user_info_' . $host_id, json_encode( $userinfo['response'] ), HOUR_IN_SECONDS );
					$userinfo = $userinfo['response'];
				}
			} else {
				$userinfo = json_decode( $userinfo );
			}

			if ( ! empty( $userinfo ) && $current_userdata->user_email === $userinfo->email ) {
				$can_start_meeting = true;
			}
		}
	}
}

$meeting_date_raw   = false;
$meeting_is_started = false;
$current_meeting    = false;

if ( $recurring && ! empty( $occurrences ) ) {
	foreach ( $occurrences as $occurrence_key => $occurrence ) {
		if ( 'deleted' === $occurrence->status ) {
			continue;
		}

		$occurrence_date_obj = new DateTime( $occurrence->start_time );
		$occurrence_date_obj->modify( '+' . $occurrence->duration . ' minutes' );
		$occurrence_date_obj->setTimezone( wp_timezone() );
		$occurrence_date_unix = $occurrence_date_obj->format( 'U' );

		if ( wp_date( 'U' ) < $occurrence_date_unix ) {
			$start_time = $occurrence->start_time;
			$duration   = $occurrence->duration;
			break;
		}
	}
}

$occurrence_date      = new DateTime( $start_time );
$occurrence_date_unix = $occurrence_date->format( 'U' );
$meeting_is_started   = ( $occurrence_date_unix > wp_date( 'U', strtotime( '+10 minutes' ) ) ) ? false : true;

$meeting_date = wp_date( bp_core_date_format( true, true, __( ' \a\t ', 'buddyboss-pro' ) ), strtotime( $start_time ) );

$current_date     = wp_date( 'U' );
$meeting_date_obj = new DateTime( $start_time );
$meeting_date_obj->setTimezone( wp_timezone() );
$meeting_date_obj->modify( '+' . $duration . ' minutes' );
$meeting_date_unix = $meeting_date_obj->format( 'U' );

$utc_date_time = $start_time;
$date          = wp_date( bp_core_date_format( true, true, __( ' \a\t ', 'buddyboss-pro' ) ), strtotime( $utc_date_time ), new DateTimeZone( $timezone ) );
?>

<div class="zoom-meeting-block">
    <div class="zoom-meeting-block-info">
        <h2 id="bp-zoom-meeting-block-title-<?php echo $meeting_id; ?>">
            <?php echo $topic; ?>
	        <?php if ( $recurring ) : ?>
                <span class="recurring-meeting-label"><?php _e( 'Recurring', 'buddyboss-pro' ); ?></span>
	        <?php endif; ?>
	        <?php if ( 'started' === $status ) : ?>
                <span class="live-meeting-label"><?php _e( 'Live', 'buddyboss-pro' ); ?></span>
	        <?php endif; ?>
        </h2>
        <div class="bb-meeting-date zoom-meeting_date"><?php echo $date . ( ! empty( $timezone ) ? ' (' . bp_zoom_get_timezone_label( $timezone ) . ')' : '' ); ?></div>
		<?php if ( $recurring ) : ?>
            <div class="bb-meeting-occurrence"><?php echo bp_zoom_get_recurrence_label( $bp_zoom_meeting_block->id, $bp_zoom_meeting_block ); ?></div>
		<?php endif; ?>
        <div class="bp-zoom-block-show-details">
            <a href="#bp-zoom-block-show-details-popup-<?php echo $meeting_id; ?>"
               class="show-meeting-details">
                <span class="bb-icon-calendar-small"></span> <?php _e( 'Meeting Details', 'buddyboss-pro' ); ?>
            </a>
        </div>
        <div id="bp-zoom-block-show-details-popup-<?php echo $meeting_id; ?>"
             class="bzm-white-popup bp-zoom-block-show-details mfp-hide">
            <header class="bb-zm-model-header"><?php echo $topic; ?>
                <button title="Close (Esc)" type="button" class="mfp-close">×</button>
            </header>
            <div id="bp-zoom-single-meeting" class="meeting-item meeting-item-table single-meeting-item-table">
                <div class="single-meeting-item">
                    <div class="meeting-item-head"><?php _e( 'Date and Time', 'buddyboss-pro' ); ?></div>
                    <div class="meeting-item-col">
						<?php echo $date . ( ! empty( $timezone ) ? ' (' . bp_zoom_get_timezone_label( $timezone ) . ')' : '' ); ?>
                    </div>
                </div>
	            <?php if ( $recurring ) : ?>
                    <div class="single-meeting-item">
                        <div class="meeting-item-head"><?php _e( 'Occurrences', 'buddyboss-pro' ); ?></div>
                        <div class="meeting-item-col">
                            <?php echo bp_zoom_get_recurrence_label( $bp_zoom_meeting_block->id, $bp_zoom_meeting_block ); ?>
                        </div>
                    </div>
	            <?php endif; ?>
                <div class="single-meeting-item">
                    <div class="meeting-item-head"><?php _e( 'Meeting ID', 'buddyboss-pro' ); ?></div>
                    <div class="meeting-item-col">
                        <span class="meeting-id"><?php echo $meeting_id; ?></span>
                    </div>
                </div>
				<?php if ( ! empty( $agenda ) ) { ?>
                    <div class="single-meeting-item">
                        <div class="meeting-item-head"><?php _e( 'Description', 'buddyboss-pro' ); ?></div>
                        <div class="meeting-item-col"><?php echo nl2br( $agenda ); ?></div>
                    </div>
				<?php }
				$hours   = ( ( 0 !== $duration ) ? floor( $duration / 60 ) : 0 );
				$minutes = ( ( 0 !== $duration ) ? ( $duration % 60 ) : 0 );
				?>
                <div class="single-meeting-item">
                    <div class="meeting-item-head"><?php _e( 'Duration', 'buddyboss-pro' ); ?></div>
                    <div class="meeting-item-col">
						<?php
						if ( 0 < $hours ) {
							echo ' ' . sprintf( _n( '%d hour', '%d hours', $hours, 'buddyboss-pro' ), $hours );
						}
						if ( 0 < $minutes ) {
							echo ' ' . sprintf( _n( '%d minute', '%d minutes', $minutes, 'buddyboss-pro' ), $minutes );
						}
						?>
                    </div>
                </div>
                <div class="single-meeting-item">
                    <div class="meeting-item-head"><?php _e( 'Meeting Passcode', 'buddyboss-pro' ); ?></div>
                    <div class="meeting-item-col">
						<?php if ( ! empty( $password ) ) : ?>
                            <div class="z-form-row-action">
                                <div class="pass-wrap">
                                    <span class="hide-password on"><strong>&middot;&middot;&middot;&middot;&middot;&middot;&middot;&middot;&middot;</strong></span>
                                    <span class="show-password"><strong><?php echo $password; ?></strong></span>
                                </div>
                                <div class="pass-toggle">
                                    <a href="javascript:;" class="toggle-password show-pass on"><i
                                                class="bb-icon-eye"></i><?php _e( 'Show passcode', 'buddyboss-pro' ); ?>
                                    </a>
                                    <a href="javascript:;" class="toggle-password hide-pass"><i
                                                class="bb-icon-eye-off"></i><?php _e( 'Hide passcode', 'buddyboss-pro' ); ?>
                                    </a>
                                </div>
                            </div>
						<?php else : ?>
                            <span class="no-pass-required">
						<i class="bb-icon-close"></i>
						<span><?php _e( 'No passcode required', 'buddyboss-pro' ); ?></span>
					</span>
						<?php endif; ?>
                    </div>
                </div>
				<?php
				if ( ! empty( $registration_url ) ) {
					?>
                    <div class="single-meeting-item">
                        <div class="meeting-item-head"><?php _e( 'Registration Link', 'buddyboss-pro' ); ?></div>
                        <div class="meeting-item-col">
                            <div class="copy-link-wrap">
                                <a class="bb-registration-url" target="_blank"
                                   href="<?php echo esc_url( $registration_url ); ?>"><?php echo esc_url( $registration_url ); ?></a>
                            </div>
                        </div>
                    </div>
					<?php
				} ?>
				<?php if ( ! empty( $join_url ) ) { ?>
                    <div class="single-meeting-item">
                        <div class="meeting-item-head"><?php _e( 'Meeting Link', 'buddyboss-pro' ); ?></div>
                        <div class="meeting-item-col">
                            <div class="copy-link-wrap">
                                <a class="bb-invitation-url" target="_blank"
                                   href="<?php echo esc_url( $join_url ); ?>"><?php echo esc_url( $join_url ); ?></a>
                                <a class="edit copy-invitation-link"
                                   href="#copy-invitation-popup-<?php echo $meeting_id; ?>" role="button"
                                   data-meeting-id="<?php echo $meeting_id; ?>"><span
                                            class="bb-icon bb-icon-eye"></span><?php _e( 'View Invitation', 'buddyboss-pro' ); ?>
                                </a>

                                <div id="copy-invitation-popup-<?php echo $meeting_id; ?>"
                                     class="bzm-white-popup copy-invitation-popup copy-invitation-popup-block mfp-hide">
                                    <header class="bb-zm-model-header"><?php _e( 'View Invitation', 'buddyboss-pro' ); ?>
                                        <a href="#bp-zoom-block-show-details-popup-<?php echo $meeting_id; ?>"
                                           class="show-meeting-details"
                                           title="<?php _e( 'Close', 'buddyboss-pro' ); ?>"><i
                                                    class="bb-icon-close"></i><a/>
                                    </header>

                                    <div id="meeting-invitation-container">
                                            <textarea id="meeting-invitation"
                                                      readonly="readonly"><?php echo bp_get_zoom_meeting_invitation( $meeting_id ); ?></textarea>
                                    </div>

                                    <footer class="bb-zm-model-footer">
                                        <a href="#" id="copy-invitation-details" class="button small"
                                           data-copied="<?php _e( 'Copied to clipboard', 'buddyboss-pro' ); ?>"><?php _e( 'Copy Meeting Invitation', 'buddyboss-pro' ); ?></a>
                                    </footer>
                                </div>
                            </div>
                        </div>
                    </div>
				<?php } ?>
                <div class="single-meeting-item">
                    <div class="meeting-item-head"><?php _e( 'Video', 'buddyboss-pro' ); ?></div>
                    <div class="meeting-item-col">
                        <div class="video-info-wrap">
                            <span><?php _e( 'Host', 'buddyboss-pro' ); ?></span>
                            <span class="info-status"><?php echo $host_video ? __( ' On', 'buddyboss-pro' ) : __( 'Off', 'buddyboss-pro' ); ?></span>
                        </div>
                        <div class="video-info-wrap">
                            <span><?php _e( 'Participant', 'buddyboss-pro' ); ?></span>
                            <span class="info-status"><?php echo $participants_video ? __( 'On', 'buddyboss-pro' ) : __( 'Off', 'buddyboss-pro' ); ?></span>
                        </div>
                    </div>
                </div>
                <div class="single-meeting-item">
                    <div class="meeting-item-head"><?php _e( 'Meeting Options', 'buddyboss-pro' ); ?></div>
                    <div class="meeting-item-col">
						<?php
						$bp_get_zoom_meeting_join_before_host  = $join_before_host ? 'yes' : 'no';
						$bp_get_zoom_meeting_mute_participants = $mute_participants ? 'yes' : 'no';
						$bp_get_zoom_meeting_waiting_room      = $waiting_room ? 'yes' : 'no';
						$bp_get_zoom_meeting_authentication    = $meeting_authentication ? 'yes' : 'no';
						$bp_get_zoom_meeting_auto_recording    = ( 'cloud' === $auto_recording ) ? 'yes' : 'no';
						?>
                        <div class="bb-meeting-option <?php echo $bp_get_zoom_meeting_join_before_host; ?>">
                            <i class="<?php echo $join_before_host ? 'bb-icon-check-small' : 'bb-icon-close'; ?>"></i>
                            <span><?php _e( 'Enable join before host', 'buddyboss-pro' ); ?></span>
                        </div>
                        <div class="bb-meeting-option <?php echo $bp_get_zoom_meeting_mute_participants; ?>">
                            <i class="<?php echo $mute_participants ? 'bb-icon-check-small' : 'bb-icon-close'; ?>"></i>
                            <span><?php _e( 'Mute participants upon entry', 'buddyboss-pro' ); ?></span>
                        </div>
                        <div class="bb-meeting-option <?php echo $bp_get_zoom_meeting_waiting_room; ?>">
                            <i class="<?php echo $waiting_room ? 'bb-icon-check-small' : 'bb-icon-close'; ?>"></i>
                            <span><?php _e( 'Enable waiting room', 'buddyboss-pro' ); ?></span>
                        </div>
                        <div class="bb-meeting-option <?php echo $bp_get_zoom_meeting_authentication; ?>">
                            <i class="<?php echo $meeting_authentication ? 'bb-icon-check-small' : 'bb-icon-close'; ?>"></i>
                            <span><?php _e( 'Only authenticated users can join', 'buddyboss-pro' ); ?></span>
                        </div>
                        <div class="bb-meeting-option <?php echo $bp_get_zoom_meeting_auto_recording; ?>">
                            <i class="<?php echo 'cloud' === $auto_recording ? 'bb-icon-check-small' : 'bb-icon-close'; ?>"></i>
                            <span><?php _e( 'Record the meeting automatically in the cloud', 'buddyboss-pro' ); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="zoom-meeting-block-right">
		<?php if ( ! $meeting_is_started ) : ?>
            <div class="bp_zoom_countdown countdownHolder" data-timer="<?php echo $occurrence_date_unix; ?>"></div>
		<?php endif; ?>
		<?php
		$recordings = bp_zoom_recording_get( array(), array(
			'meeting_id' => $meeting_id
		) );

		if ( bp_zoom_is_zoom_recordings_enabled() ) : ?>
            <div id="bp-zoom-meeting-recording-<?php echo $meeting_id; ?>" data-title="<?php echo $topic; ?>"
                 data-meeting-id="<?php echo $meeting_id; ?>" class="bp-zoom-meeting-recording-fetch">
				<?php set_query_var( 'recording_fetch', 'no' ); ?>
				<?php set_query_var( 'meeting_id', $meeting_id ); ?>
				<?php set_query_var( 'topic', $topic ); ?>
				<?php bp_get_template_part( 'zoom/meeting/recordings' ); ?>
            </div>
		<?php endif; ?>
		<?php if ( $meeting_is_started && $current_date < $meeting_date_unix ) : ?>
            <div class="meeting-actions">
                <a class="button small primary join-meeting-in-app" target="_blank"
                   href="<?php echo $can_start_meeting ? esc_url( $start_url ) : esc_url( $join_url ); ?>">
					<?php if ( $can_start_meeting ) : ?>
						<?php _e( 'Start Meeting', 'buddyboss-pro' ); ?>
					<?php else : ?>
						<?php _e( 'Join Meeting', 'buddyboss-pro' ); ?>
					<?php endif; ?>
                </a>
            </div>
		<?php endif; ?>
    </div>
</div>
