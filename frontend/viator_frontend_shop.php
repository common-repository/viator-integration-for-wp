<?php
require_once VIATORWP_PLUGIN_DIR.'frontend/viator_add_product_custom_tabs.php';
add_action( 'woocommerce_single_product_summary', 'viatorwp_custom_button_by_categories', 36 ,0 );
if(!function_exists('viatorwp_custom_button_by_categories')){
	function viatorwp_custom_button_by_categories(){
	    global $product;
	    $product_meta_code = get_post_meta($product->get_id(), '_viator_product_code');    
	    $product_code = '';
	    if(!empty($product_meta_code) && isset($product_meta_code[0])){
	    	$product_code = $product_meta_code[0];
	    }

	    echo '<div id="vas-product-price" data-product-code="'.esc_attr($product_code).'" class="vas-loading"><h3>'.esc_html__('Fetching Price Please Wait', 'viator-integration-for-wp').'</h3></div>';
	}
}

add_action('wp_enqueue_scripts', 'viatorwp_front_css_and_js');
if(!function_exists('viatorwp_front_css_and_js')){
	function viatorwp_front_css_and_js()
	{
		if(function_exists('is_product')){
			if(is_product()){
				wp_enqueue_style('viatorwp-front-custom-css', VIATORWP_PLUGIN_URL . 'assets/frontend/css/vas-front.css', array(), VIATORWP_VERSION, 'all');
				wp_enqueue_style('viatorwp-front-reviews-custom-css', VIATORWP_PLUGIN_URL . 'assets/frontend/css/viatorwp-front-reviews.css', array(), VIATORWP_VERSION, 'all');

				$local = array(     		   
					'ajax_url'                     => admin_url( 'admin-ajax.php' ),            
					'vas_security_nonce'           => wp_create_nonce('vas_ajax_check_nonce'),
					'post_id'                      => get_the_ID()
				); 
				wp_register_script('viatorwp-front-custom-js', VIATORWP_PLUGIN_URL . 'assets/frontend/js/vas-front.js', array('jquery'), VIATORWP_VERSION , true );                        
				wp_localize_script('viatorwp-front-custom-js', 'vas_localize_front_data', $local );        
				wp_enqueue_script('viatorwp-front-custom-js');
			}
		}
	}
}

add_action( 'wp_ajax_nopriv_viatorwp_get_product_price_from_api_ajax', 'viatorwp_get_product_price_from_api');  
add_action( 'wp_ajax_viatorwp_get_product_price_from_api_ajax', 'viatorwp_get_product_price_from_api') ; 
if(!function_exists('viatorwp_get_product_price_from_api')){
	function viatorwp_get_product_price_from_api()
	{
		$response['status'] = false;
		$response = array();
		 if (isset($_POST['vas_security_nonce']) &&  wp_verify_nonce( $_POST['vas_security_nonce'], 'vas_ajax_check_nonce' ) ){
		 	if(isset($_POST['product_id']) && !empty($_POST['product_id'])){
			 	$product_code = sanitize_text_field($_POST['product_id']);
				$method = 'availability/schedules/'.$product_code;
				$url = VIATORWP_API_END_POINT.$method;
				$product_avail_resp = viatorwp_wp_http_methods($url, 'GET','');
				if(empty(json_decode($product_avail_resp, true))){
					$product_avail_resp = gzdecode($product_avail_resp);
				}
				if(!empty($product_avail_resp)){
					$product_avail_resp = json_decode($product_avail_resp, true);
					if(isset($product_avail_resp['currency']) && !empty($product_avail_resp['currency'])){
						$response['currency'] = isset($product_avail_resp['currency'])?$product_avail_resp['currency']:'';
						$response['status'] = true;
					}

					if(isset($product_avail_resp['summary']) && !empty($product_avail_resp['summary'])){
						$response['price'] = isset($product_avail_resp['summary']['fromPrice'])?$product_avail_resp['summary']['fromPrice']:0;
						$response['status'] = true;
					}
				}
			}
		 }
		 echo json_encode($response);
		wp_die();
	}
}


add_action( 'wp_ajax_nopriv_viatorwp_get_product_locations_from_api_ajax', 'viatorwp_get_product_locations_from_api');  
add_action( 'wp_ajax_viatorwp_get_product_locations_from_api_ajax', 'viatorwp_get_product_locations_from_api') ;
if(!function_exists('viatorwp_get_product_locations_from_api')){
	function viatorwp_get_product_locations_from_api()
	{
		$response['status'] = false; $location_details_resp = array();
		$response = array();
		 if (isset($_POST['vas_security_nonce']) &&  wp_verify_nonce( $_POST['vas_security_nonce'], 'vas_ajax_check_nonce' ) ){
		 	if(isset($_POST['product_id']) && !empty($_POST['product_id'])){
			 	$product_code = sanitize_text_field($_POST['product_id']);
			 	$product_details = get_page_by_path($product_code, OBJECT, 'product' );
			 	if(!empty($product_details)){
			 		$product_id = $product_details->ID;
			 		$product_meeting = get_post_meta($product_id, '_viator_meeting_and_pickup');
					if(isset($product_meeting[0]) && !empty($product_meeting[0]['travelerPickup'])){
						if(isset($product_meeting[0]['travelerPickup']['locations']) && !empty($product_meeting[0]['travelerPickup']['locations'])){
							if(is_array($product_meeting[0]['travelerPickup']['locations'])){
								foreach ($product_meeting[0]['travelerPickup']['locations'] as $tpl_key => $tpl_value) {
									if(isset($tpl_value['location']) && isset($tpl_value['location']['ref'])){
										$loc[] = $tpl_value['location']['ref'];	
									}
								}
								$location_details = viatorwp_get_location_details($loc);
								if(empty(json_decode($location_details, true))){
									$location_details = gzdecode($location_details);
								}
								if(!empty($location_details)){
									$location_details = json_decode($location_details, true);
									if(isset($location_details['locations']) && !empty($location_details['locations'])){
										foreach($location_details['locations'] as $ld_key => $ld_value) {
											if(!empty($ld_value)){
												$location_details_resp['travelerPickup'][] = viatorwp_format_location_details($ld_value);
											}
										}	
									}
									
								}
							}
						}
						$location_details_resp['additionalInfo'] = isset($product_meeting[0]['travelerPickup']['additionalInfo'])?$product_meeting[0]['travelerPickup']['additionalInfo']:'';
					}
			 	}
			 }
		}
		echo json_encode($location_details_resp);
		wp_die();
	}
}