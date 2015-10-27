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
});