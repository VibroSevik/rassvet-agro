	$(document).ready(function () {
	$('input[name^=quantity]:not([value=\'1\'])').each(function(indx){
		recalc($(this).data('product_id'));
	});
	});	
	
	function recalc(product_id) {
		var quantity = $('#quantity-'+product_id).val();
		var quantity = typeof(quantity) != 'undefined' ? quantity : 1;
		var options_price = 0;
	
		$('#option_'+product_id+' option:selected, #option_'+product_id+' input:checked').each(function() {
			if ($(this).attr('price_prefix') == '+') { options_price = options_price + Number($(this).attr('price')); }
			if ($(this).attr('price_prefix') == '-') { options_price = options_price - Number($(this).attr('price')); }
		});
	
		var price_no_format = Number($('.price_no_format'+product_id).attr('price'));
		var special_no_format = Number($('.special_no_format'+product_id).attr('price'));
		var new_price = (price_no_format + options_price) * quantity;
		var new_special = (special_no_format + options_price) * quantity;
		$('.price_no_format' + product_id).html(price_format(new_price));
		$('.special_no_format' + product_id).html(price_format(new_special));
	}
	
	var cart = {
	'add': function(product_id, quantity) {
	var quantity = typeof(quantity) != 'undefined' ? quantity : 1;
	var option = $('#option_'+product_id+' input[type=\'text\'], #option_'+product_id+' input[type=\'radio\']:checked, #option_'+product_id+' input[type=\'checkbox\']:checked, #option_'+product_id+' select');
	if ($('#option_'+product_id).length != 0) {
		var data = option.serialize() + '&product_id=' + product_id + '&quantity=' + quantity;
	} else {
		var data = 'product_id=' + product_id + '&quantity=' + quantity;
	}
	
		$.ajax({
			url: 'index.php?route=checkout/cart/add',
			type: 'post',
			data: data,
			dataType: 'json',
			beforeSend: function() {
				$('#cart > button').button('loading');
			},
			success: function(json) {
				$('.alert, .text-danger').remove();
				$('#cart > button').button('reset');
				
				if (json['error']) {
					if (json['error']['option']) {
						for (i in json['error']['option']) {
							$('#option-' + i).after($('<span class="text-danger">' + json['error']['option'][i] + '</span>').fadeIn().delay('2000').fadeOut());
						}
					}
				}

				if (json['success']) {
					$('#content').parent().before('<div class="alert alert-success"><i class="fa fa-check-circle"></i> ' + json['success'] + '<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
					$('html, body').animate({ scrollTop: 0 }, 'slow');
					$('#cart').load('index.php?route=common/cart/info #cart > *');
				}
			}
		});
	},
	'update': function(key, quantity) {
		$.ajax({
			url: 'index.php?route=checkout/cart/edit',
			type: 'post',
			data: 'key=' + key + '&quantity=' + (typeof(quantity) != 'undefined' ? quantity : 1),
			dataType: 'json',
			beforeSend: function() {
				$('#cart > button').button('loading');
			},
			success: function(json) {
				$('#cart > button').button('reset');

				if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
					location = 'index.php?route=checkout/cart';
				} else {
					$('#cart').load('index.php?route=common/cart/info #cart > *');
				}
			}
		});
	},
	'remove': function(key) {
		$.ajax({
			url: 'index.php?route=checkout/cart/remove',
			type: 'post',
			data: 'key=' + key,
			dataType: 'json',
			beforeSend: function() {
				$('#cart > button').button('loading');
			},
			success: function(json) {
				$('#cart > button').button('reset');

				if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
					location = 'index.php?route=checkout/cart';
				} else {
					$('#cart').load('index.php?route=common/cart/info #cart > *');
				}
			}
		});
	}
}