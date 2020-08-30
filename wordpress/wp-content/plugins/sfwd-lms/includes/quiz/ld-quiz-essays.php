<?php
/**
 * Adds ability to have "Essay / Open Answer" questions in Wp Pro Quiz
 *
 * @since 2.2.0
 *
 * @package LearnDash\Essay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the essay post type.
 *
 * Holds the responses of essay questions submitted by the user.
 * Fires on `init` hook.
 *
 * @since 2.2.0
 */
function learndash_register_essay_post_type() {
	
	$labels = array(
		'name'               => esc_html_x( 'Submitted Essays', 'Post Type General Name', 'learndash' ),
		'singular_name'      => esc_html_x( 'Submitted Essay', 'Post Type Singular Name', 'learndash' ),
		'menu_name'          => esc_html__( 'Submitted Essays', 'learndash' ),
		'name_admin_bar'     => esc_html__( 'Submitted Essays', 'learndash' ),
		'parent_item_colon'  => esc_html__( 'Parent Submitted Essay:', 'learndash' ),
		'all_items'          => esc_html__( 'All Submitted Essays', 'learndash' ),
		'add_new_item'       => esc_html__( 'Add New Submitted Essay', 'learndash' ),
		'add_new'            => esc_html__( 'Add New', 'learndash' ),
		'new_item'           => esc_html__( 'New Submitted Essay', 'learndash' ),
		'edit_item'          => esc_html__( 'Edit Submitted Essay', 'learndash' ),
		'update_item'        => esc_html__( 'Update Submitted Essay', 'learndash' ),
		'view_item'          => esc_html__( 'View Submitted Essay', 'learndash' ),
		'view_items'         => esc_html__( 'View Submitted Essays', 'learndash' ),
		'search_items'       => esc_html__( 'Search Submitted Essays', 'learndash' ),
		'not_found'          => esc_html__( 'Submitted Essay Not found', 'learndash' ),
		'not_found_in_trash' => esc_html__( 'Submitted Essay Not found in Trash', 'learndash' ),
		'item_published'	 =>	esc_html__( 'Submitted Essay Published', 'learndash' ),
		'item_published_privately' => esc_html__( 'Submitted Essay Published Privately', 'learndash' ),
		'item_reverted_to_draft' => esc_html__( 'Submitted Essay Reverted to Draft', 'learndash' ),
		'item_scheduled'	 =>	esc_html__( 'Submitted Essay Scheduled', 'learndash' ),
		'item_updated'		 =>	esc_html__( 'Submitted Essay Updated', 'learndash' ),

	);

	$capabilities = array(
		'edit_essay'          => 'edit_essay',
		'read_essay'          => 'read_essay',
		'delete_essay'        => 'delete_essay',
		'edit_essays'         => 'edit_essays',
		'edit_others_essays'  => 'edit_others_essays',
		'publish_essays'      => 'publish_essays',
		'read_private_essays' => 'read_private_essays',
	);

	if ( learndash_is_admin_user() ) {
		$show_in_admin_bar = false;
	} else if ( learndash_is_group_leader_user() ) {
		$show_in_admin_bar = false;
	} else {
		$show_in_admin_bar = false;
	}

	$args = array(
		'label'               => esc_html__( 'sfwd-essays', 'learndash' ),
		'description'         => esc_html__( 'Submitted essays via a quiz question.', 'learndash' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'comments', 'author'),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => false,
		'show_in_admin_bar'	  => $show_in_admin_bar,
		'query_var' 		  => true,
		'rewrite' 			  => array( 'slug' => 'essay' ), 
		'menu_position'       => 5,
		'show_in_admin_bar'   => false,
		'show_in_nav_menus'   => false,
		'can_export'          => true,
		'has_archive'         => false,
		'show_in_rest'        => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => true,
		'capability_type'     => 'essay',
		'capabilities'        => $capabilities,
		'map_meta_cap'        => true,
	);
	/** This filter is documented in includes/ld-assignment-uploads.php */
	$args = apply_filters( 'learndash-cpt-options', $args, 'sfwd-essays' );

	register_post_type( 'sfwd-essays', $args );
}

add_action( 'init', 'learndash_register_essay_post_type' );



/**
 * Adds the essay post type capabilities.
 *
 * Add essay capabilities to administrators and group leaders.
 * Fires on `admin_init` hook.
 *
 * @since 2.2.0
 */
function learndash_add_essay_caps() {
	$admin_role = get_role( 'administrator' );
	if ( ( $admin_role ) && ( $admin_role instanceof WP_Role ) ) {
	
		$cap  = $admin_role->has_cap( 'delete_others_essays' );
		if ( empty( $cap ) ) {
			$admin_role->add_cap( 'edit_essays' );
			$admin_role->add_cap( 'edit_others_essays' );
			$admin_role->add_cap( 'publish_essays' );
			$admin_role->add_cap( 'read_essays' );
			$admin_role->add_cap( 'read_private_essays' );
			$admin_role->add_cap( 'delete_essays' );
			$admin_role->add_cap( 'edit_published_essays' );
			$admin_role->add_cap( 'delete_others_essays' );
			$admin_role->add_cap( 'delete_published_essays' );
		}
	}
	
	$group_leader_role = get_role( 'group_leader' );
	if ( ( $group_leader_role ) && ( $group_leader_role instanceof WP_Role ) ) {
		$group_leader_role->add_cap( 'edit_essays' );
		$group_leader_role->add_cap( 'edit_others_essays' );
		$group_leader_role->add_cap( 'publish_essays' );
		$group_leader_role->add_cap( 'read_essays' );
		$group_leader_role->add_cap( 'read_private_essays' );
		$group_leader_role->add_cap( 'delete_essays' );
		$group_leader_role->add_cap( 'edit_published_essays' );
		$group_leader_role->add_cap( 'delete_others_essays' );
		$group_leader_role->add_cap( 'delete_published_essays' );
	}
}

add_action( 'admin_init', 'learndash_add_essay_caps' );



/**
 * Maps the meta capabilities for essay post type.
 *
 * Fires on `map_meta_cap` hook.
 *
 * @since 2.2.0
 *
 * @param array  $caps    An Array of the user's capabilities.
 * @param string $cap     Capability name.
 * @param int    $user_id The User ID.
 * @param array  $args    Optional. Adds the context to the cap. Typically the object ID. Default empty array.
 *
 * @return array An array of user's capabilities.
 */
function learndash_map_metacap_essays( $caps, $cap, $user_id, $args = array() ) {
	if ( !is_string( $cap ) ) return $caps;

	/* If editing, deleting, or reading a essays, get the post and post type object. */
	if ( 'edit_essay' == $cap || 'delete_essay' == $cap || 'read_essay' == $cap ) {
		
		// Ensure $args is valid
		if ( ( !is_array( $args ) ) || ( !isset( $args[0] ) ) ) {
			return $caps;
		}
		
		$post      = get_post( $args[0] );
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return $caps;
		}

		$post_type = get_post_type_object( $post->post_type );

		/* Set an empty array for the caps. */
		$caps = array();
	}

	/* If editing a essay, assign the required capability. */
	if ( 'edit_essay' == $cap ) {
		if ( $user_id == $post->post_author ) {
			$caps[] = $post_type->cap->edit_posts;
		} else {
			$caps[] = $post_type->cap->edit_others_posts;
		}
	} /* If deleting a essay, assign the required capability. */
	elseif ( 'delete_essay' == $cap ) {
		if ( $user_id == $post->post_author ) {
			$caps[] = $post_type->cap->delete_posts;
		} else {
			$caps[] = $post_type->cap->delete_others_posts;
		}
	} /* If reading a private essay, assign the required capability. */
	elseif ( 'read_essay' == $cap ) {

		if ( 'private' != $post->post_status ) {
			$caps[] = 'read';
		} elseif ( $user_id == $post->post_author ) {
			$caps[] = 'read';
		} else {
			$caps[] = $post_type->cap->read_private_posts;
		}
	}

	/* Return the capabilities required by the user. */

	return $caps;
}

add_filter( 'map_meta_cap', 'learndash_map_metacap_essays', 10, 4 );



/**
 * Registers the 'Graded' and 'Not Graded' post status.
 *
 * Fires on `init` hook.
 *
 * @since 2.2.0
 */
function learndash_register_essay_post_status() {
	register_post_status( 'graded', array(
		'label'                     => esc_html_x( 'Graded', 'Custom Essay post type status: Graded', 'learndash' ),
		'public'                    => true,
		'exclude_from_search'       => true,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		// translators: placeholder: Graded Essay count.
		'label_count'               => _n_noop( 'Graded <span class="count">(%s)</span>', 'Graded <span class="count">(%s)</span>', 'learndash' ),
	) );

	register_post_status( 'not_graded', array(
		'label'                     => esc_html_x( 'Not Graded', 'Custom Essay post type status: Not Graded', 'learndash' ),
		'public'                    => true,
		'exclude_from_search'       => true,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		// translators: placeholder: Not Graded Essay count.
		'label_count'               => _n_noop( 'Not Graded <span class="count">(%s)</span>', 'Not Graded <span class="count">(%s)</span>', 'learndash' ),
	) );
}

add_action( 'init', 'learndash_register_essay_post_status' );


/**
 * Manages the permissions for the essay post type.
 *
 * Only allow admins, group leaders, and essay owners to see the assignment.
 * Fires on `wp` hook.
 *
 * @global WP_Post $post Global post object.
 *
 * @since 2.1.0
 */
function learndash_essay_permissions() {
	if ( is_singular( learndash_get_post_type_slug( 'essay' ) ) ) {
		$can_view_file = false;

		$post = get_post();
		if ( ( $post ) && ( is_a( $post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'essay' ) === $post->post_type ) ) {
			$user_id = get_current_user_id();

			if ( ! empty( $user_id ) ) {
				if ( ( learndash_is_admin_user( $user_id ) ) || ( $post->post_author == $user_id ) ) {
					$can_view_file = true;
				} else if ( ( learndash_is_group_leader_user( $user_id ) ) && ( learndash_is_group_leader_of_user( $user_id, $post->post_author ) ) ) {
					$can_view_file = true;
				}
			}
		}

		if ( true === $can_view_file ) {
			$uploaded_file = get_post_meta( $post->ID, 'upload', true );
			if ( ( ! empty( $uploaded_file ) ) && ( ! strstr( $post->post_content, $uploaded_file ) ) ) {
				/**
				 * Filters quiz essay upload link HTML output.
				 *
				 * @param string $upload_link Essay upload link HTML output.
				 */
				$post->post_content .= apply_filters( 'learndash-quiz-essay-upload-link', '<p><a target="_blank" href="' . esc_url( $uploaded_file ) . '">' . esc_html__( 'View uploaded file', 'learndash' ) . '</a></p>' );
			}
			return;
		} else {
			if ( empty( $user_id ) ) {
				$current_url     = remove_query_arg( 'test' );
				$redirect_to_url = wp_login_url( esc_url( $current_url ), true );
			} else {
				$redirect_to_url = get_bloginfo( 'url' );
			}
			/**
			 * Filters the URL to redirect a user if it does not have permission to view the essay.
			 *
			 * @param string $redirect_url Redirect URL.
			 */
			wp_safe_redirect( apply_filters( 'learndash_essay_permissions_redirect_url', $redirect_to_url ) );
			exit;
		}
	}
}

add_action( 'wp', 'learndash_essay_permissions' ); //, 0, 3 );


/**
 * Adds custom columns to the essay post type listing in admin.
 *
 * Fires on `manage_edit-sfwd-essays_columns` hook.
 *
 * @since 2.1.0
 *
 * @param array $cols An array of admin columns for a post type.
 *
 * @return array $cols An array of admin columns for a post type.
 */
function add_essays_data_columns( $cols ) {

	$cols_new = array();
	
	foreach( $cols as $col_key => $col_label ) {
		if ($col_key == 'title') {
			$cols_new[$col_key] = esc_html__( 'Essay Question Title', 'learndash' );
		} else if ($col_key == 'author') {
			$cols_new[$col_key] 			= esc_html__( 'Submitted By', 'learndash' );
			
			// We add all out own columns after 'author' 
			$cols_new['approval_status'] 	= esc_html__('Status', 'learndash' );
			$cols_new['approval_points'] 	= esc_html__('Points', 'learndash' );
			// translators: placeholder: Course.
			$cols_new['course'] 			= 	sprintf( esc_html_x( 'Assigned %s', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) );
			// translators: placeholder: Lesson.
			$cols_new['lesson'] 			= 	sprintf( esc_html_x( 'Assigned %s', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) );
			// translators: placeholder: Quiz.
			$cols_new['quiz']				= 	sprintf( esc_html_x( 'Assigned %s', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) );

		} else {
			$cols_new[$col_key] = $col_label;
		}
	}
	return $cols_new;
}



/**
 * Populates the essay post type columns in the admin.
 *
 * Columns are can be filtered by quiz, lesson, and course.
 * Fires on `manage_sfwd-essays_posts_custom_column` hook.
 *
 * @since 2.2.0
 *
 * @param string $column  The slug of the column.
 * @param int    $post_id Post ID.
 */
function learndash_populate_essay_cpt_columns( $column, $post_id ) {
	
	$essay = get_post( $post_id );
	switch ( $column ) {
		
		case 'approval_status':
			$post_status_object = get_post_status_object($essay->post_status);
			if ( ( !empty( $post_status_object ) ) && ( is_object( $post_status_object ) ) && ( property_exists( $post_status_object, 'label' ) ) ) {
				echo $post_status_object->label;
			}
			$essay 			= get_post( $post_id );
			if ( $essay->post_status == 'not_graded' ) {
				?><button id="essay_approve_<?php echo $post_id ?>" class="small essay_approve_single"><?php esc_html_e( 'approve', 'learndash' ); ?></button><?php
			}
			break;
			
		case 'approval_points':
			$essay 			= get_post( $post_id );
			$quiz_id		= get_post_meta( $post_id, 'quiz_id', true );
			$question_id	= get_post_meta( $post_id, 'question_id', true );

			if ( ! empty( $quiz_id ) ) {
				$questionMapper = new WpProQuiz_Model_QuestionMapper();
				$question       = $questionMapper->fetchById( intval( $question_id ), null );
				if ( $question instanceof WpProQuiz_Model_Question ) {
								
					$submitted_essay_data = learndash_get_submitted_essay_data( $quiz_id, $question_id, $essay );
				
					$max_points = $question->getPoints();
				
					$current_points = 0;
					if ( isset( $submitted_essay_data['points_awarded'] ) )
						$current_points = intval( $submitted_essay_data['points_awarded'] );
				
					if ( $essay->post_status == 'not_graded' ) {
						$current_points = '<input id="essay_points_'. $post_id .'" class="small-text" type="number" value="'. $current_points .'" max="'. $max_points .'" min="0" step="1" name="essay_points['. $post_id .']" />';
						// translators: placeholders: input points / maximum point for essay.
						echo sprintf( _x( '%1$s / %2$d', 'placeholders: input points / maximum point for essay', 'learndash' ), $current_points, $max_points );
					} else {
						// translators: placeholders: current awarded points / maximum point for essay.
						echo sprintf( esc_html_x( '%1$d / %2$d', 'placeholders: current awarded points / maximum point for essay', 'learndash'), $current_points, $max_points );
					}
				} else {
					echo '-';
				}
			}
		
		
			break;
			
		case 'quiz':
			$quiz_id = get_post_meta( $post_id, 'quiz_id', true );
			if ( !empty( $quiz_id ) ) {
				$quiz_post_id = learndash_get_quiz_id_by_pro_quiz_id( $quiz_id );
				if ( empty( $quiz_post_id ) ) { 
					$quizMapper = new WpProQuiz_Model_QuizMapper();
					$quiz = $quizMapper->fetch( $quiz_id );
					if ( ( !empty( $quiz ) ) && ( $quiz instanceof WpProQuiz_Model_Quiz ) ) {
						$quiz_title = $quiz->getName();
						echo  $quiz_title;
					} else {
						echo '&#8212;';
					}
				} else {
					$quiz_title = get_the_title( $quiz_post_id );
					$edit_url = get_edit_post_link( intval( $quiz_post_id ) );
				
					$filter_url = add_query_arg( array( 'post_type' => 'sfwd-essays', 'quiz_id' => $quiz_id ), admin_url( 'edit.php' ) );
				
					$course_id = get_post_meta( $post_id, 'course_id', true );
					if ( ! empty( $course_id ) ) {
						$edit_url = add_query_arg('course_id', $course_id, $edit_url );
						$filter_url = add_query_arg('course_id', $course_id, $filter_url );
					}
					$lesson_id = get_post_meta( $post_id, 'lesson_id', true );
					if ( ! empty( $lesson_id ) ) {
						$edit_url = add_query_arg('lesson_id', $lesson_id, $edit_url );
						$filter_url = add_query_arg('lesson_id', $lesson_id, $filter_url );
					}
					//echo sprintf( '<a href="%s">%s</a>', $edit_url, $quiz_title );
					echo  '<a href="'. esc_url( $edit_url ) .'">'. $quiz_title .'</a>';
					$row_actions['edit'] = '<a href="'. esc_url( $edit_url ) .'">' . esc_html__('edit', 'learndash' ) .'</a>';
					$row_actions['filter_post'] = '<a href="'. esc_url( $filter_url ) .'">' . esc_html__('filter', 'learndash' ) .'</a>';
					echo learndash_list_table_row_actions( $row_actions );
				}
			}
			break;

		case 'lesson':
			$quiz_post_id = get_post_meta( $post_id, 'quiz_post_id', true );
			if ( !empty( $quiz_post_id ) )
				$lesson_id = get_post_meta( $quiz_post_id, 'lesson_id', true );
			else	
				$lesson_id = get_post_meta( $post_id, 'lesson_id', true );
			
			if ( !empty( $lesson_id ) ) {
				$lesson = get_post( $lesson_id );
				if ( ( !empty( $lesson ) ) && ( $lesson instanceof WP_Post ) ) {
					$edit_url = get_edit_post_link( $lesson_id );
					$filter_url = add_query_arg( array( 'post_type' => 'sfwd-essays', 'lesson_id' => $lesson_id ), admin_url( 'edit.php' ) );
					
					if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
						$course_id = get_post_meta( $post_id, 'course_id', true );
						if ( ! empty( $course_id ) ) {
							$edit_url = add_query_arg('course_id', $course_id, $edit_url );
							$filter_url = add_query_arg('course_id', $course_id, $filter_url );
						}
					}
					echo  '<a href="'. $edit_url .'">'. get_the_title( $lesson_id ) .'</a>';
					$row_actions['edit'] = '<a href="'. $edit_url .'">' . esc_html__('edit', 'learndash' ) .'</a>';
					$row_actions['filter_post'] = '<a href="'. $filter_url .'">' . esc_html__('filter', 'learndash' ) .'</a>';
					echo learndash_list_table_row_actions( $row_actions );
					
				}
			} else {
				echo '&#8212;';
			}
			break;

		case 'course':
			$quiz_post_id = get_post_meta( $post_id, 'quiz_post_id', true );
			if ( !empty( $quiz_post_id ) )
				$course_id = get_post_meta( $quiz_post_id, 'course_id', true );
			else
				$course_id = get_post_meta( $post_id, 'course_id', true );
			
			if ( !empty( $course_id ) ) {
				$edit_url = get_edit_post_link( $course_id );
				$filter_url = add_query_arg( array( 'post_type' => 'sfwd-essays', 'course_id' => $course_id ), admin_url( 'edit.php' ) );

				echo  '<a href="'. $edit_url .'">'. get_the_title( $course_id ) .'</a>';
				$row_actions['edit'] = '<a href="'. $edit_url .'">' . esc_html__('edit', 'learndash' ) .'</a>';
				$row_actions['filter_post'] = '<a href="'. $filter_url .'">' . esc_html__('filter', 'learndash' ) .'</a>';
				echo learndash_list_table_row_actions( $row_actions );
			} else {
				echo '&#8212;';
			}
			break;
	}
}

add_action( 'manage_sfwd-essays_posts_custom_column', 'learndash_populate_essay_cpt_columns', 10, 2 );

/**
 * Adds 'Approve' option next to certain selects on the Essay edit screen in the admin.
 *
 * Fires on `admin_footer` hook.
 *
 * @todo  check if needed, jQuery selector seems incorrect
 *
 * @since 2.3.0
 */
function learndash_essay_bulk_actions() {
	global $post;

	if ( ! empty( $post->post_type ) && $post->post_type == 'sfwd-essays' ) {
		$approve_text = esc_html__('Approve', 'learndash'); 
		
		?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery('<option>').val('approve_essay').text('<?php echo $approve_text ?>').appendTo("select[name='action']");
					jQuery('<option>').val('approve_essay').text('<?php echo $approve_text ?>').appendTo("select[name='action2']");
				});
			</script>
		<?php
	}
}
add_action( 'admin_footer', 'learndash_essay_bulk_actions' );


/**
 * Adds inline actions to assignments on post listing hover in the admin.
 *
 * Fires on `post_row_actions` hook.
 *
 * @since 2.1.0
 *
 * @param array   $actions An array of post actions.
 * @param WP_Post $post    The `WP_Post` object.
 *
 * @return array $actions An array of post actions.
 */
function learndash_essay_inline_actions( $actions, $post ) {
	if ( $post->post_type == 'sfwd-essays' ) {
		
		$upload = get_post_meta( $post->ID, 'upload', true );
		if ( ! empty( $upload ) ) {
			$actions['download_essay'] = '<a href="' . esc_url( $upload ) . '" target="_blank">' . esc_html__( 'Download', 'learndash' ) . '</a>';
		} 
		/*
		if ($post->post_status == 'not_graded') {
			$approve_link = '';
			$actions['approve_essay'] = "<a href='" . $approve_link . "' target='_blank'>" . esc_html__( 'Approve', 'learndash' ) . '</a>';
		}
		*/
	}

	return $actions;
}
add_filter( 'post_row_actions', 'learndash_essay_inline_actions', 10, 2 );


/**
 * Adjust essay post type query in admin
 *
 * Essay query should only include essays with a 'graded' and 'not_graded' post status.
 * Fires on `pre_get_posts` hook.
 *
 * @since 2.2.0
 *
 * @param WP_Query $essay_query The `WP_Query` instance (passed by reference).
 */
function learndash_modify_admin_essay_listing_query( $essay_query ) {
	if ( is_admin() && $essay_query->is_main_query() && 'sfwd-essays' == $essay_query->query['post_type'] && ( ( ! isset( $_GET['post_status'] ) ) || ( isset( $_GET['post_status'] ) && 'all' == $_GET['post_status'] ) ) ) {
		$essay_query->set( 'post_status', array( 'graded', 'not_graded' ) );
	}
}

add_action( 'pre_get_posts', 'learndash_modify_admin_essay_listing_query' );



/**
 * Adds a new essay response.
 *
 * Called from `LD_QuizPro::checkAnswers()` via AJAX.
 *
 * @since 2.2.0
 *
 * @param string                   $response      Essay response.
 * @param WpProQuiz_Model_Question $this_question Pro quiz question object.
 * @param WpProQuiz_Model_Quiz     $quiz          Pro Quiz object.
 * @param array|null               $post_data     Optional. Quiz information and answers. Default null.
 *
 * @return boolean|int|WP_Error Returns essay ID or `WP_Error` if the essay could not be created.
 */
function learndash_add_new_essay_response( $response, $this_question, $quiz, $post_data = null ) {
	if ( ! is_a( $this_question, 'WpProQuiz_Model_Question' ) || ! is_a ( $quiz, 'WpProQuiz_Model_Quiz' ) ) {
		return false;
	}

	$user = wp_get_current_user();

	// essay args defaults
	$essay_args = array(
		'post_title'   => $this_question->getTitle(),
		'post_status'  => 'not_graded',
		'post_type'    => 'sfwd-essays',
		'post_author'  => $user->ID,
	);

	$essay_data = $this_question->getAnswerData();
	$essay_data = array_shift( $essay_data );

	// switch on grading progression in order to set post status
	switch ( $essay_data->getGradingProgression() ) {
		case '':
		case 'not-graded-none':
			$essay_args['post_status'] = 'not_graded';
			break;
		case 'not-graded-full':
			$essay_args['post_status'] = 'not_graded';
			break;
		case 'graded-full' :
			$essay_args['post_status'] = 'graded';
			break;
	}

	// switch on graded type to handle the response
	// used a switch in case we add more types
	switch( $essay_data->getGradedType() ) {
		case 'text' :
			$essay_args['post_content'] = wp_kses( 
				$response,
				/**
				 * Filters list of allowed html tags in essay content.
				 *
				 * Used in allowed_html paramter of wp_kses function.
				 *
				 * @param array $allowed_tags An array of allowed HTML tags in essay content.
				 */
				apply_filters('learndash_essay_new_allowed_html', wp_kses_allowed_html( 'post' ) ) 
			);
			break;
		case 'upload' :
			$essay_args['post_content'] = esc_html__( 'See upload below.', 'learndash' );
	}

	/**
	 * Filters new essay submission `wp_insert_post` arguments.
	 *
	 * @param array $essay_args An array of essay arguments.
	 */
	$essay_args = apply_filters( 'learndash_new_essay_submission_args', $essay_args );
	$essay_id = wp_insert_post( $essay_args );

	if ( ! empty( $essay_id ) ) {
		if ( ( isset( $post_data['quiz_id'] ) ) && ( ! empty( $post_data['quiz_id'] ) ) ) {
			$quiz_id = absint( $post_data['quiz_id'] );
		} else {
			$quiz_id = learndash_get_quiz_id_by_pro_quiz_id(  $this_question->getQuizId() );
		}
		
		if ( isset( $post_data['course_id'] ) ) {
			$course_id = intval( $post_data['course_id'] );
			if ( !empty( $course_id ) ) {
				$lesson_id = learndash_course_get_single_parent_step( $course_id, $quiz_id );
			} else {
				$lesson_id = 0;
			}
		} else {
			$course_id = learndash_get_course_id( $quiz_id );
			$lesson_id = learndash_get_lesson_id( $quiz_id );
		}

		update_post_meta( $essay_id, 'question_id', $this_question->getId() );
		update_post_meta( $essay_id, 'quiz_pro_id', $this_question->getQuizId() );
		update_post_meta( $essay_id, 'quiz_id', $this_question->getQuizId() );
		update_post_meta( $essay_id, 'course_id', $course_id );
		update_post_meta( $essay_id, 'lesson_id', $lesson_id );

		if ( 'upload' == $essay_data->getGradedType() ){
			update_post_meta( $essay_id, 'upload', esc_url( $response ) );
		}
	}

	/**
	 * Fires after a new essay is submitted.
	 *
	 * @param int   $essay_id  The new Essay ID created after essay submission.
	 * @param array $essay_arg An array of essay arguments.
	 */
	do_action( 'learndash_new_essay_submitted', $essay_id, $essay_args );

	return $essay_id;
}



/**
 * Removes the default submitdiv meta box from the essay post type in the admin edit screen.
 *
 * Fires on `admin_menu` hook.
 *
 * @since 2.2.0
 */
function learndash_essays_remove_subbmitdiv_metabox() {
	remove_meta_box( 'submitdiv', 'sfwd-essays', 'side' );
}

add_action( 'admin_menu', 'learndash_essays_remove_subbmitdiv_metabox' );



/**
 * Registers the essay upload metabox.
 *
 * Fires on `add_meta_boxes_sfwd-essays` hook.
 *
 * @since 2.2.0
 */
function learndash_register_essay_upload_metabox() {
	add_meta_box(
		'learndash_essay_upload_div',
		esc_html__( 'Essay Upload', 'learndash' ),
		'learndash_essay_upload_meta_box',
		'sfwd-essays',
		'normal',
		'high'
	);
	
	// This is added here because we wanted the inline comments ability on the single edit post type form. But since 
	// This post type uses custom post statuses the default logic in WP was failing. 
	add_meta_box( 'commentsdiv', esc_html__( 'Comments', 'learndash' ), 'post_comment_meta_box', null, 'normal', 'core' );
}

add_action( 'add_meta_boxes_sfwd-essays', 'learndash_register_essay_upload_metabox' );



/**
 * Prints the essay upload metabox content.
 *
 * @since 2.2.0
 *
 * @param WP_Post $essay The `WP_Post` essay object.
 */
function learndash_essay_upload_meta_box( $essay ) {
	$upload = get_post_meta( $essay->ID, 'upload', true );
	if ( ! empty( $upload ) ) {
		echo sprintf( '<a target="_blank" href="%1$s">%s</a>', esc_url( $upload ) );
	} else {
		esc_html_e( 'Upload was not provided for this question', 'learndash' );
	}
}



/**
 * Registers the essay grading response metabox.
 *
 * Used when a grader wants to respond to a essay submitted by the user.
 *
 * @since 2.2.0
 */
function learndash_register_essay_grading_response_metabox() {
	add_meta_box(
		'learndash_essay_grading_response_div',
		esc_html__( 'Your Response to Submitted Essay (optional)', 'learndash' ),
		'learndash_essay_grading_response_meta_box',
		'sfwd-essays',
		'normal',
		'high'
	);
}

//add_action( 'add_meta_boxes_sfwd-essays', 'learndash_register_essay_grading_response_metabox' );



/**
 * Prints the essay grading response metabox content.
 *
 * @since 2.2.0
 *
 * @param WP_Post $essay The `WP_Post` essay object.
 */
function learndash_essay_grading_response_meta_box( $essay ) {
	$grading_response = get_post_meta( $essay->ID, 'ld_essay_grading_response', true );
	$grading_response = ( ! empty( $grading_response ) ) ? wp_kses( 
		$grading_response,
		/**
		 * Filters list of allowed HTML tags in essay grading response meta box content.
		 *
		 * Used in allowed_html paramter of wp_kses function.
		 *
		 * @param array $allowed_tags An array of allowed HTML tags in essay grading response.
		 */
		apply_filters('learndash_essay_grading_response_meta_box_allowed_html', wp_kses_allowed_html( 'post' ) ) ) : '';
	/**
	 * Filters essay grading response message.
	 *
	 * @param string $grading_response Grading response message.
	 */
	$grading_response = apply_filters( 'learndash_grading_response', $grading_response );
	?>
		<textarea name="grading-response" id="grading-response" rows="10"><?php echo $grading_response; ?></textarea>
	<?php
}



/**
 * Saves the essay grading response to the post meta.
 *
 * Fires on `save_post_sfwd-essays` metabox.
 *
 * @since 2.2.0
 *
 * @param int     $essay_id ID of the essay to be saved.
 * @param WP_Post $essay    The `WP_Post` essay object.
 * @param boolean $update   Whether this is an existing post being updated or not.
 */
function learndash_save_essay_grading_response( $essay_id, $essay, $update ) {
	if ( ! isset( $_POST['grading-response'] ) ) {
		return;
	}

	$grading_response = wp_kses( 
		$_POST['grading-response'],
		/**
		 * Filters list of allowed html tags essay grading response while saving it to post meta.
		 *
		 * Used in allowed_html paramter of wp_kses function.
		 *
		 * @param array $allowed_tags An array of allowed HTML tags in essay grading response.
		 */
		apply_filters('learndash_essay_save_grading_response_allowed_html', wp_kses_allowed_html( 'post' ) ) 
	);

	/** This filter is documented in includes/quiz/ld-quiz-essays.php */
	$grading_response = apply_filters( 'learndash_grading_response', $grading_response );
	update_post_meta( $essay_id, 'ld_essay_grading_response', $grading_response );

	/**
	 * Fires after the grading response is updated.
	 *
	 * @param string $grading_response Grading response message.
	 */
	do_action( 'learndash_essay_grading_response_updated', $grading_response );
}

add_action( 'save_post_sfwd-essays', 'learndash_save_essay_grading_response', 10, 3 );



/**
 * Registers the essay grading metabox.
 *
 * Replaces the submitdiv meta box that comes with every post type.
 * Fires on `add_meta_boxes_sfwd-essays` hook.
 *
 * @since 2.2.0
 */
function learndash_register_essay_grading_metabox() {
	add_meta_box(
		'learndash_essay_status_div',
		esc_html__( 'Essay Grading Status', 'learndash' ),
		'learndash_essay_grading_meta_box',
		'sfwd-essays',
		'side',
		'core'
	);
}

add_action( 'add_meta_boxes_sfwd-essays', 'learndash_register_essay_grading_metabox' );



/**
 * Prints the essay grading metabox content.
 *
 * Copied/modified version of submitdiv from core.
 *
 * @since 2.2.0
 *
 * @param WP_Post $essay The `WP_Post` essay object.
 */
function learndash_essay_grading_meta_box( $essay ) {
	$post_type            = $essay->post_type;
	$post_type_object     = get_post_type_object( $post_type );
	$can_publish          = current_user_can( $post_type_object->cap->publish_posts );
	$quiz_id              = get_post_meta( $essay->ID, 'quiz_id', true );
	$question_id          = get_post_meta( $essay->ID, 'question_id', true );

	if ( ! empty( $quiz_id ) ) {
		$questionMapper = new WpProQuiz_Model_QuestionMapper();
		$question       = $questionMapper->fetchById( intval( $question_id ), null );

	}

	if ( $question && is_a( $question, 'WpProQuiz_Model_Question' ) )  {
		$submitted_essay_data = learndash_get_submitted_essay_data( $quiz_id, $question->getId(), $essay );
	}

	?>
	<div class="submitbox" id="submitpost">
		<div id="minor-publishing">
			<div id="misc-publishing-actions">
				<div class="misc-pub-section misc-pub-post-status">
					<?php if ( 'not_graded' == $essay->post_status || 'graded' == $essay->post_status || $can_publish ) : ?>

						<div id="post-status-select">
							<select name='post_status' id='post_status'>
								<option <?php selected( $essay->post_status, 'not_graded' ); ?>
									value='not_graded'><?php esc_html_e( 'Not Graded', 'learndash' ) ?></option>
								<option <?php selected( $essay->post_status, 'graded' ); ?>
									value='graded'><?php esc_html_e( 'Graded', 'learndash' ) ?></option>
							</select>
						</div>

					<?php endif; ?>
				</div>

				<div class="misc-pub-section">
					<?php if ( $question && is_a( $question, 'WpProQuiz_Model_Question' ) ) : ?>
						<p>
							<strong><?php esc_html_e( 'Essay Question', 'learndash' ); ?>:</strong> <?php echo $question->getQuestion(); ?>
							<?php
								$test_url = admin_url( 'admin.php' );
								$question_edit_url = '';
								if ( ( true === is_data_upgrade_quiz_questions_updated() ) && ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'enabled' ) === 'yes' ) ) {
									$question_post_id = learndash_get_question_post_by_pro_id( $question->getId() );
									if ( ! empty( $question_post_id ) ) {
										$question_edit_url = get_edit_post_link( $question_post_id );
									}
								} 
								
								if ( empty( $question_edit_url ) ) {
									$question_edit_url = add_query_arg(
										array(
											'page' => 'ldAdvQuiz',
											'module' => 'question',
											'action' => 'addEdit',
											'quiz_id'  => $quiz_id,
											'questionId' => $question->getId(),
										), 
										admin_url( 'admin.php' )
									);
								}
							?>
							<span>(<a href="<?php echo esc_url( $question_edit_url ); ?>"><?php esc_html_e( 'Edit', 'learndash' ); ?></a>)</span>
						</p>
						<p><strong><?php esc_html_e( 'Points available', 'learndash' ); ?>:</strong> <?php echo $question->getPoints(); ?></p>
						<p>
							<strong><?php esc_html_e( 'Points awarded', 'learndash' ); ?>:</strong>
							<input name="points_awarded" type="number" min="0" max="<?php echo $question->getPoints(); ?>" value="<?php echo $submitted_essay_data['points_awarded']; ?>">
							<input name="original_points_awarded" type="hidden" value="<?php echo $submitted_essay_data['points_awarded']; ?>">
						</p>
						<input name="quiz_id" type="hidden" value="<?php echo $quiz_id; ?>">
						<input name="question_id" type="hidden" value="<?php echo $question->getId(); ?>">
					<?php else : ?>
						<p><?php esc_html_e( 'We could not find the essay question for this response', 'learndash' ); ?></p>
					<?php endif; ?>
				</div>

				<div class="misc-pub-section">
					<?php					
						$essay_quiz_post_id = get_post_meta( $essay->ID, 'quiz_post_id', true );
						if ( empty( $essay_quiz_post_id ) ) {
							
							$essay_quiz_query_args = array(
								'post_type'		=>	'sfwd-quiz',
								'post_status'	=>	'publish',
								'meta_key'		=>	'quiz_pro_id_' . intval( $quiz_id ),
								'meta_value'	=>	intval( $quiz_id ),
								'meta_compare'	=>	'=',
								'fields'		=>	'ids',
								'orderby'		=>	'title',
								'order'			=>	'ASC'
							);
					
							$essay_quiz_query = new WP_Query( $essay_quiz_query_args );
							if ( count( $essay_quiz_query->posts ) > 1 ) {
								?>
								<p>
								<strong><?php 
								// translators: placeholder: Quiz.
								echo sprintf( esc_html_x( 'Essay %s', 'placeholder: Quiz.', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ); ?>:</strong>
								<select name="essay_quiz_post_id">
									<option value=""><?php 
									// translators: placeholder: Quiz.
									echo sprintf( esc_html_x('No %s', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ); ?></option>
									<?php
									foreach( $essay_quiz_query->posts as $quiz_post_id ) {
										?><option value="<?php echo $quiz_post_id ?>"><?php echo get_the_title( $quiz_post_id ); ?></option><?php
									}
									?>
								</select>
								</p>
								<?php
								
							} else {
								$essay_quiz_post_id = $essay_quiz_query->posts[0];
							}
						} 
						
						if ( !empty( $essay_quiz_post_id ) ) {
							$essay_quiz_edit_link = get_edit_post_link( $essay_quiz_post_id );
							?><p>
							<strong><?php 
							// translators: placeholder: Quiz.
							echo sprintf( esc_html_x( 'Essay %s', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ); ?>:</strong> <?php echo get_the_title( $essay_quiz_post_id ); ?> <?php
								if (!empty( $essay_quiz_edit_link ) ) {
									?><span>(<a href="<?php echo esc_url( $essay_quiz_edit_link ); ?>"><?php esc_html_e( 'edit', 'learndash' ); ?></a>)</span><?php
								}
								?>
							</p>
							<?php

							$essay_quiz_course_id = get_post_meta( $essay_quiz_post_id, 'course_id', true );
							if ( !empty( $essay_quiz_course_id ) ) {
								$course_quiz_edit_link = get_edit_post_link( $essay_quiz_course_id );
								?>
								<p>
								<strong><?php 
								// translators: placeholder: Course.
								echo sprintf( esc_html_x( 'Essay %s', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ); ?>:</strong> <?php echo get_the_title( $essay_quiz_course_id ); ?> <?php
									if (!empty( $course_quiz_edit_link ) ) {
										?><span>(<a href="<?php echo esc_url( $course_quiz_edit_link ); ?>"><?php esc_html_e( 'edit', 'learndash' ); ?></a>)</span><?php
									}
									?>
								</p>
								<?php
								
								$essay_quiz_lesson_id = get_post_meta( $essay_quiz_post_id, 'lesson_id', true );
								if ( !empty( $essay_quiz_lesson_id ) ) {
									$lesson_quiz_edit_link = get_edit_post_link( $essay_quiz_lesson_id );
									?>
									<p>
									<strong><?php 
									// translators: placeholder: Lesson.
									echo sprintf( esc_html_x( 'Essay %s', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ); ?>:</strong> <?php echo get_the_title( $essay_quiz_lesson_id ); ?> <?php
										if (!empty( $lesson_quiz_edit_link ) ) {
											?><span>(<a href="<?php echo esc_url( $lesson_quiz_edit_link ); ?>"><?php esc_html_e( 'edit', 'learndash' ); ?></a>)</span><?php
										}
										?>
									</p>
									<?php
								}
							}
						}
					?>
				</div>


				<?php
				/* translators: Publish box date format, see http://php.net/date */
				$datef = esc_html__( 'M j, Y @ H:i', 'default' );
				if ( 0 != $essay->ID ) :
					$stamp = wp_kses_post( __( 'Submitted on: <b>%1$s</b>', 'learndash' ) );
					$date  = date_i18n( $datef, strtotime( $essay->post_date ) );
				endif;

				if ( $can_publish ) : // Contributors don't get to choose the date of publish ?>
					<div class="misc-pub-section curtime misc-pub-curtime">
					<span id="timestamp"><?php printf( $stamp, $date ); ?></span>
					</div>
				<?php endif; ?>

				<?php
				/** This action is documented in https://developer.wordpress.org/reference/hooks/post_submitbox_misc_actions/ */
				do_action( 'post_submitbox_misc_actions' );
				?>
			</div>
			<div class="clear"></div>
		</div>

		<div id="major-publishing-actions">
			<?php
			/** This action is documented in https://developer.wordpress.org/reference/hooks/post_submitbox_start/ */
			do_action( 'post_submitbox_start' );
			?>
			<div id="delete-action">
				<?php
				if ( current_user_can( "delete_post", $essay->ID ) ) :
					if ( ! EMPTY_TRASH_DAYS ) :
						$delete_text = esc_html__( 'Delete Permanently', 'learndash' );
					else :
						$delete_text = esc_html__( 'Move to Trash', 'learndash' );
					endif;
					?>
					<a class="submitdelete deletion" href="<?php echo get_delete_post_link( $essay->ID ); ?>"><?php echo $delete_text; ?></a><?php
				endif;
				?>
			</div>

			<div id="publishing-action">
				<span class="spinner"></span>
				<?php if ( $can_publish ) : ?>
					<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Update', 'learndash' ) ?>"/>
					<?php submit_button( esc_html__( 'Update', 'learndash' ), 'primary button-large', 'submit', false ); ?>
				<?php endif; ?>
			</div>
			<div class="clear"></div>
		</div>
	</div>

	<?php
}



/**
 * Gets the essay data for this particular submission
 *
 * Loop through all the quizzes and return the quiz that matches as soon as it's found
 *
 * @since 2.2.0
 *
 * @param int     $quiz_id     Quiz ID.
 * @param int     $question_id Question ID.
 * @param WP_Post $essay       The `WP_Post` essay object.
 *
 * @return mixed The submitted essay data.
 */
function learndash_get_submitted_essay_data( $quiz_id, $question_id, $essay  ) {
	$users_quiz_data = get_user_meta( $essay->post_author, '_sfwd-quizzes', true );
	if ( ( !empty( $users_quiz_data ) ) && ( is_array( $users_quiz_data ) ) ) {
		if ( ( $essay ) && ( is_a( $essay, 'WP_Post' ) ) ) {
			$essay_quiz_time = get_post_meta( $essay->ID, 'quiz_time', true );
		} else {
			$essay_quiz_time = null;
		}

		foreach ( $users_quiz_data as $quiz_data ) {
			// We check for a match on the quiz time from the essay postmeta first. 
			// If the essay_quiz_time is not empty and does NOT match then continue;
			if ( ( absint( $essay_quiz_time ) ) && ( isset( $quiz_data['time'] ) ) && ( absint( $essay_quiz_time ) !== absint( $quiz_data['time'] ) ) ) {
				continue;
			}
			if (empty($quiz_data['pro_quizid']) ||  $quiz_id != $quiz_data['pro_quizid'] || ! isset( $quiz_data['has_graded'] ) || false == $quiz_data['has_graded'] ) {
				continue;
			}

			if ((isset($quiz_data['graded'])) && (!empty($quiz_data['graded']))) {
				foreach ( $quiz_data['graded'] as $key => $graded_question ) {
					if ( ( $key == $question_id ) && ( $essay->ID == $graded_question['post_id'] ) ) {
						return $quiz_data['graded'][ $key ];
					}
				}
			}
		}
	}
}



/**
 * Updates the user's essay and quiz data on post save.
 *
 * Fires on `save_post_sfwd-essays` hook.
 *
 * @since 2.2.0
 *
 * @param int     $essay_id ID of the essay to be saved.
 * @param WP_Post $essay    The `WP_Post` essay object.
 * @param boolean $update   Whether this is an existing post being updated or not.
 */
function learndash_save_essay_status_metabox_data( $essay_id, $essay, $update ) {
	
	if ( ! isset( $_POST['question_id'] ) || empty( $_POST['question_id'] ) ) {
		return;
	}

	$quiz_id = intval( $_POST['quiz_id'] );
	$question_id = intval( $_POST['question_id'] );

	$submitted_essay = learndash_get_submitted_essay_data( $quiz_id, $question_id, $essay );
	
	if ( ( isset( $_POST['essay_quiz_post_id'] ) ) && ( !empty( $_POST['essay_quiz_post_id'] ) ) ) {
		$essay_quiz_post_id = intval( $_POST['essay_quiz_post_id'] );

		update_post_meta( $essay_id, 'quiz_post_id', $essay_quiz_post_id );
	}
	
	
	$quiz_score_difference = 0;
	if ( isset( $_POST['post_status'] ) ) {
		if ( ( $_POST['post_status'] != $submitted_essay['status'] ) ) {
			if ( $_POST['post_status'] == 'graded' )
				$quiz_score_difference = 1;
			else if ( $_POST['post_status'] == 'not_graded' )
				$quiz_score_difference = -1;
		}
	}
		



	$submitted_essay['status'] = esc_html( $_POST['post_status'] );
	$submitted_essay['points_awarded'] = intval( $_POST['points_awarded'] );

	/**
	 * Filters essay status data.
	 *
	 * @param array $essay_data An array of essay status data.
	 */
	$submitted_essay = apply_filters( 'learndash_essay_status_data', $submitted_essay );
	learndash_update_submitted_essay_data( $quiz_id, $question_id, $essay, $submitted_essay );

	$original_points_awarded = isset( $_POST['original_points_awarded'] ) ? intval( $_POST['original_points_awarded'] ) : null;
	$points_awarded = isset( $_POST['points_awarded'] ) ?  intval( $_POST['points_awarded'] ) : null;

	if ( ! is_null( $original_points_awarded ) && ! is_null( $points_awarded ) ) {
		if ( $points_awarded > $original_points_awarded ) {
			$points_awarded_difference = intval( $points_awarded ) - intval( $original_points_awarded );
		} else {
			$points_awarded_difference = ( intval( $original_points_awarded ) - intval( $points_awarded ) ) * -1;
		}

		$updated_scoring = array(
			'updated_question_score' => $points_awarded,
			'points_awarded_difference' => $points_awarded_difference,
			'score_difference' => $quiz_score_difference
		);

		/**
		 * Filters updated essay scoring data before saving it to post meta.
		 *
		 * @param array $updated_scoring An array of updated essay score data.
		 */
		$updated_scoring = apply_filters( 'learndash_updated_essay_scoring', $updated_scoring );
		learndash_update_quiz_data( $quiz_id, $question_id, $updated_scoring, $essay );

		/**
		 * Fires after all the quiz data is updated.
		 *
		 * @param int     $quiz_id         Quiz ID.
		 * @param int     $question_id     Question_id.
		 * @param array   $updated_scoring An array of updated essay score data.
		 * @param WP_Post $essay           WP_Post object for essay.
		 */
		do_action( 'learndash_essay_all_quiz_data_updated', $quiz_id, $question_id, $updated_scoring, $essay );
	}
}

add_action( 'save_post_sfwd-essays', 'learndash_save_essay_status_metabox_data', 10, 3 );



/**
 * Updates the user's submitted essay data.
 *
 * Finds the essay in this particular quiz attempt in the user's meta and updates its data.
 *
 * @since 2.2.0
 *
 * @param int     $quiz_id         Quiz ID.
 * @param int     $question_id     Question ID.
 * @param WP_Post $essay           The `WP_Post` essay object.
 * @param array   $submitted_essay Submitted essay data.
 */
function learndash_update_submitted_essay_data( $quiz_id, $question_id, $essay, $submitted_essay ) {
	$users_quiz_data = get_user_meta( $essay->post_author, '_sfwd-quizzes', true );

	if ( ( $essay ) && ( is_a( $essay, 'WP_Post' ) ) ) {
		$essay_quiz_time = get_post_meta( $essay->ID, 'quiz_time', true );
	} else {
		$essay_quiz_time = null;
	}

	$quizdata_changed = array();

	foreach ( $users_quiz_data as $quiz_key => $quiz_data ) {
		// We check for a match on the quiz time from the essay postmeta first. 
		// If the essay_quiz_time is not empty and does NOT match then continue;
		if ( ( absint( $essay_quiz_time ) ) && ( isset( $quiz_data['time'] ) ) && ( absint( $essay_quiz_time ) !== absint( $quiz_data['time'] ) ) ) {
			continue;
		}
		
		if ( $quiz_id != $quiz_data['pro_quizid'] || ! isset( $quiz_data['has_graded'] ) || false == $quiz_data['has_graded'] ) {
			continue;
		}

		foreach ( $quiz_data['graded'] as $question_key => $graded_question ) {
			if ( ( $question_key == $question_id ) && ( $essay->ID == $graded_question['post_id'] ) ) {
				$users_quiz_data[ $quiz_key ]['graded'][ $question_key ] = $submitted_essay;
				if ( ( isset( $submitted_essay['status'] ) ) && ( 'graded' === $submitted_essay['status'] ) ) {
					$quizdata_changed[] = $users_quiz_data[ $quiz_key ];
				}
			}
		}
	}

	update_user_meta( $essay->post_author, '_sfwd-quizzes', $users_quiz_data );

	/**
	 * Fires after the essay response data is updated.
	 *
	 * @param int     $quiz_id         Quiz ID.
	 * @param int     $question_id     Question ID.
	 * @param WP_Post $essay           WP_Post object for essay.
	 * @param array   $submitted_essay An array of submitted essay data.
	 */
	do_action( 'learndash_essay_response_data_updated', $quiz_id, $question_id, $essay, $submitted_essay );
}

/**
 * Updates the user's quiz data.
 *
 * Finds this particular quiz attempt in the user's meta and updates its data.
 *
 * @since 2.2.0
 *
 * @param int     $quiz_id         Quiz ID.
 * @param int     $question_id     Question ID.
 * @param array   $updated_scoring An array of updated quiz scoring data.
 * @param WP_Post $essay           The `WP_Post` essay object.
 */
function learndash_update_quiz_data( $quiz_id, $question_id, $updated_scoring, $essay ) {
	$affected_quiz_keys = array();
	
	$users_quiz_data = get_user_meta( $essay->post_author, '_sfwd-quizzes', true );

	if ( ( $essay ) && ( is_a( $essay, 'WP_Post' ) ) ) {
		$essay_quiz_time = get_post_meta( $essay->ID, 'quiz_time', true );
	} else {
		$essay_quiz_time = null;
	}
	
	// We need to find the user meta quiz to matches the essay being scored. 
	foreach ( $users_quiz_data as $quiz_key => $quiz_data ) {

		// We check for a match on the quiz time from the essay postmeta first. 
		// If the essay_quiz_time is not empty and does NOT match then continue;
		if ( ( absint( $essay_quiz_time ) ) && ( isset( $quiz_data['time'] ) ) && ( absint( $essay_quiz_time ) !== absint( $quiz_data['time'] ) ) ) {
			continue;
		}

		if ( ( $quiz_id != $quiz_data['pro_quizid'] ) || ( !isset( $quiz_data['has_graded'] ) ) || ( false == $quiz_data['has_graded'] ) )
			continue;

		if ( ( !isset( $quiz_data['graded'][$question_id]['post_id'] ) ) || ( $quiz_data['graded'][$question_id]['post_id'] != $essay->ID ) )
			continue;

		$affected_quiz_keys[] = $quiz_key;
		
		// update total score
		$users_quiz_data[ $quiz_key ]['score'] = $users_quiz_data[ $quiz_key ]['score'] + $updated_scoring['score_difference'];

		// update total points
		$users_quiz_data[ $quiz_key ]['points'] = $users_quiz_data[ $quiz_key ]['points'] + $updated_scoring['points_awarded_difference'];

		// update total score percentage
		$updated_percentage = ( $users_quiz_data[ $quiz_key ]['points'] / $users_quiz_data[ $quiz_key ]['total_points'] ) * 100;
		$users_quiz_data[ $quiz_key ]['percentage'] = round( $updated_percentage, 2 );

		// update passing score
		$quizmeta = get_post_meta( $quiz_data['quiz'], '_sfwd-quiz', true );
		$passingpercentage = intVal( $quizmeta['sfwd-quiz_passingpercentage'] );
		$users_quiz_data[ $quiz_key ]['pass'] = ( $users_quiz_data[ $quiz_key ]['percentage'] >= $passingpercentage ) ? 1 : 0;

		learndash_update_quiz_statistics( $quiz_id, $question_id, $updated_scoring, $essay, $users_quiz_data[ $quiz_key ] );
		learndash_update_quiz_activity( $essay->post_author, $users_quiz_data[ $quiz_key ] );
	}

	update_user_meta( $essay->post_author, '_sfwd-quizzes', $users_quiz_data );

	if ( !empty( $affected_quiz_keys ) ) {
		foreach( $affected_quiz_keys as $quiz_key ) {
			if ( isset( $users_quiz_data[ $quiz_key ] ) ) {
				$send_quiz_completed = true;

				if ( ( isset( $users_quiz_data[ $quiz_key ]['has_graded'] ) ) && ( true === $users_quiz_data[ $quiz_key ]['has_graded'] ) ) {
					if ( ( isset( $users_quiz_data[ $quiz_key ]['graded'] ) ) && ( ! empty( $users_quiz_data[ $quiz_key ]['graded'] ) ) ) {
						foreach ( $users_quiz_data[ $quiz_key ]['graded'] as $grade_item ) {
							if ( ( isset( $grade_item['status'] ) ) && ( $grade_item['status'] !== 'graded' ) ) {
								$send_quiz_completed = false;
							}
						}
					}
				} 
				if ( true === $send_quiz_completed ) {
					if ( isset( $users_quiz_data[ $quiz_key ]['course'] ) )
						$course_id = intval( $users_quiz_data[ $quiz_key ]['course'] );
					else
						$course_id = learndash_get_course_id( $essay->ID );

					learndash_process_mark_complete( $essay->post_author, $users_quiz_data[ $quiz_key ]['quiz'], false, $course_id );

					/** This action is documented in includes/ld-users.php */
					do_action( 'learndash_quiz_completed', $users_quiz_data[ $quiz_key ], get_user_by( 'ID', $essay->post_author ) );

					/*
					if ( ( isset( $users_quiz_data[ $quiz_key ]['topic'] ) ) && ( ! empty( $users_quiz_data[ $quiz_key ]['topic'] ) ) ) {
						learndash_process_mark_complete( $essay->post_author, absint( $users_quiz_data[ $quiz_key ]['topic'] ), false, $course_id );
					}
					*/
					/*
					if ( ( isset( $users_quiz_data[ $quiz_key ]['lesson'] ) ) && ( ! empty( $users_quiz_data[ $quiz_key ]['lesson'] ) ) ) {
						learndash_process_mark_complete( $essay->post_author, absint( $users_quiz_data[ $quiz_key ]['lesson'] ), false, $course_id );
					}
					*/
				}
			}
		}
	}

	/**
	 * Fires after the essay quiz data is updated.
	 *
	 * @param int     $quiz_id         Quiz ID.
	 * @param int     $question_id     Question ID.
	 * @param array   $updated_scoring An array of updated essay scoring data.
	 * @param WP_Post $essay           WP_Post object for essay.
	 */
	do_action( 'learndash_essay_quiz_data_updated', $quiz_id, $question_id, $updated_scoring, $essay );
}

/**
 * Updates the quiz activity for a user.
 *
 * @param int   $user_id   User ID.
 * @param array $quiz_data An array of quiz activity data to be updated.
 */
function learndash_update_quiz_activity( $user_id = 0, $quiz_data = array() ) {
	if ( ( !empty( $user_id ) ) && ( !empty( $quiz_data ) ) ) {

		$quiz_data_meta = $quiz_data;
		
		// Remove many fields that we either don't need or are duplicate of the main table columns
		unset($quiz_data_meta['quiz']);
		unset($quiz_data_meta['pro_quizid']);
		unset($quiz_data_meta['time']);
		unset($quiz_data_meta['completed']);
		unset($quiz_data_meta['started']);
		//unset($quiz_data_meta['graded']);
		
		if ($quiz_data_meta['rank'] == '-')
			unset($quiz_data_meta['rank']);

		if ( $quiz_data['pass'] == true )
			$quiz_data_pass = true;
		else	
			$quiz_data_pass = false;
		
		learndash_update_user_activity(
			array(
				'course_id'				=>	(isset( $quiz_data['course'] ) ) ? intval( $quiz_data['course'] ) : 0,
				'post_id'				=>	$quiz_data['quiz'],
				'user_id'				=>	$user_id,
				'activity_type'			=>	'quiz',
				'activity_status'		=>	$quiz_data_pass,
				'activity_started'		=>	$quiz_data['started'],
				'activity_completed'	=>	$quiz_data['completed'], 
				'activity_meta'			=>	$quiz_data_meta,
			)
		);
	}
}

/**
 * Updates the quiz statistics for the given quiz attempt.
 *
 * Updates the score when the essay grading is adjusted, I ran this through manual SQL queries
 * because WpProQuiz doesn't offer an elegant way to grab a particular question and update it.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.2.0
 *
 * @param int     $quiz_id           Quiz ID.
 * @param int     $question_id       Question ID.
 * @param array   $updated_quiz_data Updated quiz statistics data.
 * @param WP_Post $essay             The `WP_Post` essay object.
 * @param array   $users_quiz_data   User quiz data.
 */
function learndash_update_quiz_statistics( $quiz_id, $question_id, $updated_quiz_data, $essay, $users_quiz_data ) {
	global $wpdb;

	if ( ( isset( $users_quiz_data['statistic_ref_id'] ) ) && ( ! empty( $users_quiz_data['statistic_ref_id'] ) ) ) {
		$refId = absint( $users_quiz_data['statistic_ref_id'] );
	} else {
		$refId = $wpdb->get_var(
			$wpdb->prepare("
						SELECT statistic_ref_id
						FROM ". LDLMS_DB::get_table_name( 'quiz_statistic_ref' ) ." WHERE quiz_id = %d AND user_id = %d
					", $quiz_id, $essay->post_author)
		);

		$refId = absint( $refId );
	}

	$row = $wpdb->get_results(
		$wpdb->prepare("
					SELECT *
					FROM ". LDLMS_DB::get_table_name( 'quiz_statistic' ) ." WHERE statistic_ref_id = %d AND question_id = %d
				", $refId, $question_id)
	);

	if ( empty( $row ) ) {
		return;
	}

	if ( $updated_quiz_data['updated_question_score'] > 0 ) {
		$correct_count = 1;
		$incorrect_count = 0;
	} else {
		$correct_count = 0;
		$incorrect_count = 1;
	}

	$update  = $wpdb->update(
		LDLMS_DB::get_table_name( 'quiz_statistic' ),
		array(
			'correct_count' => $correct_count,
			'incorrect_count' => $incorrect_count,
			'points' => $updated_quiz_data['updated_question_score'],
		),
		array(
			'statistic_ref_id' => $refId,
			'question_id' => $question_id,
		),
		array( '%d', '%d', '%d'	),
		array( '%d', '%d' )
	);

	/**
	 * Fires after the essay question stats are updated.
	 */
	do_action( 'learndash_essay_question_stats_updated' );
}



/**
 * Restricts the assignment listings view to group leader only.
 *
 * Fires on `parse_query` hook.
 *
 * @since 2.2.0
 *
 * @param object $query The `WP_Query` instance (passed by reference).
 *
 * @return object $query The `WP_Query` instance (passed by reference).
 */
function learndash_restrict_essay_listings_for_group_admins( $query ) {
	global $pagenow, $typenow;

	if ( !is_admin() ) return;
	if ( $pagenow != 'edit.php' ) return;
	if ( !$query->is_main_query() ) return;
	if ( empty( $typenow ) ) return;
	if ( $typenow != 'sfwd-essays' ) return;
	
	$q_vars = & $query->query_vars;

	$user_id = get_current_user_id();

	if ( learndash_is_group_leader_user( $user_id ) ) {
		
		$group_ids = learndash_get_administrators_group_ids( $user_id );
		
		$course_ids = array();
		$user_ids = array();

		if ( ! empty( $group_ids ) && is_array( $group_ids ) ) {
			foreach( $group_ids as $group_id ) {
				$group_course_ids = learndash_group_enrolled_courses( $group_id );
				if ( ! empty( $group_course_ids ) && is_array( $group_course_ids ) ) {
					$course_ids = array_merge( $course_ids, $group_course_ids );
				}

				$group_users = learndash_get_groups_user_ids( $group_id );
				if ( ! empty( $group_users ) && is_array( $group_users ) ) {
					foreach( $group_users as $group_user_id ) {
						$user_ids[ $group_user_id ] = $group_user_id;
					}
				}
			}
		}

		if ( ! empty( $course_ids ) && count( $course_ids ) ) {
			
			if (!isset( $q_vars['meta_query'] ) )
				$q_vars['meta_query'] = array();
			
			$q_vars['meta_query'][] = array(
				'key'     => 'course_id',
				'value'   => $course_ids,
				'compare' => 'IN',
			);
		} 
		
		if ( ! empty( $user_ids ) && count( $user_ids ) ) {
			$q_vars['author__in'] = $user_ids;
		} else {
			$q_vars['author__in'] = - 2;
		}
	}
}
add_filter( 'parse_query', 'learndash_restrict_essay_listings_for_group_admins' );


/**
 * Handles the AJAX file upload for an essay question.
 *
 * Fires on `learndash_upload_essay` AJAX action.
 *
 * @since 2.2.0
 *
 * Runs checks for needing information, or will die and send an error back to browser
 */
function learndash_upload_essay() {

	if ( ! isset( $_POST['nonce'] ) || ! isset( $_POST['question_id'] ) || ! isset( $_FILES['essayUpload'] ) ) {
		wp_send_json_error();
		die();
	}

	$nonce = $_POST['nonce'];
	$question_id = intval( $_POST['question_id'] );
	if ( empty( $question_id ) ) {
		wp_send_json_error();
		die();
	}

	/**
	 * Changes in v2.5.4 to include the question_id as part of the nonce
	 */
	if ( ! wp_verify_nonce( $nonce, 'learndash-upload-essay-'. $question_id ) ) {
		wp_send_json_error();
		die( 'Security check' );
	} else {

		if ( !is_user_logged_in() ) {
			/**
			 * Filters whether to allow essay upload or not if the user is not logged in.
			 *
			 * @param boolean $allow_upload Whether to allow upload.
			 * @param int     $question_id  ID of the essay question.
			 */
			if ( !apply_filters('learndash_essay_upload_user_check', false, $question_id ) ) {
				wp_send_json_error();
				die();
			}
		}

		$file_desc = learndash_essay_fileupload_process( $_FILES['essayUpload'], $question_id );

		if ( ! empty( $file_desc ) ) {
			wp_send_json_success( $file_desc );
		} else {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Unknown error.', 'learndash' )
				)
			);
		}
		die();
	}
}

add_action( 'wp_ajax_learndash_upload_essay', 'learndash_upload_essay' );
add_action( 'wp_ajax_nopriv_learndash_upload_essay', 'learndash_upload_essay' );


/**
 * Handles the file uploads for the essays.
 *
 * @since 2.2.0
 *
 * @param array $uploadfiles  An array of uploaded files data.
 * @param int   $question_id Question ID.
 *
 * @return array An array of file data like file name and link.
 */
function learndash_essay_fileupload_process( $uploadfiles, $question_id ) {
	if ( is_array( $uploadfiles ) ) {

		// look only for uploded files
		if ( $uploadfiles['error'] == 0 ) {

			$filetmp = $uploadfiles['tmp_name'];

			//clean filename
			$filename = learndash_clean_filename( $uploadfiles['name'] );

			//extract extension
			if ( ! function_exists( 'wp_get_current_user' ) ) {
				include ABSPATH . 'wp-includes/pluggable.php';
			}

			//current user
			$user = get_current_user_id();

			$limit_file_exts = learndash_get_allowed_upload_mime_extensions_for_post( $question_id );

			// get file info
			// @fixme: wp checks the file extension....
			$filetype = wp_check_filetype( basename( $filename ), $limit_file_exts );
			if ( ( empty( $filetype ) ) || ( empty( $filetype['ext'] ) ) || ( empty( $filetype['type'] ) ) || ( ! $limit_file_exts[ $filetype['ext'] ] ) ) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Invalid essay uploaded file type.', 'learndash' )
					)
				);
				die();
			}
						
			//$filetitle = preg_replace( '/\.[^.]+$/', '', basename( $filename ) );
			$filetitle = pathinfo( $filename, PATHINFO_FILENAME );
			$file_time = microtime(true) * 100;
			
			$filename = sprintf( 'question_%d_%d_%s.%s', $question_id, $file_time, $filetitle, $filetype['ext'] );
			/** This filter is documented in includes/import/class-ld-import-quiz-statistics.php */
			$filename = apply_filters( 'learndash_essay_upload_filename', $filename, $question_id, $filetitle, $filetype['ext'] );
			$upload_dir = wp_upload_dir();
			$upload_dir_base = str_replace( '\\', '/', $upload_dir['basedir'] );
			$upload_url_base = $upload_dir['baseurl'];
			/** This filter is documented in includes/import/class-ld-import-quiz-statistics.php */
			$upload_dir_path = $upload_dir_base . apply_filters( 'learndash_essay_upload_dirbase', '/essays', $filename, $upload_dir );
			/** This filter is documented in includes/import/class-ld-import-quiz-statistics.php */
			$upload_url_path = $upload_url_base . apply_filters( 'learndash_essay_upload_urlbase', '/essays/', $filename, $upload_dir );

			if ( ! file_exists( $upload_dir_path ) ) {
				if ( is_writable( dirname( $upload_dir_path ) ) ) {
					wp_mkdir_p( $upload_dir_path );
				} else {
					wp_send_json_error(
						array(
							'message' => esc_html__( 'Unable to write to UPLOADS directory. Is this directory writable by the server?', 'learndash' )
						)
					);
					die();
				}
			}

			// Add an index.php file to prevent directory browesing
			$_index = trailingslashit( $upload_dir_path ) . 'index.php';
			if ( !file_exists( $_index ) ) {
				file_put_contents ( $_index , '//LearnDash is THE Best LMS' );
			}					
			
			$file_title = pathinfo( basename( $filename ), PATHINFO_FILENAME );
			$file_ext   = pathinfo( basename( $filename ), PATHINFO_EXTENSION );

			/**
			 * Check if the filename already exist in the directory and rename the
			 * file if necessary
			 */
			$i = 0;

			while ( file_exists( $upload_dir_path . '/' . $filename ) ) {
				$i++;
				//$filename = sprintf( 'question_%d_%s_%d.%s', $question_id, $filetitle, $i, $filetype['ext'] );
				/** This filter is documented in includes/import/class-ld-import-quiz-statistics.php */
				//$filename = apply_filters( 'learndash_essay_upload_filename_dup', $filename, $question_id, $filetitle, $i, $filetype['ext'] );
				$filename = $file_title . '_' . $i . '.' . $file_ext;
			}
			
			$filedest = $upload_dir_path . '/' . $filename;
			$destination = $upload_url_path . $filename;

			/**
			 * Check write permissions
			 */
			if ( ! is_writeable( $upload_dir_path ) ) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Unable to write to directory. Is this directory writable by the server?', 'learndash' )
					)
				);
				die();
			}

			/**
			 * Save temporary file to uploads dir
			 */
			if ( ! @move_uploaded_file( $filetmp, $filedest ) ) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'The uploaded file could not be move to the destination directory.', 'learndash' )
					)
				);
				die();
			}

			$file_desc = array();
			$file_desc['filename'] = $filename;
			$file_desc['filelink'] = $destination;
			$file_desc['message']  = esc_html__( 'Essay upload success.', 'learndash' );

			return $file_desc;
		}
	}
}

/**
 * Handles the approval of the essay in bulk.
 *
 * Fires on `load-edit.php` hook.
 *
 * @since 2.3.0
 */
function learndash_essay_bulk_actions_approve() {
	if ( ( ( isset( $_REQUEST['post'] ) ) && ( ! empty( $_REQUEST['post'] ) ) && (is_array( $_REQUEST['post'] ) ) ) && ( ( isset( $_REQUEST['post_type'] ) ) && ( $_REQUEST['post_type'] == 'sfwd-essays' ) ) ) {
		
		$action = '';
  		if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
  			$action = esc_attr( $_REQUEST['action'] );

  		else if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
  			$action = esc_attr( $_REQUEST['action2'] );
	
		else if ( ( isset( $_REQUEST['ld_action'] ) ) && ( $_REQUEST['ld_action'] == 'approve_essay') )  
  			$action = 'approve_essay';
	
		if ( $action == 'approve_essay' ) {

			if ( ( isset( $_REQUEST['post'] ) ) && ( !empty( $_REQUEST['post'] ) ) ) {

				if ( !is_array( $_REQUEST['post'] ) ) 
					$essays = array($_REQUEST['post']);
				else
					$essays = $_REQUEST['post'];

				foreach( $essays as $essay_id ) {
					
					if ( ( !isset( $_REQUEST['essay_points'][$essay_id] ) ) || ( $_REQUEST['essay_points'][$essay_id] == '' ) ) 
						continue;

					// get the new assigned points. 
					$submitted_essay['points_awarded'] = intval( $_REQUEST['essay_points'][$essay_id] );

					$essay_post = get_post( $essay_id );
					if ( ( !empty( $essay_post ) ) && ( $essay_post instanceof WP_Post ) && ( $essay_post->post_type == 'sfwd-essays' ) ) {
					
						if ( $essay_post->post_status != 'graded' )
							$quiz_score_difference = 1;
					
						// First we update the essat post with the new post_status	
						$essay_post->post_status = 'graded';
						wp_update_post( $essay_post );
						
						$user_id 		= $essay_post->post_author;
						$quiz_id 		= get_post_meta( $essay_post->ID, 'quiz_id', true );
						$question_id 	= get_post_meta( $essay_post->ID, 'question_id', true );
						
						// Stole the following section ot code from learndash_save_essay_status_metabox_data();
						$submitted_essay_data = learndash_get_submitted_essay_data( $quiz_id, $question_id, $essay_post );
						
						if ( isset( $submitted_essay_data['points_awarded'] ) )
							$original_points_awarded = intval( $submitted_essay_data['points_awarded'] );
						else
							$original_points_awarded = 0;
						
						$submitted_essay_data['status'] = 'graded';
						
						// get the new assigned points. 
						$submitted_essay_data['points_awarded'] = intval( $_REQUEST['essay_points'][$essay_id] );
						
						/** This filter is documented in includes/quiz/ld-quiz-essays.php */
						$submitted_essay_data = apply_filters( 'learndash_essay_status_data', $submitted_essay_data );
						learndash_update_submitted_essay_data( $quiz_id, $question_id, $essay_post, $submitted_essay_data );

						if ( ! is_null( $original_points_awarded ) && ! is_null( $submitted_essay_data['points_awarded'] ) ) {
							if ( $submitted_essay_data['points_awarded'] > $original_points_awarded ) {
								$points_awarded_difference = intval( $submitted_essay_data['points_awarded'] ) - intval( $original_points_awarded );
							} else {
								$points_awarded_difference = ( intval( $original_points_awarded ) - intval( $submitted_essay_data['points_awarded'] ) ) * -1;
							}

							$updated_scoring_data = array(
								'updated_question_score' => $submitted_essay_data['points_awarded'],
								'points_awarded_difference' => $points_awarded_difference,
								'score_difference' => $quiz_score_difference
							);

							/** This filter is documented in includes/quiz/ld-quiz-essays.php */
							$updated_scoring = apply_filters( 'learndash_updated_essay_scoring', $updated_scoring_data );
							learndash_update_quiz_data( $quiz_id, $question_id, $updated_scoring_data, $essay_post );

							/** This action is documented in includes/quiz/ld-quiz-essays.php */
							do_action( 'learndash_essay_all_quiz_data_updated', $quiz_id, $question_id, $updated_scoring_data, $essay_post );
						}
					}
				}
			}
		}
	}		
}

add_action( 'load-edit.php', 'learndash_essay_bulk_actions_approve' );


/**
 * Deletes the uploaded file when essay post is deleted.
 *
 * Fires on `before_delete_post` hook.
 *
 * @since 2.5.0
 *
 * @param int $post_id Post ID.
 */
function learndash_before_delete_essay( $post_id ) {
	
	if ( ( !empty( $post_id ) ) && ( 'sfwd-essays' == get_post_type( $post_id ) ) ) {
		$file_path = get_post_meta( $post_id, 'upload', true );
		if ( !empty( $file_path ) ) {
			$file_path = basename( $file_path );
			
			$url_link_arr = wp_upload_dir();
			$file_path = trailingslashit( str_replace('\\', '/', $url_link_arr['basedir'] ) ) . 'essays/' . basename( $file_path );
			if ( file_exists( $file_path ) ) {
				unlink( $file_path );
			}
		}
	}
}

add_action( 'before_delete_post', 'learndash_before_delete_essay' );


/**
 * Updates the essays post meta with a reference to the quiz attempt user meta.
 *
 * Fires on `learndash_quiz_submitted` hook.
 *
 * @since 3.1.0
 *
 * @param array   $quizdata Optional. An array of quiz attempt data. Default empty array.
 * @param WP_User $user     The `WP_User` instance.
 */
function learndash_quiz_submitted_update_essay( $quizdata = array(), $user ) {
	if ( ( isset( $quizdata['time'] ) ) && ( ! empty( $quizdata['time'] ) ) ) {
		if ( ( isset( $quizdata['has_graded'] ) ) && ( true === $quizdata['has_graded'] ) ) {
			if ( ( isset( $quizdata['graded'] ) ) && ( ! empty( $quizdata['graded'] ) ) ) {
				foreach( $quizdata['graded'] as $question_id => $graded_data ) {
					if ( isset( $graded_data['post_id'] ) ) {
						$essay_post_id = absint( $graded_data['post_id'] );
						if ( ! empty( $essay_post_id ) ) {
							$quiz_time = get_post_meta( $essay_post_id, 'quiz_time', true );
							if ( ! $quiz_time ) {
								update_post_meta( $essay_post_id, 'quiz_time', $quizdata['time'] );
							}
						}
					}
				}
			}
		}
	}
} 
add_action( 'learndash_quiz_submitted', 'learndash_quiz_submitted_update_essay', 1, 2 );