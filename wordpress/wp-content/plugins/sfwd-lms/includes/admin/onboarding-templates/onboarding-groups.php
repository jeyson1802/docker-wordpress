<?php
/**
 * Onboarding Template.
 *
 * Displayed when no entities were added to help the user.
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="ld-onboarding-screen">
	<div class="ld-onboarding-main">
		<span class="dashicons dashicons-welcome-add-page"></span>
		<h2>
		<?php
			echo sprintf(
				// translators: placeholder: Groups.
				esc_html_x( 'You don\'t have any %s yet', 'Placeholder: Groups', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'groups' )
			);
			?>
		</h2>
		<p>
		<?php
			echo sprintf(
				// translators: Groups, Group, Group.
				esc_html_x( 'Users can be placed into %1$s and assigned a %2$s Leader who can track the progress and performance of any user in the %3$s.', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'groups' ),
				LearnDash_Custom_Label::get_label( 'group' ),
				LearnDash_Custom_Label::get_label( 'groups' )
			);
			?>
		</p>
		<a href="<?php echo admin_url( 'post-new.php?post_type=' . learndash_get_post_type_slug( 'group' ) ); ?>" class="button button-secondary">
			<span class="dashicons dashicons-plus-alt"></span>
			<?php
				echo sprintf(
					// translators: placeholder: Group.
					esc_html_x( 'Add your first %s', 'placeholder: Group', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'group' )
				);
				?>
		</a>
	</div> <!-- .ld-onboarding-main -->

	<div class="ld-onboarding-more-help">
		<div class="ld-onboarding-row">
		<div class="ld-onboarding-col">
				<h3>
				<?php
					echo sprintf(
						// translators: placeholder: Group.
						esc_html_x( 'Creating a %s', 'placeholder: Group', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'group' )
					);
					?>
				</h3>
				<img src="<?php echo esc_url( LEARNDASH_LMS_PLUGIN_URL ); ?>assets/images/post-type-empty-state.jpg" alt="" />
			</div>
			<div class="ld-onboarding-col">
				<h3><?php esc_html_e( 'Related help and documentation', 'learndash' ); ?></h3>
				<ul>
					<li><a href="https://www.learndash.com/support/docs/users-groups/" target="_blank" rel="noopener noreferrer">
					<?php
					echo sprintf(
						// translators: placeholder: Groups.
						esc_html_x( 'Users & %s Documentation', 'placeholder: Group', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'groups' )
					);
					?>
					</a></li>
				</ul>
			</div>
		</div>

	</div> <!-- .ld-onboarding-more-help -->

</section> <!-- .ld-onboarding-screen -->
