// Imagify tabs ====================================================================================
(function ($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names
	$('.imagify-badge').addClass('imagify-badge-checked');
	$('.imagify-toggle-label').eq(0).css('color', '#c8ced5');
	$('.imagify-toggle-label').eq(1).css('color', '#3b3f4a');
	// Plan switcher.
	$('#imagify-toggle-plan').change(function() {
		var isChecked = $(this).is(':checked');
		$('.imagify-toggle-label').eq(0).css('color', isChecked ? '#c8ced5' : '#3b3f4a');
		$('.imagify-toggle-label').eq(1).css('color', isChecked ? '#3b3f4a' : '#c8ced5');
		$('.imagify-badge').toggleClass('imagify-badge-checked', isChecked);
		$('#imagify_all_plan_view').toggleClass('imagify-year-selected', isChecked).toggleClass('imagify-month-selected', ! isChecked);
		$('.imagify-arrow-container img').eq(0).toggle(! isChecked);
		$('.imagify-arrow-container img').eq(1).toggle(isChecked);
	});

})(jQuery, document, window);


// Imagify payment modal ===========================================================================
(function ($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	var imagifyModal = {};

	if (! $('#imagify-pricing-modal').length) {
		return;
	}

	imagifyModal = {
		$modal:       $('#imagify-pricing-modal'),
		// Plans selection view & payment process view hidden by default.
		$plansView:   $('#imagify-plans-selection-view'),
		$paymentView: $('#imagify-payment-process-view').hide(),
		$successView: $('#imagify-success-view').hide(),
		speedFadeIn:  300,

		getHtmlPrice: function (content, period) {
			var monthly, yearly, m, y, output;

			if (! period) {
				period = null;
			}

			if (typeof content !== 'object') {
				content += ''; // Be sure content is a string.
				content = content.split('.');
				content[1] = content[1].length === 1 ? content[1] + '0' : ('' + content[1]).substring(0, 2);

				output = '<span class="imagify-price-big">' + content[0] + '</span> ';
				output += '<span class="imagify-price-mini">.' + content[1] + '</span>';

				return output;
			}

			monthly = content.monthly + '';
			yearly = content.yearly + '';
			m = '0' === monthly ? ['0', '00'] : monthly.split('.');
			y = '0' === yearly ? ['0', '00'] : yearly.split('.');
			output = '<span class="imagify-switch-my">';
			/* eslint-disable indent */
			output += '<span aria-hidden="' + (period === 'monthly' ? 'false' : 'true') + '" class="imagify-monthly">';
			output += '<span class="imagify-price-big">' + m[0] + '</span> ';
			output += '<span class="imagify-price-mini">.' + (m[1].length === 1 ? m[1] + '0' : ('' + m[1]).substring(0, 2)) + '</span>';
			output += '</span> ';
			output += '<span aria-hidden="' + (period === 'yearly' ? 'false' : 'true') + '" class="imagify-yearly">';
			output += '<span class="imagify-price-big">' + y[0] + '</span> ';
			output += '<span class="imagify-price-mini">.' + (y[1].length === 1 ? y[1] + '0' : ('' + y[1]).substring(0, 2)) + '</span>';
			output += '</span>';
			/* eslint-enable indent */
			output += '</span>';

			return output;
		},

		getHtmlDiscountPrice: function (content, period) {
			var monthly, yearly,
				output = '';

			if (! period) {
				period = null;
			}

			if (typeof content === 'object') {
				monthly = content.monthly + '';
				yearly = content.yearly + '';

				output += '<span class="imagify-price-discount">';
				/* eslint-disable indent */
				output += '<span class="imagify-price-discount-dollar">$</span>';
				output += '<span class="imagify-switch-my">';
				output += '<span aria-hidden="' + (period === 'monthly' ? 'false' : 'true') + '" class="imagify-monthly">';
				output += '<span class="imagify-price-discount-number">' + monthly + '</span>';
				output += '</span>';
				output += '<span aria-hidden="' + (period === 'yearly' ? 'false' : 'true') + '" class="imagify-yearly">';
				output += '<span class="imagify-price-discount-number">' + yearly + '</span>';
				output += '</span>';
				output += '</span>';
				/* eslint-enable indent */
				output += '</span>';
			} else {
				content += ''; // Be sure content is a string.
				output += '<span class="imagify-price-discount">';
				/* eslint-disable indent */
				output += '<span class="imagify-price-discount-dollar">$</span>';
				output += '<span class="imagify-price-discount-number">' + content + '</span>';
				/* eslint-enable indent */
				output += '</span>';
			}

			return output;
		},

		/**
		 * @uses imagifyModal.getHtmlPrice()
		 * @uses imagifyModal.getHtmlDiscountPrice()
		 */
		populateOffer: function ($offer, datas, type, classes) {
			var promo = w.imagify_discount_datas,
				add = datas.additional_gb,   // 4 (monthly)
				ann = datas.annual_cost,     // 49.9 (monthly)
				id = datas.id,              // 3 (monthly/onetime)
				lab = datas.label,           // 'lite' (monthly/onetime)
				mon = datas.monthly_cost,    // 4.99 (monthly)
				quo = datas.quota,           // 1000 (MB) - 5000 images (monthly/onetime)
				cos = datas.cost,            // 3.49 (onetime)
				label = datas.label.replace(/_.*$/, ''),
				name = -1 === quo ? 'Unlimited' : (quo >= 1000 ? quo / 1000 + ' GB' : quo + ' MB'),
				pcs = 'monthly' === type ? {monthly: mon, yearly: Math.round(ann / 12 * 100) / 100} : cos,
				pcsd = pcs, // Used if discount is active.
				percent, $datas_c, datas_content, applies_to = [],
				offer_by = '',
				additional_data = '';

			applies_to = imagifyModal.getPromoAppliesTo(promo);


			// Change pricing value only if discount in percentage is active and if offer is a monthly and not a onetime.
			if (
				promo.is_active
				&& 'percentage' === promo.coupon_type
				&& 'monthly' === type
				&& (0 < mon)
				&& (applies_to.includes(lab) || 'all' === applies_to[0])
			) {
				percent = (100 - promo.coupon_value) / 100;
				pcs = 'monthly' === type ? {
					monthly: mon * percent,
					yearly:  Math.round((ann * percent) / 12 * 100) / 100
				} : cos * percent;
			}

			if (typeof classes !== 'undefined') {
				$offer.addClass('imagify-' + type + '-' + lab + classes);
				$offer.addClass('imagify-' + type + '-' + lab + classes);
			}

			// Label.
			$offer.find('.imagify-label-plans').text(label);

			// Name.
			$offer.find('.imagify-offer-size').text(name);

			// Main prices (pcs can be an object or a string).
			$offer.find('.imagify-number-block').html(imagifyModal.getHtmlPrice(pcs, 'monthly'));

			if ('Unlimited' === name) {
				offer_by = 'quota';
				$offer.addClass('imagify-best-value');
				additional_data = 'No additional cost';
			} else {
				offer_by = '/month';
				additional_data = '$' + add + ' per additional Gb';
			}

			$offer.find('.imagify-offer-by').text(offer_by);

			// discount prices
			$offer.find('.imagify-price-block').prev('.imagify-price-discount').remove();
			if (
				promo.is_active
				&& 'percentage' === promo.coupon_type
				&& 'monthly' === type
				&& (0 < mon)
				&& (applies_to.includes(lab) || 'all' === applies_to[0])
			) {
				$offer.find('.imagify-price-block').before(imagifyModal.getHtmlDiscountPrice(pcsd, 'monthly'));
			}
			// Nb images.
			$offer.find('.imagify-approx-nb').text(quo * 5);

			if ('monthly' === type) {
				// Additional price.
				$offer.find('.imagify-price-add-data').text(additional_data);
			}

			// Button data-offer attr.
			$datas_c = $offer.find('.imagify-payment-btn-select-plan').length ? $offer.find('.imagify-payment-btn-select-plan') : $offer;

			if ('monthly' === type) {
				datas_content = '{"' + lab + '":{"id":' + id + ',"name":"' + name + '","label":"' + lab + '","data":' + quo + ',"dataf":"' + name + '","imgs":' + (quo * 5) + ',"prices":{"monthly":' + pcs.monthly + ',"yearly":' + pcs.yearly + ',"add":' + add + '}}}';
			} else {
				datas_content = '{"ot' + lab + '":{"id":' + id + ',"name":"' + name + '","label":"' + lab + '","data":' + quo + ',"dataf":"' + name + '","imgs":' + (quo * 5) + ',"price":' + pcs + '}}';
			}

			$datas_c.attr('data-offer', datas_content);

			return $offer;
		},

		/**
		 * @uses imagifyModal.populateOffer()
		 */
		getPricing: function ($button) {
			var nonce = $button.data('nonce'),
				prices_rq_datas = {
					action:       'imagify_get_prices',
					imagifynonce: nonce
				},
				prices_rq_discount = {
					action:       'imagify_get_discount',
					imagifynonce: nonce
				};

			imagifyModal.$modal.find('.imagify-modal-loader').hide().show();
			imagifyModal.$modal.addClass('imagify-modal-loading');

			/**
			 * TODO: change the way to waterfall requests.
			 * Use setInterval + counter instead.
			 */

			// Get the true prices.
			$.post(ajaxurl, prices_rq_datas, function (prices_response) {

				if (! prices_response.success) {
					// TODO: replace modal content by any information.
					// An error occurred.

					return;
				}

				// Get the discount informations.
				$.post(ajaxurl, prices_rq_discount, function (discount_response) {
					var prices_datas, promo_datas,
						offers,
						mo_html = '',
						$mo_tpl,
						mo_clone,
						$estim_block, $offers_block,
						$banners, date_end, plan_names, promo, discount;

					if (! discount_response.success) {
						// TODO: replace modal content by any information.
						// An error occurred.
						return;
					}

					prices_datas = prices_response.data;
					promo_datas = discount_response.data;
					offers = {
						mo: []
					};
					$mo_tpl = $('#imagify-offer-monthly-template');
					mo_clone = $mo_tpl.html();
					$estim_block = $('.imagify-estimation-block');

					// Remove inactive offers.
					$.each(prices_datas.monthlies, function (index, value) {
						if ('undefined' === typeof value.active
							||
							('undefined' !== typeof value.active && true === value.active)
						) {
							if ('starter' === value.label) {
								return;
							}
							offers.mo.push(value);
						}
					});

					// Refresh Analyzing block.
					$estim_block.removeClass('imagify-analyzing');

					// Reset offer selection.
					$('.imagify-offer-selected').removeClass('imagify-offer-selected').find('.imagify-checkbox').prop('checked', false);

					// Don't create prices table if something went wrong during request.
					if (null === offers.mo) {
						$offers_block = $('.imagify-pre-checkout-offers');

						// Hide main content.
						$offers_block.hide().attr('aria-hidden', true);

						// Show error message.
						$offers_block.closest('.imagify-modal-views').find('.imagify-popin-message').remove();
						$offers_block.after('<div class="imagify-popin-message imagify-error"><p>' + imagifyPricingModal.labels.errorPriceAPI + '</p></div>');

						// Show the modal content.
						imagifyModal.$modal.find('.imagify-modal-loader').fadeOut(300);
						imagifyModal.$modal.removeClass('imagify-modal-loading');
						return;
					}

					// Autofill coupon code & Show banner if discount is active.
					w.imagify_discount_datas = promo_datas;

					if (promo_datas.is_active) {
						if (promo_datas.applies_to instanceof Array) {
							plan_names = [];
							var plan_list = [];

							for (var plan_infos = 0; plan_infos < promo_datas.applies_to.length; plan_infos++) {
								plan_list.push(promo_datas.applies_to[plan_infos].plan_name);
							}

							plan_list.forEach(function (item) {
								if (! plan_names.includes(item)) {
									plan_names.push(item);
								}
							});

							plan_names = plan_names.join(', ');
						} else {
							plan_names = promo_datas.applies_to;
						}

						$banners = $('.imagify-modal-promotion');
						date_end = promo_datas.date_end.split('T')[0];
						promo = promo_datas.coupon_value;
						discount = 'percentage' === promo_datas.coupon_type ? promo + '%' : '$' + promo;


						// Show banners.
						$banners.addClass('active').attr('aria-hidden', 'false');

						// Populate banners.
						$banners.find('.imagify-promotion-number').text(discount);
						$banners.find('.imagify-promotion-plan-name').text(plan_names);
						$banners.find('.imagify-promotion-date').text(date_end);

					}

					/**
					 * Below lines will build Plan and Onetime offers lists.
					 * It will also pre-select a Plan and/or Onetime in both of views: pre-checkout and pricing tables.
					 */
					if (0 === offers.mo.length) {
						$('.imagify-pre-checkout-offers .imagify-offer-monthly').remove();
						$('.imagify-tabs').remove();
						$('.imagify-pricing-tab-monthly').remove();
					} else {
						// Now, do the MONTHLIES Markup.
						offers.mo = offers.mo.reverse();
						$.each(offers.mo, function (index, value) {
							var $tpl,
								classes = '';

							// Populate each offer.
							$tpl = $(mo_clone).clone();
							$tpl = imagifyModal.populateOffer($tpl, value, 'monthly', classes);

							// Complete Monthlies HTML.
							mo_html += $tpl[0].outerHTML;
						});

						// Wait for element to be ready after ajax callback before adding ribbon.
						setTimeout(function() {
							// Add best value ribbon to unlimited plan.
							$('.imagify-best-value').prepend('<div class="imagify-ribbon"><span>Best Value!</span></div>');
						}, 100);
					}

					// Fill pricing tables.
					if ($mo_tpl.parent().find('.imagify-offer-line')) {
						$mo_tpl.parent().find('.imagify-offer-line').remove();
					}

					$mo_tpl.before(mo_html);


					// Show the content.
					imagifyModal.$modal.find('.imagify-modal-loader').fadeOut(300);
					imagifyModal.$modal.removeClass('imagify-modal-loading');

				}); // Third AJAX request to get discount information.

			}); // End $.post.
		},

		/**
		 * 1) Modal Payment change/select plan
		 * 2) Checkout selection(s)
		 * 3) Payment process
		 */

		getPeriod: function () {
			return $('#imagify_all_plan_view').hasClass('imagify-month-selected') ? 'monthly' : 'yearly';
		},

		switchToView: function ($view, data) {

			var viewId = $view.attr('id'),
				$modalContent = imagifyModal.$modal.children('.imagify-modal-content');

			$view.siblings('.imagify-modal-views').hide().attr('aria-hidden', 'true');

			// Plans view has tabs: display the right one.
			if (data && data.tab) {
				$view.find('a[href="#' + data.tab + '"]').trigger('click.imagify');
			}

			// Payment view: it's an iframe.
			if ('imagify-payment-process-view' === viewId) {
				$modalContent.addClass('imagify-iframe-viewing');
			} else {
				$modalContent.removeClass('imagify-iframe-viewing');
			}

			// Success view: some tweaks.
			if ('imagify-success-view' === viewId) {
				$modalContent.addClass('imagify-success-viewing');
				imagifyModal.$modal.attr('aria-labelledby', 'imagify-success-view');
			} else {
				$modalContent.removeClass('imagify-success-viewing');
				imagifyModal.$modal.removeAttr('aria-labelledby');
			}

			$view.fadeIn(imagifyModal.speedFadeIn).attr('aria-hidden', 'false');
		},

		iframeSetSrc: function (params) {
			/**
			 * params = {
			 *     'plan_id': 0,
			 *     'period': 'monthly'|'yearly'
			 * }
			 */

			var $iframe = $('#imagify-payment-iframe'),
				iframe_src = $iframe.attr('src'),
				pay_src = $iframe.data('src'),
				// Stop it ESLint, you're drunk.
				key, amount, // eslint-disable-line no-unused-vars
				rt_yearly, rt_monthly, $iframeClone, tofind;

			// If we only change monthly/yearly payment mode.
			if (typeof params === 'string' && '' !== iframe_src) {
				tofind = 'monthly' === params ? 'yearly' : 'monthly';
				iframe_src = iframe_src.replace(tofind, params);
				$iframe.attr('src', iframe_src);
				return;
			}

			// If we get new informations about products.
			if (typeof params !== 'object') {
				return;
			}

			if (! params.period) {
				w.imagify.info('No period defined');
				return;
			}

			rt_yearly = 'yearly' === params.period ? params.plan_id : 0;
			rt_monthly = 'monthly' === params.period ? params.plan_id : 0;

			// Compose route.
			// pay_src + /:monthlyplan(0)/:yearlyplan(0)/
			pay_src = pay_src + 0 + '/' + rt_monthly + '/' + rt_yearly + '/none/';

			console.log(pay_src);
			// iFrame sort of cache fix.
			$iframeClone = $iframe.remove().attr('src', pay_src);

			imagifyModal.$paymentView.html($iframeClone);
		},

		/**
		 * Public function triggered by payement iframe.
		 */
		paymentClose: function () {
			$('.imagify-iframe-viewing .close-btn').trigger('click.imagify');
			$('.imagify-iframe-viewing').removeClass('imagify-iframe-viewing');
		},

		/**
		 * @uses imagifyModal.switchToView()
		 */
		paymentBack: function () {
			imagifyModal.switchToView(imagifyModal.$plansView);
		},

		/**
		 * @uses imagifyModal.switchToView()
		 */
		paymentSuccess: function () {
			imagifyModal.switchToView(imagifyModal.$successView);
		},

		/**
		 * @uses imagifyModal.paymentClose()
		 * @uses imagifyModal.paymentBack()
		 * @uses imagifyModal.paymentSuccess()
		 */
		checkPluginMessage: function (e) {
			var origin = e.origin || e.originalEvent.origin; // eslint-disable-line no-shadow

			if ( imagifyPricingModal.imagify_app_domain !== origin ) {
				return;
			}

			switch (e.data) {
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
		},
		getPromoAppliesTo: function(promo){
			var applies_to = [];
			if (promo.applies_to instanceof Array) {
				var plan_list = [];

				for (var plan_infos = 0; plan_infos < promo.applies_to.length; plan_infos++) {
					plan_list.push(promo.applies_to[plan_infos].plan_name);
				}

				plan_list.forEach(function (item) {
					if (! applies_to.includes(item)) {
						applies_to.push(item);
					}
				});
			} else {
				applies_to = [promo.applies_to];
			}
			return applies_to;
		}
	};

	/**
	 * INIT.
	 */

	/**
	 * Get pricings on modal opening.
	 * Build the pricing tables inside modal.
	 */
	$('.imagify-get-pricing-modal').on('click.imagify-ajax', function () {
		imagifyModal.getPricing($(this));
	});

	/**
	 * Get pricing on modal opening for admin bar menu, the button is added dynamically
	 * Build the pricing tables inside modal.
	 */
	$(document).on('click', '.imagify-admin-bar-upgrade-plan', function () {
		imagifyModal.getPricing($(this));
	});

	/**
	 * Reset the modal on close.
	 */
	$(d).on('modalClosed.imagify', '.imagify-payment-modal', function () {
		$(this).find('.imagify-modal-content').removeClass('imagify-success-viewing imagify-iframe-viewing');

		// Reset first view after fadeout ~= 300 ms.
		setTimeout(function () {
			$('.imagify-modal-views').hide();
			$('#imagify-plans-selection-view').show();
		}, 300);

		//delay scrolltop top to avoid flickering
		setTimeout(function () {
			$('.imagify-payment-modal').find('.imagify-modal-content').scrollTop(0);
		}, 400);
	});

	/**
	 * View game, step by step.
	 */

	/**
	 * 2) when you checkout.
	 *
	 * @uses imagifyModal.switchToView()
	 * @uses imagifyModal.getPeriod()
	 * @uses imagifyModal.iframeSetSrc()
	 */
	imagifyModal.$modal.on('click.imagify', '.imagify-payment-btn-select-plan', function (e) {
		var checkout_datas;

		e.preventDefault();

		checkout_datas = {};

		// Clear user account cache.
		if (imagifyPricingModal.userDataCache) {
			$.post(ajaxurl, {
				action:   imagifyPricingModal.userDataCache.deleteAction,
				_wpnonce: imagifyPricingModal.userDataCache.deleteNonce
			});
		}

		// Change views to go to checkout/payment view.
		imagifyModal.switchToView(imagifyModal.$paymentView);

		checkout_datas.plan_id = Object.values(JSON.parse($(this).attr('data-offer')))[0].id;
		checkout_datas.period  = imagifyModal.getPeriod();

		imagifyModal.iframeSetSrc(checkout_datas);
	});

	// Message/communication API.
	w.addEventListener('message', imagifyModal.checkPluginMessage, true);

})(jQuery, document, window);
