<?php

/**
 * Viator_Admin_Csv
 * @author 		Magazine3
 * This class is used to retrieve CSV file contents
 */
$viator_data = get_option('viator_data');
$viator_api_key = isset($viator_data['api_key'])?$viator_data['api_key']:'';
$viator_api_endpoint = isset($viator_data['api_end_point_url'])?$viator_data['api_end_point_url']:VIATORWP_API_ENDPOINT;
if(!empty($viator_api_endpoint)){
	$len_endpoint = strlen($viator_api_endpoint);
	if($viator_api_endpoint[$len_endpoint - 1] !== '/'){
		$viator_api_endpoint .= '/';
	}
}
define('VIATORWP_API_END_POINT', $viator_api_endpoint); // LIVE END POINT
define('VIATORWP_API_KEY', $viator_api_key); // LIVE API KEY
define('VIATORWP_API_CONTENT_TYPE', 'application/json;version=2.0'); // LIVE API KEY


require_once VIATORWP_PLUGIN_DIR.'admin/vas-admin-cronjab-settings.php';

add_action('admin_enqueue_scripts', 'viatorwp_admin_css_and_js');
if(!function_exists('viatorwp_admin_css_and_js')){
	function viatorwp_admin_css_and_js()
	{
		$local = array(     		   
			'ajax_url'                     => admin_url( 'admin-ajax.php' ),            
			'vas_admin_security_nonce'     => wp_create_nonce('vas_ajax_check_nonce'),
			'post_id'                      => get_the_ID()
		); 
		wp_register_script('viatorwp-admin-custom-js', VIATORWP_PLUGIN_URL . 'assets/admin/js/viator-admin.js', array('jquery'), VIATORWP_VERSION , true );  

		$local = apply_filters('viatorwp_localize_filter',$local,'viator_localize_admin_data');

		wp_localize_script('viatorwp-admin-custom-js', 'viator_localize_admin_data', $local );        
		wp_enqueue_script('viatorwp-admin-custom-js');
	}
}

add_action('wp_ajax_viatorwp_update_product_details_from_api_ajax', 'viator_update_product_details');
add_action('wp_ajax_nopriv_viatorwp_update_product_details_from_api_ajax', 'viator_update_product_details');
if(!function_exists('viator_update_product_details')){
	function viator_update_product_details()
	{
		if (isset($_POST['vas_admin_security_nonce']) &&  wp_verify_nonce( $_POST['vas_admin_security_nonce'], 'vas_ajax_check_nonce' ) ){
		 	if(isset($_POST['product_id']) && !empty($_POST['product_id'])){
		 		$product_id = intval($_POST['product_id']);
		 		$post_details = get_post($product_id);
		 		if(!empty($post_details)){
		 			$product_code = get_post_meta($product_id, '_viator_product_code');
		 			if(!empty($product_code) && isset($product_code[0])){
		 				viatorwp_fetch_product_details($product_code[0], 'UPDATE_PRODUCT', $product_id);
		 			}
		 		}
		 	}
		}
		wp_die();
	}
}

/** 
 * Fetches value from uploaded CSV file 
 */
if(!function_exists('viatorwp_get_csv_file_data')){
	function viatorwp_get_csv_file_data(){
		$response['status'] = true;
		$response['message'] = 'Success';
		$csv_file_details = get_option('viator_data');
		global $wpdb;
		if(isset($csv_file_details) && !empty($csv_file_details)){
			if(isset($csv_file_details['vas_csv_path']) && file_exists($csv_file_details['vas_csv_path'])){
				$cols = array(); $client_csv_data = array();
				$handle_csv = fopen($csv_file_details['vas_csv_path'], "r");
				if(!empty($handle_csv)){
					$i = 0; $col_head_count = 0;
						while(($line = fgetcsv($handle_csv)) !== FALSE) {
							if($i == 0) {
					          	$c = 0;
					          	if(!empty($line)){
					          		$col_head_count = count($line);
						          	foreach($line as $col) {
						              $cols[$c] = trim(strtolower(strtoupper($col)));
						              $c++;
						          	}
					      		}
					      	} else if($i > 0) {
					          	$c = 0;
					          	if(!empty($line)){
						          	if(count($line) == $col_head_count){
							          	foreach($line as $col) {
							            	$client_csv_data[$i][$cols[$c]] = $col;
							            	$c++;
							          	}
						          	}
						        }
				      		}
				      		$i++;
						}
			
						if(!empty($client_csv_data)){
							// Create table to database if table doesn't exist
							$table_name = $wpdb->prefix."viatorwp_uploaded_products";
							$is_table_exist_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );
							$db_table_name = $wpdb->get_var($is_table_exist_query);
							if($db_table_name != $table_name) {
								$charset_collate = $wpdb->get_charset_collate();
								$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
									`id` INT(11) NOT NULL AUTO_INCREMENT , 
									`product_url` VARCHAR(500) NULL , 
									`city` VARCHAR(255) NULL , 
									`country` VARCHAR(255) NULL , 
									`product_code` VARCHAR(255) NULL , 
									`flag` INT(11) NOT NULL DEFAULT '0' COMMENT '0 - Not extracted, 1 - Extracted' ,
									PRIMARY KEY (`id`), 
									INDEX `vpp_product_code` (`product_code`),
									INDEX `vpp_flag` (`flag`)) $charset_collate;";
									require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		    					dbDelta($sql);
							}


							$query = $wpdb->prepare("SELECT * FROM $table_name");
							$uploaded_products = $wpdb->get_results($query, ARRAY_A);

							$api_result = array(); $product_codes = array();
							foreach ($client_csv_data as $ccd_key => $ccd_value) {
								if(!empty($ccd_value['product_url'])){
									$insert_product_data = array(); $product_code = '';
									if(isset($ccd_value['product_url']) && !empty($ccd_value['product_url'])){
										$duplicate_flag = 0; 
										if(!empty($uploaded_products) && is_array($uploaded_products)){
											foreach ($uploaded_products as $up_key => $up_value) {
												if(isset($up_value['product_url'])){
													if(sanitize_url($ccd_value['product_url']) == $up_value['product_url']){
														$duplicate_flag = 1;
														break;
													}
												}
											}
										}

										if($duplicate_flag == 1){
											continue;
										}

										$explode_url = explode('/', $ccd_value['product_url']);
										$url_explode_count = count($explode_url);
										if(isset($explode_url[$url_explode_count -1]) && !empty($explode_url[$url_explode_count -1])){

											$product_url = explode('-',$explode_url[$url_explode_count -1]);
											if(isset($product_url[1]) && !empty($product_url[1])){
												$product_code = $product_url[1];
											}else if(isset($product_url[0]) && !empty($product_url[0])){
												$product_code = $product_url[0];
											}
											$insert_data['product_url'] = sanitize_url($ccd_value['product_url']);
											$insert_data['city'] = isset($ccd_value['city'])?sanitize_text_field($ccd_value['city']):'';
											$insert_data['country'] = isset($ccd_value['country'])?sanitize_text_field($ccd_value['country']):'';
											$insert_data['product_code'] = sanitize_text_field($product_code);
											$insert_data['flag'] = 0;
											$insert_format = array('%s', '%s', '%s', '%s', '%d');
											$wpdb->insert($table_name, $insert_data, $insert_format);
										}
									}
								}	
							}
						}
				}else{
					$response['status'] = false;
					$response['message'] = esc_html__('Error opening CSV file, please check', 'viator-integration-for-wp');
				}
			}else{
				$response['status'] = false;
				$response['message'] = esc_html__('CSV file missing. kindly upload and try again', 'viator-integration-for-wp');
			}
		}
		else{
			$response['status'] = false;
			$response['message'] = esc_html__('CSV file missing. kindly upload and try again', 'viator-integration-for-wp');
		}
		return $response;
	}
}


if(!function_exists('viatorwp_fetch_product_details')){
	function viatorwp_fetch_product_details($product_code='', $method_action='ADD_PRODUCT', $product_id='')
	{
		if(!empty($product_code)){
			$method = 'products/'.$product_code;
			$url = VIATORWP_API_END_POINT.$method;
			$product_response = viatorwp_wp_http_methods($url, 'GET', '');
			if(empty(json_decode($product_response, true))){
				$product_response = gzdecode($product_response);
			}
			$product_response = json_decode($product_response, true);

			if(isset($product_response['status']) && $product_response['status'] == 'ACTIVE'){
				$product_details = viatorwp_format_product_response($product_response);
				
				if($method_action == 'ADD_PRODUCT'){
					viatorwp_add_external_product($product_details);
				}else if($method_action == 'UPDATE_PRODUCT'){
					viatorwp_update_external_product($product_details, $product_id);
				}
			}else{
				// Update flag value when there is error from API response
		        global $wpdb;
		        $table_name = $wpdb->prefix."viatorwp_uploaded_products";
		        $wpdb->update($table_name, array('flag' => 2), array('product_code' => sanitize_text_field($product_code)), array('%d'), array('%s'));
			}
		}
	}
}

/** 
 *  PHP Curl API request method
 * @arguments
 * url : Endpoint URL for API method
 * type: API request method (GET, POST etc)
 * request_data: Request data for API method if any
 */

if(!function_exists('viatorwp_wp_http_methods')){
	function viatorwp_wp_http_methods($url, $type='GET',$request_data='')
	{
		$response = array();
		$args = array(
			'headers' => array(
	        	'exp-api-key' => VIATORWP_API_KEY,
	        	'Accept-Language' => 'en-US',
	        	'Accept' => VIATORWP_API_CONTENT_TYPE,
	        	'Content-Type' => VIATORWP_API_CONTENT_TYPE,
	        	'Accept-Encoding' => 'gzip',
	    	),
	    	'timeout' => 45,
		);
		if(!empty($request_data)){
			$args['body'] = $request_data;
		}
		if($type == 'POST'){
			$response = wp_remote_post( $url, $args );
		}else{
			$response = wp_remote_get( $url, $args );
		}
		return isset($response['body'])?$response['body']:'';
	}
}

/** 
 * Format API response to one standard format
 * @arguments
 * product_details: Details received from API
 */

if(!function_exists('viatorwp_format_product_response')){
	function viatorwp_format_product_response($product_details)
	{
		$formatted_response = array();
		$formatted_response['title'] = isset($product_details['title'])?$product_details['title']:'';
		$formatted_response['description'] = isset($product_details['description'])?$product_details['description']:'';
		$formatted_response['productCode'] = isset($product_details['productCode'])?$product_details['productCode']:'';
		$formatted_response['productUrl'] = isset($product_details['productUrl'])?$product_details['productUrl']:'';
		$formatted_response['images'] = isset($product_details['images'])?$product_details['images']:'';
		$formatted_response['destinationId'] = isset($product_details['destinations'][0])?$product_details['destinations'][0]['ref']:'';

		$product_categories = array(); $sub_term_details = array(); $parent_term_details = array();
		// Get destination details from Database Table
		if(isset($formatted_response['destinationId']) && !empty($formatted_response['destinationId'])){	
			$destination_details = viatorwp_get_destination_details($formatted_response['destinationId']);
			if(empty($destination_details)){
				viatorwp_fetch_viator_destination_details_from_api();
				$destination_details = viatorwp_get_destination_details($formatted_response['destinationId']);
			}
			if(!empty($destination_details)){
				$sub_term_details = get_term_by('name',$destination_details['destinationName'],'product_cat');
				$parent_dest_details = viatorwp_get_destination_parent_details($destination_details['parentId']);
				if(!empty($parent_dest_details)){
					$parent_term_details = get_term_by('name',$parent_dest_details['destinationName'],'product_cat');
				}


				if(!$parent_term_details){
					$category_slug = 'viatorwp-parent-cat-'. strtolower(sanitize_text_field($parent_dest_details['destinationName']));
					wp_insert_term(sanitize_text_field($parent_dest_details['destinationName']), 'product_cat', array(
					    'description' => 'Country', 
					    'parent' => 0,
					    'slug' => $category_slug
					) );

					$parent_term_details = get_term_by('name',$parent_dest_details['destinationName'],'product_cat');
				}

				if(!$sub_term_details){
					$category_slug = 'viatorwp-sub-cat-'. strtolower(sanitize_text_field($destination_details['destinationName']));
					wp_insert_term( sanitize_text_field($destination_details['destinationName']), 'product_cat', array(
					    'description' => 'City', 
					    'parent' => isset($parent_term_details->term_id)?intval($parent_term_details->term_id):0 ,
					    'slug' => $category_slug
					) );

					$sub_term_details = get_term_by('name',$destination_details['destinationName'],'product_cat');
				}	
			}	
		} 

		if(!empty($parent_term_details) && $sub_term_details){
			$product_categories[] = $parent_term_details->term_id; 
			$product_categories[] = $sub_term_details->term_id; 
		}else if(!empty($parent_term_details) && empty($sub_term_details)){
			$product_categories[] = $parent_term_details->term_id;
		}
		else if(empty($parent_term_details) && !empty($sub_term_details)){
			$product_categories[] = $sub_term_details->term_id;
		}
		$formatted_response['categoryId'] = $product_categories;

		if(isset($product_details['inclusions']) && !empty($product_details['inclusions'])){
			$formatted_response['inclusions'] = $product_details['inclusions'];
		}else{
			$formatted_response['inclusions'] = '';
		}
		if(isset($product_details['exclusions']) && !empty($product_details['exclusions'])){
			$formatted_response['exclusions'] = $product_details['exclusions'];
		}else{
			$formatted_response['exclusions'] = '';
		}
		if(isset($product_details['logistics']) && !empty($product_details['logistics'])){
			$formatted_response['logistics'] = $product_details['logistics'];
		}else{
			$formatted_response['logistics'] = '';
		}
		if(isset($product_details['itinerary']) && !empty($product_details['itinerary'])){
			$formatted_response['itinerary'] = $product_details['itinerary'];
		}else{
			$formatted_response['itinerary'] = '';
		}
		if(isset($product_details['additionalInfo']) && !empty($product_details['additionalInfo'])){
			$formatted_response['additionalInfo'] = $product_details['additionalInfo'];
		}else{
			$formatted_response['additionalInfo'] = '';
		}
		if(isset($product_details['cancellationPolicy']) && !empty($product_details['cancellationPolicy'])){
			$formatted_response['cancellationPolicy'] = $product_details['cancellationPolicy'];
		}else{
			$formatted_response['cancellationPolicy'] = '';
		}
		if(isset($product_details['reviews']) && !empty($product_details['reviews'])){
			$formatted_response['reviews'] = $product_details['reviews'];
		}else{
			$formatted_response['reviews'] = '';
		}
		return $formatted_response;	
	}
}

/** 
 * Check for existing destional details, if destionation doesn't exist the create new table
 * @arguments
 * destionation_id: Destionation id to get the details
 */
if(!function_exists('viatorwp_get_destination_details')){
	function viatorwp_get_destination_details($destination_id='')
	{
		global $wpdb; $destination_details = array();
		if(!empty($destination_id)){
			$table_name = $wpdb->prefix."viatorwp_api_destinations";
			$query = $wpdb->prepare("SELECT * FROM $table_name WHERE destinationId = %d", $destination_id);
			$destination_results = $wpdb->get_results($query, ARRAY_A);
			if(!empty($destination_results)){
				$destination_details['parentId'] = $destination_results[0]['parentId'];
				$destination_details['destinationName'] = $destination_results[0]['destinationName'];
				$destination_details['destinationType'] = $destination_results[0]['destinationType'];
				$destination_details['destinationId'] = $destination_results[0]['destinationId'];
			}else{
				$is_table_exist_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );
				$db_table_name = $wpdb->get_var($is_table_exist_query);
				if($db_table_name != $table_name) {
					$charset_collate = $wpdb->get_charset_collate();
					$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
						id INT(11) NOT NULL AUTO_INCREMENT , 
						sortOrder INT(11) NULL , 
						selectable TINYINT NOT NULL DEFAULT '0' , 
						destinationUrlName VARCHAR(255) NULL , 
						defaultCurrencyCode VARCHAR(255) NULL , 
						lookupId VARCHAR(255) NULL , 
						parentId INT(255) NOT NULL DEFAULT '0' , 
						timeZone VARCHAR(255) NULL , 
						destinationName VARCHAR(255) NOT NULL , 
						destinationId INT(11) NOT NULL DEFAULT '0' , 
						destinationType VARCHAR(255) NULL , 
						latitude VARCHAR(255) NULL , 
						longitude VARCHAR(255) NOT NULL , PRIMARY KEY (`id`), 
						INDEX vas_api_dest_name (destinationName), 
						INDEX vas_api_dest_id (destinationId), 
						INDEX vas_api_dest_type (destinationType)) ENGINE = InnoDB;";

					require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			    	dbDelta($sql);
			    }
		    }
		}
		return $destination_details;
	}
}

if(!function_exists('viatorwp_fetch_viator_destination_details_from_api')){
	function viatorwp_fetch_viator_destination_details_from_api()
	{
		global $wpdb;
		$method = 'v1/taxonomy/destinations';
		$url = VIATORWP_API_END_POINT.$method;
		$destination_response = viatorwp_wp_http_methods($url, 'GET', '');
		if(empty(json_decode($destination_response, true))){
			$destination_response = gzdecode($destination_response);
		}
		$destination_response = json_decode($destination_response, true);
		$table_name = $wpdb->prefix."viatorwp_api_destinations";
		if(isset($destination_response['data']) && isset($destination_response['data'][0])){
			if(is_array($destination_response['data'])){
				foreach ($destination_response['data'] as $dest_key => $dest_value) {
					$insert_data['sortOrder'] = intval($dest_value['sortOrder']);
					$insert_data['selectable'] = intval($dest_value['selectable']);
					$insert_data['destinationUrlName'] = sanitize_text_field($dest_value['destinationUrlName']);
					$insert_data['defaultCurrencyCode'] = sanitize_text_field($dest_value['defaultCurrencyCode']);
					$insert_data['lookupId'] = sanitize_text_field($dest_value['lookupId']);
					$insert_data['parentId'] = intval($dest_value['parentId']);
					$insert_data['timeZone'] = sanitize_text_field($dest_value['timeZone']);
					$insert_data['destinationName'] = sanitize_text_field(strtoupper($dest_value['destinationName']));
					$insert_data['destinationId'] = intval($dest_value['destinationId']);
					$insert_data['destinationType'] = strtoupper(sanitize_text_field($dest_value['destinationType']));
					$insert_data['latitude'] = sanitize_text_field($dest_value['latitude']);
					$insert_data['longitude'] = sanitize_text_field($dest_value['longitude']);
					$insert_format = array('%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s');
					$wpdb->insert($table_name, $insert_data, $insert_format);	
				}
			}
		}
	}
}

if(!function_exists('viatorwp_get_destination_parent_details')){
	function viatorwp_get_destination_parent_details($parent_id='')
	{
		global $wpdb;
		$country_details = array();
		$table_name = $wpdb->prefix."viatorwp_api_destinations";
		if(!empty($parent_id)){
			$destination_country_details = viator_get_destination_country_details($parent_id);
			if(isset($destination_country_details[0]) && !empty($destination_country_details)){
				if($destination_country_details[0]['destinationType'] == 'REGION'){
					$destination_country_details = viator_get_destination_country_details($destination_country_details[0]['parentId']);
					if(isset($destination_country_details[0]) && !empty($destination_country_details)){
						if($destination_country_details[0]['destinationType'] == 'COUNTRY'){
							$country_details = $destination_country_details[0];
						}
					}
				}else if($destination_country_details[0]['destinationType'] == 'COUNTRY'){
					$country_details = $destination_country_details[0];
				}
			}
		}
		return $country_details;
	}
}

if(!function_exists('viator_get_destination_country_details')){
	function viator_get_destination_country_details($parent_id='')
	{
		global $wpdb;
		$destination_results = array();
		if(!empty($parent_id)){
			$table_name = $wpdb->prefix."viatorwp_api_destinations";
			$query = $wpdb->prepare("SELECT * FROM $table_name WHERE destinationId = %d", $parent_id);
			$destination_results = $wpdb->get_results($query, ARRAY_A);
		}
		return $destination_results;
	}
}

if(!function_exists('viatorwp_get_location_details')){
	function viatorwp_get_location_details($location_ref = array(), $is_gzip=0)
	{
		if(!empty($location_ref)){
			$method = 'locations/bulk';
			$url = VIATORWP_API_END_POINT.$method;
			$requested_data['locations'] = $location_ref; 
			$requested_data = json_encode($requested_data);
			$location_response = viatorwp_wp_http_methods($url, 'POST', $requested_data);
			return $location_response;
		}
	}
}

if(!function_exists('viatorwp_update_products')){
	function viatorwp_update_products()
	{
		ob_start();
		require_once VIATORWP_PLUGIN_DIR.'admin/viatorwp_update_products.php';
		echo ob_get_clean();
		// ob_end_clean();
	}
}


add_action( 'add_meta_boxes', 'viatorwp_create_custom_product_metabox' );
if ( ! function_exists( 'viatorwp_create_custom_product_metabox' ) )
{
    function viatorwp_create_custom_product_metabox()
    {
        add_meta_box(
            'viatorwp_iec_metabox',
            esc_html__('Inclusion Exclusion And Cancellation Policy', 'viator-integration-for-wp' ),
            'viatorwp_custom_tab_meta_box_content',
            'product',
            'normal',
            'low'
        );

        add_meta_box(
            'viatorwp_additional_info_metabox',
            esc_html__('Additional Info', 'viator-integration-for-wp' ),
            'viatorwp_additional_info_tab_meta_box_content',
            'product',
            'normal',
            'low'
        );

        add_meta_box(
            'viatorwp_whatto_expect_metabox',
            esc_html__('What To Expect', 'viator-integration-for-wp' ),
            'viatorwp_whatto_expect_tab_meta_box_content',
            'product',
            'normal',
            'low'
        );
    }
}
//  Custom metabox content in admin product pages
if ( ! function_exists( 'viatorwp_custom_tab_meta_box_content' ) ){
    function viatorwp_custom_tab_meta_box_content( $post ){
        $inclusion_exclusion_and_cancel_policy = get_post_meta($post->ID, '_viator_inclusions', true) ? get_post_meta($post->ID, '_viator_inclusions', true) : '';
        $args['textarea_rows'] = 6;
        wp_editor( $inclusion_exclusion_and_cancel_policy, 'viator_inclusions', $args );
        echo '<input type="hidden" name="viatorwp_custom_product_field_nonce" value="' . wp_create_nonce() . '">';
    }
}

if ( ! function_exists( 'viatorwp_additional_info_tab_meta_box_content' ) ){
    function viatorwp_additional_info_tab_meta_box_content( $post ){
        $viator_additional_info = get_post_meta($post->ID, '_viator_additional_info', true) ? get_post_meta($post->ID, '_viator_additional_info', true) : '';
        $args['textarea_rows'] = 6;
        wp_editor( $viator_additional_info, 'viator_additional_info', $args );
        echo '<input type="hidden" name="viatorwp_custom_product_field_nonce" value="' . wp_create_nonce() . '">';
    }
}

if ( ! function_exists( 'viatorwp_whatto_expect_tab_meta_box_content' ) ){
    function viatorwp_whatto_expect_tab_meta_box_content( $post ){
        $viator_itinerary = get_post_meta($post->ID, '_viator_itinerary', true) ? get_post_meta($post->ID, '_viator_itinerary', true) : '';
        $args['textarea_rows'] = 6;
        wp_editor( $viator_itinerary, 'viator_whatto_expect', $args );
        echo '<input type="hidden" name="viatorwp_custom_product_field_nonce" value="' . wp_create_nonce() . '">';
    }
}

add_action( 'save_post', 'viatorwp_save_product_custom_content_meta_box', 10, 1 );
if ( ! function_exists( 'viatorwp_save_product_custom_content_meta_box' ) )
{
    function viatorwp_save_product_custom_content_meta_box( $post_id ) {
    	// Sanitize user input and update the meta field in the database.
    	if(isset($_POST['viatorwp_custom_product_field_nonce']) && wp_verify_nonce($_POST['viatorwp_custom_product_field_nonce'])){
    		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}
    		if(current_user_can( 'manage_options' )){
		    	if(isset($_POST['viator_inclusions'])){
		        	update_post_meta( $post_id, '_viator_inclusions', wp_kses_post($_POST['viator_inclusions']));
		        }
		        if(isset($_POST['viator_additional_info'])){
		        	update_post_meta( $post_id, '_viator_additional_info', wp_kses_post($_POST['viator_additional_info']));
		        }
		        if(isset($_POST['viator_whatto_expect'])){
		        	update_post_meta( $post_id, '_viator_itinerary', wp_kses_post($_POST['viator_whatto_expect']));
		        }
		    }
	    }
    }
}