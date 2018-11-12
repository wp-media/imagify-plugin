(function($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	$.fn.twentytwenty = function( options, callback ) {
		options = $.extend( {
			handlePosition: 0.5,
			orientation:    'horizontal',
			labelBefore:    'Before',
			labelAfter:     'After'
		}, options );

		return this.each( function() {
			var sliderPct         = options.handlePosition,
				$container        = $( this ),
				sliderOrientation = options.orientation,
				beforeDirection   = ( 'vertical' === sliderOrientation ) ? 'down' : 'left',
				afterDirection    = ( 'vertical' === sliderOrientation ) ? 'up'   : 'right',
				$beforeImg        = $container.find( 'img:first' ),
				$afterImg         = $container.find( 'img:last' ),
				offsetX           = 0,
				offsetY           = 0,
				imgWidth          = 0,
				imgHeight         = 0,
				$slider, $overlay,
				calcOffset = function( dimensionPct ) {
					var width  = parseInt( $beforeImg.width(), 10 ),
						height = parseInt( $beforeImg.height(), 10 );

					if ( ! width || ! height ) {
						width  = parseInt( $beforeImg.attr( 'width' ), 10 );
						height = parseInt( $beforeImg.attr( 'height' ), 10 );
					}

					return {
						w:  width  + "px",
						h:  height + "px",
						cw: ( dimensionPct * width )  + "px",
						ch: ( dimensionPct * height ) + "px"
					};
				},
				adjustContainer = function( offset ) {
					// Make it dynamic, in case the "before" image changes.
					var $beforeImage = $container.find( '.twentytwenty-before' );

					if ( 'vertical' === sliderOrientation ) {
						$beforeImage.css( 'clip', 'rect(0,' + offset.w + ',' + offset.ch + ',0)' );
					} else {
						$beforeImage.css( 'clip', 'rect(0,' + offset.cw + ',' + offset.h + ',0)' );
					}

					$container.css( 'height', offset.h );

					if ( typeof callback === 'function' ) {
						callback();
					}
				},
				adjustSlider = function( pct ) {
					var offset = calcOffset( pct );

					if ( 'vertical' === sliderOrientation ) {
						$slider.css( 'top', offset.ch );
					} else {
						$slider.css( 'left', offset.cw );
					}

					adjustContainer( offset );
				};


			if ( $container.parent( '.twentytwenty-wrapper' ).length ) {
				$container.unwrap();
			}
			$container.wrap( '<div class="twentytwenty-wrapper twentytwenty-' + sliderOrientation + '"></div>' );

			$container.children( '.twentytwenty-overlay, .twentytwenty-handle' ).remove();
			$container.append( '<div class="twentytwenty-overlay"></div>' );
			$container.append( '<div class="twentytwenty-handle"></div>' );

			$slider = $container.find( '.twentytwenty-handle' );

			$slider.append( '<span class="twentytwenty-' + beforeDirection + '-arrow"></span>' );
			$slider.append( '<span class="twentytwenty-' + afterDirection + '-arrow"></span>' );
			$container.addClass( 'twentytwenty-container' );
			$beforeImg.addClass( 'twentytwenty-before' );
			$afterImg.addClass( 'twentytwenty-after' );

			$overlay = $container.find( '.twentytwenty-overlay' );

			$overlay.append( '<div class="twentytwenty-labels twentytwenty-before-label"><span class="twentytwenty-label-content">' + options.labelBefore + '</span></div>' );
			$overlay.append( '<div class="twentytwenty-labels twentytwenty-after-label"><span class="twentytwenty-label-content">' + options.labelAfter + '</span></div>' );

			$( w ).on( 'resize.twentytwenty', function() {
				adjustSlider( sliderPct );
			} );

			$slider.on( 'movestart', function( e ) {
				if ( 'vertical' !== sliderOrientation && ( ( e.distX > e.distY && e.distX < -e.distY ) || ( e.distX < e.distY && e.distX > -e.distY ) ) ) {
					e.preventDefault();
				} else if ( 'vertical' === sliderOrientation && ( ( e.distX < e.distY && e.distX < -e.distY ) || ( e.distX > e.distY && e.distX > -e.distY ) ) ) {
					e.preventDefault();
				}

				$container.addClass( 'active' );

				offsetX   = $container.offset().left;
				offsetY   = $container.offset().top;
				imgWidth  = $beforeImg.width();
				imgHeight = $beforeImg.height();
			} );

			$slider.on( 'moveend', function() {
				$container.removeClass( 'active' );
			} );

			$slider.on( 'move', function( e ) {
				if ( $container.hasClass('active') ) {
					sliderPct = 'vertical' === sliderOrientation ? ( e.pageY - offsetY ) / imgHeight : ( e.pageX - offsetX ) / imgWidth;

					if ( sliderPct < 0 ) {
						sliderPct = 0;
					} else if ( sliderPct > 1 ) {
						sliderPct = 1;
					}

					adjustSlider( sliderPct );
				}
			} );

			$container.find( 'img' ).on( 'mousedown', function( e ) {
				e.preventDefault();
			} );

			$( w ).trigger( 'resize.twentytwenty' );
		} );
	};

} )(jQuery, document, window);

/**
 * Twentytwenty Imagify Init
 */
(function($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	/*
	 * Mini chart
	 *
	 * @param {element} canvas
	 */
	var drawMeAChart = function ( canvas ) {
			canvas.each( function() {
				var value = parseInt( $( this ).closest( '.imagify-chart' ).next( '.imagify-chart-value' ).text(), 10 );

				new w.imagify.Chart( this, { // eslint-disable-line no-new
					type: 'doughnut',
					data: {
						datasets: [{
							data:            [ value, 100 - value ],
							backgroundColor: [ '#00B3D3', '#D8D8D8' ],
							borderColor:     '#2A2E3C',
							borderWidth:     1
						}]
					},
					options: {
						legend: {
							display: false
						},
						events:    [],
						animation: {
							easing: 'easeOutBounce'
						},
						tooltips: {
							enabled: false
						},
						responsive:       false,
						cutoutPercentage: 60
					}
				} );
			} );
		},
		/**
		 * Dynamic modal
		 *
		 * @param {object} Parameters to build modal with datas
		 */
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
			/* eslint-disable indent */
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
				/* eslint-enable indent */
			modalHtml += '</div>';

			settings.modalAppendTo.append( modalHtml );

			settings.trigger.on( 'click.imagify', function( e ) {
				var $modal     = $( $( this ).data( 'target' ) ),
					imgsLoaded = 0,
					$tt, checkLoad;

				e.preventDefault();

				if ( settings.openModal ) {
					w.imagify.openModal( $( this ) );
				}

				$modal.find( '.imagify-modal-content' ).css( {
					'width':     ( $( w ).outerWidth() * 0.85 ) + 'px',
					'max-width': settings.width
				} );

				// Load before img.
				$modal.find( '.imagify-img-before' ).on( 'load', function() {
					imgsLoaded++;
				} ).attr( 'src', settings.originalUrl );

				// Load after img.
				$modal.find( '.imagify-img-after' ).on( 'load', function() {
					imgsLoaded++;
				} ).attr( 'src', settings.optimizedUrl + ( settings.optimizedUrl.indexOf( '?' ) > 0 ? '&' : '?' ) + 'v=' + Date.now() );

				$tt       = $modal.find( '.twentytwenty-container' );
				checkLoad = setInterval( function() {
					if ( 2 !== imgsLoaded ) {
						return;
					}

					$tt.twentytwenty( {
						handlePosition: 0.3,
						orientation:    'horizontal',
						labelBefore:    imagifyTTT.labels.originalL,
						labelAfter:     imagifyTTT.labels.optimizedL
					}, function() {
						var windowH = $( w ).height(),
							ttH     = $modal.find( '.twentytwenty-container' ).height(),
							ttTop   = $modal.find( '.twentytwenty-wrapper' ).position().top,
							$handle, $labels, $datas, datasH, handlePos, labelsPos;

						if ( ! $tt.closest( '.imagify-modal-content' ).hasClass( 'loaded' ) ) {
							$tt.closest( '.imagify-modal-content' ).removeClass( 'loading' ).addClass( 'loaded' );
							drawMeAChart( $modal.find( '.imagify-level-optimized .imagify-chart canvas' ) );
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
									bottom: -scrollTop
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
		/* eslint-disable indent */
			ttBeforeButtons += '<button type="button" class="imagify-comparison-original selected" data-img="original">' + labelOriginal + '</button>';
			ttBeforeButtons += '<button type="button" class="imagify-comparison-normal" data-img="normal">' + labelNormal + '</button>';
			ttBeforeButtons += '<button type="button" class="imagify-comparison-aggressive" data-img="aggressive">' + labelAggressive + '</button>';
			/* eslint-enable indent */
		ttBeforeButtons += '</span>';
		ttAfterButtons   = '<span class="twentytwenty-duo-buttons twentytwenty-duo-right">';
		/* eslint-disable indent */
			ttAfterButtons  += '<button type="button" class="imagify-comparison-normal" data-img="normal">' + labelNormal + '</button>';
			ttAfterButtons  += '<button type="button" class="imagify-comparison-aggressive" data-img="aggressive">' + labelAggressive + '</button>';
			ttAfterButtons  += '<button type="button" class="imagify-comparison-ultra selected" data-img="ultra">' + labelUltra + '</button>';
			/* eslint-enable indent */
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
					drawMeAChart( $( '.imagify-level-ultra .imagify-chart canvas' ) );
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
				$( '.twentytwenty-before-label .twentytwenty-label-content' ).text( $container.data( image + '-label' ) );
				$( '.imagify-c-level.go-left' ).attr( 'aria-hidden', 'true' ).removeClass( 'go-left go-right' );
				$( '.imagify-level-' + image ).attr( 'aria-hidden', 'false' ).addClass( 'go-left' );
			}

			// Right buttons.
			if ( 'right' === side ) {
				$imgAfter.removeClass( 'twentytwenty-after' );
				$container.find( '.img-' + image ).addClass( 'twentytwenty-after' );
				$( '.twentytwenty-after-label .twentytwenty-label-content' ).text( $container.data( image + '-label' ) );
				$( '.imagify-c-level.go-right' ).attr( 'aria-hidden', 'true' ).removeClass( 'go-left go-right' );
				$( '.imagify-level-' + image ).attr( 'aria-hidden', 'false' ).addClass( 'go-right' );
			}

			drawMeAChart( $( '.imagify-level-' + image + ' .imagify-chart canvas' ) );
		} );
	} );


	/**
	 * Imagify comparison inside Media post edition.
	 */
	if ( imagifyTTT.imageWidth && $( '.post-php .wp_attachment_image .thumbnail' ).length > 0 ) {

		var $oriParent   = $( '.post-php .wp_attachment_image' ),
			oriSource    = { src: $( '#imagify-full-original' ).val(), size: $( '#imagify-full-original-size' ).val() },
			$optimizeBtn = $( '#misc-publishing-actions' ).find( '.misc-pub-imagify .button-primary' ),
			filesize, saving;

		imagifyTTT.widthLimit = parseInt( imagifyTTT.widthLimit, 10 );

		// If shown image > 360, use twentytwenty.
		if ( imagifyTTT.imageWidth > imagifyTTT.widthLimit && oriSource.src ) {

			filesize = $( '.misc-pub-filesize strong' ).text();
			saving   = $( '.imagify-data-item .imagify-chart-value' ).text();

			// Create button to trigger.
			$( '[id^="imgedit-open-btn-"]' ).before( '<button type="button" class="imagify-button-primary button-primary imagify-modal-trigger" data-target="#imagify-visual-comparison" id="imagify-start-comparison">' + imagifyTTT.labels.compare + '</button>' );

			// Modal and trigger event creation.
			imagifyTwentyModal( {
				width:         parseInt( imagifyTTT.imageWidth, 10 ),
				height:        parseInt( imagifyTTT.imageHeight, 10 ),
				originalUrl:   oriSource.src,
				optimizedUrl:  imagifyTTT.imageSrc,
				originalSize:  oriSource.size,
				optimizedSize: filesize,
				saving:        saving,
				modalAppendTo: $oriParent,
				trigger:       $( '#imagify-start-comparison' ),
				modalId:       'imagify-visual-comparison'
			} );
		}
		// Else put images next to next.
		else if ( imagifyTTT.imageWidth < imagifyTTT.widthLimit && oriSource.src ) {
			// TODO
		}
		// If image has no backup.
		else if ( $( '#imagify-full-original' ).length > 0 && '' === oriSource.src ) {
			// do nothing ?
		}
		// In case image is not optimized.
		else {
			// If is not in optimizing process, propose the Optimize button trigger.
			if ( $( '#misc-publishing-actions' ).find( '.misc-pub-imagify .button-primary' ).length === 1 ) {
				$( '[id^="imgedit-open-btn-"]' ).before( '<span class="spinner imagify-hidden"></span><a class="imagify-button-primary button-primary imagify-optimize-trigger" id="imagify-optimize-trigger" href="' + $optimizeBtn.attr( 'href' ) + '">' + imagifyTTT.labels.optimize + '</a>' );

				$( '#imagify-optimize-trigger' ).on( 'click', function() {
					$( this ).prev( '.spinner' ).removeClass( 'imagify-hidden' ).addClass( 'is-active' );
				} );
			}
		}

	}

	/**
	 * Images comparison in attachments list page (upload.php).
	 */
	if ( $( '.upload-php .imagify-compare-images' ).length > 0 ) {

		$( '.imagify-compare-images' ).each( function() {
			var $this  = $( this ),
				id     = $this.data( 'id' ),
				$datas = $this.closest( '#post-' + id ).find( '.column-imagify_optimized_file' );

			// Modal and trigger event creation.
			imagifyTwentyModal( {
				width:         parseInt( $this.data( 'full-width' ), 10 ),
				height:        parseInt( $this.data( 'full-height' ), 10 ),
				originalUrl:   $this.data( 'backup-src' ),
				optimizedUrl:  $this.data( 'full-src' ),
				originalSize:  $datas.find( '.original' ).text(),
				optimizedSize: $datas.find( '.imagify-data-item .big' ).text(),
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
	if ( $( '.upload-php' ).length > 0 ) {

		var getVar = function( param ) {
				var vars = {};

				w.location.href.replace(
					/[?&]+([^=&]+)=?([^&]*)?/gi,
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
					var $datas, originalSrc, $actions;

					if ( ! $( '.media-modal .imagify-datas-details' ).length ) {
						return;
					}

					originalSrc = $( '#imagify-original-src' ).val();

					if ( originalSrc ) {
						// Trigger creation.
						$actions = $( '.media-frame-content .attachment-actions' );

						$actions.find( '#imagify-media-frame-comparison-btn' ).remove();
						$actions.prepend( '<button type="button" class="imagify-button-primary button-primary imagify-modal-trigger" data-target="#imagify-comparison-modal" id="imagify-media-frame-comparison-btn">' + imagifyTTT.labels.compare + '</button>' );

						// Get datas.
						$datas = $( '.media-frame-content .compat-field-imagify' );

						// Modal and trigger event creation.
						imagifyTwentyModal( {
							width:         parseInt( $( '#imagify-full-width' ).val(), 10 ),
							height:        parseInt( $( '#imagify-full-height' ).val(), 10 ),
							originalUrl:   originalSrc,
							optimizedUrl:  $( '#imagify-full-src' ).val(),
							originalSize:  $( '#imagify-original-size' ).val(),
							optimizedSize: $datas.find( '.imagify-data-item .big' ).text(),
							saving:        $datas.find( '.imagify-chart-value' ).text(),
							modalAppendTo: $( '.media-frame-content .thumbnail-image' ),
							trigger:       $( '#imagify-media-frame-comparison-btn' ),
							modalId:       'imagify-comparison-modal',
							openModal:     true
						} );
					}

					clearInterval( tempTimer );
					tempTimer = null;
				}, 20 );
			};

		// If attachment is clicked, or the "Previous" and "Next" buttons, build the modal inside the modal.
		$( '.upload-php' ).on( 'click', '.media-frame.mode-grid .attachment, .edit-media-header .left, .edit-media-header .right', function() {
			imagifyContentInModal();
		} );

		// If attachment is mentionned in URL, build the modal inside the modal.
		if ( getVar( 'item' ) ) {
			imagifyContentInModal();
		}
	}

	/**
	 * Images comparison in custom folders list page.
	 */
	if ( $( '#imagify-files-list-form' ).length > 0 ) {

		var buildComparisonModal = function( $buttons ) {
			$buttons.each( function() {
				var $this  = $( this ),
					id     = $this.data( 'id' ),
					$datas = $this.closest( 'tr' ).find( '.column-optimization .imagify-data-item' );

				$( '#imagify-comparison-' + id ).remove();

				// Modal and trigger event creation.
				imagifyTwentyModal( {
					width:         parseInt( $this.data( 'full-width' ), 10 ),
					height:        parseInt( $this.data( 'full-height' ), 10 ),
					originalUrl:   $this.data( 'backup-src' ),
					optimizedUrl:  $this.data( 'full-src' ),
					originalSize:  $datas.find( '.original' ).text(),
					optimizedSize: $datas.find( '.optimized' ).text(),
					saving:        $datas.find( '.imagify-chart-value' ).text(),
					modalAppendTo: $this.closest( '.column-primary' ),
					trigger:       $this,
					modalId:       'imagify-comparison-' + id
				} );
			} );
		};

		/**
		 * Update the comparison tool window when a file row is updated via ajax, and the ones already printed.
		 */
		$( w ).on( 'comparisonprinted.imagify', function( e, id ) {
			var $buttons;

			id = id || 0;

			if ( id ) {
				$buttons = $( '#imagify-files-list-form' ).find( '.imagify-compare-images[data-id="' + id + '"]' );
			} else {
				$buttons = $( '#imagify-files-list-form' ).find( '.imagify-compare-images' );
			}

			if ( $buttons.length ) {
				buildComparisonModal( $buttons );
			}
		} )
			.trigger( 'comparisonprinted.imagify' );
	}

} )(jQuery, document, window);
