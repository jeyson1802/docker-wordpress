/* jshint browser: true */
/* global bp, bp_zoom_meeting_vars, bp_select2 */
/* @version 1.0.0 */
window.bp = window.bp || {};

( function( exports, $ ) {

    /**
	 * [Zoom description]
     *
	 * @type {Object}
	 */
	bp.Zoom = {
		/**
		 * [start description]
         *
		 * @return {[type]} [description]
		 */
		start: function () {
			this.setupGlobals();

			// Listen to events ("Add hooks!")
			this.addListeners();
		},

		/**
		 * [setupGlobals description]
         *
		 * @return {[type]} [description]
		 */
		setupGlobals: function () {
            this.bp_zoom_ajax = false;
            this.bp_zoom_meeting_container_elem = '#bp-zoom-meeting-container';
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			$( '#meetings-list' ).scroll( 'scroll', this.scrollMeetings.bind( this ) );
			$( '#meetings-list' ).on( 'click', '.load-more a', this.loadMoreMeetings.bind( this ) );
			$( document ).on( 'click', '#meetings-list .meeting-item, #bp-zoom-meeting-cancel-edit', this.loadSingleMeeting.bind( this ) );
			$( document ).on( 'click', '.bp-back-to-meeting-list', this.backToMeetingList.bind( this ) );
			$( document ).on( 'click', '.bp-close-create-meeting-form', this.backToMeetingList.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-create-meeting-button', this.loadCreateMeeting.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-meeting-edit-button', this.loadEditMeeting.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-meeting-occurrence-edit-button', this.openOccurrenceEditPopup.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-meeting-occurrence-delete-button', this.openOccurrenceDeletePopup.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-only-this-meeting-edit', this.loadEditOccurrence.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-all-meeting-edit', this.loadEditMeeting.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-meeting-form-submit', this.updateMeeting.bind( this ) );
			$( document ).on( 'click', '.bp-zoom-delete-meeting', this.deleteMeeting.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-only-this-meeting-delete', this.deleteOnlyThisMeeting.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-all-meeting-delete', this.deleteAllMeetingOccurrences.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-single-meeting .toggle-password', this.togglePassword.bind( this ) );
			$( document ).on( 'click', '.recording-list-row .toggle-password', this.toggleRecordingPassword.bind( this ) );
			$( document ).on( 'click', '#copy-invitation-details', this.copyInvitationDetails.bind( this ) );
			$( document ).on( 'click', '#copy-download-link', this.copyDownloadLink.bind( this ) );
			$( document ).on( 'click', '.play_btn, .bb-shared-screen', this.openRecordingModal.bind( this ) );
			$( document ).on( 'click', '.bb-close-model', this.closeRecordingModal.bind( this ) );
			$( document ).on( 'click', '.meeting-actions-anchor', this.openMeetingActions.bind( this ) );
			$( document ).on( 'submit', '#bp-zoom-meeting-container #bp_zoom_meeting_search_form', this.searchMeetingActions.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-meeting-container #bp-zoom-meeting-recorded-switch-checkbox', this.searchMeetingActions.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-meeting-auto-recording', this.toggleAutoRecording.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-meeting-recurring', this.toggleRecurring.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-meeting-recurrence', this.toggleRecurrence.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-meeting-start-date', this.toggleDates.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-meeting-repeat-interval', this.toggleRepeatInterval.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-meeting-registration', this.toggleRegistration.bind( this ) );
			$( document ).on( 'ready', this.documentReady.bind( this ) );
			$( document ).on( 'bp_ajax_request', this.bp_ajax_request.bind( this ) );
			$( document ).on( 'change', '.bp-zoom-recordings-dates', this.scrollToRecordings.bind(this) );
			$( document ).on( 'change', '#bp-zoom-meeting-timezone', this.changeTimezone.bind(this) );

			$( document ).on( 'click', '#meetings-sync', this.syncGroupMeetings.bind( this ) );

			document.addEventListener( 'keyup', this.checkPressedKey.bind( this ) );

			$( document ).on( 'click', 'body.zoom', function ( event ) {

				if ( $( event.target ).hasClass( 'meeting-actions-anchor' ) || $( event.target ).parent().hasClass( 'meeting-actions-anchor' ) ) {
					return event;
				} else {
					$( '.meeting-actions-list.open' ).removeClass( 'open' );
				}

			} );

			$( document ).on( 'click', '.bp-toggle-meeting-password', function ( e ) {
				e.preventDefault();
				var $this = $( this );
				var $input = $this.next( '#bp-zoom-meeting-password' );
				$this.toggleClass( 'bb-eye' );
				if ( $this.hasClass( 'bb-eye' ) ) {
					$input.attr( 'type', 'text' );
				} else {
					$input.attr( 'type', 'password' );
				}
			} );

			this.mask_meeting_id();
		},

		bp_ajax_request: function() {
			this.triggerCountdowns();
			this.triggerLibsOnForm();
			this.mask_meeting_id();
		},

		documentReady: function()  {
			this.triggerCountdowns();
			this.triggerLibsOnForm();
			this.triggerFetchRecordings();
		},

		scrollToRecordings: function ( e ) {
			var target = $( e.target );
			var scrollToElement = $( '.mfp-content .bp-zoom-block-show-recordings' ).find( '[data-recorded-date="' + target.val() + '"]' );
			if ( scrollToElement.length ) {
				var total_scroll = scrollToElement.outerHeight();
				$( scrollToElement.nextAll() ).each( function () {
					total_scroll += $( this ).outerHeight();
				} );
				$( '.mfp-content .recording-list-row-wrap' ).animate( {
					scrollTop: $( '.mfp-content .recording-list-row-wrap' )[0].scrollHeight - total_scroll
				}, 100 );
			}
		},

		triggerFetchRecordings:function() {
			var recording_elements = [];
			$('.bp-zoom-meeting-recording-fetch').each(function(key,element){
				var meeting_id = $(element).data('meeting-id');
				recording_elements.push(meeting_id);
			});

			this.fetchRecording(recording_elements,0);
		},

		fetchRecording: function(recording_elements,index) {
			var self = this;
			if ( typeof recording_elements[index] !== 'undefined' ) {
				var title = $(document).find('#bp-zoom-meeting-recording-'+ recording_elements[index]).data('title');
				$.ajax({
					type: 'GET',
					url: bp_zoom_meeting_vars.ajax_url,
					data: {action: 'zoom_meeting_recordings', meeting_id: recording_elements[index], title: title},
					success: function (response) {
						if ( response.success && typeof response.data !== 'undefined' ) {
							var recording_hidden = $(document).find('#bp-zoom-meeting-recording-' + recording_elements[index]);
							recording_hidden.replaceWith(response.data.contents);
							jQuery('.show-recordings').magnificPopup({
								type: 'inline',
								midClick: true,
								callbacks: {
									open: function () {
										var mf_content = $('.mfp-content');
										var meeting_date = $( '#bp-zoom-single-meeting' ).data( 'meeting-start-date' );
										var scrollToElement = mf_content.find( '.bp-zoom-block-show-recordings' ).find( '[data-recorded-date="' + meeting_date + '"]' );
										if ( !scrollToElement.length ) {
											meeting_date = new Date( meeting_date );
											mf_content.find( '[data-recorded-date]' ).each( function () {
												var row_recording_date = $( this ).data( 'recorded-date' );
												var row_recording_date_obj = new Date( row_recording_date );
												if ( row_recording_date_obj.getTime() === meeting_date.getTime() ) {
													scrollToElement = mf_content.find( '.bp-zoom-block-show-recordings' ).find( '[data-recorded-date="' + row_recording_date + '"]' );
													return false;
												}
											} );
											if ( !scrollToElement.length ) {
												mf_content.find( '[data-recorded-date]' ).each( function () {
													var row_recording_date = $( this ).data( 'recorded-date' );
													var row_recording_date_obj = new Date( row_recording_date );
													if ( row_recording_date_obj.getTime() >= meeting_date.getTime() ) {
														scrollToElement = mf_content.find( '.bp-zoom-block-show-recordings' ).find( '[data-recorded-date="' + row_recording_date + '"]' );
														return false;
													}
												} );
											}
										}
										if ( scrollToElement.length ) {
											var total_scroll = scrollToElement.outerHeight();
											$( scrollToElement.nextAll() ).each( function () {
												total_scroll += $( this ).outerHeight();
											} );
											mf_content.find( '.recording-list-row-wrap' ).animate( {
												scrollTop: mf_content.find( '.recording-list-row-wrap' )[0].scrollHeight - total_scroll
											}, 100 );
											$('.bp-zoom-recordings-dates').val(scrollToElement.data('recorded-date'));
										}
									},
								}
							});
							index = index + 1;
							self.fetchRecording(recording_elements, index);
						}
					}
				});
			}
		},

		triggerCountdowns: function() {
			var countdowns = $('.bp_zoom_countdown');
			if ( countdowns.length ) {
				countdowns.each(function () {
					var ts = $(this).data('timer');
					var reload = $(this).data('reload');
					ts = parseInt(ts) * 1000;
					$(this).countdown({
						timestamp: ts,
						callback: function (days, hours, minutes, seconds) {
							var summaryTime = days + hours + minutes + seconds;
							if (summaryTime === 0 && reload === 1) {
								window.location.reload();
							}
						}
					});
				});
			}
		},

		triggerLibsOnForm: function() {
			$('.copy-invitation-link').magnificPopup({
				type: 'inline',
				midClick: true,
			});

			jQuery('.show-recordings').magnificPopup({
				type: 'inline',
				midClick: true,
				callbacks: {
					open: function () {
						var mf_content = $('.mfp-content');
						var meeting_date = $( '#bp-zoom-single-meeting' ).data( 'meeting-start-date' );
						var scrollToElement = mf_content.find( '.bp-zoom-block-show-recordings' ).find( '[data-recorded-date="' + meeting_date + '"]' );
						if ( !scrollToElement.length ) {
							meeting_date = new Date( meeting_date );
							mf_content.find( '[data-recorded-date]' ).each( function () {
								var row_recording_date = $( this ).data( 'recorded-date' );
								var row_recording_date_obj = new Date( row_recording_date );
								if ( row_recording_date_obj.getTime() === meeting_date.getTime() ) {
									scrollToElement = mf_content.find( '[data-recorded-date="' + row_recording_date + '"]' );
									return false;
								}
							} );
							if ( !scrollToElement.length ) {
								mf_content.find( '[data-recorded-date]' ).each( function () {
									var row_recording_date = $( this ).data( 'recorded-date' );
									var row_recording_date_obj = new Date( row_recording_date );
									if ( row_recording_date_obj.getTime() >= meeting_date.getTime() ) {
										scrollToElement = mf_content.find( '.bp-zoom-block-show-recordings' ).find( '[data-recorded-date="' + row_recording_date + '"]' );
										return false;
									}
								} );
							}
						}
						if ( scrollToElement.length ) {
							var total_scroll = scrollToElement.outerHeight();
							$( scrollToElement.nextAll() ).each( function () {
								total_scroll += $( this ).outerHeight();
							} );
							mf_content.find( '.recording-list-row-wrap' ).animate( {
								scrollTop: mf_content.find( '.recording-list-row-wrap' )[0].scrollHeight - total_scroll
							}, 100 );
							$('.bp-zoom-recordings-dates').val(scrollToElement.data('recorded-date'));
						}
					},
				}
			});

			jQuery('.show-meeting-details').magnificPopup({
				type: 'inline',
				midClick: true,
				callbacks: {
					beforeClose: function() {
						if ( this.content.hasClass('copy-invitation-popup-block') ) {
							$('.mfp-close').show();
						}
					},
				}
			});

			var meeting_wrapper = $('#bp-zoom-single-meeting-wrapper');
			if (typeof jQuery.fn.datetimepicker !== 'undefined') {

				meeting_wrapper.find('#bp-zoom-meeting-start-date').datetimepicker({
					format: 'Y-m-d',
					timepicker: false,
					mask: true,
					minDate: 0,
                    yearStart: new Date().getFullYear(),
					defaultDate: new Date(),
                    scrollMonth: false,
                    scrollTime: false,
                    scrollInput: false,
					onSelectDate: function (date,element) {
						meeting_wrapper.find('#bp-zoom-meeting-end-date-time').datetimepicker({
							minDate: element.val(),
						});
					}
				});

				meeting_wrapper.find('#bp-zoom-meeting-end-date-time').datetimepicker({
					format: 'Y-m-d',
					timepicker: false,
					mask: true,
					minDate: 0,
					defaultDate: new Date().setDate(new Date().getDate() + 6),
					scrollMonth: false,
					scrollTime: false,
					scrollInput: false,
				});

				meeting_wrapper.find('#bp-zoom-meeting-start-time').datetimepicker({
					format: 'h:i',
					formatTime:	'h:i',
					datepicker: false,
					hours12: true,
					step: 30,
				});

                var options = {
                    placeholder: 'hh:mm',
                    translation: {
                        'P': {
                            pattern: /0|1/, optional: false
                        },
                        'Q': {
                            pattern: /0|1|2/, optional: false
                        },
                        'X': {
                            pattern: /0|[1-9]/, optional: false
                        },
                        'Y': {
                            pattern: /[0-5]/, optional: false
                        },
                        'Z': {
                            pattern: /[0-9]/, optional: false
                        },
                    },
                    onKeyPress: function(cep, e, field, options) {
                        var masks = [ 'PX:YZ', 'PQ:YZ' ];
                        var mask = ( cep.length > 1 && cep.substr(0,1) > 0 ) ? masks[1] : masks[0];
                        meeting_wrapper.find('#bp-zoom-meeting-start-time').mask(mask, options);
                    }
                };

                meeting_wrapper.find('#bp-zoom-meeting-start-time').mask( 'PX:YZ', options );
			}

			if (typeof jQuery.fn.select2 !== 'undefined') {
				meeting_wrapper.find('#bp-zoom-meeting-timezone').select2({
					minimumInputLength: 0,
					closeOnSelect: true,
					language: (typeof bp_select2 !== 'undefined' && typeof bp_select2.lang !== 'undefined') ? bp_select2.lang : 'en',
					dropdownCssClass: 'bb-select-dropdown',
					containerCssClass: 'bb-select-container',
				});
			}
		},

		toggleAutoRecording: function(e) {
			var target = $(e.target), form_recording_options = target.closest('form').find('.bp-zoom-meeting-auto-recording-options');
			if(target.is(':checked')) {
				form_recording_options.removeClass('bp-hide');
			} else {
				form_recording_options.addClass('bp-hide');
			}
		},

		toggleRecurring: function ( e ) {
			var target = $( e.target ),
				form_recurring_options = target.closest( 'form' ).find( '.bp-zoom-meeting-recurring-options' ),
				registration_options = target.closest( 'form' ).find( '.bp-zoom-meeting-registration-options' );
			if ( target.is( ':checked' ) ) {
				form_recurring_options.removeClass( 'bp-hide' );
				if ( target.closest( 'form' ).find( '#bp-zoom-meeting-registration' ).is( ':checked' ) && target.closest( 'form' ).find( '#bp-zoom-meeting-recurring' ).is( ':checked' ) && [ '1', '2', '3' ].includes( target.closest( 'form' ).find( '#bp-zoom-meeting-recurrence' ).val() ) ) {
					registration_options.removeClass( 'bp-hide' );
				}
			} else {
				form_recurring_options.addClass( 'bp-hide' );
				registration_options.addClass( 'bp-hide' );
			}
		},

		formatDate: function (date) {
			var d = new Date(date),
				month = '' + (d.getMonth() + 1),
				day = '' + d.getDate(),
				year = d.getFullYear();

			if (month.length < 2) {
				month = '0' + month;
			}
			if (day.length < 2) {
				day = '0' + day;
			}

			return [year, month, day].join('-');
		},

		toggleDates: function(e) {
			var target = $(e.target),
				form = target.closest('form'),
				recurrence = form.find('#bp-zoom-meeting-recurrence'),
				repeat_interval = form.find('#bp-zoom-meeting-repeat-interval'),
				start_date_time = form.find('#bp-zoom-meeting-start-date'),
				end_date_time = form.find('#bp-zoom-meeting-end-date-time'),
				start_date = new Date(start_date_time.val()),
				end_date_time_date = new Date( end_date_time.val() );
			e.preventDefault();

			if ( start_date.getTime() >= end_date_time_date.getTime() ) {
				if ( recurrence.val() == '1' ) {
					start_date.setDate( start_date.getDate() + ( 6 * repeat_interval.val() ) );
					end_date_time.val( this.formatDate( start_date ) );
				}
				if ( recurrence.val() == '2' ) {
					start_date.setDate( start_date.getDate() + ( 6 * ( 7 * repeat_interval.val() ) ) );
					end_date_time.val( this.formatDate( start_date ) );
				}
				if ( recurrence.val() == '3' ) {
					start_date.setMonth( start_date.getMonth() + ( 6 * repeat_interval.val() ) );
					end_date_time.val( this.formatDate( start_date ) );
				}
			}
		},

		toggleRegistration: function ( e ) {
			var target = $( e.target ),
				form = target.closest( 'form#bp_zoom_meeting_form' ),
				registration_options = form.find( '.bp-zoom-meeting-registration-options' );

			if ( target.is( ':checked' ) && form.find( '#bp-zoom-meeting-recurring' ).is( ':checked' ) && [ '1', '2', '3' ].includes( form.find( '#bp-zoom-meeting-recurrence' ).val() ) ) {
				registration_options.removeClass( 'bp-hide' );
			} else {
				registration_options.addClass( 'bp-hide' );
			}
		},

		toggleRepeatInterval: function (e) {
			var target = $(e.target),
				recurrence = target.closest('form').find('#bp-zoom-meeting-recurrence'),
				start_date_time = target.closest('form').find('#bp-zoom-meeting-start-date'),
				end_date_time = target.closest('form').find('#bp-zoom-meeting-end-date-time'),
				start_date = new Date(start_date_time.val()),
				end_date = new Date();
			e.preventDefault();

			if (recurrence.val() == '1') {
				end_date.setDate(start_date.getDate() + (6 * target.val()));
				end_date_time.val(this.formatDate(end_date));
			}
			if (recurrence.val() == '2') {
				end_date.setDate(start_date.getDate() + (6 * (7 * target.val())));
				end_date_time.val(this.formatDate(end_date));
			}
			if (recurrence.val() == '3') {
				end_date.setMonth(start_date.getMonth() + (6 * target.val()));
				end_date_time.val(this.formatDate(end_date));
			}
		},

		toggleRecurrence: function (e) {
			var target = $(e.target),
				form = target.closest('form'),
				form_recurrence_options = form.find('.bp-zoom-meeting-recurring-sub-options'),
				registration_options = form.find( '.bp-zoom-meeting-registration-options' ),
				registration_wrapper = form.find( '.bp-zoom-meeting-registration-wrapper' ),
				form_occurs_on_options = form.find('.bp-zoom-meeting-occurs-on'),
				form_occurs_on_monthly = form.find('#bp-zoom-meeting-occurs-on-month'),
				form_occurs_on_weekly = form.find('#bp-zoom-meeting-occurs-on-week'),
				interval_type_label = form.find('#bp-zoom-meeting-repeat-interval-type'),
				repeat_interval = form.find('#bp-zoom-meeting-repeat-interval'),
				start_date_time = form.find('#bp-zoom-meeting-start-date'),
				end_date_time = form.find('#bp-zoom-meeting-end-date-time'),
				i = 1, repeat_interval_html = '',
				start_date = new Date(start_date_time.val());
			e.preventDefault();

			if (target.val() == '-1') {
				form_recurrence_options.addClass('bp-hide');
				registration_options.addClass( 'bp-hide' );
				registration_wrapper.addClass( 'bp-hide' );
			} else {
				if ( target.closest( 'form' ).find( '#bp-zoom-meeting-registration' ).is( ':checked' ) && target.closest( 'form' ).find( '#bp-zoom-meeting-recurring' ).is( ':checked' ) ) {
					registration_options.removeClass( 'bp-hide' );
				}
				registration_wrapper.removeClass( 'bp-hide' );

				if (target.val() == '1') {
					form_occurs_on_options.addClass('bp-hide');
					interval_type_label.text('day');
					repeat_interval_html = '';
					for (i = 1; i <= 15; i++) {
						repeat_interval_html += '<option value="' + i + '">' + i + '</option>';
					}
					repeat_interval.html(repeat_interval_html);

					start_date.setDate( start_date.getDate() + ( 6 * repeat_interval.val() ) );
					end_date_time.val( this.formatDate( start_date ) );
				}
				if (target.val() == '2') {
					form_occurs_on_options.removeClass('bp-hide');
					form_occurs_on_weekly.removeClass('bp-hide');
					form_occurs_on_monthly.addClass('bp-hide');
					interval_type_label.text('week');
					repeat_interval_html = '';
					for (i = 1; i <= 12; i++) {
						repeat_interval_html += '<option value="' + i + '">' + i + '</option>';
					}
					repeat_interval.html(repeat_interval_html);

					start_date.setDate( start_date.getDate() + ( 6 * ( 7 * repeat_interval.val() ) ) );
					end_date_time.val( this.formatDate( start_date ) );
				}
				if (target.val() == '3') {
					form_occurs_on_options.removeClass('bp-hide');
					form_occurs_on_weekly.addClass('bp-hide');
					form_occurs_on_monthly.removeClass('bp-hide');
					interval_type_label.text('month');
					repeat_interval_html = '';
					for (i = 1; i <= 3; i++) {
						repeat_interval_html += '<option value="' + i + '">' + i + '</option>';
					}
					repeat_interval.html(repeat_interval_html);

					start_date.setMonth( start_date.getMonth() + ( 6 * repeat_interval.val() ) );
					end_date_time.val( this.formatDate( start_date ) );
				}

				form_recurrence_options.removeClass('bp-hide');
			}
		},

		loadCreateMeeting: function( e ) {
            e.preventDefault();
            var target = $( e.currentTarget ),
                group_id = target.data('group-id');

            $('#bp-zoom-single-meeting-wrapper').empty();
			$( '#meetings-list .meeting-item' ).removeClass( 'current' );

			if ( $(this.bp_zoom_meeting_container_elem).length ) {
				$(this.bp_zoom_meeting_container_elem).addClass('bp-create-meeting');
				$(this.bp_zoom_meeting_container_elem).removeClass('bp-past-meeting');
				$(this.bp_zoom_meeting_container_elem).removeClass('bp-future-meeting');
				$(this.bp_zoom_meeting_container_elem).removeClass('bp-edit-meeting');
				$(this.bp_zoom_meeting_container_elem).removeClass('bp-single-meeting');
			}

            this.abort_zoom_ajax.bind(this);

            this.bp_zoom_ajax = $.ajax({
                type: 'GET',
                url: bp_zoom_meeting_vars.ajax_url,
                data: {action: 'zoom_meeting_create_meeting', group_id: group_id },
                success: function (response) {
                    if (typeof response.data !== 'undefined' && response.data.contents) {
                        $('#bp-zoom-single-meeting-wrapper').html(response.data.contents);

                        $('#bp-zoom-single-meeting-wrapper').find('#bp-zoom-meeting-start-date').datetimepicker({
                            format: 'Y-m-d',
                            timepicker: false,
                            mask: true,
                            minDate: 0,
                            yearStart: new Date().getFullYear(),
                            defaultDate: new Date(),
                            scrollMonth: false,
                            scrollTime: false,
                            scrollInput: false,
	                        onSelectDate: function (date,element) {
		                        $('#bp-zoom-single-meeting-wrapper').find('#bp-zoom-meeting-end-date-time').datetimepicker({
			                        minDate: element.val(),
		                        });
	                        }
                        });

	                    $('#bp-zoom-single-meeting-wrapper').find('#bp-zoom-meeting-end-date-time').datetimepicker({
		                    format: 'Y-m-d',
		                    timepicker: false,
		                    mask: true,
		                    minDate: 0,
		                    defaultDate: new Date().setDate(new Date().getDate() + 6),
		                    scrollMonth: false,
		                    scrollTime: false,
		                    scrollInput: false,
	                    });

                        $('#bp-zoom-single-meeting-wrapper').find('#bp-zoom-meeting-start-time').datetimepicker({
                            format: 'h:i',
                            formatTime:	'h:i',
                            datepicker: false,
                            hours12: true,
							step: 30,
                        });

                        var options = {
                            placeholder: 'hh:mm',
                            translation: {
                                'P': {
                                    pattern: /0|1/, optional: false
                                },
                                'Q': {
                                    pattern: /0|1|2/, optional: false
                                },
                                'X': {
                                    pattern: /0|[1-9]/, optional: false
                                },
                                'Y': {
                                    pattern: /[0-5]/, optional: false
                                },
                                'Z': {
                                    pattern: /[0-9]/, optional: false
                                },
                            },
                            onKeyPress: function(cep, e, field, options) {
                                var masks = [ 'PX:YZ', 'PQ:YZ' ];
                                var mask = ( cep.length > 1 && cep.substr(0,1) > 0 ) ? masks[1] : masks[0];
                                $('#bp-zoom-single-meeting-wrapper').find('#bp-zoom-meeting-start-time').mask(mask, options);
                            }
                        };

                        $('#bp-zoom-single-meeting-wrapper').find('#bp-zoom-meeting-start-time').mask( 'PX:YZ', options );

                        $('#bp-zoom-single-meeting-wrapper').find('#bp-zoom-meeting-timezone').select2({
                            minimumInputLength: 0,
                            closeOnSelect: true,
                            language: (typeof bp_select2 !== 'undefined' && typeof bp_select2.lang !== 'undefined') ? bp_select2.lang : 'en',
                            dropdownCssClass: 'bb-select-dropdown',
                            containerCssClass: 'bb-select-container',
                        });

                        if ( bp_zoom_meeting_vars.group_meetings_url !== '') {
                            var create_meeting_url = bp_zoom_meeting_vars.group_meetings_url + 'create-meeting';
                            window.history.pushState(null, null, create_meeting_url );
                        }
                    }
                }
            });
        },

		openOccurrenceDeletePopup: function( e ) {
			var target = $( e.currentTarget ),
				meeting_item = target.closest( '.meeting-item-container' ),
				occurrence_id = meeting_item.data('occurrence-id');
			e.preventDefault();

			if ( typeof occurrence_id !== 'undefined' && occurrence_id != '' ) {
				$.magnificPopup.open({
					items: {
						src: '#bp-zoom-delete-occurrence-popup-'+occurrence_id,
						type: 'inline'
					}
				});
			}
		},

		openOccurrenceEditPopup: function( e ) {
			var target = $( e.currentTarget ),
				meeting_item = target.closest( '.meeting-item-container' ),
				occurrence_id = meeting_item.data('occurrence-id');
			e.preventDefault();

			if ( typeof occurrence_id !== 'undefined' && occurrence_id != '' ) {
				$.magnificPopup.open({
					items: {
						src: '#bp-zoom-edit-occurrence-popup-'+occurrence_id,
						type: 'inline'
					}
				});
			}
		},

		loadEditOccurrence: function( e ) {
			var target = $( e.currentTarget ),
				id = target.data('id'),
				meeting_id = target.data('meeting-id'),
				occurrence_id = target.data('occurrence-id');
			e.preventDefault();

			$.magnificPopup.close();

			this.ajaxEditMeetingLoader( id, meeting_id, occurrence_id );
		},

        loadEditMeeting: function( e ) {
            var target = $( e.currentTarget ),
                id = target.data('id'),
	            meeting_id = target.data('meeting-id');
            e.preventDefault();

	        $.magnificPopup.close();

	        this.ajaxEditMeetingLoader( id, meeting_id, '' );
        },

		ajaxEditMeetingLoader: function( id, meeting_id, occurrence_id ) {
			var self = this;

			$('#bp-zoom-single-meeting-wrapper').empty();

			var data = { action: 'zoom_meeting_edit_meeting', 'id': id };
			if ( typeof occurrence_id !== 'undefined' && occurrence_id !== '' ) {
				data.occurrence_id = occurrence_id;
			}
			if ( typeof meeting_id !== 'undefined' && meeting_id !== '' ) {
				data.meeting_id = meeting_id;
			}

			if ( $( self.bp_zoom_meeting_container_elem ).length ) {
				$( self.bp_zoom_meeting_container_elem )
					.addClass( 'bp-create-meeting' )
					.removeClass( 'bp-past-meeting' )
					.removeClass( 'bp-future-meeting' )
					.removeClass( 'bp-edit-meeting' )
					.removeClass( 'bp-single-meeting' );
			}

			self.abort_zoom_ajax();

			self.bp_zoom_ajax = $.ajax({
				type: 'GET',
				url: bp_zoom_meeting_vars.ajax_url,
				data: data,
				success: function (response) {
					if (typeof response.data !== 'undefined' && response.data.contents) {
						$('#bp-zoom-single-meeting-wrapper').html(response.data.contents);

						$('#bp-zoom-single-meeting-wrapper').find('#bp-zoom-meeting-start-date').datetimepicker({
							format: 'Y-m-d',
							timepicker: false,
							mask: true,
							minDate: 0,
							yearStart: new Date().getFullYear(),
							defaultDate: new Date(),
							scrollMonth: false,
							scrollTime: false,
							scrollInput: false,
							onSelectDate: function (date,element) {
								$('#bp-zoom-single-meeting-wrapper').find('#bp-zoom-meeting-end-date-time').datetimepicker({
									minDate: element.val(),
								});
							}
						});

						$('#bp-zoom-single-meeting-wrapper').find('#bp-zoom-meeting-end-date-time').datetimepicker({
							format: 'Y-m-d',
							timepicker: false,
							mask: true,
							minDate: 0,
							defaultDate: new Date().setDate(new Date().getDate() + 6),
							scrollMonth: false,
							scrollTime: false,
							scrollInput: false,
						});

						$('#bp-zoom-single-meeting-wrapper').find('#bp-zoom-meeting-start-time').datetimepicker({
							format: 'h:i',
							formatTime:	'h:i',
							datepicker: false,
							hours12: true,
							step: 30,
						});

						var options = {
							placeholder: 'hh:mm',
							translation: {
								'P': {
									pattern: /0|1/, optional: false
								},
								'Q': {
									pattern: /0|1|2/, optional: false
								},
								'X': {
									pattern: /0|[1-9]/, optional: false
								},
								'Y': {
									pattern: /[0-5]/, optional: false
								},
								'Z': {
									pattern: /[0-9]/, optional: false
								},
							},
							onKeyPress: function(cep, e, field, options) {
								var masks = [ 'PX:YZ', 'PQ:YZ' ];
								var mask = ( cep.length > 1 && cep.substr(0,1) > 0 ) ? masks[1] : masks[0];
								$('#bp-zoom-single-meeting-wrapper').find('#bp-zoom-meeting-start-time').mask(mask, options);
							}
						};

						$('#bp-zoom-single-meeting-wrapper').find('#bp-zoom-meeting-start-time').mask( 'PX:YZ', options );

						$('#bp-zoom-single-meeting-wrapper').find('#bp-zoom-meeting-timezone').select2({
							minimumInputLength: 0,
							closeOnSelect: true,
							language: (typeof bp_select2 !== 'undefined' && typeof bp_select2.lang !== 'undefined') ? bp_select2.lang : 'en',
							dropdownCssClass: 'bb-select-dropdown',
							containerCssClass: 'bb-select-container',
						});
					}
				}
			});
		},

		backToMeetingList: function(e) {
			e.preventDefault();

			if ( $(this.bp_zoom_meeting_container_elem).length ) {
				$(this.bp_zoom_meeting_container_elem).removeClass('bp-create-meeting');
				$(this.bp_zoom_meeting_container_elem).removeClass('bp-past-meeting');
				$(this.bp_zoom_meeting_container_elem).removeClass('bp-future-meeting');
				$(this.bp_zoom_meeting_container_elem).removeClass('bp-edit-meeting');
				$(this.bp_zoom_meeting_container_elem).removeClass('bp-single-meeting');
			}
		},

		loadSingleMeeting: function(e){
			var target = $( e.target ),
				meeting_item = target.closest( '.meeting-item' ),
				meeting_action = meeting_item.data( 'action' ),
				meeting_zoom_type = meeting_item.data( 'zoom-type' ),
				id = meeting_item.data('id');
			e.preventDefault();
			var self = this;

			// when cancelling paren meeting editing for recurring meeting, reload the page.
			if ( 'edit-cancel' === meeting_action && 'meeting' === meeting_zoom_type ) {
				window.location.reload();
				return false;
			}

			if ( $(this.bp_zoom_meeting_container_elem).length ) {
				$(this.bp_zoom_meeting_container_elem).removeClass('bp-create-meeting');
				$(this.bp_zoom_meeting_container_elem).removeClass('bp-past-meeting');
				$(this.bp_zoom_meeting_container_elem).removeClass('bp-future-meeting');
				$(this.bp_zoom_meeting_container_elem).removeClass('bp-edit-meeting');
				$(this.bp_zoom_meeting_container_elem).addClass('bp-single-meeting');

				if ( $('.bp-navs.group-subnav').find('li.bp-groups-tab.current.selected').hasClass('meetings') ) {
					$(this.bp_zoom_meeting_container_elem).addClass('bp-future-meeting');
				} else if ( $('.bp-navs.group-subnav').find('li.bp-groups-tab.current.selected').hasClass('past-meetings') ) {
					$(this.bp_zoom_meeting_container_elem).addClass('bp-past-meeting');
				}
			}

            if ( target.hasClass( 'view-recordings' ) || target.hasClass( 'dashicons' ) || meeting_item.hasClass( 'current' ) ) {
                return false;
            }

            $( '#meetings-list .meeting-item' ).removeClass( 'current' );
            $( '#meetings-list .meeting-item[data-id=' + id + ']' ).addClass( 'current' );

            $('#bp-zoom-single-meeting-wrapper').empty();

            this.abort_zoom_ajax.bind(this);

            this.bp_zoom_ajax = $.ajax({
				type: 'GET',
				url: bp_zoom_meeting_vars.ajax_url,
				data: {action: 'zoom_meeting_get_single_meeting', 'id': id},
				success: function (response) {
					if (typeof response.data !== 'undefined' && response.data.contents) {
						$('#bp-zoom-single-meeting-wrapper').html(response.data.contents);

                        if ( bp_zoom_meeting_vars.group_meetings_url !== '') {
                            var meeting_url = bp_zoom_meeting_vars.group_meetings_url + 'meetings/' + id;
                            window.history.pushState(null, null, meeting_url );
                        }
					}

                    $('#bp-zoom-single-meeting-wrapper').find('#copy-invitation-link').magnificPopup({
                        type:'inline',
                        midClick: true // Allow opening popup on middle mouse click. Always set it to true if you don't provide alternative source in href.
                    });

					$('#bp-zoom-single-meeting-wrapper').find('.show-recordings').magnificPopup({
						type:'inline',
						midClick: true,
						callbacks: {
							open: function () {
								var mf_content = $('.mfp-content');
								var meeting_date = $( '#bp-zoom-single-meeting' ).data( 'meeting-start-date' );
								var scrollToElement = mf_content.find( '.bp-zoom-block-show-recordings' ).find( '[data-recorded-date="' + meeting_date + '"]' );
								if ( !scrollToElement.length ) {
									meeting_date = new Date( meeting_date );
									mf_content.find( '[data-recorded-date]' ).each( function () {
										var row_recording_date = $( this ).data( 'recorded-date' );
										var row_recording_date_obj = new Date( row_recording_date );
										if ( row_recording_date_obj.getTime() === meeting_date.getTime() ) {
											scrollToElement = mf_content.find( '[data-recorded-date="' + row_recording_date + '"]' );
											return false;
										}
									} );
									if ( !scrollToElement.length ) {
										mf_content.find( '[data-recorded-date]' ).each( function () {
											var row_recording_date = $( this ).data( 'recorded-date' );
											var row_recording_date_obj = new Date( row_recording_date );
											if ( row_recording_date_obj.getTime() >= meeting_date.getTime() ) {
												scrollToElement = mf_content.find( '.bp-zoom-block-show-recordings' ).find( '[data-recorded-date="' + row_recording_date + '"]' );
												return false;
											}
										} );
									}
								}
								if ( scrollToElement.length ) {
									var total_scroll = scrollToElement.outerHeight();
									$( scrollToElement.nextAll() ).each( function () {
										total_scroll += $( this ).outerHeight();
									} );
									mf_content.find( '.recording-list-row-wrap' ).animate( {
										scrollTop: mf_content.find( '.recording-list-row-wrap' )[0].scrollHeight - total_scroll
									}, 100 );
									$('.bp-zoom-recordings-dates').val(scrollToElement.data('recorded-date'));
								}
							},
						}
					});

                    self.mask_meeting_id();
                    self.triggerCountdowns();
				}
			});
		},

		scrollMeetings: function(event) {
			if ( event.target.id === 'meetings-list' ) { // or any other filtering condition.
				var el = event.target;
				if ( el.scrollTop + el.offsetHeight >= el.scrollHeight && ! el.classList.contains( 'loading' ) ) {
					var load_more = $(el).find('.load-more');
					if ( load_more.length ) {
						el.classList.add('loading');
						load_more.find('a').trigger('click');
					}
				}
			}
		},

		loadMoreMeetings: function (e) {
			var _this = $(e.currentTarget);
			e.preventDefault();
            var self = this;

			if (_this.hasClass('loading')) {
				return false;
			}

			_this.addClass('loading');

            var recorded = false;
            if ( $( '#bp-zoom-meeting-recorded-switch-checkbox' ).is( ':checked' ) ) {
                recorded = true;
            }

            $.ajax({
				type: 'GET',
				url: bp_zoom_meeting_vars.ajax_url,
				data: {
				    action: 'zoom_meeting_load_more',
                    'page': this.getLinkParams(_this.prop('href'), 'acpage'),
                    'search_terms': $( '#bp-zoom-meeting-container #bp_zoom_meeting_search' ).val(),
                    'recorded'    : recorded,
                    'past'        : $( '#bp-zoom-meeting-container #bp-zoom-meeting-recorded-switch-checkbox' ).length,
                },
				success: function (response) {
					if (typeof response.data !== 'undefined' && response.data.contents) {
						_this.closest('.load-more').replaceWith(response.data.contents);
					}
					_this.removeClass('loading');
					$('#meetings-list').removeClass('loading');
                    self.mask_meeting_id();
				}
			});
		},

		updateMeeting: function (e) {
			var _this = $(e.currentTarget);
			e.preventDefault();

			if (_this.hasClass('loading') || _this.hasClass('disabled')) {
				return false;
			}

			var self = this;

			_this.addClass('loading');

            _this.parents( '.bp-meeting-fields-wrap' ).find( '.bp-feedback.error' ).remove();

			var form_data = _this.closest('form').serialize();

            this.abort_zoom_ajax.bind(this);

            this.bp_zoom_ajax = $.ajax({
				type: 'POST',
				url: bp_zoom_meeting_vars.ajax_url,
				data: form_data,
				success: function (response) {
					var error_html = '';
					if (response.success) {
						if (typeof response.data !== 'undefined') {
							if ( response.data.redirect_url !== '' ) {
								window.location.href = response.data.redirect_url;
								return false;
							} else {
								window.location.reload();
							}
						}
					} else {
						if ( response.data.errors ) {
							for( var er in response.data.errors ) {
								error_html = '<aside class="bp-feedback bp-messages error">' +
									'<span class="bp-icon" aria-hidden="true"></span>' +
									'<p>' + response.data.errors[er].message + '</p>' +
									'</aside>';
								_this.parents( '.bp-meeting-fields-wrap' ).prepend( error_html );
							}
						} else if ( response.data.error ) {
                            error_html = '<aside class="bp-feedback bp-messages error">' +
                                    '<span class="bp-icon" aria-hidden="true"></span>' +
                                    '<p>' + response.data.error + '</p>' +
                                '</aside>';
                            _this.parents( '.bp-meeting-fields-wrap' ).prepend( error_html );
                        }
						_this.removeClass('loading');

                        $('html, body').animate({ scrollTop: $('#bp-zoom-single-meeting-wrapper').offset().top - 100 }, 500 );
					}
                    self.mask_meeting_id();
				}
			});
		},

		deleteOnlyThisMeeting: function( e ) {
			var target = $(e.target),
				id = target.data('id'),
				meeting_id = target.data('meeting-id'),
				occurrence_id = target.data('occurrence-id');
			e.preventDefault();

			this.ajaxDeleteMeeting( id, meeting_id, occurrence_id );
		},

		deleteAllMeetingOccurrences: function( e ) {
			var target = $(e.target),
				id = target.data('id'),
				meeting_id = target.data('meeting-id');
			e.preventDefault();

			this.ajaxDeleteMeeting( id, meeting_id );
		},

		ajaxDeleteMeeting: function( id, meeting_id, occurrence_id ) {
			var self = this;

			var data = {
				'action': 'zoom_meeting_delete',
				'meeting_id': meeting_id,
				'id': id,
				'_wpnonce': bp_zoom_meeting_vars.meeting_delete_nonce,
			};

			if ( typeof occurrence_id !== 'undefined' && occurrence_id !== '' ) {
				data.occurrence_id = occurrence_id;
			}

			self.abort_zoom_ajax.bind(self);

			self.bp_zoom_ajax = $.ajax({
				type: 'POST',
				url: bp_zoom_meeting_vars.ajax_url,
				data: data,
				success: function (response) {
					if (true === response.data.deleted && '1' === bp_zoom_meeting_vars.is_single_meeting) {
						if ( true === response.data.is_past && bp_zoom_meeting_vars.group_meetings_past_url !== '') {
							window.location.href = bp_zoom_meeting_vars.group_meetings_past_url;
						} else if (bp_zoom_meeting_vars.group_meetings_url !== '') {
							window.location.href = bp_zoom_meeting_vars.group_meetings_url;
						}
						return false;
					}
				}
			});
		},

		deleteMeeting: function (e) {
			var target = $(e.target),
                meeting_item = target.parents('.meeting-item-container'),
				meeting_id = meeting_item.data('meeting-id'),
                id = meeting_item.data('id');
			e.preventDefault();

			if ( ! confirm( bp_zoom_meeting_vars.meeting_confirm_msg ) ) {
				return false;
			}

			this.ajaxDeleteMeeting( id, meeting_id );
		},

		togglePassword: function (e) {
			var _this = $(e.currentTarget), meeting_row = _this.closest('.single-meeting-item');
			e.preventDefault();

			if (_this.hasClass('show-pass')) {
				_this.removeClass('on');
				meeting_row.find('.toggle-password.hide-pass').addClass('on');
				meeting_row.find('.hide-password').removeClass('on');
				meeting_row.find('.show-password').addClass('on');
			} else {
				_this.removeClass('on');
				meeting_row.find('.toggle-password.show-pass').addClass('on');
				meeting_row.find('.show-password').removeClass('on');
				meeting_row.find('.hide-password').addClass('on');
			}
		},

		toggleRecordingPassword: function(e) {
			var _this = $(e.currentTarget), recording_row = _this.closest('.recording-list-row');
			e.preventDefault();

			if (_this.hasClass('show-pass')) {
				recording_row.find('.toggle-password.show-pass').addClass('bp-hide');
				recording_row.find('.show-password').removeClass('bp-hide');
			} else {
				recording_row.find('.toggle-password.show-pass').removeClass('bp-hide');
				recording_row.find('.show-password').addClass('bp-hide');
			}
		},

		copyInvitationDetails: function (e) {
			var target = $(e.currentTarget);
			e.preventDefault();

			if ( target.hasClass('copied') ) {
				return false;
			}

			var meeting_invitation = $('#meeting-invitation'), button_text = target.text();
			meeting_invitation.select();
			try {
				var successful = document.execCommand('copy');
				// var msg = successful ? 'successful' : 'unsuccessful';
				if (successful) {
					target.addClass('copied');
                    target.html(target.data('copied'));

					setTimeout(function () {
						target.removeClass('copied');
                        target.html(button_text);
					}, 3000);
				}
			} catch (err) {
				console.log('Oops, unable to copy');
			}
		},

        copyDownloadLink: function (e) {
			var _this = $(e.currentTarget),
                button_text = _this.html();
			e.preventDefault();

			if ( _this.hasClass('copied') ) {
				return false;
			}

			var textArea = document.createElement('textarea');
			textArea.value = _this.data('download-link');
			if ( _this.closest('.bp-zoom-block-show-recordings').length ) {
				_this.closest('.bp-zoom-block-show-recordings')[0].appendChild(textArea);
			} else {
				document.body.appendChild(textArea);
			}
			textArea.select();
			try {
				var successful = document.execCommand('copy');
				// var msg = successful ? 'successful' : 'unsuccessful';
				if (successful) {
					_this.addClass('copied');
                    _this.html(_this.data('copied'));

					setTimeout(function () {
						_this.removeClass('copied');
                        _this.html(button_text);
					}, 3000);
				}
			} catch (err) {
				console.log('Oops, unable to copy');
			}
			if ( _this.closest('.bp-zoom-block-show-recordings').length ) {
				_this.closest('.bp-zoom-block-show-recordings')[0].removeChild(textArea);
			} else {
				document.body.removeChild(textArea);
			}
		},

		getLinkParams: function (url, param) {
			var qs;
			if (url) {
				qs = (-1 !== url.indexOf('?')) ? '?' + url.split('?')[1] : '';
			} else {
				qs = document.location.search;
			}

			if (!qs) {
				return null;
			}

			var params = qs.replace(/(^\?)/, '').split('&').map(function (n) {
				return n = n.split('='), this[n[0]] = n[1], this;
			}.bind({}))[0];

			if (param) {
				return params[param];
			}

			return params;
		},

		closeCreateMeetingModal: function (event) {
			event.preventDefault();

			$('#bp-meeting-create').hide();
		},

        openRecordingModal: function(e) {
			var _this = $(e.currentTarget);
			e.preventDefault();

			_this.closest('.recording-list-row').find('.bb-media-model-wrapper').show();
		},

		closeRecordingModal: function(e) {
            e.preventDefault();

            if ( $('.bb-media-model-wrapper').find( 'video' ).length > 0 ) {
                $('.bb-media-model-wrapper').find( 'video' ).get(0).pause();
            }

            if ( $('.bb-media-model-wrapper').find( 'audio' ).length > 0 ) {
                $('.bb-media-model-wrapper').find( 'audio' ).get(0).pause();
            }

			$('.bb-media-model-wrapper').hide();
		},

        checkPressedKey: function( e ) {
            var self = this;
            e = e || window.event;
            switch ( e.keyCode ) {
                case 27: // escape key
                    self.closeRecordingModal(e);
                    break;
            }
        },

        openMeetingActions: function(e) {
            var _this = $(e.currentTarget);
            e.preventDefault();

            _this.next('.meeting-actions-list').toggleClass('open');
        },

        searchMeetingActions: function(e) {
            var _this = $(e.currentTarget),
                self_id = _this.attr('id'),
                self = this;

            if ('bp-zoom-meeting-recorded-switch-checkbox' !== self_id) {
                e.preventDefault();
            }

            var recorded = false;
            if ( $( '#bp-zoom-meeting-recorded-switch-checkbox' ).is( ':checked' ) ) {
                recorded = true;
            }

            $( '#bp-zoom-meeting-container #bp-zoom-dropdown-options-loader' ).show();

            var page = 1;
            var param = {
                'action'      : 'zoom_meeting_search',
                'recorded'    : recorded,
                'page'        : page,
                'search_terms': $( '#bp-zoom-meeting-container #bp_zoom_meeting_search' ).val(),
                'past'        : $( '#bp-zoom-meeting-container #bp-zoom-meeting-recorded-switch-checkbox' ).length,
            };

            $.ajax({
                type: 'GET',
                url: bp_zoom_meeting_vars.ajax_url,
                async: true,
                data: param,
                success: function (response) {
                    if ( typeof response.data !== 'undefined' && response.data.contents) {
                        $('#bp-zoom-meeting-container .bp-zoom-meeting-left-inner #meetings-list').html(response.data.contents);

                        var id = $('#bp-zoom-single-meeting-wrapper').find('.meeting-item-container').data('id');
                        if ( id == 0 || id == null || typeof id === 'undefined' ) {
                        	if ( $('#bp_zoom_meeting_form').length && $('#bp_zoom_meeting_form').find('#bp-zoom-meeting-id').length ) {
								id = $('#bp_zoom_meeting_form').find('#bp-zoom-meeting-id').val();
							}
						}
                        if ( id ) {
                        	$('#meetings-list').find('.meeting-item[data-id="' + id + '"]').addClass('current');
						}
                    }

                    $( '#bp-zoom-meeting-container #bp-zoom-dropdown-options-loader' ).hide();
                    self.mask_meeting_id();
                }
            });

        },

		changeTimezone: function ( e ) {
			var _this = $( e.target );
			var currentDate = new Date( new Date().toLocaleDateString( 'en-US', { timeZone: _this.val() } ) );
			var args = {
				minDate: this.formatDate( currentDate )
			};
			var selectedDate = new Date( jQuery( '#bp-zoom-meeting-start-date' ).val() );
			if ( selectedDate < currentDate ) {
				args.value = this.formatDate( currentDate );
			}
			jQuery( '#bp-zoom-meeting-start-date' ).datetimepicker( args );
		},

        abort_zoom_ajax: function () {
            if (this.bp_zoom_ajax !== false) {
                this.bp_zoom_ajax.abort();
                this.bp_zoom_ajax = false;
            }
        },

        mask_meeting_id: function() {
		    if ( typeof jQuery.fn.mask !== 'undefined' ) {
                $('#meetings-list').find('.meeting-id').mask('AA: 000 0000 0000');
                $('#bp-zoom-single-meeting').find('.meeting-id').mask('000 0000 0000');
                $('.zoom-meeting-id').mask('000 0000 0000');
                $(document).find('.bb-meeting-id').mask('AA: 000 0000 0000');
            }
        },

        syncGroupMeetings: function (e) {
            var _this = $(e.currentTarget),
                group_id = _this.data('group-id'),
                offset = 0;
            e.preventDefault();

            _this.addClass('loading');

            this.bp_zoom_sync_function(offset, group_id);
        },

        bp_zoom_sync_function: function (offset, group_id) {
		    var self = this;
            $.ajax({
                type: 'POST',
                url: bp_zoom_meeting_vars.ajax_url,
                data: {
                    'action': 'zoom_meetings_sync',
                    'group_id': group_id,
                    'offset': offset,
                },
                success: function (response) {
                    if (typeof response.success !== 'undefined') {
                        if (response.success && typeof response.data !== 'undefined') {
                            if ('running' === response.data.status) {
                                self.bp_zoom_sync_function(response.data.offset, group_id);
                            } else {
                                $('#meetings-sync').removeClass('loading');
                                if ( response.data.redirect_url ) {
									window.location.href = response.data.redirect_url;
								} else {
									window.location.reload();
								}
                                return false;
                            }
                        } else {
                            $('#meetings-sync').removeClass('loading');
							if ( response.data.redirect_url ) {
								window.location.href = response.data.redirect_url;
							} else {
								window.location.reload();
							}
                            return false;
                        }
                    }
                },
                error: function () {
                    $('#meetings-sync').removeClass('loading');
                    return false;
                }
            });
        },

	};

	// Launch BP Zoom
	bp.Zoom.start();

} )( bp, jQuery );
