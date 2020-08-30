<?php
use \BuddyBoss\Zoom\Firebase\JWT\JWT;

/**
 * Class Connecting Zoom API
 *
 * @since   1.0.0
 */
if ( ! class_exists( 'BP_Zoom_Conference_Api' ) ) {

	class BP_Zoom_Conference_Api {

		/**
		 * Zoom API Key
		 *
		 * @var string
		 */
		public $zoom_api_key;

		/**
		 * Zoom API Secret
		 *
		 * @var string
		 */
		public $zoom_api_secret;

		/**
		 * Instance of BP_Zoom_Conference_Api
		 *
		 * @var object
		 */
		protected static $_instance;

		/**
		 * Zoom API URL
		 *
		 * @var string
		 */
		private $api_url = 'https://api.zoom.us/v2/';

		/**
		 * Create only one instance so that it may not Repeat
		 *
		 * @since 1.2.10
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		public function __construct( $zoom_api_key = '', $zoom_api_secret = '' ) {
			$this->zoom_api_key    = $zoom_api_key;
			$this->zoom_api_secret = $zoom_api_secret;
		}

		protected function send_request( $called_function, $data, $request = 'GET' ) {
			$request_url = $this->api_url . $called_function;
			$args        = array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->generate_jwt_key(),
					'Content-Type'  => 'application/json',
				),
			);

			if ( 'POST' === $request ) {
				$args['body']   = ! empty( $data ) ? json_encode( $data ) : array();
				$args['method'] = 'POST';
				$response       = wp_remote_post( $request_url, $args );
			} elseif ( 'DELETE' === $request ) {
				$args['body']   = ! empty( $data ) ? json_encode( $data ) : array();
				$args['method'] = 'DELETE';
				$response       = wp_remote_request( $request_url, $args );
			} elseif ( 'PATCH' === $request ) {
				$args['body']   = ! empty( $data ) ? json_encode( $data ) : array();
				$args['method'] = 'PATCH';
				$response       = wp_remote_request( $request_url, $args );
			} else {
				$args['body'] = ! empty( $data ) ? $data : array();
				$response     = wp_remote_get( $request_url, $args );
			}

			$response_code = wp_remote_retrieve_response_code( $response );
			$response      = wp_remote_retrieve_body( $response );

			return array(
				'response' => json_decode( $response ),
				'code'     => $response_code,
			);
		}

		/**
		 * Generate JWT Key
		 *
		 * @since 1.0.0
		 * @return string JWT key
		 */
		private function generate_jwt_key() {
			$key    = $this->zoom_api_key;
			$secret = $this->zoom_api_secret;

			$token = array(
				'iss' => $key,
				'exp' => time() + 3600, // 60 seconds as suggested
			);

			return JWT::encode( $token, $secret );
		}

		/**
		 * Creates a User
		 *
		 * @since 1.0.0
		 *
		 * @param array $data
		 *
		 * @return array|bool|string
		 */
		public function create_user( $data = array() ) {
			$args              = array();
			$args['action']    = $data['action'];
			$args['user_info'] = array(
				'email'      => $data['email'],
				'type'       => $data['type'],
				'first_name' => $data['first_name'],
				'last_name'  => $data['last_name'],
			);

			return $this->send_request( 'users', $args, 'POST' );
		}

		/**
		 * Updates a User
		 *
		 * @since 1.0.0
		 *
		 * @param array $data
		 *
		 * @return array|bool|string
		 */
		public function update_user( $data = array() ) {
			$args = array(
				'type'       => $data['type'],
				'first_name' => $data['first_name'],
				'last_name'  => $data['last_name'],
			);

			return $this->send_request( 'users/' . $data['user_id'], $args, 'PATCH' );
		}

		/**
		 * Get user list
		 *
		 * @since 1.0.0
		 * @param $page
		 * @return array
		 */
		public function list_users( $page = 1 ) {
			$args                = array();
			$args['page_size']   = 300;
			$args['page_number'] = absint( $page );

			return $this->send_request( 'users', $args, 'GET' );
		}

		/**
		 * Get users info by user ID
		 *
		 * @since 1.0.0
		 *
		 * @param $user_id
		 *
		 * @return array|bool|string
		 */
		public function get_user_info( $user_id ) {
			$args = array();

			return $this->send_request( 'users/' . $user_id, $args );
		}

		/**
		 * Delete a User
		 *
		 * @since 1.0.0
		 * @param $user_id
		 *
		 * @return array|bool|string
		 */
		public function delete_user( $user_id ) {
			return $this->send_request( 'users/' . $user_id, false, 'DELETE' );
		}

		/**
		 * Get Meetings
		 *
		 * @since 1.0.0
		 * @param $host_id
		 *
		 * @return array
		 */
		public function list_meetings( $host_id ) {
			$args              = array();
			$args['page_size'] = 300;

			return $this->send_request( 'users/' . $host_id . '/meetings', $args, 'GET' );
		}

		/**
		 * Create A meeting API
		 *
		 * @since 1.0.0
		 * @param array $data
		 *
		 * @return object
		 */
		public function create_meeting( $data = array() ) {

			$args = array();

			$alternative_host_ids = '';
			if ( ! empty( $data['alternative_host_ids'] ) ) {
				if ( is_array( $data['alternative_host_ids'] ) ) {
					$alternative_host_ids = implode( ',', $data['alternative_host_ids'] );
				} else {
					$alternative_host_ids = $data['alternative_host_ids'];
				}
			}

			$args['topic']      = ! empty( $data['title'] ) ? $data['title'] : '';
			$args['agenda']     = ! empty( $data['description'] ) ? $data['description'] : '';
			$args['type']       = ! empty( $data['type'] ) ? $data['type'] : 2; // Scheduled
			$args['start_time'] = ! empty( $data['start_date'] ) ? $data['start_date'] : '';
			$args['timezone']   = $data['timezone'];
			$args['password']   = ! empty( $data['password'] ) ? $data['password'] : '';
			$args['duration']   = ! empty( $data['duration'] ) ? $data['duration'] : 60;
			if ( ! empty( $data['recurrence'] ) ) {
				$args['recurrence']   = $data['recurrence'];
			}
			$args['settings'] = array(
				'join_before_host'       => ! empty( $data['join_before_host'] ) ? true : false,
				'host_video'             => ! empty( $data['host_video'] ) ? true : false,
				'participant_video'      => ! empty( $data['participants_video'] ) ? true : false,
				'mute_upon_entry'        => ! empty( $data['mute_participants'] ) ? true : false,
				'approval_type'          => ! empty( $data['registration'] ) ? 0 : 2,
				'meeting_authentication' => ! empty( $data['meeting_authentication'] ) ? true : false,
				'waiting_room'           => ! empty( $data['waiting_room'] ) ? true : false,
				'auto_recording'         => ! empty( $data['auto_recording'] ) ? $data['auto_recording'] : 'none',
				'registration_type'      => ! empty( $data['registration_type'] ) ? $data['registration_type'] : 1,
				'alternative_hosts'      => isset( $alternative_host_ids ) ? $alternative_host_ids : '',
			);

			return $this->send_request( 'users/' . $data['host_id'] . '/meetings', $args, 'POST' );
		}

		/**
		 * Updating Meeting Info
		 *
		 * @since 1.0.0
		 * @param $update_data
		 *
		 * @return array
		 */
		public function update_meeting( $update_data = array() ) {

			$args = array();

			$alternative_host_ids = '';
			if ( ! empty( $update_data['alternative_host_ids'] ) ) {
				if ( is_array( $update_data['alternative_host_ids'] ) ) {
					$alternative_host_ids = implode( ',', $update_data['alternative_host_ids'] );
				} else {
					$alternative_host_ids = $update_data['alternative_host_ids'];
				}
			}

			$args['topic']      = ! empty( $update_data['title'] ) ? $update_data['title'] : '';
			$args['agenda']     = ! empty( $update_data['description'] ) ? $update_data['description'] : '';
			$args['type']       = ! empty( $update_data['type'] ) ? $update_data['type'] : 2; // Scheduled
			$args['start_time'] = ! empty( $update_data['start_date'] ) ? $update_data['start_date'] : '';
			$args['timezone']   = ! empty( $update_data['timezone'] ) ? $update_data['timezone'] : 'UTC';
			$args['password']   = ! empty( $update_data['password'] ) ? $update_data['password'] : '';
			$args['duration']   = ! empty( $update_data['duration'] ) ? $update_data['duration'] : 60;
			if ( ! empty( $update_data['recurrence'] ) ) {
				$args['recurrence']   = $update_data['recurrence'];
			}
			$args['settings']   = array(
				'join_before_host'       => ! empty( $update_data['join_before_host'] ) ? true : false,
				'host_video'             => ! empty( $update_data['host_video'] ) ? true : false,
				'participant_video'      => ! empty( $update_data['participants_video'] ) ? true : false,
				'mute_upon_entry'        => ! empty( $update_data['mute_participants'] ) ? true : false,
				'approval_type'          => ! empty( $update_data['registration'] ) ? 0 : 2,
				'meeting_authentication' => ! empty( $update_data['meeting_authentication'] ) ? true : false,
				'waiting_room'           => ! empty( $update_data['waiting_room'] ) ? true : false,
				'auto_recording'         => ! empty( $update_data['auto_recording'] ) ? $update_data['auto_recording'] : 'none',
				'registration_type'      => ! empty( $update_data['registration_type'] ) ? $update_data['registration_type'] : 1,
				'alternative_hosts'      => isset( $alternative_host_ids ) ? $alternative_host_ids : '',
			);

			return $this->send_request( 'meetings/' . $update_data['meeting_id'], $args, 'PATCH' );
		}

		/**
		 * Updating Meeting Occurrence Info
		 *
		 * @since 1.0.4
		 * @param $occurrence_id
		 * @param $update_data
		 *
		 * @return array
		 */
		public function update_meeting_occurrence( $occurrence_id, $update_data = array() ) {
			$args = array();

			$post_time  = $update_data['start_date'];
			$start_time = gmdate( 'Y-m-d\TH:i:s', strtotime( $post_time ) );
			$args['start_time'] = $start_time;

			if ( isset( $update_data['duration'] ) ) {
				$args['duration'] = $update_data['duration'];
			}

			if ( isset( $update_data['description'] ) ) {
				$args['agenda'] = $update_data['description'];
			}

			$args['settings']   = array(
				'join_before_host'       => ! empty( $update_data['join_before_host'] ),
				'host_video'             => ! empty( $update_data['host_video'] ),
				'participant_video'      => ! empty( $update_data['participants_video'] ),
				'mute_upon_entry'        => ! empty( $update_data['mute_participants'] ),
				'waiting_room'           => ! empty( $update_data['waiting_room'] ),
				'auto_recording'         => ! empty( $update_data['auto_recording'] ) ? $update_data['auto_recording'] : 'none',
			);

			return $this->send_request( 'meetings/' . $update_data['meeting_id'] . '/?occurrence_id=' . $occurrence_id, $args, 'PATCH' );
		}

		/**
		 * Get a Meeting Info
		 *
		 * @since 1.0.0
		 * @param  int $meeting_id
		 * @param  int|bool $occurrence_id
		 * @param  bool $show_previous_occurrences
		 *
		 * @return array
		 */
		public function get_meeting_info( $meeting_id, $occurrence_id = false, $show_previous_occurrences = false ) {
			$args = array();
			$query_url = '';

			if ( ! empty( $occurrence_id ) ) {
				$query_url .= '/?occurrence_id=' . $occurrence_id;
			} else if ( ! empty( $show_previous_occurrences ) ) {
				$query_url .= '/?show_previous_occurrences=' . true;
			}

			return $this->send_request( 'meetings/' . $meeting_id . $query_url, $args, 'GET' );
		}

		/**
		 * Delete A Meeting
		 *
		 * @since 1.0.0
		 * @param int $meeting_id
		 * @param int|bool $occurrence_id
		 *
		 * @return array
		 */
		public function delete_meeting( $meeting_id, $occurrence_id = false ) {
			$args = array();

			$occurrence_url = '';
			if ( $occurrence_id ) {
				$occurrence_url = '/?occurrence_id=' . $occurrence_id;
			}

			return $this->send_request( 'meetings/' . $meeting_id . $occurrence_url, $args, 'DELETE' );
		}

		/**
		 * Get daily account reports by month
		 *
		 * @since 1.0.0
		 * @param $month
		 * @param $year
		 *
		 * @return bool|mixed
		 */
		public function get_daily_report( $month, $year ) {
			$args          = array();
			$args['year']  = $year;
			$args['month'] = $month;

			return $this->send_request( 'report/daily', $args, 'GET' );
		}

		/**
		 * Get Account Reports
		 *
		 * @since 1.0.0
		 * @param $zoom_account_from
		 * @param $zoom_account_to
		 *
		 * @return array
		 */
		public function get_account_report( $zoom_account_from, $zoom_account_to ) {
			$args              = array();
			$args['from']      = $zoom_account_from;
			$args['to']        = $zoom_account_to;
			$args['page_size'] = 300;

			return $this->send_request( 'report/users', $args, 'GET' );
		}

		/**
		 * Register webiner participants
		 *
		 * @since 1.0.0
		 * @param $webinar_id
		 * @param $first_name
		 * @param $last_name
		 * @param $email
		 *
		 * @return mixed
		 */
		public function register_webinar_participants( $webinar_id, $first_name, $last_name, $email ) {
			$data               = array();
			$data['first_name'] = $first_name;
			$data['last_name']  = $last_name;
			$data['email']      = $email;

			return $this->send_request( 'webinars/' . $webinar_id . '/registrants', $data, 'POST' );
		}

		/**
		 * List webinars
		 *
		 * @since 1.0.0
		 * @param $user_id
		 *
		 * @return bool|mixed
		 */
		public function list_webinar( $user_id ) {
			$data              = array();
			$data['page_size'] = 300;

			return $this->send_request( 'users/' . $user_id . '/webinars', $data, 'GET' );
		}

		/**
		 * List Webinar Participants
		 *
		 * @since 1.0.0
		 * @param $webinar_id
		 *
		 * @return bool|mixed
		 */
		public function list_webinar_participants( $webinar_id ) {
			$data              = array();
			$data['page_size'] = 300;

			return $this->send_request( 'webinars/' . $webinar_id . '/registrants', $data, 'GET' );
		}

		/**
		 * Get recording by meeting ID
		 *
		 * @since 1.0.0
		 * @param $meeting_id
		 *
		 * @return bool|mixed
		 */
		public function recordings_by_meeting( $meeting_id ) {
			return $this->send_request( 'meetings/' . $meeting_id . '/recordings', false, 'GET' );
		}

		/**
		 * Get instances by meeting ID
		 *
		 * @since 1.0.0
		 * @param $meeting_id
		 *
		 * @return bool|mixed
		 */
		public function meeting_instances( $meeting_id ) {
			return $this->send_request( 'past_meetings/' . $meeting_id . '/instances', false, 'GET' );
		}

		/**
		 * Get all recordings by USER ID
		 *
		 * @since 1.0.0
		 * @param $host_id
		 * @param $data array
		 *
		 * @return bool|mixed
		 */
		public function list_recording( $host_id, $data = array() ) {
			$post_data = array();
			$from      = gmdate( 'Y-m-d', strtotime( '-1 year', time() ) );
			$to        = gmdate( 'Y-m-d', time() );

			$post_data['from'] = ! empty( $data['from'] ) ? $data['from'] : $from;
			$post_data['to']   = ! empty( $data['to'] ) ? $data['to'] : $to;

			return $this->send_request( 'users/' . $host_id . '/recordings', $post_data, 'GET' );
		}

		/**
		 * Get meeting recording settings.
		 *
		 * @since 1.0.0
		 * @param $meeting_id
		 *
		 * @return bool|mixed
		 */
		public function recording_settings( $meeting_id ) {
			return $this->send_request( 'meetings/' . $meeting_id . '/recordings/settings', false, 'GET' );
		}

		/**
		 * Get meeting invitation template.
		 *
		 * @since 1.0.0
		 * @param $meeting_id
		 *
		 * @return bool|mixed
		 */
		public function meeting_invitation( $meeting_id ) {
			return $this->send_request( 'meetings/' . $meeting_id . '/invitation', false, 'GET' );
		}
	}

	function bp_zoom_conference() {
		return BP_Zoom_Conference_Api::instance();
	}

	// setup zoom.
	bp_zoom_conference()->zoom_api_key    = bp_zoom_api_key();
	bp_zoom_conference()->zoom_api_secret = bp_zoom_api_secret();
}
