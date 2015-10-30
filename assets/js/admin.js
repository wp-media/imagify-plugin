jQuery(function($){
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
			
			$.get(ajaxurl + "?action=imagify_signup&email=" +inputValue + "&imagifysignupnonce="+ $('#imagifysignupnonce').val())
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
			
			$.get(ajaxurl + "?action=imagify_check_api_key_validity&api_key=" +inputValue + "&imagifycheckapikeynonce="+ $('#imagifycheckapikeynonce').val())
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
			$( $(this).attr('href') ).css('display', 'flex').hide().fadeIn(400).attr('aria-hidden', 'false').attr('tabindex', '0').focus().removeAttr('tabindex');
			return false;
		});

		// on click on close button
		$('.imagify-modal').find('.close-btn').on('click', function(){
			$(this).closest('.imagify-modal').fadeOut(400).attr('aria-hidden', 'true');
		})
		.on('blur', function(){
			var $modal = $(this).closest('.imagify-modal');
			if ( $modal.attr('aria-hidden') === 'false' ) {
				$modal.attr('tabindex', '0').focus().removeAttr('tabindex');
			}
		});

	}
});