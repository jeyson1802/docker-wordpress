<?php
/**
 * BuddyBoss Zoom AJAX.
 *
 * @package BuddyBoss\Zoom\Ajax
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Zoom_Ajax' ) ) {
	/**
	 * Class BP_Zoom_Ajax
	 */
	class BP_Zoom_Ajax {
		/**
		 * Your __construct() method will contain configuration options for
		 * your extension.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
		    if ( ! bbp_pro_is_license_valid() ) {
		        return;
            }
			$this->setup_filters();
			$this->setup_actions();
		}

		/**
		 * Setup the group zoom class filters
		 *
		 * @since 1.0.0
		 */
		private function setup_filters() {}

		/**
		 * Setup actions.
		 *
		 * @since 1.0.0
		 */
		public function setup_actions() {
			add_action( 'wp_ajax_zoom_meeting_add', array( $this, 'zoom_meeting_add' ) );
			add_action( 'wp_ajax_zoom_meeting_delete', array( $this, 'zoom_meeting_delete' ) );
			add_action( 'wp_ajax_zoom_meeting_recordings', array( $this, 'zoom_meeting_recordings' ) );
			add_action( 'wp_ajax_zoom_meeting_create_meeting', array( $this, 'zoom_meeting_create_meeting' ) );
			add_action( 'wp_ajax_zoom_meeting_edit_meeting', array( $this, 'zoom_meeting_edit_meeting' ) );
			add_action( 'wp_ajax_zoom_meeting_occurrence_edit', array( $this, 'zoom_meeting_occurrence_edit' ) );
			add_action( 'wp_ajax_zoom_api_check_connection', array( $this, 'check_connection' ) );
			add_action( 'wp_ajax_zoom_meetings_sync', array( $this, 'zoom_meetings_sync' ) );

			add_action( 'wp_ajax_zoom_meeting_invitation', array( $this, 'zoom_meeting_invitation' ) );
			add_action( 'wp_ajax_zoom_meeting_load_more', array( $this, 'zoom_meeting_load_more' ) );
			add_action( 'wp_ajax_zoom_meeting_search', array( $this, 'zoom_meeting_search' ) );
			add_action( 'wp_ajax_zoom_meeting_get_single_meeting', array( $this, 'zoom_meeting_get_single_meeting' ) );
			add_action( 'wp_ajax_nopriv_zoom_api_check_connection', array( $this, 'check_connection' ) );
		}

		/**
		 * Check zoom conference connection.
		 *
		 * @since 1.0.0
		 */
		public function check_connection() {
			$api_key    = ! empty( $_GET['key'] ) ? $_GET['key'] : '';
			$api_secret = ! empty( $_GET['secret'] ) ? $_GET['secret'] : '';
			$email      = ! empty( $_GET['email'] ) ? $_GET['email'] : '';

			if ( empty( $api_key ) || empty( $api_secret ) || empty( $email ) ) {
				wp_send_json_error( array( 'message' => __( 'Please check your API keys and email.', 'buddyboss-pro' ) ) );
            }

			$email = filter_var( $email, FILTER_VALIDATE_EMAIL );

			if ( ! $email ) {
				wp_send_json_error( array( 'message' => __( 'Please check your email.', 'buddyboss-pro' ) ) );
            }

			bp_zoom_conference()->zoom_api_key    = $api_key;
			bp_zoom_conference()->zoom_api_secret = $api_secret;

			$user_info = bp_zoom_conference()->get_user_info( $email );

			if ( 200 === $user_info['code'] && ! empty( $user_info['response'] ) ) {
				wp_send_json_success( array( 'user' => $user_info['response'], 'message' => __( 'Zoom API is connected!', 'buddyboss-pro' ) ) );
			}

			wp_send_json_error( array( 'message' => $user_info['response']->message ) );
		}

		/**
		 * Function handle recurring meeting update.
		 *
		 * @since 1.0.4
		 */
		public function zoom_meeting_occurrence_edit() {
			if ( ! bp_is_post_request() ) {
				wp_send_json_error( array( 'error' => __( 'There was a problem when updating. Please try again.', 'buddyboss-pro' ) ) );
			}

			$wp_nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );

			// Nonce check!
			if ( empty( $wp_nonce ) || ! wp_verify_nonce( $wp_nonce, 'bp_zoom_meeting' ) ) {
				wp_send_json_error( array( 'error' => __( 'There was a problem when updating. Please try again.', 'buddyboss-pro' ) ) );
			}

			if ( ! bp_is_active( 'groups' ) ) {
				wp_send_json_error( array( 'error' => __( 'Groups is not active.', 'buddyboss-pro' ) ) );
			}

			$group_id = filter_input( INPUT_POST, 'bp-zoom-meeting-group-id', FILTER_VALIDATE_INT );

			if ( empty( $group_id ) || ! bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), $group_id ) ) {
				wp_send_json_error( array( 'error' => __( 'You do not have permission to create meeting in this group.', 'buddyboss-pro' ) ) );
			}

			if ( ! bp_zoom_is_group_setup( $group_id ) ) {
                wp_send_json_error( array( 'error' => __( 'This group does not have Zoom enabled. Please check the settings.', 'buddyboss-pro' ) ) );
            }

            $host_email = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-email', true );
            $api_key    = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-key', true );
            $api_secret = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-secret', true );
            $host_type  = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-host-type', true );

            bp_zoom_conference()->zoom_api_key    = ! empty( $api_key ) ? $api_key : '';
            bp_zoom_conference()->zoom_api_secret = ! empty( $api_secret ) ? $api_secret : '';

			// check user host.
			if ( empty( $host_email ) ) {
				wp_send_json_error( array( 'error' => __( 'Please choose API Host in the settings and try again.', 'buddyboss-pro' ) ) );
			}

			$description = filter_input( INPUT_POST, 'bp-zoom-meeting-description', FILTER_SANITIZE_STRING );

			$start_date = filter_input( INPUT_POST, 'bp-zoom-meeting-start-date', FILTER_DEFAULT );

			if ( empty( $start_date ) ) {
				wp_send_json_error( array( 'error' => __( 'Please select the meeting date.', 'buddyboss-pro' ) ) );
			}

			if ( ! preg_match( '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $start_date ) ) {
				wp_send_json_error( array( 'error' => __( 'Please enter valid date as format <strong>yyyy-mm-dd</strong>.', 'buddyboss-pro' ) ) );
			}

			if ( strtotime( $start_date ) < strtotime( wp_date( 'Y-m-d' ) ) ) {
				wp_send_json_error( array( 'error' => __( 'Please change the meeting date to a future date.', 'buddyboss-pro' ) ) );
			}

			$start_time = filter_input( INPUT_POST, 'bp-zoom-meeting-start-time', FILTER_DEFAULT );
			if ( empty( $start_time ) ) {
				$start_time = '12:00';
			} else {
				$explode_start_time = explode( ':', $start_time );
				if ( ! isset( $explode_start_time[0] ) || empty( $explode_start_time[0] ) || '00' === $explode_start_time[0] ) {
					$explode_start_time[0] = '12';
				}
				$start_time = implode( ':', $explode_start_time );
			}

			$duration_hr  = filter_input( INPUT_POST, 'bp-zoom-meeting-duration-hr', FILTER_VALIDATE_INT );
			$duration_min = filter_input( INPUT_POST, 'bp-zoom-meeting-duration-min', FILTER_VALIDATE_INT );

			$duration = (int) ( ( $duration_hr * 60 ) + $duration_min );

			if ( $duration < 15 ) {
				wp_send_json_error( array( 'error' => __( 'Please select the meeting duration to a minimum of 15 minutes.', 'buddyboss-pro' ) ) );
			}

			$start_meridian = filter_input( INPUT_POST, 'bp-zoom-meeting-start-time-meridian', FILTER_SANITIZE_STRING );

			if ( empty( $start_meridian ) ) {
				$start_meridian = gmdate( 'A', strtotime( 'now' ) );
			}

			$id                    = filter_input( INPUT_POST, 'bp-zoom-meeting-id', FILTER_VALIDATE_INT );
			$occurrence_id         = filter_input( INPUT_POST, 'bp-zoom-meeting-zoom-occurrence-id', FILTER_SANITIZE_STRING );
			$meeting_id            = filter_input( INPUT_POST, 'bp-zoom-meeting-zoom-id', FILTER_SANITIZE_STRING );
			$join_before_host      = filter_input( INPUT_POST, 'bp-zoom-meeting-join-before-host', FILTER_VALIDATE_BOOLEAN );
			$host_video            = filter_input( INPUT_POST, 'bp-zoom-meeting-host-video', FILTER_VALIDATE_BOOLEAN );
			$participants_video    = filter_input( INPUT_POST, 'bp-zoom-meeting-participants-video', FILTER_VALIDATE_BOOLEAN );
			$mute_participants     = filter_input( INPUT_POST, 'bp-zoom-meeting-mute-participants', FILTER_VALIDATE_BOOLEAN );
			$waiting_room          = filter_input( INPUT_POST, 'bp-zoom-meeting-waiting-room', FILTER_VALIDATE_BOOLEAN );
			$auto_recording        = filter_input( INPUT_POST, 'bp-zoom-meeting-auto-recording', FILTER_VALIDATE_BOOLEAN );
			$auto_recording_option = filter_input( INPUT_POST, 'bp-zoom-meeting-recording', FILTER_SANITIZE_STRING );

			if ( 1 === (int) $host_type && $auto_recording ) {
				$auto_recording = 'local';
			} else {
				if ( $auto_recording ) {
					$auto_recording = $auto_recording_option;
				} else {
					$auto_recording = 'none';
				}
			}

			$timezone = wp_timezone_string();
			// update parent meeting details.
			$parent_meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting_id );
			if ( ! empty( $parent_meeting->id ) ) {
			    $timezone = $parent_meeting->timezone;
			}

			$start_date_time = gmdate( 'Y-m-d\TH:i:s', strtotime( $start_date . ' ' . $start_time . ' ' . $start_meridian ) );

			$start_date_time_save = new DateTime( $start_date . ' ' . $start_time . ' ' . $start_meridian, new DateTimeZone( $timezone ) );
			$start_date_time_save->setTimeZone( new DateTimeZone( 'UTC' ) );
			$start_date_time_save = $start_date_time_save->format( 'Y-m-d\TH:i:s' );

			$data = array(
				'description'        => $description,
				'start_date'         => $start_date_time,
				'duration'           => $duration,
				'join_before_host'   => $join_before_host,
				'host_video'         => $host_video,
				'participants_video' => $participants_video,
				'mute_participants'  => $mute_participants,
				'waiting_room'       => $waiting_room,
				'auto_recording'     => $auto_recording,
				'meeting_id'         => $meeting_id,
			);

			$zoom_meeting = bp_zoom_conference()->update_meeting_occurrence( $occurrence_id, $data );

			if ( ! empty( $zoom_meeting['code'] ) && in_array( $zoom_meeting['code'], array( 300, 400, 404 ), true ) ) {
				$response_error = array( 'error' => $zoom_meeting['response']->message );

				if ( ! empty( $zoom_meeting['response']->errors ) ) {
					$response_error['errors'] = $zoom_meeting['response']->errors;
				}
				wp_send_json_error( $response_error );
			}

			if ( ! empty( $zoom_meeting['code'] ) && 204 === $zoom_meeting['code'] ) {
				$meeting                     = new BP_Zoom_Meeting( $id );
				$meeting->description        = $data['description'];
				$meeting->start_date         = $start_date_time_save;
				$meeting->duration           = $data['duration'];
				$meeting->join_before_host   = $data['join_before_host'];
				$meeting->host_video         = $data['host_video'];
				$meeting->participants_video = $data['participants_video'];
				$meeting->mute_participants  = $data['mute_participants'];
				$meeting->waiting_room       = $data['waiting_room'];
				$meeting->auto_recording     = $data['auto_recording'];
				$meeting->start_date_utc     = $start_date_time_save;

				if ( ! $meeting->save() ) {
					wp_send_json_error( array( 'error' => __( 'There was an error saving the occurrence.', 'buddyboss-pro' ) ) );
                }

				// update parent meeting details.
				if ( ! empty( $parent_meeting->id ) ) {
					$updated_meeting = bp_zoom_conference()->get_meeting_info( $meeting_id );
					if ( ! empty( $updated_meeting['code'] ) && 200 === $updated_meeting['code'] && ! empty( $updated_meeting['response'] ) ) {
					    bp_zoom_meeting_update_meta( $parent_meeting->id, 'zoom_details', json_encode( $updated_meeting['response'] ) );
					}
                }

				// delete meeting invitation transient.
				if ( ! empty( $meeting_id ) ) {
					delete_transient( 'bp_zoom_meeting_invitation_' . $meeting_id );
				}

                $group_link   = bp_get_group_permalink( groups_get_group( $group_id ) );
                $redirect_url = trailingslashit( $group_link . 'zoom/meetings/' . $id );

				wp_send_json_success(
					array(
						'redirect_url' => $redirect_url,
						'id'           => (int) $id,
						'meeting_id'   => (int) $meeting_id,
					)
				);
			}

			wp_send_json_error( array( 'error' => __( 'There was a problem when updating an occurrence. Please try again.', 'buddyboss-pro' ) ) );
        }

		/**
		 * Zoom meeting add.
		 *
		 * @since 1.0.0
		 */
		public function zoom_meeting_add() {
			if ( ! bp_is_post_request() ) {
				wp_send_json_error( array( 'error' => __( 'There was a problem when adding. Please try again.', 'buddyboss-pro' ) ) );
			}

			$wp_nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );

			// Nonce check!
			if ( empty( $wp_nonce ) || ! wp_verify_nonce( $wp_nonce, 'bp_zoom_meeting' ) ) {
				wp_send_json_error( array( 'error' => __( 'There was a problem when adding. Please try again.', 'buddyboss-pro' ) ) );
			}

			if ( ! bp_is_active( 'groups' ) ) {
				wp_send_json_error( array( 'error' => __( 'Groups is not active.', 'buddyboss-pro' ) ) );
            }

			$group_id = filter_input( INPUT_POST, 'bp-zoom-meeting-group-id', FILTER_VALIDATE_INT );

			if ( empty( $group_id ) || ! bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), $group_id ) ) {
				wp_send_json_error( array( 'error' => __( 'You do not have permission to create meeting in this group.', 'buddyboss-pro' ) ) );
            }

			$host_email   = '';
			$host_type = false;
			if ( ! empty( $group_id ) ) {
				if ( ! bp_zoom_is_group_setup( $group_id ) ) {
					wp_send_json_error( array( 'error' => __( 'This group does not have Zoom enabled. Please check the settings.', 'buddyboss-pro' ) ) );
				}

				$host_email = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-email', true );
				$api_key    = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-key', true );
				$api_secret = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-secret', true );
				$host_type  = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-host-type', true );

				bp_zoom_conference()->zoom_api_key    = ! empty( $api_key ) ? $api_key : '';
				bp_zoom_conference()->zoom_api_secret = ! empty( $api_secret ) ? $api_secret : '';
			}

			// check user host.
			if ( empty( $host_email ) ) {
				wp_send_json_error( array( 'error' => __( 'Please choose API Host in the settings and try again.', 'buddyboss-pro' ) ) );
			}

			$title = filter_input( INPUT_POST, 'bp-zoom-meeting-title', FILTER_SANITIZE_STRING );

			if ( empty( $title ) ) {
				wp_send_json_error( array( 'error' => __( 'Please enter the meeting title.', 'buddyboss-pro' ) ) );
			}

			$description = filter_input( INPUT_POST, 'bp-zoom-meeting-description', FILTER_SANITIZE_STRING );
			$timezone    = filter_input( INPUT_POST, 'bp-zoom-meeting-timezone', FILTER_SANITIZE_STRING );
			$start_date  = filter_input( INPUT_POST, 'bp-zoom-meeting-start-date', FILTER_DEFAULT );

			if ( empty( $start_date ) ) {
				wp_send_json_error( array( 'error' => __( 'Please select the meeting date.', 'buddyboss-pro' ) ) );
			}

			if ( ! preg_match( '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $start_date ) ) {
				wp_send_json_error( array( 'error' => __( 'Please enter valid date as format <strong>yyyy-mm-dd</strong>.', 'buddyboss-pro' ) ) );
			}

			$start_time = filter_input( INPUT_POST, 'bp-zoom-meeting-start-time', FILTER_DEFAULT );
			if ( empty( $start_time ) ) {
				$start_time = '12:00';
			} else {
				$explode_start_time = explode( ':', $start_time );
				if ( ! isset( $explode_start_time[0] ) || empty( $explode_start_time[0] ) || '00' === $explode_start_time[0] ) {
					$explode_start_time[0] = '12';
				}
				$start_time = implode( ':', $explode_start_time );
			}

			$duration_hr  = filter_input( INPUT_POST, 'bp-zoom-meeting-duration-hr', FILTER_VALIDATE_INT );
			$duration_min = filter_input( INPUT_POST, 'bp-zoom-meeting-duration-min', FILTER_VALIDATE_INT );

			$duration = (int) ( ( $duration_hr * 60 ) + $duration_min );

			if ( $duration < 15 ) {
				wp_send_json_error( array( 'error' => __( 'Please select the meeting duration to a minimum of 15 minutes.', 'buddyboss-pro' ) ) );
			}

			$start_meridian = filter_input( INPUT_POST, 'bp-zoom-meeting-start-time-meridian', FILTER_SANITIZE_STRING );

			if ( empty( $start_meridian ) ) {
				$start_meridian = gmdate( 'A', strtotime( 'now' ) );
			}

			$start_date_time = gmdate( 'Y-m-d\TH:i:s', strtotime( $start_date . ' ' . $start_time . ' ' . $start_meridian ) );

			if ( strtotime( $start_date_time ) < strtotime( wp_date( 'Y-m-d\TH:i:s', strtotime( 'now' ), new DateTimeZone( $timezone ) ) ) ) {
				wp_send_json_error( array( 'error' => __( 'Please change the meeting date to a future date.', 'buddyboss-pro' ) ) );
			}

			$password = filter_input( INPUT_POST, 'bp-zoom-meeting-password', FILTER_SANITIZE_STRING );

			$id                     = filter_input( INPUT_POST, 'bp-zoom-meeting-id', FILTER_VALIDATE_INT );
			$meeting_id             = filter_input( INPUT_POST, 'bp-zoom-meeting-zoom-id', FILTER_SANITIZE_STRING );
			$registration           = filter_input( INPUT_POST, 'bp-zoom-meeting-registration', FILTER_VALIDATE_BOOLEAN );
			$registration_type      = filter_input( INPUT_POST, 'bp-zoom-meeting-registration-type', FILTER_VALIDATE_INT );
			$join_before_host       = filter_input( INPUT_POST, 'bp-zoom-meeting-join-before-host', FILTER_VALIDATE_BOOLEAN );
			$host_video             = filter_input( INPUT_POST, 'bp-zoom-meeting-host-video', FILTER_VALIDATE_BOOLEAN );
			$participants_video     = filter_input( INPUT_POST, 'bp-zoom-meeting-participants-video', FILTER_VALIDATE_BOOLEAN );
			$mute_participants      = filter_input( INPUT_POST, 'bp-zoom-meeting-mute-participants', FILTER_VALIDATE_BOOLEAN );
			$waiting_room           = filter_input( INPUT_POST, 'bp-zoom-meeting-waiting-room', FILTER_VALIDATE_BOOLEAN );
			$meeting_authentication = filter_input( INPUT_POST, 'bp-zoom-meeting-authentication', FILTER_VALIDATE_BOOLEAN );
			$auto_recording         = filter_input( INPUT_POST, 'bp-zoom-meeting-auto-recording', FILTER_VALIDATE_BOOLEAN );
			$alternative_host_ids   = filter_input( INPUT_POST, 'bp-zoom-meeting-alt-host-ids', FILTER_SANITIZE_STRING );
			$auto_recording_option  = filter_input( INPUT_POST, 'bp-zoom-meeting-recording', FILTER_SANITIZE_STRING );
			$recurring              = filter_input( INPUT_POST, 'bp-zoom-meeting-recurring', FILTER_VALIDATE_BOOLEAN );
			$recurrence             = filter_input( INPUT_POST, 'bp-zoom-meeting-recurrence', FILTER_VALIDATE_INT );

			$alternative_host_ids = str_replace( ' ', '', $alternative_host_ids );
			$alternative_host_ids = explode( ',', $alternative_host_ids );

			if ( 1 === (int) $host_type && $auto_recording ) {
				$auto_recording = 'local';
            } else {
				if ( $auto_recording ) {
					$auto_recording = $auto_recording_option;
				} else {
					$auto_recording = 'none';
				}
            }

			$type = 2;
			if ( $recurring ) {
				if ( 1 === $recurrence || 2 === $recurrence || 3 === $recurrence ) {
					$type = 8;
				}
				if ( -1 === $recurrence ) {
					$type = 3;
				}
			}

			$data = array(
				'title'                  => $title,
				'description'            => $description,
				'type'                   => $type,
				'host_id'                => $host_email,
				'user_id'                => bp_loggedin_user_id(),
				'start_date'             => $start_date_time,
				'timezone'               => $timezone,
				'duration'               => $duration,
				'password'               => $password,
				'registration'           => $registration,
				'join_before_host'       => $join_before_host,
				'host_video'             => $host_video,
				'participants_video'     => $participants_video,
				'mute_participants'      => $mute_participants,
				'waiting_room'           => $waiting_room,
				'meeting_authentication' => $meeting_authentication,
				'auto_recording'         => $auto_recording,
				'alternative_host_ids'   => $alternative_host_ids,
				'recurring'              => $recurring,
			);

			$recurrence_obj = array();
			if ( $recurring && 8 === $type ) {
				$recurrence_obj['type'] = $recurrence;
				$repeat_interval = filter_input( INPUT_POST, 'bp-zoom-meeting-repeat-interval', FILTER_VALIDATE_INT );
				$end_time_select = filter_input( INPUT_POST, 'bp-zoom-meeting-end-time-select', FILTER_SANITIZE_STRING );

				if ( 1 === $recurrence ) {
					if ( 90 < $repeat_interval ) {
						$repeat_interval = 90;
					}
				} else if ( 2 === $recurrence ) {
					if ( 12 < $repeat_interval ) {
						$repeat_interval = 12;
					}

					$weekly_days = filter_input( INPUT_POST, 'bp-zoom-meeting-weekly-days', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
					$recurrence_obj['weekly_days'] = implode( ',', $weekly_days );
				} else if ( 3 === $recurrence ) {
					if ( 3 < $repeat_interval ) {
						$repeat_interval = 3;
					}
					$monthly_occurs_on = filter_input( INPUT_POST, 'bp-zoom-meeting-monthly-occurs-on', FILTER_SANITIZE_STRING );

					if ( 'day' === $monthly_occurs_on ) {
						$monthly_day = filter_input( INPUT_POST, 'bp-zoom-meeting-monthly-day', FILTER_VALIDATE_INT );
						$recurrence_obj['monthly_day'] = $monthly_day;
					} else if ( 'week' === $monthly_occurs_on ) {
						$monthly_week_day = filter_input( INPUT_POST, 'bp-zoom-meeting-monthly-week-day', FILTER_VALIDATE_INT );
						$monthly_week = filter_input( INPUT_POST, 'bp-zoom-meeting-monthly-week', FILTER_VALIDATE_INT );
						$recurrence_obj['monthly_week_day'] = $monthly_week_day;
						$recurrence_obj['monthly_week'] = $monthly_week;
					}
				}

				if ( 'date' === $end_time_select ) {
					$end_date_time = filter_input( INPUT_POST, 'bp-zoom-meeting-end-date-time', FILTER_SANITIZE_STRING );
					$end_date_time = new DateTime( $end_date_time . ' ' . $start_time . ' ' . $start_meridian, new DateTimeZone( $timezone ) );
					$end_date_time->setTimezone( new DateTimeZone( 'UTC' ) );
					$recurrence_obj['end_date_time'] = $end_date_time->format( 'Y-m-d\TH:i:s\Z' );
				} else {
					$end_times = filter_input( INPUT_POST, 'bp-zoom-meeting-end-times', FILTER_VALIDATE_INT );

					if ( 50 < $end_times ) {
						$end_times = 50;
					}
					$recurrence_obj['end_times'] = $end_times;
				}

				$recurrence_obj['repeat_interval'] = $repeat_interval;

				$data['recurrence'] = $recurrence_obj;

				// add registration type if meeting is recurring of type 1,2,3
				$data['registration_type'] = $registration_type;
			}

			if ( ! empty( $meeting_id ) ) {
				$data['meeting_id'] = $meeting_id;
				$zoom_meeting       = bp_zoom_conference()->update_meeting( $data );
			} else {
				$zoom_meeting = bp_zoom_conference()->create_meeting( $data );
			}

			if ( ! empty( $zoom_meeting['code'] ) && in_array( $zoom_meeting['code'], array( 300, 400, 404 ), true ) ) {
				$response_error = array( 'error' => $zoom_meeting['response']->message );

				if ( ! empty( $zoom_meeting['response']->errors ) ) {
					$response_error['errors'] = $zoom_meeting['response']->errors;
				}
				wp_send_json_error( $response_error );
			}

			if ( ! empty( $zoom_meeting['code'] ) && in_array( $zoom_meeting['code'], array( 201, 204 ), true ) ) {
			    if ( ! empty( $zoom_meeting['response'] ) ) {
					$data['meeting_id']     = $zoom_meeting['response']->id;
					$meeting_id             = $zoom_meeting['response']->id;
				}

				if ( ! empty( $id ) ) {
					$data['id'] = $id;
				}

				if ( ! empty( $meeting_id ) ) {
					$data['meeting_id'] = $meeting_id;
				}

				if ( ! empty( $group_id ) ) {
					$data['group_id'] = $group_id;
				}

				if ( ! empty( $alternative_host_ids ) ) {
					$data['alternative_host_ids'] = implode( ',', $alternative_host_ids );
				}

				if ( 204 === $zoom_meeting['code'] && ! empty( $meeting_id ) ) {
					$updated_meeting = bp_zoom_conference()->get_meeting_info( $meeting_id );
					if ( ! empty( $updated_meeting['code'] ) && 200 === $updated_meeting['code'] && ! empty( $updated_meeting['response'] ) ) {
						$zoom_meeting = $updated_meeting;
					}
				}

				if ( $recurring && 8 === $type && ! empty( $zoom_meeting['response']->occurrences ) ) {
					$data['start_date_utc'] = $zoom_meeting['response']->created_at;
				} else {
					$data['start_date_utc'] = ! empty( $zoom_meeting['response']->start_time ) ? $zoom_meeting['response']->start_time : wp_date( 'Y-m-d\TH:i:s', strtotime( $start_date_time ), new DateTimeZone( 'UTC' ) );;
				}

				if ( empty( $data['password'] ) && ! empty( $zoom_meeting['response']->password ) ) {
				    $data['password'] = $zoom_meeting['response']->password;
                }

				$id = bp_zoom_meeting_add( $data );

				if ( ! $id ) {
					wp_send_json_error( array( 'error' => __( 'There was an error saving the meeting.', 'buddyboss-pro' ) ) );
				}

				if ( $recurring && 8 === $type && ! empty( $zoom_meeting['response']->occurrences ) ) {

					if ( ! empty( $meeting_id ) ) {
					    // delete current occurrences and store new ones from zoom api.
					    bp_zoom_meeting_delete( array( 'parent' => $meeting_id ) );
				    }

					unset( $data['id'] );
					$occurrence_add = 0;
					$occurrence_id = false;
					foreach ( $zoom_meeting['response']->occurrences as $meeting_occurrence ) {

					    if ( 'deleted' === $meeting_occurrence->status ) {
					        continue;
                        }

						$meeting_occurrence_info = bp_zoom_conference()->get_meeting_info( $zoom_meeting['response']->id, $meeting_occurrence->occurrence_id );
						if ( 200 === $meeting_occurrence_info['code'] && ! empty( $meeting_occurrence_info['response'] ) ) {
							$data['title']                  = $meeting_occurrence_info['response']->topic;
							$data['type']                   = $meeting_occurrence_info['response']->type;
							$data['description']            = $meeting_occurrence_info['response']->agenda;
							$data['meeting_authentication'] = $meeting_occurrence_info['response']->settings->meeting_authentication;
							$data['join_before_host']       = $meeting_occurrence_info['response']->settings->join_before_host;
							$data['host_video']             = $meeting_occurrence_info['response']->settings->host_video;
							$data['participants_video']     = $meeting_occurrence_info['response']->settings->participant_video;
							$data['mute_participants']      = $meeting_occurrence_info['response']->settings->mute_upon_entry;
							$data['waiting_room']           = $meeting_occurrence_info['response']->settings->waiting_room;
							$data['auto_recording']         = $meeting_occurrence_info['response']->settings->auto_recording;
							$data['alternative_host_ids']   = $meeting_occurrence_info['response']->settings->alternative_hosts;
						}

						$data['hide_sitewide']  = false;
						$data['meeting_id']     = $meeting_occurrence->occurrence_id;
						$data['duration']       = $meeting_occurrence->duration;
						$data['parent']         = $zoom_meeting['response']->id;
						$data['zoom_type']      = 'meeting_occurrence';
						$data['start_date']     = $meeting_occurrence->start_time;
						$data['start_date_utc'] = $meeting_occurrence->start_time;
						$data['recurring']      = false;
						$occurrence_added_id = bp_zoom_meeting_add( $data );

						if ( 0 === $occurrence_add ) {
							$occurrence_id = $occurrence_added_id;
                        }

						$occurrence_add ++;
					}
				}

				if ( ! empty( $zoom_meeting['response'] ) ) {
					bp_zoom_meeting_update_meta( $id, 'zoom_details', json_encode( $zoom_meeting['response'] ) );
					bp_zoom_meeting_update_meta( $id, 'zoom_join_url', $zoom_meeting['response']->join_url );
					bp_zoom_meeting_update_meta( $id, 'zoom_start_url', $zoom_meeting['response']->start_url );
					if ( isset( $zoom_meeting['response']->registration_url ) && ! empty( $zoom_meeting['response']->registration_url ) ) {
						bp_zoom_meeting_update_meta( $id, 'zoom_registration_url', $zoom_meeting['response']->registration_url );
					} else {
						bp_zoom_meeting_delete_meta( $id, 'zoom_registration_url' );
					}
				}

				if ( $recurring && 8 === $type ) {
					bp_zoom_meeting_update_meta( $id, 'zoom_registration_type', $registration_type );
				} else {
					bp_zoom_meeting_delete_meta( $id, 'zoom_registration_type' );
                }

				// delete meeting invitation transient.
				if ( ! empty( $meeting_id ) ) {
					delete_transient( 'bp_zoom_meeting_invitation_' . $meeting_id );
				}

				if ( bp_is_active( 'groups' ) ) {
					$group_link   = bp_get_group_permalink( groups_get_group( $group_id ) );
					$redirect_url = trailingslashit( $group_link . 'zoom/meetings/' . $id );

					// if recurring meeting update then reload the page.
					if ( $recurring && 8 === $type && ! empty( $occurrence_id ) ) {
						$redirect_url = trailingslashit( $group_link . 'zoom/meetings/' . $occurrence_id );
                    }
				} else {
					$redirect_url = '';
				}

				wp_send_json_success(
					array(
						'redirect_url' => $redirect_url,
						'id'           => (int) $id,
						'meeting_id'   => (int) $meeting_id,
					)
				);
			}

			wp_send_json_error( array( 'error' => __( 'There was a problem when adding. Please try again.', 'buddyboss-pro' ) ) );
		}

		/**
		 * Zoom meeting delete
		 *
		 * @since 1.0.0
		 */
		public function zoom_meeting_delete() {
			if ( ! bp_is_post_request() ) {
				return;
			}

			// Nonce check!
			if ( empty( filter_input( INPUT_POST, '_wpnonce' ) ) || ! wp_verify_nonce( filter_input( INPUT_POST, '_wpnonce' ), 'bp_zoom_meeting_delete' ) ) {
				return;
			}

			$id            = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
			$meeting_id    = filter_input( INPUT_POST, 'meeting_id', FILTER_SANITIZE_STRING );
			$occurrence_id = filter_input( INPUT_POST, 'occurrence_id', FILTER_SANITIZE_STRING );

			if ( empty( $id ) || empty( $meeting_id ) ) {
			    return;
            }

			$meeting = new BP_Zoom_Meeting( $id );

			if ( empty( $meeting->id ) ) {
				wp_send_json_success(
					array(
						'deleted' => true,
						'is_past' => false,
					)
				);
			}

			$is_past = $meeting->is_past;

			// empty occurrence then parent meeting edit load.
			if ( empty( $occurrence_id ) && ! empty( $meeting_id ) ) {
				if ( ! empty( $meeting->zoom_type ) && 'meeting_occurrence' === $meeting->zoom_type ) {
					$parent_meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting_id );
					if ( ! empty( $parent_meeting->id ) ) {
						$id = $parent_meeting->id;
					}
				}
			}

			$meeting = new BP_Zoom_Meeting( $id );

			if ( empty( $meeting->group_id ) ) {
				wp_send_json_error( array( 'error' => __( 'You do not have permission to delete a meeting in this group.', 'buddyboss-pro' ) ) );
			}

            if ( ! bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), $meeting->group_id ) ) {
                wp_send_json_error( array( 'error' => __( 'You do not have permission to delete a meeting in this group.', 'buddyboss-pro' ) ) );
            }

            $api_key    = groups_get_groupmeta( $meeting->group_id, 'bp-group-zoom-api-key', true );
            $api_secret = groups_get_groupmeta( $meeting->group_id, 'bp-group-zoom-api-secret', true );

            bp_zoom_conference()->zoom_api_key    = ! empty( $api_key ) ? $api_key : '';
            bp_zoom_conference()->zoom_api_secret = ! empty( $api_secret ) ? $api_secret : '';

			if ( empty( $occurrence_id ) ) {
				$meeting_deleted = bp_zoom_conference()->delete_meeting( $meeting_id );
            } else {
				$meeting_deleted = bp_zoom_conference()->delete_meeting( $meeting_id, $occurrence_id );
            }

			if ( isset( $meeting_deleted['code'] ) && 204 === $meeting_deleted['code'] && bp_zoom_meeting_delete( array( 'id' => $id ) ) ) {

			    if ( $meeting->recurring && empty( $occurrence_id ) ) {
				    bp_zoom_meeting_delete( array( 'parent' => $meeting_id ) );
                }

				// update parent meeting details.
				if ( ! empty( $meeting_id ) ) {
				    $parent_meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting_id );
				    if ( ! empty( $parent_meeting->id ) ) {
					    $updated_meeting = bp_zoom_conference()->get_meeting_info( $meeting_id );
					    if ( ! empty( $updated_meeting['code'] ) && 200 === $updated_meeting['code'] && ! empty( $updated_meeting['response'] ) ) {
						    bp_zoom_meeting_update_meta( $parent_meeting->id, 'zoom_details', json_encode( $updated_meeting['response'] ) );
					    }
				    }
				}

				// delete meeting invitation transient.
				if ( ! empty( $meeting_id ) ) {
					delete_transient( 'bp_zoom_meeting_invitation_' . $meeting_id );
				}

				wp_send_json_success(
					array(
						'deleted' => true,
						'is_past' => $is_past,
					)
				);
			}

			if ( isset( $meeting_deleted['code'] ) && in_array( $meeting_deleted['code'], array( 400, 404 ) ) ) {
				$response_error = array( 'error' => $meeting_deleted['response']->message );

				if ( ! empty( $meeting_deleted['response']->errors ) ) {
					$response_error['errors'] = $meeting_deleted['response']->errors;
				}
				wp_send_json_error( $response_error );
            }

			wp_send_json_success(
				array(
					'deleted' => $meeting_deleted,
					'is_past' => $is_past,
				)
			);
		}

		/**
		 * Zoom meeting invitation.
		 *
		 * @since 1.0.0
		 */
		public function zoom_meeting_invitation() {
			$meeting_id = filter_input( INPUT_GET, 'meeting_id', FILTER_SANITIZE_STRING );

			if ( empty( $meeting_id ) ) {
				wp_send_json_error( array( 'error' => __( 'Please provide ID of the meeting.', 'buddyboss-pro' ) ) );
			}

			$meetings = bp_zoom_meeting_get_specific( array( 'meeting_id' => $meeting_id ) );

			if ( ! empty( $meetings['meetings'] ) ) {
			    $meeting = $meetings['meetings'][0];

				if ( ! empty( $meeting->group_id ) ) {
					$api_key    = groups_get_groupmeta( $meeting->group_id, 'bp-group-zoom-api-key', true );
					$api_secret = groups_get_groupmeta( $meeting->group_id, 'bp-group-zoom-api-secret', true );

					bp_zoom_conference()->zoom_api_key    = ! empty( $api_key ) ? $api_key : '';
					bp_zoom_conference()->zoom_api_secret = ! empty( $api_secret ) ? $api_secret : '';
				}
			}

			$invitation_response = bp_zoom_conference()->meeting_invitation( $meeting_id );

			if ( 200 === $invitation_response['code'] && ! empty( $invitation_response['response'] ) ) {
			    $invitation = $invitation_response['response']->invitation;

			    wp_send_json_success( array( 'invitation' => $invitation ) );
			}

			wp_send_json_error( array( 'error' => __( 'There was a issue in fetching invitation.', 'buddyboss-pro' ) ) );
		}

		/**
		 * Zoom meeting load more list
		 *
		 * @since 1.0.0
		 */
		public function zoom_meeting_load_more() {
			$recorded     = filter_input( INPUT_GET, 'recorded', FILTER_VALIDATE_BOOLEAN );
			$search_terms = filter_input( INPUT_GET, 'search_terms', FILTER_SANITIZE_STRING );
			$past         = filter_input( INPUT_GET, 'past', FILTER_VALIDATE_INT );
			$page         = filter_input( INPUT_GET, 'page', FILTER_VALIDATE_INT );

			$args = array(
				'recorded'     => $recorded,
				'search_terms' => $search_terms,
				'page'         => $page,
			);

			if ( ! empty( $past ) ) {
				$args['from']  = wp_date( 'Y-m-d H:i:s', null, new DateTimeZone( 'UTC' ) );
				$args['since'] = false;
				$args['sort']  = 'DESC';
			} else {
				$args['sort']  = 'ASC';
				$args['since'] = wp_date( 'Y-m-d H:i:s', null, new DateTimeZone( 'UTC' ) );
				$args['from']  = false;
			}

			ob_start();
			if ( bp_has_zoom_meetings( $args ) ) {
				while ( bp_zoom_meeting() ) {
					bp_the_zoom_meeting();

					bp_get_template_part( 'zoom/loop-meeting' );
				}
				if ( bp_zoom_meeting_has_more_items() ) {
					?>
					<div class="load-more">
						<a class="button full outline" href="<?php bp_zoom_meeting_load_more_link(); ?>">
							<?php esc_html_e( 'Load More', 'buddyboss-pro' ); ?>
						</a>
					</div>
					<?php
				}
			}
			$response = ob_get_clean();
			wp_send_json_success( array( 'contents' => $response ) );
		}

		/**
		 * Zoom meeting load more list
		 *
		 * @since 1.0.0
		 */
		public function zoom_meeting_search() {
			ob_start();

			$recorded     = filter_input( INPUT_GET, 'recorded', FILTER_VALIDATE_BOOLEAN );
			$search_terms = filter_input( INPUT_GET, 'search_terms', FILTER_SANITIZE_STRING );
			$page         = filter_input( INPUT_GET, 'page', FILTER_VALIDATE_INT );
			$past         = filter_input( INPUT_GET, 'past', FILTER_VALIDATE_INT );

			$args = array(
				'recorded'     => $recorded,
				'search_terms' => $search_terms,
				'page'         => $page,
			);

			if ( ! empty( $past ) ) {
				$args['from']  = wp_date( 'Y-m-d H:i:s', null, new DateTimeZone( 'UTC' ) );
				$args['since'] = false;
				$args['sort']  = 'DESC';
			}

			if ( bp_has_zoom_meetings( $args ) ) {
				while ( bp_zoom_meeting() ) {
					bp_the_zoom_meeting();

					bp_get_template_part( 'zoom/loop-meeting' );
				}
				if ( bp_zoom_meeting_has_more_items() ) {
					?>
					<div class="load-more">
						<a class="button full outline" href="<?php bp_zoom_meeting_load_more_link(); ?>">
							<?php esc_html_e( 'Load More', 'buddyboss-pro' ); ?>
						</a>
					</div>
					<?php
				}
			} else {
				bp_nouveau_user_feedback( 'meetings-loop-none' );
			}
			$response = ob_get_clean();
			wp_send_json_success( array( 'contents' => $response ) );
		}

		/**
		 * Zoom single meeting load.
		 *
		 * @since 1.0.0
		 */
		public function zoom_meeting_get_single_meeting() {
			$id         = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );

			if ( empty( $id ) ) {
			    wp_send_json_error( array( 'error' => __( 'Please provide meeting ID.', 'buddyboss-pro' ) ) );
			}

			ob_start();
			if ( bp_has_zoom_meetings( array( 'include' => $id ) ) ) {
				while ( bp_zoom_meeting() ) {
					bp_the_zoom_meeting();

					bp_get_template_part( 'zoom/single-meeting-item' );
				}
			}
			$response = ob_get_clean();
			wp_send_json_success( array( 'contents' => $response ) );
		}

		/**
		 * Zoom create meeting form load.
		 *
		 * @since 1.0.0
		 */
		public function zoom_meeting_create_meeting() {
			ob_start();

				bp_get_template_part( 'zoom/create-meeting' );

			$response = ob_get_clean();
			wp_send_json_success( array( 'contents' => $response ) );
		}

		/**
		 * Zoom edit meeting form load.
		 *
		 * @since 1.0.0
		 */
		public function zoom_meeting_edit_meeting() {
			$id            = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );
			$meeting_id    = filter_input( INPUT_GET, 'meeting_id', FILTER_SANITIZE_STRING );
			$occurrence_id = filter_input( INPUT_GET, 'occurrence_id', FILTER_SANITIZE_STRING );

			if ( empty( $id ) ) {
				wp_send_json_error( array( 'error' => __( 'Please provide meeting ID.', 'buddyboss-pro' ) ) );
			}

			// empty occurrence then parent meeting edit load.
			if ( empty( $occurrence_id ) && ! empty( $meeting_id ) ) {
			    $meeting_obj = new BP_Zoom_Meeting( $id );
			    if ( ! empty( $meeting_obj->zoom_type ) && 'meeting_occurrence' === $meeting_obj->zoom_type ) {
				    $meeting = bp_zoom_meeting_get( array( 'meeting_id' => $meeting_id ) );
				    if ( ! empty( $meeting['meetings'][0] ) ) {
					    $id = $meeting['meetings'][0]->id;
				    }
			    }
			}

			ob_start();
			if ( bp_has_zoom_meetings( array( 'include' => $id ) ) ) {
				while ( bp_zoom_meeting() ) {
					bp_the_zoom_meeting();

					bp_get_template_part( 'zoom/edit-meeting' );
				}
			}
			$response = ob_get_clean();
			wp_send_json_success( array( 'contents' => $response ) );
		}

		/**
		 * Zoom meetings sync.
         *
         * @since 1.0.0
		 */
		public function zoom_meetings_sync() {
			global $wpdb;
			$bp = buddypress();

			$offset   = filter_input( INPUT_POST, 'offset', FILTER_VALIDATE_INT );
			$group_id = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );

			$api_key    = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-key', true );
			$api_secret = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-secret', true );

			bp_zoom_conference()->zoom_api_key    = ! empty( $api_key ) ? $api_key : '';
			bp_zoom_conference()->zoom_api_secret = ! empty( $api_secret ) ? $api_secret : '';

			$zoom_meeting_query = "SELECT id FROM {$bp->table_prefix}bp_zoom_meetings WHERE group_id = {$group_id} AND hide_sitewide = 0 AND zoom_type = 'meeting' LIMIT 10 OFFSET {$offset}";
			$meetings           = $wpdb->get_col( $zoom_meeting_query );

			if ( ! empty( $meetings ) ) {
				foreach ( $meetings as $meeting_id ) {
					$meeting = new BP_Zoom_Meeting( $meeting_id );

					$meeting_info = bp_zoom_conference()->get_meeting_info( $meeting->meeting_id );

					if ( 200 === $meeting_info['code'] && ! empty( $meeting_info['response'] ) ) {

						$object = json_decode( json_encode( $meeting_info['response'] ), true );

						$zoom_meeting_id = $meeting->meeting_id;

						if ( isset( $object['topic'] ) ) {
							$meeting->title = $object['topic'];
						}

						if ( isset( $object['timezone'] ) ) {
							$meeting->timezone = $object['timezone'];
						}

						if ( isset( $object['start_time'] ) ) {
							$meeting->start_date_utc = $object['start_time'];
							$meeting->start_date     = wp_date( 'Y-m-d\TH:i:s', strtotime( $meeting->start_date_utc ), new DateTimeZone( $meeting->timezone ) );
						}

						if ( isset( $object['duration'] ) ) {
							$meeting->duration = (int) $object['duration'];
						}

						if ( isset( $object['agenda'] ) ) {
							$meeting->description = $object['agenda'];
						}

						bp_zoom_meeting_update_meta( $meeting->id, 'zoom_details', json_encode( $meeting_info['response'] ) );

						if ( isset( $object['start_url'] ) ) {
							bp_zoom_meeting_update_meta( $meeting->id, 'zoom_start_url', $object['start_url'] );
						}

						if ( isset( $object['join_url'] ) ) {
							bp_zoom_meeting_update_meta( $meeting->id, 'zoom_join_url', $object['join_url'] );
						}

						delete_transient( 'bp_zoom_meeting_invitation_' . $zoom_meeting_id );

						if ( isset( $object['password'] ) ) {
							$meeting->password = $object['password'];
						}

						if ( isset( $object['settings'] ) ) {
							$settings = $object['settings'];

							if ( isset( $settings['host_video'] ) ) {
								$meeting->host_video = (bool) $settings['host_video'];
							}

							if ( isset( $settings['participant_video'] ) ) {
								$meeting->participants_video = (bool) $settings['participant_video'];
							}

							if ( isset( $settings['join_before_host'] ) ) {
								$meeting->join_before_host = (bool) $settings['join_before_host'];
							}

							if ( isset( $settings['mute_upon_entry'] ) ) {
								$meeting->mute_participants = (bool) $settings['mute_upon_entry'];
							}

							if ( isset( $settings['approval_type'] ) ) {
								$approval_type = (int) $settings['approval_type'];

								if ( in_array( $approval_type, array(
										0,
										1
									) ) && isset( $object['registration_url'] ) && ! empty( $object['registration_url'] ) ) {
									bp_zoom_meeting_update_meta( $meeting->id, 'zoom_registration_url', $object['registration_url'] );
								} else {
									bp_zoom_meeting_delete_meta( $meeting->id, 'zoom_registration_url' );
								}
							}

							if ( 8 === $object['type'] && isset( $settings['registration_type'] ) ) {
								bp_zoom_meeting_update_meta( $meeting->id, 'zoom_registration_type', $settings['registration_type'] );
							} else {
								bp_zoom_meeting_delete_meta( $meeting->id, 'zoom_registration_type' );
							}

							if ( isset( $settings['auto_recording'] ) ) {
								$meeting->auto_recording = $settings['auto_recording'];
							}

							if ( isset( $settings['alternative_hosts'] ) ) {
								$meeting->alternative_host_ids = $settings['alternative_hosts'];
							}

							if ( isset( $settings['waiting_room'] ) ) {
								$meeting->waiting_room = (bool) $settings['waiting_room'];
							}

							if ( isset( $settings['meeting_authentication'] ) ) {
								$meeting->meeting_authentication = (bool) $settings['meeting_authentication'];
							}
						}

						// if recordings enabled, then sync the recordings.
						if ( bp_zoom_is_zoom_recordings_enabled() ) {
							$instances = bp_zoom_conference()->meeting_instances( $zoom_meeting_id );
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
														'meeting_id'   => $zoom_meeting_id,
														'uuid'         => $uuid_recordings_response->uuid,
													) ) ) ) {
													$add_args = array(
														'recording_id' => $uuid_recordings_response_recording_file->id,
														'meeting_id'   => $zoom_meeting_id,
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

							    if ( ! $recordings || ( ! empty( $instances['code'] ) && 200 === $instances['code'] && ( empty( $instances['response']->meetings ) || $instances['response']->meetings ) ) ) {
									$uuid_recordings_response = bp_zoom_conference()->recordings_by_meeting( $meeting->meeting_id );
									//$meeting_recording_count  = count( bp_zoom_recording_get( array(), array( 'meeting_id' => $meeting->meeting_id ) ) );
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
													'meeting_id'   => $zoom_meeting_id,
													'uuid'         => $uuid_recordings_response->uuid,
												) ) ) ) {

												$add_args = array(
													'recording_id' => $uuid_recordings_response_recording_file->id,
													'meeting_id'   => $zoom_meeting_id,
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

							$recordings_count = bp_zoom_recording_get( array(), array(
								'meeting_id' => $zoom_meeting_id,
							) );

							bp_zoom_meeting_update_meta( $meeting_id, 'zoom_recording_count', count( $recordings_count ) );
						}

						if ( isset( $object['type'] ) ) {
						    $meeting->type = $object['type'];

						    if ( 8 === $object['type'] ) {
							    bp_zoom_meeting_delete( array( 'parent' => $meeting->meeting_id ) );

							    $data = array(
								    'title'                  => $meeting->title,
								    'type'                   => $meeting->type,
								    'description'            => $meeting->description,
								    'group_id'               => $meeting->group_id,
								    'user_id'                => $meeting->user_id,
								    'host_id'                => $meeting->host_id,
								    'timezone'               => $meeting->timezone,
								    'meeting_authentication' => $meeting->meeting_authentication,
								    'password'               => $meeting->password,
								    'join_before_host'       => $meeting->join_before_host,
								    'host_video'             => $meeting->host_video,
								    'participants_video'     => $meeting->participants_video,
								    'mute_participants'      => $meeting->mute_participants,
								    'waiting_room'           => $meeting->waiting_room,
								    'auto_recording'         => $meeting->auto_recording,
								    'alternative_host_ids'   => $meeting->alternative_host_ids,
							    );

							    if ( ! empty( $meeting_info['response']->occurrences ) ) {
							        $occurrence_counter = 0;
								    foreach ( $meeting_info['response']->occurrences as $meeting_occurrence ) {

								        if ( 'deleted' === $meeting_occurrence->status ) {
								            continue;
								        }

								        if ( 0 === $occurrence_counter ) {
									        $meeting->start_date     = wp_date( 'Y-m-d\TH:i:s', strtotime( $meeting_occurrence->start_time ), new DateTimeZone( $meeting->timezone ) );
								        }

									    $meeting_occurrence_info = bp_zoom_conference()->get_meeting_info( $meeting->meeting_id, $meeting_occurrence->occurrence_id );
									    if ( 200 === $meeting_occurrence_info['code'] && ! empty( $meeting_occurrence_info['response'] ) ) {
										    $data['title']                  = $meeting_occurrence_info['response']->topic;
										    $data['type']                   = $meeting_occurrence_info['response']->type;
										    $data['description']            = $meeting_occurrence_info['response']->agenda;
										    $data['meeting_authentication'] = $meeting_occurrence_info['response']->settings->meeting_authentication;
										    $data['join_before_host']       = $meeting_occurrence_info['response']->settings->join_before_host;
										    $data['host_video']             = $meeting_occurrence_info['response']->settings->host_video;
										    $data['participants_video']     = $meeting_occurrence_info['response']->settings->participant_video;
										    $data['mute_participants']      = $meeting_occurrence_info['response']->settings->mute_upon_entry;
										    $data['waiting_room']           = $meeting_occurrence_info['response']->settings->waiting_room;
										    $data['auto_recording']         = $meeting_occurrence_info['response']->settings->auto_recording;
										    $data['alternative_host_ids']   = $meeting_occurrence_info['response']->settings->alternative_hosts;
									    }

									    $data['hide_sitewide']  = false;
									    $data['meeting_id']     = $meeting_occurrence->occurrence_id;
									    $data['duration']       = $meeting_occurrence->duration;
									    $data['parent']         = $meeting->meeting_id;
									    $data['zoom_type']      = 'meeting_occurrence';
									    $data['start_date']     = $meeting_occurrence->start_time;
									    $data['start_date_utc'] = $meeting_occurrence->start_time;
									    $data['recurring']      = false;
									    bp_zoom_meeting_add( $data );

									    $occurrence_counter ++;
								    }
							    }
						    }
						}

						// save the meeting.
						$meeting->save();
					} else if ( 404 === $meeting_info['code'] && ! empty( $meeting_info['response'] ) && isset( $meeting_info['response']->code ) && 3001 === $meeting_info['response']->code ) {
						bp_zoom_meeting_delete( array( 'id' => $meeting_id ) );
						bp_zoom_meeting_delete( array( 'parent' => $meeting_id ) );
						bp_zoom_recording_delete( array( 'meeting_id' => $meeting->meeting_id ) );
					}

					$offset ++;
				}

				$records_updated = sprintf( __( '%s meetings updated successfully.', 'buddyboss-pro' ), number_format_i18n( $offset ) );

				wp_send_json_success(
					array(
						'status'  => 'running',
						'offset'  => $offset,
						'records' => $records_updated
					)
				);

			} else {
				$group_link   = bp_get_group_permalink( groups_get_group( $group_id ) );
				$redirect_url = trailingslashit( $group_link . 'zoom/?sync_meeting_done=1' );
				wp_send_json_success(
					array(
						'status'       => 1,
						'message'      => __( 'Meetings update complete!', 'buddyboss-pro' ),
						'redirect_url' => $redirect_url
					)
				);
			}

			wp_send_json_error();
		}

		public function zoom_meeting_recordings() {
			$meeting_id = filter_input( INPUT_GET, 'meeting_id', FILTER_SANITIZE_STRING );
			$title = filter_input( INPUT_GET, 'title', FILTER_SANITIZE_STRING );

			set_query_var( 'recording_fetch', 'yes' );
			set_query_var( 'meeting_id', $meeting_id );
			set_query_var( 'topic', $title );

			ob_start();
			bp_get_template_part( 'zoom/meeting/recordings' );
			$content = ob_get_clean();

			wp_send_json_success(
			        array( 'contents' => $content )
            );
		}
	}

	new BP_Zoom_Ajax();
}

