jQuery(function($){
	/*
	 * Add a "Imagify'em all" in the select list	 
	 */
	var bulk_opt = '<option value="imagify-bulk-upload">' + imagifyUpload.bulkActionsLabels.optimize + '</option>';
	 	bulk_opt += '<option value="imagify-bulk-restore">' + imagifyUpload.bulkActionsLabels.restore + '</option>';

	$('.bulkactions select[name="action"]').find('option:last-child').before( bulk_opt );
	$('.bulkactions select[name="action2"]').find('option:last-child').before( bulk_opt );
	
	/*
	 * Process optimization for all selected images
	 */
	$('#doaction').add('#doaction2')
				  .on('click', function(e) {
					value = $(this).prev('select').val();
					value = value.split('-');

					if ( 'imagify' !== value[0] ) {
						return;
					}

					e.preventDefault();

					action = value[2];			
					ids    = $('input[name^="media"]:checked').map( function(){
					    return this.value;
					}).get();

					ids.forEach( function( id, index ) {
						setTimeout(function(){
							$('#imagify-' + action + '-' + id ).trigger('click');
						}, index*300);
					});
						
				});
	
	/*
	 * Process to one of these actions: restore, optimize or re-optimize	 
	 */	
	$(document).on('click', '.button-imagify-restore, .button-imagify-manual-upload, .button-imagify-manual-override-upload', function(e){
		e.preventDefault();

		var $obj 	= $(this);
		var $parent = ( $obj.parents('.column-imagify_optimized_file, .compat-field-imagify .field').length ) ? $obj.parents('.column-imagify_optimized_file, .compat-field-imagify .field') : $obj.closest('.column');
		var href 	= $obj.attr('href');

		$parent.html('<div class="button"><span class="imagify-spinner"></span>' + $obj.data('waiting-label') + '</div>');

		$.get( href.replace( 'admin-post.php', 'admin-ajax.php' ) )
		.done( function( response ){
			$parent.html( response.data );
			$parent.find('.imagify-datas-more-action a').addClass('is-open').find('.the-text').text( $parent.find('.imagify-datas-more-action a').data('close') );
			$parent.find('.imagify-datas-details').addClass('is-open');
			
			draw_me_a_chart( $parent.find('.imagify-chart-container').find('canvas') );
		} );
	} );

	/*
	 * Toggle slide in custom column	 
	 */
	$('.imagify-datas-details').hide();

	$(document).on('click', '.imagify-datas-more-action a', function(){
		if ( $(this).hasClass('is-open') ) {
			$( $(this).attr('href') ).slideUp('300').removeClass('is-open');
			$(this).removeClass('is-open').find('.the-text').text( $(this).data('open') );
		}
		else {
			$( $(this).attr('href') ).slideDown('300').addClass('is-open');
			$(this).addClass('is-open').find('.the-text').text( $(this).data('close') );
		}
		return false;
	});

	
	// Some usefull functions to help us with media modal

	var get_var = function (param) {
			var vars = {};
			window.location.href.replace( 
				/[?&]+([^=&]+)=?([^&]*)?/gi, // regexp
				function( m, key, value ) { // callback
					vars[key] = value !== undefined ? value : '';
				}
			);

			if ( param ) {
				return vars[param] ? vars[param] : null;	
			}
			return vars;
		},
		check_modal = function() {
			var tempTimer = setInterval( function(){
				if ( $('.media-modal').find('.imagify-datas-details').length ) {
					$('.media-modal').find('.imagify-datas-details').hide();
					draw_me_a_chart( $('.media-modal').find('#imagify-consumption-chart') );
					clearInterval(tempTimer);
					tempTimer = null;
				}
			}, 20 );
		};
	
	// Intercept the right moment if media details is clicked (mode grid)
	// Bear Feint
	 
	$('.upload-php').find('.media-frame.mode-grid').on('click', '.attachment', function(){
		check_modal();
	});

	// On page load in upload.php check if item param exists
	if ( $('.upload-php').length > 0 && get_var('item') ) {
		check_modal();
	}

	// On media clicked
	$('#insert-media-button').on('click.imagify', function() {
		var waitContent = setInterval( function() {
			if ( $('.media-frame-content').find('.attachments').length > 0 ) {
				$('.media-frame-content').find('.attachments').on('click.imagify', '.attachment', function(){
					check_modal();
				});
				clearInterval(waitContent);
			}
		}, 100);
	});

	/*
	 * Mini chart
	 *
	 * @param {element} canvas
	 */	
	function draw_me_a_chart( canvas ) {
		canvas.each(function(){
		
			var the_value = parseInt( $(this).closest('.imagify-chart').next('.imagify-chart-value').text() ),
				overviewData = [
				{
					value: the_value,
					color: '#00B3D3'
				},
				{
					value: 100 - the_value,
					color: '#D8D8D8'
				}
				],
				overviewDoughnut = new Chart( $(this)[0].getContext('2d')).Doughnut(overviewData, {
					segmentStrokeColor	: '#FFF',
					segmentStrokeWidth	: 1,
					animateRotate		: true,
					tooltipEvents		: []
				});
		});
	}
	draw_me_a_chart( $('.imagify-chart-container').find('canvas') );
});