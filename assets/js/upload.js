jQuery(function($){
	/*
	 * Add a "Imagify'em all" in the select list	 
	 */
	var bulk_opt = '<option value="imagify-bulk-upload">'+imagifyUpload.bulkActionsLabels.optimize+'</option>';
	 	bulk_opt += '<option value="imagify-bulk-restore">'+imagifyUpload.bulkActionsLabels.restore+'</option>';
	$(".bulkactions select[name='action']").find("option:last-child").before(bulk_opt);
    $(".bulkactions select[name='action2']").find("option:last-child").before(bulk_opt);
	
	/*
	 * Process optimization for all selected images
	 */
	$('#doaction').add('#doaction2')
				  .click( function(e) {
					$value = $(this).prev("select").val();
					$value = $value.split('-');
									
					if ( 'imagify' !== $value[0] ) {
						return;
					}
					
					e.preventDefault();
					
					$action = $value[2];			
					$ids    = $( "input[name^=media]:checked" ).map( function(){
					    return this.value;
					}).get();
					
					$ids.forEach(function(id, index) {
						//setTimeout(function(){
							$('#imagify-'+$action+'-'+id).trigger('click');
						//}, index*1000);
					});
						
				});
	
	/*
	 * Process to one of these actions: restore, optimize or re-optimize	 
	 */	
	$(document).on('click', '.button-imagify-restore, .button-imagify-manual-upload, .button-imagify-manual-override-upload', function(e){
		e.preventDefault();

		var $obj 	= $(this);
		var	$parent = $obj.parents('.column-imagify_optimized_file');
		var $href 	= $obj.attr('href');

		$parent.html( '<div class="button"><span class="imagify-spinner"></span>'+$obj.data('waiting-label')+'</div>' );

		$.get( $href.replace( 'admin-post.php', 'admin-ajax.php' ) )
		.done( function(response){
			$parent.html(response.data);
			$parent.find('.imagify-datas-more-action a').addClass('is-open').find('.the-text').text( $parent.find('.imagify-datas-more-action a').data('close') );
			$parent.find('.imagify-datas-details').addClass('is-open');
			
			draw_me_a_chart( $parent.find('.imagify-chart-container').find('canvas') );
		} );
	} );

	/*
	 * Toggle slide in custom column	 
	 */
	$('.imagify-datas-details').hide();

	$('.column-imagify_optimized_file').on('click', '.imagify-datas-more-action a', function(){
		if ( $(this).hasClass('is-open') ) {
			$( $(this).attr('href') ).slideUp('300').removeClass('is-open');
			$(this).removeClass('is-open').find('.the-text').text( $(this).data('open') );
		}
		else {
			$( $(this).attr('href') ).slideDown('300').addClass('is-open');
			$(this).addClass('is-open').find('.the-text').text( $(this).data('close') );;
		}
		return false;
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
					color: "#00B3D3"
				},
				{
					value: 100 - the_value,
					color:"#D8D8D8"
				}
				],
				overviewDoughnut = new Chart( $(this)[0].getContext("2d")).Doughnut(overviewData, {
					segmentStrokeColor : "#FFF",
					segmentStrokeWidth : 1,
					animateRotate : true,
					tooltipEvents: []
				});
		});
	}
	draw_me_a_chart( $('.imagify-chart-container').find('canvas') );
});