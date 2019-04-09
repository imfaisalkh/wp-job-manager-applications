jQuery(document).ready(function($) {

	// Help select2 calculate the true width of multiselect fields in the application form.
	if ( $.isFunction( $.fn.select2 ) && typeof job_manager_select2_args !== 'undefined' ) {
		$( '.application_details select[multiple]' ).each( function() {
			if ( typeof $(this).data( 'chosen' ) !== 'undefined' ) {
				return;
			}
			$(this).select2( job_manager_select2_args );
		} );
	}

	if ( $('.job-manager-applications-error').size() ) {
		$('.application_button').click();
	}

	$('body').on( 'change', '.job-manager-application-form :input', function() {
		$( 'input.wp_job_manager_send_application_button' ).removeAttr( 'disabled', 'disabled' ).removeClass( 'disabled' );
	});

	$('body').on( 'submit', '.job-manager-application-form', function() {
		var form    = $(this);
		var success = true;

		$('.job-manager-applications-error').remove();

		$(this).find(':input[required]').each(function(){
			if ( ! $(this).val() ) {
				var message = job_manager_applications.i18n_required.replace( '%s', $(this).closest('fieldset').find('label').text() );
				form.prepend( '<p class="job-manager-error job-manager-applications-error">' + message + '</p>' );
				success = false;
				return false;
			}
		});

		// Prevent multiple submissions
		if ( success ) {
			$( 'input.wp_job_manager_send_application_button' ).attr( 'disabled', 'disabled' ).addClass( 'disabled' );
		}

		return success;
	});

});
