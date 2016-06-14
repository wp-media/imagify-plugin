jQuery(function($){

	var concat = ajaxurl.indexOf("?") > 0 ? "&" : "?";

	// avoid error on IE
	var imagify = {
			log: function (content) {
				if (console !== 'undefined') console.log(content);
			}
	};

	var overviewCanvas = document.getElementById("imagify-overview-chart"),
		overviewData = [
		{
			value: imagifyBulk.labels.totalUnoptimizedAttachments,
			color:"#D9E4EB",
			highlight: "#D9E4EB",
			label: imagifyBulk.labels.overviewChartLabels.unoptimized
		},
		{
			value: imagifyBulk.labels.totalOptimizedAttachments,
			color: "#46B1CE",
			highlight: "#46B1CE",
			label: imagifyBulk.labels.overviewChartLabels.optimized
		},
		{
			value: imagifyBulk.labels.totalErrorsAttachments,
			color: "#2E3242",
			highlight: "#2E3242",
			label: imagifyBulk.labels.overviewChartLabels.error
		}
	];

	// to avoid JS error
	if ( overviewCanvas ) {
		var overviewDoughnut = new Chart(overviewCanvas.getContext("2d")).Doughnut(overviewData, {
			segmentStrokeColor : "transparent",
			segmentStrokeWidth : 0,
			animateRotate : true,
			animation: true,
			percentageInnerCutout: 85,
			legendTemplate : "<ul class=\"imagify-<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<segments.length; i++){%><li><span style=\"background-color:<%=segments[i].fillColor%>\"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>",
			tooltipTemplate: "<%= value %>"
		});

		//then you just need to generate the legend 
		//var overviewLegend = overviewDoughnut.generateLegend();
		//bugged `segments undefined` ?

		//and append it to your page somewhere
		overviewLegend = '<ul class="imagify-doughnut-legend">';
		
		$(overviewData).each(function(i){
			overviewLegend += '<li><span style="background-color:' + overviewData[i].color + '"></span>' + overviewData[i].label + '</li>';
		});

		overviewLegend += '</ul>';
		
		document.getElementById("imagify-overview-chart-legend").innerHTML = overviewLegend;
	}
	
	// Heartbeat
	$(document).on('heartbeat-send', function(e, data) {
        data['imagify_heartbeat'] = imagifyBulk.heartbeat_id;
    });
	
	// Listen for the custom event "heartbeat-tick" on $(document).
    $(document).on( 'heartbeat-tick', function(e, data) {
        if ( ! data['imagify_bulk_data'] ) {
            return;
        }
        
        data = data['imagify_bulk_data'];
        
        // The overview chart percent
		$('#imagify-overview-chart-percent').html(data.optimized_attachments_percent + '<span>%</span>');
		$('.imagify-total-percent').html(data.optimized_attachments_percent + '%' );
		
		// The comsuption bar
		$('.imagify-unconsumed-percent').html(data.unconsumed_quota + '%');
		$('.imagify-unconsumed-bar').animate({'width': data.unconsumed_quota + '%'});

		// The total optimized images
		$('#imagify-total-optimized-attachments').html(data.already_optimized_attachments);
		
		// The original bar
		$('#imagify-original-bar').find('.imagify-barnb')
								  .html(data.original_human);

		// The optimized bar
		$('#imagify-optimized-bar').animate({'width': data.optimized_percent+"%"})
		$('#imagify-optimized-bar').find('.imagify-barnb')
								   .html(data.optimized_human);
								   
		// The Percent data
		$('#imagify-total-optimized-attachments-pct').html( data.optimized_percent + '%' );
		
		overviewDoughnut.segments[0].value = data.unoptimized_attachments;
		overviewDoughnut.segments[1].value = data.optimized_attachments;
		overviewDoughnut.segments[2].value = data.errors_attachments;
		overviewDoughnut.update();
    });

	// Simulate a click on the "Imagif'em all" button
	$('#imagify-simulate-bulk-action').click(function(e){
		e.preventDefault();
		$('#imagify-bulk-action').trigger('click');
	});

	$('#imagify-bulk-action').click(function(){
		var $obj = $(this),
			$optimization_level = $('[name="optimization_level"]:checked').val();

		if (typeof $optimization_level === "undefined") {
		    $optimization_level = -1;
		}
		
		if ( $obj.attr('disabled') ) {
			return false;
		}

		$obj.attr('disabled', 'disabled');
		$obj.find('.dashicons').addClass('rotate');
		
		
		confirmMessage =  function(){
			return imagifyBulk.labels.processing;
		};
		$(window).on('beforeunload', confirmMessage);
		
		// Display an alert to wait
		swal({
			title:imagifyBulk.labels.waitTitle,
			text: imagifyBulk.labels.waitText,
			closeOnConfirm: false,
			showConfirmButton: false,
			html: true,
			imageUrl: imagifyBulk.labels.waitImageUrl
		});
		
		$.get(ajaxurl+concat+"action="+imagifyBulk.ajax_action+"&optimization_level="+$optimization_level+"&imagifybulkuploadnonce="+$('#imagifybulkuploadnonce').val())
		.done(function(response) {
			if( !response.success ) {
				$obj.removeAttr('disabled');
				$obj.find('.dashicons').removeClass('rotate');
				
				swal_title = '';
				swal_text  = '';
				
				// remove confirm dialog before quit the page
				$(window).off('beforeunload', confirmMessage);
								
				if ( response.data.message == 'invalid-api-key' ) {
					swal_title = imagifyBulk.labels.invalidAPIKeyTitle;
				}
				
				if ( response.data.message == 'over-quota' ) {
					swal_title = imagifyBulk.labels.overQuotaTitle;
					text  = imagifyBulk.labels.overQuotaText;
				}
				
				if ( response.data.message == 'no-images' ) {
					swal_title = imagifyBulk.labels.noAttachmentToOptimizeTitle;
					swal_text  = imagifyBulk.labels.noAttachmentToOptimizeText;
				}
				
				// Display an alert to warn that all images has been optimized
				swal({
					title		: swal_title,
					text 		: swal_text,
					type	    : "info",
					customClass : "imagify-sweet-alert"
				});

			} else {				
				swal.close();

				var config = {
					'lib': ajaxurl+concat+"action=imagify_bulk_upload&imagifybulkuploadnonce="+$('#imagifybulkuploadnonce').val(),
					'images': response.data,
					'context': imagifyBulk.ajax_context
				}

				var table  = $('.imagify-bulk-table table tbody'),
					files  = 0,
					errors = 0,
					original_overall_size = 0,
					overall_saving = 0;

				$('.imagify-row-progress').slideDown();
				$('.imagify-no-uploaded-yet, .imagify-row-complete').hide(200);

				Optimizer = new ImagifyGulp(config);

				// before the attachment optimization
				Optimizer.before(function(data) {
					table.find('.imagify-row-progress').after('<tr id="attachment-'+data.id+'"><td class="imagify-cell-filename"><span class="imagiuploaded"><img src="'+data.thumbnail+'"/>"</span><span class="imagifilename">'+data.filename+'</span></td><td class="imagify-cell-status"><span class="imagistatus status-compressing"><span class="dashicons dashicons-admin-generic rotate"></span>Compressing<span></span></span></td><td class="imagify-cell-original"></td><td class="imagify-cell-optimized"></td><td class="imagify-cell-percentage"></td><td class="imagify-cell-thumbnails"></td><td class="imagify-cell-savings"></td></tr>');
				})
				// after the attachment optimization
				.each(function (data) {
					var $progress = $('#imagify-progress-bar');
					$progress.css({'width': data.progress + '%'});
					$progress.find('.percent').html(data.progress + '%');

					if ( data.success ) {
						$('#attachment-'+data.image+' .imagify-cell-status').html('<span class="imagistatus status-complete"><span class="dashicons dashicons-yes"></span>Complete</span>');
						$('#attachment-'+data.image+' .imagify-cell-original').html(data.original_size_human);
						$('#attachment-'+data.image+' .imagify-cell-optimized').html(data.new_size_human);
						$('#attachment-'+data.image+' .imagify-cell-percentage').html('<span class="imagify-chart"><span class="imagify-chart-container"><canvas height="18" width="18" id="imagify-consumption-chart" style="width: 18px; height: 18px;"></canvas></span></span><span class="imagipercent">'+data.percent+'</span>%');
					draw_me_a_chart( $('#attachment-'+data.image+' .imagify-cell-percentage').find('canvas') );
						$('#attachment-'+data.image+' .imagify-cell-thumbnails').html(data.thumbnails);
						$('#attachment-'+data.image+' .imagify-cell-savings').html(Optimizer.humanSize(data.overall_saving, 1));

						// The table footer total optimized files
						files = files + data.thumbnails + 1;
						$('.imagify-cell-nb-files').html(files + ' file(s)');

						// The table footer original size
						original_overall_size = original_overall_size + data.original_overall_size;
						$('.imagify-total-original').html(Optimizer.humanSize(original_overall_size, 1));

						// The table footer overall saving
						overall_saving = overall_saving + data.overall_saving;
						$('.imagify-total-gain').html(Optimizer.humanSize(overall_saving, 1));

					} else {
						error_class     = 'error';
						error_dashicon  = 'dismiss';
						error_message   = 'Error';
												
						if ( data.error.indexOf("You've consumed all your data") >= 0 ) {
							swal({
								title: imagifyBulk.labels.overQuotaTitle,
								text: imagifyBulk.labels.overQuotaText,
								type: "error",
								customClass: "imagify-sweet-alert",
								html: true,
							},
							function(){
								location.reload();
							});
						}
						
						if ( data.error.indexOf("This image is already compressed") >=0 ) {
							error_class    = 'warning';
							error_dashicon = 'warning';
							error_message  = 'Notice';
						} else {
							errors++;
							$('.imagify-cell-errors').html(errors + ' error(s)');
						}
						
						$('#attachment-'+data.image).after('<tr><td colspan="7"><span class="status-'+error_class+'">'+data.error+'</span></td></tr>');
						
						$('#attachment-'+data.image+' .imagify-cell-status').html('<span class="imagistatus status-'+error_class+'"><span class="dashicons dashicons-'+error_dashicon+'"></span>'+error_message+'</span>');			
					}
				})
				// after all attachments optimization
				.done(function (data) {
					$obj.removeAttr('disabled');
					$obj.find('.dashicons').removeClass('rotate');

					// remove confirm dialog before quit the page
					$(window).off('beforeunload', confirmMessage);

					// Hide the progress bar
					$('.imagify-row-progress').slideUp();

					if ( data.global_percent !== 'NaN' ) {
						// Display the complete section
						$('.imagify-row-complete').removeClass('hidden')
												  .addClass( 'done' )
												  .attr('aria-hidden', 'false');
						$('html, body').animate({
							scrollTop: $('.imagify-row-complete').offset().top
						}, 200);

						$('.imagify-ac-rt-total-gain').html(data.global_gain_human);
						$('.imagify-ac-rt-total-original').html(data.global_original_size_human);

						text2share = imagifyBulk.labels.textToShare;
						text2share = text2share.replace( '%1$s', data.global_gain_human );
						text2share = text2share.replace( '%2$s', data.global_original_size_human );
						text2share = encodeURIComponent(text2share);

						$('.imagify-sn-twitter').attr( 'href', 'https://twitter.com/intent/tweet?source=webclient&amp;original_referer=' + imagifyBulk.labels.pluginURL + '&amp;text=' + text2share + '&amp;url=' + imagifyBulk.labels.pluginURL + '&amp;related=imagify&amp;hastags=performance,web,wordpress' );
						
						$('.imagify-ac-chart').attr('data-percent', data.global_percent);
						draw_me_complete_chart( $('.imagify-ac-chart').find('canvas') );
					}
				})
				.error(function (id) {
					imagify.log('Can\'t optimize image with id ' + id);
				})
				.run();
			}
		})
		.fail(function () {
			swal({
				title: imagifyBulk.labels.getUnoptimizedImagesErrorTitle,
				text: imagifyBulk.labels.getUnoptimizedImagesErrorText,
				type: "error",
				customClass: "imagify-sweet-alert"
			},
			function(){
				location.reload();
			});
		});
	});

	/*
	 * Mini chart
	 * You can use draw_me_a_chart() function with AJAX calls
	 *
	 * @param {element} canvas
	 */
	function draw_me_a_chart( canvas ) {
		canvas.each(function(){

			var the_value = parseInt( $(this).closest('.imagify-chart').next('.imagipercent').text() ),
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

	/*
	 * Complete Chart
	 * You can use draw_me_complete_chart() function with AJAX calls
	 *
	 * @param {element} canvas
	 */
	function draw_me_complete_chart( canvas ) {
		canvas.each(function(){

			var the_value = parseInt( $(this).closest('.imagify-ac-chart').attr('data-percent') ),
				overviewData = [
				{
					value: the_value,
					color: "#40B1D0"
				},
				{
					value: 100 - the_value,
					color:"#FFFFFF"
				}
				],
				overviewDoughnut = new Chart( $(this)[0].getContext("2d")).Doughnut(overviewData, {
					segmentStrokeColor : "transparent",
					segmentStrokeWidth : 0,
					animateRotate : true,
					animation: true,
					percentageInnerCutout: 70,
					tooltipEvents: []
				});
		});
	}
});

var width = 700, height = 290;
if ( window.innerWidth ) {
	var clientLeft = (window.innerWidth-width)/2;
	var clientTop = (window.innerHeight-height)/2;
}
else {
	var clientLeft = (document.body.clientWidth-width)/2;
	var clientTop = (document.body.clientHeight-height)/2;
}

[].forEach.call( document.querySelectorAll('.imagify-share-networks a'), function(el) {
	el.addEventListener('click', function(evt) {
		window.open(this.href,'',"status=no, scrollbars=no, menubar=no, top="+clientTop+", left="+clientLeft+", width="+width+", height="+height);
		evt.preventDefault();
	}, false);
});