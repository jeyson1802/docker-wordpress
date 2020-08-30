<div class="wrap">

    <div class="bp-admin-card section-bp_zoom_settings_section">
        <h2><?php _e( 'Zoom <span>&mdash; requires license</span>', 'buddyboss-pro' ); ?></h2>
        <p>
			<?php
			printf(
				__( 'You need to activate a license key for <strong>BuddyBoss Platform Pro</strong> to unlock this feature. %s', 'buddyboss-pro' ),
				sprintf(
					'<a href="%s">%s</a>',
					bp_get_admin_url(
						add_query_arg(
							array(
								'page' => 'buddyboss-updater',
								'tab'  => 'buddyboss_theme',
							),
							'admin.php'
						)
					),
					__( 'Add License key', 'buddyboss-pro' )
				)
			)
			?>
        </p>
    </div>

</div>
