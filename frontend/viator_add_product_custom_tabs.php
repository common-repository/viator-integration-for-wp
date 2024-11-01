<?php
add_filter( 'woocommerce_product_tabs', 'viatorwp_custom_product_tabs' );
function viatorwp_custom_product_tabs( $tabs ) {
    // Adds the other products tab
    $tabs['inclusions_products_tab'] = array(
        'title'     => esc_html__( 'Inclusions & Exclusions', 'viator-integration-for-wp' ),
        'priority'  => 120,
        'callback'  => 'viatorwp_inclusions_and_exclusions_products_tab_content'
    );
    $tabs['meeting_products_tab'] = array(
        'title'     => esc_html__( 'Meeting And Pickup', 'viator-integration-for-wp' ),
        'priority'  => 130,
        'callback'  => 'viatorwp_meeting_and_pickup_products_tab_content'
    );
    $tabs['whatto_expect_products_tab'] = array(
        'title'     => esc_html__( 'What To Expect', 'viator-integration-for-wp' ),
        'priority'  => 140,
        'callback'  => 'viatorwp_whatto_expect_products_tab_content'
    );
    $tabs['additional_info_products_tab'] = array(
        'title'     => esc_html__( 'Additional Info', 'viator-integration-for-wp' ),
        'priority'  => 150,
        'callback'  => 'viatorwp_additional_info_products_tab_content'
    );
    return $tabs;
}

// New Tab contents
if(!function_exists('viatorwp_inclusions_and_exclusions_products_tab_content')){
	function viatorwp_inclusions_and_exclusions_products_tab_content() {
		global $post;
		$inclusions_tab_content = get_post_meta( get_the_ID(), '_viator_inclusions');
		if(!empty($inclusions_tab_content)){
			if(is_array($inclusions_tab_content)){
				foreach ($inclusions_tab_content as $itc_key => $itc_value) {
					echo isset($inclusions_tab_content[$itc_key])?$inclusions_tab_content[$itc_key]:'';
				}
			}
		}
	}
}

if(!function_exists('viatorwp_meeting_and_pickup_products_tab_content')){
	function viatorwp_meeting_and_pickup_products_tab_content()
	{
		global $post;
		$pickup_details = '';
		$product_meeting = get_post_meta(get_the_ID(), '_viator_meeting_and_pickup');
		if(!empty($product_meeting) && isset($product_meeting[0])){
			if(isset($product_meeting[0]) && !empty($product_meeting[0]['start'])){
				echo '<p><strong> '. esc_html__('Meeting Point', 'viator-integration-for-wp') .'</strong></p>';
				$start_point = $product_meeting[0]['start'];
				if(isset($start_point[0]) && isset($start_point[0]['location'])){
					$loc[] = $start_point[0]['location']['ref'];
					
					$location_details = viatorwp_get_location_details($loc); // Make a call to API to get location details
					$location_details = json_decode($location_details, true);
					$location_name = ''; $full_address = '';
					if(isset($location_details['locations']) && isset($location_details['locations'][0])){
						$location_details = $location_details['locations'][0];

						$formatted_location = viatorwp_format_location_details($location_details);

						if(isset($formatted_location['name']) && !empty($formatted_location['name'])){
							echo '<p>'.esc_html($formatted_location['name']).'</p>';
						}
						if(isset($formatted_location['address']) && !empty($formatted_location['address'])){
							echo '<p>'.esc_html($formatted_location['address']).'</p>';
						}
					}
					if(isset($start_point[0]['description']) && !empty($start_point[0]['description'])){
						echo '<p>'.esc_html($start_point[0]['description']).'</p>';
					}else{
						echo '<p>'.esc_html__('Meeting point data not available', 'viator-integration-for-wp').'</p>'; 
					}
				}
			}
			?>
			<div id="travel-pickup">
				
			</div>
			<?php
			if(isset($product_meeting[0]) && !empty($product_meeting[0]['end'])){
				echo '<p><strong> '. esc_html__('End Point', 'viator-integration-for-wp') .'</strong></p>';
				$end_point = $product_meeting[0]['end'];
				if(isset($end_point[0]) && isset($end_point[0]['location'])){
					$loc[] = $end_point[0]['location']['ref'];

					$location_details = viatorwp_get_location_details($loc); // Make a call to API to get location details
					$location_details = json_decode($location_details, true);
					$location_name = ''; $full_address = '';
					if(isset($location_details['locations']) && isset($location_details['locations'][0])){
						$location_details = $location_details['locations'][0];

						$formatted_location = viatorwp_format_location_details($location_details);

						if(isset($formatted_location['name']) && !empty($formatted_location['name'])){
							echo '<p>'.esc_html($formatted_location['name']).'</p>';
						}
						if(isset($formatted_location['address']) && !empty($formatted_location['address'])){
							echo '<p>'.esc_html($formatted_location['address']).'</p>';
						}
					}
					if(isset($end_point[0]['description']) && !empty($end_point[0]['description'])){
						echo '<p>'.esc_html($end_point[0]['description']).'</p>';
					}else{
						echo '<p>'.esc_html__('End point data not available', 'viator-integration-for-wp').'</p>';
					}
				}
			}
			if(!empty($pickup_details)){
				echo '<p><strong>'.esc_html__('Pickup Details', 'viator-integration-for-wp').'</strong></p>';
				echo '<p>'.esc_html($pickup_details).'</p>';
			}
			if(isset($product_meeting[0]) && !empty($product_meeting[0]['redemption'])){
				if(isset($product_meeting[0]['redemption']['specialInstructions'])){
					echo '<p><strong>'.esc_html__('Special Instructions', 'viator-integration-for-wp').'</strong></p>';
					echo '<p>'.esc_html($product_meeting[0]['redemption']['specialInstructions']).'</p>';
				}
			}
		}else{
			echo '<p>'.esc_html__('Meeting and pickup data for this product is not available', 'viator-integration-for-wp').'</p>';
		}
	}
}

if(!function_exists('viatorwp_whatto_expect_products_tab_content')){
	function viatorwp_whatto_expect_products_tab_content()
	{
		global $post;
		$itinerary = get_post_meta( get_the_ID(), '_viator_itinerary');
		echo isset($itinerary[0])?$itinerary[0]:'';
	}
}

if(!function_exists('viatorwp_additional_info_products_tab_content')){
	function viatorwp_additional_info_products_tab_content()
	{
		global $post;
		$additional_info = get_post_meta( get_the_ID(), '_viator_additional_info');
		echo isset($additional_info[0])?$additional_info[0]:'';
	}
}

if(!function_exists('viatorwp_format_location_details')){
	function viatorwp_format_location_details($location_details='')
	{
		$format_loc = array();
		$format_loc['name'] = '';
		$format_loc['address'] = '';
		if(!empty($location_details)){
			if(isset($location_details['name'])){
				$format_loc['name'] = $location_details['name'];
			}
			if(isset($location_details['address']) && !empty($location_details['address'])){
				$full_address = isset($location_details['address']['street'])?$location_details['address']['street']:'';
				$full_address .= isset($location_details['address']['administrativeArea'])?$location_details['address']['administrativeArea']:'';
				$full_address .= isset($location_details['address']['state'])?$location_details['address']['state']:'';
				$full_address .= isset($location_details['address']['country'])?$location_details['address']['country']:'';
				$format_loc['address'] = $full_address;
			}
		}
		return $format_loc;
	}
}

if(!function_exists('viatorwp_itinerary_location_details_and_description')){
	function viatorwp_itinerary_location_details_and_description($location='', $pass_by='', $address='' , $description='', $admisssion_ticket='', $itinerary_duration_mins='') {
		?>
		<div class="viatorwp-itinerary-card">
	      <div class="viatorwp-itinerary-info">
	        <span class="viatorwp-itinerary-title"><strong><?php echo esc_html($location . $pass_by); ?></strong></span>
	        <span><?php echo esc_html($address); ?></span>
	        <span style="width: 200%;"><?php echo esc_html($description); ?></span>
	        <?php 
	        $admission_ticket_text = '';
	        if($admisssion_ticket == 'YES'){
	        	$admission_ticket_text = 'Admission Ticket Included';
	        }else if($admisssion_ticket == 'NOT_APPLICABLE'){
	        	$admission_ticket_text = 'Admission Ticket Free';
	        }
	    	$duration = 0;
	    	if(!empty($itinerary_duration_mins)){
	    		$duration_time = $itinerary_duration_mins['fixedDurationInMinutes'];
	    		$hours = 0; $minutes = 0; $duration_in_text = '';
	    		if($duration_time > 0){
	    			$hours = floor($duration_time / 60);
	    			$minutes = ($duration_time % 60);
	    			if($hours > 0){
	    				$duration_in_text = $hours. esc_html__(' hour ', 'viator-integration-for-wp');
	    			}
	    			$duration_in_text .= $minutes. esc_html__(' minutes', 'viator-integration-for-wp');
	    			?>
	    				<span><?php echo esc_html($duration_in_text) . esc_html(' - '.$admission_ticket_text); ?></span>
	    			<?php
	    		}
	    	}
	        ?>
	      </div>
	    </div>
		<?php
	}
}

add_filter( 'woocommerce_product_tabs', 'viatorwp_display_product_review_count', 110 );
if(!function_exists('viatorwp_display_product_review_count')){
	function viatorwp_display_product_review_count( $tabs ) {
		$tabs['reviews']['callback'] = 'viatorwp_update_review_tab_content';	// Custom description callback
		return $tabs;
	}
}

if(!function_exists('viatorwp_update_review_tab_content')){
	function viatorwp_update_review_tab_content() {
		global $product;
		$viator_product_reviews = get_post_meta(get_the_ID(), '_viator_api_reviews', true) ? get_post_meta(get_the_ID(), '_viator_api_reviews', true) : '';
		if(isset($viator_product_reviews['reviewCountTotals']) && !empty($viator_product_reviews['reviewCountTotals'])){
			if(is_array($viator_product_reviews['reviewCountTotals'])){
				$roundoff_rating = isset($viator_product_reviews['combinedAverageRating'])?round($viator_product_reviews['combinedAverageRating'], 2):0;
				$total_rating = 5;
				$total_review_counts = isset($viator_product_reviews['totalReviews'])?$viator_product_reviews['totalReviews']:0;
			?>
			<div id="reviews" class="woocommerce-Reviews">
				<span class="viatorwp-review-heading"><?php echo esc_html__('User Rating', 'viator-integration-for-wp'); ?></span>
				<?php 
				if($roundoff_rating > 0){
					for ($i=1; $i <= floor($roundoff_rating); $i++) { 
					?>
						<span class="fa fa-star viatorwp-review-checked"></span>
					<?php
					}
				}
				?>
				<p><?php echo isset($viator_product_reviews['combinedAverageRating'])?esc_html(round($viator_product_reviews['combinedAverageRating'], 2)):''; ?> <?php echo esc_html__('average based on', 'viator-integration-for-wp'); ?> <?php echo esc_html($total_review_counts); ?> <?php esc_html__('reviews', 'viator-integration-for-wp');?></p>
				<hr style="border:3px solid #f1f1f1">

				<div class="viatorwp-review-row">
				<?php
					$background_color_array = array('1' => '#f44336', '2' => '#ff9800', '3' => '#00bcd4', '4' => '#2196F3', '5' => '#04AA6D');
					foreach (array_reverse($viator_product_reviews['reviewCountTotals']) as $r_key => $r_value) {
						if(!empty($r_value)){
							$background_color = ''; $bar_width = '0%';
							foreach ($background_color_array as $bg_key => $bg_value) {
								if($bg_key == $r_value['rating']){
									$background_color = $bg_value;
								}
							}
							if($r_value['count'] > 0 && $total_review_counts > 0){
								$bar_width = ($r_value['count'] / $total_review_counts) * 100;
								$bar_width = $bar_width."%";
							}
							$bar_style = "width: ".$bar_width."; height: 18px; background-color:".$background_color;
						?>
							<div class="viatorwp-review-side">
							    <div><?php echo isset($r_value['rating'])?esc_html($r_value['rating'].' Star'):''; ?></div>
							  </div>
							  <div class="viatorwp-review-middle">
							    <div class="viatorwp-review-bar-container">
							      <div style="<?= esc_attr($bar_style); ?>"></div>
							    </div>
							</div>
							<div class="viatorwp-review-side viatorwp-review-right">
				    			<div><?php echo esc_html($r_value['count']); ?></div>
				  			</div>
						<?php
						}
					} ?>
				</div>
			</div>
			<?php
			}
		}
		// echo comments_template();
	}
}