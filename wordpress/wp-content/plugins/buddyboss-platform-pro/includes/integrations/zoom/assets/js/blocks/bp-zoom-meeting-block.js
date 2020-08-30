import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { __experimentalGetSettings, dateI18n, date } from '@wordpress/date';
import {
	TextControl,
	TextareaControl,
	PanelBody,
	Popover,
	DateTimePicker,
	DatePicker,
	Button,
	__experimentalText as Text,
	SelectControl,
	CheckboxControl,
	Placeholder,
	BaseControl,
	RadioControl
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { doAction, addAction } from '@wordpress/hooks';
import { differenceBy, camelCase } from 'lodash';
import moment from 'moment';

const moment_default_datetime_format = 'YYYY-MM-DD HH:mm:ss';

/**
 * Returns whether buddyboss category is in editor cats list or not
 *
 * @return {boolean} true if category is in list.
 */
export const isBuddyBossInCategories = () => {
	const blockCategories = wp.blocks.getCategories();
	for ( var i in blockCategories ) {
		if ( 'buddyboss' === blockCategories[i].slug ) {
			return true;
		}
	}
	return false;
};

const currentDateTime = new Date( bpZoomMeetingBlock.wp_date_time );
currentDateTime.setMinutes( currentDateTime.getMinutes() + ( 60 - currentDateTime.getMinutes() ) );

registerBlockType( 'bp-zoom-meeting/create-meeting', {
	title: __( 'Zoom Meeting', 'buddyboss-pro' ),
	description: __( 'Create meeting in Zoom', 'buddyboss-pro' ),
	icon: 'video-alt2',
	category: isBuddyBossInCategories() ? 'buddyboss' : 'common',
	keywords: [ __( 'zoom', 'buddyboss-pro' ), __( 'meeting', 'buddyboss-pro' ) ],
	supports: {
		html: false,
		reusable: false,
	},

	attributes: {
		id: {
			type: 'number',
			default: ''
		},
		meetingId: {
			type: 'number',
			default: ''
		},
		hostId: {
			type: 'string',
			default: typeof bpZoomMeetingBlock.default_host_id !== 'undefined' ? bpZoomMeetingBlock.default_host_id : ''
		},
		hostDisplayName: {
			type: 'string',
			default: typeof bpZoomMeetingBlock.default_host_user !== 'undefined' ? bpZoomMeetingBlock.default_host_user : ''
		},
		alt_hosts: {
			type: 'string',
			default: ''
		},
		title: {
			type: 'string',
			default: ''
		},
		description: {
			type: 'string',
			default: ''
		},
		startDate: {
			type: 'string',
			default: moment( currentDateTime ).format( moment_default_datetime_format )
		},
		duration: {
			type: 'string',
			default: '30'
		},
		timezone: {
			type: 'string',
			default: typeof bpZoomMeetingBlock.wp_timezone !== 'undefined' ? bpZoomMeetingBlock.wp_timezone : ''
		},
		password: {
			type: 'string',
			default: ''
		},
		registration: {
			type: 'boolean',
			default: false
		},
		registration_type: {
			type: 'number',
			default: 1
		},
		hostVideo: {
			type: 'boolean',
			default: false
		},
		participantsVideo: {
			type: 'boolean',
			default: false
		},
		joinBeforeHost: {
			type: 'boolean',
			default: false
		},
		muteParticipants: {
			type: 'boolean',
			default: false
		},
		waitingRoom: {
			type: 'boolean',
			default: false
		},
		authentication: {
			type: 'boolean',
			default: false
		},
		autoRecording: {
			type: 'string',
			default: 'none'
		},
		recurring: {
			type: 'boolean',
			default: false
		},
		recurrence: {
			type: 'number',
			default: 1
		},
		repeat_interval: {
			type: 'number',
			default: 1
		},
		end_time_select: {
			type: 'string',
			default: 'date'
		},
		end_times: {
			type: 'number',
			default: 7
		},
		end_date_time: {
			type: 'string',
			default: moment( new Date().setDate( new Date( bpZoomMeetingBlock.wp_date_time ).getDate() + 6 ) ).format( moment_default_datetime_format )
		},
		weekly_days: {
			type: 'array',
			default: ['4']
		},
		monthly_occurs_on: {
			type: 'string',
			default: 'day'
		},
		monthly_day: {
			type: 'number',
			default: 1
		},
		monthly_week: {
			type: 'number',
			default: 1
		},
		monthly_week_day: {
			type: 'number',
			default: 1
		},
		occurrences: {
			type: 'array',
			default: []
		},
		current_occurrence: {
			type: 'object',
			default: {}
		},
		occurrence_edit: {
			type: 'boolean',
			default: false
		},
		current_occurrence_start_time: {
			type: 'string',
			default: ''
		},
		current_occurrence_duration: {
			type: 'number',
			default: 0
		},
		meetingFormType: {
			type: 'string',
			default: ''
		},
		external_meeting: {
			type: 'boolean',
			default: false
		}
	},

	edit: ( props ) => {
		const { clientId, setAttributes } = props;
		const host_user_type = typeof bpZoomMeetingBlock.default_host_user_type !== 'undefined' ? bpZoomMeetingBlock.default_host_user_type : 1;
		const {
			meetingId,
			hostId,
			hostDisplayName,
			title,
			description,
			startDate,
			duration,
			timezone,
			password,
			registration,
			registration_type,
			hostVideo,
			participantsVideo,
			joinBeforeHost,
			muteParticipants,
			waitingRoom,
			authentication,
			autoRecording,
			meetingFormType,
			alt_hosts,
			external_meeting,
			recurring,
			recurrence,
			repeat_interval,
			end_times,
			end_date_time,
			end_time_select,
			weekly_days,
			monthly_occurs_on,
			monthly_day,
			monthly_week,
			monthly_week_day,
			occurrences,
			current_occurrence,
			occurrence_edit,
			current_occurrence_start_time,
			current_occurrence_duration
		} = props.attributes;

		let repeat_interval_options = [], repeat_every = __('day', 'buddyboss-pro'),
			start_date_dt = new Date(startDate),
			end_date_dt = new Date();

		const setMeetingId = ( val ) => {
			let reg = new RegExp( '^\\d+$' );
			if ( '' !== val && reg.test(val) ) {
				val = parseInt( val.toString().replace( /\s/g, '' ) );
			}
			setAttributes( { meetingId: val } );
		}
		const setHostId = ( val ) => {
			setAttributes( { hostId: val } );
		}
		const setHostDisplayName = ( val ) => {
			setAttributes( { hostDisplayName: val } );
		}
		const setTitle = ( val ) => {
			setAttributes( { title: val } );
		}
		const setDescription = ( val ) => {
			setAttributes( { description: val } );
		}
		const setStartDate = ( val ) => {
			let nowDate = new Date( bpZoomMeetingBlock.wp_date_time );
			let selectedDate = new Date( val );
			if ( nowDate.getTime() < selectedDate.getTime() ) {
				setAttributes( { startDate: val } );

				let end_date_time_date = new Date( end_date_time );

				if ( selectedDate.getTime() >= end_date_time_date.getTime() ) {
					let start_date_dt_val = new Date( val );

					if ( recurrence === 1 ) {
						start_date_dt_val.setDate( start_date_dt_val.getDate() + ( 6 * repeat_interval ) );
						setEndDateTime( moment( start_date_dt_val ).format( moment_default_datetime_format ) );
					} else if ( recurrence === 2 ) {
						start_date_dt_val.setDate( start_date_dt_val.getDate() + ( 6 * ( 7 * repeat_interval ) ) );
						setEndDateTime( moment( start_date_dt_val ).format( moment_default_datetime_format ) );
					} else if ( recurrence === 3 ) {
						start_date_dt_val.setMonth( start_date_dt_val.getMonth() + ( 6 * repeat_interval ) );
						setEndDateTime( moment( start_date_dt_val ).format( moment_default_datetime_format ) );
					}
				}
			}
		}
		const setDuration = ( val ) => {
			setAttributes( { duration: val } );
		}
		const setTimezone = ( val ) => {
			setAttributes( { timezone: val } );

			var currentDateTimeZoneWise = new Date( new Date().toLocaleString( 'en-US', { timeZone: val } ) );
			var month = '' + (currentDateTimeZoneWise.getMonth() + 1),
				day = '' + currentDateTimeZoneWise.getDate(),
				year = currentDateTimeZoneWise.getFullYear(),
				hour = '' + currentDateTimeZoneWise.getHours(),
				minutes = '' + currentDateTimeZoneWise.getMinutes(),
				seconds = '' + currentDateTimeZoneWise.getSeconds();

			if (month.length < 2) {
				month = '0' + month;
			}
			if (day.length < 2) {
				day = '0' + day;
			}
			if (hour.length < 2) {
				hour = '0' + hour;
			}
			if (minutes.length < 2) {
				minutes = '0' + minutes;
			}
			if (seconds.length < 2) {
				seconds = '0' + seconds;
			}

			bpZoomMeetingBlock.wp_date_time = [year, month, day].join('-') + 'T' + [hour,minutes,seconds].join(':');

			var currentStartDateObj = new Date( startDate );
			if ( meetingId.length === 0 && currentStartDateObj < currentDateTimeZoneWise ) {
				setAttributes( { startDate: bpZoomMeetingBlock.wp_date_time } );
			}
		}
		const setPassword = ( val ) => {
			setAttributes( { password: val } );
		}
		const setRegistration = ( val ) => {
			setAttributes( { registration: val } );
		}
		const setRegistrationType = ( val ) => {
			setAttributes( { registration_type: parseInt( val ) } );
		}
		const setHostVideo = ( val ) => {
			setAttributes( { hostVideo: val } );
		}
		const setParticipantsVideo = ( val ) => {
			setAttributes( { participantsVideo: val } );
		}
		const setJoinBeforeHost = ( val ) => {
			setAttributes( { joinBeforeHost: val } );
		}
		const setMuteParticipants = ( val ) => {
			setAttributes( { muteParticipants: val } );
		}
		const setWaitingRoom = ( val ) => {
			setAttributes( { waitingRoom: val } );
		}
		const setAuthentication = ( val ) => {
			setAttributes( { authentication: val } );
		}
		const setAutoRecording = ( val ) => {
			setAttributes( { autoRecording: val } );
		}
		const setMeetingFormType = ( val ) => {
			setAttributes( { meetingFormType: val } );
		}
		const setRecurring = ( val ) => {
			setAttributes( { recurring: val } );
		}
		const setRecurrence = ( val ) => {
			setAttributes( { recurrence: parseInt( val ) } );

			if ( val == 1 ) {
				end_date_dt.setDate( start_date_dt.getDate() + ( 6 * repeat_interval ) );
				setEndDateTime( moment( end_date_dt ).format( moment_default_datetime_format ) );
			} else if ( val == 2 ) {
				end_date_dt.setDate( start_date_dt.getDate() + ( 6 * ( 7 * repeat_interval ) ) );
				setEndDateTime( moment( end_date_dt ).format( moment_default_datetime_format ) );
			} else if ( val == 3 ) {
				end_date_dt.setMonth( start_date_dt.getMonth() + ( 6 * repeat_interval ) );
				setEndDateTime( moment( end_date_dt ).format( moment_default_datetime_format ) );
			}
		}
		const setRepeatInterval = ( val ) => {
			setAttributes( { repeat_interval: val } );

			if ( recurrence === 1 ) {
				end_date_dt.setDate( start_date_dt.getDate() + ( 6 * val ) );
				setEndDateTime( moment( end_date_dt ).format( moment_default_datetime_format ) );
			} else if ( recurrence === 2 ) {
				end_date_dt.setDate( start_date_dt.getDate() + ( 6 * ( 7 * val ) ) );
				setEndDateTime( moment( end_date_dt ).format( moment_default_datetime_format ) );
			} else if ( recurrence === 3 ) {
				end_date_dt.setMonth( start_date_dt.getMonth() + ( 6 * val ) );
				setEndDateTime( moment( end_date_dt ).format( moment_default_datetime_format ) );
			}
		}
		const setEndTimes = ( val ) => {
			setAttributes( { end_times: parseInt( val ) } );
		}
		const setEndDateTime = ( val ) => {
			let meetingDate = new Date( startDate );
			let selectedDate = new Date( val );
			if ( meetingDate.getTime() < selectedDate.getTime() ) {
				setAttributes( { end_date_time: val } );
			}
		}
		const setOccurrenceStartTime = ( start_time ) => {
			let nowDate = new Date();
			let selectedDate = new Date( start_time );
			if ( nowDate.getTime() < selectedDate.getTime() ) {
				setAttributes( { current_occurrence_start_time: start_time } );
			}
		}
		const setOccurrenceDuration = ( duration_val ) => {
			setAttributes( { current_occurrence_duration: duration_val } );
		}
		const setEndTimeSelect = ( val ) => {
			setAttributes( { end_time_select: val } );
		}
		const setWeeklyDays = ( val ) => {
			setAttributes( { weekly_days: val } );
		}
		const setMonthlyOccursOn = ( val ) => {
			setAttributes( { monthly_occurs_on: val } );
		}
		const setMonthlyDay = ( val ) => {
			setAttributes( { monthly_day: parseInt( val ) } );
		}
		const setMonthlyWeek = ( val ) => {
			setAttributes( { monthly_week: val } );
		}
		const setMonthlyWeekDay = ( val ) => {
			setAttributes( { monthly_week_day: parseInt( val ) } );
		}
		const setOccurrences = ( val ) => {
			setAttributes( { occurrences: val } );

			for ( let o in val ) {
				let nowDate = new Date( bpZoomMeetingBlock.wp_date_time );
				let selectedDate = new Date( val[o].start_time );
				if ( nowDate.getTime() < selectedDate.getTime() && 'deleted' !== val[o].status ) {
					setStartDate( val[o].start_time );
					break;
				}
			}
		}
		const setOccurrenceEdit = ( val ) => {
			setAttributes( { occurrence_edit: val } );
		}
		const setCurrentOccurrence = ( val ) => {
			setAttributes( { current_occurrence: val } );
			setOccurrenceDuration(val.duration);
			setOccurrenceStartTime(val.start_time);
		}
		const setAltHosts = ( val ) => {
			setAttributes( { alt_hosts: val } );
		}
		const setExternalMeeting = ( val ) => {
			setAttributes( { external_meeting: val } );
		}
		const settings = __experimentalGetSettings();
		const [ isPickerOpen, setIsPickerOpen ] = useState( false );
		const [ isRecurrencePickerOpen, setIsRecurrencePickerOpen ] = useState( false );

		const resolvedFormat = settings.formats.datetime || 'Y-m-d H:i:s';

		let auto_recording_options = [];

		if ( host_user_type == 2 ) {
			auto_recording_options = [
				{ label: __( 'No Recordings', 'buddyboss-pro' ), value: 'none' },
				{ label: __( 'Cloud', 'buddyboss-pro' ), value: 'cloud' },
				{ label: __( 'Local', 'buddyboss-pro' ), value: 'local' },
			];
		} else {
			auto_recording_options = [
				{ label: __( 'No Recordings', 'buddyboss-pro' ), value: 'none' },
				{ label: __( 'Local', 'buddyboss-pro' ), value: 'local' },
			];
		}

		if ( recurrence === 1 ) {
			repeat_every = __( 'day', 'buddyboss-pro' );
			repeat_interval_options = [];
			for ( let i = 1; i <= 15; i++ ) {
				repeat_interval_options.push( { label: i, value: i } );
			}
		} else if ( recurrence === 2 ) {
			repeat_every = __( 'week', 'buddyboss-pro' );
			repeat_interval_options = [];
			for ( let i = 1; i <= 12; i++ ) {
				repeat_interval_options.push( { label: i, value: i } );
			}
		} else if ( recurrence === 3 ) {
			repeat_every = __( 'month', 'buddyboss-pro' );
			repeat_interval_options = [];
			for ( let i = 1; i <= 3; i++ ) {
				repeat_interval_options.push( { label: i, value: i } );
			}
		}

		return (
			<>
				{'' === meetingFormType ?
					<Placeholder
						icon="video-alt2"
						className="bb-input-container"
						label={__( 'Zoom Meeting', 'buddyboss-pro' )}
						instructions={__( 'Create meeting or add existing meeting.', 'buddyboss-pro' )}
					>

						<Button isSecondary onClick={() => {
							setMeetingFormType( 'create' )
						}}>
							{__( 'Create Meeting', 'buddyboss-pro' )}
						</Button>
						<Button isSecondary onClick={() => {
							setMeetingFormType( 'existing' )
						}}>
							{__( 'Add Existing Meeting', 'buddyboss-pro' )}
						</Button>
					</Placeholder>
					: ''
				}
				{'existing' === meetingFormType ?
					<>
						<Placeholder icon="video-alt2" className="bb-meeting-id-input-container"
						             label={__( 'Add Existing Meeting', 'buddyboss-pro' )}>
							<TextControl
								label={__( 'Meeting ID', 'buddyboss-pro' )}
								value={meetingId}
								className="components-placeholder__input bb-meeting-id-wrap"
								placeholder={__( 'Enter meeting ID without spaces…', 'buddyboss-pro' )}
								onChange={setMeetingId}
							/>
							<BaseControl
								className="bb-buttons-wrap"
							>
								<Button isPrimary onClick={( e ) => {
									var target = e.target;
									target.setAttribute( 'disabled', true );
									const meeting_data = {
										'_wpnonce': bpZoomMeetingBlock.bp_zoom_meeting_nonce,
										'bp-zoom-meeting-id': meetingId,
									};

									wp.ajax.send( 'zoom_meeting_update_in_site', {
										data: meeting_data,
										success: function ( response ) {
											target.removeAttribute( 'disabled' );
											wp.data.dispatch( 'core/notices' ).createNotice(
												'success', // Can be one of: success, info, warning, error.
												__( 'Meeting Updated.', 'buddyboss-pro' ), // Text string to display.
												{
													isDismissible: true, // Whether the user can dismiss the notice.
												}
											);
											if ( typeof response.host_name !== 'undefined' ) {
												setHostDisplayName( response.host_name );
											}
											if ( typeof response.host_email !== 'undefined' ) {
												setHostId( response.host_email );
											}
											if ( typeof response.meeting !== 'undefined' ) {
												if ( typeof response.meeting.id !== 'undefined' ) {
													setMeetingId( response.meeting.id );
												}
												if ( typeof response.meeting.host_id !== 'undefined' ) {
													setHostId( response.meeting.host_id );
												}
												if ( typeof response.meeting.topic !== 'undefined' ) {
													setTitle( response.meeting.topic );
												}
												if ( typeof response.meeting.agenda !== 'undefined' ) {
													setDescription( response.meeting.agenda );
												}
												if ( typeof response.meeting.timezone !== 'undefined' ) {
													setTimezone( response.meeting.timezone );
												}
												if ( typeof response.meeting.start_time !== 'undefined' ) {
													setAttributes( { startDate: response.meeting.start_time } );
												}
												if ( typeof response.meeting.duration !== 'undefined' ) {
													setDuration( response.meeting.duration );
												}
												if ( typeof response.meeting.password !== 'undefined' ) {
													setPassword( response.meeting.password );
												}
												if ( typeof response.meeting.type !== 'undefined' && [ 3, 8 ].includes( response.meeting.type ) ) {
													setRecurring( true );
												}
												if ( typeof response.meeting.occurrences !== 'undefined' && response.meeting.occurrences.length ) {
													setOccurrences( response.meeting.occurrences );
												}
												if ( typeof response.meeting.recurrence !== 'undefined' ) {
													if ( typeof response.meeting.recurrence.type !== 'undefined' ) {
														setRecurrence( response.meeting.recurrence.type );
													}
													if ( typeof response.meeting.recurrence.repeat_interval !== 'undefined' ) {
														setRepeatInterval( response.meeting.recurrence.repeat_interval );
													}
													if ( typeof response.meeting.recurrence.end_times !== 'undefined' ) {
														setEndTimes( response.meeting.recurrence.end_times );
														setEndTimeSelect( 'times' );
													}
													if ( typeof response.meeting.recurrence.end_date_time !== 'undefined' ) {
														setEndDateTime( response.meeting.recurrence.end_date_time );
														setEndTimeSelect( 'date' );
													}
													if ( typeof response.meeting.recurrence.weekly_days !== 'undefined' ) {
														setWeeklyDays( response.meeting.recurrence.weekly_days.split( ',' ) );
													}
													if ( typeof response.meeting.recurrence.monthly_day !== 'undefined' ) {
														setMonthlyDay( response.meeting.recurrence.monthly_day );
														setMonthlyOccursOn( 'day' );
													}
													if ( typeof response.meeting.recurrence.monthly_week !== 'undefined' ) {
														setMonthlyWeek( response.meeting.recurrence.monthly_week );
														setMonthlyOccursOn( 'week' );
													}
													if ( typeof response.meeting.recurrence.monthly_week_day !== 'undefined' ) {
														setMonthlyWeekDay( response.meeting.recurrence.monthly_week_day );
														setMonthlyOccursOn( 'week' );
													}
												}
												if ( typeof response.meeting.settings !== 'undefined' ) {
													if ( typeof response.meeting.settings.alternative_hosts !== 'undefined' ) {
														setAltHosts( response.meeting.settings.alternative_hosts );
													}
													if ( typeof response.meeting.settings.approval_type !== 'undefined' && 0 == response.meeting.settings.approval_type ) {
														setRegistration( true );
													}
													if ( typeof response.meeting.settings.registration_type !== 'undefined' ) {
														setRegistrationType( response.meeting.settings.registration_type );
													}
													if ( typeof response.meeting.settings.host_video !== 'undefined' ) {
														setHostVideo( response.meeting.settings.host_video );
													}
													if ( typeof response.meeting.settings.participant_video !== 'undefined' ) {
														setParticipantsVideo( response.meeting.settings.participant_video );
													}
													if ( typeof response.meeting.settings.join_before_host !== 'undefined' ) {
														setJoinBeforeHost( response.meeting.settings.join_before_host );
													}
													if ( typeof response.meeting.settings.mute_upon_entry !== 'undefined' ) {
														setMuteParticipants( response.meeting.settings.mute_upon_entry );
													}
													if ( typeof response.meeting.settings.waiting_room !== 'undefined' ) {
														setWaitingRoom( response.meeting.settings.waiting_room );
													}
													if ( typeof response.meeting.settings.meeting_authentication !== 'undefined' ) {
														setAuthentication( response.meeting.settings.meeting_authentication );
													}
													if ( typeof response.meeting.settings.auto_recording !== 'undefined' ) {
														setAutoRecording( response.meeting.settings.auto_recording );
													}
												}
											}
											setMeetingFormType( 'create' );
											setExternalMeeting( true );
											var editorInfo = wp.data.select( 'core/editor' );
											if ( editorInfo.isEditedPostSaveable() ) {
												if ( !editorInfo.isCurrentPostPublished() && ~[ 'draft', 'auto-draft' ].indexOf( editorInfo.getEditedPostAttribute( 'status' ) ) ) {
													wp.data.dispatch( 'core/editor' ).autosave();
												} else {
													wp.data.dispatch( 'core/editor' ).savePost();
												}
											}
										},
										error: function ( error ) {
											target.removeAttribute( 'disabled' );
											wp.data.dispatch( 'core/notices' ).createNotice(
												'error', // Can be one of: success, info, warning, error.
												error.error, // Text string to display.
												{
													isDismissible: true, // Whether the user can dismiss the notice.
												}
											);
										}
									} );
								}}>
									{__( 'Save', 'buddyboss-pro' )}
								</Button>
								{meetingId < 1 || '' === meetingId ?
									<Button isTertiary onClick={() => {
										setMeetingFormType( '' )
									}}>
										{__( 'Cancel', 'buddyboss-pro' )}
									</Button>
									:
									''
								}
							</BaseControl>
						</Placeholder>

					</>
					:
					''
				}
				{'create' === meetingFormType ?
					<>
						<Placeholder icon="video-alt2" label={
							!external_meeting ?
								__( 'Create Meeting', 'buddyboss-pro' )
								:
								__( 'Existing Meeting', 'buddyboss-pro' )
						}
						             className="bp-meeting-block-create">
							<TextControl
								label=''
								type="hidden"
								value={meetingId}
							/>
							<TextControl
								label={__( 'Title', 'buddyboss-pro' )}
								value={title}
								onChange={setTitle}
							/>
							<BaseControl
								label={__( 'When', 'buddyboss-pro' )}
								className="bb-meeting-time-wrap"
							>
								<time dateTime={date( 'c', startDate )}>
									<Button
										icon="edit"
										isTertiary
										isLink
										onClick={() =>
											setIsPickerOpen(
												( _isPickerOpen ) => !_isPickerOpen
											)
										}>
										{moment( startDate ).format('MMMM DD, YYYY h:mm a')}
									</Button>
									{isPickerOpen && (
										<Popover onClose={setIsPickerOpen.bind( null, false )}>
											<DateTimePicker
												currentDate={startDate}
												onChange={setStartDate}
												is12Hour={true}
											/>
										</Popover>
									)}
								</time>
							</BaseControl>
							<SelectControl
								label={__( 'Timezone', 'buddyboss-pro' )}
								value={timezone}
								options={bpZoomMeetingBlock.timezones}
								onChange={setTimezone}
							/>
							<SelectControl
								label={__( 'Auto Recording', 'buddyboss-pro' )}
								value={autoRecording}
								options={auto_recording_options}
								onChange={setAutoRecording}
							/>
							<BaseControl className="bb-buttons-wrap">
								<Button
									className="submit-meeting"
									isPrimary
									onClick={( e ) => {
										const target = e.target;
										target.setAttribute( 'disabled', true );
										const meeting_data = {
											'_wpnonce': bpZoomMeetingBlock.bp_zoom_meeting_nonce,
											'bp-zoom-meeting-zoom-id': meetingId,
											'bp-zoom-meeting-start-date': startDate,
											'bp-zoom-meeting-timezone': timezone,
											'bp-zoom-meeting-duration': duration,
											'bp-zoom-meeting-password': password,
											'bp-zoom-meeting-recording': autoRecording,
											'bp-zoom-meeting-alt-host-ids': alt_hosts,
											'bp-zoom-meeting-title': title,
											'bp-zoom-meeting-description': description,
										};

										meeting_data['bp-zoom-meeting-type'] = 2;

										if ( recurring ) {
											if ( 1 === recurrence ) {
												if ( 'date' === end_time_select ) {
													meeting_data['bp-zoom-meeting-end-date-time'] = end_date_time;
												} else {
													meeting_data['bp-zoom-meeting-end-times'] = end_times;
												}
												meeting_data['bp-zoom-meeting-recurrence'] = 1;
												meeting_data['bp-zoom-meeting-end-time-select'] = end_time_select;
												meeting_data['bp-zoom-meeting-repeat-interval'] = repeat_interval;
												meeting_data['bp-zoom-meeting-type'] = 8;
											} else if ( 2 === recurrence ) {
												if ( weekly_days ) {
													meeting_data['bp-zoom-meeting-weekly-days'] = weekly_days;
												}
												if ( 'date' === end_time_select ) {
													meeting_data['bp-zoom-meeting-end-date-time'] = end_date_time;
												} else {
													meeting_data['bp-zoom-meeting-end-times'] = end_times;
												}
												meeting_data['bp-zoom-meeting-recurrence'] = 2;
												meeting_data['bp-zoom-meeting-end-time-select'] = end_time_select;
												meeting_data['bp-zoom-meeting-repeat-interval'] = repeat_interval;
												meeting_data['bp-zoom-meeting-type'] = 8;
											} else if ( 3 === recurrence ) {
												if ( 'day' === monthly_occurs_on ) {
													meeting_data['bp-zoom-meeting-monthly-day'] = monthly_day;
												} else if ( 'week' === monthly_occurs_on ) {
													meeting_data['bp-zoom-meeting-monthly-week'] = monthly_week;
													meeting_data['bp-zoom-meeting-monthly-week-day'] = monthly_week_day;
												}
												if ( 'date' === end_time_select ) {
													meeting_data['bp-zoom-meeting-end-date-time'] = end_date_time;
												} else {
													meeting_data['bp-zoom-meeting-end-times'] = end_times;
												}
												meeting_data['bp-zoom-meeting-recurrence'] = 3;
												meeting_data['bp-zoom-meeting-monthly-occurs-on'] = monthly_occurs_on;
												meeting_data['bp-zoom-meeting-end-time-select'] = end_time_select;
												meeting_data['bp-zoom-meeting-repeat-interval'] = repeat_interval;
												meeting_data['bp-zoom-meeting-type'] = 8;
											} else {
												meeting_data['bp-zoom-meeting-type'] = 3;
											}
										}

										if ( registration ) {
											meeting_data['bp-zoom-meeting-registration'] = 1;
											if ( meeting_data['bp-zoom-meeting-type'] === 8 ) {
												meeting_data['bp-zoom-meeting-registration-type'] = registration_type;
											}
										}

										if ( joinBeforeHost ) {
											meeting_data['bp-zoom-meeting-join-before-host'] = 1;
										}

										if ( hostVideo ) {
											meeting_data['bp-zoom-meeting-host-video'] = 1;
										}

										if ( participantsVideo ) {
											meeting_data['bp-zoom-meeting-participants-video'] = 1;
										}

										if ( muteParticipants ) {
											meeting_data['bp-zoom-meeting-mute-participants'] = 1;
										}

										if ( waitingRoom ) {
											meeting_data['bp-zoom-meeting-waiting-room'] = 1;
										}

										if ( authentication ) {
											meeting_data['bp-zoom-meeting-authentication'] = 1;
										}

										wp.ajax.send( 'zoom_meeting_block_add', {
											data: meeting_data,
											success: function ( response ) {
												if ( response.meeting.id ) {
													setMeetingId( response.meeting.id );
												}
												if ( typeof response.meeting.occurrences !== 'undefined' && response.meeting.occurrences.length ) {
													setOccurrences( response.meeting.occurrences );
												}
												target.removeAttribute( 'disabled' );
												wp.data.dispatch( 'core/notices' ).createNotice(
													'success', // Can be one of: success, info, warning, error.
													__( 'Meeting Updated.', 'buddyboss-pro' ), // Text string to display.
													{
														isDismissible: true, // Whether the user can dismiss the notice.
													}
												);
												setMeetingFormType( 'create' );
												//save post if is ok to save
												var editorInfo = wp.data.select( 'core/editor' );
												if ( editorInfo.isEditedPostSaveable() ) {
													if ( !editorInfo.isCurrentPostPublished() && ~[ 'draft', 'auto-draft' ].indexOf( editorInfo.getEditedPostAttribute( 'status' ) ) ) {
														wp.data.dispatch( 'core/editor' ).autosave();
													} else {
														wp.data.dispatch( 'core/editor' ).savePost();
													}
												}
											},
											error: function ( error ) {
												target.removeAttribute( 'disabled' );
												if ( typeof error.errors !== 'undefined' ) {
													for ( let er in error.errors ) {
														wp.data.dispatch( 'core/notices' ).createNotice(
															'error',
															error.errors[er].message, // Text string to display.
															{
																isDismissible: true, // Whether the user can dismiss the notice.
															}
														);
													}
												} else {
													wp.data.dispatch( 'core/notices' ).createNotice(
														'error', // Can be one of: success, info, warning, error.
														error.error, // Text string to display.
														{
															isDismissible: true, // Whether the user can dismiss the notice.
														}
													);
												}
											}
										} );
									}
									}>
									{__( 'Save Meeting', 'buddyboss-pro' )}
								</Button>
								{meetingId < 1 || '' === meetingId ?
									<Button isTertiary onClick={() => {
										setMeetingFormType( '' )
									}}>
										{__( 'Cancel', 'buddyboss-pro' )}
									</Button>
									:
									<Button isDestructive onClick={(e) => {
										const target = e.target;
										if ( confirm( 'Are you sure you want to delete this meeting?', 'buddyboss-pro' ) ) {

											target.setAttribute( 'disabled', true );
											const meeting_data = {
												'_wpnonce': bpZoomMeetingBlock.bp_zoom_meeting_nonce,
												'bp-zoom-meeting-zoom-id': meetingId,
											};

											wp.ajax.send( 'zoom_meeting_block_delete_meeting', {
												data: meeting_data,
												success: function () {
													wp.data.dispatch('core/block-editor').removeBlock(clientId);
													target.removeAttribute( 'disabled' );
													wp.data.dispatch( 'core/notices' ).createNotice(
														'success', // Can be one of: success, info, warning, error.
														__( 'Meeting Deleted.', 'buddyboss-pro' ), // Text string to display.
														{
															isDismissible: true, // Whether the user can dismiss the notice.
														}
													);
													var editorInfo = wp.data.select( 'core/editor' );
													// save post if is ok to save
													if ( editorInfo.isEditedPostSaveable() ) {
														if ( !editorInfo.isCurrentPostPublished() && ~[ 'draft', 'auto-draft' ].indexOf( editorInfo.getEditedPostAttribute( 'status' ) ) ) {
															wp.data.dispatch( 'core/editor' ).autosave();
														} else {
															wp.data.dispatch( 'core/editor' ).savePost();
														}
													}
												},
												error: function ( error ) {
													target.removeAttribute( 'disabled' );
													if ( typeof error.errors !== 'undefined' ) {
														for ( let er in error.errors ) {
															wp.data.dispatch( 'core/notices' ).createNotice(
																'error',
																error.errors[er].message, // Text string to display.
																{
																	isDismissible: true, // Whether the user can dismiss the notice.
																}
															);
														}
													} else {
														wp.data.dispatch( 'core/notices' ).createNotice(
															'error', // Can be one of: success, info, warning, error.
															error.error, // Text string to display.
															{
																isDismissible: true, // Whether the user can dismiss the notice.
															}
														);
													}
												}
											} );
										}
									}}>
										{__( 'Delete', 'buddyboss-pro' )}
									</Button>
								}
							</BaseControl>
						</Placeholder>
					</>
					:
					''
				}
				{'create' === meetingFormType ?
					<InspectorControls>
						<PanelBody
							title={__( 'Settings', 'buddyboss-pro' )}
							initialOpen={true}>
							<TextareaControl
								label={__( 'Description (optional)', 'buddyboss-pro' )}
								value={description}
								onChange={setDescription}
							/>
							<TextControl
								label={__( 'Passcode (optional)', 'buddyboss-pro' )}
								onChange={setPassword}
								value={password}
							/>
							<TextControl
								type="number"
								label={__( 'Duration (minutes)', 'buddyboss-pro' )}
								onChange={setDuration}
								value={duration}
							/>
							<TextControl
								label={__( 'Default Host', 'buddyboss-pro' )}
								type="text"
								disabled
								value={hostDisplayName}
							/>
							{
								host_user_type == 2
									?
									<TextControl
										label={__( 'Alternative Hosts', 'buddyboss-pro' )}
										onChange={setAltHosts}
										value={alt_hosts}
										placeholder={__( 'Example: mary@company.com', 'buddyboss-pro' )}
										help={__( 'Entered by email, comma separated. Each email added needs to match with a user in your Zoom account.', 'buddyboss-pro' )}
									/>
									:
									''
							}
							<CheckboxControl
								label={__( 'Start video when host joins', 'buddyboss-pro' )}
								checked={hostVideo}
								onChange={setHostVideo}
								className="bb-checkbox-wrap"
							/>
							<CheckboxControl
								label={__( 'Start video when participants join', 'buddyboss-pro' )}
								checked={participantsVideo}
								onChange={setParticipantsVideo}
								className="bb-checkbox-wrap"
							/>
							{
								host_user_type == 2
									?
									<>
										<CheckboxControl
											label={__( 'Require Registration', 'buddyboss-pro' )}
											checked={registration}
											onChange={setRegistration}
											className="bb-checkbox-wrap"
										/>
										{
											registration
												?
												<>
													<RadioControl
														selected={ registration_type }
														options={ [
															{ label: __( 'Attendees register once and can attend any of the occurrences', 'buddyboss-pro' ), value: 1 },
															{ label: __( 'Attendees need to register for each occurrence to attend', 'buddyboss-pro' ), value: 2 },
															{ label: __( 'Attendees register once and can choose one or more occurrences to attend', 'buddyboss-pro' ), value: 3 },
														] }
														onChange={setRegistrationType}
													/>
												</>
												:
												''
										}
									</>
									:
									''
							}
							<CheckboxControl
								label={__( 'Enable join before host', 'buddyboss-pro' )}
								checked={joinBeforeHost}
								onChange={setJoinBeforeHost}
								className="bb-checkbox-wrap"
							/>
							<CheckboxControl
								label={__( 'Mute participants upon entry', 'buddyboss-pro' )}
								checked={muteParticipants}
								onChange={setMuteParticipants}
								className="bb-checkbox-wrap"
							/>
							<CheckboxControl
								label={__( 'Enable waiting room', 'buddyboss-pro' )}
								checked={waitingRoom}
								onChange={setWaitingRoom}
								className="bb-checkbox-wrap"
							/>
							<CheckboxControl
								label={__( 'Only authenticated users can join', 'buddyboss-pro' )}
								checked={authentication}
								onChange={setAuthentication}
								className="bb-checkbox-wrap"
							/>
						</PanelBody>
						<PanelBody
							title={__( 'Recurring Options', 'buddyboss-pro' )}
							initialOpen={false}>
							<CheckboxControl
								label={__( 'Recurring Meeting', 'buddyboss-pro' )}
								checked={recurring}
								onChange={setRecurring}
							/>
							{true === recurring ?
								<>
									<SelectControl
										label={__( 'Recurrence', 'buddyboss-pro' )}
										value={recurrence}
										options={[
											{ label: __( 'Daily', 'buddyboss-pro' ), value: 1 },
											{ label: __( 'Weekly', 'buddyboss-pro' ), value: 2 },
											{ label: __( 'Monthly', 'buddyboss-pro' ), value: 3 },
										]}
										onChange={setRecurrence}
									/>
									<SelectControl
										label={__( 'Repeat every', 'buddyboss-pro' )}
										value={repeat_interval}
										options={repeat_interval_options}
										onChange={setRepeatInterval}
										help={repeat_every}
									/>
									{2 === recurrence
										?
										<SelectControl
											label={__( 'Days', 'buddyboss-pro' )}
											value={weekly_days}
											options={[
												{ label: __( 'Sunday', 'buddyboss-pro' ), value: 1 },
												{ label: __( 'Monday', 'buddyboss-pro' ), value: 2 },
												{ label: __( 'Tuesday', 'buddyboss-pro' ), value: 3 },
												{ label: __( 'Wednesday', 'buddyboss-pro' ), value: 4 },
												{ label: __( 'Thursday', 'buddyboss-pro' ), value: 5 },
												{ label: __( 'Friday', 'buddyboss-pro' ), value: 6 },
												{ label: __( 'Saturday', 'buddyboss-pro' ), value: 7 },
											]}
											onChange={setWeeklyDays}
											multiple
										/>
										:
										''
									}
									{3 === recurrence
										?
										<>
											<SelectControl
												label={__( 'Occures on', 'buddyboss-pro' )}
												value={monthly_occurs_on}
												options={[
													{ label: __( 'Day of the month', 'buddyboss-pro' ), value: 'day' },
													{
														label: __( 'Week of the month', 'buddyboss-pro' ),
														value: 'week'
													},
												]}
												onChange={setMonthlyOccursOn}
											/>
											{'day' === monthly_occurs_on
												?
												<SelectControl
													label={__( 'Day', 'buddyboss-pro' )}
													value={monthly_day}
													options={[
														{ label: __( '1', 'buddyboss-pro' ), value: 1 },
														{ label: __( '2', 'buddyboss-pro' ), value: 2 },
														{ label: __( '3', 'buddyboss-pro' ), value: 3 },
														{ label: __( '4', 'buddyboss-pro' ), value: 4 },
														{ label: __( '5', 'buddyboss-pro' ), value: 5 },
														{ label: __( '6', 'buddyboss-pro' ), value: 6 },
														{ label: __( '7', 'buddyboss-pro' ), value: 7 },
														{ label: __( '8', 'buddyboss-pro' ), value: 8 },
														{ label: __( '9', 'buddyboss-pro' ), value: 9 },
														{ label: __( '10', 'buddyboss-pro' ), value: 10 },
														{ label: __( '11', 'buddyboss-pro' ), value: 11 },
														{ label: __( '12', 'buddyboss-pro' ), value: 12 },
														{ label: __( '13', 'buddyboss-pro' ), value: 13 },
														{ label: __( '14', 'buddyboss-pro' ), value: 14 },
														{ label: __( '15', 'buddyboss-pro' ), value: 15 },
														{ label: __( '16', 'buddyboss-pro' ), value: 16 },
														{ label: __( '17', 'buddyboss-pro' ), value: 17 },
														{ label: __( '18', 'buddyboss-pro' ), value: 18 },
														{ label: __( '19', 'buddyboss-pro' ), value: 19 },
														{ label: __( '20', 'buddyboss-pro' ), value: 20 },
														{ label: __( '21', 'buddyboss-pro' ), value: 21 },
														{ label: __( '22', 'buddyboss-pro' ), value: 22 },
														{ label: __( '23', 'buddyboss-pro' ), value: 23 },
														{ label: __( '24', 'buddyboss-pro' ), value: 24 },
														{ label: __( '25', 'buddyboss-pro' ), value: 25 },
														{ label: __( '26', 'buddyboss-pro' ), value: 26 },
														{ label: __( '27', 'buddyboss-pro' ), value: 27 },
														{ label: __( '28', 'buddyboss-pro' ), value: 28 },
														{ label: __( '29', 'buddyboss-pro' ), value: 29 },
														{ label: __( '30', 'buddyboss-pro' ), value: 30 },
														{ label: __( '31', 'buddyboss-pro' ), value: 31 },
													]}
													onChange={setMonthlyDay}
													help={__( 'of the month', 'buddyboss-pro' )}
												/>
												:
												<>
													<SelectControl
														value={monthly_week}
														options={[
															{ label: __( 'First', 'buddyboss-pro' ), value: 1 },
															{ label: __( 'Second', 'buddyboss-pro' ), value: 2 },
															{ label: __( 'Third', 'buddyboss-pro' ), value: 3 },
															{ label: __( 'Fourth', 'buddyboss-pro' ), value: 4 },
															{ label: __( 'Last', 'buddyboss-pro' ), value: -1 },
														]}
														onChange={setMonthlyWeek}
													/>
													<SelectControl
														value={monthly_week_day}
														options={[
															{ label: __( 'Sunday', 'buddyboss-pro' ), value: 1 },
															{ label: __( 'Monday', 'buddyboss-pro' ), value: 2 },
															{ label: __( 'Tuesday', 'buddyboss-pro' ), value: 3 },
															{ label: __( 'Wednesday', 'buddyboss-pro' ), value: 4 },
															{ label: __( 'Thursday', 'buddyboss-pro' ), value: 5 },
															{ label: __( 'Friday', 'buddyboss-pro' ), value: 6 },
															{ label: __( 'Saturday', 'buddyboss-pro' ), value: 7 },
														]}
														onChange={setMonthlyWeekDay}
														help={__( 'of the month', 'buddyboss-pro' )}
													/>
												</>
											}
										</>
										:
										''
									}
									{4 !== recurrence
										?
										<>
											<SelectControl
												label={__( 'End by', 'buddyboss-pro' )}
												value={end_time_select}
												options={[
													{ label: __( 'Date', 'buddyboss-pro' ), value: 'date' },
													{ label: __( 'Occurrences', 'buddyboss-pro' ), value: 'times' },
												]}
												onChange={setEndTimeSelect}
											/>
											{'date' == end_time_select ?
												<time dateTime={date( 'c', end_date_time )}>
													<Button
														icon="edit"
														isTertiary
														isLink
														onClick={() =>
															setIsRecurrencePickerOpen(
																( isRecurrencePickerOpen ) => !isRecurrencePickerOpen
															)
														}>
														{moment( end_date_time ).format('MMMM DD, YYYY')}
													</Button>
													{isRecurrencePickerOpen && (
														<Popover
															onClose={setIsRecurrencePickerOpen.bind( null, false )}>
															<DatePicker
																currentDate={end_date_time}
																onChange={setEndDateTime}
															/>
														</Popover>
													)}
												</time>
												:
												''
											}
											{'times' == end_time_select ?
												<SelectControl
													label={__( 'End After', 'buddyboss-pro' )}
													value={end_times}
													help={__( 'occurences', 'buddyboss-pro' )}
													options={[
														{ label: __( '1', 'buddyboss-pro' ), value: 1 },
														{ label: __( '2', 'buddyboss-pro' ), value: 2 },
														{ label: __( '3', 'buddyboss-pro' ), value: 3 },
														{ label: __( '4', 'buddyboss-pro' ), value: 4 },
														{ label: __( '5', 'buddyboss-pro' ), value: 5 },
														{ label: __( '6', 'buddyboss-pro' ), value: 6 },
														{ label: __( '7', 'buddyboss-pro' ), value: 7 },
														{ label: __( '8', 'buddyboss-pro' ), value: 8 },
														{ label: __( '9', 'buddyboss-pro' ), value: 9 },
														{ label: __( '10', 'buddyboss-pro' ), value: 10 },
														{ label: __( '11', 'buddyboss-pro' ), value: 11 },
														{ label: __( '12', 'buddyboss-pro' ), value: 12 },
														{ label: __( '13', 'buddyboss-pro' ), value: 13 },
														{ label: __( '14', 'buddyboss-pro' ), value: 14 },
														{ label: __( '15', 'buddyboss-pro' ), value: 15 },
														{ label: __( '16', 'buddyboss-pro' ), value: 16 },
														{ label: __( '17', 'buddyboss-pro' ), value: 17 },
														{ label: __( '18', 'buddyboss-pro' ), value: 18 },
														{ label: __( '19', 'buddyboss-pro' ), value: 19 },
														{ label: __( '20', 'buddyboss-pro' ), value: 20 },
													]}
													onChange={setEndTimes}
												/>
												:
												''
											}
										</>
										:
										''
									}
								</>
								:
								''}
						</PanelBody>
						{true === recurring && occurrences.length ?
							<PanelBody
								title={__( 'Occurrences', 'buddyboss-pro' )}
								initialOpen={false}>
								{
									occurrences.map( ( occurrence ) => {
										let nowDate = new Date( bpZoomMeetingBlock.wp_date_time );
										let selectedDate = new Date( occurrence.start_time );
										if ( nowDate.getTime() > selectedDate.getTime() || 'deleted' === occurrence.status ) {
											return '';
										}
										return <Fragment key={occurrence.occurrence_id}>
											<Text as="p">
												{moment( occurrence.start_time ).format('MMMM DD, YYYY h:mm a')}
											</Text>
											<Button
												isLink
												className="edit-occurrences-button"
												onClick={() => {
													setOccurrenceEdit( true );
													setCurrentOccurrence( occurrence );
												}
												}>
												{__( 'Edit', 'buddyboss-pro' )}
											</Button>
											<Button isLink="true" className="edit-occurrences-button"
											        onClick={(e) => {
												        const target = e.target;

												        if ( ! confirm( bpZoomMeetingBlock.delete_occurrence_confirm_str ) ) {
												        	return false;
												        }

												        target.setAttribute( 'disabled', true );
												        const meeting_data = {
													        '_wpnonce': bpZoomMeetingBlock.bp_zoom_meeting_nonce,
													        'bp-zoom-meeting-zoom-id': meetingId,
													        'bp-zoom-meeting-occurrence-id': occurrence.occurrence_id,
												        };

												        wp.ajax.send( 'zoom_meeting_block_delete_occurrence', {
													        data: meeting_data,
													        success: function () {
													        	setOccurrences(occurrences.filter(function( obj ) {
															        return obj.occurrence_id !== occurrence.occurrence_id;
														        }));
														        setOccurrenceEdit( false );
														        target.removeAttribute( 'disabled' );
														        wp.data.dispatch( 'core/notices' ).createNotice(
															        'success', // Can be one of: success, info, warning, error.
															        __( 'Occurrence Deleted.', 'buddyboss-pro' ), // Text string to display.
															        {
																        isDismissible: true, // Whether the user can dismiss the notice.
															        }
														        );
														        var editorInfo = wp.data.select( 'core/editor' );
														        // save post if is ok to save
														        if ( editorInfo.isEditedPostSaveable() ) {
															        if ( !editorInfo.isCurrentPostPublished() && ~[ 'draft', 'auto-draft' ].indexOf( editorInfo.getEditedPostAttribute( 'status' ) ) ) {
																        wp.data.dispatch( 'core/editor' ).autosave();
															        } else {
																        wp.data.dispatch( 'core/editor' ).savePost();
															        }
														        }
													        },
													        error: function ( error ) {
														        target.removeAttribute( 'disabled' );
														        if ( typeof error.errors !== 'undefined' ) {
															        for ( let er in error.errors ) {
																        wp.data.dispatch( 'core/notices' ).createNotice(
																	        'error',
																	        error.errors[er].message, // Text string to display.
																	        {
																		        isDismissible: true, // Whether the user can dismiss the notice.
																	        }
																        );
															        }
														        } else {
															        wp.data.dispatch( 'core/notices' ).createNotice(
																        'error', // Can be one of: success, info, warning, error.
																        error.error, // Text string to display.
																        {
																	        isDismissible: true, // Whether the user can dismiss the notice.
																        }
															        );
														        }
													        }
												        } );
											}
											}>
												{__( 'Delete', 'buddyboss-pro' )}
											</Button>
											{
												occurrence_edit && current_occurrence && current_occurrence.occurrence_id === occurrence.occurrence_id
													?
													<Fragment>
														<DateTimePicker
															is12Hour={true}
															currentDate={current_occurrence_start_time}
															onChange={setOccurrenceStartTime}
														/>
														<TextControl
															type="number"
															label={__( 'Duration (minutes)', 'buddyboss-pro' )}
															onChange={setOccurrenceDuration}
															value={current_occurrence_duration}
														/>
														<BaseControl className="bb-buttons-wrap">
															<Button
																isPrimary
																className="submit-meeting"
																onClick={(e) => {
																	const target = e.target;
																	target.setAttribute( 'disabled', true );
																	const meeting_data = {
																		'_wpnonce': bpZoomMeetingBlock.bp_zoom_meeting_nonce,
																		'bp-zoom-meeting-zoom-id': meetingId,
																		'bp-zoom-meeting-occurrence-id': current_occurrence.occurrence_id,
																		'bp-zoom-meeting-start-time': current_occurrence_start_time,
																		'bp-zoom-meeting-timezone': timezone,
																		'bp-zoom-meeting-duration': current_occurrence_duration,
																		'bp-zoom-meeting-recording': autoRecording,
																		'bp-zoom-meeting-alt-host-ids': alt_hosts,
																	};

																	if ( joinBeforeHost ) {
																		meeting_data['bp-zoom-meeting-join-before-host'] = 1;
																	}

																	if ( hostVideo ) {
																		meeting_data['bp-zoom-meeting-host-video'] = 1;
																	}

																	if ( participantsVideo ) {
																		meeting_data['bp-zoom-meeting-participants-video'] = 1;
																	}

																	if ( muteParticipants ) {
																		meeting_data['bp-zoom-meeting-mute-participants'] = 1;
																	}

																	if ( waitingRoom ) {
																		meeting_data['bp-zoom-meeting-waiting-room'] = 1;
																	}

																	if ( authentication ) {
																		meeting_data['bp-zoom-meeting-authentication'] = 1;
																	}

																	wp.ajax.send( 'zoom_meeting_block_update_occurrence', {
																		data: meeting_data,
																		success: function () {
																			for ( var o_index in occurrences ) {
																				if ( occurrences[o_index].occurrence_id === current_occurrence.occurrence_id ) {
																					occurrences[o_index].duration = current_occurrence_duration;
																					occurrences[o_index].start_time = current_occurrence_start_time;
																					break;
																				}
																			}
																			setOccurrences( occurrences );
																			setOccurrenceEdit( false );
																			target.removeAttribute( 'disabled' );
																			wp.data.dispatch( 'core/notices' ).createNotice(
																				'success', // Can be one of: success, info, warning, error.
																				__( 'Meeting Updated.', 'buddyboss-pro' ), // Text string to display.
																				{
																					isDismissible: true, // Whether the user can dismiss the notice.
																				}
																			);
																			var editorInfo = wp.data.select( 'core/editor' );
																			// save post if is ok to save
																			if ( editorInfo.isEditedPostSaveable() ) {
																				if ( !editorInfo.isCurrentPostPublished() && ~[ 'draft', 'auto-draft' ].indexOf( editorInfo.getEditedPostAttribute( 'status' ) ) ) {
																					wp.data.dispatch( 'core/editor' ).autosave();
																				} else {
																					wp.data.dispatch( 'core/editor' ).savePost();
																				}
																			}
																		},
																		error: function ( error ) {
																			target.removeAttribute( 'disabled' );
																			if ( typeof error.errors !== 'undefined' ) {
																				for ( let er in error.errors ) {
																					wp.data.dispatch( 'core/notices' ).createNotice(
																						'error',
																						error.errors[er].message, // Text string to display.
																						{
																							isDismissible: true, // Whether the user can dismiss the notice.
																						}
																					);
																				}
																			} else {
																				wp.data.dispatch( 'core/notices' ).createNotice(
																					'error', // Can be one of: success, info, warning, error.
																					error.error, // Text string to display.
																					{
																						isDismissible: true, // Whether the user can dismiss the notice.
																					}
																				);
																			}
																		}
																	} );
																}}>
																{__( 'Save', 'buddyboss-pro' )}
															</Button>
															<Button isTertiary onClick={() => {
																setOccurrenceEdit( false );
															}}>
																{__( 'Cancel', 'buddyboss-pro' )}
															</Button>
														</BaseControl>
													</Fragment>
													:
													''
											}

										</Fragment>
									} )
								}
							</PanelBody>
							:
							''}
					</InspectorControls>
					:
					''
				}
			</>
		);
	},
} );

/**
 * Get meeting blocks in current editor
 *
 * @return {[]} Array of meeting blocks
 */
export const getMeetingBlocks = () => {
	const editorBlocks = wp.data.select( 'core/block-editor' ).getBlocks(),
		meetingBlocks = [];
	let i = 0;

	for ( i in editorBlocks ) {
		if ( editorBlocks[i].isValid && editorBlocks[i].name === 'bp-zoom-meeting/create-meeting' ) {
			meetingBlocks.push( editorBlocks[i] );
		}
	}
	return meetingBlocks;
};

wp.domReady( function () {
	var postSaveButtonClasses = '.editor-post-publish-button';
	jQuery( document ).on( 'click', postSaveButtonClasses, function ( e ) {
		e.stopPropagation();
		e.preventDefault();
		let meetingBlocks = getMeetingBlocks();
		if ( meetingBlocks.length ) {
			for ( let i in meetingBlocks ) {
				jQuery( '#block-' + meetingBlocks[i].clientId ).find( '.submit-meeting' ).trigger( 'click' );
			}
		}
		//wp.data.dispatch( 'core/editor' ).lockPostSaving( 'bpZoomMeetingBlocks' );
	} )
} )

// const unsubscribe = wp.data.subscribe(function () {
//     let select = wp.data.select('core/editor');
//     var isSavingPost = select.isSavingPost();
//     var isAutosavingPost = select.isAutosavingPost();
//     if (isSavingPost && !isAutosavingPost) {
//         unsubscribe();
//         wp.data.dispatch('core/notices').createNotice(
//             'error', // Can be one of: success, info, warning, error.
//             __( 'Please save the meeting.', 'buddyboss-pro' ), // Text string to display.
//             {
//                 isDismissible: true, // Whether the user can dismiss the notice.
//             }
//         );
//     }
// });

/**
 * A compare helper for lodash's difference by
 */
const compareBlocks = ( block ) => { return block.clientId };

/**
 * A change listener for blocks
 *
 * The subscribe on the 'core/editor' getBlocks() function fires on any change,
 * not just additions/removals. Therefore we actually compare the array with a
 * previous state and look for changes in length or uid.
 */
const onBlocksChangeListener = ( selector, listener ) => {
	let previousBlocks = selector();
	return () => {
		const selectedBlocks = selector();

		if( selectedBlocks.length !== previousBlocks.length ) {
			listener( selectedBlocks, previousBlocks );
			previousBlocks = selectedBlocks;
		} else if ( differenceBy( selectedBlocks, previousBlocks, compareBlocks ).length ) {
			listener( selectedBlocks, previousBlocks, differenceBy( selectedBlocks, previousBlocks, compareBlocks ) );
			previousBlocks = selectedBlocks;
		}
	}
}

let blockEditorLoaded = false;
let blockEditorLoadedInterval = setInterval( function () {
	if ( document.getElementById( 'post-title-0' ) || document.getElementById( 'post-title-1' ) ) {/*post-title-1 is ID of Post Title Textarea*/
		blockEditorLoaded = true;

		/**
		 * Subscribe to block data
		 *
		 * This function subscribes to block data, compares old and new states upon
		 * change and fires actions accordingly.
		 */
		wp.data.subscribe( onBlocksChangeListener( wp.data.select( 'core/block-editor' ).getBlocks, ( blocks, oldBlocks, difference = null ) => {
			let addedBlocks = differenceBy( blocks, oldBlocks, compareBlocks );
			let deletedBlocks = differenceBy( oldBlocks, blocks, compareBlocks );

			if ( oldBlocks.length == blocks.length && difference ) {

				// A block has been deleted
				for ( var i in deletedBlocks ) {
					const block = deletedBlocks[i];
					const actionName = 'blocks.transformed.from.' + camelCase( block.name );
					doAction( actionName, block );
				}

				// A block has been added
				for ( var i in addedBlocks ) {
					const block = addedBlocks[i];
					const actionName = 'blocks.transformed.to.' + camelCase( block.name );
					doAction( actionName, block );
				}
			}

			// A block has been added
			for ( var i in addedBlocks ) {
				const block = addedBlocks[i];
				const actionName = 'blocks.added.' + camelCase( block.name );
				doAction( actionName, block );
			}

			// A block has been deleted
			for ( var i in deletedBlocks ) {
				const block = deletedBlocks[i];
				const actionName = 'blocks.removed.' + camelCase( block.name );
				doAction( actionName, block );
			}
		} ) );
	}
	if ( blockEditorLoaded ) {
		clearInterval( blockEditorLoadedInterval );
	}
}, 500 );

/**
 * An action listener, which fires the deletion of the metadata
 * once the remove action is seen.
 */
addAction('blocks.added.bpZoomMeetingCreateMeeting', 'bpZoomMeetingCreateMeeting/addBlock', ( block ) => {
	block.attributes.meetingId = '';
	block.attributes.id = '';
});

// addAction('blocks.removed.bpZoomMeetingCreateMeeting', 'bpZoomMeetingCreateMeeting/removeBlock', ( block ) => {
// 	console.log('remove');
// });
