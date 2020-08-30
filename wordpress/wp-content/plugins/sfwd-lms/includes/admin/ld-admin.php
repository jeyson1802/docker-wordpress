<?php
/**
 * Functions for wp-admin
 *
 * @since 2.1.0
 *
 * @package LearnDash\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prints content in the head tag in the admin dashboard.
 *
 * Adds learndash icon next to the LearnDash LMS menu item
 *
 * @since 2.1.0
 */
function learndash_admin_head() {
	?>
		<style> 
		/* #adminmenu #toplevel_page_learndash-lms div.wp-menu-image:before { content: "\f472"; } */
		/*
		#adminmenu #toplevel_page_learndash-lms div.wp-menu-image:before {
			background: url('<?php echo esc_url( LEARNDASH_LMS_PLUGIN_URL . '/assets/ldlms-menu-icon.svg' ); ?>') center center no-repeat;
			content: '';
			opacity: 0.7;
		}
		*/
		</style>
	<?php
}

//add_action( 'admin_head', 'learndash_admin_head' );


/**
 * Adds the LearnDash post type to the admin body class.
 *
 * Fires on `admin_body_class` hook.
 *
 * @since 2.5.8
 *
 * @param string $class Optional. The admin body CSS classes. Default empty.
 *
 * @return string Admin body CSS classes.
 */
function learndash_admin_body_class( $class = '' ) {
	global $learndash_post_types;

	$screen = get_current_screen();
	if ( in_array( $screen->id, $learndash_post_types ) ) {
		$class .= ' learndash-post-type ' . $screen->post_type;
	}

	if ( in_array( $screen->post_type, $learndash_post_types ) ) {
		$class .= ' learndash-screen';
	}

	if ( learndash_is_group_leader_user() ) {
		$class .= ' learndash-user-group-leader';
	} else {
		$class .= ' learndash-user-admin';
	}

	return $class;
}
add_filter( 'admin_body_class', 'learndash_admin_body_class' );

/**
 * Hides the top-level menus with no submenus.
 *
 * Fires on `admin_footer` hook.
 *
 * @since 2.1.0
 */
function learndash_hide_menu_when_not_required() {
	?>
		<script>
		jQuery(window).ready(function() {
		if(jQuery(".toplevel_page_learndash-lms").length && jQuery(".toplevel_page_learndash-lms").find("li").length <= 1)
			jQuery(".toplevel_page_learndash-lms").hide();
		});
		</script>
	<?php
}

add_filter( 'admin_footer', 'learndash_hide_menu_when_not_required', 99 );

/**
 * Checks whether to load the admin assets.
 *
 * @global string  $pagenow
 * @global string  $typenow
 * @global WP_Post $post                 Global post object.
 * @global array   $learndash_post_types An array of LearnDash post types.
 * @global array   $learndash_pages      An array of LearnDash pages.
 *
 * @since 3.0.0
 *
 * @return boolean Returns true to load the admin assets otherwise false.
 */
function learndash_should_load_admin_assets() {
	global $pagenow, $post, $typenow;
	global $learndash_post_types, $learndash_pages;

	// Get post type.
	$post_type = get_post_type();
	if ( ! $post_type ) {
		$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : $post_type;
	}

	$is_ld_page = false;
	if ( ( isset( $_GET['page'] ) ) && ( in_array( $_GET['page'], $learndash_pages ) ) ) {
		$is_ld_page = true;
	}

	$is_ld_post_type = false;
	if ( ( ! empty( $post_type ) ) && ( in_array( $post_type, $learndash_post_types ) ) ) {
		$is_ld_post_type = true;
	}

	$is_ld_pagenow = false;
	if ( ( in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) && ( is_a( $post, 'WP_Post' ) ) && ( in_array( $post->post_type, $learndash_post_types ) ) ) {
		$is_ld_pagenow = true;
	}

	$load_admin_assets = false;
	if ( ( true === $is_ld_page ) || ( true === $is_ld_post_type ) || ( true === $is_ld_pagenow ) ) {
		$load_admin_assets = true;
	}

	/**
	 * Filters whether to load the admin assets or not.
	 *
	 * @param boolean $load_admin_assets Whether to load admin assets.
	 */
	return apply_filters( 'learndash_load_admin_assets', $load_admin_assets );
}

/**
 * Enqueues the scripts and styles for admin.
 *
 * Fires on `admin_enqueue_scripts` hook.
 *
 * @global string  $pagenow
 * @global string  $typenow
 * @global WP_Post $post                    Global post object.
 * @global array   $learndash_assets_loaded An array of loaded styles and scripts.
 *
 * @since 2.1.0
 */
function learndash_load_admin_resources() {
	global $pagenow, $post, $typenow;
	//global $learndash_post_types, $learndash_pages;
	global $learndash_assets_loaded;

	wp_enqueue_style(
		'learndash-admin-menu-style',
		LEARNDASH_LMS_PLUGIN_URL . 'assets/css/learndash-admin-menu' . leardash_min_asset() . '.css',
		array(),
		LEARNDASH_SCRIPT_VERSION_TOKEN
	);
	wp_style_add_data( 'learndash-admin-menu-style', 'rtl', 'replace' );
	$learndash_assets_loaded['styles']['learndash-admin-menu-style'] = __FUNCTION__;

	wp_enqueue_script(
		'learndash-admin-menu-script',
		LEARNDASH_LMS_PLUGIN_URL . 'assets/js/learndash-admin-menu' . leardash_min_asset() . '.js',
		array( 'jquery' ),
		LEARNDASH_SCRIPT_VERSION_TOKEN,
		true
	);
	wp_style_add_data( 'learndash-admin-menu-script', 'rtl', 'replace' );
	$learndash_assets_loaded['scripts']['learndash-admin-menu-script'] = __FUNCTION__;

	if ( learndash_should_load_admin_assets() ) {

		/**
		 * Needed for standalone Builders.
		 */
		// to get the tinyMCE editor
		wp_enqueue_editor();

		// for media uploads
		wp_enqueue_media();

		wp_enqueue_style(
			'learndash_style',
			LEARNDASH_LMS_PLUGIN_URL . 'assets/css/style' . leardash_min_asset() . '.css',
			array(),
			LEARNDASH_SCRIPT_VERSION_TOKEN
		);
		wp_style_add_data( 'learndash_style', 'rtl', 'replace' );
		$learndash_assets_loaded['styles']['learndash_style'] = __FUNCTION__;

		wp_enqueue_style(
			'learndash-admin-style',
			LEARNDASH_LMS_PLUGIN_URL . 'assets/css/learndash-admin-style' . leardash_min_asset() . '.css',
			array(),
			LEARNDASH_SCRIPT_VERSION_TOKEN
		);
		wp_style_add_data( 'learndash-admin-style', 'rtl', 'replace' );
		$learndash_assets_loaded['styles']['learndash-admin-style'] = __FUNCTION__;

		wp_enqueue_style(
			'sfwd-module-style',
			LEARNDASH_LMS_PLUGIN_URL . 'assets/css/sfwd_module' . leardash_min_asset() . '.css',
			array(),
			LEARNDASH_SCRIPT_VERSION_TOKEN
		);
		wp_style_add_data( 'sfwd-module-style', 'rtl', 'replace' );
		$learndash_assets_loaded['styles']['sfwd-module-style'] = __FUNCTION__;

		if ( ( $pagenow == 'edit.php' ) && ( in_array( $typenow, array( 'sfwd-essays', 'sfwd-assignment', 'sfwd-topic', 'sfwd-quiz' ) ) ) ) {
			wp_enqueue_script(
				'sfwd-module-script',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/js/sfwd_module' . leardash_min_asset() . '.js',
				array( 'jquery' ),
				LEARNDASH_SCRIPT_VERSION_TOKEN,
				true
			);
			$learndash_assets_loaded['scripts']['sfwd-module-script'] = __FUNCTION__;
			wp_localize_script( 'sfwd-module-script', 'sfwd_data', array() );
		}
	}

	if ( ( in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) && ( $post->post_type == 'sfwd-quiz' ) ) {
		wp_enqueue_script(
			'wpProQuiz_admin_javascript',
			plugins_url( 'js/wpProQuiz_admin' . leardash_min_asset() . '.js', WPPROQUIZ_FILE ),
			array( 'jquery' ),
			LEARNDASH_SCRIPT_VERSION_TOKEN,
			true
		);
		$learndash_assets_loaded['scripts']['wpProQuiz_admin_javascript'] = __FUNCTION__;
	}

	if ( ( in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) && ( $post->post_type == 'sfwd-lessons' ) ) {
		wp_enqueue_style(
			'ld-datepicker-ui-css',
			LEARNDASH_LMS_PLUGIN_URL . 'assets/css/jquery-ui' . leardash_min_asset() . '.css',
			array(),
			LEARNDASH_SCRIPT_VERSION_TOKEN
		);
		wp_style_add_data( 'ld-datepicker-ui-css', 'rtl', 'replace' );
		$learndash_assets_loaded['styles']['ld-datepicker-ui-css'] = __FUNCTION__;
	}

	if (
		( ( $pagenow == 'admin.php' ) && ( isset( $_GET['page'] ) ) && ( $_GET['page'] == 'ldAdvQuiz' ) )
		&& ( ( isset( $_GET['module'] ) ) && ( $_GET['module'] == 'statistics' ) )
		) {
		wp_enqueue_style(
			'ld-datepicker-ui-css',
			LEARNDASH_LMS_PLUGIN_URL . 'assets/css/jquery-ui' . leardash_min_asset() . '.css',
			array(),
			LEARNDASH_SCRIPT_VERSION_TOKEN
		);
		wp_style_add_data( 'ld-datepicker-ui-css', 'rtl', 'replace' );
		$learndash_assets_loaded['styles']['ld-datepicker-ui-css'] = __FUNCTION__;
	}
}

add_action( 'admin_enqueue_scripts', 'learndash_load_admin_resources' );




/**
 * Changes the label in the admin bar for a single topic to 'Edit Topic'.
 *
 * @todo  consider for deprecation, action is commented
 *
 * @since 2.1.0
 */
function learndash_admin_bar_link() {
	global $wp_admin_bar;
	global $post;

	if ( ! is_super_admin() || ! is_admin_bar_showing() ) {
		return;
	}

	if ( is_single() && $post->post_type == 'sfwd-topic' ) {
		$wp_admin_bar->add_menu(
			array(
				'id'     => 'edit_fixed',
				'parent' => false,
				// translators: Edit Topic Label.
				'title'  => sprintf( esc_html_x( 'Edit %s', 'Edit Topic Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'topic' ) ),
				'href'   => get_edit_post_link( $post->id ),
			)
		);
	}
}



/**
 * Outputs the Reports Page.
 *
 * @since 2.1.0
 */
function learndash_lms_reports_page() {
	?>
		<div  id="learndash-reports"  class="wrap">
			<h1><?php esc_html_e( 'User Reports', 'learndash' ); ?></h1>
			<br>
			<div class="sfwd_settings_left">
				<div class=" " id="sfwd-learndash-reports_metabox">
					<div class="inside">
						<a class="button-primary" href="<?php echo admin_url( 'admin.php?page=learndash-lms-reports&action=sfp_update_module&nonce-sfwd=' . wp_create_nonce( 'sfwd-nonce' ) . '&page_options=sfp_home_description&courses_export_submit=Export' ); ?>"><?php 
						// translators: Export User Course Data Label.
						printf( esc_html_x( 'Export User %s Data', 'Export User Course Data Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ); ?></a>
						<a class="button-primary" href="<?php echo admin_url( 'admin.php?page=learndash-lms-reports&action=sfp_update_module&nonce-sfwd=' . wp_create_nonce( 'sfwd-nonce' ) . '&page_options=sfp_home_description&quiz_export_submit=Export' ); ?>"><?php printf( esc_html_x( 'Export %s Data', 'Export Quiz Data Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ); ?></a>
						<?php
							/**
							 * Fires after report page buttons.
							 *
							 * @since 2.1.0
							 */
							do_action( 'learndash_report_page_buttons' );
						?>
					</div>
				</div>
			</div>
		</div>
	<?php
}



/**
 * Adds JavaScript code to the admin footer.
 *
 * @since 2.1.0
 *
 * @global string $learndash_current_page_link
 * @global string $parent_file
 * @global string $submenu_file
 *
 * @TODO We need to get rid of this JS logic and replace with filter to set the $parent_file
 * See:
 * https://developer.wordpress.org/reference/hooks/parent_file/
 * https://developer.wordpress.org/reference/hooks/submenu_file/
 */
function learndash_select_menu() {
	global $learndash_current_page_link;
	global $parent_file, $submenu_file;

	if ( ! empty( $learndash_current_page_link ) ) {
	?>
		<script type="text/javascript">
		//jQuery(window).on('load', function( $) {
			jQuery("body").removeClass("sticky-menu");
			jQuery("#toplevel_page_learndash-lms, #toplevel_page_learndash-lms > a").removeClass('wp-not-current-submenu' );
			jQuery("#toplevel_page_learndash-lms").addClass('current wp-has-current-submenu wp-menu-open' );
			jQuery("#toplevel_page_learndash-lms a[href='<?php echo $learndash_current_page_link; ?>']").parent().addClass("current");
		//});
		</script>
	<?php
	}
};



/**
 * Adds the shortcode column in admin for quizzes.
 *
 * Fires on `manage_edit-sfwd-quiz_columns` hook.
 *
 * @since 2.1.0
 *
 * @param array $cols An array of columns for admin posts listing.
 *
 * @return array $cols An array of columns for admin posts listing.
 */
function add_shortcode_data_columns( $cols ) {
	return array_merge(
		array_slice( $cols, 0, 3 ),
		array( 'shortcode' => esc_html__( 'Shortcode', 'learndash' ) ),
		array_slice( $cols, 3 )
	);
}



/**
 * Adds the assigned course columns for lessons and quizzes in the admin.
 *
 * @global string $typenow
 *
 * @since 2.1.0
 *
 * @param array $cols An array of columns for admin posts listing.
 *
 * @return array $cols An array of columns for admin posts listing.
 */
function add_course_data_columns( $cols ) {
	global $typenow;

	$new_columns = array();

	if ( in_array( $typenow, array( 'sfwd-assignment' ) ) !== false ) {
		$new_columns = array(
			// translators: Assigned Course Label.
			'course' => sprintf( esc_html_x( 'Assigned %s', 'Assigned Course Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			// translators: Assigned Lesson Label.
			'lesson' => sprintf( esc_html_x( 'Assigned %s', 'Assigned Lesson Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
		);
	} 
	elseif ( learndash_get_post_type_slug( 'transaction' ) === $typenow ) {
		/*
		$new_columns = array(
			// translators: Assigned Course Label.
			'course' => sprintf( esc_html_x( 'Assigned %s', 'Assigned Course Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			// translators: Assigned Group Label.
			'group' => sprintf( esc_html_x( 'Assigned %s', 'Assigned Group Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ) ),
		);
		*/
	}
	 else {
		if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) != 'yes' ) {
			$new_columns = array(
				// translators: Assigned Course Label.
				'course' => sprintf( esc_html_x( 'Assigned %s', 'Assigned Course Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			);
		}
	}

	if ( ! empty( $new_columns ) ) {
		$cols = array_merge(
			array_slice( $cols, 0, 3 ),
			$new_columns,
			array_slice( $cols, 3 )
		);
	}

	return $cols;
}



/**
 * Adds the assigned lesson & assigned course columns for topics and assignments in the admin.
 *
 * @since 2.1.0
 *
 * @param array $cols An array of columns for admin posts listing.
 *
 * @return array $cols An array of columns for admin posts listing.
 */
function add_lesson_data_columns( $cols ) {
	if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) != 'yes' ) {
		$cols = array_merge(
			array_slice( $cols, 0, 3 ),
			array(
				'course' => sprintf( esc_html_x( 'Assigned %s', 'Assigned Course Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
				'lesson' => sprintf( esc_html_x( 'Assigned %s', 'Assigned Lesson Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
			),
			array_slice( $cols, 3 )
		);
	}

	return $cols;
}



/**
 * Adds the status and points columns for assignments in the admin.
 *
 * @since 2.1.0
 *
 * @param array $cols An array of columns for admin posts listing.
 *
 * @return array $cols An array of columns for admin posts listing.
 */
function add_assignment_data_columns( $cols ) {
	return array_merge(
		array_slice( $cols, 0, 3 ),
		array(
			'approval_status' => esc_html__( 'Status', 'learndash' ),
			'approval_points' => esc_html__( 'Points', 'learndash' ),
		),
		array_slice( $cols, 3 )
	);
}


/**
 * Removes the tags column for quizzes.
 *
 * @since 2.1.0
 *
 * @param array $cols An array of columns for admin posts listing.
 *
 * @return array $cols An array of columns for admin posts listing.
 */
function remove_tags_column( $cols ) {
	unset( $cols['tags'] );
	return $cols;
}



/**
 * Removes the categories column for quizzes.
 *
 * @since 2.1.0
 *
 * @param array $cols An array of columns for admin posts listing.
 *
 * @return array $cols An array of columns for admin posts listing.
 */
function remove_categories_column( $cols ) {
	unset( $cols['categories'] );
	return $cols;
}

/**
 * Outputs approval status for assignment in admin posts listing.
 *
 * Fires on `manage_sfwd-assignment_posts_custom_column` hook.
 *
 * @since 2.1.0
 *
 * @param string $column_name   Name of the column.
 * @param int    $assignment_id ID of the assigment.
 */
function manage_asigned_assignment_columns( $column_name, $assignment_id ) {
	switch ( $column_name ) {
		case 'approval_status':
			$assignment_lesson_id = intval( get_post_meta( $assignment_id, 'lesson_id', true ) );
			if ( ! empty( $assignment_lesson_id ) ) {
				$approval_status_flag = learndash_is_assignment_approved_by_meta( $assignment_id );
				if ( 1 == $approval_status_flag ) {
					$approval_status_label = _x( 'Approved', 'Assignment approval status', 'learndash' );
				} else {
					$approval_status_flag  = 0;
					$approval_status_label = _x( 'Not Approved', 'Assignment approval status', 'learndash' );
				}
				$approval_status_url = admin_url( 'edit.php?post_type=' . get_post_type( $assignment_id ) . '&approval_status=' . $approval_status_flag );

				echo '<a href="' . esc_url( $approval_status_url ) . '">' . esc_html( $approval_status_label ) . '</a>';
				if ( 1 != $approval_status_flag ) {
					?>
					<button id="assignment_approve_<?php echo esc_attr( $assignment_id ); ?>" class="small assignment_approve_single"><?php esc_html_e( 'approve', 'learndash' ); ?></button>
					<?php
				}
			}
			break;

		case 'approval_points':
			if ( learndash_assignment_is_points_enabled( $assignment_id ) ) {
				$max_points = 0;

				$assignment_settings_id = intval( get_post_meta( $assignment_id, 'lesson_id', true ) );
				if ( ! empty( $assignment_settings_id ) ) {
					$max_points = learndash_get_setting( $assignment_settings_id, 'lesson_assignment_points_amount' );
				}

				$current_points = get_post_meta( $assignment_id, 'points', true );
				if ( ! is_numeric( $current_points ) ) {
					$approval_status_flag = learndash_is_assignment_approved_by_meta( $assignment_id );
					if ( 1 != $approval_status_flag ) {
						$current_points = '<input id="assignment_points_' . $assignment_id . '" class="small-text" type="number" value="0" max="' . $max_points . '" min="0" step="1" name="assignment_points[' . $assignment_id . ']" />';
					} else {
						$current_points = '0';
					}
				}
				// translators: placeholders: current points / maximum point for assignment.
				echo sprintf( _x( '%1$s / %2$s', 'placeholders: current points / maximum point for assignment', 'learndash' ), $current_points, $max_points );
			} else {
				esc_html_x( 'Not Enabled', 'Points for assignment not enabled', 'learndash' );
			}
			break;

		default:
			break;
	}
}

/**
 * Outputs the values for assigned courses in admin columns
 * for lessons, quizzes, topics, and assignments.
 *
 * @global string $typenow
 *
 * @since 2.1.0
 *
 * @param string $column_name The name of the column.
 * @param int    $id          The ID of the column.
 */
function manage_asigned_course_columns( $column_name, $id ) {
	global $typenow;

	if ( learndash_get_post_type_slug( 'transaction' ) === $typenow ) {
		return;
	}

	switch ( $column_name ) {
		case 'shortcode':
			$valid_quiz  = false;
			$quiz_pro_id = learndash_get_setting( $id, 'quiz_pro', true );
			$quiz_pro_id = absint( $quiz_pro_id );
			if ( ! empty( $quiz_pro_id ) ) {
				$quiz_mapper = new WpProQuiz_Model_QuizMapper();
				$quiz_pro    = $quiz_mapper->fetch( $quiz_pro_id );
				if ( ( is_a( $quiz_pro, 'WpProQuiz_Model_Quiz' ) ) && ( $quiz_pro_id === $quiz_pro->getId() ) ) {
					$valid_quiz = true;
					echo '<strong>[ld_quiz quiz_id="' . $id . '"]</strong>';
					echo '<br />[LDAdvQuiz ' . $quiz_pro_id . ']';
					echo '<br />[LDAdvQuiz_toplist ' . $quiz_pro_id . ']';
				}
			}

			if ( false === $valid_quiz ) {
				?>
				<span class="ld-error"><?php esc_html_e( 'Missing ProQuiz Associated Settings.', 'learndash' ); ?></span>
				<?php
			}

			break;
		case 'course':
			/*
			if ( get_post_type( $id ) == learndash_get_post_type_slug( 'transaction' ) ) {
				$course_id = get_post_meta( $id, 'course_id', true );
				if ( ! empty( $course_id ) ) {
					$row_actions = array();
					$edit_url    = get_edit_post_link( $course_id );

					echo '<a href="' . esc_url( $edit_url ) . '">' . get_the_title( $course_id ) . '</a>';
					$row_actions['edit']        = '<a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'edit', 'learndash' ) . '</a>';
					$row_actions['filter_post'] = '<a href="' . esc_url( add_query_arg( 'course_id', $course_id ) ) . '">' . esc_html__( 'filter', 'learndash' ) . '</a>';
					echo learndash_list_table_row_actions( $row_actions );

				}
			} else {
			*/	
				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
					if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) || ( get_post_type( $id ) == 'sfwd-assignment' ) ) {
						//$course_id = intval( $_GET['course_id'] );

						//$course_id = learndash_get_course_id( $id );
						$course_id = get_post_meta( $id, 'course_id', true );
					} else {
						//$course_id = 0;
						$course_id = get_post_meta( $id, 'course_id', true );
					}
				} else {
					//$course_id = learndash_get_course_id( $id );
					$course_id = get_post_meta( $id, 'course_id', true );
				}
			//}

			if ( ! empty( $course_id ) ) {
				$row_actions = array();
				$edit_url    = get_edit_post_link( $course_id );

				echo '<a href="' . esc_url( $edit_url ) . '">' . get_the_title( $course_id ) . '</a>';
				$row_actions['edit']        = '<a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'edit', 'learndash' ) . '</a>';
				$row_actions['filter_post'] = '<a href="' . esc_url( add_query_arg( 'course_id', $course_id ) ) . '">' . esc_html__( 'filter', 'learndash' ) . '</a>';
				echo learndash_list_table_row_actions( $row_actions );

			} /*
			elseif ( get_post_type( $id ) == learndash_get_post_type_slug( 'transaction' ) ) {
				echo '-';
			} */
			else {
				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) != 'yes' ) {
					if ( get_post_type( $id ) === 'sfwd-quiz' ) {
						echo '&#8212;';
					} else {
						// translators: placeholder: Course.
						echo '<span class="ld-error dashicons dashicons-warning" title="' . sprintf( esc_html_x( '%s Required', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ) . '"></span>';
					}
				} else {
					echo '&#8212;';
				}
			}
			break;

		/*
		case 'group':
			if ( get_post_type( $id ) == learndash_get_post_type_slug( 'transaction' ) ) {
				$group_id = get_post_meta( $id, 'group_id', true );
				if ( ! empty( $group_id ) ) {
					$row_actions = array();
					$edit_url    = get_edit_post_link( $group_id );

					echo '<a href="' . esc_url( $edit_url ) . '">' . get_the_title( $group_id ) . '</a>';
					$row_actions['edit']        = '<a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'edit', 'learndash' ) . '</a>';
					$row_actions['filter_post'] = '<a href="' . esc_url( add_query_arg( 'group_id', $group_id ) ) . '">' . esc_html__( 'filter', 'learndash' ) . '</a>';
					echo learndash_list_table_row_actions( $row_actions );
				}
			}
		break;
		*/

		case 'lesson':
			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {

				if ( in_array( $typenow, array( 'sfwd-assignment' ) ) ) {
					$course_id = get_post_meta( $id, 'course_id', true );
					$lesson_id = get_post_meta( $id, 'lesson_id', true );
				} elseif ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
					$course_id = intval( $_GET['course_id'] );
					$lesson_id = learndash_course_get_single_parent_step( $course_id, $id );
				} else {
					$lesson_id = get_post_meta( $id, 'lesson_id', true );
				}
			} else {
				//$lesson_id = learndash_get_setting( $id, 'lesson' );
				$lesson_id = get_post_meta( $id, 'lesson_id', true );
				//$course_id = learndash_get_setting( $id, 'course' );
				$course_id = get_post_meta( $id, 'course_id', true );
			}

			if ( ! empty( $lesson_id ) ) {
				$row_actions = array();

				$edit_url   = get_edit_post_link( $lesson_id );
				$filter_url = add_query_arg( 'lesson_id', $lesson_id );

				//$course_id = get_post_meta( $id, 'course_id', true );
				if ( ! empty( $course_id ) ) {
					// For the filter URL we always add the course if available.
					$filter_url = add_query_arg( 'course_id', $course_id, $filter_url );

					if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
						$edit_url = add_query_arg( 'course_id', $course_id, $edit_url );
					} else {
						$edit_url = remove_query_arg( 'course_id', $edit_url );
					}
				} else {
					$filter_url = remove_query_arg( 'course_id', $filter_url );
					$edit_url   = remove_query_arg( 'course_id', $edit_url );
				}

				echo '<a href="' . esc_url( $edit_url ) . '">' . get_the_title( $lesson_id ) . '</a>';
				$row_actions['edit']        = '<a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'edit', 'learndash' ) . '</a>';
				$row_actions['filter_post'] = '<a href="' . esc_url( $filter_url ) . '">' . esc_html__( 'filter', 'learndash' ) . '</a>';
				echo learndash_list_table_row_actions( $row_actions );
			} else {
				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) != 'yes' ) {

					if ( get_post_type( $id ) === 'sfwd-quiz' ) {
						echo '&#8212;';
					} else {
						echo '<span class="ld-error dashicons dashicons-warning" title="' .
						//  translators: placeholder: Lesson.
						sprintf( esc_html_x( '%s Required', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ) . '"></span>';
					}
				} else {
					echo '&#8212;';
				}
			}
			break;

		default:
			break;
	}
}

/**
 * Gets the the table row actions output.
 *
 * @param array   $actions        An array of table row actions.
 * @param boolean $always_visible Optional. Whether the row will be always visible. Default false.
 *
 * @return string The table row actions HTML output.
 */
function learndash_list_table_row_actions( $actions, $always_visible = false ) {
	$action_count = count( $actions );
	$i            = 0;

	if ( ! $action_count ) {
		return '';
	}

	$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';
	foreach ( $actions as $action => $link ) {
		++$i;
		( $i == $action_count ) ? $sep = '' : $sep = ' | ';
		$out                          .= "<span class='$action'>$link$sep</span>";
	}
	$out .= '</div>';

	$out .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . esc_html__( 'Show more details', 'learndash' ) . '</span></button>';

	return $out;
}

/**
 * Outputs the select dropdown before the filter button to filter post listing by course.
 *
 * Fires on `restrict_manage_posts` hook.
 *
 * @global string   $pagenow
 * @global SFWD_LMS $sfwd_lms Global `SFWD_LMS` object.
 *
 * @since 2.1.0
 *
 * @param string $post_type The post type slug.
 * @param string $location  Optional. The location of the extra table nav markup. Default empty.
 */
function restrict_listings_by_course( $post_type, $location = '' ) {
	global $pagenow, $sfwd_lms;

	$ld_post_types = array(
		//'sfwd-courses',
		//'sfwd-lessons',
		//'sfwd-topic',
		//'sfwd-quiz',
		'sfwd-certificates',
		//'groups',
		'sfwd-assignment',
		//'sfwd-transactions',
		'sfwd-essays',
	);

	if ( ! is_admin() ) {
		return;
	}
	if ( 'edit.php' !== $pagenow ) {
		return;
	}
	if ( ( isset( $_GET['post_status'] ) ) && ( 'trash' === $_GET['post_status'] ) ) {
		return;
	}
	if ( ( ! isset( $post_type ) ) || ( ! in_array( $post_type, $ld_post_types ) ) ) {
		return;
	}

	// First we display the object taxonomies
	if ( ! in_array( $post_type, array( 'sfwd-quiz' ) ) ) {
		$object_taxonomies = get_object_taxonomies( $post_type );
		if ( ( ! empty( $object_taxonomies ) ) && ( is_array( $object_taxonomies ) ) ) {
			// We remove 'category' from the object taxonomies because by now WP has already output it.
			// Maybe at some point we can move the filter earlier
			$object_taxonomies = array_diff( $object_taxonomies, array( 'category' ) );
		}
		/** This filter is documented in includes/admin/class-learndash-admin-posts-listing.php */
		$object_taxonomies = apply_filters( 'learndash-admin-taxonomy-filters-display', $object_taxonomies, $post_type );

		if ( ( ! empty( $object_taxonomies ) ) && ( is_array( $object_taxonomies ) ) ) {
			foreach ( $object_taxonomies as $taxonomy_slug ) {
				if ( isset( $_GET[ $taxonomy_slug ] ) ) {
					$selected = esc_attr( $_GET[ $taxonomy_slug ] );
				} else {
					$selected = false;
				}

				//if ( $taxonomy_slug == 'post_tag' )
				//	$taxonomy_slug_name = 'tag';
				//else
					$taxonomy_slug_name = $taxonomy_slug;

				$dropdown_options = array(
					'taxonomy'          => $taxonomy_slug,
					'name'              => $taxonomy_slug_name,
					//'show_option_all' => get_taxonomy( $taxonomy_slug )->labels->all_items,
					'show_option_none'  => get_taxonomy( $taxonomy_slug )->labels->all_items,
					'option_none_value' => '',
					'hide_empty'        => 0,
					'hierarchical'      => get_taxonomy( $taxonomy_slug )->hierarchical,
					'show_count'        => 0,
					'orderby'           => 'name',
					'value_field'       => 'slug',
					'selected'          => $selected,
				);

				echo '<label class="screen-reader-text" for="' . $taxonomy_slug . '">' .
				//  translators: placeholder: Taxonomy name.
				sprintf( esc_html__( 'Filter by %s', 'placeholder: Taxonomy name', 'learndash' ), get_taxonomy( $taxonomy_slug )->labels->singular_name ) . '</label>';
				wp_dropdown_categories( $dropdown_options );
			}
		}
	}

	$cpt_filters_shown['sfwd-courses'] = array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-assignment', 'sfwd-quiz', 'sfwd-essays', 'groups', 'sfwd-transactions' );
	$cpt_filters_shown['sfwd-lessons'] = array( 'sfwd-topic', 'sfwd-assignment', 'sfwd-quiz', 'sfwd-essays' );
	$cpt_filters_shown['sfwd-topic']   = array();
	$cpt_filters_shown['sfwd-quiz']    = array( 'sfwd-essays' );
	//$cpt_filters_shown['groups']       = array( 'sfwd-transactions' );

	/**
	 * Filters list of CPT shown for a filter.
	 *
	 * @param array $cpt_filters_shown An array of cpts shown filter.
	 */
	$cpt_filters_shown = apply_filters( 'learndash-admin-cpt-filters-display', $cpt_filters_shown );

	$course_ids = array();
	$lesson_ids = array();
	$group_ids  = array();

	// Courses filter
	if ( in_array( $_GET['post_type'], $cpt_filters_shown['sfwd-courses'] ) ) {
		$query_options_course = array(
			'post_type'      => 'sfwd-courses',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		if ( learndash_is_group_leader_user( get_current_user_id() ) ) {
			$group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
			if ( ! empty( $group_ids ) && is_array( $group_ids ) ) {
				foreach ( $group_ids as $group_id ) {
					$group_course_ids = learndash_group_enrolled_courses( $group_id );
					if ( ! empty( $group_course_ids ) && is_array( $group_course_ids ) ) {
						$course_ids = array_merge( $course_ids, $group_course_ids );
					}
				}
			}

			if ( ! empty( $course_ids ) && count( $course_ids ) ) {
				$query_options_course['post__in'] = $course_ids;
			}
		}
		/** This filter is documented in includes/class-ld-lms.php */
		$lazy_load = apply_filters( 'learndash_element_lazy_load_admin', true );
		if ( true == $lazy_load ) {
			/**
			 * Filters whether to lazy load admin settings for a post type or not.
			 *
			 * The dynamic portion of the hook `$_GET['post_type']` refers to the post type slug.
			 *
			 * @param boolean $lazy_load Whether to lazy load or not.
			 */
			$lazy_load = apply_filters( 'learndash_element_lazy_load_admin_' . esc_attr( $_GET['post_type'] ) . '_filters', true );
			if ( true == $lazy_load ) {
				$query_options_course['paged']          = 1;
				/** This filter is documented in includes/class-ld-lms.php */
				$query_options_course['posts_per_page'] = apply_filters( 'learndash_element_lazy_load_per_page', LEARNDASH_LMS_DEFAULT_LAZY_LOAD_PER_PAGE, esc_attr( $_GET['post_type'] ) );
			}
		}

		/**
		 * Filters course filter query arguments.
		 *
		 * @param array  $query_options_course An array of course filter query arguments.
		 * @param string $post_type            Post type to check.
		 */
		$query_options_course = apply_filters( 'learndash_course_post_options_filter', $query_options_course, $_GET['post_type'] );

		$query_posts_course = new WP_Query( $query_options_course );

		if ( ! empty( $query_posts_course->posts ) ) {
			if ( count( $query_posts_course->posts ) >= $query_posts_course->found_posts ) {
				// If the number of returned posts is equal or greater then found_posts then no need to run lazy load
				$lazy_load = false;
			}

			$post_type_nonce = wp_create_nonce( 'sfwd-courses' );

			if ( true == $lazy_load ) {
				$lazy_load_data               = array();
				$lazy_load_data['query_vars'] = $query_options_course;
				$lazy_load_data['query_type'] = 'WP_Query';
				$lazy_load_data['nonce']      = $post_type_nonce;

				if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
					$lazy_load_data['value'] = intval( $_GET['course_id'] );
				} else {
					$lazy_load_data['value'] = 0;
				}

				$lazy_load_data = ' learndash_lazy_load_data="' . htmlspecialchars( json_encode( $lazy_load_data ) ) . '" ';
			} else {
				$lazy_load_data = '';
			}

			echo '<select ' . $lazy_load_data . " name='course_id' id='course_id' class='postform' data-ld_selector_nonce='" . $post_type_nonce . "' data-ld_selector_default='0'>";
			echo "<option value=''>" .
			//  translators: placeholder: Courses.
			sprintf( esc_html_x( 'Show All %s', 'placeholder: Courses.', 'learndash' ), LearnDash_Custom_Label::get_label( 'courses' ) ) . '</option>';

			foreach ( $query_posts_course->posts as $p ) {
				echo '<option value=' . $p->ID, ( ( ( isset( $_GET['course_id'] ) ) && ( intval( $_GET['course_id'] ) == intval( $p->ID ) ) ) ? ' selected="selected"' : '' ) . '>' . apply_filters( 'the_title',  $p->post_title, $p->ID ) . '</option>';
			}
			echo '</select>';

			$lazy_load_spinner = '<span style="display:none;" class="learndash_lazy_loading"><img class="learndash_lazy_load_spinner" alt="' . esc_html__( 'loading', 'learndash' ) . '" src="' . admin_url( '/images/wpspin_light.gif' ) . '" /> </span>';
			echo $lazy_load_spinner;

		}
	}

	// Lessons filter
	if ( in_array( $_GET['post_type'], $cpt_filters_shown['sfwd-lessons'] ) ) {

		echo "<select name='lesson_id' id='lesson_id' class='postform' data-ld_selector_nonce='" . wp_create_nonce( 'sfwd-lessons' ) . "' data-ld_selector_default='0'>";
		echo "<option value=''>" . 
		// translators: placeholder: Lessons.
		sprintf( esc_html_x( 'Show All %s', 'placeholder: Lessons', 'learndash' ), LearnDash_Custom_Label::get_label( 'lessons' ) ) . '</option>';
		if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
			if ( $_GET['post_type'] == 'sfwd-topic' ) {
				$lessons_items = $sfwd_lms->select_a_lesson_or_topic( intval( $_GET['course_id'] ), false );
			} else {
				$lessons_items = $sfwd_lms->select_a_lesson_or_topic( intval( $_GET['course_id'] ) );
			}

			$selected_lesson_id = 0;
			if ( ( isset( $_GET['lesson_id'] ) ) && ( ! empty( $_GET['lesson_id'] ) ) ) {
				$selected_lesson_id = intval( $_GET['lesson_id'] );
			}
			if ( ! empty( $lessons_items ) ) {
				foreach ( $lessons_items as $id => $title ) {
					echo '<option value="' . $id . '" ' . selected( $selected_lesson_id, $id ) . '>' . apply_filters( 'the_title',  $title, $id ) . '</option>';
				}
			}
		}
		echo '</select>';

	}

	// Topicss filter
	if ( in_array( $_GET['post_type'], $cpt_filters_shown['sfwd-topic'] ) ) {
		$query_options_topic = array(
			'post_type'      => 'sfwd-topic',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		// If the course_id is selected we limit the lesson selector to only those related to course_id
		// @since 2.3
		if ( ( isset( $_GET['lesson_id'] ) ) && ( ! empty( $_GET['lesson_id'] ) ) ) {
			$query_options_topic['meta_key']     = 'lesson_id';
			$query_options_topic['meta_value']   = intval( $_GET['lesson_id'] );
			$query_options_topic['meta_compare'] = '=';
		} else {
			if ( ! empty( $lesson_ids ) && count( $lesson_ids ) ) {
				if ( ! isset( $query_options_topic['meta_query'] ) ) {
					$query_options_topic['meta_query'] = array();
				}

				$query_options_topic['meta_query'][] = array(
					'key'     => 'lesson_id',
					'value'   => $lesson_ids,
					'compare' => 'IN',
				);
			}
		}
		/** This filter is documented in includes/class-ld-lms.php */
		$lazy_load = apply_filters( 'learndash_element_lazy_load_admin', true );
		if ( $lazy_load == true ) {
			/** This filter is documented in includes/admin/ld-admin.php */
			$lazy_load = apply_filters( 'learndash_element_lazy_load_admin_' . $_GET['post_type'] . '_filters', true );
			if ( $lazy_load == true ) {
				$query_options_topic['paged']          = 1;
				/** This filter is documented in includes/class-ld-lms.php */
				$query_options_topic['posts_per_page'] = apply_filters( 'learndash_element_lazy_load_per_page', LEARNDASH_LMS_DEFAULT_LAZY_LOAD_PER_PAGE, esc_attr( $_GET['post_type'] ) );
			}
		}

		/**
		 * Filters lesson filter query arguments.
		 *
		 * @param array  $query_options_topic An array of lesson filter query arguments.
		 * @param string $post_type           Post type to check.
		 */
		$query_options_topic = apply_filters( 'learndash_lesson_post_options_filter', $query_options_topic, $_GET['post_type'] );

		$query_posts_topic = new WP_Query( $query_options_topic );

		if ( ! empty( $query_posts_topic->posts ) ) {
			if ( count( $query_posts_topic->posts ) >= $query_posts_topic->found_posts ) {
				// If the number of returned posts is equal or greater then found_posts then no need to run lazy load
				$lazy_load = false;
			}

			$post_type_nonce = wp_create_nonce( 'sfwd-topic' );
			if ( $lazy_load == true ) {
				$lazy_load_data               = array();
				$lazy_load_data['query_vars'] = $query_options_topic;
				$lazy_load_data['query_type'] = 'WP_Query';
				$lazy_load_data['nonce']      = $post_type_nonce;

				if ( isset( $_GET['topic_id'] ) ) {
					$lazy_load_data['value'] = intval( $_GET['topic_id'] );
				} else {
					$lazy_load_data['value'] = 0;
				}

				$lazy_load_data = ' learndash_lazy_load_data="' . htmlspecialchars( json_encode( $lazy_load_data ) ) . '" ';
			} else {
				$lazy_load_data = '';
			}

			echo '<select ' . $lazy_load_data . " name='topic_id' id='topic_id' class='postform' data-ld_selector_nonce='" . $post_type_nonce . "' data-ld_selector_default='0'>";
			echo "<option value=''>" . 
			// translators: Show All Topics Option Label.
			sprintf( esc_html_x( 'Show All %s', 'Show All Topics Option Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'topic' ) ) . '</option>';
			foreach ( $query_posts_topic->posts as $p ) {
				echo '<option value=' . $p->ID, ( @$_GET['topic_id'] == $p->ID ? ' selected="selected"' : '' ) . '>' .  apply_filters( 'the_title',  $p->post_title, $p->ID ) . '</option>';
			}
			echo '</select>';
		}
	}

	// Quiz Filters
	if ( in_array( $_GET['post_type'], $cpt_filters_shown['sfwd-quiz'] ) ) {
		//$quiz    = new WpProQuiz_Model_QuizMapper();
		//$quizzes = $quiz->fetchAll();
		//echo "<select name='quiz_id' id='quiz_id' class='postform'>";
		//echo "<option value=''>". sprintf( esc_html_x( 'Show All %s', 'Show All Quizzes', 'learndash' ), LearnDash_Custom_Label::get_label( 'quizzes' ) ) .'</option>';
		//foreach ( $quizzes as $quiz ) {
		//	echo '<option value='. $quiz->getId(), ( @$_GET['quiz_id'] == $quiz->getId() ? ' selected="selected"' : '').'>' . $quiz->getName() .'</option>';
		//}
		//echo '</select>';

		$query_options_quiz = array(
			'post_type'      => 'sfwd-quiz',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		// If the course_id is selected we limit the lesson selector to only those related to course_id
		// @since 2.3
		if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
			$query_options_quiz['meta_key']   = 'course_id';
			$query_options_quiz['meta_value'] = intval( $_GET['course_id'] );
		} else {
			if ( ! empty( $course_ids ) && count( $course_ids ) ) {

				if ( ! isset( $query_options_quiz['meta_query'] ) ) {
					$query_options_quiz['meta_query'] = array();
				}

				$query_options_quiz['meta_query'][] = array(
					'key'     => 'course_id',
					'value'   => $course_ids,
					'compare' => 'IN',
				);
			}
		}
		/** This filter is documented in includes/admin/ld-admin.php */
		$query_options_quiz = apply_filters( 'learndash_lesson_post_options_filter', $query_options_quiz, esc_attr( $_GET['post_type'] ) );
		$query_posts_quiz   = new WP_Query( $query_options_quiz );

		if ( ! empty( $query_posts_quiz->posts ) ) {
			if ( count( $query_posts_quiz->posts ) >= $query_posts_quiz->found_posts ) {
				// If the number of returned posts is equal or greater then found_posts then no need to run lazy load
				$lazy_load = false;
			}

			$post_type_nonce = wp_create_nonce( 'sfwd-quiz' );
			if ( $lazy_load == true ) {
				$lazy_load_data               = array();
				$lazy_load_data['query_vars'] = $query_options_quiz;
				$lazy_load_data['query_type'] = 'WP_Query';
				$lazy_load_data['nonce']      = $post_type_nonce;

				if ( isset( $_GET['quiz_id'] ) ) {
					$lazy_load_data['value'] = intval( $_GET['quiz_id'] );
				} else {
					$lazy_load_data['value'] = 0;
				}

				$lazy_load_data = ' learndash_lazy_load_data="' . htmlspecialchars( json_encode( $lazy_load_data ) ) . '" ';
			} else {
				$lazy_load_data = '';
			}

			echo '<select ' . $lazy_load_data . " name='quiz_id' id='quiz_id' class='postform' data-ld_selector_nonce='" . $post_type_nonce . "' data-ld_selector_default='0'>";
			echo "<option value=''>" .
			// translators: Show All Quizzes Option Label.
			sprintf( esc_html_x( 'Show All %s', 'Show All Quizzes Option Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'quizzes' ) ) . '</option>';
			foreach ( $query_posts_quiz->posts as $p ) {
				$quiz_pro_id = get_post_meta( $p->ID, 'quiz_pro_id', true );
				if ( ! empty( $quiz_pro_id ) ) {
					if ( ( isset( $_GET['quiz_id'] ) ) && ( ! empty( $_GET['quiz_id'] ) ) ) {
						$is_Selected = selected( absint( $_GET['quiz_id'] ), absint( $quiz_pro_id ), false );
					} else {
						$is_Selected = '';
					}
					echo '<option value="' . absint( $quiz_pro_id ) . '" ' . $is_Selected . '>' . apply_filters( 'the_title',  $p->post_title, $p->ID ) . '</option>';
				}
			}
			echo '</select>';
		} else {
			echo "<select name='quiz_id' id='quiz_id' class='postform'>";
			echo "<option value=''>" . sprintf( esc_html_x( 'Show All %s', 'Show All Quizzes Option Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'quizzes' ) ) . '</option>';
			echo '</select>';
		}
	}

	if ( $_GET['post_type'] == 'sfwd-assignment' ) {
		$selected_1 = '';
		$selected_0 = '';

		if ( isset( $_GET['approval_status'] ) ) {
			if ( $_GET['approval_status'] == 1 ) {
				$selected_1 = 'selected="selected"';
				$selected_0 = '';
			} if ( $_GET['approval_status'] == 0 ) {
				$selected_0 = 'selected="selected"';
				$selected_1 = '';
			}
		} elseif ( ( isset( $_GET['approval_status'] ) ) && ( $_GET['approval_status'] == 0 ) ) {
			$selected_0 = 'selected="selected"';
			$selected_1 = '';
		} elseif ( ! isset( $_GET['approval_status'] ) ) {
			$selected_0 = '';
			$selected_1 = '';
		}
		?>
			<select name='approval_status' id='approval_status' class='postform'>
				<option value='-1'><?php esc_html_e( 'Approval Status', 'learndash' ); ?></option>
				<option value='1' <?php echo $selected_1; ?>><?php esc_html_e( 'Approved', 'learndash' ); ?></option>
				<option value='0' <?php echo $selected_0; ?>><?php esc_html_e( 'Not Approved', 'learndash' ); ?></option>
			</select>
		<?php
	}

	if ( in_array( $_GET['post_type'], $cpt_filters_shown['sfwd-quiz'] ) ) {

	}
}



/**
 * Queries the post listing data based on the user selections in the admin.
 *
 * @global string $pagenow
 * @global string $typenow
 *
 * @since 2.1.0
 *
 * @param WP_Query $query The `WP_Query` object.
 *
 * @return WP_Query The `WP_Query` object.
 */
function course_table_filter( $query ) {
	global $pagenow, $typenow;
	$q_vars = &$query->query_vars;

	if ( ! is_admin() ) {
		return;
	}
	if ( $pagenow != 'edit.php' ) {
		return;
	}
	if ( ! $query->is_main_query() ) {
		return;
	}
	if ( empty( $typenow ) ) {
		return;
	}

	/*
	if ( ( isset( $_GET['course_id'] ) ) && ( !empty( $_GET['course_id'] ) )
	  && ( $typenow == 'sfwd-lessons' || $typenow == 'sfwd-topic' || $typenow == 'sfwd-quiz' || $typenow == 'sfwd-assignment' || $typenow == 'sfwd-essays' ) ) {
			$q_vars['meta_query'][] = array(
				'key' => 'course_id',
				'value'	=> $_GET['course_id'],
			);
		}

		if ( ( isset($_GET['lesson_id'] ) ) && ( !empty( $_GET['lesson_id'] ) ) && ( $typenow == 'sfwd-topic' || $typenow == 'sfwd-assignment' || $typenow == 'sfwd-essays' ) ) {
			$q_vars['meta_query'][] = array(
				'key' => 'lesson_id',
				'value'	=> $_GET['lesson_id'],
			);
		}

		if ( ( isset( $_GET['quiz_id'] ) )  && ( !empty( $_GET['quiz_id'] ) ) && ( $typenow == 'sfwd-essays' ) ) {
			$q_vars['meta_query'][] = array(
				'key' 	=>	'quiz_id',
				'value'	=> 	intval( $_GET['quiz_id'] ),
			);
		}

		// set custom post status anytime we are looking at essays with no particular post status
		if ( ( isset( $_GET['post_status'] ) ) && ( !isset( $_GET['post_status'] ) ) && ( $typenow == 'sfwd-essays' ) ) {
			$q_vars['post_status'] = array( 'graded', 'not_graded' );
		}

		if ( ( isset( $_GET['approval_status'] ) ) && ( $typenow == 'sfwd-topic' || $typenow == 'sfwd-assignment' ) ) {
			if ( $_GET['approval_status'] == 1 ) {
				$q_vars['meta_query'][] = array(
					'key' 	=> 	'approval_status',
					'value'	=> 	1,
				);
			} else if ( $_GET['approval_status'] == 0 ) {
				$q_vars['meta_query'][] = array(
					'key' 		=> 	'approval_status',
					'compare' 	=> 	'NOT EXISTS',
				);
			}
		}
	}
	*/

	if ( $typenow == 'sfwd-courses' ) {

		if ( ( isset( $_GET['post_tag'] ) ) && ( ! empty( $_GET['post_tag'] ) ) ) {
			$post_tag = esc_attr( $_GET['post_tag'] );
			if ( $post_tag != '0' ) {
				$post_tag_term = get_term_by( 'slug', $post_tag, 'post_tag' );
				if ( ( ! empty( $post_tag_term ) ) && ( $post_tag_term instanceof WP_Term ) ) {
					$q_vars['tag_id'] = $post_tag_term->term_id;
				}
			}
		}
	} elseif ( $typenow == 'sfwd-lessons' ) {
		if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
				$course_steps_by_type = learndash_course_get_steps_by_type( intval( $_GET['course_id'] ), $typenow );
				if ( ! empty( $course_steps_by_type ) ) {
					$q_vars['post__in'] = $course_steps_by_type;
					$q_vars['orderby']  = 'post__in';
				}
			} else {
				if ( ! isset( $q_vars['meta_query'] ) ) {
					$q_vars['meta_query'] = array();
				}

				$q_vars['meta_query'][] = array(
					'key'   => 'course_id',
					'value' => intval( $_GET['course_id'] ),
				);
			}
		}
		if ( ( isset( $_GET['post_tag'] ) ) && ( ! empty( $_GET['post_tag'] ) ) ) {
			$post_tag = esc_attr( $_GET['post_tag'] );
			if ( $post_tag != '0' ) {
				$post_tag_term = get_term_by( 'slug', $post_tag, 'post_tag' );
				if ( ( ! empty( $post_tag_term ) ) && ( $post_tag_term instanceof WP_Term ) ) {
					$q_vars['tag_id'] = $post_tag_term->term_id;
				}
			}
		}
	} elseif ( $typenow == 'sfwd-topic' ) {
		if ( ( isset( $_GET['post_tag'] ) ) && ( ! empty( $_GET['post_tag'] ) ) ) {
			$post_tag = esc_attr( $_GET['post_tag'] );
			if ( $post_tag != '0' ) {
				$post_tag_term = get_term_by( 'slug', $post_tag, 'post_tag' );
				if ( ( ! empty( $post_tag_term ) ) && ( $post_tag_term instanceof WP_Term ) ) {
					$q_vars['tag_id'] = $post_tag_term->term_id;
				}
			}
		}

		if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
				$course_steps_by_type = learndash_course_get_steps_by_type( intval( $_GET['course_id'] ), $typenow );
				if ( ! empty( $course_steps_by_type ) ) {
					$q_vars['post__in'] = $course_steps_by_type;
					$q_vars['orderby']  = 'post__in';

				}
			} else {
				if ( ! isset( $q_vars['meta_query'] ) ) {
					$q_vars['meta_query'] = array();
				}

				$q_vars['meta_query'][] = array(
					'key'   => 'course_id',
					'value' => intval( $_GET['course_id'] ),
				);
			}
		}

		if ( ( isset( $_GET['lesson_id'] ) ) && ( ! empty( $_GET['lesson_id'] ) ) ) {
			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
				$lesson_topics = learndash_course_get_children_of_step( intval( $_GET['course_id'] ), intval( $_GET['lesson_id'] ), $typenow );
				if ( ! empty( $lesson_topics ) ) {
					$HAS_LESSONS        = true;
					$q_vars['post__in'] = $lesson_topics;
				}
			} else {

				if ( ! isset( $q_vars['meta_query'] ) ) {
					$q_vars['meta_query'] = array();
				}

				$q_vars['meta_query'][] = array(
					'key'   => 'lesson_id',
					'value' => intval( $_GET['lesson_id'] ),
				);
			}
		}
		$q_vars['relation'] = 'AND';

	} elseif ( $typenow == 'sfwd-quiz' ) {

		if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
				$quiz_ids = array();
				if ( ( isset( $_GET['lesson_id'] ) ) && ( ! empty( $_GET['lesson_id'] ) ) ) {
					$quiz_ids = learndash_course_get_children_of_step( intval( $_GET['course_id'] ), intval( $_GET['lesson_id'] ), 'sfwd-quiz' );
				} else {
					$quiz_ids = learndash_course_get_steps_by_type( intval( $_GET['course_id'] ), 'sfwd-quiz' );
				}

				if ( ! empty( $quiz_ids ) ) {
					$q_vars['post__in'] = $quiz_ids;
				} else {
					$q_vars['post__in'] = array( 0 );
				}
			}
		} else {
			if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
				if ( ! isset( $q_vars['meta_query'] ) ) {
					$q_vars['meta_query'] = array();
				}

				$q_vars['meta_query'][] = array(
					'key'   => 'course_id',
					'value' => intval( $_GET['course_id'] ),
				);
			}

			if ( ( isset( $_GET['lesson_id'] ) ) && ( ! empty( $_GET['lesson_id'] ) ) ) {
				if ( ! isset( $q_vars['meta_query'] ) ) {
					$q_vars['meta_query'] = array();
				}

				$q_vars['meta_query'][] = array(
					'key'   => 'lesson_id',
					'value' => intval( $_GET['lesson_id'] ),
				);
			}
		}
	} elseif ( $typenow == 'sfwd-assignment' ) {

		if ( ( isset( $_GET['approval_status'] ) ) && ( $_GET['approval_status'] == 1 ) ) {
			if ( ! isset( $q_vars['meta_query'] ) ) {
				$q_vars['meta_query'] = array();
			}

			$q_vars['meta_query'][] = array(
				'key'   => 'approval_status',
				'value' => 1,
			);
		} elseif ( ( isset( $_GET['approval_status'] ) ) && ( $_GET['approval_status'] == 0 ) ) {
			if ( ! isset( $q_vars['meta_query'] ) ) {
				$q_vars['meta_query'] = array();
			}

			$q_vars['meta_query'][] = array(
				'key'     => 'approval_status',
				'compare' => 'NOT EXISTS',
			);
		} elseif ( ! isset( $_GET['approval_status'] ) ) {
			///
		}

		if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
			if ( ! isset( $q_vars['meta_query'] ) ) {
				$q_vars['meta_query'] = array();
			}

			$q_vars['meta_query'][] = array(
				'key'   => 'course_id',
				'value' => intval( $_GET['course_id'] ),
			);
		}

		if ( ( isset( $_GET['lesson_id'] ) ) && ( ! empty( $_GET['lesson_id'] ) ) ) {
			if ( ! isset( $q_vars['meta_query'] ) ) {
				$q_vars['meta_query'] = array();
			}

			$q_vars['meta_query'][] = array(
				'key'   => 'lesson_id',
				'value' => intval( $_GET['lesson_id'] ),
			);
		}
	/*
	} elseif ( $typenow == 'groups' ) {
		if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
			$groups = learndash_get_course_groups( intval( $_GET['course_id'] ), true );
			if ( ! empty( $groups ) ) {
				$q_vars['post__in'] = $groups;
			} else {
				$q_vars['post__in'] = array( -1 );
			}
		}
	*/	
	} elseif ( $typenow == 'sfwd-essays' ) {
		if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
			if ( ! isset( $q_vars['meta_query'] ) ) {
				$q_vars['meta_query'] = array();
			}

			$q_vars['meta_query'][] = array(
				'key'   => 'course_id',
				'value' => intval( $_GET['course_id'] ),
			);
		}

		if ( ( isset( $_GET['lesson_id'] ) ) && ( ! empty( $_GET['lesson_id'] ) ) ) {
			if ( ! isset( $q_vars['meta_query'] ) ) {
				$q_vars['meta_query'] = array();
			}

			$q_vars['meta_query'][] = array(
				'key'   => 'lesson_id',
				'value' => intval( $_GET['lesson_id'] ),
			);
		}

		if ( ( isset( $_GET['quiz_id'] ) ) && ( ! empty( $_GET['quiz_id'] ) ) ) {
			if ( ! isset( $q_vars['meta_query'] ) ) {
				$q_vars['meta_query'] = array();
			}

			$q_vars['meta_query'][] = array(
				'key'   => 'quiz_id',
				'value' => intval( $_GET['quiz_id'] ),
			);
		}
	} 
	/*
	elseif ( $typenow == 'sfwd-transactions' ) {
		if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
			if ( ! isset( $q_vars['meta_query'] ) ) {
				$q_vars['meta_query'] = array();
			}

			$q_vars['meta_query'][] = array(
				'key'   => 'course_id',
				'value' => intval( $_GET['course_id'] ),
			);
		}

		if ( ( isset( $_GET['group_id'] ) ) && ( ! empty( $_GET['group_id'] ) ) ) {
			if ( ! isset( $q_vars['meta_query'] ) ) {
				$q_vars['meta_query'] = array();
			}

			$q_vars['meta_query'][] = array(
				'key'   => 'group_id',
				'value' => intval( $_GET['group_id'] ),
			);
		}
	}
	*/
	//  if ( isset( $q_vars['meta_query'] ) ) {
	//      error_log('meta_query<pre>'. print_r( $q_vars['meta_query'], true ) .'</pre>');
	//  } else {
	//      error_log('meta_query not set');
	//  }
}



/**
 * Generates the lesson IDs and course IDs once for all existing lessons, quizzes, and topics.
 *
 * Fires on `admin_init` hook.
 *
 * @since 2.1.0
 */
function learndash_generate_patent_course_and_lesson_id_onetime() {

	if ( isset( $_GET['learndash_generate_patent_course_and_lesson_ids_onetime'] ) || get_option( 'learndash_generate_patent_course_and_lesson_ids_onetime', 'yes' ) == 'yes' ) {
		$quizzes = get_posts( 'post_type=sfwd-quiz&posts_per_page=-1' );

		if ( ! empty( $quizzes ) ) {
			foreach ( $quizzes as $quiz ) {
				update_post_meta( $quiz->ID, 'course_id', learndash_get_course_id( $quiz->ID ) );
				$meta = get_post_meta( $quiz->ID, '_sfwd-quiz', true );
				if ( ! empty( $meta['sfwd-quiz_lesson'] ) ) {
					update_post_meta( $quiz->ID, 'lesson_id', $meta['sfwd-quiz_lesson'] );
				}
			}//exit;
		}

		$topics = get_posts( 'post_type=sfwd-topic&posts_per_page=-1' );

		if ( ! empty( $topics ) ) {
			foreach ( $topics as $topic ) {
				update_post_meta( $topic->ID, 'course_id', learndash_get_course_id( $topic->ID ) );
				$meta = get_post_meta( $topic->ID, '_sfwd-topic', true );
				if ( ! empty( $meta['sfwd-topic_lesson'] ) ) {
					update_post_meta( $topic->ID, 'lesson_id', $meta['sfwd-topic_lesson'] );
				}
			}
		}

		$lessons = get_posts( 'post_type=sfwd-lessons&posts_per_page=-1' );

		if ( ! empty( $lessons ) ) {
			foreach ( $lessons as $lesson ) {
				update_post_meta( $lesson->ID, 'course_id', learndash_get_course_id( $lesson->ID ) );
			}
		}

		update_option( 'learndash_generate_patent_course_and_lesson_ids_onetime', 'no' );

	}
}

add_action( 'admin_init', 'learndash_generate_patent_course_and_lesson_id_onetime' );



/**
 * Updates the post IDs that maintain relationships between
 * courses, lessons, topics, and quizzes on post save.
 *
 * Fires on `save_post` hook.
 *
 * @since 2.1.0
 *
 * @param int $post_id The ID of the post being saved.
 *
 * @return void|string Returns empty string if the post ID is empty.
 */
function learndash_patent_course_and_lesson_id_save( $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( empty( $post_id ) || empty( $_POST['post_type'] ) ) {
		return '';
	}

	// Check permissions
	if ( 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}
	} else {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	if ( 'sfwd-lessons' == $_POST['post_type'] || 'sfwd-quiz' == $_POST['post_type'] || 'sfwd-topic' == $_POST['post_type'] ) {
		if ( isset( $_POST[ $_POST['post_type'] . '_course' ] ) ) {
			update_post_meta( $post_id, 'course_id', (int) $_POST[ $_POST['post_type'] . '_course' ] );
		}
	}

	if ( 'sfwd-topic' == $_POST['post_type'] || 'sfwd-quiz' == $_POST['post_type'] ) {
		if ( isset( $_POST[ $_POST['post_type'] . '_lesson' ] ) ) {
			update_post_meta( $post_id, 'lesson_id', (int) $_POST[ $_POST['post_type'] . '_lesson' ] );
		}
	}

	if ( 'sfwd-lessons' == $_POST['post_type'] || 'sfwd-topic' == $_POST['post_type'] ) {
		global $wpdb;

		if ( isset( $_POST[ $_POST['post_type'] . '_course' ] ) ) {
			$course_id = (int) get_post_meta( $post_id, 'course_id', true );
		}

		if ( ! empty( $course_id ) ) {
			$posts_with_lesson = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'lesson_id' AND meta_value = '%d'", $post_id ) );

			if ( ! empty( $posts_with_lesson ) && ! empty( $posts_with_lesson[0] ) ) {
				foreach ( $posts_with_lesson as $post_with_lesson ) {
					$post_course_id = learndash_get_setting( $post_with_lesson, 'course' );

					if ( $post_course_id != $course_id ) {
						learndash_update_setting( $post_with_lesson, 'course', (int) $course_id );

						$quizzes_under_lesson_topic = $wpdb->get_col(
							$wpdb->prepare(
								'SELECT post_id FROM ' . $wpdb->postmeta . ' WHERE meta_key = %s AND meta_value = %d',
								'lesson_id',
								$posts_with_lesson
							)
						);
						if ( ! empty( $quizzes_under_lesson_topic ) && ! empty( $quizzes_under_lesson_topic[0] ) ) {
							foreach ( $quizzes_under_lesson_topic as $quiz_post_id ) {
								$quiz_course_id = learndash_get_setting( $quiz_post_id, 'course' );
								if ( $course_id != $quiz_course_id ) {
									learndash_update_setting( $quiz_course_id, 'course', (int) $course_id );
								}
							}
						}
					}
				}
			}
		}
	}
}

/**
 * Updates the post IDs that maintain relationships between
 * courses, lessons, topics, and quizzes on post save.
 *
 * @since 2.1.0
 *
 * @param int $post_id The ID of the post being saved.
 *
 * @return void|string Returns empty string if the post ID is empty.
 */
function learndash_patent_course_and_lesson_id_save_NEW( $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( empty( $post_id ) || empty( $_POST['post_type'] ) ) {
		return '';
	}

	// Check permissions
	//  if ( 'page' == $_POST['post_type'] ) {
	//      if ( ! current_user_can( 'edit_page', $post_id ) ) {
	//          return;
	//      }
	//  } else {
	//      if ( ! current_user_can( 'edit_post', $post_id ) ) {
	//          return;
	//      }
	//  }

	if ( in_array( $_POST['post_type'], array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) !== false ) {

		if ( current_user_can( 'edit_course', $post_id ) ) {

			// Update the associated course ID
			$course_id = 0;
			if ( isset( $_POST[ $_POST['post_type'] . '_course' ] ) ) {
				$course_id = intval( $_POST[ $_POST['post_type'] . '_course' ] );
			}

			update_post_meta( $post_id, 'course_id', $course_id );

			// Update the associated lesson ID
			if ( in_array( $_POST['post_type'], array( 'sfwd-topic', 'sfwd-quiz' ) ) !== false ) {
				$lesson_id = 0;
				if ( isset( $_POST[ $_POST['post_type'] . '_lesson' ] ) ) {
					$lesson_id = intval( $_POST[ $_POST['post_type'] . '_lesson' ] );
				}

				update_post_meta( $post_id, 'lesson_id', $lesson_id );
			}

			// If here the course_id was changes on a lesson or topic. So we now need to update any sub items (referenced by the post meta lesson_id)
			if ( in_array( $_POST['post_type'], array( 'sfwd-lessons', 'sfwd-topic' ) ) !== false ) {
				global $wpdb;

				if ( isset( $_POST[ $_POST['post_type'] . '_course' ] ) ) {
					$course_id = get_post_meta( $post_id, 'course_id', true );
				}

				if ( ! empty( $course_id ) ) {
					$posts_with_lesson = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'lesson_id' AND meta_value = '%d'", $post_id ) );

					if ( ! empty( $posts_with_lesson ) && ! empty( $posts_with_lesson[0] ) ) {
						foreach ( $posts_with_lesson as $post_with_lesson ) {
							$post_course_id = learndash_get_setting( $post_with_lesson, 'course' );

							if ( $post_course_id != $course_id ) {
								learndash_update_setting( $post_with_lesson, 'course', $course_id );

								$quizzes_under_lesson_topic = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'lesson_id' AND meta_value = '%d'", $posts_with_lesson ) );
								if ( ! empty( $quizzes_under_lesson_topic ) && ! empty( $quizzes_under_lesson_topic[0] ) ) {
									foreach ( $quizzes_under_lesson_topic as $quiz_post_id ) {
										$quiz_course_id = learndash_get_setting( $quiz_post_id, 'course' );
										if ( $course_id != $quiz_course_id ) {
											learndash_update_setting( $quiz_course_id, 'course', $course_id );
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
}

add_action( 'save_post', 'learndash_patent_course_and_lesson_id_save' );

/**
 * Prints the AJAX lazy loaded element results.
 *
 * Fires on `learndash_element_lazy_loader` AJAX action.
 */
function learndash_element_lazy_loader() {

	$reply_data = array();

	if ( current_user_can( 'read' ) ) {
		if ( ( isset( $_POST['query_data']['nonce'] ) ) && ( ! empty( $_POST['query_data']['nonce'] ) ) ) {
			if ( ( isset( $_POST['query_data']['query_vars']['post_type'] ) ) && ( ! empty( $_POST['query_data']['query_vars']['post_type'] ) ) ) {
				if ( wp_verify_nonce( $_POST['query_data']['nonce'], esc_attr( $_POST['query_data']['query_vars']['post_type'] ) ) ) {

					if ( ( isset( $_POST['query_data']['query_vars'] ) ) && ( ! empty( $_POST['query_data']['query_vars'] ) ) ) {
						$reply_data['query_data'] = $_POST['query_data'];

						if ( isset( $_POST['query_data']['query_type'] ) ) {
							switch ( $_POST['query_data']['query_type'] ) {
								case 'WP_Query':
									$query = new WP_Query( $_POST['query_data']['query_vars'] );
									if ( $query instanceof WP_Query ) {
										if ( ! empty( $query->posts ) ) {
											$reply_data['html_options'] = '';
											foreach ( $query->posts as $p ) {
												if ( intval( $p->ID ) == intval( $_POST['query_data']['value'] ) ) {
													$selected = ' selected="selected" ';
												} else {
													$selected = '';
												}
												$reply_data['html_options'] .= '<option ' . $selected . ' value="' . $p->ID . '">' . apply_filters( 'the_title', $p->post_title, $p->ID ) . '</option>';
											}
										}
									}
									break;

								case 'WP_User_Query':
									$query = new WP_User_Query( $_POST['query_data']['query_vars'] );
									break;

								default:
									break;
							}
						}
					}
				}
			}
		}
	}
	
	echo json_encode( $reply_data );

	wp_die(); // this is required to terminate immediately and return a proper response
}

add_action( 'wp_ajax_learndash_element_lazy_loader', 'learndash_element_lazy_loader' );


add_filter( 'views_edit-sfwd-essays', 'learndash_edit_list_table_views', 10, 1 );
add_filter( 'views_edit-sfwd-assignment', 'learndash_edit_list_table_views', 10, 1 );

/**
 * Hides the list table views for non admin users.
 *
 * Fires on `views_edit-sfwd-essays` and `views_edit-sfwd-assignment` hook.
 *
 * @param array $views Optional. An array of available list table views. Default empty array.
 */
function learndash_edit_list_table_views( $views = array() ) {
	if ( ! learndash_is_admin_user() ) {
		$views = array();
	}

	return $views;
}

add_filter( 'plugin_row_meta', 'learndash_plugin_row_meta', 10, 4 );

/**
 * Adds the changelog link in plugin row meta.
 *
 * Fires on `plugin_row_meta` hook.
 *
 * @param array  $plugin_meta An array of the plugin's metadata.
 * @param string $plugin_file  Path to the plugin file.
 * @param array  $plugin_data An array of plugin data.
 * @param string $status      Status of the plugin.
 *
 * @return array An array of the plugin's metadata.
 */
function learndash_plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
	if ( $plugin_file == LEARNDASH_LMS_PLUGIN_KEY ) {
		if ( ! isset( $plugin_meta['changelog'] ) ) {
			$plugin_meta['changelog'] = '<a target="_blank" href="https://www.learndash.com/changelog">' . esc_html__( 'Changelog', 'learndash' ) . '</a>';
		}
	}

	return $plugin_meta;
}


/**
 * Overrides the post tag edit 'count' column to show only the related count for the LearnDash post types.
 *
 * Fires on `manage_edit-post_tag_columns` and `manage_edit-category_columns` hook.
 *
 * @param array $columns Optional. An array of column headers. Default empty array.
 *
 * @return array An array of column headers.
 */
function learndash_manage_edit_post_tag_columns( $columns = array() ) {
	if ( ( isset( $_GET['post_type'] ) ) && ( ! empty( $_GET['post_type'] ) ) ) {
		if ( in_array( $_GET['post_type'], array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic' ) ) ) {
			if ( isset( $columns['posts'] ) ) {
				unset( $columns['posts'] );
			}
			$columns['ld_posts'] = esc_html__( 'Count', 'learndash' );
		}
	}

	return $columns;
}
add_filter( 'manage_edit-post_tag_columns', 'learndash_manage_edit_post_tag_columns' );
//function learndash_manage_edit_category_columns( $columns = array() ) {
//	//error_log('columns<pre>'. print_r($columns, true) .'</pre>');
//	if ( ( isset( $_GET['post_type'] ) ) && ( !empty( $_GET['post_type'] ) ) ) {
//		if ( isset( $columns['posts'] ) ) unset( $columns['posts'] );
//
//		$columns['ld_posts'] = esc_html__( 'Count', 'learndash' );
//	}
//
//    return $columns;
//}
add_filter( 'manage_edit-category_columns', 'learndash_manage_edit_post_tag_columns' );

/**
 * Gets the custom column content for post_tag taxonomy in the terms list table.
 *
 * Fires on `manage_post_tag_custom_column` hook.
 *
 * @param string $column_content Optional. Column content. Default empty.
 * @param string $column_name    Name of the column.
 * @param int    $term_id        Term ID.
 *
 * @return string Taxonomy custom column content.
 */
function learndash_manage_post_tag_custom_column( $column_content = '', $column_name, $term_id ) {
	if ( $column_name == 'ld_posts' ) {
		if ( ( isset( $_GET['post_type'] ) ) && ( ! empty( $_GET['post_type'] ) ) ) {
			if ( in_array( $_GET['post_type'], array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic' ) ) ) {
				$query_args = array(
					'post_type'   => esc_attr( $_GET['post_type'] ),
					'post_status' => 'publish',
					'tag_id'      => $term_id,
					'fields'      => 'ids',
					'nopaging'    => true,
				);

				$query_results = new WP_Query( $query_args );
				if ( ! is_wp_error( $query_results ) ) {
					$count = count( $query_results->posts );
					if ( $count > 0 ) {
						$term           = get_term_by( 'id', $term_id, 'category' );
						$column_content = "<a href='" . esc_url(
							add_query_arg(
								array(
									'post_type' => esc_attr( $_GET['post_type'] ),
									'taxonomy'  => 'post_tag',
									'post_tag'  => $term->slug,
								),
								'edit.php'
							)
						) . "'>" . count( $query_results->posts ) . '</a>';
					} else {
						$column_content = 0;
					}
				}
			}
		}
	}
	return $column_content;
}
add_filter( 'manage_post_tag_custom_column', 'learndash_manage_post_tag_custom_column', 10, 3 );

/**
 * Gets the custom column content for category taxonomy in the terms list table.
 *
 * Fires on `manage_category_custom_column` hook.
 *
 * @param string $column_content Optional. Column content. Default empty.
 * @param string $column_name    Name of the column.
 * @param int    $term_id        Term ID.
 *
 * @return string Taxonomy custom column content.
 */
function learndash_manage_category_custom_column( $column_content = '', $column_name, $term_id ) {
	if ( $column_name == 'ld_posts' ) {
		if ( ( isset( $_GET['post_type'] ) ) && ( ! empty( $_GET['post_type'] ) ) ) {
			if ( in_array( $_GET['post_type'], array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic' ) ) ) {
				$query_args = array(
					'post_type'   => esc_attr( $_GET['post_type'] ),
					'post_status' => 'publish',
					'cat'         => $term_id,
					'fields'      => 'ids',
					'nopaging'    => true,
				);

				$query_results = new WP_Query( $query_args );
				if ( ! is_wp_error( $query_results ) ) {
					$count = count( $query_results->posts );
					if ( $count > 0 ) {
						//$term = get_term_by('id', $term_id, 'category');
						$column_content = "<a href='" . esc_url(
							add_query_arg(
								array(
									'post_type' => esc_attr( $_GET['post_type'] ),
									'taxonomy'  => 'category',
									'cat'       => $term_id,
								),
								'edit.php'
							)
						) . "'>" . count( $query_results->posts ) . '</a>';
					} else {
						$column_content = 0;
					}
				}
			}
		}
	}
	return $column_content;
}
add_filter( 'manage_category_custom_column', 'learndash_manage_category_custom_column', 10, 3 );

/**
 * Deletes all the LearnDash data.
 *
 * @global wpdb  $wpdb                 WordPress database abstraction object.
 * @global array $learndash_post_types An array of learndash post types.
 * @global array $learndash_taxonomies An array of learndash taxonomies.
 */
function learndash_delete_all_data() {
	global $wpdb, $learndash_post_types, $learndash_taxonomies;

	/**
	 * Under Multisite we don't even want to remove user data. This is because users and usermeta
	 * is shared. Removing the LD user data could result in lost information for other sites.
	 */
	if ( ! is_multisite() ) {
		// USER META SETTINGS
		//////////////////////////////

		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key='_sfwd-course_progress'" );
		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key='_sfwd-quizzes'" );

		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key LIKE 'completed_%'" );
		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key LIKE 'course_%_access_from'" );
		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key LIKE 'course_completed_%'" );
		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key LIKE 'learndash_course_expired_%'" );
		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key LIKE 'learndash_group_users_%'" );
		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key LIKE 'learndash_group_leaders_%'" );

		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key = 'ld-upgraded-user-meta-courses'" );
		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key = 'ld-upgraded-user-meta-quizzes'" );
		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key = 'course_points'" );
	}

	// CUSTOM OPTIONS
	//////////////////////////////

	$wpdb->query( 'DELETE FROM ' . $wpdb->options . " WHERE option_name LIKE 'learndash_%'" );
	$wpdb->query( 'DELETE FROM ' . $wpdb->options . " WHERE option_name LIKE 'wpProQuiz_%'" );

	// CUSTOMER POST TYPES
	//////////////////////////////

	$ld_post_types = '';
	foreach ( $learndash_post_types as $post_type ) {
		if ( ! empty( $ld_post_types ) ) {
			$ld_post_types .= ',';
		}
		$ld_post_types .= "'" . $post_type . "'";
	}

	$post_ids = $wpdb->get_col( 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_type IN (' . $ld_post_types . ')' );
	if ( ! empty( $post_ids ) ) {

		$offset = 0;

		while ( true ) {
			$post_ids_part = array_slice( $post_ids, $offset, 1000 );
			if ( empty( $post_ids_part ) ) {
				break;
			} else {
				$wpdb->query( 'DELETE FROM ' . $wpdb->postmeta . ' WHERE post_id IN (' . implode( ',', $post_ids ) . ')' );
				$wpdb->query( 'DELETE FROM ' . $wpdb->posts . ' WHERE post_parent IN (' . implode( ',', $post_ids ) . ')' );
				$wpdb->query( 'DELETE FROM ' . $wpdb->posts . ' WHERE ID IN (' . implode( ',', $post_ids ) . ')' );

				$offset += 1000;
			}
		}
	}

	// CUSTOM TAXONOMIES & TERMS
	//////////////////////////////

	foreach ( $learndash_taxonomies as $taxonomy ) {
		// Prepare & excecute SQL
		$terms = $wpdb->get_results( $wpdb->prepare( "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN ('%s') ORDER BY t.name ASC", $taxonomy ) );

			// Delete Terms
		if ( $terms ) {
			foreach ( $terms as $term ) {
				$wpdb->delete( $wpdb->term_taxonomy, array( 'term_taxonomy_id' => $term->term_taxonomy_id ) );
				$wpdb->delete( $wpdb->terms, array( 'term_id' => $term->term_id ) );
			}
		}

		// Delete Taxonomy
		$wpdb->delete( $wpdb->term_taxonomy, array( 'taxonomy' => $taxonomy ), array( '%s' ) );
	}

	// CUSTOM DB TABLES
	//////////////////////////////
	$learndash_db_tables = LDLMS_DB::get_tables();
	if ( ! empty( $learndash_db_tables ) ) {
		foreach ( $learndash_db_tables as $table_name ) {
			//$table_name = $wpdb->prefix . $table_name;
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name ) {
				$wpdb->query( 'DROP TABLE ' . $table_name );
			}
		}
	}

	// USER ROLES AND CAPABILITIES
	//////////////////////////////

	remove_role( 'group_leader' );

	// Remove any user/role capabilities we added
	$roles = get_editable_roles();
	if ( ! empty( $roles ) ) {
		foreach ( $roles as $role_name => $role_info ) {
			$role = get_role( $role_name );
			if ( ( $role ) && ( $role instanceof WP_Role ) ) {
				$role->remove_cap( 'read_assignment' );
				$role->remove_cap( 'edit_assignment' );
				$role->remove_cap( 'edit_assignments' );
				$role->remove_cap( 'edit_others_assignments' );
				$role->remove_cap( 'publish_assignments' );
				$role->remove_cap( 'read_assignment' );
				$role->remove_cap( 'read_private_assignments' );
				$role->remove_cap( 'delete_assignment' );
				$role->remove_cap( 'edit_published_assignments' );
				$role->remove_cap( 'delete_others_assignments' );
				$role->remove_cap( 'delete_published_assignments' );

				$role->remove_cap( 'group_leader' );
				$role->remove_cap( 'enroll_users' );

				$role->remove_cap( 'edit_essays' );
				$role->remove_cap( 'edit_others_essays' );
				$role->remove_cap( 'publish_essays' );
				$role->remove_cap( 'read_essays' );
				$role->remove_cap( 'read_private_essays' );
				$role->remove_cap( 'delete_essays' );
				$role->remove_cap( 'edit_published_essays' );
				$role->remove_cap( 'delete_others_essays' );
				$role->remove_cap( 'delete_published_essays' );

				$role->remove_cap( 'wpProQuiz_show' );
				$role->remove_cap( 'wpProQuiz_add_quiz' );
				$role->remove_cap( 'wpProQuiz_edit_quiz' );
				$role->remove_cap( 'wpProQuiz_delete_quiz' );
				$role->remove_cap( 'wpProQuiz_show_statistics' );
				$role->remove_cap( 'wpProQuiz_reset_statistics' );
				$role->remove_cap( 'wpProQuiz_import' );
				$role->remove_cap( 'wpProQuiz_export' );
				$role->remove_cap( 'wpProQuiz_change_settings' );
				$role->remove_cap( 'wpProQuiz_toplist_edit' );
				$role->remove_cap( 'wpProQuiz_toplist_edit' );
			}
		}
	}

	// ASSIGNMENT & ESSAY UPLOADS
	//////////////////////////////

	$url_link_arr   = wp_upload_dir();
	$assignment_dir = $url_link_arr['basedir'] . '/assignments';
	learndash_recursive_rmdir( $assignment_dir );

	$essays_dir = $url_link_arr['basedir'] . '/essays';
	learndash_recursive_rmdir( $essays_dir );

	$ld_template_dir = $url_link_arr['basedir'] . '/template';
	learndash_recursive_rmdir( $ld_template_dir );
}


/**
 * Adds groups column to user list table.
 *
 * Fires on `manage_users_columns` hook.
 *
 * @param array $columns Optional. An array of column headers. Default empty array.
 *
 * @return array An array of column headers.
 */
function learndash_user_list_columns( $columns = array() ) {
	if ( ! isset( $columns['courses'] ) ) {
		$columns['courses'] = LearnDash_Custom_Label::get_label( 'courses' );
	}

	if ( ! isset( $columns['groups'] ) ) {
		$columns['groups'] = LearnDash_Custom_Label::get_label( 'groups' );
	}

	return $columns;
}
add_filter( 'manage_users_columns', 'learndash_user_list_columns' );

/**
 * Gets the custom column content for user list table.
 *
 * Fires on `manage_users_custom_column` hook.
 *
 * @param string $column_content Optional. Column content. Default empty.
 * @param string $column_name    Optional. Name of the column. Default empty.
 * @param int    $user_id        Optional. User ID. Default 0.
 *
 * @return string Users custom column content.
 */
function learndash_user_list_column_content( $column_content = '', $column_name = '', $user_id = 0 ) {
	switch ( $column_name ) {

		case 'courses':
			$user_courses = learndash_user_get_enrolled_courses( $user_id );
			if ( empty( $user_courses ) ) {
				$user_courses = array();
			}

			if ( ! empty( $user_courses ) ) {
				// translators: placeholder: user courses count.
				$column_content .= sprintf( esc_html_x( 'Total %s', 'placeholder: user courses count', 'learndash' ), count( $user_courses ) );

				$course_names = '';

				if ( count( $user_courses ) > 5 ) {
					$user_courses = array_slice( $user_courses, 0, 5 );
				}

				foreach ( $user_courses as $course_id ) {
					$course = get_post( $course_id );
					if ( ! empty( $course_names ) ) {
						$course_names .= ', ';
					}
					$course_names .= '<a href="' . get_edit_post_link( $course_id ) . '">' . get_the_title( $course_id ) . '</a>';
				}

				if ( ! empty( $course_names ) ) {
					$column_content .= '<br />' . $course_names;
				}
			}
			break;

		case 'groups':
			$user_groups = learndash_get_users_group_ids( $user_id, true );
			if ( empty( $user_groups ) ) {
				$user_groups = array();
			}

			if ( ! empty( $user_groups ) ) {
				// translators: placeholder: count user groups.
				$column_content .= sprintf( __( 'Total %d', 'placeholder: count user groups', 'learndash' ), count( $user_groups ) );

				$groups_names = '';

				if ( count( $user_groups ) > 5 ) {
					$user_groups = array_slice( $user_groups, 0, 5 );
				}

				foreach ( $user_groups as $group_id ) {
					$group = get_post( $group_id );
					if ( ! empty( $groups_names ) ) {
						$groups_names .= ', ';
					}
					$groups_names .= '<a href="' . get_edit_post_link( $group_id ) . '">' . get_the_title( $group_id ) . '</a>';
				}

				if ( ! empty( $groups_names ) ) {
					$column_content .= '<br />' . $groups_names;
				}
			}
			break;

		default:
	}

	return $column_content;
}
add_filter( 'manage_users_custom_column', 'learndash_user_list_column_content', 10, 3 );

/**
 * Adds the user course filter in admin.
 *
 * Fires on `restrict_manage_users` hook.
 *
 * @param string $which Optional. The location of the extra table nav markup: 'top' or 'bottom'. Default empty.
 */
function learndash_add_user_nav_filter( $which = '' ) {

	$filter_output = '';

	if ( $which === 'top' ) {

		$SHOW_USER_COURSES_FILTER = true;

		$query_options_course = array(
			'post_type'      => 'sfwd-courses',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		if ( learndash_is_group_leader_user( get_current_user_id() ) ) {
			$group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
			if ( ! empty( $group_ids ) && is_array( $group_ids ) ) {
				$course_ids = array();

				foreach ( $group_ids as $group_id ) {
					$group_course_ids = learndash_group_enrolled_courses( $group_id );
					if ( ! empty( $group_course_ids ) && is_array( $group_course_ids ) ) {
						$course_ids = array_merge( $course_ids, $group_course_ids );
					}
				}
				if ( ! empty( $course_ids ) && count( $course_ids ) ) {
					$query_options_course['post__in'] = $course_ids;
				} else {
					$SHOW_USER_COURSES_FILTER = false;
				}
			} else {
				$SHOW_USER_COURSES_FILTER = false;
			}
		}
		/** This filter is documented in includes/class-ld-lms.php */
		$lazy_load = apply_filters( 'learndash_element_lazy_load_admin', true );
		if ( $lazy_load == true ) {
			/**
			 * Filters whether to lazy load admin settings for users filter or not.
			 *
			 * @param boolean $lazy_load Whether to lazy load users filter or not.
			 */
			$lazy_load = apply_filters( 'learndash_element_lazy_load_admin_users_filters', true );
			if ( $lazy_load == true ) {
				$query_options_course['paged']          = 1;
				/** This filter is documented in includes/class-ld-lms.php */
				$query_options_course['posts_per_page'] = apply_filters( 'learndash_element_lazy_load_per_page', LEARNDASH_LMS_DEFAULT_LAZY_LOAD_PER_PAGE, 'sfwd-courses' );
			}
		}

		/**
		 * Filters users filter query arguments.
		 *
		 * @param array  $query_options_course An array of users filter query arguments.
		 * @param string $post_type            Post type to check.
		 */
		$query_options_course = apply_filters( 'learndash_user_courses_options_filter', $query_options_course, 'sfwd-courses' );

		$query_posts_course = new WP_Query( $query_options_course );

		if ( ! empty( $query_posts_course->posts ) ) {
			if ( count( $query_posts_course->posts ) >= $query_posts_course->found_posts ) {
				// If the number of returned posts is equal or greater then found_posts then no need to run lazy load
				$lazy_load = false;
			}

			$post_type_nonce = wp_create_nonce( 'sfwd-courses' );
			if ( $lazy_load == true ) {
				$lazy_load_data               = array();
				$lazy_load_data['query_vars'] = $query_options_course;
				$lazy_load_data['query_type'] = 'WP_Query';
				$lazy_load_data['nonce']      = $post_type_nonce;

				if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
					$lazy_load_data['value'] = intval( $_GET['course_id'] );
				} else {
					$lazy_load_data['value'] = 0;
				}

				$lazy_load_data = ' learndash_lazy_load_data="' . htmlspecialchars( json_encode( $lazy_load_data ) ) . '" ';
			} else {
				$lazy_load_data = '';
			}

			$filter_output .= '<select ' . $lazy_load_data . ' name="course_id" id="course_id" class="postform" data-ld_selector_nonce="' . $post_type_nonce . '" style="max-width: 200px;">';
			// translators: placeholder: Courses.
			$filter_output .= '<option value="">' . sprintf( esc_html_x( 'Show All %s', 'placeholder: Courses', 'learndash' ), LearnDash_Custom_Label::get_label( 'courses' ) ) . '</option>';
			if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
				$selected_course_id = intval( $_GET['course_id'] );
			} else {
				$selected_course_id = 0;
			}
			foreach ( $query_posts_course->posts as $p ) {
				$filter_output .= '<option value="' . absint( $p->ID ) . '" ' . selected( $selected_course_id, $p->ID, false ) . '>' . apply_filters( 'the_title', $p->post_title, $p->ID ) . '</option>';
			}
			$filter_output .= '</select>';

			$lazy_load_spinner = '<span style="display:none;" class="learndash_lazy_loading"><img class="learndash_lazy_load_spinner" alt="' . esc_html__( 'loading', 'learndash' ) . '" src="' . admin_url( '/images/wpspin_light.gif' ) . '" /> </span>';
			$filter_output    .= $lazy_load_spinner;
		}

		$SHOW_USER_GROUPS_FILTER = true;

		$query_options_group = array(
			'post_type'      => 'groups',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		if ( learndash_is_group_leader_user( get_current_user_id() ) ) {
			$group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
			if ( ! empty( $group_ids ) && count( $group_ids ) ) {
				$query_options_group['post__in'] = $group_ids;
			} else {
				$SHOW_USER_GROUPS_FILTER = false;
			}
		}

		if ( $SHOW_USER_GROUPS_FILTER === true ) {
			/** This filter is documented in includes/class-ld-lms.php */
			$lazy_load = apply_filters( 'learndash_element_lazy_load_admin', true );
			if ( $lazy_load == true ) {
				/** This filter is documented in includes/admin/ld-admin.php */
				$lazy_load = apply_filters( 'learndash_element_lazy_load_admin_users_filters', true );
				if ( $lazy_load == true ) {
					$query_options_group['paged']          = 1;
					/** This filter is documented in includes/class-ld-lms.php */
					$query_options_group['posts_per_page'] = apply_filters( 'learndash_element_lazy_load_per_page', LEARNDASH_LMS_DEFAULT_LAZY_LOAD_PER_PAGE, 'groups' );
				}
			}

			/**
			 * Filters user groups filter query arguments.
			 *
			 * @param array  $query_options_group An array of user groups filter query arguments.
			 * @param string $post_type           Post type to check.
			 */
			$query_options_group = apply_filters( 'learndash_user_groups_options_filter', $query_options_group, 'groups' );

			$query_user_groups = new WP_Query( $query_options_group );

			if ( ! empty( $query_user_groups->posts ) ) {
				if ( count( $query_user_groups->posts ) >= $query_user_groups->found_posts ) {
					// If the number of returned posts is equal or greater then found_posts then no need to run lazy load
					$lazy_load = false;
				}

				$post_type_nonce = wp_create_nonce( 'sfwd-courses' );
				if ( $lazy_load == true ) {
					$lazy_load_data               = array();
					$lazy_load_data['query_vars'] = $query_options_group;
					$lazy_load_data['query_type'] = 'WP_Query';
					$lazy_load_data['nonce']      = $post_type_nonce;

					if ( ( isset( $_GET['group_id'] ) ) && ( ! empty( $_GET['group_id'] ) ) ) {
						$lazy_load_data['value'] = intval( $_GET['group_id'] );
					} else {
						$lazy_load_data['value'] = 0;
					}

					$lazy_load_data = ' learndash_lazy_load_data="' . htmlspecialchars( json_encode( $lazy_load_data ) ) . '" ';
				} else {
					$lazy_load_data = '';
				}

				$filter_output .= '<select ' . $lazy_load_data . ' name="group_id" id="group_id" class="postform" data-ld_selector_nonce="' . $post_type_nonce . '" style="max-width: 200px;">';
				$filter_output .= '<option value="">' . sprintf(
					// translators: placeholder: Groups.
					esc_html_x( 'Show All %s', 'placeholder: Group', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'groups' )
				) . '</option>';

				if ( ( isset( $_GET['group_id'] ) ) && ( ! empty( $_GET['group_id'] ) ) ) {
					$selected_group_id = intval( $_GET['group_id'] );
				} else {
					$selected_group_id = 0;
				}
				foreach ( $query_user_groups->posts as $p ) {
					$filter_output .= '<option value="' . absint( $p->ID ) . '" ' . selected( $selected_group_id, $p->ID, false ) . '>' . apply_filters( 'the_title', $p->post_title, $p->ID ) . '</option>';
				}
				$filter_output .= '</select>';

				$lazy_load_spinner = '<span style="display:none;" class="learndash_lazy_loading"><img class="learndash_lazy_load_spinner" alt="' . esc_html__( 'loading', 'learndash' ) . '" src="' . admin_url( '/images/wpspin_light.gif' ) . '" /> </span>';
				$filter_output    .= $lazy_load_spinner;
			}
		}

		if ( ( $SHOW_USER_GROUPS_FILTER === true ) || ( $SHOW_USER_COURSES_FILTER === true ) ) {
			$button_id = 'bottom' === $which ? 'ld_submit' : 'ld_submit_bottom';

			$filter_output .= get_submit_button( esc_html__( 'Filter', 'learndash' ), 'learndash', $button_id, false );
		}

		if ( ! empty( $filter_output ) ) {
			$filter_output = '</div><div class="alignleft actions">' . $filter_output . '';
			echo $filter_output;
		}
	}
}
//add_action( 'manage_users_extra_tablenav', 'learndash_add_user_nav_filter' );
add_action( 'restrict_manage_users', 'learndash_add_user_nav_filter', 99 );

/**
 * Handles the filtering for user listing.
 *
 * @global string $pagenow
 *
 * @param WP_Query $query The `WP_Query` instance (passed by reference).
 */
function learndash_filter_users_listing( $query ) {
	global $pagenow;

	remove_filter( 'pre_get_users', 'learndash_filter_users_listing' );

	if ( is_admin() && 'users.php' == $pagenow ) {
		if ( isset( $_GET['group_id'] ) ) {
			$filter_group_id = intval( $_GET['group_id'] );
			if ( ! empty( $filter_group_id ) ) {
				if ( learndash_is_group_leader_user( get_current_user_id() ) ) {
					$group_ids = learndash_get_administrators_group_ids( get_current_user_id() );

					// If the Group Leader doesn't have groups or not a managed group them clear our selected group_id
					if ( ( empty( $group_ids ) ) || ( in_array( $filter_group_id, $group_ids ) === false ) ) {
						$filter_group_id = 0;
					}
				}

				if ( ! empty( $filter_group_id ) ) {
					$query->set( 'meta_key', 'learndash_group_users_' . $filter_group_id );
					$query->set( 'meta_value', $filter_group_id );
					$query->set( 'meta_compare', '=' );
				}
			}
		}

		// @TODO : Need to figure out how to force no results on when no users are found for a course.
		// @TODO : How to filter all users not enrolled in at least on course.
		if ( isset( $_GET['course_id'] ) ) {
			$filter_course_id = intval( $_GET['course_id'] );
			if ( ! empty( $filter_course_id ) ) {
				if ( learndash_is_group_leader_user( get_current_user_id() ) ) {
					$group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
					if ( ! empty( $group_ids ) && is_array( $group_ids ) ) {
						$course_ids = array();
						foreach ( $group_ids as $group_id ) {
							$group_course_ids = learndash_group_enrolled_courses( $group_id );
							if ( ! empty( $group_course_ids ) && is_array( $group_course_ids ) ) {
								$course_ids = array_merge( $course_ids, $group_course_ids );
							}
						}
						if ( empty( $course_ids ) ) {
							$filter_course_id = 0;
						}
					}
				}

				if ( ! empty( $filter_course_id ) ) {
					$course_users_query = learndash_get_users_for_course( $filter_course_id, array(), false );
					if ( ( $course_users_query instanceof WP_User_Query ) && ( property_exists( $course_users_query, 'results' ) ) && ( ! empty( $course_users_query->results ) ) ) {
						$user_ids = $course_users_query->get_results();
						if ( ! empty( $user_ids ) ) {
							$query->set( 'include', $user_ids );
						}
					} else {
						$query->set( 'include', array( 0 ) );
					}
				}
			}
		}
	}
}
add_filter( 'pre_get_users', 'learndash_filter_users_listing' );


/**
 * Loads the plugin translations into `wp.i18n` for use in JavaScript.
 *
 * @since 3.0.0
 */
function learndash_load_inline_script_locale_data() {
	static $loaded = false;

	if ( false === $loaded ) {
		$loaded      = true;
		$locale_data = learndash_get_jed_locale_data( LEARNDASH_LMS_TEXT_DOMAIN );
		wp_add_inline_script(
			'wp-i18n',
			'wp.i18n.setLocaleData( ' . json_encode( $locale_data ) . ', "learndash" );'
		);
	}
}

/**
 * Loads the translations MO file into memory.
 *
 * @since 3.0.0
 *
 * @return array An array of translated strings.
 */
function learndash_get_jed_locale_data() {
	$translations = get_translations_for_domain( LEARNDASH_LMS_TEXT_DOMAIN );

	$locale = array(
		'' => array(
			'domain' => LEARNDASH_LMS_TEXT_DOMAIN,
			'lang'   => is_admin() ? get_user_locale() : get_locale(),
		),
	);

	if ( ! empty( $translations->headers['Plural-Forms'] ) ) {
		$locale['']['plural_forms'] = $translations->headers['Plural-Forms'];
	}

	foreach ( $translations->entries as $msgid => $entry ) {
		$locale[ $msgid ] = $entry->translations;
	}

	return $locale;
}
