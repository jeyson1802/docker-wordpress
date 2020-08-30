<?php
/**
 * LearnDash Settings Section for Group Leader Users Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Groups_Group_Leader_User' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Section_Groups_Group_Leader_User extends LearnDash_Settings_Section {

		/**
		 * Array or role capabilities ties to the settings fields.
		 *
		 * @var array $role_caps.
		 */
		private $role_caps = array();

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			$this->settings_screen_id = 'groups_page_groups-options';

			// This is the 'option_name' key used in the wp_options table.
			$this->settings_page_id = 'groups-options';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_groups_group_leader_user';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_groups_group_leader_user';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_group_leader_user';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Group Leader User Settings', 'learndash' );

			$this->settings_section_description = esc_html__( 'Controls the Group Leader capabilities.', 'learndash' );

			add_filter( 'learndash_settings_row_outside_before', array( $this, 'learndash_settings_row_outside_before' ), 30, 2 );

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			global $sfwd_lms;

			parent::load_settings_values();

			if ( ! isset( $this->setting_option_values['bypass_course_limits'] ) ) {
				$this->setting_option_values['bypass_course_limits'] = '';
			}

			if ( ! isset( $this->setting_option_values['courses_autoenroll'] ) ) {
				$this->setting_option_values['courses_autoenroll'] = '';
			}

			if ( ! isset( $this->setting_option_values['manage_groups_enabled'] ) ) {
				$this->setting_option_values['manage_groups_enabled'] = '';
			}

			if ( ! isset( $this->setting_option_values['manage_groups_capabilities'] ) ) {
				$this->setting_option_values['manage_groups_capabilities'] = 'basic';
			}

			if ( ! isset( $this->setting_option_values['manage_courses_enabled'] ) ) {
				$this->setting_option_values['manage_courses_enabled'] = '';
			}

			if ( ! isset( $this->setting_option_values['manage_courses_capabilities'] ) ) {
				$this->setting_option_values['manage_courses_capabilities'] = 'basic';
			}

			if ( ! isset( $this->setting_option_values['manage_users_enabled'] ) ) {
				$this->setting_option_values['manage_users_enabled'] = '';
			}

			if ( ! isset( $this->setting_option_values['manage_users_capabilities'] ) ) {
				$this->setting_option_values['manage_users_capabilities'] = 'basic';
			}

			$this->role_caps = array(
				'manage_groups_capabilities'  => array(
					'basic'    => array(
						'edit_groups',
						'edit_published_groups',
						'delete_group',
						'delete_groups',
						'delete_published_groups',
						'publish_groups',
					),
					'advanced' => array(
						'delete_others_groups',
						'edit_others_groups',
						'delete_private_groups',
						'edit_private_groups',
						'read_private_groups',
					),
				),
				'manage_courses_capabilities' => array(
					'basic'    => array(
						'edit_courses',
						'edit_published_courses',
						'delete_course',
						'delete_courses',
						'delete_published_courses',
						'publish_courses',

						'wpProQuiz_show',
						'wpProQuiz_add_quiz',
						'wpProQuiz_edit_quiz',
						'wpProQuiz_delete_quiz',
						'wpProQuiz_show_statistics',
						'wpProQuiz_toplist_edit',
					),
					'advanced' => array(
						'delete_others_courses',
						'edit_others_courses',
						'delete_private_courses',
						'edit_private_courses',
						'read_private_courses',

						'wpProQuiz_reset_statistics',
						'wpProQuiz_import',
						'wpProQuiz_export',
					),
				),
				'manage_users_capabilities'   => array(
					'basic'    => array(
						'edit_users',
					),
					'advanced' => array(
						'list_users',
						'create_users',
						'delete_users',
					),
				),
			);

			foreach (
				array( 
					learndash_get_post_type_slug( 'group' )  => 'manage_groups_capabilities', 
					learndash_get_post_type_slug( 'course' ) => 'manage_courses_capabilities',
				) as $post_type => $cap_section ) {

				$post_taxonomies = $sfwd_lms->get_post_args_section( $post_type, 'taxonomies' );
				if ( ! empty( $post_taxonomies ) ) {
					foreach ( $post_taxonomies as $tax ) {
						if ( isset( $tax['capabilities'] ) ) {
							foreach ( $tax['capabilities'] as $key => $cap ) {
								if ( in_array( $key, array( 'manage_terms', 'assign_terms' ), true ) ) {
									$this->role_caps[ $cap_section ]['basic'][] = $cap;
								} else {
									$this->role_caps[ $cap_section ]['advanced'][] = $cap;
								}
							}
						}
					}

					$this->role_caps[ $cap_section ]['basic']    = array_unique( $this->role_caps[ $cap_section ]['basic'] );
					$this->role_caps[ $cap_section ]['advanced'] = array_unique( $this->role_caps[ $cap_section ]['advanced'] );
				}
			}

			/**
			 * Filter Group Leader Role Capabilities.
			 *
			 * @since 3.2.0
			 *
			 * @param array  $role_caps            Group Leader Role Capabilities.
			 * @param string $settings_section_key Settings Section Key.
			 */
			$this->role_caps = apply_filters( 'learndash_group_leader_role_capabilities', $this->role_caps, $this->settings_section_key );
		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {

			$this->setting_option_fields = array(
				'courses_autoenroll'          => array(
					'name'      => 'courses_autoenroll',
					'type'      => 'checkbox-switch',
					'label'     => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Auto-enrollment', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'help_text' => sprintf(
						// translators: placeholder: courses, course.
						esc_html_x( 'Allow group leader users to have access to %1$s automatically without requiring %2$s enrollment.', 'placeholder: courses, course', 'learndash' ),
						learndash_get_custom_label_lower( 'courses' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'value'     => $this->setting_option_values['courses_autoenroll'],
					'options'   => array(
						''    => sprintf(
							// translators: placeholder: courses.
							esc_html_x( 'Group Leader has access to enrolled %s only', 'placeholder: courses', 'learndash' ),
							learndash_get_custom_label_lower( 'courses' )
						),
						'yes' => sprintf(
							// translators: placeholder: courses.
							esc_html_x( 'Group Leader has access to all %s automatically', 'placeholder: courses', 'learndash' ),
							learndash_get_custom_label_lower( 'courses' )
						),
					),
				),
				'bypass_course_limits'        => array(
					'name'      => 'bypass_course_limits',
					'type'      => 'checkbox-switch',
					'label'     => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Bypass %s limits', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'help_text' => sprintf(
						// translators: placeholder:  course.
						esc_html_x( 'Allow group leader users to access %s content in any order bypassing progression and access limitations', 'placeholder: course', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'value'     => $this->setting_option_values['bypass_course_limits'],
					'options'   => array(
						''    => esc_html__( 'Group Leader must follow the progression and access rules', 'learndash' ),
						'yes' => sprintf(
							// translators: placeholder:  course.
							esc_html_x( 'Group Leader can access %s content in any order', 'placeholder: course', 'learndash' ),
							learndash_get_custom_label_lower( 'course' )
						),
					),
				),

				/*
				'manage_html_notice' => array(
					'name' => 'manage_html_notice',
					'type' => 'html',
					'label' => '',
					'value' => 'Activating these options can interfere with customized user roles. If you have customized user roles, please see <link> before proceeding.',
					'label_full' => true,
					'input_full'  => true,
				),
				*/

				'manage_groups_enabled'       => array(
					'name'                => 'manage_groups_enabled',
					'type'                => 'checkbox-switch',
					'label'               => sprintf(
						// translators: placeholder: Groups.
						esc_html_x( 'Manage %s', 'placeholder: Groups', 'learndash' ),
						learndash_get_custom_label( 'groups' )
					),
					'help_text'           => sprintf(
						// translators: placeholder: groups.
						esc_html_x( 'Allow group leader to create and manage %s.', 'placeholder: groups', 'learndash' ),
						learndash_get_custom_label_lower( 'groups' )
					),
					'value'               => $this->setting_option_values['manage_groups_enabled'],
					'options'             => array(
						'yes' => '',
					),
					'child_section_state' => ( 'yes' === $this->setting_option_values['manage_groups_enabled'] ) ? 'open' : 'closed',
				),
				'manage_groups_capabilities'  => array(
					'name'           => 'manage_groups_capabilities',
					'type'           => 'radio',
					'label'          => '',
					'value'          => $this->setting_option_values['manage_groups_capabilities'],
					'options'        => array(
						'basic'    => array(
							'label'       => esc_html__( 'Basic', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: groups.
								esc_html_x( 'Group Leader can only create, manage, and delete his / her own %s only.', 'placeholder: Groups', 'learndash' ),
								learndash_get_custom_label_lower( 'groups' )
							),
						),
						'advanced' => array(
							'label'       => esc_html__( 'Advanced', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: groups.
								esc_html_x( 'Group Leader can create, manage and delete ANY groups on the site.', 'placeholder: Groups', 'learndash' ),
								learndash_get_custom_label_lower( 'groups' )
							),
						),
					),
					'parent_setting' => 'manage_groups_enabled',
				),

				'manage_courses_enabled'      => array(
					'name'                => 'manage_courses_enabled',
					'type'                => 'checkbox-switch',
					'label'               => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( 'Manage %s', 'placeholder: Courses', 'learndash' ),
						learndash_get_custom_label( 'courses' )
					),
					'help_text'           => sprintf(
						// translators: placeholder: courses.
						esc_html_x( 'Allow group leader to create and manage %s.', 'placeholder: courses', 'learndash' ),
						learndash_get_custom_label_lower( 'courses' )
					),
					'value'               => $this->setting_option_values['manage_courses_enabled'],
					'options'             => array(
						'yes' => '',
					),
					'child_section_state' => ( 'yes' === $this->setting_option_values['manage_courses_enabled'] ) ? 'open' : 'closed',
				),
				'manage_courses_capabilities' => array(
					'name'           => 'manage_courses_capabilities',
					'type'           => 'radio',
					'label'          => '',
					'value'          => $this->setting_option_values['manage_courses_capabilities'],
					'options'        => array(
						'basic'    => array(
							'label'       => esc_html__( 'Basic', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: courses.
								esc_html_x( 'Group Leader can only create, manage, and delete his / her own %s only.', 'placeholder: courses', 'learndash' ),
								learndash_get_custom_label_lower( 'courses' )
							),
						),
						'advanced' => array(
							'label'       => esc_html__( 'Advanced', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: courses.
								esc_html_x( 'Group Leader can create, manage and delete ANY %s on the site.', 'placeholder: courses', 'learndash' ),
								learndash_get_custom_label_lower( 'courses' )
							),
						),
					),
					'parent_setting' => 'manage_courses_enabled',
				),

				'manage_users_enabled'        => array(
					'name'                => 'manage_users_enabled',
					'type'                => 'checkbox-switch',
					'label'               => esc_html__( 'Manage Users', 'learndash' ),
					'help_text'           => esc_html__( 'Allow group leader to create and manage users.', 'learndash' ),
					'value'               => $this->setting_option_values['manage_users_enabled'],
					'options'             => array(
						'yes' => '',
					),
					'child_section_state' => ( 'yes' === $this->setting_option_values['manage_users_enabled'] ) ? 'open' : 'closed',
				),
				'manage_users_capabilities'   => array(
					'name'           => 'manage_users_capabilities',
					'type'           => 'radio',
					'label'          => '',
					'value'          => $this->setting_option_values['manage_users_capabilities'],
					'options'        => array(
						'basic'    => array(
							'label'       => esc_html__( 'Basic', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: courses.
								esc_html_x( 'Group Leader can only manage users within his / her assigned %s only.', 'placeholder: courses', 'learndash' ),
								learndash_get_custom_label_lower( 'courses' )
							),
						),
						'advanced' => array(
							'label'       => esc_html__( 'Advanced', 'learndash' ),
							'description' => esc_html__( 'Group Leader can create, manage and delete ANY users on the site.', 'learndash' ),
						),
					),
					'parent_setting' => 'manage_users_enabled',
				),
			);

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}

		/**
		 * Intercept the WP options save logic and check that we have a valid nonce.
		 *
		 * @param array  $value Array of section fields values.
		 * @param array  $old_value Array of old values.
		 * @param string $section_key Section option key should match $this->setting_option_key.
		 */
		public function section_pre_update_option( $new_values = '', $old_values = '', $setting_option_key = '' ) {
			global $wpdb;

			$group_leader_role = get_role( 'group_leader' );
			if ( is_null( $group_leader_role ) ) {
				return;
			}

			if ( $setting_option_key === $this->setting_option_key ) {

				// First make sure we have all the needed value keys.
				foreach ( $this->setting_option_fields as $setting_key => $setting_field ) {
					if ( ! isset( $new_values[ $setting_key ] ) ) {
						$new_values[ $setting_key ] = '';
					}

					if ( ! isset( $old_values[ $setting_key ] ) ) {
						$old_values[ $setting_key ] = '';
					}
				}

				$new_values = parent::section_pre_update_option( $new_values, $old_values, $setting_option_key );

				foreach ( array( 'groups', 'courses', 'users' ) as $type ) {
					if ( ( $new_values[ 'manage_' . $type . '_enabled' ] !== $old_values[ 'manage_' . $type . '_enabled' ] ) || ( 'yes' === $new_values[ 'manage_' . $type . '_enabled' ] ) ) {
						if ( 'yes' !== $new_values[ 'manage_' . $type . '_enabled' ] ) {
							$new_values[ 'manage_' . $type . '_capabilities' ] = 'basic';
							foreach ( $this->role_caps[ 'manage_' . $type . '_capabilities' ]['basic'] as $cap ) {
								$group_leader_role->add_cap( $cap, false );
							}

							foreach ( $this->role_caps[ 'manage_' . $type . '_capabilities' ]['advanced'] as $cap ) {
								$group_leader_role->add_cap( $cap, false );
							}
						} elseif ( 'yes' === $new_values[ 'manage_' . $type . '_enabled' ] ) {
							foreach ( $this->role_caps[ 'manage_' . $type . '_capabilities' ]['basic'] as $cap ) {
								$group_leader_role->add_cap( $cap, true );
							}

							foreach ( $this->role_caps[ 'manage_' . $type . '_capabilities' ]['advanced'] as $cap ) {
								if ( 'advanced' === $new_values[ 'manage_' . $type . '_capabilities' ] ) {
									$group_leader_role->add_cap( $cap, true );
								} else {
									$group_leader_role->add_cap( $cap, false );
								}
							}
						}
					}
				}

				/**
				 * Filters to bypass Group Leader meta update.
				 *
				 * @since 3.1.7
				 *
				 * @param boolean $process_users True to process used. Else false.
				 */
				if ( apply_filters( 'learndash_update_existing_group_leader_users', true ) ) {
					$gl_user_query_args = array(
						'role'         => 'group_leader',
						'number'       => 500,
						'meta_key'     => $wpdb->prefix . 'user_level',
						'meta_compare' => '=',
					);

					if ( ( 'yes' === $new_values['manage_groups_enabled'] ) || ( 'yes' === $new_values['manage_courses_enabled'] ) ) {
						$gl_user_query_args['meta_value'] = 0;
					} else {
						$gl_user_query_args['meta_value'] = 1;
					}

					$wp_user_query = new WP_User_Query( $gl_user_query_args );

					$users = $wp_user_query->get_results();
					if ( ! empty( $users ) ) {
						foreach ( $users as $user ) {
							if ( 0 === $gl_user_query_args['meta_value'] ) {
								update_user_meta( $user->ID, $wpdb->prefix . 'user_level', '1' );
							} else {
								update_user_meta( $user->ID, $wpdb->prefix . 'user_level', '0' );
							}
						}
					}
				}
			}

			return $new_values;
		}

		/**
		 * Add Header and description on email sections.
		 */
		public function learndash_settings_row_outside_before( $content = '', $field_args = array() ) {
			if ( ( isset( $field_args['name'] ) ) && ( in_array( $field_args['name'], array( 'manage_groups_enabled' ) ) ) ) {

				$content .= '<div class="ld-settings-info-banner ld-settings-info-banner-alert">';

				$message = sprintf(
					// translators: placeholder: anchor to support page and label.
					esc_html_x( 'Activating these options can interfere with customized user roles. If you have customized user roles, please review our support %s before proceeding.', 'placeholder: anchor to support page and label', 'learndash' ),
					'<a target="_blank" href="https://www.learndash.com/support/docs/users-groups/groups/global-group-settings/group-leader-capabilities/">page</a>'
				);
				$content .= wpautop( wptexturize( do_shortcode( $message ) ) );
				$content .= '</div>';
			}
			return $content;
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_Groups_Group_Leader_User::add_section_instance();
	}
);
