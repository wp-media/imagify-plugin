/* globals ajaxurl: false, console: false, imagifyTTT: true */

window.imagify = window.imagify || {
	concat: ajaxurl.indexOf( '?' ) > 0 ? '&' : '?',
	log:    function( content ) {
		if ( undefined !== console ) {
			console.log( content );
		}
	},
	info:   function( content ) {
		if ( undefined !== console ) {
			console.info( content );
		}
	}
};

(function($, d, w, undefined) {

	$.fn.twentytwenty = function(options, callback) {
		options = $.extend({
			handlePosition		: 0.5,
			orientation			: 'horizontal',
			labelBefore			: 'Before',
			labelAfter			: 'After'
		}, options);

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

			$(window).on('resize.twentytwenty', function() {
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

			$slider.on('moveend', function() {
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

} )(jQuery, document, window);

/**
 * Twentytwenty Imagify Init
 */
(function($, d, w, undefined) {

	/*
	 * Mini chart
	 *
	 * @param {element} canvas
	 */
	var drawMeAChart = function ( canvas ) {
			canvas.each( function() {
				var $this        = $( this ),
					theValue     = parseInt( $this.closest( '.imagify-chart' ).next( '.imagify-chart-value' ).text() ),
					overviewData = [
						{
							value: theValue,
							color: '#00B3D3'
						},
						{
							value: 100 - theValue,
							color: '#D8D8D8'
						}
					];

				new Chart( $this[0].getContext( '2d' ) ).Doughnut( overviewData, {
					segmentStrokeColor   : '#2A2E3C',
					segmentStrokeWidth   : 1,
					animateRotate        : true,
					percentageInnerCutout: 60,
					tooltipEvents        : []
				} );
			} );
		},
		/**
		 * Dynamic modal
		 *
		 * @param {object}	Parameters to build modal with datas
		 */
		imagifyOpenModal = function( $theLink ) {
			var theTarget = $theLink.data( 'target' ) || $theLink.attr( 'href' );

			$( theTarget ).css( 'display', 'flex' ).hide().fadeIn( 400 ).attr( 'aria-hidden', 'false' ).attr( 'tabindex', '0' ).focus().removeAttr( 'tabindex' ).addClass( 'modal-is-open' );
			$( 'body' ).addClass( 'imagify-modal-is-open' );
		},
		imagifyTwentyModal = function( options ) {
			var defaults = {
					width:         0, //px
					height:        0, //px
					originalUrl:   '', //url
					optimizedUrl:  '', //url
					originalSize:  0, //mb
					optimizedSize: 0, // mb
					saving:        0, //percent
					modalAppendTo: $( 'body' ), // jQuery element
					trigger:       $( '[data-target="imagify-visual-comparison"]' ), // jQuery element (button, link) with data-target="modalId"
					modalId:       'imagify-visual-comparison', // should be dynamic if multiple modals
					openModal:     false
				},
				settings = $.extend( {}, defaults, options ),
				modalHtml;

			if ( 0 === settings.width || 0 === settings.height || '' === settings.originalUrl || '' === settings.optimizedUrl || 0 === settings.originalSize || 0 === settings.optimizedSize || 0 === settings.saving ) {
				return 'error';
			}

			// create modal box
			modalHtml  = '<div id="' + settings.modalId + '" class="imagify-modal imagify-visual-comparison" aria-hidden="true">';
				modalHtml += '<div class="imagify-modal-content loading">';
					modalHtml += '<div class="twentytwenty-container">';
						modalHtml += '<img class="imagify-img-before" alt="" width="' + settings.width + '" height="' + settings.height + '">';
						modalHtml += '<img class="imagify-img-after" alt="" width="' + settings.width + '" height="' + settings.height + '">';
					modalHtml += '</div>';
					modalHtml += '<div class="imagify-comparison-levels">';
						modalHtml += '<div class="imagify-c-level imagify-level-original go-left">';
							modalHtml += '<p class="imagify-c-level-row">';
								modalHtml += '<span class="label">' + imagifyTTT.labels.filesize + '</span>';
								modalHtml += '<span class="value level">' + settings.originalSize + '</span>';
							modalHtml += '</p>';
						modalHtml += '</div>';
						modalHtml += '<div class="imagify-c-level imagify-level-optimized go-right">';
							modalHtml += '<p class="imagify-c-level-row">';
								modalHtml += '<span class="label">' + imagifyTTT.labels.filesize + '</span>';
								modalHtml += '<span class="value level">' + settings.optimizedSize + '</span>';
							modalHtml += '</p>';
							modalHtml += '<p class="imagify-c-level-row">';
								modalHtml += '<span class="label">' + imagifyTTT.labels.saving + '</span>';
								modalHtml += '<span class="value"><span class="imagify-chart"><span class="imagify-chart-container"><canvas id="imagify-consumption-chart-normal" width="15" height="15"></canvas></span></span><span class="imagify-chart-value">' + settings.saving + '</span>%</span>';
							modalHtml += '</p>';
						modalHtml += '</div>';
					modalHtml += '</div>';
					modalHtml += '<button class="close-btn absolute" type="button"><i aria-hidden="true" class="dashicons dashicons-no-alt"></i><span class="screen-reader-text">' + imagifyTTT.labels.close + '</span></button>';
				modalHtml += '</div>';
			modalHtml += '</div>';

			settings.modalAppendTo.append( modalHtml );

			settings.trigger.on( 'click.imagify', function( e ) {
				var $modal     = $( $( this ).data( 'target' ) ),
					imgsLoaded = 0,
					$tt, checkLoad;

				e.preventDefault();

				if ( typeof imagifyOpenModal === 'function' && settings.openModal ) {
					imagifyOpenModal( $( this ) );
				}

				$modal.find( '.imagify-modal-content').css( {
					'width'    : ( $( w ).outerWidth() * 0.85 ) + 'px',
					'max-width': settings.width
				} );

				// Load before img.
				$modal.find( '.imagify-img-before').on( 'load', function() {
					imgsLoaded++;
				} ).attr( 'src', settings.originalUrl );

				// Load after img.
				$modal.find( '.imagify-img-after' ).on( 'load', function() {
					imgsLoaded++;
				} ).attr( 'src', settings.optimizedUrl );

				$tt       = $modal.find( '.twentytwenty-container' );
				checkLoad = setInterval( function() {
					if ( 2 !== imgsLoaded ) {
						return;
					}

					$tt.twentytwenty( {
						handlePosition: 0.3,
						orientation:    'horizontal',
						labelBefore:    imagifyTTT.labels.original_l,
						labelAfter:     imagifyTTT.labels.optimized_l
					}, function() {
						var windowH = $( w ).height(),
							ttH     = $modal.find( '.twentytwenty-container' ).height(),
							ttTop   = $modal.find( '.twentytwenty-wrapper' ).position().top,
							$handle, $labels, $datas, datasH, handlePos, labelsPos;

						if ( ! $tt.closest( '.imagify-modal-content' ).hasClass( 'loaded' ) ) {
							$tt.closest( '.imagify-modal-content' ).removeClass( 'loading' ).addClass( 'loaded' );
							drawMeAChart( $modal.find( '.imagify-level-optimized' ).find( '.imagify-chart' ).find( 'canvas' ) );
						}

						// Check if image height is to big.
						if ( windowH < ttH && ! $modal.hasClass( 'modal-is-too-high' ) ) {
							$modal.addClass( 'modal-is-too-high' );

							$handle   = $modal.find( '.twentytwenty-handle' );
							$labels   = $modal.find( '.twentytwenty-label-content' );
							$datas    = $modal.find( '.imagify-comparison-levels' );
							datasH    = $datas.outerHeight();
							handlePos = ( windowH - ttTop - $handle.height() ) / 2;
							labelsPos = ( windowH - ttTop * 3 - datasH );

							$handle.css( {
								top: handlePos
							} );
							$labels.css( {
								top:    labelsPos,
								bottom: 'auto'
							} );
							$modal.find( '.twentytwenty-wrapper' ).css( {
								paddingBottom: datasH
							} );
							$modal.find( '.imagify-modal-content' ).on( 'scroll.imagify', function() {
								var scrollTop = $( this ).scrollTop();

								$handle.css( {
									top: handlePos + scrollTop
								} );
								$labels.css( {
									top: labelsPos + scrollTop
								} );
								$datas.css( {
									bottom: - scrollTop
								} );
							} );
						}
					} );

					clearInterval( checkLoad );
					checkLoad = null;
					return 'done';
				}, 75 );
			} );
		}; // imagifyTwentyModal( options );


	/**
	 * The complexe visual comparison
	 */
	$( '.imagify-visual-comparison-btn' ).on( 'click', function() {
		var $tt, imgsLoaded, loader,
			labelOriginal, labelNormal, labelAggressive, labelUltra,
			originalLabel, originalAlt, originalSrc, originalDim,
			normalAlt, normalSrc, normalDim,
			aggressiveAlt, aggressiveSrc, aggressiveDim,
			ultraLabel, ultraAlt, ultraSrc, ultraDim,
			ttBeforeButtons, ttAfterButtons, image50, twentyMe;

		if ( $( '.twentytwenty-wrapper' ).length === 1 ) {
			return;
		}

		$( $( this ).data( 'target' ) ).find( '.imagify-modal-content' ).css( 'width', ( $( w ).outerWidth() * 0.95 ) + 'px' );

		if ( $( '.twentytwenty-container' ).length > 0 && $( w ).outerWidth() <= 800 ) {
			return;
		}

		$tt              = $( '.twentytwenty-container' );
		imgsLoaded       = 0;
		loader           = $tt.data( 'loader' );
		labelOriginal    = $tt.data( 'label-original' );
		labelNormal      = $tt.data( 'label-normal' );
		labelAggressive  = $tt.data( 'label-aggressive' );
		labelUltra       = $tt.data( 'label-ultra' );

		originalLabel    = $tt.data( 'original-label' ).replace( /\*\*/, '<strong>' ).replace( /\*\*/, '</strong>' );
		originalAlt      = $tt.data( 'original-alt' );
		originalSrc      = $tt.data( 'original-img' );
		originalDim      = $tt.data( 'original-dim' ).split( 'x' );

		normalAlt        = $tt.data( 'normal-alt' );
		normalSrc        = $tt.data( 'normal-img' );
		normalDim        = $tt.data( 'normal-dim' ).split( 'x' );

		aggressiveAlt    = $tt.data( 'aggressive-alt' );
		aggressiveSrc    = $tt.data( 'aggressive-img' );
		aggressiveDim    = $tt.data( 'aggressive-dim' ).split( 'x' );

		ultraLabel       = $tt.data( 'ultra-label' ).replace( /\*\*/, '<strong>' ).replace( /\*\*/, '</strong>' );
		ultraAlt         = $tt.data( 'ultra-alt' );
		ultraSrc         = $tt.data( 'ultra-img' );
		ultraDim         = $tt.data( 'ultra-dim' ).split( 'x' );

		ttBeforeButtons  = '<span class="twentytwenty-duo-buttons twentytwenty-duo-left">';
			ttBeforeButtons += '<button type="button" class="imagify-comparison-original selected" data-img="original">' + labelOriginal + '</button>';
			ttBeforeButtons += '<button type="button" class="imagify-comparison-normal" data-img="normal">' + labelNormal + '</button>';
			ttBeforeButtons += '<button type="button" class="imagify-comparison-aggressive" data-img="aggressive">' + labelAggressive + '</button>';
		ttBeforeButtons += '</span>';
		ttAfterButtons   = '<span class="twentytwenty-duo-buttons twentytwenty-duo-right">';
			ttAfterButtons  += '<button type="button" class="imagify-comparison-normal" data-img="normal">' + labelNormal + '</button>';
			ttAfterButtons  += '<button type="button" class="imagify-comparison-aggressive" data-img="aggressive">' + labelAggressive + '</button>';
			ttAfterButtons  += '<button type="button" class="imagify-comparison-ultra selected" data-img="ultra">' + labelUltra + '</button>';
		ttAfterButtons  += '</span>';

		// Loader.
		$tt.before( '<img class="loader" src="' + loader + '" alt="Loading…" width="64" height="64">' );

		// Should be more locally integrated...
		$( '.twentytwenty-left-buttons' ).append( ttBeforeButtons );
		$( '.twentytwenty-right-buttons' ).append( ttAfterButtons );

		image50    = '<img class="img-original" alt="' + originalAlt + '" width="' + originalDim[0] + '" height="' + originalDim[1] + '">';
		image50   += '<img class="img-normal" alt="' + normalAlt + '" width="' + normalDim[0] + '" height="' + normalDim[1] + '">';
		image50   += '<img class="img-aggressive" alt="' + aggressiveAlt + '" width="' + aggressiveDim[0] + '" height="' + aggressiveDim[1] + '">';
		image50   += '<img class="img-ultra" alt="' + ultraAlt + '" width="' + ultraDim[0] + '" height="' + ultraDim[1] + '">';
		// Add switchers button only if needed.
		// Should be more locally integrated...
		image50   += $( '.twentytwenty-left-buttons' ).lenght ? ttBeforeButtons + ttAfterButtons : '';

		// Add images to 50/50 area.
		$tt.closest( '.imagify-modal-content' ).addClass( 'loading' ).find( '.twentytwenty-container' ).append( image50 );

		// Load image original.
		$( '.img-original' ).on( 'load', function() {
			imgsLoaded++;
		} ).attr( 'src', originalSrc );

		// Load image normal.
		$( '.img-normal' ).on( 'load', function() {
			imgsLoaded++;
		} ).attr( 'src', normalSrc );

		// Load image aggressive.
		$( '.img-aggressive' ).on( 'load', function() {
			imgsLoaded++;
		} ).attr( 'src', aggressiveSrc );

		// Load image ultra.
		$( '.img-ultra' ).on( 'load', function() {
			imgsLoaded++;
		} ).attr( 'src', ultraSrc );

		twentyMe = setInterval( function() {
			if ( 4 !== imgsLoaded ) {
				return;
			}

			$tt.twentytwenty({
				handlePosition: 0.6,
				orientation:    'horizontal',
				labelBefore:    originalLabel,
				labelAfter:     ultraLabel
			}, function() {
				// Fires on initialisation & each time the handle is moving.
				if ( ! $tt.closest( '.imagify-modal-content' ).hasClass( 'loaded' ) ) {
					$tt.closest( '.imagify-modal-content' ).removeClass( 'loading' ).addClass( 'loaded' );
					drawMeAChart( $( '.imagify-level-ultra' ).find( '.imagify-chart' ).find( 'canvas' ) );
				}
			} );

			clearInterval( twentyMe );
			twentyMe = null;
		}, 75);

		// On click on button choices.
		$( '.imagify-comparison-title' ).on( 'click', '.twentytwenty-duo-buttons button:not(.selected)', function( e ) {
			var $this      = $( this ),
				$container = $this.closest( '.imagify-comparison-title' ).nextAll( '.twentytwenty-wrapper' ).find( '.twentytwenty-container' ),
				side       = $this.closest( '.twentytwenty-duo-buttons' ).hasClass( 'twentytwenty-duo-left' ) ? 'left' : 'right',
				$otherSide = 'left' === side ? $this.closest( '.imagify-comparison-title' ).find( '.twentytwenty-duo-right' ) : $this.closest( '.imagify-comparison-title' ).find( '.twentytwenty-duo-left' ),
				$duo       = $this.closest( '.twentytwenty-duo-buttons' ).find( 'button' ),
				$imgBefore = $container.find( '.twentytwenty-before' ),
				$imgAfter  = $container.find( '.twentytwenty-after' ),
				image      = $this.data( 'img' ),
				clipStyles;

			e.stopPropagation();
			e.preventDefault();

			// Button coloration.
			$duo.removeClass( 'selected' );
			$this.addClass( 'selected' );

			// Other side action (to not compare same images).
			if ( $otherSide.find( '.selected' ).data( 'img' ) === image ) {
				$otherSide.find( 'button:not(.selected)' ).eq( 0 ).trigger( 'click' );
			}

			// Left buttons.
			if ( 'left' === side ) {
				clipStyles = $imgBefore.css( 'clip' );
				$imgBefore.attr( 'style', '' );
				$imgBefore.removeClass( 'twentytwenty-before' );
				$container.find( '.img-' + image ).addClass( 'twentytwenty-before' ).css( 'clip', clipStyles );
				$( '.twentytwenty-before-label' ).find( '.twentytwenty-label-content' ).text( $container.data( image + '-label' ) );
				$( '.imagify-c-level.go-left' ).attr( 'aria-hidden', 'true' ).removeClass( 'go-left go-right' );
				$( '.imagify-level-' + image ).attr( 'aria-hidden', 'false' ).addClass( 'go-left' );
			}

			// Right buttons.
			if ( 'right' === side ) {
				$imgAfter.removeClass( 'twentytwenty-after' );
				$container.find( '.img-' + image ).addClass( 'twentytwenty-after' );
				$( '.twentytwenty-after-label' ).find( '.twentytwenty-label-content' ).text( $container.data( image + '-label' ) );
				$( '.imagify-c-level.go-right' ).attr( 'aria-hidden', 'true' ).removeClass( 'go-left go-right' );
				$( '.imagify-level-' + image ).attr( 'aria-hidden', 'false' ).addClass( 'go-right' );
			}

			drawMeAChart( $( '.imagify-level-' + image ).find( '.imagify-chart' ).find( 'canvas' ) );
		} );
	} );


	/**
	 * Imagify comparison inside Media post visualization.
	 */
	if ( $( '.post-php' ).find( '.wp_attachment_image' ).find( '.thumbnail' ).length > 0 ) {

		var $oriParent   = $( '.post-php' ).find( '.wp_attachment_image' ),
			$thumbnail   = $oriParent.find( '.thumbnail' ),
			thumb        = { src: $thumbnail.prop( 'src' ), width: $thumbnail.width(), height: $thumbnail.height() },
			oriSource    = { src: $( '#imagify-full-original' ).val(), size: $( '#imagify-full-original-size' ).val() },
			$optimizeBtn = $( '#misc-publishing-actions' ).find( '.misc-pub-imagify' ).find( '.button-primary' ),
			widthLimit   = 360,
			filesize, saving;

		// If shown image > 360, use twentytwenty.
		if ( thumb.width > widthLimit && $( '#imagify-full-original' ).length > 0 && $( '#imagify-full-original' ).val() !== '' ) {

			filesize = $( '.misc-pub-filesize' ).find( 'strong' ).text();
			saving   = $( '.imagify-data-item' ).find( '.imagify-chart-value' ).text();

			// Create button to trigger.
			$( '[id^="imgedit-open-btn-"]' ).before( '<button type="button" class="imagify-button-primary button-primary imagify-modal-trigger" data-target="#imagify-visual-comparison" id="imagify-start-comparison">' + imagifyTTT.labels.compare + '</button>' );

			// Modal and trigger event creation.
			imagifyTwentyModal( {
				width:         thumb.width,
				height:        thumb.height,
				originalUrl:   oriSource.src,
				optimizedUrl:  thumb.src,
				originalSize:  oriSource.size,
				optimizedSize: filesize,
				saving:        saving,
				modalAppendTo: $oriParent,
				trigger:       $( '#imagify-start-comparison' ),
				modalId:       'imagify-visual-comparison'
			} );
		}
		// Else put images next to next.
		else if ( thumb.width < widthLimit && $( '#imagify-full-original' ).length > 0 && $( '#imagify-full-original' ).val() !== '' ) {
			// TODO
		}
		// If image has no backup.
		else if ( $( '#imagify-full-original' ).length > 0 && $( '#imagify-full-original' ).val() === '' ) {
			// do nothing ?
		}
		// In case image is not optimized.
		else {
			// If is not in optimizing process, propose the Optimize button trigger.
			if ( $( '#misc-publishing-actions' ).find( '.misc-pub-imagify' ).find( '.button-primary' ).length === 1 ) {
				$( '[id^="imgedit-open-btn-"]' ).before( '<span class="spinner imagify-hidden"></span><a class="imagify-button-primary button-primary imagify-optimize-trigger" id="imagify-optimize-trigger" href="' + $optimizeBtn.attr( 'href' ) + '">' + imagifyTTT.labels.optimize + '</a>' );

				$( '#imagify-optimize-trigger' ).on('click', function() {
					$( this ).prev( '.spinner' ).removeClass( 'imagify-hidden' ).addClass( 'is-active' );
				} );
			}
		}

	}

	/**
	 * Images comparison in attachments list page (upload.php).
	 */
	if ( $( '.upload-php' ).find( '.imagify-compare-images' ).length > 0 ) {

		$( '.imagify-compare-images' ).each( function() {
			var $this  = $( this ),
				id     = $this.data( 'id' ),
				$datas = $this.closest( '#post-' + id ).find( '.column-imagify_optimized_file' );

			// Modal and trigger event creation.
			imagifyTwentyModal( {
				width:         $this.data( 'full-width' ),
				height:        $this.data( 'full-height' ),
				originalUrl:   $this.data( 'backup-src' ),
				optimizedUrl:  $this.data( 'full-src' ),
				originalSize:  $datas.find( '.original' ).text(),
				optimizedSize: $datas.find( '.imagify-data-item' ).find( '.big' ).text(),
				saving:        $datas.find( '.imagify-chart-value' ).text(),
				modalAppendTo: $this.closest( '.column-primary' ),
				trigger:       $this,
				modalId:       'imagify-comparison-' + id
			} );
		} );
	}

	/**
	 * Images Comparison in Grid View modal.
	 */
	if ( $('.upload-php').length > 0 ) {

		var getVar = function ( param ) {
				var vars = {};

				w.location.href.replace(
					/[?&]+([^=&]+)=?([^&]*)?/gi,//
					function( m, key, value ) {
						vars[ key ] = undefined !== value ? value : '';
					}
				);

				if ( param ) {
					return vars[ param ] ? vars[ param ] : null;
				}
				return vars;
			},
			imagifyContentInModal = function() {
				var tempTimer = setInterval( function() {
						var $datas;

						if ( ! $( '.media-modal' ).find( '.imagify-datas-details' ).length ) {
							return;
						}

						if ( $( '#imagify-original-src' ).length > 0 && $( '#imagify-original-src' ) !== '' ) {
							// Trigger creation.
							$( '.media-frame-content' ).find( '.attachment-actions' ).prepend( '<button type="button" class="imagify-button-primary button-primary imagify-modal-trigger" data-target="#imagify-comparison-modal" id="imagify-media-frame-comparison-btn">' + imagifyTTT.labels.compare + '</button>' );

							// Get datas.
							$datas = $( '.media-frame-content' ).find( '.compat-field-imagify' );

							// Modal and trigger event creation.
							imagifyTwentyModal( {
								width:         $( '#imagify-full-width' ).val(),
								height:        $( '#imagify-full-height' ).val(),
								originalUrl:   $( '#imagify-original-src' ).val(),
								optimizedUrl:  $( '#imagify-full-src' ).val(),
								originalSize:  $( '#imagify-original-size' ).val(),
								optimizedSize: $datas.find( '.imagify-data-item' ).find( '.big' ).text(),
								saving:        $datas.find( '.imagify-chart-value' ).text(),
								modalAppendTo: $( '.media-frame-content' ).find( '.thumbnail-image' ),
								trigger:       $( '#imagify-media-frame-comparison-btn' ),
								modalId:       'imagify-comparison-modal',
								openModal:     true
							} );
						}

						clearInterval( tempTimer );
						tempTimer = null;
					}, 20 );
			},
			waitContent = setInterval( function() {
				if ( ! $('.upload-php').find('.media-frame.mode-grid').find('.attachments').length ) {
					return;
				}

				// If attachment is clicked, build the modal inside the modal.
				$( '.upload-php' ).find( '.media-frame.mode-grid' ).on( 'click', '.attachment', function() {
					imagifyContentInModal();
				} );

				// If attachment is mentionned in URL, build the modal inside the modal.
				if ( getVar( 'item' ) ) {
					imagifyContentInModal();
				}

				clearInterval( waitContent );
				waitContent = null;
			}, 100 );
		// If URL contain item, that will open the WP Modal View.
	}

} )(jQuery, document, window);
