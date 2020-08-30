<?php
/**
 * LearnDash Transactions (sfwd-transactions) Posts Listing Class.
 *
 * @package LearnDash
 * @subpackage admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'Learndash_Admin_Posts_Listing' ) ) && ( ! class_exists( 'Learndash_Admin_Transactions_Listing' ) ) ) {
	/**
	 * Class for LearnDash Transactions Listing Pages.
	 */
	class Learndash_Admin_Transactions_Listing extends Learndash_Admin_Posts_Listing {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->post_type = 'sfwd-transactions';

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

			$this->early_selectors = array(
				'transaction_type' => array(
					'query_arg'      => 'transaction_type',
					'selected'       => ( isset( $_GET['transaction_type'] ) ) ? absint( $_GET['transaction_type'] ) : '',
					'field_name'     => 'transaction_type',
					'field_id'       => 'transaction_type',
					'show_all_value' => '',
					'show_all_label' => esc_html__( 'Show All Transactions Types', 'learndash' ),
					'options'        => array(
						'paypal' => esc_html__( 'PayPal', 'learndash' ),
						'stripe' => esc_html__( 'Stripe', 'learndash' ),
					),
					'lazy_load'      => false,
				),
			);

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
				'group_id'  => array(
					'query_args'     => array(
						'post_type' => learndash_get_post_type_slug( 'group' ),
					),
					'query_arg'      => 'group_id',
					'selected'       => ( isset( $_GET['group_id'] ) ) ? absint( $_GET['group_id'] ) : 0,
					'field_name'     => 'group_id',
					'field_id'       => 'group_id',
					'show_all_value' => '',
					'show_all_label' => sprintf(
						// translators: placeholder: Groups.
						esc_html_x( 'Show All %s', 'placeholder: Groups', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'groups' )
					),
					'lazy_load'      => true,
				),
			);

			$this->columns['transaction_type'] = esc_html__( 'Transaction Type', 'learndash' );

			$this->columns['course_group_id'] = sprintf(
				// translators: placeholder: Course, Group.
				esc_html_x( 'Enrolled %1$s / %2$s', 'placeholder: Course, Group', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'course' ),
				LearnDash_Custom_Label::get_label( 'group' )
			);

			$this->columns['user_id'] = esc_html__( 'User', 'learndash' );

			parent::on_load_edit();
		}


		/**
		 * Display Group columns.
		 *
		 * @param string  $column_name Column being displayed.
		 * @param integer $post_id ID of Transaction (post) being displayed.
		 */
		public function manage_column_rows( $column_name = '', $post_id = 0 ) {
			$column_name = esc_attr( $column_name );
			$post_id     = absint( $post_id );

			switch ( $column_name ) {
				case 'transaction_type':
					$ipn_track_id = get_post_meta( $post_id, 'ipn_track_id', true );
					$stripe_nonce = get_post_meta( $post_id, 'stripe_nonce', true );
					if ( ! empty( $ipn_track_id ) ) {
						$payment_amount   = get_post_meta( $post_id, 'mc_gross', true );
						if ( '' === $payment_amount ) {
							$payment_amount = '0.00';
						}
						$payment_amount   = number_format_i18n( $payment_amount, 2 );
						$payment_currency = get_post_meta( $post_id, 'mc_currency', true );
						echo sprintf(
							// translators: placeholder: PayPal Purchase price, Stripe Currency.
							esc_html_x( 'PayPal: %1$s %2$s', 'placeholder: PayPal Purchase price, Stripe Currency', 'learndash' ),
							$payment_amount,
							strtoupper( esc_attr( $payment_currency ) )
						);
					} elseif ( ! empty( $stripe_nonce ) ) {
						$payment_amount   = get_post_meta( $post_id, 'stripe_price', true );
						if ( '' === $payment_amount ) {
							$payment_amount = '0.00';
						}
						$payment_amount   = number_format_i18n( $payment_amount, 2 );
						$payment_currency = get_post_meta( $post_id, 'stripe_currency', true );
						echo sprintf(
							// translators: placeholder: Stripe Purchase price, Stripe Currency.
							esc_html_x( 'Stripe: %1$s %2$s', 'placeholder: Stripe Purchase price, Stripe Currency', 'learndash' ),
							$payment_amount,
							strtoupper( esc_attr( $payment_currency ) )
						);
					}
					break;

				case 'course_group_id':
					$course_id = get_post_meta( $post_id, 'course_id', true );
					absint( $course_id );
					if ( ! empty( $course_id ) ) {
						$row_actions = array();
						echo sprintf(
							// translators: placeholder: Course.
							esc_html_x( '%s : ', 'placeholder: Course', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'course' )
						);

						if ( current_user_can( 'edit_post', $course_id ) ) {
							$edit_url = get_edit_post_link( $course_id );
							echo '<a href="' . esc_url( $edit_url ) . '">' . get_the_title( $course_id ) . '</a>';
							$row_actions['edit'] = '<a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'edit', 'learndash' ) . '</a>';
						} else {
							echo get_the_title( $course_id );
						}
						$row_actions['filter_post'] = '<a href="' . esc_url( add_query_arg( 'course_id', $course_id, $this->get_clean_filter_url() ) ) . '">' . esc_html__( 'filter', 'learndash' ) . '</a>';
						echo learndash_list_table_row_actions( $row_actions );
					} else {
						$group_id = get_post_meta( $post_id, 'group_id', true );
						if ( ! empty( $group_id ) ) {
							$row_actions = array();
							$edit_url    = get_edit_post_link( $group_id );
							echo sprintf(
								// translators: placeholder: Group.
								esc_html_x( '%s : ', 'placeholder: Group', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'group' )
							);

							if ( current_user_can( 'edit_post', $group_id ) ) {
								echo '<a href="' . esc_url( $edit_url ) . '">' . get_the_title( $group_id ) . '</a>';
								$row_actions['edit'] = '<a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'edit', 'learndash' ) . '</a>';
							} else {
								echo get_the_title( $group_id );
							}
							$row_actions['filter_post'] = '<a href="' . esc_url( add_query_arg( 'group_id', $group_id, $this->get_clean_filter_url() ) ) . '">' . esc_html__( 'filter', 'learndash' ) . '</a>';
							echo learndash_list_table_row_actions( $row_actions );
						}
					}
					break;

				case 'user_id':
					$ipn_track_id = get_post_meta( $post_id, 'ipn_track_id', true );
					$stripe_nonce = get_post_meta( $post_id, 'stripe_nonce', true );
					if ( ! empty( $ipn_track_id ) ) {
						$email = get_post_meta( $post_id, 'payer_email', true );
						if ( ! empty( $email ) ) {
							$user = get_user_by( 'email', $email );
						}
					} elseif ( ! empty( $stripe_nonce ) ) {
						// echo esc_html__( 'Stripe', 'learndash' );
						$user_id = get_post_meta( $post_id, 'user_id', true );
						if ( ! empty( $user_id ) ) {
							$user = get_user_by( 'ID', $user_id );
						}
					}

					if ( ( $user ) && ( is_a( $user, 'WP_User' ) ) ) {
						$display_name = $user->display_name . ' (' . $user->user_email . ')';
						if ( current_user_can( 'edit_users' ) ) {
							$edit_url = get_edit_user_link( $user->ID );
							echo '<a href="' . esc_url( $edit_url ) . '">' . $display_name . '</a>';
							$row_actions['edit'] = '<a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'edit', 'learndash' ) . '</a>';
						} else {
							echo $display_name;
						}
						// $row_actions['filter_post'] = '<a href="' . esc_url( add_query_arg( 'user_id', $user->ID, $this->get_clean_filter_url() ) ) . '">' . esc_html__( 'filter', 'learndash' ) . '</a>';
						echo learndash_list_table_row_actions( $row_actions );
					}
					break;
			}
		}

		protected function get_clean_filter_url() {
			$url = admin_url( 'edit.php' );
			$url = add_query_arg( 'post_type', $this->post_type, $url );
			return $url;
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

				if ( ( isset( $_GET['transaction_type'] ) ) && ( ! empty( $_GET['transaction_type'] ) ) ) {
					if ( ! isset( $q_vars['meta_query'] ) ) {
						$q_vars['meta_query'] = array();
					}

					if ( 'paypal' === $_GET['transaction_type'] ) {
						$q_vars['meta_query'][] = array(
							'key'     => 'ipn_track_id',
							'compare' => 'EXISTS',
						);
					} elseif ( 'stripe' === $_GET['transaction_type'] ) {
						$q_vars['meta_query'][] = array(
							'key'     => 'stripe_nonce',
							'compare' => 'EXISTS',
						);
					}
				} elseif ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
					if ( ! isset( $q_vars['meta_query'] ) ) {
						$q_vars['meta_query'] = array();
					}

					$q_vars['meta_query'][] = array(
						'key'   => 'course_id',
						'value' => intval( $_GET['course_id'] ),
					);
				} elseif ( ( isset( $_GET['group_id'] ) ) && ( ! empty( $_GET['group_id'] ) ) ) {
					if ( ! isset( $q_vars['meta_query'] ) ) {
						$q_vars['meta_query'] = array();
					}

					$q_vars['meta_query'][] = array(
						'key'   => 'group_id',
						'value' => intval( $_GET['group_id'] ),
					);
				}
			}
		}

		// End of functions.
	}
}
new Learndash_Admin_Transactions_Listing();
