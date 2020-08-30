<?php
/**
 * BuddyBoss - Groups Zoom Meetings
 *
 * @since 1.0.0
 */

switch ( bp_zoom_group_current_meeting_tab() ) :

	// meetings.
	case 'zoom':
	case 'meetings':
	case 'create-meeting':
	case 'past-meetings':
			bp_get_template_part( 'zoom/meetings' );
		break;

	// Any other.
	default:
		bp_get_template_part( 'groups/single/plugins' );
		break;
endswitch;
