<?php
/**
 * LearnDash Group Membership.
 *
 * @package LearnDash
 * @subpackage Groups
 */

if ( ! class_exists( 'LD_Groups_Membership' ) ) {
	/**
	 * Class to create the instance.
	 */
	class LD_Groups_Membership {
		/**
		 * Static instance variable to ensure
		 * only one instance of class is used.
		 *
		 * @since 1.0.0
		 *
		 * @var object $instance
		 */
		protected static $instance = null;

		/**
		 * Group Membership metabox instance.
		 *
		 * @var object $mb_instance
		 */
		protected $mb_instance = null;

		/**
		 * Group Membership settings.
		 *
		 * @var array $global_setting
		 */
		protected $global_setting = null;

		/**
		 * Group Membership Post settings.
		 *
		 * @var array $post_setting
		 */
		protected $post_setting = null;

		/**
		 * Array of runtime vars.
		 *
		 * Includes post_id, post, user_id, user, debug
		 */
		protected $vars = array();

		/**
		 * Get or create instance object of class.
		 *
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( ! isset( static::$instance ) ) {
				static::$instance = new static();
			}

			return static::$instance;
		}

		/**
		 * Public constructor for class
		 *
		 * @since 1.0.0
		 */
		protected function __construct() {
			add_action( 'load-post.php', array( $this, 'on_load' ) );
			add_action( 'load-post-new.php', array( $this, 'on_load' ) );
			add_filter( 'the_content', array( $this, 'the_content_filter' ), 99 );
		}

		/**
		 * Get Group Membership post metabox instance.
		 */
		protected function get_metabox_instance() {
			if ( is_null( $this->mb_instance ) ) {
				require_once LEARNDASH_LMS_PLUGIN_DIR . '/includes/settings/settings-metaboxes/class-ld-settings-metabox-group-membership-post-settings.php';
				$this->mb_instance = LearnDash_Settings_Metabox_Group_Membership_Post_Settings::add_metabox_instance();
			}

			return $this->mb_instance;
		}

		/**
		 * Initialize runtime vars.
		 */
		protected function init_vars() {
			$this->vars['post_id'] = get_the_ID();
			if ( ! empty( $this->vars['post_id'] ) ) {
				$this->vars['post'] = get_post( $this->vars['post_id'] );
			}

			if ( is_user_logged_in() ) {
				$this->vars['user_id'] = get_current_user_id();
				if ( ! empty( $this->vars['user_id'] ) ) {
					$this->vars['user'] = get_user_by( 'ID', $this->vars['user_id'] );
				}
			} else {
				$this->vars['user_id'] = 0;
			}

			if ( ( ! is_admin() ) && ( isset( $_GET['ld_debug'] ) ) ) {
				$this->vars['debug'] = true;
			} else {
				$this->vars['debug'] = false;
			}

			$this->vars['debug_messages'] = array();
		}

		/**
		 * Add debug message to array.
		 *
		 * @param string $message Message text to add.
		 */
		protected function add_debug_message( $message = '' ) {
			if ( ( isset( $this->vars['debug'] ) ) && ( true === $this->vars['debug'] ) ) {
				$this->vars['debug_messages'][] = $message;
			}
		}

		/**
		 * Output debug message.
		 */
		protected function output_debug_messages() {
			if ( ( isset( $this->vars['debug'] ) ) && ( true === $this->vars['debug'] ) && ( ! empty( $this->vars['debug_messages'] ) ) ) {
				echo '<code>';
				echo implode( '<br />', array_map( 'wp_kses_post', $this->vars['debug_messages'] ) );
				echo '<br /></code><br />';
			}
		}

		/**
		 * Load the Groups Membership Global settings
		 */
		protected function init_global_settings() {
			if ( is_null( $this->global_setting ) ) {
				$this->global_setting = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Groups_Membership' );
			}

			if ( ! isset( $this->global_setting['groups_membership_enabled'] ) ) {
				$this->global_setting['groups_membership_enabled'] = '';
			}

			if ( ! isset( $this->global_setting['groups_membership_message'] ) ) {
				$this->global_setting['groups_membership_message'] = '';
			}

			if ( ! isset( $this->global_setting['groups_membership_post_types'] ) ) {
				$this->global_setting['groups_membership_post_types'] = array();
			}

			if ( ! isset( $this->global_setting['groups_membership_user_roles'] ) ) {
				$this->global_setting['groups_membership_user_roles'] = array();
			}
		}

		/**
		 * Get the managed membership post types.
		 */
		protected function get_global_included_post_types() {
			$included_post_types = array();

			$this->init_global_settings();

			if ( ! empty( $this->global_setting['groups_membership_enabled'] ) ) {
				if ( ( is_array( $this->global_setting['groups_membership_post_types'] ) ) && ( ! empty( $this->global_setting['groups_membership_post_types'] ) ) ) {
					$included_post_types = $this->global_setting['groups_membership_post_types'];
				}
			}

			return $included_post_types;
		}

		/**
		 * Get Group Membership excluded user roles.
		 */
		protected function get_excluded_user_roles() {
			$excluded_user_roles = array();

			$this->init_global_settings();

			if ( ! empty( $this->global_setting['groups_membership_enabled'] ) ) {
				if ( ( is_array( $this->global_setting['groups_membership_user_roles'] ) ) && ( ! empty( $this->global_setting['groups_membership_user_roles'] ) ) ) {
					$excluded_user_roles = $this->global_setting['groups_membership_user_roles'];
				}
			}

			return $excluded_user_roles;
		}

		/**
		 * Get Group Membership access denied message.
		 */
		protected function get_access_denied_message() {
			static $inline_css_loaded = false;

			$access_denied_message = '';

			$this->init_global_settings();

			if ( ! empty( $this->global_setting['groups_membership_enabled'] ) ) {
				$access_denied_message = $this->global_setting['groups_membership_message'];

				if ( ( learndash_is_active_theme( 'ld30' ) ) && ( function_exists( 'learndash_get_template_part' ) ) ) {

					/**
					 * Filter to show alert message box used in LD30 templates.
					 *
					 * @since 3.2.0
					 *
					 * @param boolean $show_alert true.
					 * @param int     $post_id    Current Post ID.
					 * @param int     $user_id    Current User ID.
					 * @return boolean True to process template. Anything else to abort.
					 */
					if ( true === apply_filters( 'learndash_group_membership_access_denied_show_ld30_alert', true, $this->vars['post_id'], $this->vars['user_id'] ) ) {
						// Save for next release to load if needed.
						//$theme_template_dir = LearnDash_Theme_Register::get_active_theme_base_dir();
						// $css_front_file = $theme_template_dir . '/assets/css/learndash' . leardash_min_asset() . '.css';
						// $css_front_file_content = file_get_contents( $css_front_file );
						// if ( ! empty( $css_front_file_content ) ) {

						if ( false === $inline_css_loaded ) {
							$inline_css_loaded = true;
							$css_front_file_content = '.learndash-wrapper .ld-alert a.ld-button.learndash-group-membership-link { text-decoration: none !important; }';
							wp_add_inline_style( 'learndash-front-group-membership', $css_front_file_content );
						}

						$alert = array(
							'icon'    => 'alert',
							'message' => $access_denied_message,
							'type'    => 'warning',
						);

						if ( ( 1 === count( $this->post_setting['groups_membership_groups'] ) ) && ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_CPT', 'public' ) ) ) {

							$alert['button'] = array(
								'url'   => get_permalink( $this->post_setting['groups_membership_groups'][0] ),
								'class' => 'learndash-link-previous-incomplete learndash-group-membership-link',
								'label' => sprintf(
									// translators: placeholder: Group.
									esc_html_x( 'View %s', 'placeholder: Group', 'learndash' ),
									learndash_get_custom_label( 'group' )
								),
							);
						}

						$access_denied_message = learndash_get_template_part( 'modules/alert.php', $alert, false );
						$access_denied_message = '<div class="learndash-wrapper">' . $access_denied_message . '</div>';
					}
				}
			}

			return $access_denied_message;
		}

		/**
		 * Get Group Membership Post metabox setting
		 *
		 * @param integer $pot_id Post ID to get settings for.
		 * @return array of settings.
		 */
		protected function init_post_settings( $post_id = 0 ) {
			$this->post_setting = learndash_get_post_group_membership_settings( $post_id );
			return $this->post_setting;
		}

		/**
		 * Get the managed membership post groups.
		 *
		 * @param integer $pot_id Post ID to get settings for.
		 * @return array of post groups.
		 */
		protected function get_post_included_groups( $post_id = 0 ) {
			$included_post_groups = array();

			if ( ! empty( $post_id ) ) {
				$this->init_post_settings( $post_id );

				if ( ! empty( $this->post_setting['groups_membership_enabled'] ) ) {
					if ( ( is_array( $this->post_setting['groups_membership_groups'] ) ) && ( ! empty( $this->post_setting['groups_membership_groups'] ) ) ) {
						$included_post_groups = $this->post_setting['groups_membership_groups'];
					}
				}
			}

			$this->add_debug_message( __FUNCTION__ . ': post_id [' . $post_id . '] post included groups [' . implode( ', ', $included_post_groups ) . ']' );

			return $included_post_groups;
		}

		/**
		 * Get the managed membership post groups compare.
		 *
		 * @param integer $pot_id Post ID to get settings for.
		 * @return array of post groups.
		 */
		protected function get_post_groups_compare( $post_id = 0 ) {
			$post_groups_compare = '';

			if ( ! empty( $post_id ) ) {
				$this->init_post_settings( $post_id );

				if ( ! empty( $this->post_setting['groups_membership_enabled'] ) ) {
					$post_groups_compare = $this->post_setting['groups_membership_compare'];
				}
			}
			$this->add_debug_message( __FUNCTION__ . ': post_id [' . $post_id . '] post groups compare[' . $post_groups_compare . ']' );

			return $post_groups_compare;
		}

		/**
		 * Check if post type is managed by membership logic.
		 *
		 * @param string $post_type Post type slug to check.
		 */
		protected function is_included_post_type( $post_type = '' ) {
			if ( ( ! empty( $post_type ) ) && ( in_array( $post_type, $this->get_global_included_post_types(), true ) ) ) {
				$this->add_debug_message( __FUNCTION__ . ': post_type [' . $post_type . '] is included.' );
				return true;
			}
			$this->add_debug_message( __FUNCTION__ . ': post_type [' . $post_type . '] NOT included.' );
		}

		/**
		 * Check if user_role is excluded by membership logic.
		 *
		 * @param integer $user_id User ID.
		 */
		protected function is_excluded_user_role( $user_id = 0 ) {
			$this->add_debug_message( __FUNCTION__ . ': user_id [' . $user_id . '] ' );
			if ( ! empty( $user_id ) ) {
				$user = get_user_by( 'ID', $user_id );
				if ( ( is_object( $user ) ) && ( property_exists( $user, 'roles' ) ) && ( ! empty( $user->roles ) ) ) {
					$user_roles          = array_map( 'esc_attr', $user->roles );
					$excluded_user_roles = $this->get_excluded_user_roles();
					$excluded_user_roles = array_map( 'esc_attr', $excluded_user_roles );
					if ( ! empty( $excluded_user_roles ) ) {
						$this->add_debug_message( __FUNCTION__ . ': user_roles [' . implode( ', ', $user_roles ) . '] excluded_roles [' . implode( ', ', $excluded_user_roles ) . ']' );
						if ( array_intersect( $user_roles, $excluded_user_roles ) ) {
							$this->add_debug_message( __FUNCTION__ . ': user role excluded.' );
							return true;
						}
						$this->add_debug_message( __FUNCTION__ . ': user role NOT excluded.' );
					}
				}
			}
		}

		/**
		 * Check if Post is enabled and if the post type is included in the global settings.
		 *
		 * @param integer $post_id Post ID
		 * @return boolean
		 */
		protected function is_post_blocked( $post_id = 0 ) {
			$this->add_debug_message( __FUNCTION__ . ': post_id [' . $post_id . ']' );

			if ( is_preview() || is_admin() ) {
				$this->add_debug_message( __FUNCTION__ . ': is_preview or is_admin true. aborting.' );
				return false;
			}

			if ( ! empty( $post_id ) ) {
				$this->init_post_settings( $post_id );

				if ( $this->is_included_post_type( get_post_type( $post_id ) ) ) {
					if ( ( ! empty( $this->post_setting['groups_membership_enabled'] ) ) && ( ! empty( $this->post_setting['groups_membership_groups'] ) ) ) {
						$this->add_debug_message( __FUNCTION__ . ': post type [' . get_post_type( $post_id ) . '] is under membership control.' );
						return true;
					}
				}
				$this->add_debug_message( __FUNCTION__ . ': post type [' . get_post_type( $post_id ) . '] not under membership control. bypased' );
			}
		}

		/**
		 * Check if User enrolled groups against Post and Membership settings.
		 *
		 * @param integer $post_id Post ID
		 * @param integer $user_id USer ID
		 * @return boolean
		 */
		protected function is_user_blocked( $post_id = 0, $user_id = 0 ) {
			if ( ! empty( $post_id ) ) {
				$this->init_post_settings( $post_id );

				if ( ! empty( $user_id ) ) {
					if ( $this->is_excluded_user_role( $user_id ) ) {
						$this->add_debug_message( __FUNCTION__ . ': user role excluded. bypassed.' );
						return false;
					} else {
						$this->add_debug_message( __FUNCTION__ . ': user role not excluded. blocked.' );
					}

					if ( $this->is_user_in_post_groups( $post_id, $user_id ) ) {
						$this->add_debug_message( __FUNCTION__ . ': user in post groups. bypassed.' );
						return false;
					} else {
						$this->add_debug_message( __FUNCTION__ . ': user not in post groups. blocked.' );
					}
				} else {
					$post_groups = $this->get_post_included_groups( $post_id );
					if ( empty( $post_groups ) ) {
						$this->add_debug_message( __FUNCTION__ . ': empty post groups. bypassed.' );
						return false;
					} else {
						$this->add_debug_message( __FUNCTION__ . ': empty user. post groups exists. blocked.' );
					}
				}

				return true;
			}
		}

		/**
		 * Check if user if in the associated post membership groups.
		 *
		 * @param integer $post_id Post ID.
		 * @param integer $user_id User ID.
		 */
		protected function is_user_in_post_groups( $post_id = 0, $user_id = 0 ) {
			if ( ( ! empty( $user_id ) ) && ( ! empty( $post_id ) ) ) {
				$this->init_post_settings( $post_id );

				$post_groups = $this->get_post_included_groups( $post_id );
				$post_groups = array_map( 'absint', $post_groups );
				if ( ! empty( $post_groups ) ) {
					$user_groups = learndash_get_users_group_ids( $user_id );
					$user_groups = array_map( 'absint', $user_groups );
					if ( ! empty( $user_groups ) ) {
						$groups_compare = $this->get_post_groups_compare( $post_id );

						$common_groups = array_intersect( $user_groups, $post_groups );
						if ( 'ANY' === $groups_compare ) {
							if ( ! empty( $common_groups ) ) {
								$this->add_debug_message( __FUNCTION__ . ': user is in ANY groups.' );
								return true;
							}
							$this->add_debug_message( __FUNCTION__ . ': user not in ANY groups.' );
						} elseif ( 'ALL' === $groups_compare ) {
							if ( empty( array_diff( $common_groups, $post_groups ) ) && empty( array_diff( $post_groups, $common_groups ) ) ) {
								$this->add_debug_message( __FUNCTION__ . ': user is in ALL groups.' );
								return true;
							}
							$this->add_debug_message( __FUNCTION__ . ': user not in ALL groups.' );
						}
					} else {
						$this->add_debug_message( __FUNCTION__ . ': user groups empty.' );
					}
				}
			}
		}

		/**
		 * Called when the Post is Added or Edited.
		 */
		public function on_load() {
			global $typenow;

			if ( $this->is_included_post_type( $typenow ) ) {
				add_action( 'save_post', array( $this, 'save_post' ), 10, 3 );
				$this->get_metabox_instance();
			}
		}

		/**
		 * Called when the Post is Saved.
		 *
		 * @param integer $post_id Post ID.
		 * @param object  $post    WP_Post instance.
		 * @param boolean $update  If update to post.
		 */
		public function save_post( $post_id = 0, $post = null, $update = null ) {
			if ( $this->is_included_post_type( $post->post_type ) ) {
				$mb_instance = $this->get_metabox_instance();
				$mb_instance->save_post_meta_box( $post_id, $post, $update );
			}

			return true;
		}

		/**
		 * Start the logic to filter the content.
		 *
		 * @param string/HTML $content The Post content.
		 */
		public function the_content_filter( $content ) {
			if ( is_preview() || is_admin() ) {
				return $content;
			}

			$this->init_vars();

			if ( ( ! isset( $this->vars['post'] ) ) || ( ! is_a( $this->vars['post'], 'WP_Post' ) ) ) {
				return $content;
			}

			$post_blocked = $this->is_post_blocked( $this->vars['post_id'] );
			$user_blocked = $this->is_user_blocked( $this->vars['post_id'], $this->vars['user_id'] );

			$this->add_debug_message( __FUNCTION__ . ': post_blocked[' . $post_blocked . '] user_blocked[' . $user_blocked . ']' );

			if ( ( true === $post_blocked ) && ( true === $user_blocked ) ) {
				$this->add_debug_message( __FUNCTION__ . ': blocked.' );
				$this->output_debug_messages();

				return $this->get_access_denied_message();
			} else {
				$this->add_debug_message( __FUNCTION__ . ': not blocked.' );
				$this->output_debug_messages();

				return $content;
			}
		}

		// End of functions.
	}
	add_action(
		'init',
		function() {
			LD_Groups_Membership::get_instance();
		},
		10,
		1
	);
}

/**
 * Utility function to get the post group membership settings.
 *
 * @since 3.2.0
 *
 * @param integer $post_id Post ID.
 * @return array Array of settings.
 */
function learndash_get_post_group_membership_settings( $post_id = 0 ) {
	$learndash_settings = array();

	if ( ! empty( $post_id ) ) {
		$is_hierarchical = is_post_type_hierarchical( get_post_type( $post_id ) );

		$learndash_settings['groups_membership_enabled'] = get_post_meta( $post_id, '_ld_groups_membership_enabled', true );
		$learndash_settings['groups_membership_compare'] = get_post_meta( $post_id, '_ld_groups_membership_compare', true );
		// $learndash_settings['groups_membership_groups']  = get_post_meta( $post_id, '_ld_groups_membership_groups', true );
		$learndash_settings['groups_membership_groups'] = learndash_get_post_group_membership_groups( $post_id );

		if ( ( ! isset( $learndash_settings['groups_membership_enabled'] ) ) || ( 'on' !== $learndash_settings['groups_membership_enabled'] ) ) {
			$learndash_settings['groups_membership_enabled'] = '';
		}

		if ( ! isset( $learndash_settings['groups_membership_compare'] ) ) {
			$learndash_settings['groups_membership_compare'] = 'ANY';
		}

		if ( ! isset( $learndash_settings['groups_membership_groups'] ) ) {
			$learndash_settings['groups_membership_groups'] = array();
		}

		if ( ( 'on' === $learndash_settings['groups_membership_enabled'] ) && ( true === $is_hierarchical ) ) {
			$learndash_settings['groups_membership_children'] = get_post_meta( $post_id, '_ld_groups_membership_children', true );
			if ( ( ! isset( $learndash_settings['groups_membership_children'] ) ) || ( 'on' !== $learndash_settings['groups_membership_children'] ) ) {
				$learndash_settings['groups_membership_children'] = '';
			}
		} else {
			$learndash_settings['groups_membership_children'] = '';
		}

		if ( ( ! empty( $learndash_settings['groups_membership_groups'] ) ) && ( 'on' === $learndash_settings['groups_membership_enabled'] ) ) {
			$learndash_settings['groups_membership_groups'] = learndash_validate_groups( $learndash_settings['groups_membership_groups'] );
			if ( empty( $learndash_settings['groups_membership_groups'] ) ) {
				$learndash_settings['groups_membership_enabled']  = '';
				$learndash_settings['groups_membership_children'] = '';
			}
		} else {
			$learndash_settings['groups_membership_enabled']  = '';
			$learndash_settings['groups_membership_groups']   = array();
			$learndash_settings['groups_membership_children'] = '';
		}

		if ( ( empty( $learndash_settings['groups_membership_enabled'] ) ) && ( true === $is_hierarchical ) ) {
			$parents_post_id = wp_get_post_parent_id( $post_id );
			if ( ! empty( $parents_post_id ) ) {
				$parent_settings = learndash_get_post_group_membership_settings( $parents_post_id );
				if ( ( isset( $parent_settings['groups_membership_enabled'] ) ) && ( 'on' === $parent_settings['groups_membership_enabled'] ) ) {
					if ( ( isset( $parent_settings['groups_membership_children'] ) ) && ( 'on' === $parent_settings['groups_membership_children'] ) ) {
						$parent_settings['groups_membership_parent'] = absint( $parents_post_id );
						$learndash_settings                          = $parent_settings;
					}
				}
			}
		}
	}

	return $learndash_settings;
}

/**
 * Utility function to set the post group membership settings.
 *
 * @since 3.2.0
 *
 * @param integer $post_id  Post ID.
 * @param array   $settings Array of settings.
 *
 * @return array Array of settings.
 */
function learndash_set_post_group_membership_settings( $post_id = 0, $settings = array() ) {
	if ( ! empty( $post_id ) ) {

		$default_settings = array(
			'groups_membership_enabled'  => '',
			'groups_membership_children' => '',
			'groups_membership_compare'  => '',
			'groups_membership_groups'   => array(),
		);

		$settings = wp_parse_args( $settings, $default_settings );

		if ( ! is_array( $settings['groups_membership_groups'] ) ) {
			$settings['groups_membership_groups'] = array();
		} elseif ( ! empty( $settings['groups_membership_groups'] ) ) {
			$settings['groups_membership_groups'] = array_map( 'absint', $settings['groups_membership_groups'] );
		}

		if ( ! empty( $settings['groups_membership_groups'] ) ) {
			$settings['groups_membership_enabled'] = 'on';
		} else {
			$settings['groups_membership_enabled']  = '';
			$settings['groups_membership_children'] = '';
			$settings['groups_membership_compare']  = '';
		}

		foreach ( $settings as $_key => $_val ) {
			if ( 'groups_membership_groups' === $_key ) {
				learndash_set_post_group_membership_groups( $post_id, $_val );
			} else {
				if ( empty( $_val ) ) {
					delete_post_meta( $post_id, '_ld_' . $_key );
				} else {
					update_post_meta( $post_id, '_ld_' . $_key, $_val );
				}
			}
		}
	}
}

/**
 * Get the Groups related to the Post for Group Membership.
 *
 * @since 3.2.0
 *
 * @param integer $post_id Post ID.
 * @return array Array of settings.
 */
function learndash_get_post_group_membership_groups( $post_id = 0 ) {
	$group_ids = array();

	$post_id = absint( $post_id );
	if ( ! empty( $post_id ) ) {
		$post_meta = get_post_meta( $post_id );
		if ( ! empty( $post_meta ) ) {
			foreach ( $post_meta as $meta_key => $meta_set ) {
				if ( '_ld_groups_membership_group_' == substr( $meta_key, 0, strlen( '_ld_groups_membership_group_' ) ) ) {
					$group_id = str_replace( '_ld_groups_membership_group_', '', $meta_key );
					$group_id = absint( $group_id );
					if ( learndash_get_post_type_slug( 'group' ) === get_post_type( $group_id ) ) {
						$group_ids[] = $group_id;
					}
				}
			}
		}
	}

	return $group_ids;
}

/**
 * Set the Groups related to the Post for Group Membership.
 *
 * @since 3.2.0
 *
 * @param int   $post_id    Post ID to update.
 * @param array $groups_new Array of group IDs to set for the Post ID. Can be empty.
 */
function learndash_set_post_group_membership_groups( $post_id = 0, $groups_new = array() ) {
	$post_id = absint( $post_id );
	if ( ! is_array( $groups_new ) ) {
		$groups_new = array();
	} elseif ( ! empty( $groups_new ) ) {
		$groups_new = array_map( 'absint', $groups_new );
	}

	if ( ! empty( $post_id ) ) {

		$groups_old = learndash_get_post_group_membership_groups( $post_id );
		if ( ! is_array( $groups_old ) ) {
			$groups_old = array();
		} elseif ( ! empty( $groups_old ) ) {
			$groups_old = array_map( 'absint', $groups_old );
		}

		$groups_intersect = array_intersect( $groups_new, $groups_old );

		$groups_add = array_diff( $groups_new, $groups_intersect );
		if ( ! empty( $groups_add ) ) {
			foreach ( $groups_add as $group_id ) {
				add_post_meta( $post_id, '_ld_groups_membership_group_' . $group_id, time() );
			}
		}

		$groups_remove = array_diff( $groups_old, $groups_intersect );
		if ( ! empty( $groups_remove ) ) {
			foreach ( $groups_remove as $group_id ) {
				delete_post_meta( $post_id, '_ld_groups_membership_group_' . $group_id );
			}
		}
	}
}

