<?php
/**
 * Shortcode for ld_course_list
 *
 * @since 2.1.0
 *
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Builds the `ld_course_list` shortcode output.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @since 2.1.0
 *
 * @param array $attr {
 *    An array of shortcode attributes.
 *
 *    @type string          $include_outer_wrapper Optional. Whether to include outer wrapper.  Default 'true'.
 *    @type int             $paged                 Optional. Is the query paged. Default false.
 *    @type string          $post_type             Optional. Post type slug. Default 1.
 *    @type string          $post_status           Optional. The post status. Default 'publish'.
 *    @type string          $order                 Optional. Designates ascending ('ASC') or descending ('DESC') order. Default 'DESC'.
 *    @type string          $orderby               Optional. The name of the field to order posts by. Default 'ID'.
 *    @type int|false       $user_id               Optional. User ID. Default false
 *    @type string          $mycourses             Optional. Type of courses. Can be 'enrolled', 'not-enrolled'. Default null
 *    @type string          $status                Optional. Status of the course. Default null
 *    @type string          $post__in              Optional. List of posts IDs to check. Default null.
 *    @type string          $course_id             Optional. Course ID Default empty.
 *    @type string          $meta_key              Optional. Meta key Default empty.
 *    @type string          $meta_value            Optional. Meta Value. Default empty.
 *    @type string          $meta_compare          Optional. Meta compare operator Default empty.
 *    @type string          $tag                   Optional. Tag slug. Comma-separated (either), Plus-separated (all). Default empty.
 *    @type int|array       $tag_id                Optional. An array of tag ids (AND in). Default 0.
 *    @type string|array    $tag__and              Optional. An array of tag ids (AND in). Default empty.
 *    @type string|array    $tag__in               Optional. An array of tag ids (OR in). Default empty.
 *    @type string|array    $tag__not_in           Optional. An array of tag ids (NOT in). Default empty.
 *    @type string|array    $tag_slug__and         Optional. An array of tag slugs (AND in). Default empty.
 *    @type string|array    $tag_slug__in          Optional. An array of tag slugs (OR in). Default empty.
 *    @type string          $cat                   Optional. Category ID or comma-separated list of IDs (this or any children). Default empty.
 *    @type string          $category_name         Optional. Use category slug (not name, this or any children). Default 0.
 *    @type string|array    $category__and         Optional. An array of category IDs (AND in). Default empty.
 *    @type string|array    $category__in          Optional. An array of category IDs (OR in, no children). Default empty.
 *    @type string|array    $category__not_in      Optional. An array of category IDs (NOT in). Default empty.
 *    @type string          $tax_compare           Optional. Taxonomy compare operator. Default 'AND'
 *    @type string          $categoryselector      Optional. Category selector. Default empty.
 *    @type string          $show_thumbnail        Optional. Whether to show thumbnail. Default 'true'.
 *    @type string          $show_content          Optional. Whether to show content. Default 'true'.
 *    @type string          $author__in            Optional. An array of author IDs to query from. Default empty.
 *    @type string          $col                   Optional. Column. Default empty
 *    @type string          $progress_bar          Optional. Whether to show progress bar. Default 'false'.
 *    @type boolean         $array                 Optional. Unused. Default false.
 *    @type string          $course_grid           Optional. Whether to show progress bar. Default 'true'.
 * }
 *
 * @return string The `ld_course_list` shortcode output.
 */
function ld_course_list( $attr ) {
	global $learndash_shortcode_used;

	/**
	 * Filters course list shortcode attribute defaults.
	 *
	 * @param array $shortcode_default An Array of default shortcode attributes.
	 * @param array $shortcode_args    User defined attributes in shortcode tag.
	 */
	$attr_defaults = apply_filters(
		'ld_course_list_shortcode_attr_defaults',
		array(

			'include_outer_wrapper' => 'true',

			'num'                   => false,
			'paged'                 => 1,

			'post_type'             => learndash_get_post_type_slug( 'course' ),
			'post_status'           => 'publish',
			'order'                 => 'DESC',
			'orderby'               => 'ID',

			'user_id'               => false,
			'mycourses'             => null,
			'mygroups'              => null,
			'status'                => null,
			'post__in'              => null,

			'course_id'             => '',
			// Not sure why these are here as there is not supported logic.
			// 'lesson_id' => '',
			// 'topic_id' => '',

			'meta_key'              => '',
			'meta_value'            => '',
			'meta_compare'          => '',

			'tag'                   => '',
			'tag_id'                => 0,
			'tag__and'              => '',
			'tag__in'               => '',
			'tag__not_in'           => '',
			'tag_slug__and'         => '',
			'tag_slug__in'          => '',

			'cat'                   => '',
			'category_name'         => 0,
			'category__and'         => '',
			'category__in'          => '',
			'category__not_in'      => '',

			'tax_compare'           => 'AND',
			'categoryselector'      => '',

			'show_thumbnail'        => 'true',
			'show_content'          => 'true',

			'author__in'            => '',
			'col'                   => '',
			'progress_bar'          => 'false',
			'array'                 => false,
			'course_grid'           => 'true',
		),
		$attr
	);

	$post_type_slug  = 'course';
	$post_type_Class = 'LearnDash_Settings_Courses_Taxonomies';

	if ( ( isset( $attr['post_type'] ) ) && ( ! empty( $attr['post_type'] ) ) ) {

		if ( $attr['post_type'] == learndash_get_post_type_slug( 'lesson' ) ) {
			$post_type_slug  = 'lesson';
			$post_type_Class = 'LearnDash_Settings_Lessons_Taxonomies';
		} elseif ( $attr['post_type'] == learndash_get_post_type_slug( 'topic' ) ) {
			$post_type_slug  = 'topic';
			$post_type_Class = 'LearnDash_Settings_Topics_Taxonomies';
		} elseif ( $attr['post_type'] == learndash_get_post_type_slug( 'quiz' ) ) {
			$post_type_slug  = 'quiz';
			$post_type_Class = 'LearnDash_Settings_Quizzes_Taxonomies';
		} elseif ( $attr['post_type'] == learndash_get_post_type_slug( 'group' ) ) {
			$post_type_slug  = 'group';
			$post_type_Class = 'LearnDash_Settings_Groups_Taxonomies';
		}
	}

	if ( ! empty( $post_type_slug ) ) {
		$attr_defaults = array_merge(
			$attr_defaults,
			array(
				$post_type_slug . '_categoryselector' => '',
				$post_type_slug . '_cat'              => '',
				$post_type_slug . '_category_name'    => '',
				$post_type_slug . '_category__and'    => '',
				$post_type_slug . '_category__in'     => '',
				$post_type_slug . '_category__not_in' => '',

				$post_type_slug . '_tag'              => '',
				$post_type_slug . '_tag_id'           => '',
				$post_type_slug . '_tag__and'         => '',
				$post_type_slug . '_tag__in'          => '',
				$post_type_slug . '_tag__not_in'      => '',
				$post_type_slug . '_tag_slug__and'    => '',
				$post_type_slug . '_tag_slug__in'     => '',
			)
		);
	}

	$atts = shortcode_atts( $attr_defaults, $attr );

	if ( in_array( $atts['post_type'], learndash_get_post_types( 'course' ) ) ) {
		if ( ( $atts['mycourses'] == 'true' ) || ( $atts['mycourses'] == 'enrolled' ) ) {
			if ( is_user_logged_in() ) {
				$atts['mycourses'] = 'enrolled';
			} else {
				return '';
			}
		} elseif ( $atts['mycourses'] == 'not-enrolled' ) {
			if ( is_user_logged_in() ) {
				$atts['mycourses'] = 'not-enrolled';
			} else {
				return '';
			}
		} else {
			$atts['mycourses'] = null;
		}

		$atts['course_status'] = array();
		if ( 'enrolled' === $atts['mycourses'] ) {
			if ( ! empty( $atts['status'] ) ) {

				if ( ! is_array( $atts['status'] ) ) {
					$atts['status'] = explode( ',', $atts['status'] );
				}
				$atts['status'] = array_map( 'trim', $atts['status'] );

				foreach ( $atts['status'] as $course_status ) {
					if ( 'completed' == $course_status ) {
						$atts['course_status'][] = 'COMPLETED';
					} elseif ( 'in_progress' == $course_status ) {
						$atts['course_status'][] = 'IN_PROGRESS';
					} elseif ( 'not_started' == $course_status ) {
						$atts['course_status'][] = 'NOT_STARTED';
					}
				}
			}
		} else {
			$atts['course_status'] = null;
		}
		unset( $atts['status'] );
	} elseif ( $atts['post_type'] === learndash_get_post_type_slug( 'group' ) ) {
		if ( ( 'true' === $atts['mygroups'] ) || ( 'enrolled' === $atts['mygroups'] ) ) {
			if ( is_user_logged_in() ) {
				$atts['mygroups'] = 'enrolled';
			} else {
				return '';
			}
		} elseif ( 'not-enrolled' === $atts['mygroups'] ) {
			if ( is_user_logged_in() ) {
				$atts['mygroups'] = 'not-enrolled';
			} else {
				return '';
			}
		} else {
			$atts['mygroups'] = null;
		}

		$atts['group_status'] = array();
		if ( 'enrolled' === $atts['mygroups'] ) {
			if ( ! empty( $atts['status'] ) ) {
				if ( ! is_array( $atts['status'] ) ) {
					$atts['status'] = explode( ',', $atts['status'] );
				}
				$atts['status'] = array_map( 'trim', $atts['status'] );

				foreach ( $atts['status'] as $group_status ) {
					if ( 'completed' == $group_status ) {
						$atts['group_status'][] = 'completed';
					} elseif ( 'in_progress' == $group_status ) {
						$atts['group_status'][] = 'in-progress';
					} elseif ( 'not_started' == $group_status ) {
						$atts['group_status'][] = 'not-started';
					}
				}
			}
		} else {
			$atts['group_status'] = null;
		}
		unset( $atts['status'] );
	}

	if ( $atts['post__in'] === '' ) {
		$atts['post__in'] = null;
	}

	// if ( isset( $atts['num'] ) )
	// $atts['num'] = intval( $atts['num'] );

	if ( $atts['num'] === false ) {
		if ( ( isset( $atts['course_id'] ) ) && ( ! empty( $atts['course_id'] ) ) ) {
			$atts['num'] = learndash_get_course_lessons_per_page( intval( $atts['course_id'] ) );
		} else {
			$atts['num'] = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' );
		}
	} elseif ( $atts['num'] == '-1' ) {
		$atts['num'] = 0;
	} else {
		$atts['num'] = intval( $atts['num'] );
	}

	if ( $atts['num'] == 0 ) {
		$atts['num'] = -1;
	}

	/**
	 * Filters course list shortcode attribute values.
	 *
	 * @param array $atts Combined and filtered attribute list.
	 * @param array $attr User defined attributes in shortcode tag.
	 */
	$atts = apply_filters( 'ld_course_list_shortcode_attr_values', $atts, $attr );

	if ( is_user_logged_in() ) {

		if ( ( isset( $atts['user_id'] ) ) && ( $atts['user_id'] === false ) ) {
			$atts['user_id'] = get_current_user_id();
		} elseif ( ( isset( $atts['user_id'] ) ) && ( $atts['user_id'] !== false ) ) {
			if ( learndash_is_admin_user() ) {
				// Good leave the user_id in place.
			} elseif ( learndash_is_group_leader_user( get_current_user_id() ) ) {
				$groups = learndash_get_administrators_group_ids( get_current_user_id() );
				if ( ! empty( $groups ) ) {
					$user_courses = array();
					foreach ( $groups as $group_id ) {
						if ( learndash_is_user_in_group( $atts['user_id'], $group_id ) ) {
							$group_courses = learndash_group_enrolled_courses( $group_id );
							if ( ! empty( $group_courses ) ) {
								$user_courses = array_merge( $user_courses, $group_courses );
							}
						}
					}
					if ( ! empty( $user_courses ) ) {
						$atts['post__in'] = $user_courses;
					}
				} else {
					$atts['user_id'] = get_current_user_id();
				}
			} else {
				$atts['user_id'] = get_current_user_id();
			}
		}
	} else {
		$atts['user_id']   = false;
		$atts['mycourses'] = null;
	}

	extract( $atts );

	global $post;

	$filter     = array(
		'post_type'      => $post_type,
		'post_status'    => $post_status,
		'posts_per_page' => $num,
		'paged'          => $paged,
		'order'          => $order,
		'orderby'        => $orderby,
	);
	$meta_query = array();

	// Added an empty meta query set. Then we check later and if still empty we remove it before calling get_posts.
	if ( ! isset( $filter['meta_query'] ) ) {
		$filter['meta_query'] = array();
	}

	if ( ! empty( $author__in ) ) {
		$filter['author__in'] = $author__in;
	}

	/*
	if ( ! empty( $meta_key ) ) {
		$filter['meta_key'] = $meta_key;
	}

	if ( ! empty( $meta_value ) ) {
		$filter['meta_value'] = $meta_value;
	}

	if ( ! empty( $meta_compare ) ) {
		if ( !empty( $filter['meta_key'] ) ) {
			$filter['meta_compare'] = $meta_compare;
		}
	}
	*/

	if ( ( ! empty( $meta_key ) ) && ( ! empty( $meta_value ) ) ) {
		// if ( $meta_key == 'course_id' ) {
		// if ( empty( $course_id ) ) {
		// $course_id = $meta_value;
		// $atts['course_id'] = $meta_value;
		// }
		// } else {

			$meta_query = array(
				'key'   => $meta_key,
				'value' => $meta_value,
			);

			if ( empty( $meta_compare ) ) {
				$meta_compare = '=';
			}

			$meta_query['compare'] = $meta_compare;

			$filter['meta_query'][] = $meta_query;
			// }
	}

	if ( ( ! empty( $course_id ) ) && ( is_null( $post__in ) ) ) {
		if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			$filter['post__in'] = learndash_course_get_steps_by_type( $course_id, $atts['post_type'] );
		} else {
			$meta_query = array(
				'key'     => 'course_id',
				'value'   => intval( $course_id ),
				'compare' => '=',
			);
		}

		$filter['meta_query'][] = $meta_query;
	} elseif ( ! empty( $post__in ) ) {
		$filter['post__in'] = $post__in;
	}

	if ( LearnDash_Settings_Section::get_section_setting( $post_type_Class, 'wp_post_category' ) == 'yes' ) {

		if ( ! empty( $cat ) ) {
			// $filter['cat'] = $cat;

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => intval( $cat ),
			);
		}

		if ( ! empty( $category_name ) ) {
			// $filter['category_name'] = $category_name;

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'category',
				'field'    => 'slug',
				'terms'    => trim( $category_name ),
			);
		}

		if ( ! empty( $category__and ) ) {
			// $filter['category__and'] = explode( ',', $category__and );

			$category__and = array_map( 'intval', explode( ',', $category__and ) );

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => $category__and,
				'operator' => 'AND',
			);
		}

		if ( ! empty( $category__in ) ) {
			// $filter['category__in'] = explode( ',', $category__in );

			$category__in = array_map( 'intval', explode( ',', $category__in ) );

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => $category__in,
				'operator' => 'IN',
			);
		}

		if ( ! empty( $category__not_in ) ) {
			// $filter['category__not_in'] = explode( ',', $category__not_in );

			$category__not_in = array_map( 'intval', explode( ',', $category__not_in ) );

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => $category__not_in,
				'operator' => 'NOT IN',
			);
		}
	}

	if ( LearnDash_Settings_Section::get_section_setting( $post_type_Class, 'wp_post_tag' ) == 'yes' ) {

		if ( ! empty( $tag ) ) {
			// $filter['tag'] = $tag;

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'post_tag',
				'field'    => 'slug',
				'terms'    => trim( $tag ),
			);

		}

		if ( ! empty( $tag_id ) ) {
			// $filter['tag_id'] = $tag;

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'post_tag',
				'field'    => 'term_id',
				'terms'    => intval( $tag_id ),
			);

		}

		if ( ! empty( $tag__and ) ) {
			// $filter['tag__and'] = explode( ',', $tag__and );

			$tag__and = array_map( 'intval', explode( ',', $tag__and ) );

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'post_tag',
				'field'    => 'term_id',
				'terms'    => $tag__and,
				'operator' => 'AND',
			);
		}

		if ( ! empty( $tag__in ) ) {
			// $filter['tag__in'] = explode( ',', $tag__in );

			$tag__in = array_map( 'intval', explode( ',', $tag__in ) );

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'post_tag',
				'field'    => 'term_id',
				'terms'    => $tag__in,
				'operator' => 'IN',
			);

		}

		if ( ! empty( $tag__not_in ) ) {
			// $filter['tag__not_in'] = explode( ',', $tag__not_in );

			$tag__not_in = array_map( 'intval', explode( ',', $tag__not_in ) );

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'post_tag',
				'field'    => 'term_id',
				'terms'    => $tag__not_in,
				'operator' => 'NOT IN',
			);
		}

		if ( ! empty( $tag_slug__and ) ) {
			// $filter['tag_slug__and'] = explode( ',', $tag_slug__and );

			$tag_slug__and = array_map( 'trim', explode( ',', $tag_slug__and ) );

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'post_tag',
				'field'    => 'slug',
				'terms'    => $tag_slug__and,
				'operator' => 'AND',
			);
		}

		if ( ! empty( $tag_slug__in ) ) {
			// $filter['tag_slug__in'] = explode( ',', $tag_slug__in );

			$tag_slug__in = array_map( 'trim', explode( ',', $tag_slug__in ) );

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'post_tag',
				'field'    => 'slug',
				'terms'    => $tag_slug__in,
				'operator' => 'IN',
			);
		}
	}

	if ( LearnDash_Settings_Section::get_section_setting( $post_type_Class, 'ld_' . $post_type_slug . '_category' ) == 'yes' ) {

		// course_cat="123"
		if ( ( isset( $atts[ $post_type_slug . '_cat' ] ) ) && ( ! empty( $atts[ $post_type_slug . '_cat' ] ) ) ) {

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'ld_' . $post_type_slug . '_category',
				'field'    => 'term_id',
				'terms'    => intval( $atts[ $post_type_slug . '_cat' ] ),
			);
		}

		// course_category_name (string) - use category slug.
		// course_category_name="course-category-one"
		if ( ( isset( $atts[ $post_type_slug . '_category_name' ] ) ) && ( ! empty( $atts[ $post_type_slug . '_category_name' ] ) ) ) {

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'ld_' . $post_type_slug . '_category',
				'field'    => 'slug',
				'terms'    => trim( $atts[ $post_type_slug . '_category_name' ] ),
			);
		}

		// course_category__and (array) - use category id.
		if ( ( isset( $atts[ $post_type_slug . '_category__and' ] ) ) && ( ! empty( $atts[ $post_type_slug . '_category__and' ] ) ) ) {

			$cat__and = array_map( 'intval', explode( ',', $atts[ $post_type_slug . '_category__and' ] ) );

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy'         => 'ld_' . $post_type_slug . '_category',
				'field'            => 'term_id',
				'terms'            => $cat__and,
				'operator'         => 'AND',
				'include_children' => false,
			);
		}

		// course_category__in (array) - use category id.
		if ( ( isset( $atts[ $post_type_slug . '_category__in' ] ) ) && ( ! empty( $atts[ $post_type_slug . '_category__in' ] ) ) ) {

			$cat__in = array_map( 'intval', explode( ',', $atts[ $post_type_slug . '_category__in' ] ) );

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy'         => 'ld_' . $post_type_slug . '_category',
				'field'            => 'term_id',
				'terms'            => $cat__in,
				'operator'         => 'IN',
				'include_children' => false,
			);
		}

		// course_category___not_in (array) - use category id.
		if ( ( isset( $atts[ $post_type_slug . '_category__not_in' ] ) ) && ( ! empty( $atts[ $post_type_slug . '_category__not_in' ] ) ) ) {

			$cat__not_in = array_map( 'intval', explode( ',', $atts[ $post_type_slug . '_category__not_in' ] ) );

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy'         => 'ld_' . $post_type_slug . '_category',
				'field'            => 'term_id',
				'terms'            => $cat__not_in,
				'operator'         => 'NOT IN',
				'include_children' => false,
			);
		}
	}

	if ( LearnDash_Settings_Section::get_section_setting( $post_type_Class, 'ld_' . $post_type_slug . '_tag' ) == 'yes' ) {

		// course_tag (string) - use tag slug.
		if ( ( isset( $atts[ $post_type_slug . '_tag' ] ) ) && ( ! empty( $atts[ $post_type_slug . '_tag' ] ) ) ) {

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'ld_' . $post_type_slug . '_tag',
				'field'    => 'slug',
				'terms'    => trim( $atts[ $post_type_slug . '_tag' ] ),
			);
		}

		// course_tag_id (int) - use tag id.
		if ( ( isset( $atts[ $post_type_slug . '_tag_id' ] ) ) && ( ! empty( $atts[ $post_type_slug . '_tag_id' ] ) ) ) {

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'ld_' . $post_type_slug . '_tag',
				'field'    => 'term_id',
				'terms'    => intval( $atts[ $post_type_slug . '_tag_id' ] ),
			);
		}

		// course_tag__and (array) - use tag ids.
		if ( ( isset( $atts[ $post_type_slug . '_tag__and' ] ) ) && ( ! empty( $atts[ $post_type_slug . '_tag__and' ] ) ) ) {

			$tag__and = array_map( 'intval', explode( ',', $atts[ $post_type_slug . '_tag__and' ] ) );

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'ld_' . $post_type_slug . '_tag',
				'field'    => 'term_id',
				'terms'    => $tag__and,
				'operator' => 'AND',
			);
		}

		// course_tag__in (array) - use tag ids.
		if ( ( isset( $atts[ $post_type_slug . '_tag__in' ] ) ) && ( ! empty( $atts[ $post_type_slug . '_tag__in' ] ) ) ) {

			$tag__in = array_map( 'intval', explode( ',', $atts[ $post_type_slug . '_tag__in' ] ) );

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'ld_' . $post_type_slug . '_tag',
				'field'    => 'term_id',
				'terms'    => $tag__in,
				'operator' => 'IN',
			);
		}

		// course_tag__not_in (array) - use tag ids.
		if ( ( isset( $atts[ $post_type_slug . '_tag__not_in' ] ) ) && ( ! empty( $atts[ $post_type_slug . '_tag__not_in' ] ) ) ) {

			$tag__not_in = array_map( 'intval', explode( ',', $atts[ $post_type_slug . '_tag__not_in' ] ) );

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'ld_' . $post_type_slug . '_tag',
				'field'    => 'term_id',
				'terms'    => $tag__not_in,
				'operator' => 'NOT IN',
			);
		}

		// course_tag_slug__and (array) - use tag slugs.
		if ( ( isset( $atts[ $post_type_slug . '_tag_slug__and' ] ) ) && ( ! empty( $atts[ $post_type_slug . '_tag_slug__and' ] ) ) ) {

			$tag_slug__and = array_map( 'trim', explode( ',', $atts[ $post_type_slug . '_tag_slug__and' ] ) );

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'ld_' . $post_type_slug . '_tag',
				'field'    => 'slug',
				'terms'    => $tag_slug__and,
				'operator' => 'AND',
			);
		}

		// course_tag_slug__in (array) - use tag slugs.
		if ( ( isset( $atts[ $post_type_slug . '_tag_slug__in' ] ) ) && ( ! empty( $atts[ $post_type_slug . '_tag_slug__in' ] ) ) ) {

			$tag_slug__in = array_map( 'trim', explode( ',', $atts[ $post_type_slug . '_tag_slug__in' ] ) );

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'ld_' . $post_type_slug . '_tag',
				'field'    => 'slug',
				'terms'    => $tag_slug__in,
				'operator' => 'IN',
			);
		}
	}

	if ( ( isset( $filter['tax_query'] ) ) && ( count( $filter['tax_query'] ) > 1 ) ) {
		// Due to a quick on WP_Query the 'compare' option needs to be in the first position.
		// So we save off the current tax_query, add the 'relation', then merge in the original tax_query
		$tax_query           = $filter['tax_query'];
		$filter['tax_query'] = array( 'relation' => $tax_compare );
		$filter['tax_query'] = array_merge( $filter['tax_query'], $tax_query );

	} elseif ( ! empty( $meta_compare ) ) {
		$filter['meta_compare'] = $meta_compare;
	}

	// Logic to determine the exact post ids to query. This will help drive the category selectors below and prevent extra queries.

	$shortcode_course_id = null;
	if ( is_null( $post__in ) ) {
		if ( in_array( $atts['post_type'], learndash_get_post_types( 'course' ) ) ) {
			if ( $mycourses == 'enrolled' ) {
				$filter['post__in'] = learndash_user_get_enrolled_courses( $atts['user_id'] );
				if ( empty( $filter['post__in'] ) ) {
					return;
				}

				if ( ! empty( $course_status ) ) {
					$activity_query_args             = array(
						'post_types'      => 'sfwd-courses',
						'activity_types'  => 'course',
						'activity_status' => $course_status,
						'orderby_order'   => 'users.ID, posts.post_title',
						'date_format'     => 'F j, Y H:i:s',
						'per_page'        => '',
					);
					$activity_query_args['user_ids'] = array( $atts['user_id'] );
					$activity_query_args['post_ids'] = $filter['post__in'];

					$user_courses_reports = learndash_reports_get_activity( $activity_query_args );
					if ( ! empty( $user_courses_reports['results'] ) ) {
						// foreach( $user_courses_reports['results'] as $result ) {
						$filter['post__in'] = wp_list_pluck( $user_courses_reports['results'], 'post_id' );
						$filter['post__in'] = array_map( 'absint', $filter['post__in'] );
					} else {
						$filter['post__in'] = array(0);
					}
				}
			} elseif ( $mycourses == 'not-enrolled' ) {
				if ( in_array( $atts['post_type'], learndash_get_post_types( 'course' ) ) ) {
					$filter['post__not_in'] = learndash_user_get_enrolled_courses( $atts['user_id'] );
				}

				if ( empty( $filter['post__not_in'] ) ) {
					unset( $filter['post__not_in'] );
				}
			}
		} elseif ( $atts['post_type'] === learndash_get_post_type_slug( 'group' ) ) {
			if ( $mygroups == 'enrolled' ) {
				$user_group_ids = learndash_get_users_group_ids( $atts['user_id'] );
				if ( empty( $user_group_ids ) ) {
					return;
				}

				if ( ! empty( $group_status ) ) {
					foreach ( $user_group_ids as $group_id ) {
						$group_status = learndash_get_user_group_status( $group_id, $atts['user_id'], true );
						if ( ( ! empty( $group_status ) ) && ( ! empty( $atts['group_status'] ) ) && ( in_array( $group_status, $atts['group_status'] ) ) ) {
							$filter['post__in'][] = $group_id;
						}
					}
					if ( empty( $filter['post__in'] ) ) {
						return;
					}
				} else {
					$filter['post__in'] = $user_group_ids;
				}
			} elseif ( $mygroups == 'not-enrolled' ) {
				$filter['post__not_in'] = learndash_get_users_group_ids( $atts['user_id'] );
				if ( empty( $filter['post__not_in'] ) ) {
					unset( $filter['post__not_in'] );
				}
			}
		}
	}

	/**
	 * Filters course list shortcode query arguments.
	 *
	 * @param array $filter Query arguments.
	 * @param array $atts  Combined and filtered attribute list.
	 */
	$filter = apply_filters( 'learndash_ld_course_list_query_args', $filter, $atts );

	if ( $array == 'true' ) {
		return get_posts( $filter );
	}

	if ( ( is_singular( $post_type ) ) && ( $post ) && ( is_a( $post, 'WP_Post' ) ) && ( $post->post_type == $post_type ) ) {
		if ( ( isset( $filter['post__not_in'] ) ) && ( ! empty( $filter['post__not_in'] ) ) ) {
			$filter['post__not_in'][] = $post->ID;
		} else {
			$filter['post__not_in'] = array( $post->ID );
		}
	}

	// At this point the $filter var contains all the shortcode processing logic.
	// So now we want to save off the var to one used by the category selector (if used).
	$filter_cat                   = $filter;
	$filter_cat['posts_per_page'] = -1;

	$ld_categorydropdown = '';

	$categories    = array();
	$ld_categories = array();

	// if ( $include_outer_wrapper == 'true' ) {

	if ( ( trim( $categoryselector ) == 'true' ) && ( LearnDash_Settings_Section::get_section_setting( $post_type_Class, 'wp_post_category' ) == 'yes' ) ) {
		$cats = array();

		if ( ( isset( $_GET['catid'] ) ) && ( ! empty( $_GET['catid'] ) ) ) {
			$atts['cat'] = intval( $_GET['catid'] );

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => intval( $_GET['catid'] ),
			);
		}

		// if ( isset( $filter_cat['post__in'] ) ) {
			// $filter_cat['include'] = $filter_cat['post__in'];
		// unset( $filter_cat['post__in'] );
		// }
		// if ( isset( $filter_cat['post__not_in'] ) ) {
			// $filter_cat['include'] = $filter_cat['post__in'];
		// unset( $filter_cat['post__not_in'] );
		// }

		$cat_posts = get_posts( $filter_cat );

		// We first need to build a listing of the categories used by each of the queried posts.
		if ( ! empty( $cat_posts ) ) {
			foreach ( $cat_posts as $cat_post ) {
				$post_categories = wp_get_post_categories( $cat_post->ID );
				if ( ! empty( $post_categories ) ) {
					foreach ( $post_categories as $c ) {

						if ( empty( $cats[ $c ] ) ) {
							$cat        = get_category( $c );
							$cats[ $c ] = array(
								'id'     => $cat->cat_ID,
								'name'   => $cat->name,
								'slug'   => $cat->slug,
								'parent' => $cat->parent,
								'count'  => 0,
								'posts'  => array(),
							);
						}

						$cats[ $c ]['count']++;
						$cats[ $c ]['posts'][] = $post->ID;
					}
				}
			}

			// Once we have these categories we need to requery the categories in order to get them into a proper ordering.
			if ( ! empty( $cats ) ) {

				// And also let this query be filtered.
				/**
				 * Filters course list category query arguments.
				 *
				 * @param array $query_arguments Query arguments to be used in get_categories.
				 */
				$get_categories_args = apply_filters(
					'learndash_course_list_category_args',
					array(
						'taxonomy' => 'category',
						'type'     => $post_type,
						'include'  => array_keys( $cats ),
						'orderby'  => 'name',
						'order'    => 'ASC',
					)
				);

				if ( ! empty( $get_categories_args ) ) {
					$categories = get_categories( $get_categories_args );
				}
			}
		}
	} else {
		$categoryselector = '';
		$atts['categoryselector'];
	}

		// We can only support one of the other category OR course_category selectors
	if ( ( trim( $atts[ $post_type_slug . '_categoryselector' ] ) == 'true' ) && ( empty( $categoryselector ) )
		  && ( LearnDash_Settings_Section::get_section_setting( $post_type_Class, 'ld_' . $post_type_slug . '_category' ) == 'yes' ) ) {
		$ld_cats = array();

		if ( ( isset( $_GET[ $post_type_slug . '_catid' ] ) ) && ( ! empty( $_GET[ $post_type_slug . '_catid' ] ) ) ) {

			$atts[ $post_type_slug . '_cat' ] = intval( $_GET[ $post_type_slug . '_catid' ] );

			if ( ! isset( $filter['tax_query'] ) ) {
				$filter['tax_query'] = array();
			}

			$filter['tax_query'][] = array(
				'taxonomy' => 'ld_' . $post_type_slug . '_category',
				'field'    => 'term_id',
				'terms'    => intval( $_GET[ $post_type_slug . '_catid' ] ),
			);
		}

		$cat_posts = get_posts( $filter_cat );

		// We first need to build a listing of the categories used by each of the queried posts.
		if ( ! empty( $cat_posts ) ) {
			$args = array( 'fields' => 'ids' );
			foreach ( $cat_posts as $cat_post ) {
				$post_categories = wp_get_object_terms( $cat_post->ID, 'ld_' . $post_type_slug . '_category', $args );
				if ( ! empty( $post_categories ) ) {
					foreach ( $post_categories as $c ) {

						if ( empty( $ld_cats[ $c ] ) ) {
							$ld_cat        = get_term( $c, 'ld_' . $post_type_slug . '_category' );
							$ld_cats[ $c ] = array(
								'id'     => $ld_cat->cat_ID,
								'name'   => $ld_cat->name,
								'slug'   => $ld_cat->slug,
								'parent' => $ld_cat->parent,
								'count'  => 0,
								'posts'  => array(),
							);
						}

						$ld_cats[ $c ]['count']++;
						$ld_cats[ $c ]['posts'][] = $cat_post->ID;
					}
				}
			}

			// Once we have these categories we need to requery the categories in order to get them into a proper ordering.
			if ( ! empty( $ld_cats ) ) {

				// And also let this query be filtered.
				/**
				 * Filters course list terms query arguments according to post type slug.
				 *
				 * The dynamic part of the hook `$post_type_slug` refers to the slug of any post type.
				 *
				 * @param array $query_arguments Query arguments to be used in get_terms.
				 */
				$get_ld_categories_args = apply_filters(
					'learndash_course_list_' . $post_type_slug . '_category_args',
					array(
						'taxonomy' => 'ld_' . $post_type_slug . '_category',
						'type'     => $post_type,
						'include'  => array_keys( $ld_cats ),
						'orderby'  => 'name',
						'order'    => 'ASC',
					)
				);

				$post_type_object = get_post_type_object( $atts['post_type'] );

				$tax_object = get_taxonomy( 'ld_' . $post_type_slug . '_category' );

				if ( ! empty( $get_ld_categories_args ) ) {
					$ld_categories = get_terms( $get_ld_categories_args );
				}
			}
		}
	} else {
		$atts[ $post_type_slug . '_categoryselector' ] = '';
	}
	// }

	$loop = new WP_Query( $filter );

	$level = ob_get_level();
	ob_start();

	if ( $include_outer_wrapper == 'true' ) {
		if ( ! empty( $categories ) ) {

			$categorydropdown  = '<div id="ld_categorydropdown">';
			$categorydropdown .= '<form method="get">
					<label for="ld_categorydropdown_select">' . esc_html__( 'Categories', 'learndash' ) . '</label>
					<select id="ld_categorydropdown_select" name="catid" onChange="jQuery(\'#ld_categorydropdown form\').submit()">';
			$categorydropdown .= '<option value="">' . esc_html__( 'Select category', 'learndash' ) . '</option>';

			foreach ( $categories as $category ) {

				if ( isset( $cats[ $category->term_id ] ) ) {
					$cat               = $cats[ $category->term_id ];
					$selected          = ( empty( $_GET['catid'] ) || $_GET['catid'] != $cat['id'] ) ? '' : 'selected="selected"';
					$categorydropdown .= "<option value='" . $cat['id'] . "' " . $selected . '>' . $cat['name'] . ' (' . $cat['count'] . ')</option>';
				}
			}

			$categorydropdown .= "</select><input type='submit' style='display:none'></form></div>";

			/**
			 * Filters the HTML output of category dropdown.
			 *
			 * @since 2.1.0
			 *
			 * @param  string  $categorydropdown HTML markup for category dropdown
			 * @param  array   $atts             Combined and filtered attribute list.
			 * @param  array   $filter            Query arguments.
			 */
			echo apply_filters( 'ld_categorydropdown', $categorydropdown, $atts, $filter );
		}

		if ( ! empty( $ld_categories ) ) {

			$ld_categorydropdown  = '<div id="ld_' . $post_type_slug . '_categorydropdown">';
			$ld_categorydropdown .= '<form method="get">
					<label for="ld_' . $post_type_slug . '_categorydropdown_select">' . $tax_object->labels->name . '</label>
					<select id="ld_' . $post_type_slug . '_categorydropdown_select" name="' . $post_type_slug . '_catid" onChange="jQuery(\'#ld_' . $post_type_slug . '_categorydropdown form\').submit()">';
			$ld_categorydropdown .= '<option value="">' . sprintf( esc_html_x( 'Select %s', 'placeholder: LD Category label', 'learndash' ), $tax_object->labels->name ) . '</option>';

			foreach ( $ld_categories as $ld_category ) {

				if ( isset( $ld_cats[ $ld_category->term_id ] ) ) {
					$ld_cat               = $ld_cats[ $ld_category->term_id ];
					$selected             = ( empty( $_GET[ $post_type_slug . '_catid' ] ) || $_GET[ $post_type_slug . '_catid' ] != $ld_category->term_id ) ? '' : 'selected="selected"';
					$ld_categorydropdown .= "<option value='" . $ld_category->term_id . "' " . $selected . '>' . $ld_cat['name'] . ' (' . $ld_cat['count'] . ')</option>';
				}
			}

			$ld_categorydropdown .= "</select><input type='submit' style='display:none'></form></div>";

			/**
			 * Filters the  HTML output of category dropdown for any post type slug.
			 *
			 * The dynamic part of the hook `$post_type_slug` refers to the slug of any post type.
			 *
			 * @since 2.1.0
			 *
			 * @param string $categorydropdown HTML markup for category dropdown.
			 * @param array  $atts             Combined and filtered attribute list.
			 * @param array  $filter            Query arguments.
			 */
			echo apply_filters( 'ld_' . $post_type_slug . '_categorydropdown', $ld_categorydropdown, $atts, $filter );
		}
	}

	$filter_json = htmlspecialchars( json_encode( $atts ) );
	$filter_md5  = md5( $filter_json );

	if ( $include_outer_wrapper == 'true' ) {
		?><div id="ld-course-list-content-<?php echo $filter_md5; ?>" class="ld-course-list-content" data-shortcode-atts="<?php echo $filter_json; ?>">
		<?php
	}
	?>
	<div class="ld-course-list-items row">
	<?php

	/**
	 * The following was added in 2.5.9 to allow better work with Gutenberg block rendering.
	 * Seems when we call the $loop->the_post() in the section below we are changing the
	 * global $post object. The problem is after this loop we call wp_reset_postdata() but
	 * the global $post is not being reset. This is really only an issue with the Gutenberg
	 * render blocks.
	 *
	 * @since 2.5.9
	 */
	// if ( ( defined( 'REST_REQUEST' ) ) && ( true === REST_REQUEST ) ) {
		$post_save = $post;
	// }

	while ( $loop->have_posts() ) {
		$loop->the_post();
		if ( empty( $atts['course_id'] ) ) {
			$course_id = $course_id = learndash_get_course_id( get_the_ID() );
		} else {
			$course_id = $atts['course_id'];
		}

		echo SFWD_LMS::get_template(
			'course_list_template',
			array(
				'shortcode_atts' => $atts,
				'course_id'      => $course_id,
			)
		);
		// }
	}
	?>
	</div>
	<?php

	if ( ( isset( $filter['posts_per_page'] ) ) && ( intval( $filter['posts_per_page'] ) > 0 ) ) {
		$course_list_pager = array();
		if ( isset( $loop->query_vars['paged'] ) ) {
			$course_list_pager['paged'] = $loop->query_vars['paged'];
		} else {
			$course_list_pager['paged'] = $filter['paged'];
		}

		$course_list_pager['total_items'] = intval( $loop->found_posts );
		$course_list_pager['total_pages'] = intval( $loop->max_num_pages );

		echo SFWD_LMS::get_template(
			'learndash_pager.php',
			array(
				'pager_results' => $course_list_pager,
				'pager_context' => 'course_list',
			)
		);
	}

	if ( $include_outer_wrapper == 'true' ) {
		?>
		</div>
		<?php
	}

	$output = learndash_ob_get_clean( $level );

	// if ( ( defined( 'REST_REQUEST' ) ) && ( true === REST_REQUEST ) ) {
		$post = $post_save;
		// $GLOBALS['post'] = $post_save;
		setup_postdata( $post_save );
	// } else {
		/*
		 Restore original Post Data */
	// wp_reset_postdata();
	// }

	$learndash_shortcode_used = true;

	/**
	 * Filters HTML output of category dropdown.
	 *
	 * @since 2.1.0
	 *
	 * @param string $output HTML output of category dropdown.
	 * @param array  $atts   Shortcode attributes.
	 * @param array  $filter  Arguments to retrieve posts.
	 */
	return apply_filters( 'ld_course_list', $output, $atts, $filter );
}

add_shortcode( 'ld_course_list', 'ld_course_list' );

/**
 * Handles the AJAX pagination for the course list shortcode.
 *
 * Fires on `ld_course_list_shortcode_pager` AJAX action.
 */
function ld_course_list_shortcode_pager() {
	$reply_data = array();

	if ( ( isset( $_POST['nonce'] ) ) && ( ! empty( $_POST['nonce'] ) ) && ( wp_verify_nonce( $_POST['nonce'], 'learndash-pager' ) ) ) {

		if ( ( isset( $_POST['paged'] ) ) && ( ! empty( $_POST['paged'] ) ) ) {
			$paged = intval( $_POST['paged'] );
		} else {
			$paged = 1;
		}

		if ( ( isset( $_POST['shortcode_atts'] ) ) && ( ! empty( $_POST['shortcode_atts'] ) ) ) {
			$shortcode_atts = $_POST['shortcode_atts'];
		} else {
			$shortcode_atts = array();
		}

		$shortcode_atts['include_outer_wrapper'] = 'false';
		$shortcode_atts['paged']                 = $paged;

		$reply_data['content'] = ld_course_list( $shortcode_atts );
	}

	echo json_encode( $reply_data );
	die();

}

add_action( 'wp_ajax_ld_course_list_shortcode_pager', 'ld_course_list_shortcode_pager' );
add_action( 'wp_ajax_nopriv_ld_course_list_shortcode_pager', 'ld_course_list_shortcode_pager' );


/**
 * Generates the output for course status shortcodes.
 *
 * @since 2.1.0
 *
 * @param array  $atts    An array of shortcode attributes.
 * @param string $content Shortcode content.
 * @param string $status  The status of the course.
 *
 * @return string The course status shortcode output.
 */
function learndash_course_status_content_shortcode( $atts, $content, $status ) {

	$atts['user_id']   = empty( $atts['user_id'] ) ? get_current_user_id() : intval( $atts['user_id'] );
	$atts['course_id'] = empty( $atts['course_id'] ) ? learndash_get_course_id() : learndash_get_course_id( intval( $atts['course_id'] ) );

	if ( ( ! empty( $atts['course_id'] ) ) && ( ! empty( $atts['user_id'] ) ) && ( $atts['user_id'] == get_current_user_id() ) ) {
		if ( sfwd_lms_has_access( $atts['course_id'], $atts['user_id'] ) ) {
			if ( learndash_course_status( $atts['course_id'], $atts['user_id'] ) == $status ) {
				return do_shortcode( $content );
			}
		}
	}
	return '';
}
