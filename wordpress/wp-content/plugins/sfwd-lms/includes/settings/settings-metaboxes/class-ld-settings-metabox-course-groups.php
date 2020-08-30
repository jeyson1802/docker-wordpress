<?php
/**
 * LearnDash Settings Metabox for Course Groups Settings.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Metabox' ) ) && ( ! class_exists( 'LearnDash_Settings_Metabox_Course_Groups_Settings' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Metabox_Course_Groups_Settings extends LearnDash_Settings_Metabox {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-courses';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash-course-groups';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Course, Groups.
				esc_html_x( '%1$s %2$s', 'placeholder: Course, Groups', 'learndash' ),
				learndash_get_custom_label( 'course' ),
				learndash_get_custom_label( 'groups' )
			);

			parent::__construct();
		}

		protected function show_settings_metabox_fields( $metabox = null ) {
			if ( ( is_object( $metabox ) ) && ( is_a( $metabox, 'LearnDash_Settings_Metabox' ) ) && ( $metabox->settings_metabox_key === $this->settings_metabox_key ) ) {
				if ( ( isset( $metabox->post ) ) && ( is_a( $metabox->post, 'WP_Post ' ) ) ) {
					$course_id = $metabox->post->ID;
				} else {
					$course_id = get_the_ID();
				}

				if ( ( ! empty( $course_id ) ) && ( get_post_type( $course_id ) === learndash_get_post_type_slug( 'course' ) ) ) {

					$metabox_description = '';

					// Use nonce for verification.
					wp_nonce_field( 'learndash_course_groups_nonce_' . $course_id, 'learndash_course_groups_nonce' );

					if ( ! empty( $metabox_description ) ) {
						$metabox_description .= ' ';
					}

					$metabox_description .= sprintf(
						// translators: placeholder: Groups, Course.
						esc_html_x( 'Users enrolled via %1$s using this %2$s are excluded from the listings below and should be manage via the Group admin screen.', 'placeholder: Groups, Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'groups' ),
						LearnDash_Custom_Label::get_label( 'course' )
					);
					?>
					<div id="learndash_course_users_page_box" class="learndash_course_users_page_box">
					<?php
					if ( ! empty( $metabox_description ) ) {
						echo wpautop( wp_kses_post( $metabox_description ) );
					}

					$ld_binary_selector_course_groups = new Learndash_Binary_Selector_Course_Groups(
						array(
							'html_title'            => '',
							'course_id'             => $course_id,
							'selected_ids'          => learndash_get_course_groups( $course_id, true ),
							'search_posts_per_page' => 100,
						)
					);
					$ld_binary_selector_course_groups->show();
					?>
					</div>
					<?php
				}
			}
		}

		/**
		 * Save Settings Metabox
		 *
		 * @param integer $post_id $Post ID is post being saved.
		 * @param object  $saved_post WP_Post object being saved.
		 * @param boolean $update If update true, otherwise false.
		 * @param array   $settings_field_updates array of settings fields to update.
		 */
		public function save_post_meta_box( $post_id = 0, $saved_post = null, $update = null, $settings_field_updates = null ) {
			if ( ( isset( $_POST['learndash_course_groups_nonce'] ) ) && ( wp_verify_nonce( $_POST['learndash_course_groups_nonce'], 'learndash_course_groups_nonce_' . $post_id ) ) ) {
				if ( ( isset( $_POST['learndash_course_groups'] ) ) && ( isset( $_POST['learndash_course_groups'][ $post_id ] ) ) && ( ! empty( $_POST['learndash_course_groups'][ $post_id ] ) ) ) {
					$course_groups = (array) json_decode( stripslashes( $_POST['learndash_course_groups'][ $post_id ] ) );
					learndash_set_course_groups( $post_id, $course_groups );
				}
			}
		}

		// End of functions.
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'course' ),
		function( $metaboxes = array() ) {
			if ( ( ! isset( $metaboxes['LearnDash_Settings_Metabox_Course_Groups_Settings'] ) ) && ( class_exists( 'LearnDash_Settings_Metabox_Course_Groups_Settings' ) ) ) {
				$metaboxes['LearnDash_Settings_Metabox_Course_Groups_Settings'] = LearnDash_Settings_Metabox_Course_Groups_Settings::add_metabox_instance();
			}

			return $metaboxes;
		},
		50,
		1
	);
}
