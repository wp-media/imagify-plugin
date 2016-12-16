jQuery(function($){

	var concat = ajaxurl.indexOf("?") > 0 ? "&" : "?",
		imagify = {
			log: function ( content ) {
				if (console !== 'undefined') console.log( content );
			},
			info: function ( content ) {
				if (console !== 'undefined') console.info( content );
			}
		};

	/*
	 * Create a new Imagify account
	 */
	$('#imagify-signup').click( function(e){
		e.preventDefault();

		// Display the sign up form
		swal({
			title: imagifyAdmin.labels.signupTitle,
			html: imagifyAdmin.labels.signupText,
			confirmButtonText: imagifyAdmin.labels.signupConfirmButtonText,
			input: 'email',
			closeOnConfirm: false,
			allowOutsideClick: true,
			showLoaderOnConfirm: true,
			customClass: "imagify-sweet-alert imagify-sweet-alert-signup",
			inputValidator: function(inputValue) {
    			return new Promise(function(resolve, reject) {
            			if ($.trim(inputValue) == "" || ! inputValue) {
                            reject(imagifyAdmin.labels.signupErrorEmptyEmail);
                        } else {
                            resolve();
                        }
    			});
            },
			preConfirm: function(inputValue) {
    			return new Promise(function(resolve, reject) {
        			setTimeout(function() {
            			$.get(ajaxurl + concat + "action=imagify_signup&email=" +inputValue + "&imagifysignupnonce="+ $('#imagifysignupnonce').val())
            			.done(function(response){
                            if( !response.success ) {
                                reject(response.data);
                            } else {
                                resolve();
                            }
                        });
        			}, 2000);
			    });
			},
		}).then(function(inputValue){
            swal({
                title:imagifyAdmin.labels.signupSuccessTitle,
                html: imagifyAdmin.labels.signupSuccessText,
                type: "success",
                customClass: "imagify-sweet-alert"
            });
	    });
	});
	/*
	 * Check and save the Imagify API Key
	 */
	$('#imagify-save-api-key').click( function(e){
		e.preventDefault();

		// Display the sign up form
		swal({
			title: imagifyAdmin.labels.saveApiKeyTitle,
			html: imagifyAdmin.labels.saveApiKeyText,
			confirmButtonText: imagifyAdmin.labels.saveApiKeyConfirmButtonText,
			input: 'text',
			allowOutsideClick: true,
			showLoaderOnConfirm: true,
			customClass: "imagify-sweet-alert imagify-sweet-alert-signup",
			inputValidator: function(inputValue) {
    			return new Promise(function(resolve, reject) {
        			if ($.trim(inputValue) == "" || ! inputValue) {
                        reject(imagifyAdmin.labels.ApiKeyErrorEmpty);
			        } else {
    			        resolve();
			        }
    			});
			},
			preConfirm: function(inputValue) {
    			return new Promise(function(resolve, reject) {
        			$.get(ajaxurl + concat + "action=imagify_check_api_key_validity&api_key=" +inputValue + "&imagifycheckapikeynonce="+ $('#imagifycheckapikeynonce').val())
                    .done(function(response){
                        if( !response.success ) {
					        reject( response.data );
				        } else {
    				        resolve();
    				    }
    			    });
                });
			},
		}).then(function(inputValue){
            swal({
                title: imagifyAdmin.labels.ApiKeyCheckSuccessTitle,
                html: imagifyAdmin.labels.ApiKeyCheckSuccessText,
                type: "success",
                customClass: "imagify-sweet-alert"
            });
		});
	});
	
	/*
	 * Toggle an Imagify notice	 
	 */
	$('.imagify-notice-dismiss').click( function( e ) {
		e.preventDefault();
		
		var obj 	= $(this),
			parent  = obj.parents('.imagify-welcome, .imagify-notice'),
			href 	= obj.attr('href');
			
			// Hide the notice
			parent.fadeTo( 100 , 0, function() {
				$(this).slideUp( 100, function() {
					$(this).remove();
				});
			});
			
			// Save the dismiss notice
			$.get( href.replace( 'admin-post.php', 'admin-ajax.php' ) );		
	});


	/*
	 * Imagify Light modal
	 */
	var imagify_open_modal = function( $the_link ){

		var the_target = $the_link.data('target') || $the_link.attr('href');

		$( the_target ).css('display', 'flex').hide().fadeIn(400).attr('aria-hidden', 'false').attr('tabindex', '0').focus().removeAttr('tabindex').addClass('modal-is-open');
		$('body').addClass('imagify-modal-is-open');

	};

	// accessibility
	$( '.imagify-modal' ).attr( 'aria-hidden', 'true' );

	// on click on modal trigger
	$( '.imagify-modal-trigger' ).on('click.imagify', function(){
		imagify_open_modal( $(this) );
		return false;
	});

	// on click on close button
	$( document ).on( 'click.imagify', '.imagify-modal .close-btn', function(){
		$(this).closest( '.imagify-modal' ).fadeOut( 400 ).attr( 'aria-hidden', 'true' ).removeClass( 'modal-is-open' );

		// in Payment modal case
		if ( $(this).closest( '.imagify-modal' ).hasClass( 'imagify-payment-modal' ) ) {
			
			// reset viewing class & aria-labelledby
			$(this).closest( '.imagify-modal-content' ).removeClass( 'imagify-success-viewing imagify-iframe-viewing' );
			
			// reset first view after fadeout ~= 300 ms
			setTimeout( function() {
				$( '.imagify-modal-views' ).hide();
				$( '#imagify-pre-checkout-view' ).show();
			}, 300 );
		}

		$( 'body' ).removeClass( 'imagify-modal-is-open' );
	})
	.on( 'blur.imagify', '.imagify-modal .close-btn', function(){
		var $modal = $(this).closest('.imagify-modal');
		if ( $modal.attr('aria-hidden') === 'false' ) {
			$modal.attr('tabindex', '0').focus().removeAttr('tabindex');
		}
	});

	// On click on dropped layer of modal
	$( document ).on('click.imagify', '.imagify-modal', function( e ) {
		$( e.target ).filter( '.modal-is-open' ).find( '.close-btn' ).trigger( 'click.imagify' );
	});

	// `Esc` key binding
	$( window ).on( 'keydown.imagify', function( e ) {
		if ( e.keyCode == 27 && $('.imagify-modal.modal-is-open').length > 0 ) {

			e.preventDefault();
			
			// trigger the event
			$('.imagify-modal.modal-is-open').find('.close-btn').trigger('click.imagify');

			return false;
		}
	});
	
	var busy = false,
		xhr	 = false;
		
	$( '#wp-admin-bar-imagify' ).hover( function() {
		if ( true === busy ) {
			return;
		}
		
		busy = true;
		
		var $adminBarProfile = $('#wp-admin-bar-imagify-profile-content');
		
		if( $adminBarProfile.is(':empty') ) {
			xhr = $.get(ajaxurl + concat + "action=imagify_get_admin_bar_profile&imagifygetadminbarprofilenonce="+ $('#imagifygetadminbarprofilenonce').val())
			.done(function(response){
				$adminBarProfile.html(response.data);
				$('#wp-admin-bar-imagify-profile-loading').remove();
				busy = false;
			});	
		}
	});

	/**
	 * Payment Modal
	 * 
	 * @since  1.6
	 * @since  1.6.3 include discount campaign
	 * @author Geoffrey
	 */
	
	if ( $('#imagify-pricing-modal').length ) {
		var $modal = $('#imagify-pricing-modal'),
			imagify_get_html_price = function( content, period ) {
				if ( ! period ) period = null;
				output = '';

				if ( typeof content === 'object' ) {
					var monthly = content.monthly + "",
						yearly = content.yearly + "",
						m = monthly.split('.'),
						y = yearly.split('.');

					output += '<span class="imagify-switch-my"><span aria-hidden="' + ( period === 'monthly' ? 'false' : 'true' ) + '" class="imagify-monthly"><span class="imagify-price-big">' + m[0] + '</span> <span class="imagify-price-mini">.' + ( m[1].length === 1 ? m[1] + '0' : ( '' + m[1] ).substring( 0, 2 )  ) + '</span></span> <span aria-hidden="' + ( period === 'yearly' ? 'false' : 'true' ) + '" class="imagify-yearly"><span class="imagify-price-big">' + y[0] + '</span> <span class="imagify-price-mini">.' + ( y[1].length === 1 ? y[1] + '0' : ( '' + y[1] ).substring( 0, 2 ) ) + '</span></span></span>';
				} else {
					var content = content + "", // be sure content is a string
						v = content.split('.');

					output += '<span class="imagify-price-big">' + v[0] + '</span> <span class="imagify-price-mini">.' + ( v[1].length === 1 ? v[1] + '0' : ( '' + v[1] ).substring( 0, 2 ) ) + '</span>';
				}

				return output;
			},
			imagify_get_html_discount_price = function( content, period ) {
				if ( ! period ) period = null;
				output = '';

				if ( typeof content === 'object' ) {
					var monthly = content.monthly + "",
						yearly = content.yearly + "";

					output += '<span class="imagify-price-discount">'
								+ '<span class="imagify-price-discount-dollar">$</span>'
								+ '<span class="imagify-switch-my">'
									+ '<span aria-hidden="' + ( period === 'monthly' ? 'false' : 'true' ) + '" class="imagify-monthly">'
										+ '<span class="imagify-price-discount-number">' + monthly + '</span>'
									+ '</span>'
									+ '<span aria-hidden="' + ( period === 'yearly' ? 'false' : 'true' ) + '" class="imagify-yearly">'
										+ '<span class="imagify-price-discount-number">' + yearly + '</span>'
									+ '</span>'
								+ '</span>'
							+ '</span>';
				} else {
					var content = content + ""; // be sure content is a string

					output += '<span class="imagify-price-discount">'
								+ '<span class="imagify-price-discount-dollar">$</span>'
								+ '<span class="imagify-price-discount-number">' + content + '</span>'
							+ '</span>';
				}

				return output;
			},
			imagify_populate_offer = function ( $offer, datas, type, classes ) {
				var promo = window.imagify_discount_datas,
					add   = datas.additional_gb,   // 4 (monthly)
					ann   = datas.annual_cost,	   // 49.9 (monthly)
					id    = datas.id,			   // 3 (monthly/onetime)
					lab   = datas.label,		   // 'lite' (monthly/onetime)
					mon   = datas.monthly_cost,	   // 4.99 (monthly)
					quo   = datas.quota,		   // 1000 (MB) - 5000 images (monthly/onetime)
					cos   = datas.cost,            // 3.49 (onetime)
					name  = ( quo >= 1000 ? quo/1000 + ' GB' : quo + ' MB' ),
					pcs   = type === 'monthly' ? { monthly: mon, yearly: Math.round( ann / 12 * 100 ) / 100 } : cos,
					pcsd  = pcs; // used if discount is active

				// change pricing value only if discount in percentage is active
				// and if offer is a monthly and not a onetime
				if ( promo.is_active && promo.coupon_type === 'percentage' && type === 'monthly' ) {
					var percent = ( 100 - promo.coupon_value ) / 100;
					pcs = type === 'monthly' ? { monthly: mon * percent, yearly: Math.round( ( ann * percent ) / 12 * 100 ) / 100 } : cos * percent;
				}

				if ( typeof classes !== 'undefined' ) {
					$offer.addClass( 'imagify-' + type + '-' + lab + classes);
				}

				// name
				$offer.find('.imagify-offer-size').text( name );

				// main prices (pcs can be an object or a string)
				$offer.find('.imagify-number-block').html( imagify_get_html_price( pcs, 'monthly' ) );
				
				// discount prices
				if ( promo.is_active && promo.coupon_type === 'percentage' && type === 'monthly' ) {
					
					$offer.find('.imagify-price-block').prev( '.imagify-price-discount' ).remove();
					$offer.find('.imagify-price-block').before( imagify_get_html_discount_price( pcsd, 'monthly' ) );
				}

				// nb images
				$offer.find('.imagify-approx-nb').text( quo * 5 );

				if ( type === 'monthly' ) {
					// additionnal price
					$offer.find('.imagify-price-add-data').text( '$' + add );
				}

				// button data-offer attr
				var $datas_c = $offer.find('.imagify-payment-btn-select-plan').length ? $offer.find('.imagify-payment-btn-select-plan') : $offer,
					datas_content = ( type === 'monthly' )
								? 
								'{"' + lab + '":{"id":' + id + ',"name":"' + name + '","data":' + quo + ',"dataf":"' + name + '","imgs":' + ( quo * 5 ) + ',"prices":{"monthly":' + pcs.monthly + ',"yearly":' + pcs.yearly + ',"add":' + add + '}}}'
								:
								'{"ot' + lab + '":{"id":' + id + ',"name":"' + name + '","data":' + quo + ',"dataf":"' + name + '","imgs":' + ( quo * 5 ) + ',"price":' + pcs + '}}'
								;

				$datas_c.attr('data-offer', datas_content);

				return $offer;
			},
			imagify_populate_pay_btn = function() {
				var pl_datas = JSON.parse( $( '.imagify-offer-monthly' ).attr( 'data-offer' ) ),
					ot_datas = JSON.parse( $( '.imagify-offer-onetime' ).attr( 'data-offer' ) ),
					price    = 0,
					price_pl = 0,
					price_ot = 0;

				// calculate price_pl only if that offer is selected
				if ( $( '.imagify-offer-monthly' ).hasClass( 'imagify-offer-selected' ) ) {
					price_pl = $('#imagify-subscription-monthly').filter(':checked').length ? pl_datas[ Object.keys( pl_datas )[0] ].prices.monthly : pl_datas[ Object.keys( pl_datas )[0] ].prices.yearly * 12;
				}
				// calculate price_ot only if that offer is selected
				if ( $( '.imagify-offer-onetime' ).hasClass( 'imagify-offer-selected' ) ) {
					price_ot = ot_datas[ Object.keys( ot_datas )[0] ].price;
				}

				// calculate price
				price = parseFloat( price_ot + price_pl ).toFixed(2);

				// edit button price
				$( '.imagify-global-amount' ).text( price );

				if ( price == '0.00' || price === 0 ) {
					$( '#imagify-modal-checkout-btn' ).attr( 'disabled', 'disabled' ).addClass( 'imagify-button-disabled' );
				} else {
					$( '#imagify-modal-checkout-btn' ).removeAttr( 'disabled' ).removeClass( 'imagify-button-disabled' );
				}

			},
			imagify_get_pricing = function( $button ){
				var nonce = $button.data('nonce'),
					prices_rq_datas = {
						action: 'imagify_get_prices',
						imagifynonce: nonce
					},
					imgs_rq_datas = {
						action: 'imagify_get_images_counts',
						imagifynonce: nonce
					},
					prices_rq_discount = {
						action: 'imagify_get_discount',
						imagifynonce: nonce
					};

				$modal.find('.imagify-modal-loader').hide().show();

				/*
					TODO: change the way to waterfall requests
					Use setInterval + counter instead
				 */

				// get the true prices
				$.post( ajaxurl, prices_rq_datas, function( prices_response ) {

					if ( prices_response.success ) {

						// get the image estimates sizes
						$.post( ajaxurl, imgs_rq_datas, function( imgs_response ) {

							if ( imgs_response.success ) {

								// get the discount informations
								$.post( ajaxurl, prices_rq_discount, function( discount_response ) {

									if ( discount_response.success ) {
							
										var images_datas = imgs_response.data
											prices_datas = prices_response.data,
											promo_datas  = discount_response.data,
											monthlies    = prices_datas.monthlies,
											onetimes     = prices_datas.onetimes,
											mo_user_cons = Math.round( images_datas.average_month_size.raw / 1000000 ), // 1000000 in MB,
											ot_user_cons = Math.round( images_datas.total_library_size.raw / 1000000 ), // in MB,
											$mo_tpl      = $('#imagify-offer-monthly-template'),
											$ot_tpl      = $('#imagify-offer-onetime-template'),
											ot_clone     = $ot_tpl.html(),
											mo_clone     = $mo_tpl.html(),
											ot_html      = '',
											mo_html      = '',
											ot_suggested = false,
											mo_suggested = false,
											$estim_block = $( '.imagify-estimation-block' );

										// Refresh Analyzing block
										$estim_block.removeClass( 'imagify-analyzing' );
										$estim_block.find( '.average-month-size' ).text( images_datas.average_month_size.human );
										$estim_block.find( '.total-library-size' ).text( images_datas.total_library_size.human );

										// Switch offers title is < 25mb
										if ( mo_user_cons < 25 &&  ot_user_cons < 25 ) {
											$( '.imagify-pre-checkout-offers .imagify-modal-title' ).addClass( 'imagify-enough-free' );
											$('.imagify-offer-selected' ).removeClass( 'imagify-offer-selected' ).find( '.imagify-checkbox' ).removeAttr( 'checked' );
										} else {
											$( '.imagify-enough-free' ).removeClass( 'imagify-enough-free' );
											$('.imagify-offer-selected' ).addClass( 'imagify-offer-selected' ).find( '.imagify-checkbox' ).attr( 'checked', 'checked' );
										}

										// Don't create prices table if something went wrong during request
										if ( monthlies === null || onetimes === null ) {
											var $offers_block = $( '.imagify-pre-checkout-offers' );
											
											// hide main content
											$offers_block.hide().attr( 'aria-hidden', true );

											// show error message
											$offers_block.closest('.imagify-modal-views').find('.imagify-popin-message').remove();
											$offers_block.after('<div class="imagify-popin-message imagify-error"><p>' + imagifyAdmin.labels.errorPriceAPI + '</p></div>');

											// show the modal content
											$modal.find('.imagify-modal-loader').fadeOut(300);

											return;
										}

										// Autofill coupon code & Show banner if discount is active
										window.imagify_discount_datas = promo_datas;

										if ( promo_datas.is_active ) {
											var $banners = $( '.imagify-modal-promotion' ),
												date_end = promo_datas.date_end.split('T')[0],
												promo    = promo_datas.coupon_value;
												discount = promo_datas.coupon_type === 'percentage' ? promo + '%' : '$' + promo;

											// fill coupon code
											$( '#imagify-coupon-code' ).val( promo_datas.label ).attr( 'readonly', true );

											// show banners
											$banners.addClass( 'active' ).attr( 'aria-hidden', 'false' );

											// populate banners
											$banners.find( '.imagify-promotion-number' ).text( discount );
											$banners.find( '.imagify-promotion-date' ).text( date_end );
											

											// auto validate coupon
											imagify_check_coupon();
										}

										/**
										 * Below lines will build Plan and Onetime offers lists
										 * It will also pre-select a Plan and Onetime in both of views: pre-checkout and pricing tables
										 */

										// Now, do the MONTHLIES Markup
										$.each( monthlies, function( index, value ) {
											
											// if it's free, don't show it
											if ( value.label === 'free' ) {
												return true; // continue-like (but $.each is not a loop… so)
											}

											var $tpl= $( mo_clone ).clone();

											// if offer is too big (far) than estimated needs, don't show the offer
											if ( mo_suggested !== false && ( index - mo_suggested ) > 2 ) {
												return true;
											}

											// parent classes
											classes = '';
											if ( ( mo_user_cons < value.quota && mo_suggested === false ) ) {
												classes = ' imagify-offer-selected';
												mo_suggested = index;

												// add this offer as pre-selected item in pre-checkout view
												var $offer = $('.imagify-pre-checkout-offers').find('.imagify-offer-monthly');

												// populate the Pre-checkout view depending on user_cons
												imagify_populate_offer( $offer, value, 'monthly' );
											}

											// populate each offer
											$tpl = imagify_populate_offer( $tpl, value, 'monthly', classes );

											// complete Monthlies HTML
											mo_html += $tpl[0].outerHTML;

										});

										// Deal with the case of too much small offers (before recommanded one)
										var prev_offers = $( mo_html ).filter('.imagify-offer-selected').prevAll();

										// if we have more than 1 previous offer
										if ( prev_offers.length > 1 ) {
											var nb_to_remove  = prev_offers.length - 1,
												$total_offers = $( mo_html );

											// remove too far previous offer
											for ( i = 0; i < nb_to_remove; i++ ) {
												delete $total_offers[ i ]
											}

											// rebuild mo_html with removed items
											mo_html = '';
											for ( j = 0; j < $total_offers.length; j++) {
												mo_html += $( '<div/>' ).append($total_offers[j]).html();
											}
										}

										// Do the ONETIMES Markup
										$.each( onetimes, function( index, value ) {
											var $tpl   = $( ot_clone ).clone(),
												$offer = $( '.imagify-pre-checkout-offers' ).find( '.imagify-offer-onetime' );

											// parent classes
											classes = '';
											if ( ( ot_user_cons < value.quota && ot_suggested === false ) ) {
												classes = ' imagify-offer-selected';
												ot_suggested = true;

												// populate the Pre-checkout view depending on user_cons
												imagify_populate_offer( $offer, value, 'onetime' );
											}
											
											// if too big, populate with the biggest offer available
											// TODO: create custom offers at this point
											if ( index === onetimes.length-1 && ot_suggested === false ) {
												// populate the Pre-checkout view depending on user_cons
												var tvalue = onetimes[ onetimes.length - 1 ];
												imagify_populate_offer( $offer, tvalue, 'onetime' );
											}

											// populate each offer
											$tpl = imagify_populate_offer( $tpl, value, 'onetime', classes );

											// complete Onetimes HTML
											ot_html += $tpl[0].outerHTML;
										});

										// Fill pricing tables
										if ( $mo_tpl.parent().find( '.imagify-offer-line' ) ) {
											$mo_tpl.parent().find( '.imagify-offer-line' ).remove();
										}
										$mo_tpl.before( mo_html );

										if ( $ot_tpl.parent().find( '.imagify-offer-line' ) ) {
											$ot_tpl.parent().find( '.imagify-offer-line' ).remove();
										}
										$ot_tpl.before( ot_html );

										// Show the content 
										$modal.find( '.imagify-modal-loader' ).fadeOut( 300 );

									} else {
										// TODO: replace modal content by any information
										// an error occurred
									}

								}); // third AJAX request to get discount information

							} else {
								// TODO: replace modal content by any information
								// an error occurred
							}

						}); // second AJAX request for image estimation sizes

					} else {
						// TODO: replace modal content by any information
						// an error occurred
					}

					// populate Pay button
					imagify_populate_pay_btn();
				}); // end $.post
			},
			imagify_check_check = function( $checkbox ) {
				var sel_class = 'imagify-offer-selected';

				$checkbox.each(function(){
					if ( $(this).is(':checked') ) {
						$(this).closest('.imagify-offer-line').addClass( sel_class );
					}
					else {
						$(this).closest('.imagify-offer-line').removeClass( sel_class );	
					}
				});

				// Update pay button
				imagify_populate_pay_btn();
			},
			imagify_check_radio = function( $radio ) {
				var year_class = 'imagify-year-selected',
					month_class = 'imagify-month-selected';

				$radio.each(function(){
					// to handle modal pricing & modal suggestion
					var $_this = $(this);
					var $parent = '';

					if ( $_this.parent('.imagify-cart-list-switcher').length ) {
						$parent = $_this.closest('.imagify-cart');
					} else if ( $_this.parent('.imagify-small-options').length ) {
						$parent = $_this.parent('.imagify-small-options').next('.imagify-pricing-table');
					} else {
					  $parent = $_this.closest('.imagify-offer-line');
					}

					var $to_switch = $parent.find('.imagify-switch-my');

					if ( $_this.val() === 'yearly' ) {
						$parent.addClass( year_class ).removeClass( month_class );
						$to_switch.find('.imagify-monthly').attr('aria-hidden', 'true');
						$to_switch.find('.imagify-yearly').attr('aria-hidden', 'false');
					} else {
						$parent.addClass( month_class ).removeClass( year_class );
						$to_switch.find('.imagify-monthly').attr('aria-hidden', 'false');
						$to_switch.find('.imagify-yearly').attr('aria-hidden', 'true');
					}
				});

				// update Pay button information
				imagify_populate_pay_btn();

				return $radio;
			},
			imagify_check_coupon = function() {
				var code = $( '#imagify-coupon-code' ).val();

				if ( code !== '' ) {
					var $cptext  = $( '.imagify-coupon-text' ),
						$label   = $cptext.find( 'label' ),
						$section = $( '.imagify-coupon-section' ),
						nonce    = $( '#imagify-get-pricing-modal' ).data( 'nonce' );

					$cptext.addClass( 'checking' );

					// get the true prices
					$.post( ajaxurl, {action: 'imagify_check_coupon', coupon: code, imagifynonce: nonce }, function( response ) {

						$cptext.removeClass( 'checking' );

						// error during the request
						if ( response.success === 'false' ) {

							$label.text( imagifyAdmin.labels.errorCouponAPI );
							$section.removeClass( 'validated' ).addClass( 'invalid' );

						} else {
							if ( response.data.success ) {
								var coupon_value  = response.data.coupon_type === 'percentage' ? response.data.value + '%' : '$' + response.data.value;
								$section.removeClass( 'invalid' ).addClass( 'validated' );
								$label.html( imagifyAdmin.labels.successCouponAPI );
								$label.find( '.imagify-coupon-offer' ).text( coupon_value );
								$label.find( '.imagify-coupon-word' ).text( code );
							} else {
								$section.removeClass( 'validated' ).addClass( 'invalid' );
								$label.text( response.data.detail );
							}

						}
					});

				}
			},
			$checkboxes = $('.imagify-offer-line').find('.imagify-checkbox'),
			$radios		= $('.imagify-payment-modal').find('.imagify-radio-line').find('input');
		
		// check all boxes on load
		imagify_check_check( $checkboxes );
		imagify_check_radio( $radios.filter(':checked') );

		// check coupon onload
		imagify_check_coupon();

		var populate_btn_price = setInterval( function() {
			imagify_populate_pay_btn();
		}, 1000 );

		// check the changed box
		$checkboxes.on('change.imagify', function(){
			imagify_check_check( $(this) );
		});

		// check the radio box
		$radios.on('change.imagify', function(){
			imagify_check_radio( $(this) );
		});

		/**
		 * Get pricings on modal opening
		 * Build the pricing tables inside modal
		 */
		$('#imagify-get-pricing-modal').on('click.imagify-ajax', function(){
			imagify_get_pricing( $(this) );
		});

		/**
		 * 1) Modal Payment change/select plan
		 * 2) Checkout selection(s)
		 * 3) Payment process
		 */
		
		// plans selection view & payment process view hidden by default
		var $plans_view		= $('#imagify-plans-selection-view'),
			$payment_view	= $('#imagify-payment-process-view'),
			$pre_view		= $('#imagify-pre-checkout-view'),
			$success_view	= $('#imagify-success-view'),
			speedFadeIn		= 300,
			$another_btn	= $('.imagify-choose-another-plan'),
			imagify_iframe_set_src = function( params ) {
				/*
					params = {
						'monthly': {
							'lite': {
								name: 'something',
								id: ''
							}
						},
						'onetime': {
							'recommended': {
								name: 'Recommend',
								id: ''
							}
						},
						'period': 'monthly'|'yearly'
					}
				*/
				var $iframe		= $('#imagify-payment-iframe'),
					iframe_src  = $iframe.attr('src'),
					pay_src     = $iframe.data('src'),
					monthly_id	= 0,
					onetime_id	= 0;

				// if we get new informations about products
				if ( typeof params === 'object' ) {
					if ( params.monthly ) {
						monthly_id = params.monthly[ Object.keys( params.monthly )[0] ].id;
					}
					if ( params.onetime ) {
						onetime_id = params.onetime[ Object.keys( params.onetime )[0] ].id;
						// if onetime ID === 999 it's a custom plan, send datas instead
						onetime_id = ( onetime_id == 999 ? params.onetime[ Object.keys( params.onetime )[0] ].data : onetime_id );
					}
					
					if ( params.period ) {
						var key        = imagify_get_api_key(),
							rt_onetime = onetime_id,
							rt_yearly  = params.period === 'yearly' ? monthly_id : 0,
							rt_monthly = params.period === 'monthly' ? monthly_id : 0,
							coupon     = $('#imagify-coupon-code').val(),
							rt_coupon  = coupon === '' ? 'none' : coupon,
							// not used but…
							amount     = parseFloat( $( '.imagify-global-amount' ).text() ).toFixed(2);

						// compose route
						// pay_src + :ontimeplan(0)/:monthlyplan(0)/:yearlyplan(0)/:coupon(none)/
						pay_src = pay_src + rt_onetime + '/' + rt_monthly + '/' + rt_yearly + '/' + rt_coupon + '/';

						// iFrame sort of cache fix
						$iframeClone = $iframe.clone();
						$iframe.remove();

						$iframeClone.attr( 'src', pay_src );
						$payment_view.html( $iframeClone );

					} else {
						imagify.info( 'No period defined' );
					}
				}
				// if we only change monthly/yearly payment mode
				else if ( typeof params === 'string' && iframe_src !== '' ) {
					tofind = params === 'monthly' ? 'yearly' : 'monthly';
					iframe_src = iframe_src.replace( tofind, params );
					$iframe.attr( 'src', iframe_src );
				}
			},
			imagify_get_period = function() {
				return ( $('.imagify-offer-monthly').hasClass('imagify-month-selected') ? 'monthly' : 'yearly' );
			},
			imagify_get_api_key = function(){
				return $('#imagify-payment-iframe').data('imagify-api');
			};

		/**
		 * Get validation for Coupon Code
		 * - On blur
		 * - On Enter or Spacebar press
		 * - On click OK button
		 *
		 * @since  1.6.3 Only if field hasn't readonly attribute (discount auto-applied)
		 */
		$( '#imagify-coupon-code' ).on( 'blur.imagify', function() {
			if ( ! $(this).attr('readonly') ) {
				imagify_check_coupon();
			}
		} ).on( 'keydown.imagify', function( e ) {
			if ( ! $(this).attr('readonly') ) {
				if ( e.keyCode === 13 || e.keyCode === 32 ) {
					imagify_check_coupon();
					return false;
				}
				if ( $(this).val().length >= 3 ) {
					$(this).closest( '.imagify-coupon-input' ).addClass( 'imagify-canbe-validate' );
				} else {
					$(this).closest( '.imagify-coupon-input' ).removeClass( 'imagify-canbe-validate' );
				}
			}
		} );

		$( '#imagify-coupon-validate' ).on( 'click.imagify', function() {
			imagify_check_coupon();
			$(this).closest( '.imagify-canbe-validate' ).removeClass( 'imagify-canbe-validate' );
		} );
		/**
		 * View game, step by step
		 */
		// init views
		//$pre_view.hide();
		$plans_view.hide();
		$payment_view.hide();
		$success_view.hide();

		// 1) when you decide to choose another plan
		
		// 1.a) on click, display choices
		$another_btn.on('click.imagify', function(){
			var $_this	= $(this),
				type	= $_this.data('imagify-choose');

			// hide current
			$_this.closest('.imagify-modal-views').hide().attr('aria-hidden', 'true');
			// hide the checkout view (click could be a triggered action ;p)
			$payment_view.hide().attr('aria-hidden', 'true');

			// show choices
			$plans_view.fadeIn(speedFadeIn).attr('aria-hidden', 'false');
			
			// trigger on tab
			var temp = setInterval(function(){
					var tab = type == 'plan' ? 'monthly' : 'onetime';
					$plans_view.find('a[href="#imagify-pricing-tab-' + tab + '"]').trigger('click.imagify');
					clearInterval( temp );
					temp = null;
				}, 60 );

			return false;
		});

		// 1.b) on click in a choice, return to pre-checkout step
		$modal.on('click.imagify', '.imagify-payment-btn-select-plan', function(){

			var $_this		= $(this),
				$offer_line	= $_this.closest('.imagify-offer-line'),
				datas		= $_this.data('offer'),
				datas_str	= $_this.attr('data-offer'),
				is_onetime	= ( $_this.closest('.imagify-tab-content').attr('id') === 'imagify-pricing-tab-monthly' ? false : true ),
				$target_line	= ( is_onetime ? $pre_view.find('.imagify-offer-onetime') : $pre_view.find('.imagify-offer-monthly') ),
				period		= ( is_onetime ? null : ( ( $_this.closest('.imagify-pricing-table').hasClass('imagify-month-selected') ) ? 'monthly' : 'yearly') ),
				price		= ( is_onetime ? imagify_get_html_price( datas[ Object.keys( datas )[0] ].price ) : imagify_get_html_price ( datas[ Object.keys( datas )[0] ].prices, period ) ),
				discount    = $offer_line.find('.imagify-price-discount').html(),
				imgs		= $offer_line.find('.imagify-approx-nb').text(),
				offer_size	= $offer_line.find('.imagify-offer-size').text(),
				monthly_txt = ( ! is_onetime ? '<span class="imagify-price-by">' + $offer_line.find('.imagify-price-by').text() + '</span>' : '' );
			
			// change views to go back pre-checkout
			$plans_view.hide().attr('aria-hidden', 'true');
			$pre_view.fadeIn(speedFadeIn).attr('aria-hidden', 'false');

			// change price (+ "/month" if found in monthly plans)
			$target_line.find('.imagify-number-block').html( price + monthly_txt )

			// change discount
			$target_line.find('.imagify-price-discount').html( discount );

			// change approx images nb
			$target_line.find('.imagify-approx-nb').text( imgs );

			// change offer size name
			$target_line.find('.imagify-offer-size').text( offer_size );

			// change datas (json)
			$target_line.attr('data-offer', datas_str );

			if ( ! is_onetime ) {
				$target_line.find('.imagify-price-add-data').text( $offer_line.find('.imagify-price-add-data').text() );

				// trigger period selected from offer selection view to pre-checkout view
				if ( period === 'monthly' ) {
					$target_line.find('#imagify-subscription-monthly').trigger('click.imagify');
				}
				else {
					$target_line.find('#imagify-subscription-yearly').trigger('click.imagify');
				}
				$target_line.find('.imagify-inline-options').find('input:radio:checked').trigger('change.imagify');
			}

			// update price information in button
			imagify_populate_pay_btn();

			return false;
		});


		// 2) when you checkout
		$( '#imagify-modal-checkout-btn' ).on( 'click.imagify', function() {

			// do nothing if button disabled
			if ( $( this ).hasClass( 'imagify-button-disabled' ) ) {
				return;
			} 

			var $monthly_offer = $( '.imagify-offer-monthly' ),
				$onetime_offer = $( '.imagify-offer-onetime' ),
				checkout_datas = {},
				period_choosen = $monthly_offer.hasClass( 'imagify-year-selected' ) ? 'year' : 'month';

			// if user choose a monthly plan
			if ( $monthly_offer.hasClass( 'imagify-offer-selected' ) ) {
				
				checkout_datas.monthly = JSON.parse( $monthly_offer.attr( 'data-offer' ) );
				$( '.imagify-cart-list-my-choice' ).show();

				// price calculation
				prices = checkout_datas.monthly[Object.keys(checkout_datas.monthly)[0]].prices;
				save_price = Math.round( ( ( prices.monthly - prices.yearly ) * 12 ) * 100 ) / 100;
				$( '.imagify-nb-save-per-year' ).text( '$' + save_price );

			} else {
				$( '.imagify-cart-list-my-choice' ).hide();
			}

			// if user choose a one time plan
			if ( $onetime_offer.hasClass('imagify-offer-selected') ) {
				checkout_datas.onetime = JSON.parse( $onetime_offer.attr('data-offer') );	
			}

			// change views to go to checkout/payment view
			$pre_view.hide().attr( 'aria-hidden', 'true' );
			$payment_view.fadeIn( speedFadeIn ).attr( 'aria-hidden', 'false' )
						 .closest( '.imagify-modal-content' ).addClass( 'imagify-iframe-viewing' );

			checkout_datas.period = imagify_get_period();

			imagify_iframe_set_src( checkout_datas );
			return false;
		});
		
		/**
		 * Go back to previous step ("Choose Another Plan" links)
		 */
		$('.imagify-back-to-plans').on('click.imagify', function(){
			var $_this 		= $(this),
				is_onetime 	= $_this.closest('.imagify-cart-item').hasClass('imagify-cart-item-onetime');

			if ( is_onetime ) {
				$('.imagify-offer-onetime').find('.imagify-choose-another-plan').trigger('click.imagify');
			} else {
				$('.imagify-offer-monthly').find('.imagify-choose-another-plan').trigger('click.imagify');
			}

			return false;
		});

		/**
		 * Public function triggered by payement iframe
		 */
		//$pre_view.hide();
		//$plans_view.hide();
		//$payment_view.hide();
		//$success_view.hide();
		var paymentClose = function() {
				$( '.imagify-iframe-viewing .close-btn' ).trigger( 'click.imagify' );
				$( '.imagify-iframe-viewing' ).removeClass( 'imagify-iframe-viewing' );
				return false;
			},
			paymentBack = function() {
				$( '.imagify-iframe-viewing' ).removeClass( 'imagify-iframe-viewing' );
				$payment_view.hide();
				$pre_view.fadeIn(200);
				return false;
			},
			paymentSuccess = function() {
				$( '.imagify-iframe-viewing' ).removeClass( 'imagify-iframe-viewing' );
				$payment_view.hide();
				$success_view.closest( '.imagify-modal-content' ).addClass( 'imagify-success-viewing' );
				$success_view.closest( '.imagify-modal' ).attr( 'aria-labelledby', 'imagify-success-view' );
				$success_view.fadeIn(200);
				return false;
			},
			checkPluginMessage = function(event) {
				var origin = event.origin || event.originalEvent.origin;

				if ( origin === 'https://app.imagify.io' || origin === 'http://dapp.imagify.io' ) {
					switch (event.data) {
						case 'cancel': paymentClose(); break;
						case 'back': paymentBack(); break;
						case 'success': paymentSuccess(); break;
					}
				}
			};

		// message/communication API
		window.addEventListener( 'message', checkPluginMessage, true );

	}

	/**
	 * Tabs
	 * 
	 * @Markup:
	 * ul.imagify-tabs
	 * 		li.imagify-tab.imagify-current
	 * 			a[href="#target"]
	 * div.imagify-tabs-contents
	 * 		div.imagify-tab-content#target
	 */
	if ( $('.imagify-tabs').length ) {

		var $tabs = $('.imagify-tab');

		$tabs.on('click.imagify', function(){
			
			var $_this = $(this);

			if ( ! $_this.hasClass('imagify-current') ) {
				var target = $_this.find('a').attr('href') || '#' + $_this.find('a').attr('aria-controls'),
					curr_class = 'imagify-current';

				// show right tab content
				$_this.closest('.imagify-tabs').next('.imagify-tabs-contents').find('.imagify-tab-content').hide().attr('aria-hidden', 'true');
				$( target ).fadeIn(275).attr('aria-hidden', 'false');

				// change active tabs
				$_this.closest('.imagify-tabs').find('.imagify-tab').removeClass( curr_class ).attr('aria-selected', 'false');
				$_this.addClass( curr_class ).attr('aria-selected', 'true');
			}

			return false;
		});

	}
});