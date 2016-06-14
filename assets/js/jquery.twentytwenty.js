(function(window, $, undefined){

	$.fn.twentytwenty = function(options, callback) {
		var options = $.extend({
			handlePosition		: 0.5,
			orientation			: 'horizontal',
			labelBefore			: 'Before',
			labelAfter			: 'After'
		}
		, options);
		return this.each(function() {

			var sliderPct			= options.handlePosition,
				$container			= $(this),
				sliderOrientation	= options.orientation,
				beforeDirection		= (sliderOrientation === 'vertical') ? 'down' : 'left',
				afterDirection		= (sliderOrientation === 'vertical') ? 'up' : 'right',
				$beforeImg			= $container.find('img:first'),
				$afterImg			= $container.find('img:last');
			
			
			$container.wrap('<div class="twentytwenty-wrapper twentytwenty-' + sliderOrientation + '"></div>');
			$container.append('<div class="twentytwenty-overlay"></div>');
			$container.append('<div class="twentytwenty-handle"></div>');
			
			var $slider = $container.find('.twentytwenty-handle');

			$slider.append('<span class="twentytwenty-' + beforeDirection + '-arrow"></span>');
			$slider.append('<span class="twentytwenty-' + afterDirection + '-arrow"></span>');
			$container.addClass('twentytwenty-container');
			$beforeImg.addClass('twentytwenty-before');
			$afterImg.addClass('twentytwenty-after');
			
			var $overlay = $container.find('.twentytwenty-overlay');

			$overlay.append('<div class="twentytwenty-labels twentytwenty-before-label"><span class="twentytwenty-label-content">' + options.labelBefore + '</span></div>');
			$overlay.append('<div class="twentytwenty-labels twentytwenty-after-label"><span class="twentytwenty-label-content">' + options.labelAfter + '</span></div>');


			// some usefull function and vars declarations
			
			var calcOffset = function(dimensionPct) {
					var w = $beforeImg.width();
					var h = $beforeImg.height();
					return {
						w: w+"px",
						h: h+"px",
						cw: (dimensionPct*w)+"px",
						ch: (dimensionPct*h)+"px"
					};
				},

				adjustContainer = function( offset ) {
					// make it dynamic, in case "before" image change
					var $beforeImg = $container.find('.twentytwenty-before');

					if ( sliderOrientation === 'vertical' ) {
						$beforeImg.css( 'clip', 'rect(0,' + offset.w + ',' + offset.ch + ',0)' );
					}
					else {
						$beforeImg.css( 'clip', 'rect(0,' + offset.cw + ',' + offset.h + ',0)' );
					}
					$container.css( 'height', offset.h );

					if ( typeof callback === 'function' ) {
						callback();
					}
				},
				adjustSlider = function( pct ) {
					var offset = calcOffset(pct);
					$slider.css( ( sliderOrientation === 'vertical' ) ? 'top' : 'left', ( sliderOrientation === 'vertical' ) ? offset.ch : offset.cw );
					adjustContainer( offset );
				},
				offsetX = 0,
				offsetY = 0,
				imgWidth = 0,
				imgHeight = 0;

			$(window).on('resize.twentytwenty', function(e) {
				adjustSlider( sliderPct );
			});
			
			$slider.on('movestart', function(e) {
				if ( ( ( e.distX > e.distY && e.distX < -e.distY ) || ( e.distX < e.distY && e.distX > -e.distY ) ) && sliderOrientation !== 'vertical' ) {
					e.preventDefault();
				}
				else if ( ( ( e.distX < e.distY && e.distX < -e.distY ) || ( e.distX > e.distY && e.distX > -e.distY ) ) && sliderOrientation === 'vertical' ) {
					e.preventDefault();
				}
				$container.addClass('active');
				offsetX 	= $container.offset().left;
				offsetY 	= $container.offset().top;
				imgWidth 	= $beforeImg.width(); 
				imgHeight 	= $beforeImg.height();          
			});

			$slider.on('moveend', function(e) {
				$container.removeClass('active');
			});

			$slider.on('move', function(e) {
				if ( $container.hasClass('active') ) {

					sliderPct = ( sliderOrientation === 'vertical' ) ? ( e.pageY-offsetY )/imgHeight : ( e.pageX-offsetX )/imgWidth;

					if ( sliderPct < 0 ) {
						sliderPct = 0;
					}
					if ( sliderPct > 1 ) {
						sliderPct = 1;
					}
					adjustSlider( sliderPct );
				}
			});

			$container.find('img').on('mousedown', function(event) {
				event.preventDefault();
			});

			$(window).trigger('resize.twentytwenty');
		});
	};

})(window, jQuery);

/**
 * Twentytwenty Imagify Init
 */
(function($, window, document, undefined){

	/*
	 * Mini chart
	 *
	 * @param {element} canvas
	 */	
	var draw_me_a_chart = function ( canvas ) {
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
					segmentStrokeColor	: '#2A2E3C',
					segmentStrokeWidth	: 1,
					animateRotate		: true,
					percentageInnerCutout: 60,
					tooltipEvents		: []
				});
		});
	};

	/**
	 * Dynamic modal
	 *
	 * @param {object}	Parameters to build modal with datas
	 */
	var imagify_open_modal = function( $the_link ){

			var the_target = $the_link.data('target') || $the_link.attr('href');

			$( the_target ).css('display', 'flex').hide().fadeIn(400).attr('aria-hidden', 'false').attr('tabindex', '0').focus().removeAttr('tabindex').addClass('modal-is-open');
			$('body').addClass('imagify-modal-is-open');

		},
		imagify_twenty_modal = function( options ) {
		
		var defaults = {
				width: 0, //px
				height: 0, //px
				original_url: '', //url
				optimized_url: '', //url
				original_size: 0, //mb
				optimized_size: 0, // mb
				saving: 0, //percent
				modal_append_to: $('body'), // jQuery element
				trigger: $('[data-target="imagify-visual-comparison"]'), // jQuery element (button, link) with data-target="modal_id"
				modal_id: 'imagify-visual-comparison', // should be dynamic if multiple modals
				open_modal: false
			},
			settings = $.extend({}, defaults, options);

		if ( settings.width === 0 || settings.height === 0 || settings.original_url === ''|| settings.optimized_url === '' || settings.original_size === 0 || settings.optimized_size === 0 || settings.saving === 0 ) {
			return 'error';
		}

		// create modal box
		settings.modal_append_to.append(''
			+ '<div id="' + settings.modal_id + '" class="imagify-modal imagify-visual-comparison" aria-hidden="true">'
				+ '<div class="imagify-modal-content loading">'
					+ '<div class="twentytwenty-container">'
						+ '<img class="imagify-img-before" alt="" width="' + settings.width + '" height="' + settings.height + '">'
						+ '<img class="imagify-img-after" alt="" width="' + settings.width + '" height="' + settings.height + '">'
					+ '</div>'
					+ '<div class="imagify-comparison-levels">'
						+ '<div class="imagify-c-level imagify-level-original go-left">'
							+ '<p class="imagify-c-level-row">'
								+ '<span class="label">' + imagifyTTT.labels.filesize + '</span>'
								+ '<span class="value level">' + settings.original_size + '</span>'
							+ '</p>'
						+ '</div>'
						+ '<div class="imagify-c-level imagify-level-optimized go-right">'
							+ '<p class="imagify-c-level-row">'
								+ '<span class="label">' + imagifyTTT.labels.filesize + '</span>'
								+ '<span class="value level">' + settings.optimized_size + '</span>'
							+ '</p>'
							+ '<p class="imagify-c-level-row">'
								+ '<span class="label">' + imagifyTTT.labels.saving + '</span>'
								+ '<span class="value"><span class="imagify-chart"><span class="imagify-chart-container"><canvas id="imagify-consumption-chart-normal" width="15" height="15"></canvas></span></span><span class="imagify-chart-value">' + settings.saving + '</span>%</span>'
							+ '</p>'
						+'</div>'
					+ '</div>'
					+ '<button class="close-btn absolute" type="button"><i aria-hidden="true" class="dashicons dashicons-no-alt"></i><span class="screen-reader-text">' + imagifyTTT.labels.close + '</span></button>'
				+ '</div>'
			+ '</div>'
		);

		settings.trigger.on('click.imagify', function(){

			var $modal = $( $(this).data('target') ),
				imgs_loaded = 0;

			if ( typeof imagify_open_modal === 'function' && settings.open_modal ) {
				imagify_open_modal( $(this) );
			}

			$modal.find('.imagify-modal-content').css({
				'width'		: ($(window).outerWidth()*0.85) + 'px',
				'max-width'	: settings.width
			});

			// load before img
			$modal.find('.imagify-img-before').on('load', function(){
				imgs_loaded++;
			}).attr('src', settings.original_url);

			// load after img
			$modal.find('.imagify-img-after').on('load', function(){
				imgs_loaded++;
			}).attr('src', settings.optimized_url);

			var $tt = $modal.find('.twentytwenty-container'),
				check_load = setInterval( function(){

					if ( imgs_loaded === 2 ) {
						$tt.twentytwenty({
							handlePosition: 0.3,
							orientation: 	'horizontal',
							labelBefore: 	imagifyTTT.labels.original_l,
							labelAfter: 	imagifyTTT.labels.optimized_l
						}, function(){

							var windowH	= $(window).height(),
								ttH 	= $modal.find('.twentytwenty-container').height(),
								ttTop	= $modal.find('.twentytwenty-wrapper').position().top;

							if ( ! $tt.closest('.imagify-modal-content').hasClass('loaded') ) {
								$tt.closest('.imagify-modal-content').removeClass('loading').addClass('loaded');
								draw_me_a_chart( $modal.find('.imagify-level-optimized').find('.imagify-chart').find('canvas') );
							}

							// check if image height is to big
							if ( windowH < ttH && ! $modal.hasClass('modal-is-too-high') ) {
								$modal.addClass('modal-is-too-high');

								var $handle		= $modal.find('.twentytwenty-handle'),
									$labels		= $modal.find('.twentytwenty-label-content'),
									$datas		= $modal.find('.imagify-comparison-levels'),
									datasH		= $datas.outerHeight(),
									handle_pos	= ( windowH - ttTop - $handle.height() ) / 2,
									labels_pos	= ( windowH - ttTop * 3 - datasH );

								$handle.css({
									top: handle_pos
								});
								$labels.css({
									top: labels_pos,
									bottom: 'auto'
								});
								$modal.find('.twentytwenty-wrapper').css({
									paddingBottom: datasH
								});

								$modal.find('.imagify-modal-content').on('scroll.imagify', function(){
									$handle.css({
										top: handle_pos + $(this).scrollTop()
									});
									$labels.css({
										top: labels_pos + $(this).scrollTop()
									});
									$datas.css({
										bottom: - ( $(this).scrollTop() )
									});
								});
							}

						});
						clearInterval( check_load );
						check_load = null;
						return 'done';
					}
				}, 75 );

			return false;
		});
	}; // imagify_twenty_modal( options );


	/**
	 * The complexe visual comparison
	 */
	$('.imagify-visual-comparison-btn').on('click', function(){

		if ( $('.twentytwenty-wrapper').length === 1) {
			return;
		}
		
		$( $(this).data('target') ).find('.imagify-modal-content').css('width', ($(window).outerWidth()*0.95) + 'px');

		if ( $('.twentytwenty-container').length > 0 && $(window).outerWidth() > 800 ) {

			var $tt 				= $('.twentytwenty-container'),
				imgs_loaded			= 0,
				loader 				= $tt.data('loader'),
				label_original		= $tt.data('label-original'),
				label_normal		= $tt.data('label-normal'),
				label_aggressive	= $tt.data('label-aggressive'),
				label_ultra			= $tt.data('label-ultra'),

				original_label 		= $tt.data('original-label').replace(/\*\*/, '<strong>').replace(/\*\*/, '</strong>'),
				original_alt 		= $tt.data('original-alt'),
				original_src 		= $tt.data('original-img'),
				original_dim 		= $tt.data('original-dim').split('x'),

				normal_label 		= $tt.data('normal-label').replace(/\*\*/, '<strong>').replace(/\*\*/, '</strong>'),
				normal_alt 			= $tt.data('normal-alt'),
				normal_src 			= $tt.data('normal-img'),
				normal_dim 			= $tt.data('normal-dim').split('x'),

				aggressive_label 	= $tt.data('aggressive-label').replace(/\*\*/, '<strong>').replace(/\*\*/, '</strong>'),
				aggressive_alt 		= $tt.data('aggressive-alt'),
				aggressive_src 		= $tt.data('aggressive-img'),
				aggressive_dim 		= $tt.data('aggressive-dim').split('x'),

				ultra_label 		= $tt.data('ultra-label').replace(/\*\*/, '<strong>').replace(/\*\*/, '</strong>'),
				ultra_alt 			= $tt.data('ultra-alt'),
				ultra_src 			= $tt.data('ultra-img'),
				ultra_dim 			= $tt.data('ultra-dim').split('x'),

				tt_before_buttons	= '<span class="twentytwenty-duo-buttons twentytwenty-duo-left">' +
										'<button type="button" class="imagify-comparison-original selected" data-img="original">' + label_original + '</button>' +
										'<button type="button" class="imagify-comparison-normal" data-img="normal">' + label_normal + '</button>' +
										'<button type="button" class="imagify-comparison-aggressive" data-img="aggressive">' + label_aggressive + '</button>' +
									'</span>',
				tt_after_buttons	= '<span class="twentytwenty-duo-buttons twentytwenty-duo-right">' +
										'<button type="button" class="imagify-comparison-normal" data-img="normal">' + label_normal + '</button>' +
										'<button type="button" class="imagify-comparison-aggressive" data-img="aggressive">' + label_aggressive + '</button>' +
										'<button type="button" class="imagify-comparison-ultra selected" data-img="ultra">' + label_ultra + '</button>' +
									'</span>';

			// loader
			$tt.before('<img class="loader" src="' + loader + '" alt="Loading…" width="64" height="64">')

			// add switchers button only if needed
			// should be more locally integrate...
			var duo_buttons = ( $('.twentytwenty-left-buttons').lenght ) ? tt_before_buttons + tt_after_buttons : '';
			// should be more locally integrate...
			$('.twentytwenty-left-buttons').append(tt_before_buttons);		
			$('.twentytwenty-right-buttons').append(tt_after_buttons);		

			// add images to 50/50 area
			$tt.closest('.imagify-modal-content').addClass('loading').find('.twentytwenty-container').append(
					'<img class="img-original" alt="' + original_alt + '" width="' + original_dim[0] + '" height="' + original_dim[1] + '">' +
					'<img class="img-normal" alt="' + normal_alt + '" width="' + normal_dim[0] + '" height="' + normal_dim[1] + '">' + 
					'<img class="img-aggressive" alt="' + aggressive_alt + '" width="' + aggressive_dim[0] + '" height="' + aggressive_dim[1] + '">' +
					'<img class="img-ultra" alt="' + ultra_alt + '" width="' + ultra_dim[0] + '" height="' + ultra_dim[1] + '">' +
					duo_buttons
			);

			// load image original
			$('.img-original').on('load', function(){
				imgs_loaded++;
			}).attr('src', original_src);

			// load image normal
			$('.img-normal').on('load', function(){
				imgs_loaded++;
			}).attr('src', normal_src);

			// load image aggressive
			$('.img-aggressive').on('load', function(){
				imgs_loaded++;
			}).attr('src', aggressive_src);

			// load image ultra
			$('.img-ultra').on('load', function(){
				imgs_loaded++;
			}).attr('src', ultra_src);

			var twenty_me = setInterval(function(){
				if ( imgs_loaded === 4 ) {
					$tt.twentytwenty({
						handlePosition: 0.6,
						orientation: 	'horizontal',
						labelBefore: 	original_label,
						labelAfter: 	ultra_label
					}, function(){
						// fires on initialisation & each time the handle is moving
						if ( ! $tt.closest('.imagify-modal-content').hasClass('loaded') ) {
							$tt.closest('.imagify-modal-content').removeClass('loading').addClass('loaded');
							draw_me_a_chart( $('.imagify-level-ultra').find('.imagify-chart').find('canvas') );
						}
					});
					clearInterval( twenty_me );
					twenty_me = null;
				}
			}, 75);

			// on click on button choices
			
			$('.imagify-comparison-title').on('click', '.twentytwenty-duo-buttons button:not(.selected)', function(e){

				e.stopPropagation();

				var $_this 		= $(this),
					$container 	= $_this.closest('.imagify-comparison-title').nextAll('.twentytwenty-wrapper').find('.twentytwenty-container'),
					side		= $_this.closest('.twentytwenty-duo-buttons').hasClass('twentytwenty-duo-left') ? 'left' : 'right',
					$other_side	= side === 'left' ? $_this.closest('.imagify-comparison-title').find('.twentytwenty-duo-right') : $_this.closest('.imagify-comparison-title').find('.twentytwenty-duo-left'),
					$duo 		= $_this.closest('.twentytwenty-duo-buttons').find('button'),
					$img_before	= $container.find('.twentytwenty-before'),
					$img_after	= $container.find('.twentytwenty-after'),
					image 		= $_this.data('img');

				// button coloration
				$duo.removeClass('selected');
				$_this.addClass('selected');

				// other side action (to not compare same images)
				if ( $other_side.find('.selected').data('img') === image ) {
					$other_side.find('button:not(.selected)').eq(0).trigger('click');
				}

				// left buttons
				if ( side === 'left' ) {
					var clip_styles = $img_before.css('clip');
					$img_before.attr('style', '');
					$img_before.removeClass('twentytwenty-before');
					$container.find( '.img-' + image ).addClass('twentytwenty-before').css('clip', clip_styles);
					$('.twentytwenty-before-label').find('.twentytwenty-label-content').text( $container.data( image + '-label' ) );
					$('.imagify-c-level.go-left').attr('aria-hidden', 'true').removeClass('go-left go-right');
					$('.imagify-level-' + image).attr('aria-hidden', 'false').addClass('go-left');
				}

				// right buttons
				if ( side === 'right' ) {
					$img_after.removeClass('twentytwenty-after')
					$container.find( '.img-' + image ).addClass('twentytwenty-after');
					$('.twentytwenty-after-label').find('.twentytwenty-label-content').text( $container.data( image + '-label' ) );
					$('.imagify-c-level.go-right').attr('aria-hidden', 'true').removeClass('go-left go-right');
					$('.imagify-level-' + image).attr('aria-hidden', 'false').addClass('go-right');
				}

				draw_me_a_chart( $('.imagify-level-' + image).find('.imagify-chart').find('canvas') );

				return false;

			});
		}

	});


	/**
	 * Imagify comparison inside Media post visualization
	 */
	if ( $('.post-php').find('.wp_attachment_image').find('.thumbnail').length > 0 ) {
		
		var $ori_parent = $('.post-php').find('.wp_attachment_image'),
			$thumbnail	= $ori_parent.find('.thumbnail'),
			thumb		= { src: $thumbnail.prop('src'), width: $thumbnail.width(), height: $thumbnail.height() },
			ori_source	= { src: $('#imagify-full-original').val(), size: $('#imagify-full-original-size').val() },
			$optimize_btn = $('#misc-publishing-actions').find('.misc-pub-imagify').find('.button-primary'),
			width_limit = 360;

		// if shown image > 360, use twentytwenty
		if ( thumb.width > width_limit && $('#imagify-full-original').length > 0 && $('#imagify-full-original').val() !== '' ) {

			var imgs_loaded = 0,
				filesize	= $('.misc-pub-filesize').find('strong').text(),
				saving		= $('.imagify-data-item').find('.imagify-chart-value').text();

			// create button to trigger
			$('[id^="imgedit-open-btn-"]').before('<button type="button" class="imagify-button-primary button-primary imagify-modal-trigger" data-target="#imagify-visual-comparison" id="imagify-start-comparison">' + imagifyTTT.labels.compare + '</button>')

			// Modal and trigger event creation
			var is_modalified = imagify_twenty_modal({
				width:				thumb.width,
				height:				thumb.height,
				original_url:		ori_source.src,
				optimized_url:		thumb.src,
				original_size:		ori_source.size,
				optimized_size:		filesize,
				saving:				saving,
				modal_append_to:	$ori_parent,
				trigger:			$('#imagify-start-comparison'),
				modal_id:			'imagify-visual-comparison'
			});
		}
		// else put images next to next
		else if ( thumb.width < width_limit && $('#imagify-full-original').length > 0 && $('#imagify-full-original').val() !== '' ) {
			// TODO
		}
		// if image has no backup
		else if ( $('#imagify-full-original').length > 0 && $('#imagify-full-original').val() === '' ) {
			// do nothing ?
		}
		// in case image is not optimized
		else {
			// if is not in optimizing process, propose the Optimize button trigger
			if ( $('#misc-publishing-actions').find('.misc-pub-imagify').find('.button-primary').length === 1 ) {
				$('[id^="imgedit-open-btn-"]').before('<span class="spinner imagify-hidden"></span><a class="imagify-button-primary button-primary imagify-optimize-trigger" id="imagify-optimize-trigger" href="' + $optimize_btn.attr('href') + '">' + imagifyTTT.labels.optimize + '</a>');

				$('#imagify-optimize-trigger').on('click', function(){
					$(this).prev('.spinner').removeClass('imagify-hidden').addClass('is-active');
				});
			}
		}

	}

	/**
	 * Images comparison in attachments list page (upload.php)
	 */
	if ( $('.upload-php').find('.imagify-compare-images').length > 0 ) {

		$('.imagify-compare-images').each(function(){

			var $_this = $(this),
				id = $_this.data('id'),
				$datas = $_this.closest('#post-' + id ).find('.column-imagify_optimized_file'),
				
				// Modal and trigger event creation
				is_modalified = imagify_twenty_modal({
					width:				$_this.data('full-width'),
					height:				$_this.data('full-height'),
					original_url:		$_this.data('backup-src'),
					optimized_url:		$_this.data('full-src'),
					original_size:		$datas.find('.original').text(),
					optimized_size:		$datas.find('.imagify-data-item').find('.big').text(),
					saving:				$datas.find('.imagify-chart-value').text(),
					modal_append_to:	$_this.closest('.column-primary'),
					trigger:			$_this,
					modal_id:			'imagify-comparison-' + id
				});

		});
	}

	/**
	 * Images Comparison in Grid View modal
	 */
	if ( $('.upload-php').length > 0 ) {


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
			imagify_content_in_modal = function() {
			
				var tempTimer = setInterval( function(){
						if ( $('.media-modal').find('.imagify-datas-details').length ) {
							if ( $('#imagify-original-src').length > 0 && $('#imagify-original-src') !== '' ) {

								// trigger creation
								$('.media-frame-content').find('.attachment-actions').prepend( '<button type="button" class="imagify-button-primary button-primary imagify-modal-trigger" data-target="#imagify-comparison-modal" id="imagify-media-frame-comparison-btn">' + imagifyTTT.labels.compare + '</button>' );

								// get datas
								var $datas = $('.media-frame-content').find('.compat-field-imagify');

								// Modal and trigger event creation
								is_modalified = imagify_twenty_modal({
									width:				$('#imagify-full-width').val(),
									height:				$('#imagify-full-height').val(),
									original_url:		$('#imagify-original-src').val(),
									optimized_url:		$('#imagify-full-src').val(),
									original_size:		$('#imagify-original-size').val(),
									optimized_size:		$datas.find('.imagify-data-item').find('.big').text(),
									saving:				$datas.find('.imagify-chart-value').text(),
									modal_append_to:	$('.media-frame-content').find('.thumbnail-image'),
									trigger:			$('#imagify-media-frame-comparison-btn'),
									modal_id:			'imagify-comparison-modal',
									open_modal: true
								});
							}

							clearInterval(tempTimer);
							tempTimer = null;
						}
					}, 20 );
			},
			waitContent = setInterval( function() {
				if ( $('.upload-php').find('.media-frame.mode-grid').find('.attachments').length > 0 ) {
					
					// if attachment is clicked, build the modal inside the modal
					$('.upload-php').find('.media-frame.mode-grid').on('click', '.attachment', function(){
						imagify_content_in_modal();
					});

					// if attachment is mentionned in URL, build the modal inside the modal
					if ( get_var('item') ){
						imagify_content_in_modal();
					}

					clearInterval(waitContent);
					waitContent = null;
				}
			}, 100);

		// if URL contain item, that will open the WP Modal View
	}

})(jQuery, window, document);
