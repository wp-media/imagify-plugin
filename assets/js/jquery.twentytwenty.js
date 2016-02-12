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
(function(window, $, undefined){

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

	// Imagify comparison inside Media post visualization
	
	if ( $('.post-php').find('.wp_attachment_image').find('.thumbnail').length > 0 ) {
		var $ori_parent = $('.post-php').find('.wp_attachment_image'),
			$thumbnail	= $ori_parent.find('.thumbnail'),
			thumb		= { src: $thumbnail.prop('src'), width: $thumbnail.width(), height: $thumbnail.height() },
			ori_source	= { src: $('#imagify-full-original').val(), size: $('#imagify-full-original-size').val() };

		// if shown image > 300, use twentytwenty
		if ( thumb.width > 300 && $('#imagify-full-original').length > 0 ) {

			var imgs_loaded = 0,
				filesize	= $('.misc-pub-filesize').find('strong').text(),
				saving		= $('.imagify-data-item').find('.imagify-chart-value').text();

			// create button to trigger
			$('[id^="imgedit-open-btn-"]').before('<button type="button" class="imagify-button-primary button-primary imagify-modal-trigger" data-target="#imagify-visual-comparison" id="imagify-start-comparison">' + imagifyTTT.labels.compare + '</button>')

			// create modal box
			$ori_parent.append(''
				+ '<div id="imagify-visual-comparison" class="imagify-modal" aria-hidden="true">'
					+ '<div class="imagify-modal-content loading">'
						+ '<div class="twentytwenty-container">'
							+ '<img class="imagify-img-before" alt="" width="' + thumb.width + '" height="' + thumb.height + '">'
							+ '<img class="imagify-img-after" alt="" width="' + thumb.width + '" height="' + thumb.height + '">'
						+ '</div>'
						+ '<div class="imagify-comparison-levels">'
							+ '<div class="imagify-c-level imagify-level-original go-left">'
								+ '<p class="imagify-c-level-row">'
									+ '<span class="label">' + imagifyTTT.labels.filesize + '</span>'
									+ '<span class="value level">' + ori_source.size + '</span>'
								+ '</p>'
							+ '</div>'
							+ '<div class="imagify-c-level imagify-level-optimized go-right">'
								+ '<p class="imagify-c-level-row">'
									+ '<span class="label">' + imagifyTTT.labels.filesize + '</span>'
									+ '<span class="value level">' + filesize + '</span>'
								+ '</p>'
								+ '<p class="imagify-c-level-row">'
									+ '<span class="label">' + imagifyTTT.labels.saving + '</span>'
									+ '<span class="value"><span class="imagify-chart"><span class="imagify-chart-container"><canvas id="imagify-consumption-chart-normal" width="15" height="15"></canvas></span></span><span class="imagify-chart-value">' + saving + '</span>%</span>'
								+ '</p>'
							+'</div>'
						+ '</div>'
						+ '<button class="close-btn absolute" type="button"><i aria-hidden="true" class="dashicons dashicons-no-alt"></i><span class="screen-reader-text">' + imagifyTTT.labels.close + '</span></button>'
					+ '</div>'
				+ '</div>'
			);

			$('#imagify-start-comparison').on('click.imagify', function(){

				$( $(this).data('target') ).find('.imagify-modal-content').css({
					'width'		: ($(window).outerWidth()*0.95) + 'px',
					'max-width'	: thumb.width
				});

				// load before img
				$('.imagify-img-before').on('load', function(){
					imgs_loaded++;
				}).attr('src', ori_source.src);

				// load after img
				$('.imagify-img-after').on('load', function(){
					imgs_loaded++;
				}).attr('src', thumb.src);

				var $tt = $('.twentytwenty-container'),
					check_load = setInterval( function(){

						if ( imgs_loaded === 2 ) {
							$tt.twentytwenty({
								handlePosition: 0.3,
								orientation: 	'horizontal',
								labelBefore: 	imagifyTTT.labels.original_l,
								labelAfter: 	imagifyTTT.labels.optimized_l
							}, function(){
								if ( ! $tt.closest('.imagify-modal-content').hasClass('loaded') ) {
									$tt.closest('.imagify-modal-content').removeClass('loading').addClass('loaded');
									draw_me_a_chart( $('.imagify-level-optimized').find('.imagify-chart').find('canvas') );
								}
							});
							clearInterval( check_load );
							check_load = null;
						}
					}, 75 );

			});
		}
		// else put images next to next
		else {

		}

	}

})(window, jQuery);
