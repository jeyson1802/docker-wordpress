<?php
/**
 * BuddyBoss - Zoom Meeting Recordings
 *
 * @since 1.0.0
 */
global $zoom_meeting_template;

if ( empty( $meeting_id ) && ! empty( $zoom_meeting_template->meeting->id ) ) {
	$meeting_id = bp_get_zoom_meeting_zoom_meeting_id();
}

if ( empty( $meeting_id ) ) {
	return;
}

$title = '';
if ( ! empty( $topic ) ) {
	$title = $topic;
}

$meeting  = false;
$group_id = false;
if ( ! empty( $zoom_meeting_template->meeting->id ) ) {
	$m_id       = bp_get_zoom_meeting_id();
	$group_id = bp_get_zoom_meeting_group_id();
	$title    = bp_get_zoom_meeting_title();
}

if ( ! empty( $m_id ) && ! empty( $meeting_id ) ) {
	$meeting_obj = new BP_Zoom_Meeting( $m_id );
	if ( ! empty( $meeting_obj ) && 'meeting_occurrence' === $meeting_obj->zoom_type ) {
		$m_id = false;
	}
}

if ( empty( $m_id ) && ! empty( $meeting_id ) ) {
    $meeting_row = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting_id );
	if ( ! empty( $meeting_row ) ) {
		$m_id = $meeting_row->id;
	}
}

if ( ! empty( $m_id ) && empty( $group_id ) ) {
	$meeting = new BP_Zoom_Meeting( $m_id );
	if ( ! empty( $meeting->group_id ) ) {
		$group_id = $meeting->group_id;
	}
	if ( empty( $title ) && ! empty( $meeting->title ) ) {
	    $title = $meeting->title;
    }
}

if ( ! empty( $group_id ) ) {
	$api_key    = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-key', true );
	$api_secret = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-secret', true );

	bp_zoom_conference()->zoom_api_key    = ! empty( $api_key ) ? $api_key : '';
	bp_zoom_conference()->zoom_api_secret = ! empty( $api_secret ) ? $api_secret : '';
}

if ( isset( $recording_fetch ) && 'yes' === $recording_fetch ) {
	$instances  = bp_zoom_conference()->meeting_instances( $meeting_id );
	$recordings = false;
	if ( ! empty( $instances['code'] ) && 200 === $instances['code'] && ! empty( $instances['response']->meetings ) ) {
		foreach ( $instances['response']->meetings as $response_meeting ) {

			$uuid = $response_meeting->uuid;
			if ( false !== strpos( $response_meeting->uuid, '/' ) || false !== strpos( $response_meeting->uuid, '//' ) ) {
				$uuid = '"' . $response_meeting->uuid . '"';
			}

			$uuid_recordings_response = bp_zoom_conference()->recordings_by_meeting( $uuid );
			if ( ! empty( $uuid_recordings_response['code'] ) && 200 === $uuid_recordings_response['code'] && ! empty( $uuid_recordings_response['response'] ) ) {
			    $uuid_recordings_response = $uuid_recordings_response['response'];
				if ( ! empty( $uuid_recordings_response->recording_files ) ) {
					$recordings = true;

					$recording_settings = bp_zoom_conference()->recording_settings( $uuid );
					if ( ! empty( $recording_settings['code'] ) && 404 !== $recording_settings['code'] ) {
						$recording_settings = $recording_settings['response'];
					} else {
						$recording_settings = false;
					}

					foreach ( $uuid_recordings_response->recording_files as $uuid_recordings_response_recording_file ) {
						if ( isset( $uuid_recordings_response_recording_file->id ) && empty( bp_zoom_recording_get( array(), array(
								'recording_id' => $uuid_recordings_response_recording_file->id,
								'meeting_id'   => $meeting_id,
								'uuid'         => $uuid_recordings_response->uuid,
							) ) ) ) {

							$add_args = array(
								'recording_id' => $uuid_recordings_response_recording_file->id,
								'meeting_id'   => $meeting_id,
								'uuid'         => $uuid_recordings_response->uuid,
								'details'      => $uuid_recordings_response_recording_file,
								'file_type'    => $uuid_recordings_response_recording_file->file_type,
								'start_time'   => $uuid_recordings_response->start_time,
							);

							if ( ! empty( $recording_settings->password ) ) {
								$add_args['password'] = $recording_settings->password;
							}

							bp_zoom_recording_add( $add_args );
						}
					}
				}
			}
		}
	}

	if ( ( ! empty( $instances['code'] ) && 200 === $instances['code'] && ( empty( $instances['response']->meetings ) || $instances['response']->meetings ) ) || ! $recordings ) {
		$uuid_recordings_response = bp_zoom_conference()->recordings_by_meeting( $meeting_id );
		if ( ! empty( $uuid_recordings_response['code'] ) && 200 === $uuid_recordings_response['code'] && ! empty( $uuid_recordings_response['response'] ) ) {
			$uuid_recordings_response = $uuid_recordings_response['response'];
			if ( ! empty( $uuid_recordings_response->recording_files ) ) {

				$recording_settings = bp_zoom_conference()->recording_settings( $uuid_recordings_response->uuid );
				if ( ! empty( $recording_settings['code'] ) && 404 !== $recording_settings['code'] ) {
					$recording_settings = $recording_settings['response'];
				} else {
					$recording_settings = false;
				}

				foreach ( $uuid_recordings_response->recording_files as $uuid_recordings_response_recording_file ) {
					if ( isset( $uuid_recordings_response_recording_file->id ) && empty( bp_zoom_recording_get( array(), array(
							'recording_id' => $uuid_recordings_response_recording_file->id,
							'meeting_id'   => $meeting_id,
							'uuid'         => $uuid_recordings_response->uuid,
						) ) ) ) {

						$add_args = array(
							'recording_id' => $uuid_recordings_response_recording_file->id,
							'meeting_id'   => $meeting_id,
							'uuid'         => $uuid_recordings_response->uuid,
							'details'      => $uuid_recordings_response_recording_file,
							'file_type'    => $uuid_recordings_response_recording_file->file_type,
							'start_time'   => $uuid_recordings_response->start_time,
						);

						if ( ! empty( $recording_settings->password ) ) {
							$add_args['password'] = $recording_settings->password;
						}

						bp_zoom_recording_add( $add_args );
					}
				}
			}
		}
	}
}

$recordings = bp_zoom_recording_get( array(), array(
	'meeting_id' => $meeting_id
) );


if ( empty( $recordings ) ) {
	return;
}

if ( ! empty( $m_id ) ) {
	bp_zoom_meeting_update_meta( $m_id, 'zoom_recording_count', count( $recordings ) );
}

$recordings_groups = array();

foreach ( $recordings as $key => $item ) {
	$recordings_groups[ $item->start_time ][ $key ] = $item;
}

$recording_groups_dates = array_keys( $recordings_groups );
$recording_groups_dates_print = array();
foreach ( $recording_groups_dates as $recording_groups_date ) {
	$recording_groups_dates_print[] = wp_date( 'Y-m-d', strtotime( $recording_groups_date ) );
}

$recording_groups_dates_print = array_unique( $recording_groups_dates_print );
?>
<a href="#bp-zoom-block-show-recordings-popup-<?php echo $meeting_id; ?>"
   class="button small outline join-meeting-in-app show-recordings"
   data-meeting-id="<?php echo $meeting_id; ?>"><?php _e( 'Show Recordings', 'buddyboss-pro' ); ?></a>

<div id="bp-zoom-block-show-recordings-popup-<?php echo $meeting_id; ?>"
     class="bzm-white-popup bp-zoom-block-show-recordings mfp-hide">
    <header class="bb-zm-model-header">
        <span class="bp-meeting-title-recording-popup"><?php echo $title; ?></span><?php _e( ' (Recordings)', 'buddyboss-pro' ); ?>
	    <?php if ( count( $recording_groups_dates_print ) > 1 ) { ?>
            <select class="bp-zoom-recordings-dates">
			    <?php foreach ( $recording_groups_dates_print as $recording_groups_dates_print_date ) {
				    ?>
                    <option
                    value="<?php echo $recording_groups_dates_print_date; ?>"><?php echo wp_date( bp_core_date_format(), strtotime( $recording_groups_dates_print_date ) ); ?></option><?php
			    } ?>
            </select>
		    <?php
	    } ?>
    </header>

    <div class="recording-list-row-wrap">
		<?php

		foreach ( $recordings_groups as $date => $recording_group ) {
			$recorded_date = wp_date( bp_core_date_format( true, true, __( ' \a\t ', 'buddyboss-pro' ) ), strtotime( $date ) );

			?>
            <div class="recording-list-row-group" data-recorded-date="<?php echo wp_date( 'Y-m-d', strtotime( $date ) ); ?>">
                <h4 class="clip_title"><?php echo $recorded_date; ?></h4>
				<?php

				foreach ( $recording_group as $recording ) {
					$recorded_time       = '';
					$recording_file      = json_decode( $recording->details );
					$recording_type      = isset( $recording_file->recording_type ) ? $recording_file->recording_type : '';
 					$recording_file_size = isset( $recording_file->file_size ) ? $recording_file->file_size : false;

 					if ( 'TIMELINE' === $recording_file->file_type ) {
 					    continue;
				    }

					if ( ! empty( $recording_file->recording_start ) && ! empty( $recording_file->recording_end ) ) {
						$datetime1     = date_create( $recording_file->recording_start );
						$datetime2     = date_create( $recording_file->recording_end );
						$interval      = date_diff( $datetime1, $datetime2 );
						$recorded_time = $interval->format( '%H:%i:%s' );
					}
					?>

                    <div class="recording-list-row">
                        <div class="recording-preview-img">
                            <span class="<?php echo ( 'MP4' === $recording_file->file_type || 'M4A' === $recording_file->file_type ) ? 'bb-icon-play triangle-play-icon' : ''; ?> <?php echo $recording_type; ?>"></span>
                            <?php if ( in_array( $recording_type, array( 'shared_screen_with_speaker_view', 'shared_screen_with_gallery_view', 'active_speaker', 'shared_screen', 'shared_screen_with_speaker_view(CC)', 'gallery_view' ) ) ) : ?>
                                <img src="<?php echo bp_zoom_integration_url( '/assets/images/recording-video.png' ); ?>"
                                     alt="<?php echo $recording_type; ?>"/>
                            <?php elseif ( 'audio_only' === $recording_type ) : ?>
                                <img src="<?php echo bp_zoom_integration_url( '/assets/images/recording-audio-only.png' ); ?>"
                                     alt="<?php echo $recording_type; ?>"/>
                            <?php elseif ( 'audio_transcript' === $recording_type ) : ?>
                                <img src="<?php echo bp_zoom_integration_url( '/assets/images/recording-audio-transcript.png' ); ?>"
                                     alt="<?php echo $recording_type; ?>"/>
                            <?php elseif ( 'chat_file' === $recording_type ) : ?>
                                <img src="<?php echo bp_zoom_integration_url( '/assets/images/recording-chat-file.png' ); ?>"
                                     alt="<?php echo $recording_type; ?>"/>
                            <?php elseif ( 'TIMELINE' === $recording_type || 'TIMELINE' === $recording_file->file_type ) : ?>
                                <img src="<?php echo bp_zoom_integration_url( '/assets/images/recording-timeline.png' ); ?>"
                                     alt="<?php echo $recording_type; ?>"/>
                            <?php else : ?>
                                <img src="<?php echo bp_zoom_integration_url( '/assets/images/recording-audio-only.png' ); ?>"
                                     alt="<?php echo $recording_type; ?>"/>
                            <?php endif; ?>

							<?php
							if ( ! empty( $recorded_time ) && ( 'MP4' === $recording_file->file_type || 'M4A' === $recording_file->file_type ) ) {
								echo '<span class="bb-video-time">' . $recorded_time . '</span>';
							}
							?>
                            <div class="video_link">
								<?php if ( ! empty( $recording->password ) || ( 'TIMELINE' === $recording_file->file_type || 'TRANSCRIPT' === $recording_file->file_type || 'CHAT' === $recording_file->file_type || 'CC' === $recording_file->file_type ) ) : ?>
                                    <?php if ( ! in_array( $recording_file->file_type, array( 'TIMELINE', 'TRANSCRIPT', 'CHAT', 'CC' ) ) ): ?>
                                    <a class="play_btn_link" target="_blank"
                                       href="<?php echo esc_url( $recording_file->play_url ); ?>"><?php esc_html_e( 'Play', 'buddyboss-pro' ); ?></a>
	                                <?php endif; ?>
								<?php else : ?>
                                    <a class="play_btn" href="#"><?php esc_html_e( 'Play', 'buddyboss-pro' ); ?></a>
								<?php endif; ?>
                            </div>
                        </div>

                        <div class="recording-preview-info">
                            <div class="recording-list-info">
                                <h2 class="clip_title">
                                    <?php
                                    if ( in_array( $recording_type, array( 'shared_screen_with_speaker_view', 'shared_screen_with_gallery_view', 'active_speaker', 'shared_screen', 'shared_screen_with_speaker_view(CC)', 'gallery_view' ) ) ) {
                                        _e( 'Video Recording', 'buddyboss-pro' );
                                    } else if ( 'audio_only' === $recording_type ) {
	                                    _e( 'Audio Recording', 'buddyboss-pro' );
                                    } else if ( 'chat_file' === $recording_type ) {
	                                    _e( 'Chat File', 'buddyboss-pro' );
                                    } else if ( 'audio_transcript' === $recording_type ) {
	                                    _e( 'Audio Transcript', 'buddyboss-pro' );
                                    } else if ( 'TIMELINE' === $recording_type || 'TIMELINE' === $recording_file->file_type ) {
	                                    _e( 'Timeline', 'buddyboss-pro' );
                                    }
                                    ?>
                                </h2>
                                <?php if ( ! empty( $recording_file_size ) ) : ?>
                                    <div class="clip_description">
                                        <?php echo esc_html( bp_core_format_size_units( $recording_file_size, true ) ); ?>
                                    </div>
                                <?php endif; ?>
	                            <?php if ( ! empty( $recording->password ) ) : ?>
                                    <div class="pass-toggle">
                                        <a href="#" class="toggle-password show-pass">
                                            <i class="bb-icon-eye"></i><?php _e( 'Show password', 'buddyboss-pro' ); ?>
                                        </a>
                                        <span class="show-password bp-hide"><a href="#" class="toggle-password hide-pass"><i class="bb-icon-eye-off"></i></a><span class="recording-password"><?php echo $recording->password; ?></span></span>
                                    </div>
	                            <?php endif; ?>
                            </div>
                            <?php if ( bp_zoom_is_zoom_recordings_links_enabled() ) : ?>
                                <div class="recording-button-wrap">
                                    <?php if ( ! in_array( $recording_file->file_type, array( 'TIMELINE', 'TRANSCRIPT', 'CHAT', 'CC' ) ) && ! empty( $recording_file->play_url ) ): ?>
                                        <a href="#" id="copy-download-link" class="button small outline bb-copy-link"
                                           data-download-link="<?php echo esc_url( $recording_file->play_url ); ?>"
                                           data-copied="<?php _e( 'Copied to clipboard', 'buddyboss-pro' ); ?>"><i
                                                    class="bb-icon-copy"></i><?php esc_html_e( 'Copy Link', 'buddyboss-pro' ); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $recording_file->download_url ) ) : ?>
                                        <a href="<?php echo esc_url( $recording_file->download_url ); ?>" <?php echo( empty( $recording->password ) ? '' : 'target="_blank"' ); ?>
                                           class="button small outline downloadmeeting downloadclip"><i
                                                    class="bb-icon-download"></i><?php esc_html_e( 'Download', 'buddyboss-pro' ); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

						<?php if ( empty( $recording->password ) && ( 'MP4' === $recording_file->file_type || 'M4A' === $recording_file->file_type ) ) : ?>
                            <div class="bb-media-model-wrapper bb-internal-model" style="display: none;">

                                <a data-balloon-pos="left"
                                   data-balloon="<?php esc_attr_e( 'Close', 'buddyboss-pro' ); ?>"
                                   class="bb-close-media-theatre bb-close-model" href="#">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14">
                                        <path fill="none" stroke="#FFF" stroke-linecap="round" stroke-linejoin="round"
                                              stroke-width="2" d="M13 1L1 13m12 0L1 1" opacity=".7"/>
                                    </svg>
                                </a>

                                <div class="bb-media-model-container">
                                    <div class="bb-media-model-inner">
                                        <div class="bb-media-section">
											<?php if ( 'MP4' === $recording_file->file_type ) : ?>
                                                <video controls>
                                                    <source src="<?php echo esc_url( $recording_file->download_url ); ?>"
                                                            type="video/mp4">
                                                    <p><?php esc_html_e( 'Your browser does not support HTML5 video.', 'buddyboss-pro' ); ?></p>
                                                </video>
											<?php endif; ?>
											<?php if ( 'M4A' === $recording_file->file_type ) : ?>
                                                <audio controls>
                                                    <source src="<?php echo esc_url( $recording_file->download_url ); ?>"
                                                            type="audio/mp4">
                                                    <p><?php esc_html_e( 'Your browser does not support HTML5 audio.', 'buddyboss-pro' ); ?></p>
                                                </audio>
											<?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
						<?php endif; ?>
                    </div>
					<?php
				}

				?>
            </div>
			<?php
		}
		?>
    </div>
</div>
