<?php
/**
 * LearnDash Groups (groups) Posts Listing Class.
 *
 * @package LearnDash
 * @subpackage admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'Learndash_Admin_Posts_Listing' ) ) && ( ! class_exists( 'Learndash_Admin_Groups_Listing' ) ) ) {
	/**
	 * Class for LearnDash Groups Listing Pages.
	 */
	class Learndash_Admin_Groups_Listing extends Learndash_Admin_Posts_Listing {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->post_type = 'groups';

			parent::__construct();
		}

		/**
		 * Call via the WordPress load sequence for admin pages.
		 */
		public function on_load_edit() {
			global $typenow, $post;

			if ( ( empty( $typenow ) ) || ( $typenow !== $this->post_type ) ) {
				return;
			}

			$this->post_type_selectors = array(
				'course_id' => array(
					'query_args'     => array(
						'post_type' => learndash_get_post_type_slug( 'course' ),
					),
					'query_arg'      => 'course_id',
					'selected'       => ( isset( $_GET['course_id'] ) ) ? absint( $_GET['course_id'] ) : 0,
					'field_name'     => 'course_id',
					'field_id'       => 'course_id',
					'show_all_value' => '',
					'show_all_label' => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( 'Show All %s', 'placeholder: Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					),
					'lazy_load'      => true,
				),
			);

			$this->columns['groups_courses_users'] = sprintf(
				// translators: placeholder: Courses.
				esc_html_x( '%s / Users', 'placeholder: Courses', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'courses' )
			);

			parent::on_load_edit();
		}

		/**
		 * Display Group columns.
		 *
		 * @param string  $column_name Column being displayed.
		 * @param integer $group_id ID of Group (post) being displayed.
		 */
		public function manage_column_rows( $column_name = '', $group_id = 0 ) {
			switch ( $column_name ) {

				case 'groups_courses_users':
					// Group Users.
					$group_users = learndash_get_groups_user_ids( $group_id );
					if ( ( empty( $group_users ) ) || ( ! is_array( $group_users ) ) ) {
						$group_users = array();
					}

					echo sprintf(
						// translators: placeholder: Group Users Count.
						esc_html_x( 'Users: %d', 'placeholder: Group Users Count', 'learndash' ),
						count( $group_users )
					);
					echo '<br />';

					// Group Courses.
					$group_courses = learndash_group_enrolled_courses( $group_id );
					if ( ( empty( $group_courses ) ) || ( ! is_array( $group_courses ) ) ) {
						$group_courses = array();
					}

					echo sprintf(
						// translators: placeholder: Goup Courses Count.
						esc_html_x( '%1$s: %2$d', 'placeholders: Courses, Group Courses Count', 'learndash' ),
						esc_html( learndash_get_custom_label( 'courses' ) ),
						count( $group_courses )
					);
					echo '<br />';

					// Group Leaders.
					$group_leaders = learndash_get_groups_administrator_ids( $group_id );
					if ( ( empty( $group_leaders ) ) || ( ! is_array( $group_leaders ) ) ) {
						$group_leaders = array();
					}
					printf(
						// translators: placeholder: Group Leaders Count.
						esc_html_x( 'Leaders %d', 'placeholder: Group Leaders Count', 'learndash' ),
						count( $group_leaders )
					);
					break;
			}
		}

		/**
		 * This function fill filter the table listing items based on filters selected.
		 * Called via 'parse_query' filter from WP.
		 *
		 * @since 3.2.0
		 *
		 * @param object $query WP_Query instance.
		 */
		public function parse_query_table_filter( $query ) {
			global $pagenow, $typenow;

			if ( ! is_admin() ) {
				return;
			}
			if ( 'edit.php' !== $pagenow ) {
				return;
			}
			if ( ! $query->is_main_query() ) {
				return;
			}
			if ( empty( $typenow ) ) {
				return;
			}

			if ( $typenow === $this->post_type ) {
				$q_vars = &$query->query_vars;

				if ( ! empty( $this->post_type_selectors ) ) {
					foreach ( $this->post_type_selectors as $post_type_key => $post_type_selector ) {
						if ( ( isset( $_GET[ $post_type_selector['query_arg'] ] ) ) && ( ! empty( $_GET[ $post_type_selector['query_arg'] ] ) ) ) {
							$group_ids = learndash_get_course_groups( absint( $_GET[ $post_type_selector['query_arg'] ] ), true );
							if ( ! empty( $group_ids ) ) {
								if ( ! isset( $q_vars['post__in'] ) ) {
									$q_vars['post__in'] = array();
								}
								if ( empty( $q_vars['post__in'] ) ) {
									$q_vars['post__in'] = $group_ids;
								} else {
									$q_vars['post__in'] = array_intersect( $q_vars['post__in'], $group_ids );
								}
							} else {
								$q_vars['post__in'] = array( 0 );
							}
						}
					}
				}

				/**
				 * Handle support for the WP post tag taxonomy. This is not normally handled by the WP query logic.
				 */
				if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_Taxonomies', 'wp_post_tag' ) ) {
					if ( ( isset( $_GET['post_tag'] ) ) && ( ! empty( $_GET['post_tag'] ) ) ) {
						$post_tag = esc_attr( $_GET['post_tag'] );
						if ( '0' !== $post_tag ) {
							$post_tag_term = get_term_by( 'slug', $post_tag, 'post_tag' );
							if ( ( ! empty( $post_tag_term ) ) && ( $post_tag_term instanceof WP_Term ) ) {
								$q_vars['tag_id'] = $post_tag_term->term_id;
							}
						}
					}
				}

				/*
				if ( learndash_is_group_leader_user( get_current_user_id() ) ) {
					if ( ( isset( $_GET['author'] ) ) && ( absint( $_GET['author'] ) === get_current_user_id() ) ) {
						;
					} else {
						$group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
						if ( ! empty( $group_ids ) ) {
							if ( empty( $q_vars['post__in'] ) ) {
								$q_vars['post__in'] = $group_ids;
							} else {
								$q_vars['post__in'] = array_intersect( $q_vars['post__in'], $group_ids );
							}

							if ( empty( $q_vars['post__in'] ) ) {
								$q_vars['post__in'] = array( 0 );
							}
						} else {
							$q_vars['post__in'] = array( 0 );
						}
					}
				}
				*/
			}
		}

		// End of functions.
	}
}
new Learndash_Admin_Groups_Listing();
