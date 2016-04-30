jQuery(function($){
	/*
	 * Process an API key check validity.
	 */
	var busy = false,
		xhr	 = false;

	var concat = ajaxurl.indexOf("?") > 0 ? "&" : "?";

	$('#imagify-settings #api_key').blur(function(){
		var obj   = $(this),
			value = obj.val();

		if( $.trim(value) === '' ) {
			return false;
		}

		if( $('#check_api_key').val() === value ) {
			$('#imagify-check-api-container').html('<span class="dashicons dashicons-yes"></span> ' + imagifyAdmin.labels.ValidApiKeyText);
			return false;
		}

		if ( true === busy ) {
			xhr.abort();
		} else {
			$('#imagify-check-api-container').remove();
			obj.after( '<span id="imagify-check-api-container"><span class="imagify-spinner"></span>' + imagifyAdmin.labels.waitApiKeyCheckText + "</span>" );
		}

		busy = true;

		xhr = $.get(ajaxurl+concat+"action=imagify_check_api_key_validity&api_key="+obj.val()+"&imagifycheckapikeynonce="+$('#imagifycheckapikeynonce').val())
		.done(function(response){
			if( !response.success ) {
				$('#imagify-check-api-container').html( '<span class="dashicons dashicons-no"></span> ' + response.data);
			} else {
				$('#imagify-check-api-container').remove();
				swal({
					title: imagifyAdmin.labels.ApiKeyCheckSuccessTitle,
					text: imagifyAdmin.labels.ApiKeyCheckSuccessText,
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

	/**
	 * Check the boxes by clicking "labels" (aria-describedby items)
	 */
	$('.imagify-options-line').css('cursor', 'pointer').on('click', function(e){
		if ( e.target.nodeName === 'INPUT' ) {
			return;
		}
		$('input[aria-describedby="' + $(this).attr('id') + '"]').trigger('click');
		return false;
	});

	$('.imagify-settings th span').on('click', function(e){
		if ( $(this).parent().next('td').find('input:checkbox').length === 1 ) {
			$(this).parent().next('td').find('input:checkbox').trigger('click');
		}
	})

	/**
	 * Auto check on options-line input value change
	 */
	$('.imagify-options-line').find('input').on('change focus', function(){
		var $checkbox = $(this).closest('.imagify-options-line').prev('label').prev('input');
		if ( ! $checkbox[0].checked ) {
			$checkbox.prop('checked', true);
		}
	});

	/**
	 * Imagify Backup alert
	 */
	$('.imagify-settings-section').find('#backup').on('change', function(){
		if ( ! $(this).is(':checked') ) {
			var $_this = $(this);
			swal({
				title: imagifyOptions.noBackupTitle,
				text: imagifyOptions.noBackupText,
				type: "info",
				customClass: "imagify-sweet-alert",
				showCancelButton: true,
				cancelButtonText: imagifyAdmin.labels.swalCancel
			}, function(isConfirm){
				if ( ! isConfirm ) {
					$_this.prop('checked', true);
				}
			});
		}
	});

});