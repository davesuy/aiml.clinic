(function () {
	"use strict";

//Mixin jetApbWorkHoursSettings
	var settingsObject = ( window.JetAPBConfig ) ? window.JetAPBConfig.settings : window.jetApbPostMeta.custom_schedule,
		jetApbWorkHoursSettings = {
			data: function() {
				return {
					isNewSlot: false,
					editDay: false,
					deleteDayTrigger: null,
					date: {
						start: null,
						startTimeStamp: null,
						end: null,
						endTimeStamp: null,
						name: null,
						type: null,
						editIndex: null,
					},
					dateFormat: 'MMM D, YYYY',
					currentDay: null,
					currentFrom: '00:00',
					currentTo: '00:00',
					currentIndex: null,
					deleteSlotTrigger: null,
					disabledDate: {},
					settings: {
						default_buffer_after: ( settingsObject ) ? settingsObject.default_buffer_after : 0,
						default_buffer_before: ( settingsObject ) ? settingsObject.default_buffer_before : 0,
						default_slot: ( settingsObject ) ? settingsObject.default_slot : 3600,
						working_days: ( settingsObject ) ? settingsObject.working_days : [],
						days_off: ( settingsObject ) ? settingsObject.days_off : [],
						weekdays: ( window.JetAPBConfig ) ? window.JetAPBConfig.weekdays : window.jetApbPostMeta.custom_schedule.weekdays ,
						working_hours: ( settingsObject && settingsObject.working_hours ) ? settingsObject.working_hours : {
							friday: [
								{
									from:'08:00',
									to:'17:00',
								}
							],
							monday: [
								{
									from:'08:00',
									to:'17:00',
								}
							],
							thursday: [
								{
									from:'08:00',
									to:'17:00',
								}
							],
							tuesday: [
								{
									from:'08:00',
									to:'17:00',
								}
							],
							wednesday: [
								{
									from:'08:00',
									to:'17:00',
								}
							],
							saturday: [],
							sunday: [],
						}
					}
				};
			},
			components: {
				vuejsDatepicker: window.vuejsDatepicker
			},
			methods: {
				onUpdateTimeSettings: function( valueObject ) {
					var timeStamp = moment.duration( valueObject.value ).asSeconds();

					if( 'default_slot' === valueObject.key && timeStamp < 60 ){
						this.$CXNotice.add( {
							message: wp.i18n.__( 'The slot duration cannot be less than one minute!', 'jet-appointments-booking' ),
							type: 'error',
							duration: 7000,
						} );

						timeStamp = 60;
						//return;
					}

					this.$set( this.settings, valueObject.key, timeStamp );

					this.$nextTick( function() {
						this.saveSettings();
					} );
				},
				getTimeSettings: function( key ) {
					var dateObject = moment.duration( parseInt( this.settings[ key ] ), 'seconds' ),
						minutes    = dateObject._data.minutes < 10 ? `0${dateObject._data.minutes}` : dateObject._data.minutes ,
						hours      = dateObject._data.hours < 10 ? `0${dateObject._data.hours}` : dateObject._data.hours ;

					return `${hours}:${minutes}`;
				},
				setTimeSettings: function( valueObject ) {
					this.$set( this, valueObject.key, valueObject.value );
				},
				getSlotTime: function( key ) {
					return ( -1 === this[ key ].search( /^[\d{1}]:/ ) ) ? this[ key ] : '0' + this[ key ] ;
				},
				formatDate: function( date ) {
					return moment( date ).format( this.dateFormat );
				},
				getTimeStamp: function( date ) {
					return moment( date ).valueOf();
				},
				getTimeStamp_time: function( time ) {
					return moment( time, 'hh:mm' ).valueOf();
				},
				showEditDay: function( daysType = false , date = false ) {
					if ( date && daysType ) {
						var index = this.settings[ daysType ].indexOf( date );

						this.$set( this.date, 'editIndex', index );

						for ( var key in this.settings[ daysType ][ index ] ) {
							this.$set( this.date, key, this.settings[ daysType ][ index ][key] );
						}
					}

					this.updateDisabledDates( daysType, date );

					this.date.type  = daysType;
					this.editDay    = true;
				},

				handleDayOk: function() {
					if ( ! this.date.start || this.getTimeStamp( this.date.start ) > this.getTimeStamp( this.date.end ) ) {
						this.$CXNotice.add( {
							message: wp.i18n.__( 'Date is not correct', 'jet-appointments-booking' ),
							type: 'error',
							duration: 7000,
						} );

						return;
					}

					var startDate = this.formatDate( this.date.start ),
						startTimeStamp = this.getTimeStamp( this.date.start ),
						endTimeStamp = this.date.end ? this.getTimeStamp( this.date.end ) : startTimeStamp,
						dates = this.settings[ this.date.type ],
						index = null !== this.date.editIndex ? this.date.editIndex : dates.length;

					this.$set( dates, index, {
						start: startDate,
						startTimeStamp: startTimeStamp,
						end: this.date.end ? this.formatDate( this.date.end ) : startDate ,
						endTimeStamp: endTimeStamp,
						name: this.date.name,
						type: this.date.type,
					} );

					this.updateSetting( dates, this.date.type );
					this.handleDayCancel();
				},

				handleDayCancel: function() {
					for ( var key in this.date ) {
						this.$set( this.date, key, null );
					}

					this.editDay = false;
				},

				confirmDeleteDay: function( dateObject ) {
					this.deleteDayTrigger = dateObject;
				},

				deleteDay: function( daysType = false , date = false  ) {
					var index = this.settings[ daysType ].indexOf( date );

					this.$delete( this.settings[ daysType ], index );

					this.$nextTick( function() {
						this.saveSettings();
					} );
				},

				updateDisabledDates: function( daysType = false, excludedDate = false ) {
					var newDisabledDates = [],
						daysFrom,
						toFrom;

					for ( var date in this.settings[ daysType ] ) {
						if( this.settings[ daysType ][ date ] === excludedDate ){
							continue;
						}

						daysFrom = moment( this.settings[ daysType ][ date ].start );
						toFrom   = moment( this.settings[ daysType ][ date ].end ).add( 1, 'days' );

						//Fixes datapicker bug. If set by value, the disabled date is shifted by one day.
						if( excludedDate ){
							daysFrom.add( -1, 'days' )
						}

						newDisabledDates.push( {
							from: daysFrom,
							to: toFrom,
						} );
					}

					this.$set( this.disabledDate, 'ranges', newDisabledDates );
				},
				newSlot: function( day ) {
					this.isNewSlot  = true;
					this.currentDay = day;
				},
				editSlot: function( day, slotIndex, daySlot ) {
					this.isNewSlot    = true;
					this.currentDay   = day;
					this.currentFrom  = daySlot.from;
					this.currentTo    = daySlot.to;
					this.currentIndex = slotIndex;
				},
				confirmDeleteSlot: function( day, slotIndex ) {
					this.deleteSlotTrigger = day + '-' + slotIndex;
				},
				deleteSlot: function( day, slotIndex ) {
					var dayData = this.settings.working_hours[ day ] || [];

					this.deleteSlotTrigger = null;

					dayData.splice( slotIndex, 1 );

					this.$set( this.settings.working_hours, day, dayData );

					this.$nextTick( function() {
						this.saveSettings();
					} );

				},
				handleCancel: function() {
					this.currentDay   = null;
					this.currentFrom  = '00:00';
					this.currentTo    = '00:00';
					this.currentIndex = null;
				},
				handleOk: function() {
					if ( this.getTimeStamp_time( this.currentFrom ) >= this.getTimeStamp_time( this.currentTo ) ) {
						this.$CXNotice.add( {
							message: wp.i18n.__( 'Time is not correct', 'jet-appointments-booking' ),
							type: 'error',
							duration: 7000,
						} );

						return;
					}

					var dayData = this.settings.working_hours[ this.currentDay ] || [];

					if ( null === this.currentIndex ) {
						dayData.push( {
							from: this.currentFrom,
							to: this.currentTo,
						} );
					} else {
						dayData.splice( this.currentIndex, 1, {
							from: this.currentFrom,
							to: this.currentTo,
						} );
					}

					this.$set( this.settings.working_hours, this.currentDay, dayData );

					this.$nextTick( function() {
						this.saveSettings();
						this.handleCancel();
					} );

				},
			}
		};

//Mixin jetApbSettingsPage
	var jetApbSettingsPage = {
		data: function() {
			return {
				postTypes: window.JetAPBConfig.post_types || {},
				settings: window.JetAPBConfig.settings || {}
			};
		},
		methods: {
			updateSetting: function( value, key ) {
				this.$set( this.settings, key, value );

				this.$nextTick( function() {
					this.saveSettings();
				} );
			},
			saveSettings: function( updateDBColumns = false ) {
				var self = this;

				jQuery.ajax({
					url: ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'jet_apb_save_settings',
						settings: this.settings,
						update_db_columns: updateDBColumns,
					},
				}).done( function( response ) {

					if ( response.success ) {
						self.$CXNotice.add( {
							message: response.data.message,
							type: 'success',
							duration: 7000,
						} );
					}

					self.savingDBColumns = false;

				} ).fail( function( jqXHR, textStatus, errorThrown ) {

					self.$CXNotice.add( {
						message: errorThrown,
						type: 'error',
						duration: 7000,
					} );

					self.savingDBColumns = false;

				} );
			}
		}
	}

//General Component
	Vue.component( 'jet-apb-general-settings', {
		template: '#jet-dashboard-jet-apb-general-settings',
		mixins: [ jetApbSettingsPage ],
	} );

//Working Hours Component
	Vue.component( 'jet-apb-working-hours-settings', {
		template: '#jet-dashboard-jet-apb-working-hours-settings',
		mixins: [ jetApbSettingsPage, jetApbWorkHoursSettings ],
	} );

//Labels Component
	Vue.component( 'jet-apb-labels-settings', {
		template: '#jet-dashboard-jet-apb-labels-settings',
		mixins: [ jetApbSettingsPage ],
		methods: {
			updateLabel: function( value, key ) {
				this.$set( this.settings.custom_labels, key, value )

				this.$nextTick( function() {
					this.saveSettings();
				} );
			}
		}
	} );

//Advanced Component
	Vue.component( 'jet-apb-advanced-settings', {
		template: '#jet-dashboard-jet-apb-advanced-settings',
		mixins: [ jetApbSettingsPage ],
	} );

//Tools Component
	Vue.component( 'jet-apb-tools-settings', {
		template: '#jet-dashboard-jet-apb-tools-settings',
		mixins: [ jetApbSettingsPage ],
		data: function() {
			return {
				settings: window.JetAPBConfig.settings || {},
				clearingExcluded: false,
				savingDBColumns: false,
			};
		},
		methods: {
			clearExcludedDates: function() {
				var self = this;

				self.clearingExcluded = true;

				jQuery.ajax({
					url: ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'jet_apb_clear_excluded',
					},
				}).done( function( response ) {
					self.clearingExcluded = false;
					self.$CXNotice.add( {
						message: wp.i18n.__( 'Done!', 'jet-appointments-booking' ),
						type: 'success',
						duration: 7000,
					} );
				} ).fail( function( jqXHR, textStatus, errorThrown ) {
					self.clearingExcluded = false;
					self.$CXNotice.add( {
						message: errorThrown,
						type: 'error',
						duration: 7000,
					} );
				} );

			},
			addNewColumn: function() {
				this.settings.db_columns.push( '' );
			},
			cloneColumn: function( data, index ) {
				var column = this.columnNewName( this.settings.db_columns[ index ], this.settings.db_columns );

				this.$set( this.settings.db_columns, this.settings.db_columns.length, column );
			},
			deleteColumn: function( data, index ) {
				this.settings.db_columns.splice( index, 1 );
			},
			setColumnProp: function( index, column ) {
				if( column.search( /[-\s]/gi ) !== -1 ){
					column = column.replace( /[-\s]/gi, '_');

					this.$CXNotice.add( {
						message: wp.i18n.__( 'You cannot use the "-" or "space" character in the table name, it was automatically changed to the "_" character!', 'jet-appointments-booking' ),
						type: 'error',
						duration: 7000,
					} );
				}

				column = column.toLowerCase();

				if( -1 !== jQuery.inArray( column, this.settings.db_columns ) ){
					this.$CXNotice.add( {
						message: wp.i18n.__( 'This column already exists in the table!', 'jet-appointments-booking' ),
						type: 'error',
						duration: 7000,
					} );
					column = this.columnNewName( column, this.settings.db_columns );
				}

				this.$set( this.settings.db_columns, index, column );
			},
			saveDBColumns: function() {
				if ( window.confirm( wp.i18n.__( 'Are you sure? If you change or remove any columns, all data stored in this columns will be lost!', 'jet-appointments-booking' ) ) ) {
					this.savingDBColumns = true;
					this.saveSettings( true );
				}
			},
			columnNewName: function( name, columnArray ){
				var newName = name;

				if( -1 === jQuery.inArray( newName, columnArray ) ){
					return newName;
				}else{
					return this.columnNewName( newName + '_copy', columnArray );
				}
			}
		}
	} );

	Vue.component( 'jet-apb-advanced-settings', {
		template: '#jet-dashboard-jet-apb-advanced-settings',
		mixins: [ jetApbSettingsPage ],
	} );

//Set Up Component
	var setUpEventHub = new Vue()

	Vue.component( 'jet-apb-set-up-working-hours-settings', {
			template: '#jet-dashboard-jet-apb-set-up-working-hours-settings',
			mixins: [ jetApbWorkHoursSettings ],
			mounted: function(){
				this.saveSettings();
			},
			methods: {
				updateSetting: function( value, key ) {
					this.saveSettings();
				},
				saveSettings: function() {
					setUpEventHub.$emit( 'update-settings', this.settings );
				}
			}
		}
	);

	Vue.component(
		'jet-apb-set-up', {
		template: '#jet-dashboard-jet-apb-set-up',
		data: function() {
			return {
				isSet: window.JetAPBConfig.setup.is_set,
				isReset: window.JetAPBConfig.reset.is_reset,
				resetURL: window.JetAPBConfig.reset.reset_url,
				postTypes: window.JetAPBConfig.post_types,
				dbFields: window.JetAPBConfig.db_fields,
				currentStep: 1,
				lastStep: 4,
				loading: false,
				setupData: {
					create_single_form: true,
					create_page_form:  true,
				},
				log: false,
				additionalDBColumns: [],
			}
		},
		mounted: function () {
			this.$nextTick(function () {
				setUpEventHub.$on( 'update-settings', this.updateScheduleSettings );
			})
		},
		methods: {
			updateScheduleSettings: function( scheduleSettings ) {
				var updSettings = Object.assign( this.setupData, scheduleSettings );

				this.$set( this, 'setupData', updSettings );
			},
			nextStep: function() {

				var self = this;

				if ( 1 === self.currentStep ) {

					if ( ! self.setupData.services_cpt ) {

						self.$CXNotice.add( {
							message: 'Please select post type for provided services.',
							type: 'error',
							duration: 7000,
						} );

						return;
					}

					if ( self.setupData.add_providers && ! self.setupData.providers_cpt ) {

						self.$CXNotice.add( {
							message: 'Please select post type for service providers.',
							type: 'error',
							duration: 7000,
						} );

						return;

					}

				}

				if ( self.currentStep === self.lastStep ) {

					self.loading = true;

					jQuery.ajax({
						url: ajaxurl,
						type: 'POST',
						dataType: 'json',
						data: {
							action: 'jet_apb_setup',
							setup_data: self.setupData,
							db_columns: self.additionalDBColumns,
						},
					}).done( function( response ) {
						self.loading = false;

						if ( response.success ) {
							self.currentStep++;
							self.log = response.data;
						}
					} ).fail( function( jqXHR, textStatus, errorThrown ) {
						self.loading = false;

						self.$CXNotice.add( {
							message: errorThrown,
							type: 'error',
							duration: 7000,
						} );
					} );

				} else {
					self.currentStep++;
				}

			},
			prevStep: function() {
				if ( 1 < this.currentStep ) {
					this.currentStep--;
				}
			},
			addNewColumn: function( event ) {

				var col = {
					column: '',
					collapsed: false,
				};

				this.additionalDBColumns.push( col );

			},
			setColumnProp: function( index, key, value ) {
				if( value.search( /[-\s]/gi ) !== -1 ){
					value = column.replace( /[-\s]/gi, '_');

					this.$CXNotice.add( {
						message: wp.i18n.__( 'You cannot use the "-" or "space" character in the table name, it was automatically changed to the "_" character!', 'jet-appointments-booking' ),
						type: 'error',
						duration: 7000,
					} );
				}

				value = column.toLowerCase();

				var double = jQuery.grep( this.additionalDBColumns, function( item ){ return item.column === value; } );

				if ( ! double[0] ) {
					this.additionalDBColumns[ index ][ key ] = value;
				}else{
					this.additionalDBColumns[ index ][ key ] = this.columnNewName( value, this.additionalDBColumns );
					this.$CXNotice.add( {
						message: 'This column already exists in the table!',
						type: 'error',
						duration: 7000,
					} );
				}
			},
			cloneColumn: function( index ) {
				var col    = this.additionalDBColumns[ index ],
					newCol = {
						'column': col.column + '_copy',
					};

				this.additionalDBColumns.splice( index + 1, 0, newCol );

			},
			deleteColumn: function( index ) {
				this.additionalDBColumns.splice( index, 1 );
			},
			isCollapsed: function( object ) {
				if ( undefined === object.collapsed || true === object.collapsed ) {
					return true;
				} else {
					return false;
				}
			},
			goToReset: function() {
				if ( confirm( 'Are you sure? All previously booked appoinments will be removed!' ) ) {
					window.location = this.resetURL;
				}
			},
			columnNewName: function( name, columnArray ){
				var double = jQuery.grep( columnArray, function( item ){ return item.column === name; } );

				if( ! double[0] ){
					return name;
				}else{
					return this.columnNewName( name + '_copy', columnArray );
				}
			}
		}
	});

//Custom Schedule Meta Box
	if( document.getElementById('jet-apb-custom-schedule-meta-box') ){
		var metaBoxEventHub = new Vue();

		Vue.component( 'jet-apb-working-hours-meta-box', {
				template: '#jet-apb-settings-working-hours',
				mixins: [ jetApbWorkHoursSettings ],
				mounted: function () {
					this.settings.default_buffer_after  = jetApbPostMeta.custom_schedule.buffer_after;
					this.settings.default_buffer_before = jetApbPostMeta.custom_schedule.buffer_before;

					if( ! this.settings.working_days ){
						this.settings.working_days = []
					}

					if( ! this.settings.days_off ){
						this.settings.days_off = []
					}

					if( ! this.settings.working_hours ){
						this.settings.working_hours = []
					}
				},
				methods: {
					updateSetting: function( value, key ) {
						this.saveSettings();
					},
					saveSettings: function() {
						metaBoxEventHub.$emit( 'update-settings', this.settings );
					}
				}
			}
		);

		new Vue( {
			el: '#jet-apb-custom-schedule-meta-box',
			data: function() {
				return {
					settings: window.jetApbPostMeta,
				}
			},
			mounted: function () {
				this.$nextTick(function () {
					metaBoxEventHub.$on( 'update-settings', this.updateScheduleSettings );
				})
			},
			methods: {
				updateScheduleSettings: function( scheduleSettings ) {
					var updSettings = Object.assign( this.settings.custom_schedule, scheduleSettings );

					updSettings.buffer_after  = scheduleSettings.default_buffer_after;
					updSettings.buffer_before = scheduleSettings.default_buffer_before;

					this.$nextTick(function () {
						this.$set( this.settings, 'custom_schedule', updSettings );
						this.saveSettings();
					})
				},
				updateSetting: function( key, value ) {
					this.$set( this.settings['custom_schedule'], key, value );

					this.$nextTick( function() {
						this.saveSettings();
					} );

				},
				saveSettings: function() {
					var self = this;

					jQuery.ajax({
						url: ajaxurl,
						type: 'POST',
						dataType: 'json',
						data: {
							action: 'jet_apb_save_post_meta',
							jet_apb_post_meta: self.settings,
						},
					}).done( function( response ) {

						if ( response.success ) {
							self.$CXNotice.add( {
								message: response.data.message,
								type: 'success',
								duration: 7000,
							} );
						}

					} ).fail( function( jqXHR, textStatus, errorThrown ) {

						self.$CXNotice.add( {
							message: errorThrown,
							type: 'error',
							duration: 7000,
						} );

					} );
				}
			}
		});
	}

})();