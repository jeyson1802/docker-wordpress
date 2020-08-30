<?php
/**
 * BuddyBoss - Groups Zoom Single Meeting
 *
 * @since 1.0.0
 */
?>
<div class="meeting-item-container" data-id="<?php bp_zoom_meeting_id(); ?>" data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>" data-is-recurring="<?php echo ( 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() || bp_get_zoom_meeting_recurring() ) ? '1' : '0'; ?>" <?php echo 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ? 'data-occurrence-id="'. bp_get_zoom_meeting_occurrence_id() .'"' : ''; ?>>
	<div class="bb-title-wrap">
		<a href="#" class="bp-back-to-meeting-list"><span class="bb-icon-chevron-left"></span></a>
		<div>
			<h2 class="bb-title">
                <?php bp_zoom_meeting_title(); ?>
				<?php if ( 8 === bp_get_zoom_meeting_type() ) : ?>
                    <span class="recurring-meeting-label"><?php _e( 'Recurring', 'buddyboss-pro' ); ?></span>
				<?php endif; ?>
            </h2>
			<div class="bb-timezone">
                <?php
                $utc_date_time  = bp_get_zoom_meeting_start_date_utc();
                $time_zone      = bp_get_zoom_meeting_timezone();
                $date           = wp_date(  bp_core_date_format( true, true, __( ' \a\t ', 'buddyboss-pro' ) ), strtotime( $utc_date_time ), new DateTimeZone( $time_zone ) );
                echo $date . ( ! empty( $time_zone ) ? ' (' . bp_zoom_get_timezone_label( $time_zone ) . ')' : '' );
                ?>
			</div>
		</div>
		<?php if ( bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), bp_get_current_group_id() ) && bp_zoom_groups_can_user_manage_meeting( bp_get_zoom_meeting_id() ) ) : ?>
			<div class="meeting-actions">
				<a href="#" class="meeting-actions-anchor">
					<i class="bb-icon-menu-dots-v"></i>
				</a>
				<div class="meeting-actions-list">
					<ul>
						<?php if ( true !== bp_get_zoom_meeting_is_past() ) : ?>
							<li class="bp-zoom-meeting-edit">
                                <?php if ( 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ) : ?>
                                    <a role="button" id="bp-zoom-meeting-occurrence-edit-button" class="edit-meeting" href="#" data-id="bp-meeting-edit">
                                        <i class="bb-icon-edit-square"></i><?php _e( 'Edit this Meeting', 'buddyboss-pro' ); ?>
                                    </a>
                                    <div id="bp-zoom-edit-occurrence-popup-<?php echo bp_get_zoom_meeting_occurrence_id(); ?>" class="bzm-white-popup mfp-hide bp-zoom-edit-occurrence-popup">
                                        <header class="bb-zm-model-header"><?php _e( 'You\'re changing a recurring meeting.', 'buddyboss-pro' ); ?></header>

                                        <div id="recurring-meeting-edit-container">
			                                <p>
												<?php _e( 'Do you want to edit all occurrences of this meeting, or only the selected occurrence?', 'buddyboss-pro' ); ?>
			                                </p>
                                        </div>

                                        <footer class="bb-zm-model-footer">
                                            <a href="#" id="bp-zoom-all-meeting-edit" class="button outline small" data-id="<?php bp_zoom_meeting_id(); ?>" data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>" data-is-recurring="<?php echo ( 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() || bp_get_zoom_meeting_recurring() ) ? '1' : '0'; ?>" <?php echo 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ? 'data-occurrence-id="'. bp_get_zoom_meeting_occurrence_id() .'"' : ''; ?>><?php _e( 'All occurrences', 'buddyboss-pro' ); ?></a>
                                            <a href="#" id="bp-zoom-only-this-meeting-edit" class="button small" data-id="<?php bp_zoom_meeting_id(); ?>" data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>" data-is-recurring="<?php echo ( 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() || bp_get_zoom_meeting_recurring() ) ? '1' : '0'; ?>" <?php echo 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ? 'data-occurrence-id="'. bp_get_zoom_meeting_occurrence_id() .'"' : ''; ?>><?php _e( 'Only this meeting', 'buddyboss-pro' ); ?></a>
<!--                                            <a href="javascript:$.magnificPopup.close();"><?php _e( 'Cancel', 'buddyboss-pro' ); ?></a>-->
                                        </footer>
                                    </div>
                                <?php else: ?>
                                    <a role="button" id="bp-zoom-meeting-edit-button" class="edit-meeting" href="#" data-id="<?php bp_zoom_meeting_id(); ?>" data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>" data-is-recurring="<?php echo ! empty( bp_get_zoom_meeting_parent() ) ? '1' : '0'; ?>" <?php echo ! empty( bp_get_zoom_meeting_parent() ) ? 'data-occurrence-id="'. bp_get_zoom_meeting_occurrence_id() .'"' : ''; ?>>
                                        <i class="bb-icon-edit-square"></i><?php _e( 'Edit this Meeting', 'buddyboss-pro' ); ?>
                                    </a>
                                <?php endif; ?>
                            </li>
						<?php endif; ?>
                        <li class="bp-zoom-meeting-delete">
                            <?php if ( 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ) : ?>
                                <a role="button" id="bp-zoom-meeting-occurrence-delete-button" class="delete" href="#">
                                    <i class="bb-icon-trash"></i><?php _e( 'Delete this Meeting', 'buddyboss-pro' ); ?>
                                </a>
                                <div id="bp-zoom-delete-occurrence-popup-<?php echo bp_get_zoom_meeting_occurrence_id(); ?>" class="bzm-white-popup mfp-hide bp-zoom-delete-occurrence-popup">
                                    <header class="bb-zm-model-header"><?php _e( 'Delete Meeting', 'buddyboss-pro' ); ?></header>

                                    <div id="recurring-meeting-delete-container">
										<p>
											<?php echo __( 'Topic: ', 'buddyboss-pro' ) . bp_get_zoom_meeting_title(); ?><br/>
											<?php echo __( 'Time: ', 'buddyboss-pro' ) . $date; ?>
										</p>
                                    </div>

                                    <footer class="bb-zm-model-footer">
                                        <a href="#" id="bp-zoom-only-this-meeting-delete" class="button small" data-id="<?php bp_zoom_meeting_id(); ?>" data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>" data-is-recurring="<?php echo ( 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() || bp_get_zoom_meeting_recurring() ) ? '1' : '0'; ?>" <?php echo 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ? 'data-occurrence-id="'. bp_get_zoom_meeting_occurrence_id() .'"' : ''; ?>><?php _e( 'Delete This Occurrence', 'buddyboss-pro' ); ?></a>
                                        <a href="#" id="bp-zoom-all-meeting-delete"  class="button outline small error" data-id="<?php bp_zoom_meeting_id(); ?>" data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>" data-is-recurring="<?php echo ( 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() || bp_get_zoom_meeting_recurring() ) ? '1' : '0'; ?>" <?php echo 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ? 'data-occurrence-id="'. bp_get_zoom_meeting_occurrence_id() .'"' : ''; ?>><?php _e( 'Delete All Occurrences', 'buddyboss-pro' ); ?></a>
<!--                                        <a href="javascript:$.magnificPopup.close();">--><?php //_e( 'Cancel', 'buddyboss-pro' ); ?><!--</a>-->
                                    </footer>
                                </div>
                            <?php else: ?>
                                <a role="button" class="delete bp-zoom-delete-meeting" href="javascript:;"><i class="bb-icon-trash"></i><?php _e( 'Delete this Meeting', 'buddyboss-pro' ); ?></a>
                            <?php endif; ?>
                        </li>
					</ul>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<div id="bp-zoom-single-meeting" class="meeting-item meeting-item-table single-meeting-item-table" data-meeting-start-date="<?php echo wp_date( 'Y-m-d', strtotime( bp_get_zoom_meeting_start_date_utc() ) ); ?>">
		<div class="single-meeting-item">
			<div class="meeting-item-head"><?php _e( 'Meeting ID', 'buddyboss-pro' ); ?></div>
			<div class="meeting-item-col">
				<span class="meeting-id"><?php bp_zoom_meeting_zoom_meeting_id(); ?></span>
				<?php if ( bp_get_zoom_meeting_recurring() || 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ) : ?>
                    <div class="bb-meeting-occurrence"><?php echo bp_zoom_get_recurrence_label( bp_get_zoom_meeting_id() ); ?></div>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( ! empty( bp_get_zoom_meeting_description() ) ) : ?>
            <div class="single-meeting-item">
                <div class="meeting-item-head"><?php _e( 'Description', 'buddyboss-pro' ); ?></div>
                <div class="meeting-item-col"><?php echo nl2br( bp_get_zoom_meeting_description() ); ?></div>
            </div>
		<?php endif; ?>

		<?php if ( true !== bp_get_zoom_meeting_is_past() ) {
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
			<?php
		}
		?>
		<div class="single-meeting-item">
			<div class="meeting-item-head"><?php _e( 'Meeting Passcode', 'buddyboss-pro' ); ?></div>
			<div class="meeting-item-col">
				<?php if ( ! empty( bp_get_zoom_meeting_password() ) ) : ?>
					<div class="z-form-row-action">
						<div class="pass-wrap">
							<span class="hide-password on"><strong>&middot;&middot;&middot;&middot;&middot;&middot;&middot;&middot;&middot;</strong></span>
							<span class="show-password"><strong><?php echo bp_get_zoom_meeting_password(); ?></strong></span>
						</div>
						<div class="pass-toggle">
							<a href="javascript:;" class="toggle-password show-pass on"><i class="bb-icon-eye"></i><?php _e( 'Show passcode', 'buddyboss-pro' ); ?></a>
							<a href="javascript:;" class="toggle-password hide-pass"><i class="bb-icon-eye-off"></i><?php _e( 'Hide passcode', 'buddyboss-pro' ); ?></a>
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
        $registration_url = bp_get_zoom_meeting_registration_url();
		if ( ! empty( $registration_url ) ) {
			?>
			<div class="single-meeting-item">
				<div class="meeting-item-head"><?php _e( 'Registration Link', 'buddyboss-pro' ); ?></div>
				<div class="meeting-item-col">
					<div class="copy-link-wrap">
						<a class="bb-registration-url" target="_blank" href="<?php echo esc_url( $registration_url ); ?>"><?php echo esc_url( $registration_url ); ?></a>
					</div>
				</div>
			</div>
			<?php
		}

		$join_url = bp_get_zoom_meeting_zoom_join_url();
		if ( ! empty( $join_url ) ) { ?>
		<div class="single-meeting-item">
			<div class="meeting-item-head"><?php _e( 'Meeting Link', 'buddyboss-pro' ); ?></div>
			<div class="meeting-item-col">
				<div class="copy-link-wrap">
					<a class="bb-invitation-url" target="_blank" href="<?php echo esc_url( $join_url ); ?>"><?php echo esc_url( $join_url ); ?></a>
					<a id="copy-invitation-link" class="edit copy-invitation-link" href="#copy-invitation-popup" role="button" data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>"><span class="bb-icon bb-icon-eye"></span><?php _e( 'View Invitation', 'buddyboss-pro' ); ?></a>

					<div id="copy-invitation-popup" class="bzm-white-popup copy-invitation-popup mfp-hide">
						<header class="bb-zm-model-header"><?php _e( 'View Invitation', 'buddyboss-pro' ); ?></header>

						<div id="meeting-invitation-container">
							<textarea id="meeting-invitation" readonly="readonly"><?php echo bp_get_zoom_meeting_invitation( bp_get_zoom_meeting_zoom_meeting_id() ); ?></textarea>
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
				$bp_get_zoom_meeting_join_before_host = bp_get_zoom_meeting_join_before_host() ? 'yes' : 'no';
				$bp_get_zoom_meeting_mute_participants = bp_get_zoom_meeting_mute_participants() ? 'yes' : 'no';
				$bp_get_zoom_meeting_waiting_room = bp_get_zoom_meeting_waiting_room() ? 'yes' : 'no';
				$bp_get_zoom_meeting_authentication = bp_get_zoom_meeting_authentication() ? 'yes' : 'no';
				$bp_get_zoom_meeting_auto_recording = ( 'cloud' === bp_get_zoom_meeting_auto_recording() ) ? 'yes' : 'no';
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
					<i class="<?php echo bp_get_zoom_meeting_authentication() ? 'bb-icon-check-small' : 'bb-icon-close'; ?>"></i>
					<span><?php _e( 'Only authenticated users can join', 'buddyboss-pro' ); ?></span>
				</div>
				<div class="bb-meeting-option <?php echo $bp_get_zoom_meeting_auto_recording; ?>">
					<i class="<?php echo 'cloud' === bp_get_zoom_meeting_auto_recording() ? 'bb-icon-check-small' : 'bb-icon-close'; ?>"></i>
					<span><?php _e( 'Record the meeting automatically in the cloud', 'buddyboss-pro' ); ?></span>
				</div>
			</div>
		</div>

		<?php
	        $occurrence_date_unix = wp_date( 'U', strtotime( bp_get_zoom_meeting_start_date_utc() ), new DateTimeZone( 'UTC' ) );
	        $meeting_is_started   = ( $occurrence_date_unix > wp_date( 'U', strtotime( '+10 minutes' ) ) ) ? false : true;

	        $current_date     = wp_date( 'U' );
	        $meeting_date_obj = new DateTime( bp_get_zoom_meeting_start_date_utc(), new DateTimeZone( 'UTC' ) );
	        $meeting_date_obj->modify( '+' . bp_get_zoom_meeting_duration() . ' minutes' );
	        $meeting_date_unix = $meeting_date_obj->format( 'U' );
		?>

		<?php if ( ! $meeting_is_started ) : ?>
			<div class="single-meeting-item bb-countdown-wrap">
				<div class="meeting-item-head"></div>
				<div class="meeting-item-col">
					<div class="bp_zoom_countdown countdownHolder" data-timer="<?php echo $occurrence_date_unix; ?>"></div>
				</div>
			</div>

		<?php endif; ?>

        <div class="single-meeting-item last-col meeting-buttons-wrap"><?php if ( ( $meeting_is_started && $current_date < $meeting_date_unix ) ) : ?><div class="meeting-item-col meeting-action last-col full text-right">
                <a type="button"
                   class="button primary small join-meeting-in-app"
                   target="_blank"
                   href="<?php echo bp_zoom_can_current_user_start_meeting( bp_get_zoom_meeting_id() ) ? bp_get_zoom_meeting_zoom_start_url() : bp_get_zoom_meeting_zoom_join_url(); ?>">
	                <?php if ( bp_zoom_can_current_user_start_meeting( bp_get_zoom_meeting_id() ) ) {
		                _e( 'Start Meeting', 'buddyboss-pro' );
	                } else {
		                _e( 'Join Meeting', 'buddyboss-pro' );
	                }  ?>
                </a>
            </div><?php endif; ?>

            <?php if ( bp_zoom_is_zoom_recordings_enabled() ) : ?>
                <div class="bb-recordings-wrap">
					<div class="meeting-item-head"></div>
                    <div id="bp-zoom-meeting-recording-<?php echo bp_get_zoom_meeting_zoom_meeting_id(); ?>" class="bp-zoom-meeting-recording-fetch" data-title="<?php echo bp_get_zoom_meeting_title(); ?>" data-meeting-id="<?php echo bp_get_zoom_meeting_zoom_meeting_id(); ?>">
		                <?php
                        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	                        set_query_var( 'recording_fetch', 'yes' );
                        } else {
	                        set_query_var( 'recording_fetch', 'no' );
                        }
                        ?>
		                <?php bp_get_template_part( 'zoom/meeting/recordings' ); ?>
                    </div>
				</div>
		    <?php endif; ?>
        </div>
	</div>
</div>
