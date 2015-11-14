(function(window, $, undefined){

	$.fn.twentytwenty = function(options, callback) {
		var options = $.extend({
			handlePosition:		0.5,
			orientation:		'horizontal',
			labelBefore: 		'Before',
			labelAfter: 		'After'
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

	$('.imagify-visual-comparison-btn').on('click', function(){
		
		$( $(this).data('target') ).find('.imagify-modal-content').css('width', ($(window).outerWidth()*0.95) + 'px');

		if ( $('.twentytwenty-container').length > 0 && $(window).outerWidth() > 800 ) {

			var $tt 				= $('.twentytwenty-container'),
				imgs_loaded			= 0,
				loader 				= $tt.data('loader'),
				label_original		= $tt.data('label-original'),
				label_normal		= $tt.data('label-normal'),
				label_aggressive	= $tt.data('label-aggressive'),

				original_label 		= $tt.data('original-label').replace(/\*\*/, '<strong>').replace(/\*\*/, '</strong>'),
				original_alt 		= $tt.data('original-alt'),
				original_src 		= $tt.data('original-img'),
				original_dim 		= $tt.data('original-dim').split('x'),

				optimized_label 	= $tt.data('optimized-label').replace(/\*\*/, '<strong>').replace(/\*\*/, '</strong>'),
				optimized_alt 		= $tt.data('optimized-alt'),
				optimized_src 		= $tt.data('optimized-img'),
				optimized_dim 		= $tt.data('optimized-dim').split('x'),

				aggressive_label 	= $tt.data('aggressive-label').replace(/\*\*/, '<strong>').replace(/\*\*/, '</strong>'),
				aggressive_alt 		= $tt.data('aggressive-alt'),
				aggressive_src 		= $tt.data('aggressive-img'),
				aggressive_dim 		= $tt.data('aggressive-dim').split('x');

			// loader
			$tt.before('<img class="loader" src="' + loader + '" alt="Loading…" width="20" height="20">')

			// add images to 50/50 area
			$tt.closest('.imagify-modal-content').addClass('loading').find('.twentytwenty-container').append(
					'<img class="img-original" alt="' + original_alt + '" width="' + original_dim[0] + '" height="' + original_dim[1] + '">' +
					'<img class="img-optimized" alt="' + optimized_alt + '" width="' + optimized_dim[0] + '" height="' + optimized_dim[1] + '">' + 
					'<img class="img-aggressive" alt="' + aggressive_alt + '" width="' + aggressive_dim[0] + '" height="' + aggressive_dim[1] + '">' +
					'<span class="twentytwenty-duo-buttons twentytwenty-duo-left">' +
						'<button type="button" class="imagify-comparison-original selected" data-img="original">' + label_original + '</button>' +
						'<button type="button" class="imagify-comparison-normal" data-img="optimized">' + label_normal + '</button>' +
					'</span>' +
					'<span class="twentytwenty-duo-buttons twentytwenty-duo-right">' +
						'<button type="button" class="imagify-comparison-normal" data-img="optimized">' + label_normal + '</button>' +
						'<button type="button" class="imagify-comparison-aggressive selected" data-img="aggressive">' + label_aggressive + '</button>' +
					'</span>'
			);

			// load image original
			$('.img-original').on('load', function(){
				imgs_loaded++;
			}).attr('src', original_src);

			// load image optimized
			$('.img-optimized').on('load', function(){
				imgs_loaded++;
			}).attr('src', optimized_src);

			// load image aggressive
			$('.img-aggressive').on('load', function(){
				imgs_loaded++;
			}).attr('src', aggressive_src);

			var twenty_me = setInterval(function(){
				if ( imgs_loaded === 3 ) {
					$tt.twentytwenty({
						handlePosition: 0.3,
						orientation: 	'horizontal',
						labelBefore: 	original_label,
						labelAfter: 	aggressive_label
					}, function(){
						$tt.closest('.imagify-modal-content').removeClass('loading').addClass('loaded');
					});
					clearInterval( twenty_me );
					twenty_me = null;
				}
			}, 75);

			// on click on button choices
			$tt.on('click', '.twentytwenty-duo-buttons button:not(.selected)', function(e){

				e.stopPropagation();

				var $_this 		= $(this),
					$container 	= $_this.closest('.twentytwenty-container'),
					side		= $_this.closest('.twentytwenty-duo-buttons').hasClass('twentytwenty-duo-left') ? 'left' : 'right',
					$other_side	= side === 'left' ? $container.find('.twentytwenty-duo-right') : $container.find('.twentytwenty-duo-left'),
					$duo 		= $_this.closest('.twentytwenty-duo-buttons').find('button'),
					$img_before	= $container.find('.twentytwenty-before'),
					$img_after	= $container.find('.twentytwenty-after'),
					image 		= $_this.data('img');

				// button coloration
				$duo.removeClass('selected');
				$_this.addClass('selected');

				// other side action (to not compare same images)
				if ( $other_side.find('.selected').data('img') === image ) {
					$other_side.find('button:not(.selected)').trigger('click');
				}

				// left buttons
				if ( side === 'left' ) {
					var clip_styles = $img_before.css('clip');
					$img_before.attr('style', '');
					$img_before.removeClass('twentytwenty-before');
					$container.find( '.img-' + image ).addClass('twentytwenty-before').css('clip', clip_styles);
				}

				// right buttons
				if ( side === 'right' ) {
					$img_after.removeClass('twentytwenty-after')
					$container.find( '.img-' + image ).addClass('twentytwenty-after');
				}

				return false;

			});
		}

	});

})(window, jQuery);
