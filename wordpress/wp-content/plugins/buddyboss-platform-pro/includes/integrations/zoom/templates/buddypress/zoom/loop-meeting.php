<?php
/**
 * BuddyBoss - Groups Zoom Loop Meetings
 *
 * @since 1.0.0
 */

global $bp_zoom_current_meeting;

$class           = ( ! empty( $bp_zoom_current_meeting->id ) && $bp_zoom_current_meeting->id === bp_get_zoom_meeting_id() ) ? ' current' : '';
$recording_count = bp_zoom_meeting_get_meta( bp_get_zoom_meeting_id(), 'zoom_recording_count', true );
$meeting_status  = bp_zoom_meeting_get_meta( bp_get_zoom_meeting_id(), 'meeting_status', true );

$id = bp_get_zoom_meeting_id();
if ( 'meeting_occurrence' === bp_get_zoom_meeting_zoom_type() ) {
    $parent_meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( bp_get_zoom_meeting_parent() );
	if ( ! empty( $parent_meeting ) ) {
		$id              = $parent_meeting->id;
		$recording_count = bp_zoom_meeting_get_meta( $id, 'zoom_recording_count', true );
		$meeting_status  = bp_zoom_meeting_get_meta( $id, 'meeting_status', true );

		if ( 'started' === $meeting_status && wp_date( 'Y-m-d', strtotime( bp_get_zoom_meeting_start_date_utc() ) ) !== wp_date( 'Y-m-d', strtotime( 'now' ), new DateTimeZone( 'UTC' ) ) ) {
			$meeting_status = '';
        }
	}
}
?>

<li class="meeting-item <?php echo $class; ?>" data-id="<?php bp_zoom_meeting_id(); ?>" data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>">
    <div class="meeting-item-col meeting-topic">
        <a href="<?php bp_zoom_meeting_url( bp_get_current_group_id(), bp_get_zoom_meeting_id() ); ?>" class="meeting-title">
            <?php bp_zoom_meeting_title(); ?>
        </a>
        <?php if ( 'started' === $meeting_status ) : ?>
		    <span class="live-meeting-label"><?php _e( 'Live', 'buddyboss-pro' ); ?></span>
        <?php endif; ?>
		<?php if ( bp_zoom_is_zoom_recordings_enabled() && ! empty( $recording_count ) ) : ?>
			<a role="button" href="#" class="button small view-recordings bp-zoom-meeting-view-recordings">
                <svg width="14" height="8" xmlns="http://www.w3.org/2000/svg"><g fill="#FFF" fill-rule="evenodd"><rect width="9.451" height="8" rx="1.451"/><path d="M10.5 1.64v4.753l1.637 1.046a.571.571 0 00.879-.482V1.055a.571.571 0 00-.884-.48L10.5 1.64z"/></g></svg>
                <span class="record-count"><?php echo $recording_count; ?></span>
            </a>
		<?php endif; ?>
    </div>

	<div class="meeting-item-col meeting-meta-wrap">
		<div class="meeting-id"><?php printf( __( 'ID: %d', 'buddyboss-pro' ), bp_get_zoom_meeting_zoom_meeting_id() ); ?></div>
		<?php if ( 8 === bp_get_zoom_meeting_type() ) : ?>
            <span class="recurring-meeting-label" data-bp-tooltip="<?php _e( 'Recurring', 'buddyboss-pro' ); ?>" data-bp-tooltip-pos="left"></span>
	    <?php endif; ?>
		<div class="meeting-date">
		    <?php echo wp_date( bp_core_date_format( true, true, __( ' \a\t ', 'buddyboss-pro' ) ), strtotime( bp_get_zoom_meeting_start_date_utc() ) ); ?>
		</div>
	</div>
</li>
