jQuery(function($){
	var loginLink = $('.top-links.links-wrapper-separators-left .links li:nth-of-type(3) a');
	var pageCategory = $('.category-title h1');
	var qtyInput = $('.product-view .qty-wrapper #qty');

	$('.products-grid .price-box .regular-price').empty();




	if (loginLink.html() == "Log In") { //LOGIN LINK FOR NON-DIST
		var addBoxDiv = $('.add-to-cart');
		var redirectLink = loginLink.attr('href');
		var addToCartButton = $('#product-addtocart-button');
		$('.qty-wrapper').css('display', 'none');
		addBoxDiv.find('button').attr({'title': 'Login','onclick': 'window.location.href="' + redirectLink + '"'});
		addBoxDiv.find('button > span > span').html('Login to See Distributor Pricing');
		if (addToCartButton.length != 0) {$('.product-options').append(addToCartButton); };
		$('#configurable_swatch_color li a span').click(function(){ setTimeout(UpdateRegularTierPrices, 100); swapImage($(this));});
		UpdateRegPrice();
	} 
	else { //LOGGED IN VIEW FOR DIST
		$('.catalog-product-view .product-view .product-options .option').css('display', 'block');
		//1.9.2.2 dom update
		$('.product-view .product-options .last').show();

		$('.product-options').find(".option").slice(3,4).css('display','none');//screen charge 1
		$('.product-options').find(".option").slice(4,5).css('display','none');//screen charge 2
		$('.product-options').find(".option").slice(5,6).css('display','none');//first imprint	
		$('.product-options').find(".option").slice(6,7).css('display','none');//second imprint
		if ($('body').hasClass('catalog-product-view catalog-product-view')){
			UpdateMainPrices();
			$('#configurable_swatch_color li a span').click(function(){setTimeout(UpdateTierPrices, 100);swapImage($(this));});
		}
	}





	if (pageCategory != undefined) {
		var category = pageCategory.text().toLowerCase();
		if (category == 'ceramic mugs' || category == 'travelware' || category == '') {};
	};

	



	if (qtyInput != undefined) {//CHANGE PRICING BASED ON QUANTITY
		qtyInput.keyup(function(){
			var priceMultiplier = [];
			var prices = $('.product-pricing .tier-price .price');
			prices.each(function(){
				var price = parseFloat($(this).html().replace('$', ''));
				//console.log(price); //debug
				priceMultiplier[priceMultiplier.length] = price;
			});
			var qty = $(this).val();
			var priceMultiplierIndex = $('.product-pricing').length - 1;
		});
	};




	// for description tab on product view page
	var additionalTab = $('#tab-additional a');
	if (additionalTab != undefined) {
		additionalTab.trigger('click');
	};




	//one page checkout
	if( $('body').hasClass('checkout-cart-index')){
		var cartPrice = parseFloat($('.col-unit-price .cart-price .price').html().replace('$', ''));
		var qty = parseFloat($('input.qty').val());
		var totalPrice = parseFloat($('.col-total .cart-price .price').html().replace('$', ''));
		var screenCharge;

		if ( $('.item-options dd:contains("$47.50")').html() ){
			//console.log('one screen charge');
		}
		if ( $('.item-options dd:contains("$95.00")').html()) {
			//console.log('two screen charge');
		}
	
		// console.log(cartPrice);
		// console.log(qty);
		// console.log(totalPrice);
		//var screenGuess = (totalPrice/qty);
		//console.log(screenCharge);
	}




	//edit cart
	if( $('body').hasClass('checkout-cart-configure')){
		var oldPrice = parseFloat($('.old-price .price').html().replace('$', ''));

		$('.product-options .option li').find("input").slice(0,1).prop("checked", false);//screen charge 1
		$('.product-options .option li').find("input").slice(1,2).prop("checked", false);//screen charge 2
		$('.product-options .option li').find("input").slice(2,3).prop("checked", false);//charge 1			
		$('.product-options .option li').find("input").slice(4,5).prop("checked", false);//charge 2

		//UpdateMainPrices();
		//UpdateTierPrices();

		$('.checkout-cart-configure.catalog-product-view #attribute92').change(function(){
			console.log('change');
			//$('.old-price .price').html('$' + oldPrice.toFixed(2));
			console.log(oldPrice);
		});

		$('#attribute92').val('0');
	}




	function setOptionListeners(){
		
		if ($('.product-options .option li').find("input").slice(0,1).is(':checked')){//one color
			$('.product-options').find(".option").slice(3,4).css('display','block');//screen charge 1
			$('.product-options').find(".option").slice(5,6).css('display','block');//first imprint	
		}
		if(	$('.product-options .option li').find("input").slice(1,2).is(':checked')){//second color
			$('.product-options').find(".option").slice(4,5).css('display','block');//screen charge 2
			$('.product-options').find(".option").slice(5,6).css('display','block');//first imprint	
			$('.product-options').find(".option").slice(6,7).css('display','block');//second imprint
		}
		
		$('.product-options .option li').find("input").slice(0,1).click(function(){//1 color
			$('.product-options').find(".option").slice(3,4).css('display','block');//screen charge 1
			$('.product-options').find(".option").slice(4,5).css('display','none');//screen charge 2
			$('.product-options').find(".option").slice(5,6).css('display','block');//first imprint
			$('.product-options').find(".option").slice(6,7).css('display','none');//second imprint
			$('.product-options .option li').find("input").slice(2,3).prop("checked", true);//charge 1
			$('.product-options .option li').find("input").slice(4,5).prop("checked", false);//charge 2

		});
		
		$('.product-options .option li').find("input").slice(1,2).click(function(){//2 color
			$('.product-options').find(".option").slice(3,4).css('display','none');//screen charge 1
			$('.product-options').find(".option").slice(4,5).css('display','block');//screen charge 2
			$('.product-options').find(".option").slice(5,6).css('display','block');//first imprint
			$('.product-options').find(".option").slice(6,7).css('display','block');//second imprint
			$('.product-options .option li').find("input").slice(2,3).prop("checked", false);//charge 1			
			$('.product-options .option li').find("input").slice(4,5).prop("checked", true);//charge 2
		});
	}



	function UpdateMainPrices(){
		var lowestPrice = parseFloat($('.tier-prices.product-pricing li:last-of-type .price').html().replace('$', ''));
		var distPrice = parseFloat($('.special-price .price').html());
		var oldPrice = parseFloat($('.old-price .price').html().replace('$', ''));

		$('.product-view .special-price .price').css('display', 'none');
		$('.product-view .special-price .price-label').html('As low as: $' + lowestPrice.toFixed(2));
	}




	function UpdateRegPrice(){
		if( $('body').hasClass('catalog-product-view')){
			var regPrice =  parseFloat($('.product-view .product-shop .price-box .regular-price .price').html().replace('$', ''));
			var lowestPrice = parseFloat($('.tier-prices.product-pricing li:last-of-type .price').html().replace('$', ''));
			
			if( $('.product-view .product-shop .price-box .regular-price .new-price').length )
				$('.product-view .product-shop .price-box .regular-price .new-price').html('As low as: $' + lowestPrice.toFixed(2));
			else {
				$('.product-view .product-shop .price-box .regular-price').append('<span class="new-price"></span>');
				$('.product-view .product-shop .price-box .regular-price .new-price').html('As low as: $' + lowestPrice.toFixed(2));
			}
		}
	}




	function UpdateTierPrices(){
		var tmpReset = parseFloat("0");
		var oldPrice = parseFloat($('.old-price .price').html().replace('$', ''));
		var priceTier = $('.tier-prices.product-pricing'); 
		var distPrice = parseFloat($('.special-price .price').html().replace('$', ''));
		var currentPrice = parseFloat($('.tier-prices.product-pricing .tier-0 .price').html().replace('$', ''));

		//test to see if color option is checked		
		if ($('.product-options .option li').find("input").slice(2,3).is(':checked')){//charge 1
			tmpReset = parseFloat("47.5");
			oldPrice = (oldPrice - tmpReset);
			distPrice = (distPrice - tmpReset);	

			$('.special-price .price').html('$' + distPrice.toFixed(2));
			$('.old-price .price').html('$' + oldPrice.toFixed(2));
		}
		if ($('.product-options .option li').find("input").slice(4,5).is(':checked')){//charge 2			
			tmpReset = parseFloat("95");
			oldPrice = (oldPrice - tmpReset);
			distPrice = (distPrice - tmpReset);	

			$('.special-price .price').html('$' + distPrice.toFixed(2));
			$('.old-price .price').html('$' + oldPrice.toFixed(2));
		}

		var diff =  distPrice.toFixed(2) - currentPrice.toFixed(2);

		$('.tier-prices.product-pricing .tier-price').each(function(){
			var tmpPrice = parseFloat($(this).find('.price').html().replace('$', ''));
			var newPrice = (tmpPrice + diff).toFixed(2);
			
			$(this).find('.price').html('$' + newPrice);

			var rawTierPrice = ($(this).find('.price').html()).replace('$', ''); //straight from DOM
			var percentSaving = $(this).find('.benefit span.percent');
			var distSavingPerc = (((distPrice - rawTierPrice)/distPrice)*100).toFixed(0).toString();
			percentSaving.html(distSavingPerc);
		});

		UpdateMainPrices();
	}



	function UpdateRegularTierPrices(){
		var tmpReset = parseFloat("0");
		var regPrice = parseFloat($('.regular-price .price').html().replace('$', ''));
		var priceTier = $('.tier-prices.product-pricing'); 
		var currentPrice = parseFloat($('.tier-prices.product-pricing .tier-0 .price').html().replace('$', ''));
		var diff =  regPrice.toFixed(2) - currentPrice.toFixed(2);

		$('.tier-prices.product-pricing .tier-price').each(function(){
			var tmpPrice = parseFloat($(this).find('.price').html().replace('$', ''));
			var newPrice = (tmpPrice + diff).toFixed(2);
			
			$(this).find('.price').html('$' + newPrice);
			
			var rawTierPrice = ($(this).find('.price').html()).replace('$', ''); //straight from DOM
			var percentSaving = $(this).find('.benefit span.percent');
			var distSavingPerc = (((regPrice - rawTierPrice)/regPrice)*100).toFixed(0).toString();
			percentSaving.html(distSavingPerc);
		});
		UpdateRegPrice();
	}




	function moveColorSwatches(){
		var swatches = $('.product-options .swatch-attr').parent();
		$('.product-type-data').before(swatches).slideDown('slow');
	}



	function swapImage(e){
		if (e.find('img').length){
			var tempArray = e.find('img').attr('src').split("/");
			var thisId = tempArray[tempArray.length-1];
			var mainIdArray = $('.img-box .product-image a.cloud-zoom').attr('href').split("/");
			
			mainIdArray[mainIdArray.length-1] = thisId;
			newSrc = mainIdArray.join("/");
			
			$('.img-box .product-image').find('img').attr('src', newSrc);
			$('.img-box .product-image .mousetrap').hover(function(){
				$('.img-box .product-image .cloud-zoom-big').css('background-image', 'url('+newSrc+')');
			});
			swapSku();
		}
	}



	function swapSku(){
		var label = $('.swatch-attr #color_label #select_label_color').html();
		var labelArray = label.split(" ");
		$('.product-name .sku').html(labelArray[0]);
	}





	// Product.OptionsPrice.prototype.reload 
	//    = Product.OptionsPrice.prototype.reload.wrap(function(parentMethod){
	//                  alert("Options Reload Override success");
	// });

	// Product.Config.prototype.fillSelect 
	// 	= Product.Config.prototype.fillSelect.wrap(function(parentMethod){
	//                  alert("Fill Select Override success");
	// });



	setOptionListeners();
	moveColorSwatches();

})