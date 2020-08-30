<?php
/**
 * BuddyBoss Groups Zoom.
 *
 * @package BuddyBoss\Groups\Zoom
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BP_Group_Zoom
 */
class BP_Zoom_Group {
	/**
	 * Your __construct() method will contain configuration options for
	 * your extension.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		if ( ! bbp_pro_is_license_valid() || ! bp_is_active( 'groups' ) || ! bp_zoom_is_zoom_enabled() || ! bp_zoom_is_zoom_groups_enabled() ) {
			return false;
		}

		$this->setup_filters();
		$this->setup_actions();
	}

	/**
	 * Setup the group zoom class filters
	 *
	 * @since 1.0.0
	 */
	private function setup_filters() {
	    add_filter( 'bp_nouveau_customizer_group_nav_items', array( $this, 'customizer_group_nav_items' ), 10 ,2 );
	}

	/**
	 * setup actions.
	 *
	 * @since 1.0.0
	 */
	public function setup_actions() {
		add_action( 'bp_setup_nav', array( $this, 'setup_nav' ), 100 );
		add_filter( 'document_title_parts', array( $this, 'bp_nouveau_group_zoom_set_page_title' ) );
		add_filter( 'pre_get_document_title', array( $this, 'bp_nouveau_group_zoom_set_title_tag' ), 999, 1 );

		add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Adds a zoom metabox to the new BuddyBoss Group Admin UI
		add_action( 'bp_groups_admin_meta_boxes', array( $this, 'group_admin_ui_edit_screen' ) );

		// Saves the zoom options if they come from the BuddyBoss Group Admin UI
		add_action( 'bp_group_admin_edit_after', array( $this, 'edit_screen_save' ) );

		add_action( 'bp_zoom_meeting_add', array( $this, 'create_activity' ), 10, 2 );
		add_action( 'bp_zoom_meeting_add', array( $this, 'groups_notification_meeting_created' ), 20, 2 );
		add_action( 'bp_groups_zoom_meeting_created_notification', array(
			$this,
			'groups_format_create_meeting_notification'
		), 10, 5 );
		add_action( 'bp_get_request', array( $this, 'zoom_meeting_mark_notifications' ), 1 );
		add_action( 'bp_zoom_meeting_deleted_meetings', array( $this, 'delete_meeting_notifications' ) );

		add_action( 'bp_activity_entry_content', array( $this, 'embed_meeting' ), 10 );
		// Register the activity stream actions
		add_action( 'bp_register_activity_actions', array( $this, 'register_activity_actions' ) );

		add_action( 'bp_init', array( $this, 'zoom_webhook' ), 10 );

		add_action( 'groups_delete_group', array( $this, 'delete_group_delete_all_meetings' ), 10 );
	}

	/**
	 * Setup navigation for group zoom tabs.
	 *
	 * @since 1.0.0
	 */
	public function setup_nav() {
		// return if no group.
		if ( ! bp_is_group() ) {
			return;
		}

		$current_group = groups_get_current_group();
		$group_link    = bp_get_group_permalink( $current_group );
		$sub_nav       = array();

		// if current group has zoom enable then return.
		if ( bp_zoom_is_group_setup( $current_group->id ) ) {
			$sub_nav[] = array(
				'name'            => __( 'Zoom', 'buddyboss-pro' ),
				'slug'            => 'zoom',
				'parent_url'      => $group_link,
				'parent_slug'     => $current_group->slug,
				'screen_function' => array( $this, 'zoom_page' ),
				'item_css_id'     => 'zoom',
				'position'        => 100,
				'user_has_access' => $current_group->user_has_access,
				'no_access_url'   => $group_link,
			);

			$default_args = array(
				'parent_url'      => trailingslashit( $group_link . 'zoom' ),
				'parent_slug'     => $current_group->slug . '_zoom',
				'screen_function' => array( $this, 'zoom_page' ),
				'user_has_access' => $current_group->user_has_access,
				'no_access_url'   => $group_link,
			);

			$sub_nav[] = array_merge(
				array(
					'name'     => __( 'Upcoming Meetings', 'buddyboss-pro' ),
					'slug'     => 'meetings',
					'position' => 10,
				),
				$default_args
			);

			$sub_nav[] = array_merge(
				array(
					'name'     => __( 'Past Meetings', 'buddyboss-pro' ),
					'slug'     => 'past-meetings',
					'position' => 20,
				),
				$default_args
			);

			if ( bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), $current_group->id ) ) {
				$sub_nav[] = array_merge(
					array(
						'name'     => __( 'Create Meeting', 'buddyboss-pro' ),
						'slug'     => 'create-meeting',
						'position' => 30,
					),
					$default_args
				);
			}
		}

		// If the user is a group admin, then show the group admin nav item.
		if ( bp_is_item_admin() ) {
			$admin_link = trailingslashit( $group_link . 'admin' );

			$sub_nav[] = array(
				'name'              => __( 'Zoom', 'buddyboss-pro' ),
				'slug'              => 'zoom',
				'position'          => 100,
				'parent_url'        => $admin_link,
				'parent_slug'       => $current_group->slug . '_manage',
				'screen_function'   => 'groups_screen_group_admin',
				'user_has_access'   => bp_is_item_admin(),
				'show_in_admin_bar' => true,
			);
		}

		foreach ( $sub_nav as $nav ) {
			bp_core_new_subnav_item( $nav, 'groups' );
		}

		// save edit screen options.
		if ( bp_is_groups_component() && bp_is_current_action( 'admin' ) && bp_is_action_variable( 'zoom', 0 ) ) {
			$this->edit_screen_save( $current_group->id );

			// Load zoom admin page.
			add_action( 'bp_screens', array( $this, 'zoom_admin_page' ) );
		}
	}

	/**
	 * Zoom page callback
	 *
	 * @since 1.0.0
	 */
	public function zoom_page() {
	    // when sync completes.
	    if ( isset( $_REQUEST['sync_meeting_done'] ) ) {
		    bp_core_add_message( __( 'Group meetings were successfully synced with Zoom.', 'buddyboss-pro' ), 'success' );
        }

		// if single meeting page and meeting does not exists return 404.
		if ( bp_zoom_is_single_meeting() && false === bp_zoom_get_current_meeting() ) {
			bp_do_404();

			return;
		}

        $group_id = bp_is_group() ? bp_get_current_group_id() : false;

        // if edit meeting page and meeting does not exists return 404.
        if (
            ( bp_zoom_is_edit_meeting() && false === bp_zoom_get_edit_meeting() )
            || ( ! bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), $group_id ) && bp_zoom_is_create_meeting() )
        ) {
            bp_do_404();
            return;
        }

		if ( ! bp_zoom_is_create_meeting() ) {
			$param = array(
				'per_page' => 1
			);

			if ( 'past-meetings' === bp_action_variable( 0 ) ) {
				$param['from']  = wp_date( 'Y-m-d H:i:s', null, new DateTimeZone( 'UTC' ) );
				$param['since'] = false;
				$param['sort']  = 'DESC';
			}

			if ( bp_zoom_is_groups_zoom() && ! bp_zoom_is_single_meeting() && bp_has_zoom_meetings( $param ) ) {
				while ( bp_zoom_meeting() ) {
					bp_the_zoom_meeting();

					$group_link   = bp_get_group_permalink( groups_get_group( bp_get_zoom_meeting_group_id() ) );
					$redirect_url = trailingslashit( $group_link . 'zoom/meetings/' . bp_get_zoom_meeting_id() );
					wp_safe_redirect( $redirect_url );
					exit;
				}
			}
		}

		add_action( 'bp_template_content', array( $this, 'zoom_page_content' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'groups/single/home' ) );
	}

	/**
	 * Zoom admin page callback
	 *
	 * @since 1.0.0
	 */
	public function zoom_admin_page() {
		if ( 'zoom' !== bp_get_group_current_admin_tab() ) {
			return false;
		}

		if ( ! bp_is_item_admin() && ! bp_current_user_can( 'bp_moderate' ) ) {
			return false;
		}
		add_action( 'groups_custom_edit_steps', array( $this, 'edit_screen' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'groups/single/home' ) );
	}

	/**
	 * Display zoom page content.
	 *
	 * @since 1.0.0
	 */
	function zoom_page_content() {
		do_action( 'template_notices' );
		bp_get_template_part( 'groups/single/zoom' );
	}

	/**
	 * Enqueue scripts for zoom meeting pages.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		if ( ! bp_zoom_is_groups_zoom() ) {
			return;
		}
		wp_enqueue_style( 'jquery-datetimepicker' );
		wp_enqueue_script( 'jquery-datetimepicker' );
		wp_enqueue_script( 'bp-select2' );
		if ( wp_script_is( 'bp-select2-local', 'registered' ) ) {
			wp_enqueue_script( 'bp-select2-local' );
		}
		wp_enqueue_style( 'bp-select2' );
	}

	/**
	 * Adds a zoom metabox to BuddyBoss Group Admin UI
	 *
	 * @since 1.0.0
	 *
	 * @uses add_meta_box
	 */
	public function group_admin_ui_edit_screen() {
		add_meta_box(
			'bp_zoom_group_admin_ui_meta_box',
			__( 'Zoom Conference', 'buddyboss-pro' ),
			array( $this, 'group_admin_ui_display_metabox' ),
			get_current_screen()->id,
			'advanced',
			'high'
		);
	}

	/**
	 * Displays the zoom metabox in BuddyBoss Group Admin UI
	 *
	 * @param object $item (group object)
	 *
	 * @since 1.0.0
	 *
	 */
	public function group_admin_ui_display_metabox( $item ) {
		$this->edit_screen( $item );
	}

	/**
	 * Show zoom option form when editing a group
	 *
	 * @param object $group (the group to edit if in Group Admin UI)
	 *
	 * @since 1.0.0
	 * @uses is_admin() To check if we're in the Group Admin UI
	 */
	public function edit_screen( $group = false ) {
		$group_id = empty( $group->id ) ? bp_get_new_group_id() : $group->id;

		if ( empty( $group->id ) ) {
			$group_id = bp_get_new_group_id();
		}

		if ( empty( $group_id ) ) {
			$group_id = bp_get_group_id();
		}

		if ( empty( $group_id ) ) {
			$group_id = $group->id;
		}

		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		wp_enqueue_script( 'bp-zoom-meeting-common', bp_zoom_integration_url( '/assets/js/bp-zoom-meeting-common' . $min . '.js' ), array( 'jquery' ), bb_platform_pro()->version, true );
		wp_localize_script(
			'bp-zoom-meeting-common',
			'bpZoomMeetingCommonVars',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);

		// Should box be checked already?
		$checked       = bp_zoom_group_is_zoom_enabled( $group_id );
		$api_key       = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-key', true );
		$api_secret    = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-secret', true );
		$api_email     = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-email', true );
		$webhook_token = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-webhook-token', true );
		?>

		<div class="bb-group-zoom-settings-container">

			<h4 class="bb-section-title"><?php esc_html_e( 'Group Zoom Settings', 'buddyboss-pro' ); ?></h4>

			<fieldset>
				<legend class="screen-reader-text"><?php esc_html_e( 'Group Zoom Settings', 'buddyboss-pro' ); ?></legend>
				<?php if ( ! is_admin() ) : ?>
                    <p class="bb-section-info"><?php esc_html_e( 'Connect this group to a Zoom account, to allow meetings to be scheduled from this group and synced with Zoom. Once enabled, enter your Zoom API Credentials below, using the "Setup Wizard" button to guide you step by step.', 'buddyboss-pro' ); ?></p>
                <?php else: ?>
                    <p class="bb-section-info"><?php esc_html_e( 'Connect this group to a Zoom account, to allow meetings to be scheduled from this group and synced with Zoom. Once enabled, enter your Zoom API Credentials below.', 'buddyboss-pro' ); ?></p>
                <?php endif; ?>

				<div class="field-group">
					<p class="checkbox bp-checkbox-wrap bp-group-option-enable">
						<input type="checkbox" name="bp-edit-group-zoom" id="bp-edit-group-zoom" class="bs-styled-checkbox" value="1"<?php checked( $checked ); ?> />
						<label for="bp-edit-group-zoom"><span><?php esc_html_e( 'Yes, I want to connect this group to Zoom.', 'buddyboss-pro' ); ?></span></label>
					</p>
				</div>

			</fieldset>

			<div id="bp-group-zoom-settings-additional" class="group-settings-selections <?php echo ! $checked ? 'bp-hide' : ''; ?>">

				<hr class="bb-sep-line" />
				<h4 class="bb-section-title"><?php esc_html_e( 'Group Permissions', 'buddyboss-pro' ); ?></h4>

				<fieldset class="radio group-media">
					<legend class="screen-reader-text"><?php esc_html_e( 'Group Permissions', 'buddyboss-pro' ); ?></legend>
					<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'Which members of this group are allowed to create, edit and delete Zoom meetings? The "Zoom Account Email" (below) will be assigned as the default host for every meeting in this group, regardless of who created the meeting.', 'buddyboss-pro' ); ?></p>

					<div class="bp-radio-wrap">
						<input type="radio" name="bp-group-zoom-manager" id="group-zoom-manager-members" class="bs-styled-radio" value="members"<?php bp_zoom_group_show_manager_setting( 'members', $group ); ?> />
						<label for="group-zoom-manager-members"><?php esc_html_e( 'All group members', 'buddyboss-pro' ); ?></label>
					</div>

					<div class="bp-radio-wrap">
						<input type="radio" name="bp-group-zoom-manager" id="group-zoom-manager-mods" class="bs-styled-radio" value="mods"<?php bp_zoom_group_show_manager_setting( 'mods', $group ); ?> />
						<label for="group-zoom-manager-mods"><?php esc_html_e( 'Organizers and Moderators only', 'buddyboss-pro' ); ?></label>
					</div>

					<div class="bp-radio-wrap">
						<input type="radio" name="bp-group-zoom-manager" id="group-zoom-manager-admins" class="bs-styled-radio" value="admins"<?php bp_zoom_group_show_manager_setting( 'admins', $group ); ?> />
						<label for="group-zoom-manager-admins"><?php esc_html_e( 'Organizers only', 'buddyboss-pro' ); ?></label>
					</div>
				</fieldset>

				<hr class="bb-sep-line" />
			</div>

			<div id="bp-group-zoom-settings" class="bp-group-zoom-settings <?php echo ! $checked ? 'bp-hide' : ''; ?>">

				<h4 class="bb-section-title"><?php esc_html_e( 'Zoom API Credentials', 'buddyboss-pro' ); ?></h4>
				<legend class="screen-reader-text"><?php esc_html_e( 'Zoom API Credentials', 'buddyboss-pro' ); ?></legend>
				<div class="bb-field-wrap">
					<label for="bp-group-zoom-api-key" class="group-setting-label"><?php esc_html_e( 'API Key', 'buddyboss-pro' ); ?>*</label>

					<div class="bp-input-wrap">
						<input <?php echo ! empty( $checked ) ? 'required' : ''; ?> type="text" name="bp-group-zoom-api-key" id="bp-group-zoom-api-key" class="zoom-group-instructions-main-input" value="<?php echo $api_key; ?>"/>
					</div>
				</div>

				<div class="bb-field-wrap">
					<label for="bp-group-zoom-api-secret" class="group-setting-label"><?php esc_html_e( 'API Secret', 'buddyboss-pro' ); ?>*</label>

					<div class="bp-input-wrap">
						<input <?php echo ! empty( $checked ) ? 'required' : ''; ?> type="text" name="bp-group-zoom-api-secret" id="bp-group-zoom-api-secret" class="zoom-group-instructions-main-input" value="<?php echo $api_secret; ?>"/>
					</div>
				</div>

				<div class="bb-field-wrap">
					<label for="bp-group-zoom-api-email" class="group-setting-label"><?php esc_html_e( 'Zoom Account Email', 'buddyboss-pro' ); ?>*</label>

					<div class="bp-input-wrap">
						<input <?php echo ! empty( $checked ) ? 'required' : ''; ?> type="text" name="bp-group-zoom-api-email" id="bp-group-zoom-api-email" class="zoom-group-instructions-main-input" value="<?php echo $api_email; ?>"/>
					</div>
				</div>

                <div class="bb-field-wrap">
					<label for="bp-group-zoom-api-webhook-token" class="group-setting-label"><?php esc_html_e( 'Verification Token', 'buddyboss-pro' ); ?></label>

					<div class="bp-input-wrap">
						<input type="text" name="bp-group-zoom-api-webhook-token" id="bp-group-zoom-api-webhook-token" class="zoom-group-instructions-main-input" value="<?php echo $webhook_token; ?>"/>
                        <div class="bb-description-info">
							<span class="bb-url-text"><?php echo esc_url( bp_get_groups_directory_permalink() . '?zoom_webhook=1&group_id=' . $group_id ); ?></span>
							<a href="#" id="copy-webhook-link" class="copy-webhook-link" data-balloon-pos="down" data-balloon="<?php esc_html_e( 'Copy', 'buddyboss-pro' ); ?>" data-copied="<?php esc_html_e( 'Copied', 'buddyboss-pro' ); ?>" data-webhook-link="<?php echo esc_url( bp_get_groups_directory_permalink() . '?zoom_webhook=1&group_id=' . $group_id ); ?>">
								<span class="bb-icon-copy"></span>
							</a>
						</div>
					</div>
				</div>
				<hr class="bb-sep-line" />
			</div>

			<div class="bp-zoom-group-button-wrap">
				<?php if ( ! empty( $checked ) && ! empty( $api_key ) && ! empty( $api_secret ) && ! empty( $api_email ) ) { ?>
					<a class="bp-zoom-group-check-connection" href="#" id="bp-zoom-group-check-connection">
						<i class="bb-icon-radio"></i>
						<span><?php _e( 'Check Connection', 'buddyboss-pro' ); ?></span>
					</a>
				<?php } ?>

				<?php if ( ! is_admin() ) : ?>
                    <a href="#bp-zoom-group-show-instructions-popup-<?php echo $group_id; ?>" id="bp-zoom-group-show-instructions" class="button outline show-zoom-instructions <?php if ( empty( $checked ) ) { echo 'bp-hide'; }?> ">
                        <?php _e( 'Setup Wizard', 'buddyboss-pro' ); ?>
                    </a>
                    <div id="bp-zoom-group-show-instructions-popup-<?php echo $group_id; ?>" class="bzm-white-popup bp-zoom-group-show-instructions mfp-hide">
                        <header class="bb-zm-model-header"><?php _e( 'Connect a Zoom Account', 'buddyboss-pro' ); ?></header>

                        <div class="bp-step-nav-main">

                            <div class="bp-step-nav">
                                <ul>
                                    <li class="selected"><a href="#step-1"><?php _e('Zoom Login', 'buddyboss-pro' ); ?></a></li>
                                    <li><a href="#step-2"><?php _e('Create App', 'buddyboss-pro' ); ?></a></li>
                                    <li><a href="#step-3"><?php _e('App Credentials', 'buddyboss-pro' ); ?></a></li>
                                    <li><a href="#step-4"><?php _e('Verification Token', 'buddyboss-pro' ); ?></a></li>
                                    <li><a href="#step-5"><?php _e('Finish', 'buddyboss-pro' ); ?></a></li>
                                </ul>
                            </div> <!-- .bp-step-nav -->

                            <div class="bp-step-blocks">

                                <div class="bp-step-block selected" id="step-1">
                                    <div id="zoom-instruction-container">
                                        <p><?php _e('To use Zoom, we will need you to create an "app" in your Zoom account and connect it to this group so we can sync meeting data with Zoom. This should only take a few minutes if you already have a Zoom account. Note that cloud recordings and alternate hosts will only work if you have a "Pro" or "Business" Zoom account.', 'buddyboss-pro' ); ?></p>
                                        <p><?php _e('Start by going to the <a href="https://marketplace.zoom.us/" target="_blank">Zoom App Marketplace</a> and clicking the "Sign In" link in the titlebar. You can sign in using your existing Zoom credentials. If you do not yet have a Zoom account, just click the "Sign Up" link in the titlebar. Once you have successfully signed into Zoom App Marketplace you can move to the next step.', 'buddyboss-pro' ); ?></p>
                                        <img src="<?php echo bp_zoom_integration_url( '/assets/images/wizard-sign_in.png' ); ?>" />
                                    </div>
                                </div>

                                <div class="bp-step-block" id="step-2">
                                    <div id="zoom-instruction-container">
                                        <p><?php _e('Once you are signed into Zoom App Marketplace, you need to <a href="https://marketplace.zoom.us/develop/create" target="_blank">build an app</a>. You can always find the Build App link by going to "Develop" &#8594; "Build App" from the titlebar.', 'buddyboss-pro' ); ?></p>
                                        <img src="<?php echo bp_zoom_integration_url( '/assets/images/wizard-build_app.png' ); ?>" />
                                        <p><?php _e('On the next page, select the first option "JWT" as the app type and click the "Create" button. If you see the message "Your account already has JWT credentials" you can use the existing app. In that case, click the "View here" link to modify the existing JWT app.', 'buddyboss-pro' ); ?></p>
                                        <img src="<?php echo bp_zoom_integration_url( '/assets/images/wizard-app_type.png' ); ?>" />
                                        <p><?php _e('After clicking "Create App" you will get a popup asking you to enter an App Name. Enter any name that will remind you the app is being used for this website. Then click the "Create" button.', 'buddyboss-pro' ); ?></p>
                                        <img src="<?php echo bp_zoom_integration_url( '/assets/images/wizard-app_name.png' ); ?>" />
                                        <p><?php _e( 'After clicking "Create" you will be redirected to a form asking for some basic personal information. Fill out all required fields and click the "Continue" button. Once you see "App Credentials" move to the next step.', 'buddyboss-pro' ); ?></p>
                                    </div>
                                </div>

                                <div class="bp-step-block" id="step-3">
                                    <div id="zoom-instruction-container">
                                        <p><?php _e( 'Once you get to the "App Credentials" page, copy the <strong>API Key</strong> and <strong>API Secret</strong> and paste them into the fields in the form below. Then you will need to decide which of the Zoom users in your account should be the default host for all meetings in this group. Enter their email address into the <strong>Zoom Account Email</strong> field below. The email must exist as a Host in your Zoom account.', 'buddyboss-pro' ); ?></p>
                                        <div class="bb-group-zoom-settings-container">
                                            <div class="bb-field-wrap">
                                                <label for="bp-group-zoom-api-key-popup" class="group-setting-label"><?php _e( 'API Key', 'buddyboss-pro' ); ?>*</label>
                                                <div class="bp-input-wrap">
                                                    <input type="text" name="bp-group-zoom-api-key-popup" class="zoom-group-instructions-cloned-input" value="<?php echo $api_key; ?>" />
                                                </div>
                                            </div>

                                            <div class="bb-field-wrap">
                                                <label for="bp-group-zoom-api-secret-popup" class="group-setting-label"><?php _e( 'API Secret', 'buddyboss-pro' ); ?>*</label>
                                                <div class="bp-input-wrap">
                                                    <input type="text" name="bp-group-zoom-api-secret-popup" class="zoom-group-instructions-cloned-input" value="<?php echo $api_secret; ?>" />
                                                </div>
                                            </div>

                                            <div class="bb-field-wrap">
                                                <label for="bp-group-zoom-api-email-popup" class="group-setting-label"><?php _e( 'Zoom Account Email', 'buddyboss-pro' ); ?>*</label>
                                                <div class="bp-input-wrap">
                                                    <input type="text" name="bp-group-zoom-api-email-popup" class="zoom-group-instructions-cloned-input" value="<?php echo $api_email; ?>" />
                                                </div>
                                            </div>

                                        </div><!-- .bb-group-zoom-settings-container -->

                                    </div>
                                </div>

                                <div class="bp-step-block" id="step-4">
                                    <div id="zoom-instruction-container">
                                        <p><?php _e( 'Once you have entered the API Key, API Secret, and Zoom Account Email, continue to the "Feature" tab. Enable "Event Subscriptions" and then click "Add new event subscription". This step is necessary to allow meeting updates from Zoom to automatically sync back into your group. Note that within the group on this site, you can also click the "Sync" button at any time to force a manual sync.', 'buddyboss-pro' ); ?></p>
                                        <img src="<?php echo bp_zoom_integration_url( '/assets/images/wizard-event_subscription.png' ); ?>" />
                                        <p><?php _e( 'For "Subscription Name" you can again enter any name you want. Click the 
                                                <span class="bb-icon-copy"></span> Copy Link button below to copy a special link, and then paste that link back into Zoom in the field titled "Event notification endpoint URL".', 'buddyboss-pro' ); ?></p>
                                        <p><a href="#" class="copy-webhook-link button small outline" data-text="<?php _e( 'Copy Link', 'buddyboss-pro' ); ?>" data-copied="<?php _e( 'Copied', 'buddyboss-pro' ); ?>" data-webhook-link="<?php echo esc_url( bp_get_groups_directory_permalink() . '?zoom_webhook=1&group_id=' . $group_id ); ?>">
                                                <span class="bb-icon-copy"></span> <?php _e( 'Copy Link', 'buddyboss-pro' ); ?>
                                            </a></p>
                                        <img src="<?php echo bp_zoom_integration_url( '/assets/images/wizard-event_notification.png' ); ?>" />
                                        <p><?php _e( 'Next, click the "Add events" button. In the popup, make sure to check the following options: <strong>Start Meeting, End Meeting, Meeting has been updated, Meeting has been deleted, All Recordings have completed.</strong>', 'buddyboss-pro' ); ?></p>
                                        <img src="<?php echo bp_zoom_integration_url( '/assets/images/wizard-events_1.png' ); ?>" />
                                        <img src="<?php echo bp_zoom_integration_url( '/assets/images/wizard-events_2.png' ); ?>" />
                                        <p><?php _e( 'Click "Done" to close the popup. In the "Event Subscriptions" box, click "Save".', 'buddyboss-pro' ); ?></p>
                                        <img src="<?php echo bp_zoom_integration_url( '/assets/images/wizard-event_save.png' ); ?>" />
                                        <p><?php _e( 'You should now see a "Verification Token" created at the top of the page. Click "Copy" and then paste the token into the Verification Token field at the bottom of this form. You\'re almost done!', 'buddyboss-pro' ); ?></p>
                                        <img src="<?php echo bp_zoom_integration_url( '/assets/images/wizard-token.png' ); ?>" />

                                        <div class="bb-group-zoom-settings-container">
                                            <div class="bb-field-wrap">
                                                <label for="bp-group-zoom-api-webhook-token-popup" class="group-setting-label"><?php _e( 'Verification Token', 'buddyboss-pro' ); ?></label>
                                                <div class="bp-input-wrap">
                                                    <input type="text" name="bp-group-zoom-api-webhook-token-popup" class="zoom-group-instructions-cloned-input" value="<?php echo $webhook_token; ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bp-step-block" id="step-5">
                                    <div id="zoom-instruction-container">
                                        <p><?php _e( 'Now you can click "Continue" back at Zoom. You should see a message that "Your app is activated on the account". At this point we are done with the Zoom website.', 'buddyboss-pro' ); ?></p>
                                        <img src="<?php echo bp_zoom_integration_url( '/assets/images/wizard-activated.png' ); ?>" />
                                        <p><?php _e( 'Make sure to click the "Save" button on this tab to save the data you entered. Then click the "Check Connection" button on the page to confirm the API was successfully connected. If everything worked you should see a new "Zoom" tab in your group, where you can start scheduling meetings! ', 'buddyboss-pro' ); ?></p>
                                    </div>
                                </div>

                            </div> <!-- .bp-step-blocks -->

                            <div class="bp-step-actions">
                                <span class="bp-step-prev button small outline" style="display: none;"><i class="bb-icon-arrow-left"></i> <?php _e('Previous', 'buddyboss-pro' ); ?></span>
                                <span class="bp-step-next button small outline"><i class="bb-icon-arrow-right"></i> <?php _e('Next', 'buddyboss-pro' ); ?></span>

                                <span class="save-settings button small"><?php _e('Save', 'buddyboss-pro' ); ?></span>

                            </div> <!-- .bp-step-actions -->

                        </div> <!-- .bp-step-nav-main -->

                    </div>

                    <button type="submit" class="bb-save-settings"><?php esc_attr_e( 'Save Settings', 'buddyboss-pro' ); ?></button>
				<?php else: ?>
                    <p>
                        <a class="button" href="
		                <?php
						echo bp_get_admin_url(
							add_query_arg(
								array(
									'page'    => 'bp-help',
									'article' => 88334,
								),
								'admin.php'
							)
						);
						?>"><?php _e( 'View Tutorial', 'buddyboss-pro' ); ?></a>
                    </p>
				<?php endif; ?>
			</div>

			<?php

			// Verify intent
			if ( is_admin() ) {
				?><input type="hidden" id="bp-zoom-group-id" value="<?php echo $group_id; ?>" /><?php
				wp_nonce_field( 'groups_edit_save_zoom', 'zoom_group_admin_ui' );
			} else {
				wp_nonce_field( 'groups_edit_save_zoom' );
			} ?>
		</div>
		<?php
	}

	/**
	 * Save the Group Zoom data on edit
	 *
	 * @param int $group_id (to handle Group Admin UI hook bp_group_admin_edit_after )
	 *
	 * @since 1.0.0
	 */
	public function edit_screen_save( $group_id = 0 ) {

		// Bail if not a POST action
		if ( ! bp_is_post_request() ) {
			return;
		}

		// Admin Nonce check
		if ( is_admin() ) {
			check_admin_referer( 'groups_edit_save_zoom', 'zoom_group_admin_ui' );

			// Theme-side Nonce check
		} elseif ( empty( $_REQUEST['_wpnonce'] ) || ( ! empty( $_REQUEST['_wpnonce'] ) && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'groups_edit_save_zoom' ) ) ) {
			return;
		}

		global $wpdb, $bp;


		$edit_zoom     = ! empty( $_POST['bp-edit-group-zoom'] ) ? true : false;
		$manager       = ! empty( $_POST['bp-group-zoom-manager'] ) ? $_POST['bp-group-zoom-manager'] : bp_zoom_group_get_manager( $group_id );
		$api_key       = ! empty( $_POST['bp-group-zoom-api-key'] ) ? $_POST['bp-group-zoom-api-key'] : '';
		$api_secret    = ! empty( $_POST['bp-group-zoom-api-secret'] ) ? $_POST['bp-group-zoom-api-secret'] : '';
		$api_email     = ! empty( $_POST['bp-group-zoom-api-email'] ) ? $_POST['bp-group-zoom-api-email'] : '';
		$webhook_token = ! empty( $_POST['bp-group-zoom-api-webhook-token'] ) ? $_POST['bp-group-zoom-api-webhook-token'] : '';
		$group_id      = ! empty( $group_id ) ? $group_id : bp_get_current_group_id();

		groups_update_groupmeta( $group_id, 'bp-group-zoom', $edit_zoom );
		groups_update_groupmeta( $group_id, 'bp-group-zoom-manager', $manager );

		$api_email = filter_var( $api_email, FILTER_VALIDATE_EMAIL );

		$old_api_email = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-email', true );

		if ( $edit_zoom ) {
			if ( $api_email ) {
				bp_zoom_conference()->zoom_api_key    = $api_key;
				bp_zoom_conference()->zoom_api_secret = $api_secret;

				$user_info = bp_zoom_conference()->get_user_info( $api_email );

				if ( 200 === $user_info['code'] && ! empty( $user_info['response'] ) ) {
                    groups_update_groupmeta( $group_id, 'bp-group-zoom-api-email', $api_email );
                    groups_update_groupmeta( $group_id, 'bp-group-zoom-api-key', $api_key );
                    groups_update_groupmeta( $group_id, 'bp-group-zoom-api-secret', $api_secret );
					groups_update_groupmeta( $group_id, 'bp-group-zoom-api-webhook-token', $webhook_token );
					groups_update_groupmeta( $group_id, 'bp-group-zoom-api-host', $user_info['response']->id );
					groups_update_groupmeta( $group_id, 'bp-group-zoom-api-host-type', $user_info['response']->type );
					groups_update_groupmeta( $group_id, 'bp-group-zoom-api-host-user', json_encode( $user_info['response'] ) );

					if ( $old_api_email !== $api_email ) {
						// Hide old host meetings.
						$q_hide = $wpdb->prepare( "UPDATE {$bp->table_prefix}bp_zoom_meetings SET hide_sitewide = %d WHERE group_id = %d AND host_id = %s", '1', $group_id, $old_api_email );
						$wpdb->query( $q_hide );

						// Un-hide current host meetings.
						$q_un_hide = $wpdb->prepare( "UPDATE {$bp->table_prefix}bp_zoom_meetings SET hide_sitewide = %d WHERE group_id = %d AND host_id = %s", '0', $group_id, $api_email );
						$wpdb->query( $q_un_hide );

					}

					bp_core_add_message( __( 'Group Zoom settings were successfully updated.', 'buddyboss-pro' ), 'success' );

				} else {

					groups_update_groupmeta( $group_id, 'bp-group-zoom-api-email', $api_email );
					groups_update_groupmeta( $group_id, 'bp-group-zoom-api-key', $api_key );
					groups_update_groupmeta( $group_id, 'bp-group-zoom-api-secret', $api_secret );
					groups_update_groupmeta( $group_id, 'bp-group-zoom-api-webhook-token', $webhook_token );
					groups_delete_groupmeta( $group_id, 'bp-group-zoom-api-host' );
					groups_delete_groupmeta( $group_id, 'bp-group-zoom-api-host-type' );
					groups_delete_groupmeta( $group_id, 'bp-group-zoom-api-host-user' );

					bp_core_add_message( __( 'Invalid Credentials. Please enter valid key, secret key or account email.', 'buddyboss-pro' ), 'error' );
				}
			} else {

				groups_update_groupmeta( $group_id, 'bp-group-zoom-api-email', $api_email );
				groups_update_groupmeta( $group_id, 'bp-group-zoom-api-key', $api_key );
				groups_update_groupmeta( $group_id, 'bp-group-zoom-api-secret', $api_secret );
				groups_update_groupmeta( $group_id, 'bp-group-zoom-api-webhook-token', $webhook_token );
				groups_delete_groupmeta( $group_id, 'bp-group-zoom-api-host' );
				groups_delete_groupmeta( $group_id, 'bp-group-zoom-api-host-type' );
				groups_delete_groupmeta( $group_id, 'bp-group-zoom-api-host-user' );

				bp_core_add_message( __( 'There was an error updating group Zoom API settings. Please try again.', 'buddyboss-pro' ), 'error' );
			}
		} else {
			bp_core_add_message( __( 'Group Zoom settings were successfully updated.', 'buddyboss-pro' ), 'success' );
		}

		/**
		 * Add action that fire before user redirect
		 *
		 * @Since 1.0.0
		 *
		 * @param int $group_id Current group id
		 */
		do_action( 'bp_group_admin_after_edit_screen_save', $group_id );

		// Redirect after save when not in admin
		if ( ! is_admin() ) {
			bp_core_redirect( trailingslashit( bp_get_group_permalink( buddypress()->groups->current_group ) . '/admin/zoom' ) );
		}
	}

	/**
	 * Register our activity actions with BuddyBoss
	 *
	 * @since 1.0.0
	 * @uses bp_activity_set_action()
	 */
	public function register_activity_actions() {
		// Group activity stream items
		bp_activity_set_action( buddypress()->groups->id, 'zoom_meeting_create', esc_html__( 'New Zoom meeting', 'buddyboss-pro' ), array(
			$this,
			'meeting_activity_action_callback'
		) );
	}

	/**
	 * Zoom meeting activity action.
	 *
	 * @param $action
	 * @param $activity
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function meeting_activity_action_callback( $action, $activity ) {
		if ( 'zoom_meeting_create' === $activity->type && buddypress()->groups->id === $activity->component && ! bp_zoom_is_group_setup( $activity->item_id )  ) {
			return $action;
		}

		$user_id    = $activity->user_id;
		$group_id   = $activity->item_id;
		$meeting_id = $activity->secondary_item_id;

		$meeting = new BP_Zoom_Meeting( $meeting_id );

		if ( empty( $meeting->id ) ) {
			return $action;
		}

		// User
		$user_link = bp_core_get_userlink( $user_id );

		// Meeting
		$meeting_permalink = bp_get_zoom_meeting_url( $group_id, $meeting_id );
		$meeting_title     = $meeting->title;
		$meeting_link      = '<a href="' . $meeting_permalink . '">' . $meeting_title . '</a>';

		$group      = groups_get_group( $group_id );
		$group_link = bp_get_group_link( $group );

		return sprintf(
			esc_html__( '%1$s scheduled a Zoom meeting %2$s in the group %3$s', 'buddyboss-pro' ),
			$user_link,
			$meeting_link,
			$group_link
		);
	}

	/**
	 * Create activity for meeting.
	 *
	 * @param $meeting
	 * @param $args
	 *
	 * @since 1.0.0
	 */
	public function create_activity( $meeting, $args ) {
		if ( bp_is_active( 'activity' ) &&
		     ! empty( $meeting ) &&
		     ! empty( $meeting->group_id ) &&
		     empty( $meeting->parent ) &&
		     empty( $args['id'] )
		) {

		    // if recurring parent meeting then return.
		    if ( 'meeting' === $meeting->zoom_type && true === $meeting->recurring ) {
		        return;
		    }

			$group = groups_get_group( $meeting->group_id );

			if ( ! empty( $group->id ) ) {
				$action = sprintf( __( '%1$s scheduled a Zoom meeting in the group %2$s', 'buddyboss-pro' ), bp_core_get_userlink( $meeting->user_id ), '<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( $group->name ) . '</a>' );

				$activity_id = groups_record_activity(
					array(
						'user_id'           => $meeting->user_id,
						'action'            => $action,
						'content'           => '',
						'type'              => 'zoom_meeting_create',
						'item_id'           => $meeting->group_id,
						'secondary_item_id' => $meeting->id,
					)
				);

				if ( $activity_id ) {

					// save activity id in meeting
					$meeting->activity_id = $activity_id;
					$meeting->save();

					// update activity meta
					bp_activity_update_meta( $activity_id, 'bp_meeting_id', $meeting->id );

					groups_update_groupmeta( $meeting->group_id, 'last_activity', bp_core_current_time() );
				}
			}
		}
	}

	/**
	 * Return activity meeting embed HTML
	 *
	 * @return false|string|void
	 * @since 1.0.0
	 *
	 */
	public function embed_meeting() {
	    if ( 'zoom_meeting_create' === bp_get_activity_type() &&
             buddypress()->groups->id === bp_get_activity_object_name() &&
             ! bp_zoom_is_group_setup( bp_get_activity_item_id() )  ) {
	        return;
	    }

		$meeting_id = bp_activity_get_meta( bp_get_activity_id(), 'bp_meeting_id', true );

		if ( empty( $meeting_id ) ) {
			return;
		}

		if ( bp_has_zoom_meetings(
			array(
				'include' => $meeting_id,
				'from'    => false,
				'since'   => false,
			)
		) ) {
			while ( bp_zoom_meeting() ) {
				bp_the_zoom_meeting();

				bp_get_template_part( 'zoom/activity-meeting-entry' );
			}
		}
	}

	/**
	 * Notify all group members when a meeting is created.
	 *
	 * @param $meeting
	 * @param $args
	 *
	 * @since 1.0.0
	 *
	 */
	public function groups_notification_meeting_created( $meeting, $args ) {
		if ( ! bp_is_active( 'notifications' ) || empty( $meeting ) || empty( $meeting->group_id ) || ! empty( $args['id'] ) || ! empty( $meeting->parent ) ) {
			return;
		}

		// if recurring parent meeting then return.
		if ( 'meeting' === $meeting->zoom_type && true === $meeting->recurring ) {
			return;
		}

		$group = groups_get_group( $meeting->group_id );

		$user_ids = BP_Groups_Member::get_group_member_ids( $group->id );
		foreach ( (array) $user_ids as $user_id ) {

			// do not add notification for current user.
			if ( (int) $user_id === bp_loggedin_user_id() ) {
				continue;
			}

			// Trigger a BuddyPress Notification.
			bp_notifications_add_notification(
				array(
					'user_id'           => $user_id,
					'item_id'           => $meeting->group_id,
					'secondary_item_id' => $meeting->id,
					'component_name'    => buddypress()->groups->id,
					'component_action'  => 'zoom_meeting_created',
				)
			);
		}
	}

	/**
	 * Create meeting notification for groups.
	 *
	 * @param $action
	 * @param $item_id
	 * @param $secondary_item_id
	 * @param $total_items
	 * @param $format
	 *
	 * @return mixed|void
	 * @since 1.0.0
	 */
	public function groups_format_create_meeting_notification( $action, $item_id, $secondary_item_id, $total_items, $format ) {
		$group_id = $item_id;

		$group      = groups_get_group( $group_id );
		$group_link = bp_get_group_permalink( $group );
		$meeting    = new BP_Zoom_Meeting( $secondary_item_id );
		$amount     = 'single';

		if ( (int) $total_items > 1 ) {
			$text              = sprintf(
			/* translators: total number of groups. */
				__( 'You have %1$d new Zoom meetings in groups', 'buddyboss-pro' ),
				(int) $total_items
			);
			$amount            = 'multiple';
			$notification_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) . '?n=1';

			if ( 'string' === $format ) {
				/**
				 * Filters multiple promoted to group mod notification for string format.
				 * Complete filter - bp_groups_multiple_member_promoted_to_mod_notification.
				 *
				 * @param string $string HTML anchor tag for notification.
				 * @param int $total_items Total number of rejected requests.
				 * @param string $text Notification content.
				 * @param string $notification_link The permalink for notification.
				 *
				 * @since 1.0.0
				 *
				 */
				return apply_filters( 'bp_groups_' . $amount . '_' . $action . '_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $total_items, $text, $notification_link );
			} else {
				/**
				 * Filters multiple promoted to group mod notification for non-string format.
				 * Complete filter - bp_groups_multiple_member_promoted_to_mod_notification.
				 *
				 * @param array $array Array holding permalink and content for notification.
				 * @param int $total_items Total number of rejected requests.
				 * @param string $text Notification content.
				 * @param string $notification_link The permalink for notification.
				 *
				 * @since 1.0.0
				 *
				 */
				return apply_filters(
					'bp_groups_' . $amount . '_' . $action . '_notification',
					array(
						'link' => $notification_link,
						'text' => $text,
					),
					$total_items,
					$text,
					$notification_link
				);
			}
		} else {
			$text              = sprintf(
			/* translators: 1 Meeting title. 2 Group Title. */
				__( 'Zoom meeting "%1$s" created in the group "%2$s"', 'buddyboss-pro' ),
				$meeting->title,
				$group->name
			);
			$notification_link = wp_nonce_url(
				add_query_arg(
					array(
						'action'     => 'bp_mark_read',
						'group_id'   => $item_id,
						'meeting_id' => $secondary_item_id,
					),
					$group_link . 'zoom/meetings/' . $secondary_item_id
				),
				'bp_mark_meeting_' . $item_id
			);

			if ( 'string' === $format ) {
				/**
				 * Filters single promoted to group mod notification for string format.
				 * Complete filter - bp_groups_single_zoom_meeting_created_notification.
				 *
				 * @param string $string HTML anchor tag for notification.
				 * @param int $group_link The permalink for the group.
				 * @param string $group ->name       Name of the group.
				 * @param string $text Notification content.
				 * @param string $notification_link The permalink for notification.
				 *
				 * @since 1.0.0
				 *
				 */
				return apply_filters( 'bp_groups_' . $amount . '_' . $action . '_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $group->name, $text, $notification_link );
			} else {
				/**
				 * Filters single promoted to group admin notification for non-string format.
				 * Complete filter - bp_groups_single_member_promoted_to_mod_notification.
				 *
				 * @param array $array Array holding permalink and content for notification.
				 * @param int $group_link The permalink for the group.
				 * @param string $group ->name       Name of the group.
				 * @param string $text Notification content.
				 * @param string $notification_link The permalink for notification.
				 *
				 * @since 1.0.0
				 *
				 */
				return apply_filters(
					'bp_groups_' . $amount . '_' . $action . '_notification',
					array(
						'link' => $notification_link,
						'text' => $text,
					),
					$group_link,
					$group->name,
					$text,
					$notification_link
				);
			}
		}
	}

	/**
	 * Mark zoom meeting notifications.
	 *
	 * @param string $action
	 *
	 * @since 1.0.0
	 */
	public function zoom_meeting_mark_notifications( $action = '' ) {
		// Bail if no group ID is passed
		if ( empty( $_GET['group_id'] ) ) {
			return;
		}

		// Bail if action is not for this function
		if ( 'bp_mark_read' !== $action ) {
			return;
		}

		// Get required data
		$user_id    = bp_loggedin_user_id();
		$meeting_id = intval( $_GET['meeting_id'] );
		$group_id   = intval( $_GET['group_id'] );

		// Check nonce
		if ( ! bp_verify_nonce_request( 'bp_mark_meeting_' . $group_id ) ) {
			return;

			// Check current user's ability to edit the user
		} elseif ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		if ( ! empty( $meeting_id ) ) {
			// Attempt to clear notifications for the current user from this meeting
			$success = bp_notifications_mark_notifications_by_item_id( $user_id, intval( $_GET['group_id'] ), buddypress()->groups->id, 'zoom_meeting_created', intval( $_GET['meeting_id'] ) );
		} else {
			// Attempt to clear notifications for the current user from this topic
			$success = bp_notifications_mark_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'zoom_meeting_created' );
		}

		// Do additional subscriptions actions
		do_action( 'bp_zoom_meeting_mark_notifications_handler', $success, $user_id, $group_id, $action, $meeting_id );
	}

	/**
	 * Delete create meeting notifications.
	 *
	 * @param $meeting_ids
	 *
	 * @since 1.0.0
	 */
	public function delete_meeting_notifications( $meeting_ids ) {
		if ( ! bp_is_active( 'notifications' ) ) {
			return;
		}

		if ( ! empty( $meeting_ids ) ) {
			foreach ( $meeting_ids as $meeting_id ) {
				$meeting = new BP_Zoom_Meeting( $meeting_id );

				if ( ! empty( $meeting->id ) && ! empty( $meeting->group_id ) && ! empty( $meeting->user ) ) {
					bp_notifications_delete_notifications_by_item_id( $meeting->user, $meeting->group_id, buddypress()->groups->id, 'zoom_meeting_created', $meeting_id );
				}
			}
		}
	}

	/**
	 * Customizer group nav items.
	 *
     * @param array $nav_items
	 * @param object $group
     *
	 * @since 1.0.0
	 */
	public function customizer_group_nav_items( $nav_items, $group ) {
		$nav_items['zoom'] = array(
			'name'        => __( 'Zoom', 'buddyboss-pro' ),
			'slug'        => 'zoom',
			'parent_slug' => $group->slug,
			'position'    => 90,
		);

		return $nav_items;
	}

	/**
	 * Zoom webhook handler
     *
     * @since 1.0.0
	 */
	public function zoom_webhook() {
		$zoom_webhook = filter_input( INPUT_GET, 'zoom_webhook', FILTER_VALIDATE_INT );

		if ( ! empty( $zoom_webhook ) && 1 === $zoom_webhook ) {

			$group_id = filter_input( INPUT_GET, 'group_id', FILTER_VALIDATE_INT );
			if ( ! empty( $group_id ) && 0 < $group_id && bp_zoom_is_group_setup( $group_id ) && ! empty( groups_get_group( $group_id ) ) ) {
				$content = file_get_contents( "php://input" );
				$json    = json_decode( $content, true );
				$token   = false;

				foreach ( getallheaders() as $header_name => $header_value ) {
					if ( 'Authorization' === $header_name ) {
						$token = $header_value;
						break;
					}
				}

				$group_token = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-webhook-token', true );

				if ( empty( trim( $group_token ) ) || trim( $group_token ) !== trim( $token ) ) {
					$this->forbid('No token detected' );
					exit;
				}

				if ( ! empty( $json['event'] ) && ! empty( $json['payload']['object'] ) ) {
					$event           = $json['event'];
					$object          = $json['payload']['object'];
					$zoom_meeting_id = ! empty( $object['id'] ) ? $object['id'] : false;
					$zoom_meeting    = BP_Zoom_Meeting::get_meeting_by_meeting_id( $zoom_meeting_id );
					$meeting         = false;

					if ( ! empty( $zoom_meeting ) ) {
						$meeting = new BP_Zoom_Meeting( $zoom_meeting->id );

						if ( empty( $meeting->id ) ) {
							$this->forbid('No meeting detected' );
							exit;
						}
					}

					if ( empty( $meeting ) ) {
						$this->forbid('No meeting detected' );
						exit;
					}

					if ( $meeting->group_id !== $group_id ) {
						$this->forbid('This meeting does not belong to group provided' );
						exit;
					}

					switch( $event ) {
						case 'meeting.started' :
							bp_zoom_meeting_update_meta( $meeting->id, 'meeting_status', 'started' );
							break;

						case 'meeting.ended' :
							bp_zoom_meeting_update_meta( $meeting->id, 'meeting_status', 'ended' );
							break;

                        case 'meeting.deleted' :
                            if ( ! empty( $object['occurrences'] ) ) {
                                foreach ( $object['occurrences'] as $occurrence ) {
	                                bp_zoom_meeting_delete( array( 'meeting_id' => $occurrence['occurrence_id'] ) );
                                }
                            } else {
	                            bp_zoom_meeting_delete( array( 'id' => $meeting->id ) );
                            }
                            break;

                        case 'meeting.updated' :
	                        $api_key    = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-key', true );
	                        $api_secret = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-secret', true );
	                        bp_zoom_conference()->zoom_api_key    = ! empty( $api_key ) ? $api_key : '';
	                        bp_zoom_conference()->zoom_api_secret = ! empty( $api_secret ) ? $api_secret : '';

	                        if ( isset( $object['topic'] ) ) {
		                        $meeting->title = $object['topic'];
	                        }

	                        // todo: we are not supporting other meeting types now, future enhancement.
//	                        if ( isset( $object['type'] ) && ( 8 === $object['type'] || 3 === $object['type'] || 4 === $object['type'] ) ) {
//                                $meeting->hide_sitewide = true;
//                            } else if ( isset( $object['type'] ) ) {
//		                        $meeting->hide_sitewide = false;
//	                        }

	                        delete_transient( 'bp_zoom_meeting_invitation_' . $zoom_meeting_id );

	                        if ( isset( $object['timezone'] ) ) {
		                        $meeting->timezone = $object['timezone'];
	                        }

	                        if ( isset( $object['start_time'] ) && 2 === $meeting->type ) {
                                $meeting->start_date_utc = $object['start_time'];
                                $meeting->start_date     = wp_date( 'Y-m-d\TH:i:s', strtotime( $meeting->start_date_utc ), new DateTimeZone( $meeting->timezone ) );
	                        }

	                        if ( isset( $object['created_at'] ) && ( 8 === $meeting->type || ( isset( $object['type'] ) && 8 === $object['type'] ) ) ) {
		                        $meeting->start_date_utc = $object['created_at'];
		                        $meeting->start_date     = wp_date( 'Y-m-d\TH:i:s', strtotime( $meeting->start_date_utc ), new DateTimeZone( $meeting->timezone ) );
	                        }

	                        if ( isset( $object['duration'] ) ) {
		                        $meeting->duration = (int) $object['duration'];
	                        }

	                        if ( isset( $object['agenda'] ) ) {
		                        $meeting->description = $object['agenda'];
	                        }

	                        if ( isset( $object['start_url'] ) ) {
	                            groups_update_groupmeta( $group_id, 'zoom_start_url', $object['start_url'] );
	                        }

	                        if ( isset( $object['join_url'] ) ) {
		                        groups_update_groupmeta( $group_id, 'zoom_join_url', $object['join_url'] );
	                        }

	                        if ( isset( $object['password'] ) ) {
		                        $meeting->password = $object['password'];
	                        }

	                        if ( isset( $object['settings'] ) ) {
		                        $settings = $object['settings'];

		                        if ( isset( $settings['host_video'] ) ) {
			                        $meeting->host_video = (bool) $settings['host_video'];
		                        }

		                        if ( isset( $settings['participant_video'] ) ) {
			                        $meeting->participants_video = (bool) $settings['participant_video'];
		                        }

		                        if ( isset( $settings['join_before_host'] ) ) {
			                        $meeting->join_before_host = (bool) $settings['join_before_host'];
		                        }

		                        if ( isset( $settings['mute_upon_entry'] ) ) {
			                        $meeting->mute_participants = (bool) $settings['mute_upon_entry'];
		                        }

		                        if ( isset( $settings['approval_type'] ) ) {
			                        $approval_type = (int) $settings['approval_type'];

                                    if ( in_array( $approval_type, array( 0, 1 ) ) ) {
	                                    $zoom_meeting = bp_zoom_conference()->get_meeting_info( $zoom_meeting_id );
	                                    if ( isset( $zoom_meeting['response']->registration_url ) && ! empty( $zoom_meeting['response']->registration_url ) ) {
		                                    bp_zoom_meeting_update_meta( $meeting->id, 'zoom_registration_url', $zoom_meeting['response']->registration_url );
	                                    }
                                    } else {
	                                    bp_zoom_meeting_delete_meta( $meeting->id, 'zoom_registration_url' );
                                    }
		                        }

		                        if ( 8 === $object['type'] && isset( $settings['registration_type'] ) ) {
			                        bp_zoom_meeting_update_meta( $meeting->id, 'zoom_registration_type', $settings['registration_type'] );
		                        } else {
			                        bp_zoom_meeting_delete_meta( $meeting->id, 'zoom_registration_type' );
		                        }

		                        if ( isset( $settings['auto_recording'] ) ) {
			                        $meeting->auto_recording = $settings['auto_recording'];
		                        }

		                        if ( isset( $settings['alternative_hosts'] ) ) {
			                        $meeting->alternative_host_ids = $settings['alternative_hosts'];
		                        }

		                        if ( isset( $settings['waiting_room'] ) ) {
			                        $meeting->waiting_room = (bool) $settings['waiting_room'];
		                        }

		                        if ( isset( $settings['meeting_authentication'] ) ) {
			                        $meeting->meeting_authentication = (bool) $settings['meeting_authentication'];
		                        }
	                        }

	                        if ( isset( $object['type'] ) ) {
		                        $meeting->type = $object['type'];

		                        if ( 8 === $object['type'] ) {
			                        bp_zoom_meeting_delete( array( 'parent' => $meeting->meeting_id ) );

			                        $data = array(
				                        'title'                  => $meeting->title,
				                        'type'                   => $meeting->type,
				                        'description'            => $meeting->description,
				                        'group_id'               => $meeting->group_id,
				                        'user_id'                => $meeting->user_id,
				                        'host_id'                => $meeting->host_id,
				                        'timezone'               => $meeting->timezone,
				                        'meeting_authentication' => $meeting->meeting_authentication,
				                        'password'               => $meeting->password,
				                        'join_before_host'       => $meeting->join_before_host,
				                        'host_video'             => $meeting->host_video,
				                        'participants_video'     => $meeting->participants_video,
				                        'mute_participants'      => $meeting->mute_participants,
				                        'waiting_room'           => $meeting->waiting_room,
				                        'auto_recording'         => $meeting->auto_recording,
				                        'alternative_host_ids'   => $meeting->alternative_host_ids,
			                        );

			                        if ( ! empty( $object['occurrences'] ) ) {
				                        foreach ( $object['occurrences'] as $meeting_occurrence ) {

					                        if ( 'deleted' === $meeting_occurrence['status'] ) {
						                        continue;
					                        }

					                        $meeting_occurrence_info = bp_zoom_conference()->get_meeting_info( $meeting->meeting_id, $meeting_occurrence['occurrence_id'] );
					                        if ( 200 === $meeting_occurrence_info['code'] && ! empty( $meeting_occurrence_info['response'] ) ) {
						                        $data['title']                  = $meeting_occurrence_info['response']->topic;
						                        $data['type']                   = $meeting_occurrence_info['response']->type;
						                        $data['description']            = $meeting_occurrence_info['response']->agenda;
						                        $data['meeting_authentication'] = $meeting_occurrence_info['response']->settings->meeting_authentication;
						                        $data['join_before_host']       = $meeting_occurrence_info['response']->settings->join_before_host;
						                        $data['host_video']             = $meeting_occurrence_info['response']->settings->host_video;
						                        $data['participants_video']     = $meeting_occurrence_info['response']->settings->participant_video;
						                        $data['mute_participants']      = $meeting_occurrence_info['response']->settings->mute_upon_entry;
						                        $data['waiting_room']           = $meeting_occurrence_info['response']->settings->waiting_room;
						                        $data['auto_recording']         = $meeting_occurrence_info['response']->settings->auto_recording;
						                        $data['alternative_host_ids']   = $meeting_occurrence_info['response']->settings->alternative_hosts;
					                        }

					                        $data['hide_sitewide']  = false;
					                        $data['meeting_id']     = $meeting_occurrence['occurrence_id'];
					                        $data['duration']       = $meeting_occurrence['duration'];
					                        $data['parent']         = $meeting->meeting_id;
					                        $data['zoom_type']      = 'meeting_occurrence';
					                        $data['start_date']     = $meeting_occurrence['start_time'];
					                        $data['start_date_utc'] = $meeting_occurrence['start_time'];
					                        $data['recurring']      = false;
					                        bp_zoom_meeting_add( $data );
				                        }
			                        }
		                        }
	                        }

	                        $meeting->save();
                            break;
                        case 'recording.completed':
	                        if ( ! bp_zoom_is_zoom_recordings_enabled() ) {
		                        break;
	                        }
	                        $password        = ! empty( $object['password'] ) ? $object['password'] : '';
	                        $recording_files = ! empty( $object['recording_files'] ) ? $object['recording_files'] : array();
	                        $start_time      = ! empty( $object['start_time'] ) ? $object['start_time'] : '';
	                        if ( ! empty( $recording_files ) ) {
		                        foreach ( $recording_files as $recording_file ) {
			                        $recording_id = ( isset( $recording_file['id'] ) ? $recording_file['id'] : '' );
			                        if ( ! empty( $recording_id ) && empty( bp_zoom_recording_get( array(), array( 'recording_id' => $recording_id ) ) ) ) {
				                        bp_zoom_recording_add( array(
					                        'recording_id' => $recording_id,
					                        'meeting_id'   => $zoom_meeting_id,
					                        'uuid'         => $object['uuid'],
					                        'details'      => $recording_file,
					                        'password'     => $password,
					                        'file_type'    => $recording_file['file_type'],
					                        'start_time'   => $start_time,
				                        ) );
			                        }
		                        }

		                        $count = bp_zoom_recording_get( array(), array(
			                        'meeting_id' => $zoom_meeting_id,
		                        ) );

		                        bp_zoom_meeting_update_meta( $meeting->id, 'zoom_recording_count', (int) count( $count ) );
	                        }
                            break;
					}
				}
			}
		}
	}

	/**
     * Forbid zoom webhook.
     *
     * @since 1.0.0
     *
	 * @param $reason
	 */
	public function forbid( $reason ) {
		// format the error
		$error = "=== ERROR: " . $reason . " ===\n*** ACCESS DENIED ***\n";

		// forbid
		http_response_code( 403 );

		echo $error;

		// stop executing
		exit;
	}

	/**
	 * Setup page title for the zoom.
	 *
	 * @param $title
	 *
	 * @return mixed
	 * @since 1.0.0
	 */
	public function bp_nouveau_group_zoom_set_page_title( $title ) {

		global $bp, $bp_zoom_current_meeting;
		$new_title = '';

		if ( bp_zoom_is_single_meeting() ) {
			$new_title = $bp_zoom_current_meeting->title;
		}

		if ( empty( $new_title ) && 'past-meetings' === bp_zoom_group_current_meeting_tab() ) {
			$new_title = esc_html__( 'Past Meetings', 'buddyboss-pro' );
		}

		if ( empty( $new_title ) && 'meetings' === bp_zoom_group_current_meeting_tab() ) {
			$new_title = esc_html__( 'Upcoming Meetings', 'buddyboss-pro' );
		}

		if ( empty( $new_title ) && 'create-meeting' === bp_zoom_group_current_meeting_tab() ) {
			$new_title = esc_html__( 'Create Meeting', 'buddyboss-pro' );
		}

		if ( strlen( $new_title ) > 0 ) {
			$title['title'] = $new_title;
		}

		return $title;
	}

	/**
	 * Setup title tag for the page.
	 *
	 * @param $title
	 *
	 * @return mixed
	 * @since 1.0.0
	 */
	function bp_nouveau_group_zoom_set_title_tag( $title ) {

		global $bp, $bp_zoom_current_meeting;
		$new_title = "";

		if ( bp_zoom_is_single_meeting() && ! empty( $bp_zoom_current_meeting->title ) ) {
			$new_title = $bp_zoom_current_meeting->title;
		}

		if ( empty( $new_title ) && 'past-meetings' === bp_zoom_group_current_meeting_tab() ) {
			$new_title = esc_html__( 'Past Meetings', 'buddyboss-pro' );
		}

		if ( empty( $new_title ) && 'meetings' === bp_zoom_group_current_meeting_tab() ) {
			$new_title = esc_html__( 'Upcoming Meetings', 'buddyboss-pro' );
		}

		if ( empty( $new_title ) && 'create-meeting' === bp_zoom_group_current_meeting_tab() ) {
			$new_title = esc_html__( 'Create Meeting', 'buddyboss-pro' );
		}

		if ( in_array( bp_zoom_group_current_meeting_tab(), array( 'meetings', 'past-meetings', 'create-meeting' ) ) || bp_zoom_is_single_meeting() ) {
			$sep               = apply_filters( 'document_title_separator', '-' );
			$get_current_group = bp_get_current_group_name();

			$new_title = $new_title . ' ' . $sep . ' ' . $get_current_group . ' ' . $sep . ' ' . bp_get_site_name();
		}

		//Combine the new title with the old (separator and tagline)
		if ( strlen( $new_title ) > 0 ) {
			$title = $new_title . " " . $title;
		}

		return $title;
	}

	/**
	 * Remove all meetings belonging to a specific group.
	 *
	 * @since 1.0.0
	 *
	 * @param int $group_id ID of the group.
	 */
	public function delete_group_delete_all_meetings( $group_id ) {
		bp_zoom_meeting_delete( array( 'group_id' => $group_id ) );
	}
}

