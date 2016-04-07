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
			title: imagify.signupTitle,
			text: imagify.signupText,
			confirmButtonText: imagify.signupConfirmButtonText,
			type: "input",
			closeOnConfirm: false,
			allowOutsideClick: true,
			showLoaderOnConfirm: true,
			customClass: "imagify-sweet-alert imagify-sweet-alert-signup"
		},
		function(inputValue){
			if ($.trim(inputValue) == "" || ! inputValue) {
				swal.showInputError(imagify.signupErrorEmptyEmail);
				return false;
			} 
			
			$.get(ajaxurl + concat + "action=imagify_signup&email=" +inputValue + "&imagifysignupnonce="+ $('#imagifysignupnonce').val())
			.done(function(response){
				if( !response.success ) {
					swal.showInputError(response.data);
				} else {
					swal({
						title:imagify.signupSuccessTitle,
						text: imagify.signupSuccessText,
						type: "success",
						customClass: "imagify-sweet-alert"
					});
				}
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
			title: imagify.saveApiKeyTitle,
			text: imagify.saveApiKeyText,
			confirmButtonText: imagify.saveApiKeyConfirmButtonText,
			type: "input",
			closeOnConfirm: false,
			allowOutsideClick: true,
			showLoaderOnConfirm: true,
			customClass: "imagify-sweet-alert imagify-sweet-alert-signup"
		},
		function(inputValue){
			if ($.trim(inputValue) == "" || ! inputValue) {
				swal.showInputError(imagify.signupErrorEmptyEmail);
				return false;
			} 
			
			$.get(ajaxurl + concat + "action=imagify_check_api_key_validity&api_key=" +inputValue + "&imagifycheckapikeynonce="+ $('#imagifycheckapikeynonce').val())
			.done(function(response){
				if( !response.success ) {
					swal.showInputError( response.data );
				} else {
					swal({
						title: imagify.ApiKeyCheckSuccessTitle,
						text: imagify.ApiKeyCheckSuccessText,
						type: "success",
						customClass: "imagify-sweet-alert"
					});
				}
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
	$('.imagify-modal').attr('aria-hidden', 'true');

	// on click on modal trigger
	$('.imagify-modal-trigger').on('click.imagify', function(){

		imagify_open_modal( $(this) );

		return false;
	});

	// on click on close button
	$(document).on('click.imagify', '.imagify-modal .close-btn', function(){
		$(this).closest('.imagify-modal').fadeOut(400).attr('aria-hidden', 'true').removeClass('modal-is-open');
		$('body').removeClass('imagify-modal-is-open');
	})
	.on('blur.imagify', '.imagify-modal .close-btn', function(){
		var $modal = $(this).closest('.imagify-modal');
		if ( $modal.attr('aria-hidden') === 'false' ) {
			$modal.attr('tabindex', '0').focus().removeAttr('tabindex');
		}
	});

	// `Esc` key binding
	$(window).on('keydown', function(e){
		if ( e.keyCode == 27 && $('.imagify-modal.modal-is-open').length > 0 ) {

			e.preventDefault();
			
			// trigger the event
			$('.imagify-modal.modal-is-open').find('.close-btn').trigger('click.imagify');

			return false;
		}
	});
	
	var busy = false,
		xhr	 = false;
		
	$('#wp-admin-bar-imagify').hover( function() {
		if ( true === busy ) {
			xhr.abort();
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
	 * @since  1.5
	 * @author  Geoffrey
	 */
	
	if ( $('.imagify-offer-line').length ) {

		var imagify_check_check = function( $checkbox ) {
				var sel_class = 'imagify-offer-selected';

				$checkbox.each(function(){
					if ( $(this).is(':checked') ) {
						$(this).closest('.imagify-offer-line').addClass( sel_class );
					}
					else {
						$(this).closest('.imagify-offer-line').removeClass( sel_class );	
					}
				});
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
				return $radio;
			},
			$checkboxes = $('.imagify-offer-line').find('.imagify-checkbox'),
			$radios		= $('.imagify-payment-modal').find('.imagify-radio-line').find('input');
		
		// check all boxes on load
		imagify_check_check( $checkboxes );
		imagify_check_radio( $radios.filter(':checked') );

		// check the changed box
		$checkboxes.on('change.imagify', function(){
			imagify_check_check( $(this) );
		});

		// check the radio box
		$radios.on('change.imagify', function(){
			imagify_check_radio( $(this) );
		});
			
	}

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
		speedFadeIn		= 300;

	$plans_view.hide();
	$payment_view.hide();
	$success_view.hide();
	//$pre_view.hide();

	if ( $('.imagify-payment-btn-select-plan').length ) {

		var $selection_btn	= $('.imagify-payment-btn-select-plan'),
			$another_btn	= $('.imagify-choose-another-plan'),
			imagify_get_html_price = function( content, period ) {
				if ( ! period ) period = null;
				output = '';

				if ( typeof content === 'object' ) {
					var monthly = content.monthly + "",
						yearly = content.yearly + "",
						m = monthly.split('.'),
						y = yearly.split('.');

					output += '<span class="imagify-switch-my"><span aria-hidden="' + ( period === 'monthly' ? 'false' : 'true' ) + '" class="imagify-monthly"><span class="imagify-price-big">' + m[0] + '</span> <span class="imagify-price-mini">.' + ( m[1].length === 1 ? m[1] + '0' : m[1] ) + '</span></span> <span aria-hidden="' + ( period === 'yearly' ? 'false' : 'true' ) + '" class="imagify-yearly"><span class="imagify-price-big">' + y[0] + '</span> <span class="imagify-price-mini">.' + ( y[1].length === 1 ? y[1] + '0' : y[1] ) + '</span></span></span>';
				} else {
					var content = content + "", // be sure content is a string
						v = content.split('.');

					output += '<span class="imagify-price-big">' + v[0] + '</span> <span class="imagify-price-mini">.' + ( v[1].length === 1 ? v[1] + '0' : v[1] ) + '</span>';
				}

				return output;
			},
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
					iframe_src	= $iframe.attr('src'),
					monthly_id	= 0,
					onetime_id	= 0;
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
						iframe_src = iframe_src.split('?')[0] + '?monthly=' + monthly_id + '&onetime=' + onetime_id + '&api=' + imagify_get_api_key() + '&period=' + params.period;

						$iframe.attr( 'src', iframe_src );

					} else {
						imagify.info('No period defined');
					}
				} else if ( typeof params === 'string' ) {
					iframe_src = iframe_src.split('&period=');
					$iframe.attr( 'src', iframe_src[0] + '&period=' + params );
				}
			},
			imagify_get_period = function() {
				return ( $('.imagify-cart').hasClass('imagify-month-selected') ? 'monthly' : 'yearly' );
			},
			imagify_get_api_key = function(){
				return $('#imagify-payment-iframe').data('imagify-api');
			};

		// 1) when you decide to choose another plan
		
		// 1.a) on click, display choices
		$another_btn.on('click.imagify', function(){
			var $_this	= $(this),
				type	= $_this.data('imagify-choose');

			// hide current
			$_this.closest('.imagify-modal-views').hide().attr('aria-hidden', 'true');

			// show choices
			$plans_view.fadeIn(speedFadeIn).attr('aria-hidden', 'false');
			
			if ( type === 'onetime' ) {
				var temp = setInterval(function(){
					$plans_view.find('a[href="#imagify-pricing-tab-onetime"]').trigger('click.imagify');
					clearInterval( temp );
					temp = null;
				}, 60 );
			}

			return false;
		});

		// 1.b) on click in a choice, return to pre-checkout step
		$selection_btn.on('click.imagify', function(){

			var $_this		= $(this),
				$offer_line	= $_this.closest('.imagify-offer-line'),
				datas		= $_this.data('offer'),
				datas_str	= $_this.attr('data-offer'),
				is_onetime	= ( $_this.closest('.imagify-tab-content').attr('id') === 'imagify-pricing-tab-monthly' ? false : true ),
				$target_line	= ( is_onetime ? $pre_view.find('.imagify-offer-onetime') : $pre_view.find('.imagify-offer-monthly') ),
				period		= ( is_onetime ? null : ( ( $_this.closest('.imagify-pricing-table').hasClass('imagify-month-selected') ) ? 'monthly' : 'yearly') ),
				price		= ( is_onetime ? imagify_get_html_price( datas[ Object.keys( datas )[0] ].price ) : imagify_get_html_price ( datas[ Object.keys( datas )[0] ].prices, period ) ),
				imgs		= $offer_line.find('.imagify-approx-nb').text(),
				offer_size	= $offer_line.find('.imagify-offer-size').text(),
				monthly_txt = ( ! is_onetime ? '<span class="imagify-price-by">' + $offer_line.find('.imagify-price-by').text() + '</span>' : '' );
			
			// change views to go back pre-checkout
			$plans_view.hide().attr('aria-hidden', 'true');
			$pre_view.fadeIn(speedFadeIn).attr('aria-hidden', 'false');

			// change price (+ "/month" if found in monthly plans)
			$target_line.find('.imagify-number-block').html( price + monthly_txt )

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

			return false;
		});


		// 2) when you checkout
		$('#imagify-modal-checkout-btn').on('click.imagify', function(){

			var $monthly_offer = $('.imagify-offer-monthly'),
				$onetime_offer = $('.imagify-offer-onetime'),
				checkout_datas = {},
				period_choosen = ( $monthly_offer.hasClass('imagify-year-selected') ? 'year' : 'month' );

			// if user choose a monthly plan
			if ( $monthly_offer.hasClass('imagify-offer-selected') ) {
				
				checkout_datas.monthly = JSON.parse( $monthly_offer.attr('data-offer') );
				$('.imagify-cart-list-my-choice').show();

				// price calculation
				prices = checkout_datas.monthly[Object.keys(checkout_datas.monthly)[0]].prices;
				save_price = Math.round( ( ( prices.monthly - prices.yearly ) * 12 ) * 100 ) / 100;
				$('.imagify-nb-save-per-year').text( '$' + save_price );

			} else {
				$('.imagify-cart-list-my-choice').hide();
			}

			// if user choose a one time plan
			if ( $onetime_offer.hasClass('imagify-offer-selected') ) {
				checkout_datas.onetime = JSON.parse( $onetime_offer.attr('data-offer') );	
			}

			// change views to go to checkout/payment view
			$pre_view.hide().attr('aria-hidden', 'true');
			$payment_view.fadeIn(speedFadeIn).attr('aria-hidden', 'false');

			// hide "Cancel you removing" blocks
			$('.imagify-cart-emptied-item').hide().attr('aria-hidden', 'true');

			// Step 2 active
			$('#imagify-pricing-step-2').addClass('active');
			
			// Car item emptyfied & hidden
			$payment_view.find('.imagify-cart-item').hide()
													.attr('data-offer', '');

			// Then completion of those items
			$.each( checkout_datas, function( index, value ) {

				var $line = $payment_view.find('.imagify-cart-item-' + index ),
					offer = value[Object.keys(value)[0]],
					$cart = $('.imagify-cart');
				
				$line.show();

				// product datas
				$line.attr('data-offer', JSON.stringify( value ) );

				// product name
				$line.find('.imagify-the-product-name').text( offer.name );

				// datas provide
				$line.find('.imagify-cart-offer-data').text( offer.dataf );

				// prices
				if ( index === 'onetime') {
					$line.find('.imagify-number-block').html( imagify_get_html_price( offer.price ) );
				} else {
					$line.find('.imagify-number-block').find('.imagify-switch-my').html( imagify_get_html_price( offer.prices, period_choosen + 'ly' ) );
				}

				// right class on Cart List depending on period selected
				$cart.removeClass( 'imagify-month-selected imagify-year-selected' )
								  .addClass( 'imagify-' + period_choosen + '-selected' );

				// trigger period choosen
				if ( period_choosen === 'month' ) {
					$cart.find('#imagify-checkout-monthly').trigger('click.imagify');
				} else {
					$cart.find('#imagify-checkout-yearly').trigger('click.imagify');
				}
				$cart.find('.imagify-inline-options').find('input:radio:checked').trigger('change.imagify');

			});

			checkout_datas.period = imagify_get_period();

			imagify_iframe_set_src( checkout_datas );
			return false;
		});


		// Removing an item
		$('.imagify-remove-from-cart').on('click.imagify', function(){
			var $_this		= $(this),
				$line		= $_this.closest('.imagify-cart-item'),
				is_monthly	= $line.hasClass('imagify-cart-item-monthly'),
				$other_line = ( is_monthly ? $('.imagify-cart-item-onetime') : $('.imagify-cart-item-monthly') ),
				is_empty	= $other_line.hasClass('imagify-temporary-removed'),
				offer_datas	= $line.attr('data-offer'), // string
				still_datas	= ( is_empty ? null : $other_line.data('offer') ); // object

			$line.hide().attr('aria-hidden', 'true').attr('data-offer', '').addClass('imagify-temporary-removed');
			$line.next('.imagify-cart-emptied-item').fadeIn(300).attr('aria-hidden', 'false').attr('data-offer', offer_datas )
				 .find('.imagify-removed-name').html( $line.find('.imagify-cart-product-name').html() );

			if ( typeof imagify_iframe_set_src === 'function' ) {
				if ( still_datas !== null ) {
					var datas_to_send = {};
					datas_to_send[ ( is_monthly ? 'onetime' : 'monthly' )] = still_datas;
					datas_to_send.period = imagify_get_period();
					imagify_iframe_set_src( datas_to_send );
				}
				else {
					imagify.info('No offers selected');
				}
			} else {
				imagify.info('imagify_iframe_set_src seems to be not declared');
			}
			return false;
		});

		// cancel action
		$('.imagify-cancel-removing').on('click.imagify', function(){
			
			var $_this		= $(this),
				$msg_block	= $_this.closest('.imagify-cart-emptied-item'),
				$line		= $msg_block.prev('.imagify-cart-item'),
				is_monthly	= $line.hasClass('imagify-cart-item-monthly'),
				$other_line = ( is_monthly ? $('.imagify-cart-item-onetime') : $('.imagify-cart-item-monthly') ),
				other_empty	= $other_line.hasClass('imagify-temporary-removed'),
				offer_datas	= $msg_block.attr('data-offer'), // string
				still_datas	= ( other_empty ? null : $other_line.data('offer') ); // object


			$msg_block.hide().attr('aria-hidden', 'true').attr('data-offer', '')
					  .prev('.imagify-cart-item').fadeIn(300).attr('aria-hidden', 'true').attr('data-offer', offer_datas ).removeClass('imagify-temporary-removed');

			// if new_datas === {} that is because both offers been cancelled
			var new_datas = {};
			
			new_datas.monthly = ( is_monthly ? JSON.parse( offer_datas ) : still_datas );
			new_datas.onetime = ( is_monthly ? still_datas : JSON.parse( offer_datas ) );
			new_datas.period  = imagify_get_period();
			

			if ( typeof imagify_iframe_set_src === 'function' ) {
				imagify_iframe_set_src( new_datas );
			} else {
				imagify.info('imagify_iframe_set_src seems to be not declared…');
			}				

			return false;
		});

		// on Yearly/Monthly payment change on checkout...
		$('.imagify-cart-list-my-choice').find('input[type="radio"]').on('change.imagify', function(){
			imagify_iframe_set_src( $(this).val() );
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