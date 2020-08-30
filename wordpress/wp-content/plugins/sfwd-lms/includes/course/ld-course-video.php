<?php
/**
 * Handles Video Progression logic and setup.
 *
 * @package LearnDash
 * @subpackage Course Progression
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Course_Video' ) ) {
	/**
	 * Class for handling the LearnDash Video Progression.
	 */
	class Learndash_Course_Video {

		/**
		 * Static instance of class.
		 *
		 * @var array $instance;
		 */
		private static $instance;

		/**
		 * Array of video progress data options and default values.
		 *
		 * @var array $video_data;
		 */
		private $video_data = array(
			'videos_found_provider'              => false,
			'videos_found_type'                  => false,
			'videos_auto_start'                  => false,
			'videos_show_controls'               => false,
			'videos_auto_complete'               => true,
			'videos_auto_complete_delay'         => 0,
			'videos_auto_complete_delay_message' => '',
			'videos_hide_complete_button'        => false,
			'videos_shown'                       => false,
			'video_debug'                        => false,
			'video_admin_bypass'                 => false,
			'video_cookie_key'                   => false,
			'video_focus_pause'                  => false,
			'video_track_time'                   => false,
			'video_track_expires'                => 30, // Cookie Expire Days the cookie expires. Can be partial 0.5, 1.25, etc.
			'video_track_domain'                 => '', // Cookie Domain. Default set to WP COOKIE_DOMAIN.
			'video_track_path'                   => '', // Cookie Path. Default set to COOKIEPATH or if Multisite SITECOOKIEPATH.
		);

		/**
		 * Variable to contain the final rendered video HTML element.
		 *
		 * @var string $video_content;
		 */
		private $video_content = '';

		/**
		 * LearnDash Vide Progress constructor.
		 */
		public function __construct() {
			add_action( 'wp_footer', array( $this, 'action_wp_footer' ), 1 );
			add_filter( 'learndash_process_mark_complete', array( $this, 'process_mark_complete' ), 99, 3 );
		}

		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new static();
			}

			return self::$instance;
		}

		/**
		 * Hook into the LearnDash template logic to insert the Video Progression output
		 *
		 * @param string $content  HTML content to be output to browser.
		 * @param Object $post     WP_Post instance for Lesson or Topic.
		 * @param array  $settings Current setting values for Post.
		 * @return string $content.
		 */
		public function add_video_to_content( $content = '', $post, $settings = array() ) {
			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();
			} else {
				$user_id = 0;
			}

			// Do we show the video. In some cases we do. But in others like when the setting is to show AFTER completing other steps then we set to false.
			$show_video = false;

			// In the initial flow we do apply the video restiction logic. But then in other if the user is an admin or the student has completed the lesson
			// we don't apply the video logic.
			$logic_video = false;

			if ( ( isset( $settings['lesson_video_enabled'] ) ) && ( 'on' === $settings['lesson_video_enabled'] ) ) {
				if ( ( isset( $settings['lesson_video_url'] ) ) && ( ! empty( $settings['lesson_video_url'] ) ) ) {
					// Because some copy/paste can result in leading whitespace. LEARNDASH-3819
					$settings['lesson_video_url'] = trim( $settings['lesson_video_url'] );
					$settings['lesson_video_url'] = html_entity_decode( $settings['lesson_video_url'] );

					// Just to ensure the proper settings are available
					if ( ( ! isset( $settings['lesson_video_shown'] ) ) || ( empty( $settings['lesson_video_shown'] ) ) ) {
						$settings['lesson_video_shown'] = 'BEFORE';
					}

					if ( ( isset( $settings['lesson_video_focus_pause'] ) ) && ( 'on' === $settings['lesson_video_focus_pause'] ) ) {
						$video_data['video_focus_pause'] = true;
						$this->video_data['video_focus_pause'] = true;
					}
					if ( ( isset( $settings['lesson_video_track_time'] ) ) && ( 'on' === $settings['lesson_video_track_time'] ) ) {
						$video_data['video_track_time'] = true;
						$this->video_data['video_track_time'] = true;
					}

					$bypass_course_limits_admin_users = learndash_can_user_bypass( $user_id, 'learndash_video_progression', $post->ID, $post );

					// For logged in users to allow an override filter.
					/** This filter is documented in includes/class-ld-cpt-instance.php */
					$bypass_course_limits_admin_users       = apply_filters( 'learndash_prerequities_bypass', $bypass_course_limits_admin_users, $user_id, $post->ID, $post );
					$this->video_data['video_admin_bypass'] = $bypass_course_limits_admin_users;

					if ( ! $bypass_course_limits_admin_users ) {

						if ( 'sfwd-lessons' === $post->post_type ) {
							$progress = learndash_get_course_progress( null, $post->ID );

							if ( ( ! empty( $progress['this'] ) ) && ( $progress['this'] instanceof WP_Post ) && ( true === $progress['this']->completed ) ) {
								// The student has completes this step so we show the video but don't apply the logic
								$show_video  = true;
								$logic_video = false;
							} else {
								if ( 'BEFORE' === $settings['lesson_video_shown'] ) {
									$show_video  = true;
									$logic_video = true;

									$topics = learndash_get_topic_list( $post->ID );
									if ( ! empty( $topics ) ) {
										$progress = learndash_get_course_progress( null, $topics[0]->ID );
										if ( ! empty( $progress ) ) {
											$topics_completed = 0;
											foreach ( $progress['posts'] as $topic ) {
												if ( (int) 1 === (int) $topic->completed ) {
													++$topics_completed;
													break;
												}
											}

											if ( ! empty( $topics_completed ) ) {
												$logic_video = false;
											}
										}
									}
								} elseif ( 'AFTER' === $settings['lesson_video_shown'] ) {
									if ( learndash_lesson_topics_completed( $post->ID ) ) {
										$quizzes_completed = true;

										$lesson_quizzes_list = learndash_get_lesson_quiz_list( $post->ID );
										if ( ! empty( $lesson_quizzes_list ) ) {
											foreach ( $lesson_quizzes_list as $quiz ) {
												if ( 'completed' !== $quiz['status'] ) {
													$quizzes_completed = false;
													break;
												}
											}
										}

										if ( true === $quizzes_completed ) {
											$show_video  = true;
											$logic_video = true;
										}
									} else {
										$show_video  = false;
										$logic_video = false;
									}
								}
							}
						} elseif ( 'sfwd-topic' === $post->post_type ) {
							$progress = learndash_get_course_progress( null, $post->ID );

							if ( ( ! empty( $progress['this'] ) ) && ( $progress['this'] instanceof WP_Post ) && ( true === $progress['this']->completed ) ) {
								// The student has completes this step so we show the video but don't apply the logic
								$show_video  = true;
								$logic_video = false;
							} else {
								if ( 'BEFORE' === $settings['lesson_video_shown'] ) {
									$show_video  = true;
									$logic_video = true;
								} elseif ( 'AFTER' === $settings['lesson_video_shown'] ) {
									$quizzes_completed = true;

									$lesson_quizzes_list = learndash_get_lesson_quiz_list( $post->ID );
									if ( ! empty( $lesson_quizzes_list ) ) {
										foreach ( $lesson_quizzes_list as $quiz ) {
											if ( 'completed' !== $quiz['status'] ) {
												$quizzes_completed = false;
												break;
											}
										}
									}

									if ( true === $quizzes_completed ) {
										$show_video  = true;
										$logic_video = true;
									}
								} else {
									$show_video  = false;
									$logic_video = false;
								}
							}
						}
					} else {
						$show_video  = true;
						$logic_video = false;
					}

					if ( true === $show_video ) {

						if ( ( isset( $settings['lesson_video_shown'] ) ) && ( ! empty( $settings['lesson_video_shown'] ) ) ) {
							$this->video_data['videos_shown'] = $settings['lesson_video_shown'];
						} else {
							$this->video_data['videos_shown'] = 'AFTER';
						}

						if ( ( strpos( $settings['lesson_video_url'], 'youtu.be' ) !== false ) || ( strpos( $settings['lesson_video_url'], 'youtube.com' ) !== false ) ) {
							$this->video_data['videos_found_provider'] = 'youtube';
						} elseif ( strpos( $settings['lesson_video_url'], 'vimeo.com' ) !== false ) {
							$this->video_data['videos_found_provider'] = 'vimeo';
						} elseif ( ( strpos( $settings['lesson_video_url'], 'wistia.com' ) !== false ) || ( strpos( $settings['lesson_video_url'], 'wistia.net' ) !== false ) ) {
							$this->video_data['videos_found_provider'] = 'wistia';
						} elseif ( strpos( $settings['lesson_video_url'], 'amazonaws.com' ) !== false ) {
							$this->video_data['videos_found_provider'] = 'local';
						} elseif ( ( strpos( $settings['lesson_video_url'], 'vooplayer' ) !== false ) || ( strpos( $settings['lesson_video_url'], 'spotlightr.com' ) !== false ) ) { 
							$this->video_data['videos_found_provider'] = 'vooplayer';
						} elseif ( strpos( $settings['lesson_video_url'], trailingslashit( get_home_url() ) ) !== false ) {
							$this->video_data['videos_found_provider'] = 'local';
						}

						if ( empty( $this->video_data['videos_found_provider'] ) ) {
							$home_url_domain  = parse_url( get_home_url(), PHP_URL_HOST );
							$video_url_domain = parse_url( $settings['lesson_video_url'], PHP_URL_HOST );

							if ( strtolower( $home_url_domain ) === strtolower( $video_url_domain ) ) {
								$this->video_data['videos_found_provider'] = 'local';
							}
						}

						/**
						 * Filter to override unkown video provider.
						 *
						 * @since 2.4.0
						 *
						 * @param string $video_provider Video provider to use. May be empty.
						 * @param array  $settings       Array of Video Progression Settings.
						 */
						$this->video_data['videos_found_provider'] = apply_filters( 'ld_video_provider', $this->video_data['videos_found_provider'], $settings );
						if ( empty( $this->video_data['videos_found_provider'] ) ) {
							return $content;
						}

						if ( ( substr( $settings['lesson_video_url'], 0, strlen( 'http://' ) ) == 'http://' ) || ( substr( $settings['lesson_video_url'], 0, strlen( 'https://' ) ) == 'https://' ) ) {
							if ( 'local' === $this->video_data['videos_found_provider'] ) {
								$this->video_data['videos_found_type'] = 'video_shortcode';
								$settings['lesson_video_url']          = '[video src="' . $settings['lesson_video_url'] . '"][/video]';

							} elseif ( ( 'youtube' === $this->video_data['videos_found_provider'] ) || ( 'vimeo' === $this->video_data['videos_found_provider'] ) ) {
								$this->video_data['videos_found_type'] = 'embed_shortcode';
								$settings['lesson_video_url']          = '[embed]' . $settings['lesson_video_url'] . '[/embed]';
							} elseif ( 'wistia' === $this->video_data['videos_found_provider'] ) {
								$this->video_data['videos_found_type'] = 'embed_shortcode';
								$settings['lesson_video_url']          = '[embed]' . $settings['lesson_video_url'] . '[/embed]';
							}
						} elseif ( substr( $settings['lesson_video_url'], 0, strlen( '[embed' ) ) == '[embed' ) {
							$this->video_data['videos_found_type'] = 'embed_shortcode';
						} elseif ( substr( $settings['lesson_video_url'], 0, strlen( '[video' ) ) == '[video' ) {
							$this->video_data['videos_found_type'] = 'video_shortcode';
						} elseif ( substr( $settings['lesson_video_url'], 0, strlen( '<iframe' ) ) == '<iframe' ) {
							$this->video_data['videos_found_type'] = 'iframe';
						} else {
							if ( 'vooplayer' === $this->video_data['videos_found_provider'] ) {
								if ( substr( $settings['lesson_video_url'], 0, strlen( '[vooplayer' ) ) == '[vooplayer' ) {
									$this->video_data['videos_found_type'] = 'vooplayer_shortcode';
								} else {
									$this->video_data['videos_found_type'] = 'iframe';
								}
							}
						}

						if ( ( false !== $this->video_data['videos_found_provider'] ) && ( false !== $this->video_data['videos_found_type'] ) ) {
							if ( 'local' === $this->video_data['videos_found_provider'] ) {
								if ( 'video_url' === $this->video_data['videos_found_type'] ) {
									// Nothing here

								} elseif ( 'embed_shortcode' === $this->video_data['videos_found_type'] ) {
									global $wp_embed;
									$video_content       = $wp_embed->run_shortcode( $settings['lesson_video_url'] );
									$this->video_content = do_shortcode( $video_content );

								} elseif ( 'video_shortcode' === $this->video_data['videos_found_type'] ) {
									$this->video_content = do_shortcode( $settings['lesson_video_url'] );
								} elseif ( 'iframe' === $this->video_data['videos_found_type'] ) {
									$this->video_content = $settings['lesson_video_url'];
								}
							} elseif ( ( 'youtube' === $this->video_data['videos_found_provider'] ) || ( 'vimeo' === $this->video_data['videos_found_provider'] ) || ( 'wistia' === $this->video_data['videos_found_provider'] ) ) {
								if ( 'embed_shortcode' === $this->video_data['videos_found_type'] ) {
									global $wp_embed;
									$this->video_content = $wp_embed->run_shortcode( $settings['lesson_video_url'] );
								} elseif ( 'video_shortcode' === $this->video_data['videos_found_type'] ) {
									$this->video_content = do_shortcode( $settings['lesson_video_url'] );
								} elseif ( 'iframe' === $this->video_data['videos_found_type'] ) {
									$this->video_content = $settings['lesson_video_url'];
								}
							} elseif ( 'vooplayer' === $this->video_data['videos_found_provider'] ) {
								if ( 'vooplayer_shortcode' === $this->video_data['videos_found_type'] ) {
									$this->video_content = do_shortcode( $settings['lesson_video_url'] );
								} elseif ( 'iframe' === $this->video_data['videos_found_type'] ) {
									$this->video_content = $settings['lesson_video_url'];
								}
							}

							if ( ! empty( $this->video_content ) ) {
								if ( $logic_video ) {

									if ( ( isset( $settings['lesson_video_show_controls'] ) ) && ( 'on' === $settings['lesson_video_show_controls'] ) ) {
										$this->video_data['videos_show_controls'] = 1;
									} else {
										$this->video_data['videos_show_controls'] = 0;
									}

									if ( ( isset( $settings['lesson_video_auto_start'] ) ) && ( 'on' === $settings['lesson_video_auto_start'] ) ) {
										$this->video_data['videos_auto_start'] = 1;
									} else {
										$this->video_data['videos_auto_start'] = 0;
									}

									$video_preg_pattern = '';

									if ( strstr( $this->video_content, '<iframe' ) ) {
										$video_token = 'iframe';
									} elseif ( strstr( $this->video_content, '<video' ) ) {
										$video_token = 'video';
									}
									if ( strstr( $this->video_content, ' src="' ) ) {
										$video_preg_pattern = '/<' . $video_token . '.*src=\"(.*)\".*><\/' . $video_token . '>/isU';
									} elseif ( strstr( $this->video_content, " src='" ) ) {
										$video_preg_pattern = '/<' . $video_token . ".*src=\'(.*)\'.*><\/" . $video_token . '>/isU';
									}

									if ( ! empty( $video_preg_pattern ) ) {
										preg_match( $video_preg_pattern, $this->video_content, $matches );
										if ( ( is_array( $matches ) ) && ( isset( $matches[1] ) ) && ( ! empty( $matches[1] ) ) ) {

											// Next we need to check if the video is YouTube, Vimeo, etc. so we check the matches[1]
											if ( 'youtube' === $this->video_data['videos_found_provider'] ) {
												/**
												 * Filters post content video parameters.
												 *
												 * @param array   $video_params   An array of video parameters.
												 * @param string  $video_provider Name of the video provider.
												 * @param string  $video_content  Video content HTML output.
												 * @param WP_POST $post           Post object.
												 * @param array   $settings       An array of LearnDash settings for a post.
												 */
												$ld_video_params = apply_filters(
													'ld_video_params',
													array(
														'controls' => $this->video_data['videos_show_controls'],
														'autoplay' => $this->video_data['videos_auto_start'],
														'modestbranding' => 1,
														'showinfo' => 0,
														'rel' => 0,
													),
													'youtube',
													$this->video_content,
													$post,
													$settings
												);

												// Regardless of the filter we set this param because we need it!
												$ld_video_params['enablejsapi'] = '1';

												$matches_1_new       = add_query_arg( $ld_video_params, $matches[1] );
												$this->video_content = str_replace( $matches[1], $matches_1_new, $this->video_content );

											} elseif ( 'vimeo' === $this->video_data['videos_found_provider'] ) {

												/**
												 * Ensure for Vimeo, the video controls and auto-start cannot both be disabled.
												 */
												if ( ( ! $this->video_data['videos_show_controls'] ) && ( ! $this->video_data['videos_auto_start'] ) ) {
													$this->video_data['videos_show_controls'] = true;
												}

												/** This filter is documented in includes/course/ld-course-video.php */
												$ld_video_params = apply_filters(
													'ld_video_params',
													array(
														'controls' => $this->video_data['videos_show_controls'],
														'autoplay' => $this->video_data['videos_auto_start'],
													),
													'vimeo',
													$this->video_content,
													$post,
													$settings
												);

												// Regardless of the filter we set this param because we need it!
												$ld_video_params['api'] = '1';

												$matches_1_new       = add_query_arg( $ld_video_params, $matches[1] );
												$this->video_content = str_replace( $matches[1], $matches_1_new, $this->video_content );

											} elseif ( 'wistia' === $this->video_data['videos_found_provider'] ) {
												/** This filter is documented in includes/course/ld-course-video.php */
												$ld_video_params = apply_filters(
													'ld_video_params',
													array(),
													'wistia',
													$this->video_content,
													$post,
													$settings
												);
												if ( ! empty( $ld_video_params ) ) {
													$matches_1_new       = add_query_arg( $ld_video_params, $matches[1] );
													$this->video_content = str_replace( $matches[1], $matches_1_new, $this->video_content );
												} else {
													$matches_1_new = $matches[1];
												}

												$url_path            = wp_parse_url( $matches_1_new, PHP_URL_PATH );
												$url_path_parts      = explode( '/', $url_path );
												$video_id            = $url_path_parts[ count( $url_path_parts ) - 1 ];
												$this->video_content = str_replace( '<iframe ', '<iframe data-learndash-video-wistia-id="' . $video_id . '" ', $this->video_content );
											} elseif ( 'vooplayer' === $this->video_data['videos_found_provider'] ) {
												/** This filter is documented in includes/course/ld-course-video.php */
												$ld_video_params = apply_filters(
													'ld_video_params',
													array(),
													'wistia',
													$this->video_content,
													$post,
													$settings
												);

												if ( ! empty( $ld_video_params ) ) {
													$matches_1_new       = add_query_arg( $ld_video_params, $matches[1] );
													$this->video_content = str_replace( $matches[1], $matches_1_new, $this->video_content );
												}
											} elseif ( 'local' === $this->video_data['videos_found_provider'] ) {
												/** This filter is documented in includes/course/ld-course-video.php */
												$ld_video_params = apply_filters(
													'ld_video_params',
													array(
														'controls' => $this->video_data['videos_show_controls'],
													),
													'local',
													$this->video_content,
													$post,
													$settings
												);
												if ( (int) true !== (int) $ld_video_params['controls'] ) {
													$this->video_content .= '<style>.ld-video .mejs-controls { display: none !important; visibility: hidden !important;}</style>';
												}
											}
										}
									}

									$this->video_data['videos_auto_complete'] = false;
									if ( ( isset( $settings['lesson_video_shown'] ) ) && ( 'AFTER' === $settings['lesson_video_shown'] ) ) {
										if ( ( isset( $settings['lesson_video_auto_complete'] ) ) && ( 'on' === $settings['lesson_video_auto_complete'] ) ) {
											$this->video_data['videos_auto_complete'] = true;

											if ( ( isset( $settings['lesson_video_hide_complete_button'] ) ) && ( 'on' === $settings['lesson_video_hide_complete_button'] ) ) {
												$this->video_data['videos_hide_complete_button'] = true;
											}

											if ( isset( $settings['lesson_video_auto_complete_delay'] ) ) {
												$this->video_data['videos_auto_complete_delay'] = intval( $settings['lesson_video_auto_complete_delay'] );

												$post_type_obj  = get_post_type_object( $post->post_type );
												$post_type_name = $post_type_obj->labels->name;
												$this->video_data['videos_auto_complete_delay_message'] =
												sprintf(
													// translators: placeholders: 1. Lesson or Topic label, 2. span for counter.
													wp_kses_post( _x( '<p class="ld-video-delay-message">%1$s will auto complete in %2$s seconds</p>', 'placeholders: 1. Lesson or Topic label, 2. span for counter', 'learndash' ) ),
													$post_type_obj->labels->singular_name,
													'<span class="time-countdown">' . $this->video_data['videos_auto_complete_delay'] . '</span>'
												);
											}
										}
									}
								}
							}
						}
					}
				}

				if ( ! empty( $this->video_content ) ) {
					if ( false !== $this->video_data['videos_found_provider'] ) {
						if ( isset( $_GET['ld_debug'] ) ) {
							$this->video_data['video_debug'] = true;
						}

						$video_post_url       = learndash_get_step_permalink( $post );
						$video_post_url_parts = wp_parse_url( $video_post_url );

						if ( defined( 'COOKIE_DOMAIN' ) ) {
							$this->video_data['video_track_domain'] = COOKIE_DOMAIN;
						} else {
							if ( isset( $video_post_url_parts['host'] ) ) {
								$this->video_data['video_track_domain'] = $video_post_url_parts['host'];
							}
						}

						if ( ( is_multisite() ) && ( defined( 'SITECOOKIEPATH' ) ) ) {
							$this->video_data['video_track_path'] = SITECOOKIEPATH;
						} elseif ( defined( 'COOKIEPATH' ) ) {
							$this->video_data['video_track_path'] = COOKIEPATH;
						} else {
							if ( isset( $video_post_url_parts['path'] ) ) {
								$this->video_data['video_track_path'] = $video_post_url_parts['path'];
							}
						}

						$this->video_data['video_cookie_key'] = $this->build_video_cookie_key( $post, $settings );

						/**
						 * Filters content video data.
						 *
						 * @param array $video_data An array of video data.
						 * @param array  $settings       An array of LearnDash settings for a post.
						 */
						$this->video_data = apply_filters( 'learndash_lesson_video_data', $this->video_data, $settings );

						if ( true === $logic_video ) {
							$logic_video_str = 'true';
						} else {
							$logic_video_str = 'false';
						}
						$this->video_content = '<div class="ld-video" data-video-progression="' . $logic_video_str . '" data-video-cookie-key="' . $this->video_data['video_cookie_key'] . '" data-video-provider="' . $this->video_data['videos_found_provider'] . '">' . $this->video_content . '</div>';

						$content = SFWD_LMS::get_template(
							'learndash_lesson_video',
							array(
								'content'        => $content,
								'video_content'  => $this->video_content,
								'video_settings' => $settings,
								'video_data'     => $this->video_data,
							)
						);

					} else {
						$this->video_data['videos_found_provider'] = false;

						$this->video_content = '<div class="ld-video" data-video-progression="false">' . $this->video_content . '</div>';
					}
				}
			}

			return $content;
		}

		/**
		 * Add JS logic to the page footer.
		 */
		public function action_wp_footer() {
			if ( false !== $this->video_data['videos_found_provider'] ) {

				wp_enqueue_script(
					'learndash_cookie_script_js',
					LEARNDASH_LMS_PLUGIN_URL . 'assets/vendor/js-cookie/js.cookie' . leardash_min_asset() . '.js',
					array(),
					LEARNDASH_SCRIPT_VERSION_TOKEN,
					true
				);
				$learndash_assets_loaded['scripts']['learndash_cookie_script_js'] = __FUNCTION__;

				wp_enqueue_script(
					'learndash_video_script_js',
					LEARNDASH_LMS_PLUGIN_URL . 'assets/js/learndash_video_script' . leardash_min_asset() . '.js',
					array( 'jquery' ),
					LEARNDASH_SCRIPT_VERSION_TOKEN,
					true
				);
				$learndash_assets_loaded['scripts']['learndash_video_script_js'] = __FUNCTION__;

				wp_localize_script( 'learndash_video_script_js', 'learndash_video_data', $this->video_data );

				if ( 'youtube' === $this->video_data['videos_found_provider'] ) {
					wp_enqueue_script( 'youtube_iframe_api', 'https://www.youtube.com/iframe_api', array( 'learndash_video_script_js' ), LEARNDASH_SCRIPT_VERSION_TOKEN, true );
				} elseif ( 'vimeo' === $this->video_data['videos_found_provider'] ) {
					wp_enqueue_script( 'vimeo_iframe_api', 'https://player.vimeo.com/api/player.js', array( 'learndash_video_script_js' ), LEARNDASH_SCRIPT_VERSION_TOKEN, true );
				}
			}
		}

		/**
		 * Handle Mark Complete on Lesson or Topic with Video Progress enabled.
		 *
		 * @param bool   $process_complete.
		 * @param Object $post         WP_Post object beiing marked complete.
		 * @param Object $current_user The User perforning the action.
		 */
		public function process_mark_complete( $process_complete = true, $post, $current_user ) {
			if ( ( isset( $_GET['quiz_redirect'] ) ) && ( ! empty( $_GET['quiz_redirect'] ) ) && ( isset( $_GET['quiz_type'] ) ) && ( 'lesson' === $_GET['quiz_type'] ) ) {
				$lesson_id = 0;
				$quiz_id   = 0;

				if ( isset( $_GET['lesson_id'] ) ) {
					$lesson_id = intval( $_GET['lesson_id'] );
				}
				if ( isset( $_GET['quiz_id'] ) ) {
					$quiz_id = intval( $_GET['quiz_id'] );
				}

				if ( ( ! empty( $lesson_id ) ) && ( ! empty( $quiz_id ) ) ) {
					$lesson_settings = learndash_get_setting( $lesson_id );
					if ( ( isset( $lesson_settings['lesson_video_enabled'] ) ) && ( 'on' === $lesson_settings['lesson_video_enabled'] ) ) {
						if ( ( isset( $lesson_settings['lesson_video_shown'] ) ) && ( 'AFTER' === $lesson_settings['lesson_video_shown'] ) ) {
							$process_complete = false;

							add_filter( 'learndash_completion_redirect', array( $this, 'learndash_completion_redirect' ), 99 );
						}
					}
				}
			}

			return $process_complete;

		}

		/**
		 * Redirect after Mark Complete is performed.
		 *
		 * @param string $link Link to redirect to after Mark Complete.
		 */
		public function learndash_completion_redirect( $link ) {
			if ( ( isset( $_GET['quiz_redirect'] ) ) && ( ! empty( $_GET['quiz_redirect'] ) ) && ( isset( $_GET['quiz_type'] ) ) && ( 'lesson' === $_GET['quiz_type'] ) ) {
				$lesson_id = 0;
				$quiz_id   = 0;

				if ( isset( $_GET['lesson_id'] ) ) {
					$lesson_id = intval( $_GET['lesson_id'] );
				}
				if ( isset( $_GET['quiz_id'] ) ) {
					$quiz_id = intval( $_GET['quiz_id'] );
				}

				if ( ( ! empty( $lesson_id ) ) && ( ! empty( $quiz_id ) ) ) {
					$lesson_settings = learndash_get_setting( $lesson_id );
					if ( ( isset( $lesson_settings['lesson_video_enabled'] ) ) && ( 'on' === $lesson_settings['lesson_video_enabled'] ) ) {
						if ( ( isset( $lesson_settings['lesson_video_shown'] ) ) && ( 'AFTER' === $lesson_settings['lesson_video_shown'] ) ) {
							$link = learndash_get_step_permalink( $lesson_id );

							remove_filter( 'learndash_completion_redirect', array( $this, 'learndash_completion_redirect' ), 99 );
						}
					}
				}
			}

			return $link;
		}

		/**
		 * Build unique video progress cookie key. This is used to track the video state
		 * in the user's browser.
		 *
		 * @param Object $post WP_Post object or Lesson/Topic.
		 * @param array  $settings Array of Lesson/Topic Settings
		 * @return string $cookie_key.
		 */
		public function build_video_cookie_key( $post, $settings ) {
			$cookie_key = '';
			$course_id  = learndash_get_course_id( $post );
			if ( ! empty( $course_id ) ) {
				$cookie_key = get_current_user_id() . '_' . $course_id . '_' . $post->ID;
				if ( ( isset( $settings['lesson_video_url'] ) ) && ( ! empty( $settings['lesson_video_url'] ) ) ) {
					$cookie_key .= '_' . $settings['lesson_video_url'];
				}
				$cookie_key = 'learndash-video-progress-' . md5( $cookie_key );
			}

			return $cookie_key;
		}

		// End of functions.
	}
}

add_action(
	'learndash_init',
	function() {
		Learndash_Course_Video::get_instance();
	}
);
