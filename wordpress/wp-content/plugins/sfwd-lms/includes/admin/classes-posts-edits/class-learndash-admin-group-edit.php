<?php
/**
 * LearnDash Admin Group Edit Class.
 *
 * @package LearnDash
 * @subpackage Admin
 */

if ( ( class_exists( 'Learndash_Admin_Post_Edit' ) ) && ( ! class_exists( 'Learndash_Admin_Group_Edit' ) ) ) {
	/**
	 * Class for LearnDash Admin Group Edit.
	 */
	class Learndash_Admin_Group_Edit extends Learndash_Admin_Post_Edit {

		/**
		 * Object level variable for current Group ID being edited.
		 *
		 * @var integer $group_id
		 */
		private $group_id = 0;

		/**
		 * Public constructor for class.
		 */
		public function __construct() {
			$this->post_type = learndash_get_post_type_slug( 'group' );
			parent::__construct();
		}

		/**
		 * On Load handler function for this post type edit.
		 * This function is called by a WP action when the admin
		 * page 'post.php' or 'post-new.php' are loaded.
		 */
		public function on_load() {
			if ( $this->post_type_check() ) {

				require_once LEARNDASH_LMS_PLUGIN_DIR . '/includes/settings/settings-metaboxes/class-ld-settings-metabox-group-display-content.php';
				require_once LEARNDASH_LMS_PLUGIN_DIR . '/includes/settings/settings-metaboxes/class-ld-settings-metabox-group-access-settings.php';

				require_once LEARNDASH_LMS_PLUGIN_DIR . '/includes/settings/settings-metaboxes/class-ld-settings-metabox-group-users.php';
				require_once LEARNDASH_LMS_PLUGIN_DIR . '/includes/settings/settings-metaboxes/class-ld-settings-metabox-group-leaders.php';

				/** This filter is documented in includes/admin/class-learndash-admin-menus-tabs.php */
				if ( true === apply_filters( 'learndash_show_metabox_group_courses', true ) ) {
					require_once LEARNDASH_LMS_PLUGIN_DIR . '/includes/settings/settings-metaboxes/class-ld-settings-metabox-group-courses.php';
					require_once LEARNDASH_LMS_PLUGIN_DIR . '/includes/settings/settings-metaboxes/class-ld-settings-metabox-group-courses-enroll.php';
				}

				parent::on_load();

				$this->_metaboxes = apply_filters( 'learndash_post_settings_metaboxes_init_' . $this->post_type, $this->_metaboxes );
			}
		}

		/**
		 * Register Groups meta box for admin
		 * Managed enrolled groups, users and group leaders
		 *
		 * @since 2.1.2
		 * @param string $post_type Port Type being edited.
		 */
		public function add_metaboxes( $post_type = '', $post = null ) {

			if ( $this->post_type_check( $post_type ) ) {
				parent::add_metaboxes( $post_type );
			}

			add_meta_box(
				'learndash_group_attributes_metabox',
				sprintf(
					// translators: placeholder: Group.
					esc_html_x( '%s Attributes', 'placeholder: Group', 'learndash' ),
					learndash_get_custom_label( 'group' )
				),
				array( $this, 'group_attributes_metabox_content' ),
				learndash_get_post_type_slug( 'group' ),
				'side'
			);
		}

		public function group_attributes_metabox_content( $post, $something = '' ) {
			if ( is_post_type_hierarchical( $post->post_type ) ) {
				$dropdown_args = array(
					'post_type'        => $post->post_type,
					'exclude_tree'     => $post->ID,
					'selected'         => $post->post_parent,
					'name'             => 'group_parent_id',
					'show_option_none' => __( '(no parent)', 'learndash' ),
					'sort_column'      => 'menu_order, post_title',
					'echo'             => 0,
				);

				$groups         = wp_dropdown_pages( $dropdown_args );
				if ( ! empty( $groups ) ) {
					?>
					<p class="post-attributes-label-wrapper group-parent-id-label-wrapper"><label class="post-attributes-label" for="group_parent_id"><?php echo sprintf(
						// translators: placeholder: Group.
						esc_html_x( '%s Parent', 'placeholder: Group', 'learndash' ),
						learndash_get_custom_label( 'group' )
					); ?></label></p>
					<?php echo $groups; ?>
					<?php
				}
			}

			?>
			<p class="post-attributes-label-wrapper group-menu-order-label-wrapper"><label class="post-attributes-label" for="group_menu_order"><?php _e( 'Order' ); ?></label></p>
			<input name="group_menu_order" type="text" size="4" id="group_menu_order" value="<?php echo esc_attr( $post->menu_order ); ?>" />
			<?php
		}

		/**
		 * Save metabox handler function.
		 *
		 * @param integer $post_id Post ID Question being edited.
		 * @param object  $post WP_Post Question being edited.
		 * @param boolean $update If update true, else false.
		 */
		public function save_post( $post_id = 0, $post = null, $update = false ) {
			if ( ! $this->post_type_check( $post ) ) {
				return false;
			}

			if ( ! parent::save_post( $post_id, $post, $update ) ) {
				return false;
			}

			if ( ! empty( $this->_metaboxes ) ) {
				foreach ( $this->_metaboxes as $_metaboxes_instance ) {
					$settings_fields = array();
					$settings_fields = $_metaboxes_instance->get_post_settings_field_updates( $post_id, $post, $update );
					$_metaboxes_instance->save_post_meta_box( $post_id, $post, $update, $settings_fields );
				}
			}

			$edit_post = array(
				'ID'          => $post->ID,
				'post_parent' => $post->post_parent,
				'menu_order'  => $post->menu_order,
			);

			$updated_post = false;
			if ( isset( $_POST['group_parent_id'] ) ) {
				$updated_post = true;
				$edit_post['post_parent'] = absint( $_POST['group_parent_id'] );
			}

			if ( isset( $_POST['group_menu_order'] ) ) {
				$updated_post = true;
				$edit_post['menu_order'] = absint( $_POST['group_menu_order'] );
			}

			if ( true === $updated_post ) {
				wp_update_post( $edit_post );
			}

			$group_leaders = [];
			$group_users   = [];
			$group_courses = [];

			/**
			 * Fires after the group post data is updated.
			 *
			 * @deprecated 3.1.7 No replacement.
			 * @param integer $post_id       Post ID of the group
			 * @param array   $group_leaders An array of group leaders.
			 * @param array   $group_users   An array of group users.
			 * @param array   $group_courses An array of group courses.
			 */
			do_action_deprecated( 'ld_group_postdata_updated', [$post_id, $group_leaders, $group_users, $group_courses], '3.1.7' );
		}

		// End of functions.
	}
}
new Learndash_Admin_Group_Edit();
