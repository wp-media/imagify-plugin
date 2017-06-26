/* globals ajaxurl: false, console: false, imagify: true, imagifyAdmin: true, swal: false, Promise: false */

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

// Admin bar =======================================================================================
(function($, d, w, undefined) {

	var busy = false;

	$( '#wp-admin-bar-imagify' ).hover( function() {
		var $adminBarProfile;

		if ( true === busy ) {
			return;
		}

		busy = true;

		$adminBarProfile = $( '#wp-admin-bar-imagify-profile-content' );

		if ( ! $adminBarProfile.is( ':empty' ) ) {
			return;
		}

		$.get( ajaxurl + imagify.concat + 'action=imagify_get_admin_bar_profile&imagifygetadminbarprofilenonce=' + $( '#imagifygetadminbarprofilenonce' ).val() )
		.done( function( response ) {
			$adminBarProfile.html( response.data );
			$( '#wp-admin-bar-imagify-profile-loading' ).remove();
			busy = false;
		} );
	} );

} )(jQuery, document, window);


// The big welcome notice ==========================================================================
(function($, d, w, undefined) {

	/**
	 * 1. Create a new Imagify account.
	 */
	$( '#imagify-signup' ).on( 'click.imagify', function( e ) {
		e.preventDefault();

		// Display the sign up form.
		swal( {
			title:               imagifyAdmin.labels.signupTitle,
			html:                imagifyAdmin.labels.signupText,
			confirmButtonText:   imagifyAdmin.labels.signupConfirmButtonText,
			input:               'email',
			allowOutsideClick:   true,
			showLoaderOnConfirm: true,
			customClass:         'imagify-sweet-alert imagify-sweet-alert-signup',
			inputValidator:      function( inputValue ) {
				return new Promise( function( resolve, reject ) {
					if ( $.trim( inputValue ) === '' || ! inputValue ) {
						reject( imagifyAdmin.labels.signupErrorEmptyEmail );
					} else {
						resolve();
					}
				} );
			},
			preConfirm:          function( inputValue ) {
				return new Promise( function( resolve, reject ) {
					setTimeout( function() {
						$.get( ajaxurl + imagify.concat + 'action=imagify_signup&email=' + inputValue + '&imagifysignupnonce=' + $( '#imagifysignupnonce' ).val() )
						.done( function( response ) {
							if ( ! response.success ) {
								reject( response.data );
							} else {
								resolve();
							}
						} );
					}, 2000 );
				} );
			},
		} ).then( function() {
			swal( {
				title:       imagifyAdmin.labels.signupSuccessTitle,
				html:        imagifyAdmin.labels.signupSuccessText,
				type:        'success',
				customClass: 'imagify-sweet-alert'
			} );
		} );
	} );

	/**
	 * 2. Check and save the Imagify API Key.
	 */
	$( '#imagify-save-api-key' ).on( 'click.imagify', function( e ) {
		e.preventDefault();

		// Display the API key form.
		swal( {
			title:               imagifyAdmin.labels.saveApiKeyTitle,
			html:                imagifyAdmin.labels.saveApiKeyText,
			confirmButtonText:   imagifyAdmin.labels.saveApiKeyConfirmButtonText,
			input:               'text',
			allowOutsideClick:   true,
			showLoaderOnConfirm: true,
			customClass:         'imagify-sweet-alert imagify-sweet-alert-signup',
			inputValidator:      function( inputValue ) {
				return new Promise( function( resolve, reject ) {
					if ( $.trim( inputValue ) === '' || ! inputValue ) {
						reject( imagifyAdmin.labels.ApiKeyErrorEmpty );
					} else {
						resolve();
					}
				} );
			},
			preConfirm:          function( inputValue ) {
				return new Promise( function( resolve, reject ) {
					$.get( ajaxurl + imagify.concat + 'action=imagify_check_api_key_validity&api_key=' + inputValue + '&imagifycheckapikeynonce=' + $( '#imagifycheckapikeynonce' ).val() )
					.done( function( response ) {
						if ( ! response.success ) {
							reject( response.data );
						} else {
							resolve();
						}
					} );
				} );
			},
		} ).then( function() {
			swal( {
				title:       imagifyAdmin.labels.ApiKeyCheckSuccessTitle,
				html:        imagifyAdmin.labels.ApiKeyCheckSuccessText,
				type:        'success',
				customClass: 'imagify-sweet-alert'
			} );
		} );
	} );

	/**
	 * Close an Imagify notice.
	 */
	$( '.imagify-notice-dismiss' ).on( 'click.imagify', function( e ) {
		var $this   = $( this ),
			$parent = $this.parents( '.imagify-welcome, .imagify-notice' ),
			href    = $this.attr( 'href' );

		e.preventDefault();

		// Hide the notice.
		$parent.fadeTo( 100 , 0, function() {
			$( this ).slideUp( 100, function() {
				$( this ).remove();
			} );
		} );

		// Save the dismiss notice.
		$.get( href.replace( 'admin-post.php', 'admin-ajax.php' ) );
	} );

} )(jQuery, document, window);


// Imagify light modal =============================================================================
(function($, d, w, undefined) {

	var imagifyOpenModal = function( $the_link ) {
		var the_target = $the_link.data( 'target' ) || $the_link.attr( 'href' );

		$( the_target ).css( 'display', 'flex' ).hide().fadeIn( 400 ).attr( 'aria-hidden', 'false' ).attr( 'tabindex', '0' ).focus().removeAttr( 'tabindex' ).addClass( 'modal-is-open' );
		$( 'body' ).addClass( 'imagify-modal-is-open' );
	};

	// Accessibility.
	$( '.imagify-modal' ).attr( 'aria-hidden', 'true' );

	$( d )
	// On click on modal trigger.
	.on( 'click.imagify', '.imagify-modal-trigger', function( e ) {
		e.preventDefault();
		imagifyOpenModal( $( this ) );
	} )
	// On click on close button.
	.on( 'click.imagify', '.imagify-modal .close-btn', function() {
		var $modal = $( this ).closest( '.imagify-modal' );

		$modal.fadeOut( 400 ).attr( 'aria-hidden', 'true' ).removeClass( 'modal-is-open' ).trigger( 'modalClosed.imagify' );

		$( 'body' ).removeClass( 'imagify-modal-is-open' );
	} )
	// On close button blur, improve accessibility.
	.on( 'blur.imagify', '.imagify-modal .close-btn', function() {
		var $modal = $( this ).closest( '.imagify-modal' );

		if ( $modal.attr( 'aria-hidden' ) === 'false' ) {
			$modal.attr( 'tabindex', '0' ).focus().removeAttr( 'tabindex' );
		}
	} )
	// On click on dropped layer of modal.
	.on( 'click.imagify', '.imagify-modal', function( e ) {
		$( e.target ).filter( '.modal-is-open' ).find( '.close-btn' ).trigger( 'click.imagify' );
	} )
	// `Esc` key binding.
	.on( 'keydown.imagify', function( e ) {
		if ( 27 === e.keyCode && $( '.imagify-modal.modal-is-open' ).length > 0 ) {
			e.preventDefault();
			// Trigger the event.
			$( '.imagify-modal.modal-is-open' ).find( '.close-btn' ).trigger( 'click.imagify' );
		}
	} );

} )(jQuery, document, window);


// Imagify payment modal ===========================================================================
(function($, d, w, undefined) {

	/**
	 * Payment Modal.
	 *
	 * @since  1.6
	 * @since  1.6.3 include discount campaign
	 * @author Geoffrey
	 */
	var imagifyModal = {};

	if ( ! $( '#imagify-pricing-modal' ).length ) {
		return;
	}

	imagifyModal = {
		$modal:       $( '#imagify-pricing-modal' ),
		$checkboxes:  $( '.imagify-offer-line .imagify-checkbox' ),
		$radios:      $( '.imagify-payment-modal .imagify-radio-line input' ),
		// Plans selection view & payment process view hidden by default.
		$preView:     $( '#imagify-pre-checkout-view' ),
		$plansView:   $( '#imagify-plans-selection-view' ).hide(),
		$paymentView: $( '#imagify-payment-process-view' ).hide(),
		$successView: $( '#imagify-success-view' ).hide(),
		$anotherBtn:  $( '.imagify-choose-another-plan' ),
		speedFadeIn:  300,

		getHtmlPrice: function( content, period ) {
			var monthly, yearly, m, y, output;

			if ( ! period ) {
				period = null;
			}

			if ( typeof content !== 'object' ) {
				content   += ''; // Be sure content is a string.
				content    = content.split( '.' );
				content[1] = content[1].length === 1 ? content[1] + '0' : ( '' + content[1] ).substring( 0, 2 );

				output  = '<span class="imagify-price-big">' + content[0] + '</span> ';
				output += '<span class="imagify-price-mini">.' + content[1] + '</span>';

				return output;
			}

			monthly = content.monthly + '';
			yearly  = content.yearly + '';
			m       = monthly.split( '.' );
			y       = yearly.split( '.' );
			output  = '<span class="imagify-switch-my">';
				output += '<span aria-hidden="' + ( period === 'monthly' ? 'false' : 'true' ) + '" class="imagify-monthly">';
					output += '<span class="imagify-price-big">' + m[0] + '</span> ';
					output += '<span class="imagify-price-mini">.' + ( m[1].length === 1 ? m[1] + '0' : ( '' + m[1] ).substring( 0, 2 )  ) + '</span>';
				output += '</span> ';
				output += '<span aria-hidden="' + ( period === 'yearly' ? 'false' : 'true' ) + '" class="imagify-yearly">';
					output += '<span class="imagify-price-big">' + y[0] + '</span> ';
					output += '<span class="imagify-price-mini">.' + ( y[1].length === 1 ? y[1] + '0' : ( '' + y[1] ).substring( 0, 2 ) ) + '</span>';
				output += '</span>';
			output += '</span>';

			return output;
		},

		getHtmlDiscountPrice: function( content, period ) {
			var monthly, yearly,
				output = '';

			if ( ! period ) {
				period = null;
			}

			if ( typeof content === 'object' ) {
				monthly = content.monthly + '';
				yearly  = content.yearly + '';

				output += '<span class="imagify-price-discount">';
					output += '<span class="imagify-price-discount-dollar">$</span>';
					output += '<span class="imagify-switch-my">';
						output += '<span aria-hidden="' + ( period === 'monthly' ? 'false' : 'true' ) + '" class="imagify-monthly">';
							output += '<span class="imagify-price-discount-number">' + monthly + '</span>';
						output += '</span>';
						output += '<span aria-hidden="' + ( period === 'yearly' ? 'false' : 'true' ) + '" class="imagify-yearly">';
							output += '<span class="imagify-price-discount-number">' + yearly + '</span>';
						output += '</span>';
					output += '</span>';
				output += '</span>';
			} else {
				content += ''; // Be sure content is a string.
				output  += '<span class="imagify-price-discount">';
					output  += '<span class="imagify-price-discount-dollar">$</span>';
					output  += '<span class="imagify-price-discount-number">' + content + '</span>';
				output  += '</span>';
			}

			return output;
		},

		/**
		 * @uses imagifyModal.getHtmlPrice()
		 * @uses imagifyModal.getHtmlDiscountPrice()
		 */
		populateOffer: function ( $offer, datas, type, classes ) {
			var promo = w.imagify_discount_datas,
				add   = datas.additional_gb,   // 4 (monthly)
				ann   = datas.annual_cost,     // 49.9 (monthly)
				id    = datas.id,              // 3 (monthly/onetime)
				lab   = datas.label,           // 'lite' (monthly/onetime)
				mon   = datas.monthly_cost,    // 4.99 (monthly)
				quo   = datas.quota,           // 1000 (MB) - 5000 images (monthly/onetime)
				cos   = datas.cost,            // 3.49 (onetime)
				name  = ( quo >= 1000 ? quo/1000 + ' GB' : quo + ' MB' ),
				pcs   = type === 'monthly' ? { monthly: mon, yearly: Math.round( ann / 12 * 100 ) / 100 } : cos,
				pcsd  = pcs, // Used if discount is active.
				percent, $datas_c, datas_content;

			// Change pricing value only if discount in percentage is active and if offer is a monthly and not a onetime.
			if ( promo.is_active && 'percentage' === promo.coupon_type && 'monthly' === type ) {
				percent = ( 100 - promo.coupon_value ) / 100;
				pcs     = 'monthly' === type ? { monthly: mon * percent, yearly: Math.round( ( ann * percent ) / 12 * 100 ) / 100 } : cos * percent;
			}

			if ( typeof classes !== 'undefined' ) {
				$offer.addClass( 'imagify-' + type + '-' + lab + classes);
			}

			// Name.
			$offer.find( '.imagify-offer-size' ).text( name );

			// Main prices (pcs can be an object or a string).
			$offer.find( '.imagify-number-block' ).html( imagifyModal.getHtmlPrice( pcs, 'monthly' ) );

			// discount prices
			if ( promo.is_active && 'percentage' === promo.coupon_type && 'monthly' === type ) {

				$offer.find( '.imagify-price-block' ).prev( '.imagify-price-discount' ).remove();
				$offer.find( '.imagify-price-block' ).before( imagifyModal.getHtmlDiscountPrice( pcsd, 'monthly' ) );
			}

			// Nb images.
			$offer.find( '.imagify-approx-nb' ).text( quo * 5 );

			if ( 'monthly' === type ) {
				// Additionnal price.
				$offer.find( '.imagify-price-add-data' ).text( '$' + add );
			}

			// Button data-offer attr.
			$datas_c = $offer.find( '.imagify-payment-btn-select-plan' ).length ? $offer.find( '.imagify-payment-btn-select-plan' ) : $offer;

			if ( 'monthly' === type ) {
				datas_content = '{"' + lab + '":{"id":' + id + ',"name":"' + name + '","data":' + quo + ',"dataf":"' + name + '","imgs":' + ( quo * 5 ) + ',"prices":{"monthly":' + pcs.monthly + ',"yearly":' + pcs.yearly + ',"add":' + add + '}}}';
			} else {
				datas_content = '{"ot' + lab + '":{"id":' + id + ',"name":"' + name + '","data":' + quo + ',"dataf":"' + name + '","imgs":' + ( quo * 5 ) + ',"price":' + pcs + '}}';
			}

			$datas_c.attr( 'data-offer', datas_content );

			return $offer;
		},

		populatePayBtn: function() {
			var pl_datas = JSON.parse( $( '.imagify-offer-monthly' ).attr( 'data-offer' ) ),
				ot_datas = JSON.parse( $( '.imagify-offer-onetime' ).attr( 'data-offer' ) ),
				price    = 0,
				price_pl = 0,
				price_ot = 0;

			// Calculate price_pl only if that offer is selected.
			if ( $( '.imagify-offer-monthly' ).hasClass( 'imagify-offer-selected' ) ) {
				if ( $( '#imagify-subscription-monthly' ).filter( ':checked' ).length ) {
					price_pl = pl_datas[ Object.keys( pl_datas )[0] ].prices.monthly;
				} else {
					price_pl = pl_datas[ Object.keys( pl_datas )[0] ].prices.yearly * 12;
				}
			}

			// Calculate price_ot only if that offer is selected.
			if ( $( '.imagify-offer-onetime' ).hasClass( 'imagify-offer-selected' ) ) {
				price_ot = ot_datas[ Object.keys( ot_datas )[0] ].price;
			}

			// Calculate price.
			price = parseFloat( price_ot + price_pl ).toFixed( 2 );

			// Edit button price.
			//$( '.imagify-global-amount' ).text( price ); // Not used.

			if ( '0.00' === price || 0 === price ) {
				$( '#imagify-modal-checkout-btn' ).attr( 'disabled', 'disabled' ).addClass( 'imagify-button-disabled' );
			} else {
				$( '#imagify-modal-checkout-btn' ).removeAttr( 'disabled' ).removeClass( 'imagify-button-disabled' );
			}
		},

		checkCoupon: function() {
			var code = $( '#imagify-coupon-code' ).val(),
				$cptext, $label, $section, nonce;

			if ( '' === code ) {
				return;
			}

			$cptext  = $( '.imagify-coupon-text' );
			$label   = $cptext.find( 'label' );
			$section = $( '.imagify-coupon-section' );
			nonce    = $( '#imagify-get-pricing-modal' ).data( 'nonce' );

			$cptext.addClass( 'checking' );

			// Get the true prices.
			$.post( ajaxurl, { action: 'imagify_check_coupon', coupon: code, imagifynonce: nonce }, function( response ) {
				var coupon_value;

				$cptext.removeClass( 'checking' );

				// Error during the request.
				if ( ! response.success ) {
					$section.removeClass( 'validated' ).addClass( 'invalid' );
					$label.text( imagifyAdmin.labels.errorCouponAPI );
				} else if ( response.data.success ) {
					coupon_value = 'percentage' === response.data.coupon_type ? response.data.value + '%' : '$' + response.data.value;
					$section.removeClass( 'invalid' ).addClass( 'validated' );
					$label.html( imagifyAdmin.labels.successCouponAPI );
					$label.find( '.imagify-coupon-offer' ).text( coupon_value );
					$label.find( '.imagify-coupon-word' ).text( code );
				} else {
					$section.removeClass( 'validated' ).addClass( 'invalid' );
					$label.text( response.data.detail );
				}
			} );
		},

		/**
		 * @uses imagifyModal.populateOffer()
		 * @uses imagifyModal.populatePayBtn()
		 * @uses imagifyModal.checkCoupon()
		 */
		getPricing: function( $button ){
			var nonce              = $button.data( 'nonce' ),
				prices_rq_datas    = {
					action:       'imagify_get_prices',
					imagifynonce: nonce
				},
				imgs_rq_datas      = {
					action:       'imagify_get_images_counts',
					imagifynonce: nonce
				},
				prices_rq_discount = {
					action:       'imagify_get_discount',
					imagifynonce: nonce
				};

			imagifyModal.$modal.find( '.imagify-modal-loader' ).hide().show();

			/**
			 * TODO: change the way to waterfall requests.
			 * Use setInterval + counter instead.
			 */

			// Get the true prices.
			$.post( ajaxurl, prices_rq_datas, function( prices_response ) {

				if ( ! prices_response.success ) {
					// TODO: replace modal content by any information.
					// An error occurred.

					// Populate Pay button.
					imagifyModal.populatePayBtn();
					return;
				}

				// get the image estimates sizes
				$.post( ajaxurl, imgs_rq_datas, function( imgs_response ) {

					if ( ! imgs_response.success ) {
						// TODO: replace modal content by any information.
						// An error occurred.
						return;
					}

					// Get the discount informations.
					$.post( ajaxurl, prices_rq_discount, function( discount_response ) {
						var images_datas, prices_datas, promo_datas, monthlies, onetimes,
							mo_user_cons, ot_user_cons,
							$mo_tpl, $ot_tpl,
							ot_clone, mo_clone,
							ot_html      = '',
							mo_html      = '',
							ot_suggested = false,
							mo_suggested = false,
							$estim_block, $offers_block,
							$banners, date_end, promo, discount,
							prev_offers, nb_to_remove, $total_offers,
							i, j;

						if ( ! discount_response.success ) {
							// TODO: replace modal content by any information.
							// An error occurred.
							return;
						}

						images_datas = imgs_response.data;
						prices_datas = prices_response.data;
						promo_datas  = discount_response.data;
						monthlies    = prices_datas.monthlies;
						onetimes     = prices_datas.onetimes;
						mo_user_cons = Math.round( images_datas.average_month_size.raw / 1000000 ); // 1000000 in MB,
						ot_user_cons = Math.round( images_datas.total_library_size.raw / 1000000 ); // in MB,
						$mo_tpl      = $( '#imagify-offer-monthly-template' );
						$ot_tpl      = $( '#imagify-offer-onetime-template' );
						ot_clone     = $ot_tpl.html();
						mo_clone     = $mo_tpl.html();
						$estim_block = $( '.imagify-estimation-block' );

						// Refresh Analyzing block.
						$estim_block.removeClass( 'imagify-analyzing' );
						$estim_block.find( '.average-month-size' ).text( images_datas.average_month_size.human );
						$estim_block.find( '.total-library-size' ).text( images_datas.total_library_size.human );

						// Switch offers title is < 25mb.
						if ( mo_user_cons < 25 &&  ot_user_cons < 25 ) {
							$( '.imagify-pre-checkout-offers .imagify-modal-title' ).addClass( 'imagify-enough-free' );
							$( '.imagify-offer-selected' ).removeClass( 'imagify-offer-selected' ).find( '.imagify-checkbox' ).removeAttr( 'checked' );
						} else {
							$( '.imagify-enough-free' ).removeClass( 'imagify-enough-free' );
							$( '.imagify-offer-selected' ).addClass( 'imagify-offer-selected' ).find( '.imagify-checkbox' ).attr( 'checked', 'checked' );
						}

						// Don't create prices table if something went wrong during request.
						if ( null === monthlies || null === onetimes ) {
							$offers_block = $( '.imagify-pre-checkout-offers' );

							// Hide main content.
							$offers_block.hide().attr( 'aria-hidden', true );

							// Show error message.
							$offers_block.closest( '.imagify-modal-views' ).find( '.imagify-popin-message' ).remove();
							$offers_block.after( '<div class="imagify-popin-message imagify-error"><p>' + imagifyAdmin.labels.errorPriceAPI + '</p></div>' );

							// Show the modal content.
							imagifyModal.$modal.find( '.imagify-modal-loader' ).fadeOut( 300 );
							return;
						}

						// Autofill coupon code & Show banner if discount is active.
						w.imagify_discount_datas = promo_datas;

						if ( promo_datas.is_active ) {
							$banners = $( '.imagify-modal-promotion' );
							date_end = promo_datas.date_end.split( 'T' )[0];
							promo    = promo_datas.coupon_value;
							discount = 'percentage' === promo_datas.coupon_type ? promo + '%' : '$' + promo;

							// Fill coupon code.
							$( '#imagify-coupon-code' ).val( promo_datas.label ).attr( 'readonly', true );

							// Show banners.
							$banners.addClass( 'active' ).attr( 'aria-hidden', 'false' );

							// Populate banners.
							$banners.find( '.imagify-promotion-number' ).text( discount );
							$banners.find( '.imagify-promotion-date' ).text( date_end );

							// Auto validate coupon.
							imagifyModal.checkCoupon();
						}

						/**
						 * Below lines will build Plan and Onetime offers lists.
						 * It will also pre-select a Plan and Onetime in both of views: pre-checkout and pricing tables.
						 */

						// Now, do the MONTHLIES Markup.
						$.each( monthlies, function( index, value ) {
							var $tpl, $offer,
								classes = '';

							// If it's free, don't show it.
							if ( 'free' === value.label ) {
								return true; // Continue-like (but $.each is not a loop... so).
							}

							$tpl = $( mo_clone ).clone();

							// If offer is too big (far) than estimated needs, don't show the offer.
							if ( false !== mo_suggested && ( index - mo_suggested ) > 2 ) {
								return true;
							}

							// Parent classes.
							if ( ( mo_user_cons < value.quota && false === mo_suggested ) ) {
								classes      = ' imagify-offer-selected';
								mo_suggested = index;

								// Add this offer as pre-selected item in pre-checkout view.
								$offer = $( '.imagify-pre-checkout-offers' ).find( '.imagify-offer-monthly' );

								// Populate the Pre-checkout view depending on user_cons.
								imagifyModal.populateOffer( $offer, value, 'monthly' );
							}

							// Populate each offer.
							$tpl = imagifyModal.populateOffer( $tpl, value, 'monthly', classes );

							// Complete Monthlies HTML.
							mo_html += $tpl[0].outerHTML;
						} );

						// Deal with the case of too much small offers (before recommanded one).
						prev_offers = $( mo_html ).filter( '.imagify-offer-selected' ).prevAll();

						// If we have more than 1 previous offer.
						if ( prev_offers.length > 1 ) {
							nb_to_remove  = prev_offers.length - 1;
							$total_offers = $( mo_html );

							// Remove too far previous offer.
							for ( i = 0; i < nb_to_remove; i++ ) {
								delete $total_offers[ i ];
							}

							// Rebuild mo_html with removed items.
							mo_html = '';
							for ( j = 0; j < $total_offers.length; j++) {
								mo_html += $( '<div/>' ).append( $total_offers[ j ] ).html();
							}
						}

						// Do the ONETIMES Markup.
						$.each( onetimes, function( index, value ) {
							var $tpl    = $( ot_clone ).clone(),
								$offer  = $( '.imagify-pre-checkout-offers' ).find( '.imagify-offer-onetime' ),
								classes = '',
								tvalue;

							// Parent classes.
							if ( ( ot_user_cons < value.quota && ot_suggested === false ) ) {
								classes      = ' imagify-offer-selected';
								ot_suggested = true;

								// Populate the Pre-checkout view depending on user_cons.
								imagifyModal.populateOffer( $offer, value, 'onetime' );
							}

							// If too big, populate with the biggest offer available.
							// TODO: create custom offers at this point.
							if ( ( onetimes.length - 1 ) === index && false === ot_suggested ) {
								// populate the Pre-checkout view depending on user_cons
								tvalue = onetimes[ onetimes.length - 1 ];
								imagifyModal.populateOffer( $offer, tvalue, 'onetime' );
							}

							// Populate each offer.
							$tpl = imagifyModal.populateOffer( $tpl, value, 'onetime', classes );

							// complete Onetimes HTML
							ot_html += $tpl[0].outerHTML;
						} );

						// Fill pricing tables.
						if ( $mo_tpl.parent().find( '.imagify-offer-line' ) ) {
							$mo_tpl.parent().find( '.imagify-offer-line' ).remove();
						}

						$mo_tpl.before( mo_html );

						if ( $ot_tpl.parent().find( '.imagify-offer-line' ) ) {
							$ot_tpl.parent().find( '.imagify-offer-line' ).remove();
						}

						$ot_tpl.before( ot_html );

						// Show the content.
						imagifyModal.$modal.find( '.imagify-modal-loader' ).fadeOut( 300 );

					} ); // Third AJAX request to get discount information.

				} ); // Second AJAX request for image estimation sizes.

				// Populate Pay button.
				imagifyModal.populatePayBtn();
			} ); // End $.post.
		},

		/**
		 * @uses imagifyModal.populatePayBtn()
		 */
		checkCheckbox: function( $checkbox ) {
			var sel_class = 'imagify-offer-selected';

			$checkbox.each( function() {
				var $this = $( this );

				if ( $this.is( ':checked' ) ) {
					$this.closest( '.imagify-offer-line' ).addClass( sel_class );
				} else {
					$this.closest( '.imagify-offer-line' ).removeClass( sel_class );
				}
			} );

			// Update pay button.
			imagifyModal.populatePayBtn();
		},

		/**
		 * @uses imagifyModal.populatePayBtn()
		 */
		checkRadio: function( $radio ) {
			var year_class  = 'imagify-year-selected',
				month_class = 'imagify-month-selected';

			$radio.each( function() {
				// To handle modal pricing & modal suggestion.
				var $_this = $( this ),
					$parent, $to_switch;

				if ( $_this.parent( '.imagify-cart-list-switcher' ).length ) {
					$parent = $_this.closest( '.imagify-cart' );
				} else if ( $_this.parent( '.imagify-small-options' ).length ) {
					$parent = $_this.parent( '.imagify-small-options' ).next( '.imagify-pricing-table' );
				} else {
					$parent = $_this.closest( '.imagify-offer-line' );
				}

				$to_switch = $parent.find( '.imagify-switch-my' );

				if ( $_this.val() === 'yearly' ) {
					$parent.addClass( year_class ).removeClass( month_class );
					$to_switch.find( '.imagify-monthly' ).attr( 'aria-hidden', 'true' );
					$to_switch.find( '.imagify-yearly' ).attr( 'aria-hidden', 'false' );
				} else {
					$parent.addClass( month_class ).removeClass( year_class );
					$to_switch.find( '.imagify-monthly' ).attr( 'aria-hidden', 'false' );
					$to_switch.find( '.imagify-yearly' ).attr( 'aria-hidden', 'true' );
				}
			} );

			// Update Pay button information.
			imagifyModal.populatePayBtn();

			return $radio;
		},

		/**
		 * Currently not used.
		 * @uses imagifyModal.populatePayBtn()
		 */
		populateBtnPrice: setInterval( function() {
			imagifyModal.populatePayBtn();
		}, 1000 ),

		/**
		 * 1) Modal Payment change/select plan
		 * 2) Checkout selection(s)
		 * 3) Payment process
		 */

		getPeriod: function() {
			return $( '.imagify-offer-monthly' ).hasClass( 'imagify-month-selected' ) ? 'monthly' : 'yearly';
		},

		getApiKey: function() {
			return $( '#imagify-payment-iframe' ).data( 'imagify-api' );
		},

		switchToView: function( $view, data ) {
			var viewId        = $view.attr( 'id' ),
				$modalContent = imagifyModal.$modal.children( '.imagify-modal-content' );

			$view.siblings( '.imagify-modal-views' ).hide().attr( 'aria-hidden', 'true' );

			// Plans view has tabs: display the right one.
			if ( data && data.tab ) {
				$view.find( 'a[href="#' + data.tab + '"]' ).trigger( 'click.imagify' );
			}

			// Payment view: it's an iframe.
			if ( 'imagify-payment-process-view' === viewId ) {
				$modalContent.addClass( 'imagify-iframe-viewing' );
			} else {
				$modalContent.removeClass( 'imagify-iframe-viewing' );
			}

			// Success view: some tweaks.
			if ( 'imagify-success-view' === viewId ) {
				$modalContent.addClass( 'imagify-success-viewing' );
				imagifyModal.$modal.attr( 'aria-labelledby', 'imagify-success-view' );
			} else {
				$modalContent.removeClass( 'imagify-success-viewing' );
				imagifyModal.$modal.removeAttr( 'aria-labelledby' );
			}

			$view.fadeIn( imagifyModal.speedFadeIn ).attr( 'aria-hidden', 'false' );
		},

		/**
		 * @uses imagifyModal.getApiKey()
		 */
		iframeSetSrc: function( params ) {
			/**
			 * params = {
			 *     'monthly': {
			 *         'lite': {
			 *             name: 'something',
			 *             id: ''
			 *         }
			 *     },
			 *     'onetime': {
			 *         'recommended': {
			 *             name: 'Recommend',
			 *             id: ''
			 *         }
			 *     },
			 *     'period': 'monthly'|'yearly'
			 * }
			 */

			var $iframe    = $( '#imagify-payment-iframe' ),
				iframe_src = $iframe.attr( 'src' ),
				pay_src    = $iframe.data( 'src' ),
				monthly_id = 0,
				onetime_id = 0,
				key, rt_onetime, rt_yearly, rt_monthly, coupon, rt_coupon, amount, $iframeClone, tofind;

			// If we only change monthly/yearly payment mode.
			if ( typeof params === 'string' && '' !== iframe_src ) {
				tofind     = 'monthly' === params ? 'yearly' : 'monthly';
				iframe_src = iframe_src.replace( tofind, params );
				$iframe.attr( 'src', iframe_src );
				return;
			}

			// If we get new informations about products.
			if ( typeof params !== 'object' ) {
				return;
			}

			if ( params.monthly ) {
				monthly_id = params.monthly[ Object.keys( params.monthly )[0] ].id;
			}

			if ( params.onetime ) {
				onetime_id = params.onetime[ Object.keys( params.onetime )[0] ].id;
				// If onetime ID === 999 it's a custom plan, send datas instead.
				onetime_id = ( onetime_id + '' === '999' ? params.onetime[ Object.keys( params.onetime )[0] ].data : onetime_id );
			}

			if ( ! params.period ) {
				imagify.info( 'No period defined' );
				return;
			}

			key        = imagifyModal.getApiKey();
			rt_onetime = onetime_id;
			rt_yearly  = 'yearly'  === params.period ? monthly_id : 0;
			rt_monthly = 'monthly' === params.period ? monthly_id : 0;
			coupon     = $( '#imagify-coupon-code' ).val();
			rt_coupon  = '' === coupon ? 'none' : coupon;
			// Not used but...
			amount     = parseFloat( $( '.imagify-global-amount' ).text() ).toFixed( 2 );

			// Compose route.
			// pay_src + :ontimeplan(0)/:monthlyplan(0)/:yearlyplan(0)/:coupon(none)/
			pay_src = pay_src + rt_onetime + '/' + rt_monthly + '/' + rt_yearly + '/' + rt_coupon + '/';

			// iFrame sort of cache fix.
			$iframeClone = $iframe.remove().attr( 'src', pay_src );

			imagifyModal.$paymentView.html( $iframeClone );
		},

		/**
		 * Public function triggered by payement iframe.
		 */
		paymentClose: function() {
			$( '.imagify-iframe-viewing .close-btn' ).trigger( 'click.imagify' );
			$( '.imagify-iframe-viewing' ).removeClass( 'imagify-iframe-viewing' );
		},

		/**
		 * @uses imagifyModal.switchToView()
		 */
		paymentBack: function() {
			imagifyModal.switchToView( imagifyModal.$preView );
		},

		/**
		 * @uses imagifyModal.switchToView()
		 */
		paymentSuccess: function() {
			imagifyModal.switchToView( imagifyModal.$successView );
		},

		/**
		 * @uses imagifyModal.paymentClose()
		 * @uses imagifyModal.paymentBack()
		 * @uses imagifyModal.paymentSuccess()
		 */
		checkPluginMessage: function( event ) {
			var origin = event.origin || event.originalEvent.origin;

			if ( 'https://app.imagify.io' !== origin && 'http://dapp.imagify.io' !== origin ) {
				return;
			}

			switch ( event.data ) {
				case 'cancel':
					imagifyModal.paymentClose();
					break;
				case 'back':
					imagifyModal.paymentBack();
					break;
				case 'success':
					imagifyModal.paymentSuccess();
					break;
			}
		}
	};

	/**
	 * INIT.
	 */

	// Check all boxes on load.
	imagifyModal.checkCheckbox( imagifyModal.$checkboxes );
	imagifyModal.checkRadio( imagifyModal.$radios.filter( ':checked' ) );

	// Check coupon onload.
	imagifyModal.checkCoupon();

	// Check the changed box.
	imagifyModal.$checkboxes.on( 'change.imagify', function() {
		imagifyModal.checkCheckbox( $( this ) );
	} );

	// Check the radio box.
	imagifyModal.$radios.on( 'change.imagify', function() {
		imagifyModal.checkRadio( $( this ) );
	} );

	/**
	 * Get pricings on modal opening.
	 * Build the pricing tables inside modal.
	 */
	$( '#imagify-get-pricing-modal' ).on( 'click.imagify-ajax', function() {
		imagifyModal.getPricing( $( this ) );
	} );

	/**
	 * Reset the modal on close.
	 */
	$( d ).on( 'modalClosed.imagify', '.imagify-payment-modal', function() {
		// Reset viewing class & aria-labelledby.
		$( this ).find( '.imagify-modal-content' ).removeClass( 'imagify-success-viewing imagify-iframe-viewing' );

		// Reset first view after fadeout ~= 300 ms.
		setTimeout( function() {
			$( '.imagify-modal-views' ).hide();
			$( '#imagify-pre-checkout-view' ).show();
		}, 300 );
	} );

	/**
	 * Get validation for Coupon Code
	 * - On blur
	 * - On Enter or Spacebar press
	 * - On click OK button
	 *
	 * @since 1.6.3 Only if field hasn't readonly attribute (discount auto-applied).
	 */
	$( '#imagify-coupon-code' ).on( 'blur.imagify', function() {
		if ( ! $( this ).attr('readonly') ) {
			imagifyModal.checkCoupon();
		}
	} ).on( 'keydown.imagify', function( e ) {
		var $this = $( this );

		if ( $this.attr( 'readonly' ) ) {
			return;
		}
		if ( 13 === e.keyCode || 32 === e.keyCode ) {
			imagifyModal.checkCoupon();
			return false;
		}
		if ( $this.val().length >= 3 ) {
			$this.closest( '.imagify-coupon-input' ).addClass( 'imagify-canbe-validate' );
		} else {
			$this.closest( '.imagify-coupon-input' ).removeClass( 'imagify-canbe-validate' );
		}
	} );

	$( '#imagify-coupon-validate' ).on( 'click.imagify', function() {
		imagifyModal.checkCoupon();
		$( this ).closest( '.imagify-canbe-validate' ).removeClass( 'imagify-canbe-validate' );
	} );

	/**
	 * View game, step by step.
	 */

	// 1) when you decide to choose another plan.

	/**
	 * 1.a) on click, display choices.
	 *
	 * @uses imagifyModal.switchToView()
	 */
	imagifyModal.$anotherBtn.on( 'click.imagify', function( e ) {
		var type = $( this ).data( 'imagify-choose' ),
			tab  = 'imagify-pricing-tab-' + ( 'plan' === type ? 'monthly' : 'onetime' );

		e.preventDefault();

		imagifyModal.switchToView( imagifyModal.$plansView, { tab: tab } );
	} );

	/**
	 * 1.b) on click in a choice, return to pre-checkout step.
	 *
	 * @uses imagifyModal.getHtmlPrice()
	 * @uses imagifyModal.switchToView()
	 * @uses imagifyModal.populatePayBtn()
	 */
	imagifyModal.$modal.on( 'click.imagify', '.imagify-payment-btn-select-plan', function( e ) {
		var $_this       = $( this ),
			$offer_line  = $_this.closest( '.imagify-offer-line' ),
			datas        = $_this.data( 'offer' ),
			datas_str    = $_this.attr( 'data-offer' ),
			is_onetime   = $_this.closest( '.imagify-tab-content' ).attr( 'id' ) !== 'imagify-pricing-tab-monthly',
			$target_line = is_onetime ? imagifyModal.$preView.find( '.imagify-offer-onetime' ) : imagifyModal.$preView.find( '.imagify-offer-monthly' ),
			period       = is_onetime ? null : ( $_this.closest( '.imagify-pricing-table' ).hasClass( 'imagify-month-selected' ) ? 'monthly' : 'yearly' ),
			price        = is_onetime ? imagifyModal.getHtmlPrice( datas[ Object.keys( datas )[0] ].price ) : imagifyModal.getHtmlPrice ( datas[ Object.keys( datas )[0] ].prices, period ),
			monthly_txt  = is_onetime ? '' : '<span class="imagify-price-by">' + $offer_line.find( '.imagify-price-by' ).text() + '</span>',
			discount     = $offer_line.find( '.imagify-price-discount' ).html(),
			imgs         = $offer_line.find( '.imagify-approx-nb' ).text(),
			offer_size   = $offer_line.find( '.imagify-offer-size' ).text();

		e.preventDefault();

		// Change views to go back pre-checkout.
		imagifyModal.switchToView( imagifyModal.$preView );

		// Change price (+ "/month" if found in monthly plans).
		$target_line.find( '.imagify-number-block' ).html( price + monthly_txt );

		// Change discount.
		$target_line.find( '.imagify-price-discount' ).html( discount );

		// Change approx images nb.
		$target_line.find( '.imagify-approx-nb' ).text( imgs );

		// Change offer size name.
		$target_line.find( '.imagify-offer-size' ).text( offer_size );

		// Change datas (json).
		$target_line.attr( 'data-offer', datas_str );

		if ( ! is_onetime ) {
			$target_line.find( '.imagify-price-add-data' ).text( $offer_line.find( '.imagify-price-add-data' ).text() );

			// Trigger period selected from offer selection view to pre-checkout view.
			if ( 'monthly' === period ) {
				$target_line.find( '#imagify-subscription-monthly' ).trigger( 'click.imagify' );
			} else {
				$target_line.find( '#imagify-subscription-yearly' ).trigger( 'click.imagify' );
			}
			$target_line.find( '.imagify-inline-options' ).find( 'input:radio:checked' ).trigger( 'change.imagify' );
		}

		// Update price information in button.
		imagifyModal.populatePayBtn();
	} );

	/**
	 * 2) when you checkout.
	 *
	 * @uses imagifyModal.switchToView()
	 * @uses imagifyModal.getPeriod()
	 * @uses imagifyModal.iframeSetSrc()
	 */
	$( '#imagify-modal-checkout-btn' ).on( 'click.imagify', function( e ) {
		var $monthly_offer, $onetime_offer, checkout_datas;

		e.preventDefault();

		// Do nothing if button disabled.
		if ( $( this ).hasClass( 'imagify-button-disabled' ) ) {
			return;
		}

		$monthly_offer = $( '.imagify-offer-monthly' );
		$onetime_offer = $( '.imagify-offer-onetime' );
		checkout_datas = {};

		// If user choose a monthly plan.
		if ( $monthly_offer.hasClass( 'imagify-offer-selected' ) ) {
			checkout_datas.monthly = JSON.parse( $monthly_offer.attr( 'data-offer' ) );
		}

		// If user choose a one time plan.
		if ( $onetime_offer.hasClass( 'imagify-offer-selected' ) ) {
			checkout_datas.onetime = JSON.parse( $onetime_offer.attr( 'data-offer' ) );
		}

		// Change views to go to checkout/payment view.
		imagifyModal.switchToView( imagifyModal.$paymentView );

		checkout_datas.period = imagifyModal.getPeriod();

		imagifyModal.iframeSetSrc( checkout_datas );
	} );

	/**
	 * Go back to previous step ("Choose Another Plan" links).
	 */
	$( '.imagify-back-to-plans' ).on( 'click.imagify', function( e ) {
		var $_this     = $( this ),
			is_onetime = $_this.closest( '.imagify-cart-item' ).hasClass( 'imagify-cart-item-onetime' );

		e.preventDefault();

		if ( is_onetime ) {
			$( '.imagify-offer-onetime' ).find( '.imagify-choose-another-plan' ).trigger( 'click.imagify' );
		} else {
			$( '.imagify-offer-monthly' ).find( '.imagify-choose-another-plan' ).trigger( 'click.imagify' );
		}
	} );

	// Message/communication API.
	w.addEventListener( 'message', imagifyModal.checkPluginMessage, true );

} )(jQuery, document, window);


(function($, d, w, undefined) {

	/**
	 * Tabs.
	 *
	 * @Markup:
	 * ul.imagify-tabs
	 *     li.imagify-tab.imagify-current
	 *         a[href="#target"]
	 * div.imagify-tabs-contents
	 *     div.imagify-tab-content#target
	 */
	$( '.imagify-tab' ).on( 'click.imagify', function( e ) {
		var $_this     = $( this ),
			curr_class = 'imagify-current',
			target;

		e.preventDefault();

		if ( $_this.hasClass( 'imagify-current' ) ) {
			return;
		}

		target = $_this.find( 'a' ).attr( 'href' ) || '#' + $_this.find( 'a' ).attr( 'aria-controls' );

		// Show right tab content.
		$_this.closest( '.imagify-tabs' ).next( '.imagify-tabs-contents' ).find( '.imagify-tab-content' ).hide().attr( 'aria-hidden', 'true' );
		$( target ).fadeIn( 275 ).attr( 'aria-hidden', 'false' );

		// Change active tabs.
		$_this.closest( '.imagify-tabs' ).find( '.imagify-tab' ).removeClass( curr_class ).attr( 'aria-selected', 'false' );
		$_this.addClass( curr_class ).attr( 'aria-selected', 'true' );
	} );

} )(jQuery, document, window);
