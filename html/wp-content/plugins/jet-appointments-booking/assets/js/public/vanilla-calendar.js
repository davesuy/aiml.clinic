/*
	Vanilla AutoComplete v0.1
	Copyright (c) 2019 Mauro Marssola
	GitHub: https://github.com/marssola/jet-apb-calendar
	License: http://www.opensource.org/licenses/mit-license.php
*/
var VanillaCalendar = (function () {

	"use strict";

	function VanillaCalendar( options ) {

		var opts = {
			selector: null,
			pastDates: true,
			availableWeekDays: [],
			excludedDates: [],
			worksDates: [],
			date: new Date(),
			today: null,
			button_prev: null,
			button_next: null,
			month: null,
			month_label: null,
			weekDays: [],
			weekStart: 0,
			service: 0,
			provider: 0,
			providerIsset: false,
			api: '',
			inputName: '',
			isRequired: false,
			allowedServices: false,
			onSelect: function( data, elem ) {},
			months: [ 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ],
			shortWeekday: [ 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ],
		};

		opts.today = Date.UTC( opts.date.getFullYear(), opts.date.getMonth(), opts.date.getDate(), 0, 0, 0 ) / 1000;

		var xhr           = null;
		var initialized   = false;
		var instance      = null;
		var instanceInput = null;
		var serviceID     = null;
		var serviceField  = null;
		var providerID    = null;
		var providerField = null;

		for ( var k in options ) {
			if ( opts.hasOwnProperty( k ) ) {
				opts[ k ] = options[ k ];
			}
		}

		opts.weekStart = parseInt( opts.weekStart, 10 );

		instance = document.querySelector( opts.selector );

		if ( ! instance ) {
			return;
		}

		const addEvent = function( el, type, handler ) {

			if ( ! el ) {
				return;
			}

			if ( el.attachEvent ) {
				el.attachEvent( 'on' + type, handler );
			} else {
				el.addEventListener( type, handler );
			}

		};

		const removeEvent = function( el, type, handler ){

			if ( ! el ) {
				return;
			}

			if ( el.detachEvent ) {
				el.detachEvent( 'on' + type, handler );
			} else {
				el.removeEventListener( type, handler );
			}
		};

		const getWeekDay = function ( day ) {
			return opts.weekDays[ day ];
		};

		const adjustWeekDay = function( day ) {

			day = day - opts.weekStart;

			if ( 0 > day ) {
				return day + 7;
			} else {
				return day;
			}

		};

		const setDayAvailability = function( el, timestamp, weekDay ) {
			var isAvailable = true;

			timestamp = timestamp || parseInt( el.dataset.calendarDate, 10 );
			weekDay = weekDay || el.dataset.weekDay;

			if ( opts.worksDates.length ) {
				for ( var dates in opts.worksDates ) {
					if ( timestamp >= opts.worksDates[dates].start && timestamp <= opts.worksDates[dates].end ){
						isAvailable = true;
						break;
					}else{
						isAvailable = false;
					}
				}
			}

			if ( opts.excludedDates.length ) {
				for ( var dates in opts.excludedDates ) {
					if ( timestamp >= opts.excludedDates[dates].start && timestamp <= opts.excludedDates[dates].end ){
						isAvailable = false;
						break;
					}
				}
			}

			if ( ! weekDay || ( 0 > opts.availableWeekDays.indexOf( weekDay ) ) ) {
				isAvailable = false;
			}

			el.classList.remove( 'jet-apb-calendar-date--disabled' );

			if ( timestamp <= opts.today - 1 && ! opts.pastDates ) {
				el.classList.add( 'jet-apb-calendar-date--disabled' );
			} else {

				if ( ! isAvailable ) {
					el.classList.add( 'jet-apb-calendar-date--disabled' );
				}

				el.setAttribute( 'data-status', isAvailable );

			}

		};

		const createDay = function( date ) {

			var newDayElem     = document.createElement( 'div' );
			var newDayBody     = document.createElement( 'div' );
			var weekDayNum     = adjustWeekDay( date.getDay() );
			var currentWeekDay = getWeekDay( date.getDay() );
			var timestamp      = Date.UTC( date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0 );

			timestamp = timestamp / 1000;

			newDayElem.className = 'jet-apb-calendar-date';

			if ( date.getDate() === 1 ) {
				newDayElem.style.marginLeft = ( weekDayNum * 14.28 ) + '%';
			}

			setDayAvailability( newDayElem, timestamp, currentWeekDay );

			newDayElem.setAttribute( 'data-week-day', currentWeekDay );
			newDayElem.setAttribute( 'data-calendar-date', timestamp );

			if ( timestamp === opts.today ) {
				newDayElem.classList.add( 'jet-apb-calendar-date--today' );
			}

			newDayBody.innerHTML = date.getDate();
			newDayBody.className = 'jet-apb-calendar-date-body';

			newDayElem.appendChild( newDayBody );
			opts.month.appendChild( newDayElem );

			if ( 6 === weekDayNum ) {
				opts.month.appendChild( getNewSlotsWrapper() );
			}

		};

		const getNewSlotsWrapper = function() {

			var slotsEl = document.createElement( 'div' );

			slotsEl.className = 'jet-apb-calendar-slots';

			return slotsEl;

		};

		const removeActiveClass = function() {

			instance.querySelectorAll( '.jet-apb-calendar-date--selected' ).forEach( function( el ) {
				el.classList.remove( 'jet-apb-calendar-date--selected' );
			} );

			instance.querySelectorAll( '.jet-apb-calendar-slots' ).forEach( function( el ) {
				el.classList.remove( 'jet-apb-calendar-slots--active' );
				el.innerHTML = '';
			} );

			instanceInput.val( '' ).data( 'price', 0 ).trigger( 'change.JetEngine' );

		};

		const selectDate = function( el ) {

			removeActiveClass();
			el.classList.add( 'jet-apb-calendar-date--selected' );

			var slot     = getNextSlot( el ),
				service  = null,
				provider = null,
				datenow  = new Date(),
				slotsEvent;

			if ( ! slot ) {
				return;
			}

			slot.classList.add( 'jet-apb-calendar-slots--loading' );
			instance.classList.add( 'jet-apb-calendar--loading' );

			if ( xhr ) {
				xhr.abort();
			}

			if ( opts.service.id ) {
				service = opts.service.id;
			} else {
				service = serviceID;
			}

			if ( opts.provider.id ) {
				provider = opts.provider.id;
			} else {
				provider = providerID;
			}

			if ( ! service ) {
				alert( 'Please, select the service before' );
				slot.classList.remove( 'jet-apb-calendar-slots--loading' );
				instance.classList.remove( 'jet-apb-calendar--loading' );
				return;
			}

			if ( opts.provider.field && ! providerID ) {

				if ( ! window.elementorFrontend || ! window.elementorFrontend.isEditMode() ) {
					alert( 'Please, select the provider before' );
				}

				slot.classList.remove( 'jet-apb-calendar-slots--loading' );
				instance.classList.remove( 'jet-apb-calendar--loading' );

				if ( ! window.elementorFrontend || ! window.elementorFrontend.isEditMode() ) {
					return;
				}

			}

			xhr = jQuery.ajax({
				url: opts.api.date_slots,
				type: 'POST',
				dataType: 'json',
				data: {
					service: service,
					provider: provider,
					date: el.dataset.calendarDate,
					timestamp: Math.floor( ( datenow.getTime() - datenow.getTimezoneOffset() * 60 * 1000 ) / 1000 ),
				},
			}).done( function( response ) {
				xhr            = false;
				slot.classList.remove( 'jet-apb-calendar-slots--loading' );
				instance.classList.remove( 'jet-apb-calendar--loading' );
				slot.classList.add( 'jet-apb-calendar-slots--active' );
				slot.innerHTML = response.data;

				slotsEvent = new CustomEvent( 'jet-apb-calendar-slots--loaded', { el: slot, slotHtml: response.data } );

				window.dispatchEvent( slotsEvent );
			} );

		};

		const getNextSlot = function( el ) {

			var nextEl = el.nextSibling;

			if ( ! nextEl ) {
				return null;
			}

			if ( nextEl.classList.contains( 'jet-apb-calendar-slots' ) ) {
				return nextEl;
			} else {
				return getNextSlot( nextEl );
			}

		};

		const createMonth = function () {
			clearCalendar();
			var currentMonth = opts.date.getMonth();

			while ( opts.date.getMonth() === currentMonth ) {
				createDay( opts.date );
				opts.date.setDate( opts.date.getDate() + 1 );
			}

			opts.month.appendChild( getNewSlotsWrapper() );

			opts.date.setDate( 1 );
			opts.date.setMonth( opts.date.getMonth() -1 );
			opts.month_label.innerHTML = opts.months[ opts.date.getMonth() ] + ' ' + opts.date.getFullYear();

		};

		const monthPrev = function () {
			opts.date.setMonth( opts.date.getMonth() - 1 );
			createMonth();
		}

		const monthNext = function () {
			opts.date.setMonth( opts.date.getMonth() + 1 );
			createMonth();
		}

		const clearCalendar = function () {
			opts.month.innerHTML = ''
		}

		const createInputs = function() {

			instanceInput = document.createElement( 'input' );

			instanceInput.setAttribute( 'type', 'hidden' );
			instanceInput.setAttribute( 'name', opts.inputName );
			instanceInput.classList.add( 'jet-form__field' );

			if ( opts.isRequired ) {
				instanceInput.setAttribute( 'required', true );
			}

			instance.appendChild( instanceInput );

			instanceInput = jQuery( instanceInput );

			instanceInput.data( 'field', 'appointment' );

		};

		const createCalendar = function () {
			instance.innerHTML = `
			<div class="jet-apb-calendar-header">
				<button type="button" class="jet-apb-calendar-btn" data-calendar-toggle="previous"><svg height="24" version="1.1" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M20,11V13H8L13.5,18.5L12.08,19.92L4.16,12L12.08,4.08L13.5,5.5L8,11H20Z"></path></svg></button>
				<div class="jet-apb-calendar-header__label" data-calendar-label="month"></div>
				<button type="button" class="jet-apb-calendar-btn" data-calendar-toggle="next"><svg height="24" version="1.1" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M4,11V13H16L10.5,18.5L11.92,19.92L19.84,12L11.92,4.08L10.5,5.5L16,11H4Z"></path></svg></button>
			</div>
			<div class="jet-apb-calendar-week"></div>
			<div class="jet-apb-calendar-body" data-calendar-area="month"></div>
			`
		}

		const setWeekDayHeader = function () {

			var result = '';

			for ( var i = opts.weekStart; i <= opts.weekStart + 6; i++ ) {

				if ( i <= 6 ) {
					result += '<span>' + opts.shortWeekday[ i ] + '</span>';
				} else {
					result += '<span>' + opts.shortWeekday[ ( i - 7 ) ] + '</span>';
				}

			};

			instance.querySelector( '.jet-apb-calendar-week' ).innerHTML = result;

		}

		const setValue = function( date, slot, slotEnd, price ) {
			instanceInput.val( '' + date + '|' + slot + '|' + slotEnd ).data( 'price', price ).trigger( 'change' );
		}

		const refreshDates = function( newService, newProvider ) {

			instance.classList.add( 'jet-apb-calendar--loading' );
			removeActiveClass();

			xhr = jQuery.ajax({
				url: opts.api.refresh_dates,
				type: 'GET',
				dataType: 'json',
				data: {
					service: newService,
					provider: newProvider,
				},
			}).done( function( response ) {
				xhr = false;
				instance.classList.remove( 'jet-apb-calendar--loading' );

				for ( var k in response.data ) {
					if ( opts.hasOwnProperty( k ) ) {
						opts[ k ] = response.data[ k ];
					}
				};

				instance.querySelectorAll( '.jet-apb-calendar-date' ).forEach( function( el ) {
					setDayAvailability( el );
				} );
			} );
		}

		const maybeRefreshDatesOnInit = function() {

			if ( opts.service.id ) {
				serviceID = opts.service.id;
			} else if ( opts.service.field ) {

				if ( ! serviceField ) {
					serviceField = document.querySelectorAll( '[name="' + opts.service.field + '"]' );
				}

				if ( 1 === serviceField.length ) {
					if ( serviceField.value ) {
						serviceID = serviceField.value;
					}
				} else if ( 1 < serviceField.length ) {
					for ( var i = 0; i < serviceField.length; i++ ) {
						if ( serviceField[ i ].checked ) {
							serviceID = serviceField[ i ].value;
						}
					};
				}

			}

			if ( opts.providerIsset ) {
				if ( opts.provider.id ) {
					providerID = opts.provider.id;
				} else {
					if ( ! providerField ) {
						providerField = document.querySelector( '[name="' + opts.provider.field + '"]' );
					}

					if ( providerField && providerField.value ) {
						providerID = providerField.value;
					}
				}

			}

			if ( serviceID ) {
				refreshDates( serviceID, providerID )
			}

		}

		this.init = function () {

			if ( ! opts.service ) {
				alert( 'Please set service field for current calendar' );
			}

			createCalendar();

			opts.button_prev = instance.querySelector( '[data-calendar-toggle=previous]' );
			opts.button_next = instance.querySelector( '[data-calendar-toggle=next]' );
			opts.month       = instance.querySelector( '[data-calendar-area=month]' );
			opts.month_label = instance.querySelector( '[data-calendar-label=month]' );

			opts.date.setDate( 1 );
			createInputs();
			createMonth();
			setWeekDayHeader();

			maybeRefreshDatesOnInit();

			addEvent( opts.button_prev, 'click', monthPrev );
			addEvent( opts.button_next, 'click', monthNext );

			document.addEventListener( 'click', function ( event ) {

				if ( ! event.target.matches( '.jet-apb-calendar-date-body' ) ) {
					return;
				}

				var day = event.target.parentNode;

				if ( ! day.matches( '[data-status="true"]' ) ) {
					return;
				}

				selectDate( day );

			}, false );

			document.addEventListener( 'click', function ( event ) {

				if ( ! event.target.matches( '.jet-apb-slot' ) ) {
					return;
				}

				instance.querySelectorAll( '.jet-apb-slot--selected' ).forEach( function( el ) {
					el.classList.remove( 'jet-apb-slot--selected' );
				} );

				event.target.classList.add( 'jet-apb-slot--selected' );

				setValue( event.target.dataset.date, event.target.dataset.slot, event.target.dataset.slotEnd, event.target.dataset.price );

			}, false );

			if ( opts.service.field ) {

				if ( ! serviceField ) {
					serviceField = document.querySelectorAll( '[name="' + opts.service.field + '"]' );
				}

				if ( serviceField ) {

					if ( opts.allowedServices && opts.allowedServices.length ) {
						for ( var i = 0; i < serviceField.length; i++ ) {

							if ( 'INPUT' === serviceField[ i ].nodeName ) {

								if ( 0 > opts.allowedServices.indexOf( serviceField[ i ].value ) ) {
									serviceField[ i ].closest( '.jet-form__field-wrap.radio-wrap' ).remove();
								}

							} else {

								var toRemove = [];
								var service = jQuery( serviceField[ i ] );

								for ( var j = 0; j < serviceField[ i ].options.length; j++ ) {

									if ( ! serviceField[ i ].options[ j ].value ) {
										continue;
									}

									if ( 0 > opts.allowedServices.indexOf( serviceField[ i ].options[ j ].value ) ) {
										toRemove.push( serviceField[ i ].options[ j ].value );
									}
								};

								if ( toRemove.length ) {
									for ( var j = 0; j < toRemove.length; j++ ) {
										service.find( 'option[value="' + toRemove[ j ] + '"]' ).remove();
										//serviceField[ i ].remove( toRemove[ j ] );
									};
								}

							}

						}
					}

					for ( var i = 0; i < serviceField.length; i++ ) {

						serviceField[ i ].addEventListener( 'change', function( event ) {

							if ( event.target.value !== serviceID ) {
								serviceID  = event.target.value;
								if ( ! opts.provider.id ) {
									providerID = false;
								}
								refreshDates( serviceID, providerID );
							} else {
								serviceID  = event.target.value;

								if ( ! opts.provider.id ) {
									providerID = false;
								}
							}

						}, false );

					}

				}

			}

			if ( opts.provider.field && opts.providerIsset ) {

				if ( ! providerField ) {
					providerField = document.querySelector( '[name="' + opts.provider.field + '"]' );
				}

				if ( opts.provider.field ) {

					jQuery( document ).on( 'change', '[name="' + opts.provider.field + '"]', function( event ) {

						if ( event.target.value !== providerID ) {
							providerID = event.target.value;
							refreshDates( serviceID, providerID );
						} else {
							providerID = event.target.value;
						}

					});

					/*providerField.addEventListener( 'change', function( event ) {

						console.log( event );

						if ( event.target.value !== providerID ) {
							providerID = event.target.value;
							refreshDates( serviceID, providerID );
						} else {
							providerID = event.target.value;
						}

					}, false );*/
				}

			}

			document.addEventListener( 'click', function( event ) {

				if ( ! event.target.matches( '.jet-apb-calendar-slots__close' ) ) {
					return;
				}

				removeActiveClass();

			}, false );

			initialized = true;

		}

		this.destroy = function() {

			removeEvent( opts.button_prev, 'click', monthPrev );
			removeEvent( opts.button_next, 'click', monthNext );

			clearCalendar();

			instance.innerHTML = '';

		}

		this.reset = function () {
			initialized = false;
			this.destroy();
			this.init();
		}

		this.set = function( options ) {

			for ( var k in options ) {
				if ( opts.hasOwnProperty( k ) ) {
					opts[ k ] = options[ k ];
				}
			};

			if ( initialized ) {
				this.reset();
			}

		}

		let dataArgs = instance.dataset.args;

		if ( dataArgs ) {
			dataArgs = JSON.parse( dataArgs );
			this.set( dataArgs );
		}

		this.init();

	}

	return VanillaCalendar;

})()

window.VanillaCalendar = VanillaCalendar