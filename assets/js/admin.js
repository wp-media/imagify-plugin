jQuery(function($){

	var concat = ajaxurl.indexOf("?") > 0 ? "&" : "?";

	/*
	 * Create a new Imagify account
	 */
	$('#imagify-signup').click( function(e){
		e.preventDefault();

		// Display the sign up form
		swal({
			title: imagify.signupTitle,
			text: imagify.signupText,
			confirmButtonText: imagify.signupConfirmButtonText,
			type: "input",
			closeOnConfirm: false,
			allowOutsideClick: true,
			showLoaderOnConfirm: true,
			customClass: "imagify-sweet-alert imagify-sweet-alert-signup"
		},
		function(inputValue){
			if ($.trim(inputValue) == "" || ! inputValue) {
				swal.showInputError(imagify.signupErrorEmptyEmail);
				return false;
			} 
			
			$.get(ajaxurl + concat + "action=imagify_signup&email=" +inputValue + "&imagifysignupnonce="+ $('#imagifysignupnonce').val())
			.done(function(response){
				if( !response.success ) {
					swal.showInputError(response.data);
				} else {
					swal({
						title:imagify.signupSuccessTitle,
						text: imagify.signupSuccessText,
						type: "success",
						customClass: "imagify-sweet-alert"
					});
				}
			});
		});
	});
	
	/*
	 * Check and save the Imagify API Key
	 */
	$('#imagify-save-api-key').click( function(e){
		e.preventDefault();

		// Display the sign up form
		swal({
			title: imagify.saveApiKeyTitle,
			text: imagify.saveApiKeyText,
			confirmButtonText: imagify.saveApiKeyConfirmButtonText,
			type: "input",
			closeOnConfirm: false,
			allowOutsideClick: true,
			showLoaderOnConfirm: true,
			customClass: "imagify-sweet-alert imagify-sweet-alert-signup"
		},
		function(inputValue){
			if ($.trim(inputValue) == "" || ! inputValue) {
				swal.showInputError(imagify.signupErrorEmptyEmail);
				return false;
			} 
			
			$.get(ajaxurl + concat + "action=imagify_check_api_key_validity&api_key=" +inputValue + "&imagifycheckapikeynonce="+ $('#imagifycheckapikeynonce').val())
			.done(function(response){
				if( !response.success ) {
					swal.showInputError(response.data);
				} else {
					swal({
						title:imagify.ApiKeyCheckSuccessTitle,
						text: imagify.ApiKeyCheckSuccessText,
						type: "success",
						customClass: "imagify-sweet-alert"
					});
				}
			});
		});
	});
	
	/*
	 * Toggle an Imagify notice	 
	 */
	$('.imagify-notice-dismiss').click( function( e ) {
		e.preventDefault();
		
		var obj 	= $(this),
			parent  = obj.parents('.imagify-welcome, .imagify-notice'),
			href 	= obj.attr('href');
			
			// Hide the notice
			parent.fadeTo( 100 , 0, function() {
				$(this).slideUp( 100, function() {
					$(this).remove();
				});
			});
			
			// Save the dismiss notice
			$.get( href.replace( 'admin-post.php', 'admin-ajax.php' ) );		
	});


	/*
	 * Imagify Light modal
	 */
	
	if ( $('.imagify-modal-trigger').length > 0 ) {
		
		// accessibility
		$('.imagify-modal').attr('aria-hidden', 'true');

		// on click on modal trigger
		$('.imagify-modal-trigger').on('click', function(){
			var the_target = $(this).attr('href') || $(this).data('target');

			$( the_target ).css('display', 'flex').hide().fadeIn(400).attr('aria-hidden', 'false').attr('tabindex', '0').focus().removeAttr('tabindex').addClass('modal-is-open');
			$('body').addClass('imagify-modal-is-open');

			return false;
		});

		// on click on close button
		$('.imagify-modal').find('.close-btn').on('click', function(){
			$(this).closest('.imagify-modal').fadeOut(400).attr('aria-hidden', 'true').removeClass('modal-is-open');
			$('body').removeClass('imagify-modal-is-open');
		})
		.on('blur', function(){
			var $modal = $(this).closest('.imagify-modal');
			if ( $modal.attr('aria-hidden') === 'false' ) {
				$modal.attr('tabindex', '0').focus().removeAttr('tabindex');
			}
		});

		// `Esc` key binding
		$(window).on('keydown', function(e){
			if ( e.keyCode == 27 && $('.imagify-modal.modal-is-open').length > 0 ) {

				e.preventDefault();
				
				// trigger the event
				$('.imagify-modal.modal-is-open').find('.close-btn').trigger('click');

				return false;
			}
		});
	}
	
	var busy = false,
		xhr	 = false;
		
	$('#wp-admin-bar-imagify').hover( function() {
		if ( true === busy ) {
			xhr.abort();
		}
		
		busy = true;
		
		var $adminBarProfile = $('#wp-admin-bar-imagify-profile-content');
		
		if( $adminBarProfile.is(':empty') ) {
			xhr = $.get(ajaxurl + concat + "action=imagify_get_admin_bar_profile&imagifygetadminbarprofilenonce="+ $('#imagifygetadminbarprofilenonce').val())
			.done(function(response){
				$adminBarProfile.html(response.data);
				$('#wp-admin-bar-imagify-profile-loading').remove();
				busy = false;
			});	
		}
	});
});