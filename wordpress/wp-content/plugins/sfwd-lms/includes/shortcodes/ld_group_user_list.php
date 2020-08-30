<?php
/**
 * Shortcode for learndash_group_user_list
 *
 * @since 2.1.0
 *
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `learndash_group_user_list` shortcode output.
 *
 * @since 2.1.0
 *
 * @global wpdb    $wpdb WordPress database abstraction object.
 * @global boolean $learndash_shortcode_used
 *
 * @param int $group_id ID of the group to get list for.
 *
 * @return string|void Echos shortcode output or returns string if no users found.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function learndash_group_user_list( $group_id = 0 ) {
	global $wpdb;

	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	$group_id = absint( $group_id );
	if ( ! empty( $group_id ) ) {
		$current_user = wp_get_current_user();

		if ( ( ! learndash_is_admin_user( $current_user ) ) && ( ! learndash_is_group_leader_user( $current_user ) ) ) {
			return sprintf(
				// translators: placeholder: Group.
				esc_html_x( 'Please login as a %s Administrator', 'placeholder: Group', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'group' )
			);
		}

		$users = learndash_get_groups_users( $group_id );
		if ( ! empty( $users ) ) {
			?>
			<table cellspacing="0" class="wp-list-table widefat fixed groups_user_table">
			<thead>
				<tr>
					<th class="manage-column column-sno " id="sno" scope="col" ><?php esc_html_e( 'S. No.', 'learndash' ); ?></th>
					<th class="manage-column column-name " id="group" scope="col"><?php esc_html_e( 'Name', 'learndash' ); ?></th>
					<th class="manage-column column-name " id="group" scope="col"><?php esc_html_e( 'Username', 'learndash' ); ?></th>
					<th class="manage-column column-name " id="group" scope="col"><?php esc_html_e( 'Email', 'learndash' ); ?></th>
					<th class="manage-column column-action" id="action" scope="col"><?php esc_html_e( 'Action', 'learndash' ); ?></span></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th class="manage-column column-sno " id="sno" scope="col" ><?php esc_html_e( 'S. No.', 'learndash' ); ?></th>
					<th class="manage-column column-name " id="group" scope="col"><?php esc_html_e( 'Name', 'learndash' ); ?></th>
					<th class="manage-column column-name " id="group" scope="col"><?php esc_html_e( 'Username', 'learndash' ); ?></th>
					<th class="manage-column column-name " id="group" scope="col"><?php esc_html_e( 'Email', 'learndash' ); ?></th>
					<th class="manage-column column-action" id="action" scope="col"><?php esc_html_e( 'Action', 'learndash' ); ?></span></th>
				</tr>
			</tfoot>
			<tbody>
				<?php
				$sn = 1;
				foreach ( $users as $user ) {
					$name = isset( $user->display_name ) ? $user->display_name : $user->user_nicename;
					?>
					<tr>
						<td><?php echo $sn++; ?></td>
						<td><?php echo $name; ?></td>
						<td><?php echo $user->user_login; ?></td>
						<td><?php echo $user->user_email; ?></td>
						<td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=sfwd-courses&page=group_admin_page&group_id=' . $group_id . '&user_id=' . $user->ID ) ); ?>"><?php esc_html_e( 'Report', 'learndash' ); ?></a></td>
					</tr>
					<?php
				}
				?>
			</tbody>
			</table>
			<?php
		} else {
			return esc_html__( 'No users.', 'learndash' );
		}
	}
}
// FPM: This is registered but is anyone using it? The related function takes a
// group_id int. For a proper shortcode handler is should take an array where
// group_id is passed as in [learndash_group_user_list group_id="123"]
add_shortcode( 'learndash_group_user_list', 'learndash_group_user_list' );
