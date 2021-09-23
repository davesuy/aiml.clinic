(function () {

	'use strict';

	jQuery( '.appointment-provider' ).each( function() {

		var $this = jQuery( this ),
			data  = $this.data( 'args' );

		var loadServices = function() {

			let $input = jQuery( this );
			let service = $input.val();
			let $loader = $this.find( '.appointment-provider__loader' );
			let isAjax  = window.JetAPBisAjax || false;

			if ( ! service ) {
				return;
			}

			if ( $loader.length ) {
				$loader.removeClass( 'appointment-provider__loader-hidden' );
			}

			jQuery.ajax({
				url: data.api.service_providers,
				type: 'GET',
				dataType: 'json',
				data: {
					service: service,
					custom_template: data.custom_template,
					args_str: data.args_str,
					is_ajax: isAjax,
				},
			}).done( function( response ) {

				if ( $loader.length ) {
					$loader.addClass( 'appointment-provider__loader-hidden' );
				}

				if ( ! data.custom_template ) {
					if ( response.data.length ) {
						$this.html( '<option value="">' + data.placeholder + '</option>' );
						for ( var i = 0; i < response.data.length; i++ ) {
							let item = response.data[ i ];
							$this.append('<option value="' + item.ID + '">' + item.post_title + '</option>' );
						}
					}
				} else {
					if ( response.data ) {
						$this.find( '.appointment-provider__content' ).html( response.data );
					}
				}
			} );

		};

		if ( data.service.field ) {

			if ( $this.is( 'select' ) ) {
				$this.html( '<option value="">' + data.placeholder + '</option>' );
			}

			jQuery( document ).on( 'change', '[name="' + data.service.field + '"]', loadServices );
			jQuery( 'select[name="' + data.service.field + '"]' ).each( loadServices );
			jQuery( 'input[name="' + data.service.field + '"]:checked' ).each( loadServices );

		}

	} );

}());
