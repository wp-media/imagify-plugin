jQuery(function($){
	/*
	 * Process an API key check validity.
	 */
	var busy = false,
		xhr	 = false;

	$('#imagify-settings #api_key').blur(function(){
		var obj   = $(this),
			value = obj.val();

		if( $.trim(value) === '' ) {
			return false;
		}

		if( $('#check_api_key').val() === value ) {
			$('#imagify-check-api-container').html('<span class="dashicons dashicons-yes"></span> ' + imagify.ValidApiKeyText);
			return false;
		}

		if ( true === busy ) {
			xhr.abort();
		} else {
			$('#imagify-check-api-container').remove();
			obj.after( '<span id="imagify-check-api-container"><span class="imagify-spinner"></span>' + imagify.waitApiKeyCheckText + "</span>" );
		}

		busy = true;

		xhr = $.get(ajaxurl+"?action=imagify_check_api_key_validity&api_key="+obj.val()+"&imagifycheckapikeynonce="+$('#imagifycheckapikeynonce').val())
		.done(function(response){
			if( !response.success ) {
				$('#imagify-check-api-container').html( '<span class="dashicons dashicons-no"></span> ' + response.data);
			} else {
				$('#imagify-check-api-container').remove();
				swal({
					title: imagify.ApiKeyCheckSuccessTitle,
					text: imagify.ApiKeyCheckSuccessText,
					type: "success",
					customClass: "imagify-sweet-alert"
				},
				function(){
					location.reload();
				});
			}

			busy = false;
		});
	});
});