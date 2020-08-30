<?php
/**
 * BuddyBoss - Zoom Activity Meeting Entry
 *
 * @since 1.0.0
 */

$url = false;
if ( bp_get_zoom_meeting_group_id() && bp_is_active( 'groups' ) ) {
	$group_link = bp_get_group_permalink( groups_get_group( bp_get_zoom_meeting_group_id() ) );
	$url        = trailingslashit( $group_link . 'zoom/meetings/' . bp_get_zoom_meeting_id() );
}
$current_date         = wp_date( 'U' );
$occurrence_date_unix = wp_date( 'U', strtotime( bp_get_zoom_meeting_start_date_utc() ), new DateTimeZone( 'UTC' ) );
$meeting_is_started   = ( $occurrence_date_unix > wp_date( 'U', strtotime( '+10 minutes' ) ) ) ? false : true;
$meeting_date         = wp_date( bp_core_date_format( true, true, __( ' \a\t ', 'buddyboss-pro' ) ), strtotime( bp_get_zoom_meeting_start_date_utc() ) );
$utc_date_time        = bp_get_zoom_meeting_start_date_utc();
$date                 = wp_date( bp_core_date_format( true, true, __( ' \a\t ', 'buddyboss-pro' ) ), strtotime( $utc_date_time ), new DateTimeZone( bp_get_zoom_meeting_timezone() ) );
?>
<div class="zoom-meeting-block">
    <div class="zoom-meeting-block-info">
        <a href="<?php echo $url ? esc_url( $url ) : ''; ?>"><h2><?php bp_zoom_meeting_title(); ?></h2></a>
        <div class="bb-meeting-date zoom-meeting_date"><?php echo $date . ( ! empty( bp_get_zoom_meeting_timezone() ) ? ' (' . bp_zoom_get_timezone_label( bp_get_zoom_meeting_timezone() ) . ')' : '' ); ?></div>
	    <?php if ( bp_get_zoom_meeting_recurring() ) : ?>
            <div class="bb-meeting-occurrence"><?php echo bp_zoom_get_recurrence_label( bp_get_zoom_meeting_id() ); ?></div>
	    <?php endif; ?>
        <div class="bp-zoom-block-show-details">
            <a href="#bp-zoom-block-show-details-popup-<?php bp_zoom_meeting_zoom_meeting_id(); ?>"
               class="show-meeting-details">
                <span class="bb-icon-calendar-small"></span> <?php _e( 'Meeting Details', 'buddyboss-pro' ); ?>
            </a>
        </div>
        <div id="bp-zoom-block-show-details-popup-<?php bp_zoom_meeting_zoom_meeting_id(); ?>"
             class="bzm-white-popup bp-zoom-block-show-details mfp-hide">
            <header class="bb-zm-model-header">
				<span><?php bp_zoom_meeting_title(); ?></span>
                <button title="Close (Esc)" type="button" class="mfp-close">×</button>
			</header>
            <div id="bp-zoom-single-meeting" class="meeting-item meeting-item-table single-meeting-item-table">
                <div class="single-meeting-item">
                    <div class="meeting-item-head"><?php _e( 'Date and Time', 'buddyboss-pro' ); ?></div>
                    <div class="meeting-item-col">
					    <?php
					    $utc_date_time  = bp_get_zoom_meeting_start_date_utc();
					    $date           = wp_date(  bp_core_date_format( true, true, __( ' \a\t ', 'buddyboss-pro' ) ), strtotime( $utc_date_time ), new DateTimeZone( bp_get_zoom_meeting_timezone() ) );
					    echo $date . ( ! empty( bp_get_zoom_meeting_timezone() ) ? ' (' . bp_zoom_get_timezone_label( bp_get_zoom_meeting_timezone() ) . ')' : '' );
					    ?>
                    </div>
                </div>
                <div class="single-meeting-item">
                    <div class="meeting-item-head"><?php _e( 'Meeting ID', 'buddyboss-pro' ); ?></div>
                    <div class="meeting-item-col">
                        <span class="meeting-id"><?php bp_zoom_meeting_zoom_meeting_id(); ?></span>
                    </div>
                </div>
			    <?php if ( ! empty( bp_get_zoom_meeting_description() ) ) { ?>
                    <div class="single-meeting-item">
                        <div class="meeting-item-head"><?php _e( 'Description', 'buddyboss-pro' ); ?></div>
                        <div class="meeting-item-col"><?php echo nl2br( bp_get_zoom_meeting_description() ); ?></div>
                    </div>
			    <?php }
			    $duration = bp_get_zoom_meeting_duration();
			    $hours    = ( ( 0 !== $duration ) ? floor( $duration / 60 ) : 0 );
			    $minutes  = ( ( 0 !== $duration ) ? ( $duration % 60 ) : 0 );
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
                    <div class="meeting-item-head"><?php _e( 'Meeting Password', 'buddyboss-pro' ); ?></div>
                    <div class="meeting-item-col">
					    <?php if ( ! empty( bp_get_zoom_meeting_password() ) ) : ?>
                            <div class="z-form-row-action">
                                <div class="pass-wrap">
                                    <span class="hide-password on"><strong>&middot;&middot;&middot;&middot;&middot;&middot;&middot;&middot;&middot;</strong></span>
                                    <span class="show-password"><strong><?php echo bp_get_zoom_meeting_password(); ?></strong></span>
                                </div>
                                <div class="pass-toggle">
                                    <a href="javascript:;" class="toggle-password show-pass on"><i
                                                class="bb-icon-eye"></i><?php _e( 'Show password', 'buddyboss-pro' ); ?>
                                    </a>
                                    <a href="javascript:;" class="toggle-password hide-pass"><i
                                                class="bb-icon-eye-off"></i><?php _e( 'Hide password', 'buddyboss-pro' ); ?>
                                    </a>
                                </div>
                            </div>
					    <?php else : ?>
                            <span class="no-pass-required">
						<i class="bb-icon-close"></i>
						<span><?php _e( 'No password required', 'buddyboss-pro' ); ?></span>
					</span>
					    <?php endif; ?>
                    </div>
                </div>
			    <?php
			    $registration_url = bp_get_zoom_meeting_registration_url();
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
                <?php $join_url = bp_get_zoom_meeting_zoom_join_url(); ?>
			    <?php if ( ! empty( $join_url ) ) { ?>
                    <div class="single-meeting-item">
                        <div class="meeting-item-head"><?php _e( 'Meeting Link', 'buddyboss-pro' ); ?></div>
                        <div class="meeting-item-col">
                            <div class="copy-link-wrap">
                                <a class="bb-invitation-url" target="_blank"
                                   href="<?php echo esc_url( $join_url ); ?>"><?php echo esc_url( $join_url ); ?></a>
                                <a class="edit copy-invitation-link"
                                   href="#copy-invitation-popup-<?php bp_zoom_meeting_zoom_meeting_id(); ?>" role="button"
                                   data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>"><span
                                            class="bb-icon bb-icon-eye"></span><?php _e( 'View Invitation', 'buddyboss-pro' ); ?>
                                </a>

                                <div id="copy-invitation-popup-<?php bp_zoom_meeting_zoom_meeting_id(); ?>"
                                     class="bzm-white-popup copy-invitation-popup copy-invitation-popup-block mfp-hide">
                                    <header class="bb-zm-model-header">
										<span><?php _e( 'View Invitation', 'buddyboss-pro' ); ?></span>
                                        <a href="#bp-zoom-block-show-details-popup-<?php bp_zoom_meeting_zoom_meeting_id(); ?>" class="show-meeting-details" title="<?php _e( 'Close', 'buddyboss-pro' ); ?>"><i class="bb-icon-close"></i></a>
                                    </header>

                                    <div id="meeting-invitation-container">
                                            <textarea id="meeting-invitation"
                                                      readonly="readonly"><?php echo bp_get_zoom_meeting_invitation( bp_get_zoom_meeting_zoom_meeting_id() ); ?></textarea>
                                    </div>

                                    <footer class="bb-zm-model-footer">
                                        <a href="#" id="copy-invitation-details" class="button small" data-copied="<?php _e( 'Copied to clipboard', 'buddyboss-pro' ); ?>"><?php _e( 'Copy Meeting Invitation', 'buddyboss-pro' ); ?></a>
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
                            <span class="info-status"><?php echo bp_get_zoom_meeting_host_video() ? __( ' On', 'buddyboss-pro' ) : __( 'Off', 'buddyboss-pro' ); ?></span>
                        </div>
                        <div class="video-info-wrap">
                            <span><?php _e( 'Participant', 'buddyboss-pro' ); ?></span>
                            <span class="info-status"><?php echo bp_get_zoom_meeting_participants_video() ? __( 'On', 'buddyboss-pro' ) : __( 'Off', 'buddyboss-pro' ); ?></span>
                        </div>
                    </div>
                </div>
                <div class="single-meeting-item">
                    <div class="meeting-item-head"><?php _e( 'Meeting Options', 'buddyboss-pro' ); ?></div>
                    <div class="meeting-item-col">
					    <?php
					    $bp_get_zoom_meeting_join_before_host  = bp_get_zoom_meeting_join_before_host() ? 'yes' : 'no';
					    $bp_get_zoom_meeting_mute_participants = bp_get_zoom_meeting_mute_participants() ? 'yes' : 'no';
					    $bp_get_zoom_meeting_waiting_room      = bp_get_zoom_meeting_waiting_room() ? 'yes' : 'no';
					    $bp_get_zoom_meeting_authentication    = ! empty( bp_get_zoom_meeting_authentication() ) ? 'yes' : 'no';
					    $bp_get_zoom_meeting_auto_recording    = ( 'cloud' === bp_get_zoom_meeting_auto_recording() ) ? 'yes' : 'no';
					    ?>
                        <div class="bb-meeting-option <?php echo $bp_get_zoom_meeting_join_before_host; ?>">
                            <i class="<?php echo bp_get_zoom_meeting_join_before_host() ? 'bb-icon-check-small' : 'bb-icon-close'; ?>"></i>
                            <span><?php _e( 'Enable join before host', 'buddyboss-pro' ); ?></span>
                        </div>
                        <div class="bb-meeting-option <?php echo $bp_get_zoom_meeting_mute_participants; ?>">
                            <i class="<?php echo bp_get_zoom_meeting_mute_participants() ? 'bb-icon-check-small' : 'bb-icon-close'; ?>"></i>
                            <span><?php _e( 'Mute participants upon entry', 'buddyboss-pro' ); ?></span>
                        </div>
                        <div class="bb-meeting-option <?php echo $bp_get_zoom_meeting_waiting_room; ?>">
                            <i class="<?php echo bp_get_zoom_meeting_waiting_room() ? 'bb-icon-check-small' : 'bb-icon-close'; ?>"></i>
                            <span><?php _e( 'Enable waiting room', 'buddyboss-pro' ); ?></span>
                        </div>
                        <div class="bb-meeting-option <?php echo $bp_get_zoom_meeting_authentication; ?>">
                            <i class="<?php echo ! empty( bp_get_zoom_meeting_authentication() ) ? 'bb-icon-check-small' : 'bb-icon-close'; ?>"></i>
                            <span><?php _e( 'Only authenticated users can join', 'buddyboss-pro' ); ?></span>
                        </div>
                        <div class="bb-meeting-option <?php echo $bp_get_zoom_meeting_auto_recording; ?>">
                            <i class="<?php echo 'cloud' === bp_get_zoom_meeting_auto_recording() ? 'bb-icon-check-small' : 'bb-icon-close'; ?>"></i>
                            <span><?php _e( 'Record the meeting automatically in the cloud', 'buddyboss-pro' ); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="zoom-meeting-block-right">
		<?php if ( ! $meeting_is_started ) : ?>
            <div class="bp_zoom_countdown countdownHolder" data-timer="<?php echo $occurrence_date_unix; ?>"
                 data-reload="0"></div>
		<?php endif; ?>
	    <?php if ( bp_zoom_is_zoom_recordings_enabled() ) : ?>
            <div id="bp-zoom-meeting-recording-<?php bp_zoom_meeting_zoom_meeting_id(); ?>" data-title="<?php bp_zoom_meeting_title(); ?>" data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>" class="bp-zoom-meeting-recording-fetch">
			    <?php set_query_var( 'recording_fetch', 'no' ); ?>
			    <?php set_query_var( 'meeting_id', bp_get_zoom_meeting_zoom_meeting_id() ); ?>
			    <?php set_query_var( 'topic', bp_get_zoom_meeting_title() ); ?>
			    <?php bp_get_template_part( 'zoom/meeting/recordings' ); ?>
            </div>
	    <?php endif; ?>
	    <?php if ( $meeting_is_started && $current_date < $occurrence_date_unix ) : ?>
            <div class="meeting-actions">
                <a class="button small primary join-meeting-in-app" target="_blank"
                   href="<?php echo bp_zoom_can_current_user_start_meeting( bp_get_zoom_meeting_id() ) ? bp_get_zoom_meeting_zoom_start_url() : bp_get_zoom_meeting_zoom_join_url(); ?>">
				    <?php if ( bp_zoom_can_current_user_start_meeting( bp_get_zoom_meeting_id() ) ) : ?>
					    <?php _e( 'Start Meeting', 'buddyboss-pro' ); ?>
				    <?php else : ?>
					    <?php _e( 'Join Meeting', 'buddyboss-pro' ); ?>
				    <?php endif; ?>
                </a>
            </div>
	    <?php endif; ?>
    </div>
</div>
