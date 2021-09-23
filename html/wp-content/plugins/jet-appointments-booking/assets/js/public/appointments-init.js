(function () {

	'use strict';

	var picker;
	var head = document.getElementsByTagName( 'head' )[0];
	var link = document.createElement( 'link' );

	link.rel   = 'stylesheet';
	link.type  = 'text/css';
	link.href  = window.JetAPBData.css;
	link.media = 'all';

	head.appendChild( link );

	jQuery( document ).on( 'jet-engine/booking-form/init', function() {

		var settings = {
			selector: '.appointment-calendar',
			datesFilter: true,
			pastDates: false,
			weekDays: window.JetAPBData.week_days,
			weekStart: window.JetAPBData.start_of_week,
			api: window.JetAPBData.api,
		};

		if ( window.JetAPBData.months ) {
			settings.months = window.JetAPBData.months;
		}

		if ( window.JetAPBData.shortWeekday ) {
			settings.shortWeekday = window.JetAPBData.shortWeekday;
		}

		var calendar = new VanillaCalendar( settings );

		window.JetEngine.filters.addFilter( 'forms/calculated-field-value', function( value, $field ) {

			if ( 'appointment' === $field.data( 'field' ) ) {
				value = $field.data( 'price' );
			}

			return value;
		} );

	} );

}());
