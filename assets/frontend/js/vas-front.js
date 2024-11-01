jQuery(document).ready(function($){
	let productId = $('#vas-product-price').attr('data-product-code');
	$.ajax({
		type: 'POST',
		url: vas_localize_front_data.ajax_url,
		data: {action:"viatorwp_get_product_price_from_api_ajax", product_id:productId,vas_security_nonce:vas_localize_front_data.vas_security_nonce},
		success: function(response){
			response = JSON.parse(response);
			let livePrice = '';
			if(typeof response.currency !== 'undefined'){
				livePrice = response.currency;
			}
			if(typeof response.price !== 'undefined'){
				livePrice = livePrice + ' ' + response.price;
			}
			if(livePrice.length === ""){
				livePrice = "Price Not Available";
			}
			$('#vas-product-price').html('<h3>' + livePrice + '</h3>');
		},
		error: function(error_response){

		}
	});

	$.ajax({
		type: 'POST',
		url: vas_localize_front_data.ajax_url,
		data: {action:"viatorwp_get_product_locations_from_api_ajax", product_id:productId,vas_security_nonce:vas_localize_front_data.vas_security_nonce},
		success: function(response){
			let appendHtml = '';
			if(response){
				response = JSON.parse(response);
				if(response.travelerPickup && response.travelerPickup != ''){
					appendHtml += '<p><strong>Pickup Points</strong></p>';
					appendHtml += '<select>';
					$.each(response.travelerPickup, function(i, e){
						if((e.name != '') && (typeof e.name != 'undefined')){
							appendHtml += '<optgroup label="'+e.name+'">';
							appendHtml += '<option>'+e.address+'</option>';
							appendHtml += '</optgroup>';
						}
					});
					appendHtml += '</select>';
					if(typeof response.additionalInfo != undefined){
						appendHtml += '<p>'+response.additionalInfo+'</p>';
					}					
					$('#travel-pickup').html(appendHtml);
				}
			}
		}
	});
});