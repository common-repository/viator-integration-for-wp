<?php

/**
 * Viator_Admin_Csv
 * @author 		Magazine3
 * This class is used to retrieve CSV file contents
 */

if(!function_exists('viatorwp_add_external_product')){
    function viatorwp_add_external_product($product_details)
    {
        if(class_exists('WC_Product_External')){
            $external_product = new WC_Product_External();
            $slug = $product_details['productCode'];
            $external_product->set_name($product_details['title']);
            $external_product->set_slug($slug);
            $external_product->set_description($product_details['description']);
            $external_product->set_product_url($product_details['productUrl']);
            $external_product->set_button_text('Book On Viator');
            $external_product->set_category_ids($product_details['categoryId']);
            // $external_product->set_sku($product_details['productCode']);

            // Check if product already exist
            global $wpdb;
            $product_exist = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type='product'", (strtolower($slug) )));

            if(empty($product_exist)){
                $external_product->save();

                // Update flag value when product updated in woocommerce
                $table_name = $wpdb->prefix."viatorwp_uploaded_products";
                $wpdb->update($table_name, array('flag' => 1), array('product_code' => sanitize_text_field($product_details['productCode'])), array('%d'), array('%s'));

                if(!empty($external_product->get_id())){
                    $product_id = $external_product->get_id();
                    if(isset($product_details['images']) && !empty($product_details['images'])){
                        $product_code = $product_details['productCode'];
                        viatorwp_upload_product_images($product_id, $product_details['images'], $product_code);
                    }
                    viatorwp_ie_format_products_tab_content($product_id, $product_details['inclusions'], $product_details['exclusions'], $product_details['cancellationPolicy']);

                    if(isset($product_details['inclusions']) && !empty($product_details['inclusions'])){
                        $sanitized_inclusion = viatorwp_sanitize_associative_array( $product_details['inclusions']);
                        add_post_meta( $product_id, '_viator_api_inclusions', $sanitized_inclusion);
                    }

                    if(isset($product_details['exclusions']) && !empty($product_details['exclusions'])){
                        $sanitized_exclusion = viatorwp_sanitize_associative_array( $product_details['exclusions']);
                        add_post_meta( $product_id, '_viator_api_exclusions', $sanitized_exclusion);
                    }

                    if(isset($product_details['cancellationPolicy']) && !empty($product_details['cancellationPolicy'])){
                        add_post_meta( $product_id, '_viator_api_cancellation_policy', wp_unslash($product_details['cancellationPolicy']));
                    }

                    if(isset($product_details['additionalInfo']) && !empty($product_details['additionalInfo'])){
                        viatorwp_addinfo_format_products_tab_content($product_id, $product_details['additionalInfo']);
                        $sanitized_additional_info = viatorwp_sanitize_associative_array( $product_details['additionalInfo']);
                        add_post_meta( $product_id, '_viator_api_additional_info', $sanitized_additional_info);
                    }

                    viatorwp_wte_format_products_tab_content($product_id, $product_details['itinerary']);
                    add_post_meta( $product_id, '_viator_api_itinerary', wp_unslash($product_details['itinerary']));

                    add_post_meta( $product_id, '_viator_meeting_and_pickup', wp_unslash($product_details['logistics']));
                    add_post_meta( $product_id, '_viator_product_code', sanitize_text_field($product_details['productCode']));
                    add_post_meta( $product_id, '_viator_product_title', sanitize_text_field($product_details['title']));
                    add_post_meta( $product_id, '_viator_product_content', sanitize_text_field($product_details['description']));
                    if(isset($product_details['reviews']) && !empty($product_details['reviews'])){
                        add_post_meta( $product_id, '_viator_api_reviews', wp_unslash($product_details['reviews']));
                        
                        if(isset($product_details['reviews']['reviewCountTotals']) && !empty($product_details['reviews']['reviewCountTotals'])){
                            if(is_array($product_details['reviews']['reviewCountTotals'])){
                                $roundoff_rating = isset($product_details['reviews']['combinedAverageRating'])?round($product_details['reviews']['combinedAverageRating'], 2):0;
                                $total_rating = 5;
                                $total_review_counts = isset($product_details['reviews']['totalReviews'])?$product_details['reviews']['totalReviews']:0;

                                if(get_post_meta($product_id, '_wc_review_count')){
                                    update_post_meta( $product_id, '_wc_review_count', intval($total_review_counts));
                                }else{
                                    add_post_meta($product_id, '_wc_review_count', intval($total_review_counts));
                                }

                                if(get_post_meta($product_id, '_wc_rating_count')){
                                    update_post_meta( $product_id, '_wc_rating_count', intval($total_review_counts));
                                }else{
                                    add_post_meta($product_id, '_wc_rating_count', intval($total_review_counts));
                                }

                                if(get_post_meta($product_id, '_wc_average_rating')){
                                    update_post_meta( $product_id, '_wc_average_rating', floatval($roundoff_rating));
                                }else{
                                    add_post_meta($product_id, '_wc_average_rating', floatval($roundoff_rating));
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

if(!function_exists('viatorwp_upload_product_images')){
    function viatorwp_upload_product_images($id='', $images='', $product_code='')
    {
        if(!empty($id) && !empty($images)){
            $attachment_id = array();
            if(is_array($images)){
                $image_cnt = 1;
                foreach ($images as $pi_key => $pi_value) {
                    if(isset($pi_value['variants']) && is_array($pi_value['variants'])){
                        foreach ($pi_value['variants'] as $vari_key => $vari_value) {
                            if($vari_value['height'] == 446 && $vari_value['width'] == 674){
                                $image_url = ''; $image_name = ''; $post_name = '';
                                $image_url = $vari_value['url'];
                                $image_mime = wp_get_image_mime($image_url);
                                $mime_type = '';
                                if($image_mime){
                                    $mime_type = explode('/', $image_mime)[1];
                                }
                                if($pi_value['isCover']){ 
                                    $post_name =  'Product-'. $product_code .'-' . $image_cnt;
                                    $image_name = $post_name.'.'.$mime_type; 
                                }else{
                                    $post_name =  'Product-Gallery'. $product_code .'-' . $image_cnt;
                                    $image_name = $post_name.'.'.$mime_type;  
                                }
                                $attachment_id[] = viatorwp_save_image_to_wp_uploads($image_name, $image_url, $id, $post_name, $image_mime);
                            }
                        }
                        $image_cnt++;
                    }
                }
            }
            if(count($attachment_id) > 0){
                set_post_thumbnail( $id, $attachment_id[0]);
                array_shift($attachment_id);
                $implode_attachment_id = sanitize_text_field(implode(',', $attachment_id));
                update_post_meta($id, '_product_image_gallery', $implode_attachment_id);
            }
        }
    }
}

if(!function_exists('viatorwp_save_image_to_wp_uploads')){
    function viatorwp_save_image_to_wp_uploads($image_name='', $image_url='', $id='', $post_name='', $image_mime=''){
        if(!empty($image_name) && !empty($image_url)){
            require_once( ABSPATH . 'wp-admin/includes/file.php' );

            // download to temp dir
            $temp_file = download_url( $image_url );

            if( is_wp_error( $temp_file ) ) {
                return false;
            }

            // move the temp file into the uploads directory
            $file = array(
                'name'     => $image_name,
                'type'     => $image_mime,
                'tmp_name' => $temp_file,
                'size'     => filesize( $temp_file ),
            );
            $sideload = wp_handle_sideload(
                $file,
                array(
                    'test_form'   => false // no needs to check 'action' parameter
                )
            );

            if( ! empty( $sideload[ 'error' ] ) ) {
                // you may return error message if you want
                return false;
            }

            // it is time to add our uploaded image into WordPress media library
            $attachment_id = wp_insert_attachment(
                array(
                    'guid'           => sanitize_url($sideload[ 'url' ]),
                    'post_mime_type' => sanitize_text_field($sideload[ 'type' ]),
                    'post_title'     => sanitize_text_field($post_name),
                    'post_content'   => '',
                    'post_status'    => 'inherit',
                    'post_type' => 'attachment',
                ),
                $sideload[ 'file' ], 
                $id
            );

            if( is_wp_error( $attachment_id ) || ! $attachment_id ) {
                return false;
            }

            // update medatata, regenerate image sizes
            require_once( ABSPATH . 'wp-admin/includes/image.php' );

            wp_update_attachment_metadata(
                $attachment_id,
                wp_generate_attachment_metadata( $attachment_id, $sideload[ 'file' ] )
            );
            return $attachment_id;
        }
    }
}

if(!function_exists('viatorwp_update_external_product')){
    function viatorwp_update_external_product($product_details, $product_id)
    {
        if(!empty($product_details) && !empty($product_id)){
            $update_post_array = array();
            $product_post_meta_details = get_post_meta($product_id);
            $product_post_data = get_post($product_id);
            if(!empty($product_details['title']) && isset($product_details['title'])){
                $update_post_array['post_title'] = sanitize_text_field(str_replace($product_post_meta_details['_viator_product_title'][0], $product_details['title'], $product_post_data->post_title));
            }
            if(!empty($product_details['description']) && isset($product_details['description'])){
                $update_post_array['post_content'] = sanitize_text_field(str_replace($product_post_meta_details['_viator_product_content'][0], $product_details['description'], $product_post_data->post_content));
            }
            if(!empty($update_post_array)){
                $update_post_array['ID'] = intval($product_id);
                wp_update_post( $update_post_array );
            }
        }
    }
}

add_filter( 'post_row_actions', 'viatorwp_modify_list_row_actions', 20, 2 );

if(!function_exists('viatorwp_modify_list_row_actions')){
    function viatorwp_modify_list_row_actions( $actions, $post ) {
        if ( $post->post_type == "product" ) {
            $viator_row_action = array('viator' => sprintf( '<a href="%1$s" id="%2$s" data-product-id="%3$s">%4$s</a>',
                    esc_url('#'), 
                    esc_html('viator-product-id', 'viator-integration-for-wp'), 
                    esc_attr($post->ID),
                    esc_html__('Update Viator Data', 'viator-integration-for-wp')
                ) );
            $actions = array_merge($actions, $viator_row_action);
        }
        return $actions;
    }
}

if(!function_exists('viatorwp_ie_format_products_tab_content')){
    function viatorwp_ie_format_products_tab_content($product_id, $product_inclusions=array(), $product_exclusions=array(), $product_cancellation_policy=array()) {
        $formatted_tab_content = '';
        $formatted_tab_content = viatorwp_format_product_inclusions($product_inclusions);
        $formatted_tab_content .= viatorwp_format_product_exclusions($product_exclusions);
        $formatted_tab_content .= viatorwp_format_product_cancellation_policy($product_cancellation_policy);

        add_post_meta( $product_id, '_viator_inclusions', wp_kses_post($formatted_tab_content));
    }
}

if(!function_exists('viatorwp_addinfo_format_products_tab_content')){
    function viatorwp_addinfo_format_products_tab_content($product_id, $product_add_info=array())
    {
        $additional_tab_content = '';
        if(!empty($product_add_info) && isset($product_add_info)){
            if(is_array($product_add_info)){
                $additional_tab_content .= '<ul>';
                foreach ($product_add_info as $ai_key => $ai_value) {
                    $additional_tab_content .= isset($ai_value['description'])? '<li>'.esc_html($ai_value['description']).'</li>':'';
                }
                $additional_tab_content .= '</ul>';
            } 
        }
        add_post_meta( $product_id, '_viator_additional_info', wp_kses_post($additional_tab_content));
    }
}

if(!function_exists('viatorwp_format_product_inclusions')){
    function viatorwp_format_product_inclusions($product_inclusions = array())
    {
        $inclusions_tab_content = '';
        $inclusions_tab_content .= '<h5> '.esc_html__('Inclusions', 'viator-integration-for-wp').' </h5>';
        if(!empty($product_inclusions)){
            if(is_array($product_inclusions)){
                $inclusions_tab_content .= '<ul>';
                    foreach ($product_inclusions as $pkey1 => $pvalue1) {
                        if(isset($pvalue1['otherDescription'])){
                            $inclusions_tab_content .= '<li>'.esc_html($pvalue1['otherDescription']).'</li>';
                        }else if(isset($pvalue1['description'])){
                            $inclusions_tab_content .= '<li>'.esc_html($pvalue1['description']).'</li>';
                        }
                    }
                $inclusions_tab_content .= '</ul>';
            }
        }
        return $inclusions_tab_content;
    }
}

if(!function_exists('viatorwp_format_product_exclusions')){
    function viatorwp_format_product_exclusions($product_exclusions = array())
    {
        $exclusions_tab_content = '';
        if(!empty($product_exclusions)){
            if(is_array($product_exclusions)){
                $exclusions_tab_content .= '<h5>'.esc_html__('Exclusions', 'viator-integration-for-wp').'</h5>';
                    $exclusions_tab_content .= '<ul>';
                        foreach ($product_exclusions as $pkey1 => $pvalue1) {
                            if(isset($pvalue1['otherDescription'])){
                                $exclusions_tab_content .= '<li>'.esc_html($pvalue1['otherDescription']).'</li>';
                            }else if(isset($pvalue1['description'])){
                                $exclusions_tab_content .= '<li>'.esc_html($pvalue1['description']).'</li>';
                            }
                        }
                    $exclusions_tab_content .= '</ul>';

            }
        }
        return $exclusions_tab_content;
    }
}

if(!function_exists('viatorwp_format_product_cancellation_policy')){
    function viatorwp_format_product_cancellation_policy($product_cancellation_policy=array())
    {
        $cancellation_policy_content = '';
        if(!empty($product_cancellation_policy) && isset($product_cancellation_policy)){
            $cancellation_policy_content .= '<h5>'.esc_html__('Cancellation Policy', 'viator-integration-for-wp').'</h5>';
            $cancellation_policy_content .= '<ul>';
            $cancellation_policy_content .= isset($product_cancellation_policy[0]['description'])? '<li>'.esc_html($product_cancellation_policy['description']).'</li>':'';
            if(isset($product_cancellation_policy['refundEligibility']) && is_array($product_cancellation_policy['refundEligibility'])){
                foreach ($product_cancellation_policy['refundEligibility'] as $re_key => $re_value) {
                    $days = isset($re_value['dayRangeMin'])?$re_value['dayRangeMin']:'';
                    $hours = ''; $cancel_policy_text = '';
                    if($days > 0){
                        $hours = $days * 24;
                        if(isset($re_value['percentageRefundable'])){
                            $cancel_policy_text = 'Cancellation done after '.$hours.' hours '.$re_value['percentageRefundable'].'% amount will be refunded';
                        }
                    }else{
                        if($days == 0){
                            $hours = 24;
                        }
                        if(isset($re_value['percentageRefundable'])){
                            $refund = 'No Refund';
                            if($re_value['percentageRefundable'] != 0){
                                $refund = $re_value['percentageRefundable'].'% ';
                            }
                            $cancel_policy_text = 'Cancellation done before '.$hours.' hours '.$refund.' amount will be refunded';
                        }
                    }   
                    $cancellation_policy_content .= '<li>'.esc_html($cancel_policy_text).'</li>';
                }
            }
            $cancellation_policy_content .= '</ul>';
        }
        return $cancellation_policy_content;
    }
}

if(!function_exists('viatorwp_wte_format_products_tab_content')){
    function viatorwp_wte_format_products_tab_content($product_id, $product_itinerary=array())
    {
        $product_itinerary_content = '';
        ob_start();
        if(!empty($product_itinerary) && isset($product_itinerary)){
            if(isset($product_itinerary['itineraryType']) && $product_itinerary['itineraryType'] != 'MULTI_DAY_TOUR'){
                if(isset($product_itinerary['itineraryItems']) && is_array($product_itinerary['itineraryItems'])){      
                ?>
                    <div class="itinerary-timeline">
                        <div class="viatorwp-itinerary-outer">
                <?php
                            $loc = array(); $location_details = array();
                            foreach ($product_itinerary['itineraryItems'] as $ii_key => $ii_value) {
                                if(isset($ii_value['pointOfInterestLocation']) && isset($ii_value['pointOfInterestLocation']['location'])){
                                        $loc[] = $ii_value['pointOfInterestLocation']['location']['ref'];
                                    }
                            }
                            if(!empty($loc)){
                                $location_details = viatorwp_get_location_details($loc);
                                if(empty(json_decode($location_details, true))){
                                    $location_details = gzdecode($location_details);
                                }
                                if(!empty($location_details)){
                                    $location_details = json_decode($location_details,true);
                                }
                            }
                            foreach ($product_itinerary['itineraryItems'] as $pi_key => $pi_value) {
                                if(isset($pi_value['pointOfInterestLocation']) && isset($pi_value['pointOfInterestLocation']['location'])){
                                    if(isset($pi_value['pointOfInterestLocation']['location']['ref'])){
                                        if(isset($location_details) && !empty($location_details['locations'])){
                                            if(is_array($location_details['locations'])){
                                                foreach ($location_details['locations'] as $ldl_key => $ldl_value) {
                                                    if(!empty($ldl_value)){
                                                        if($ldl_value['reference'] == $pi_value['pointOfInterestLocation']['location']['ref']){
                                                            $formatted_location_resp = viatorwp_format_location_details($ldl_value);
                                                            if(!empty($formatted_location_resp['name'])){
                                                                $product_itinerary['itineraryItems'][$pi_key]['location_name'] = $formatted_location_resp['name'];
                                                            }
                                                            if(!empty($formatted_location_resp['address'])){
                                                                $product_itinerary['itineraryItems'][$pi_key]['location_address'] = $formatted_location_resp['address'];
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            foreach ($product_itinerary['itineraryItems'] as $ii_key => $ii_value) {
                                if(!empty($ii_value)){
                                    $pass_by = isset($ii_value['passByWithoutStopping'])?$ii_value['passByWithoutStopping']:'';
                                    $location = ''; $address = '';
                                    if(isset($ii_value['location_name']) && isset($ii_value['location_name'])){
                                        $location = $ii_value['location_name'];
                                        $address = $ii_value['location_address'];
                                    }
                                    if($pass_by){
                                        $pass_by = '(Pass By)'; 
                                    }
                                    $description = isset($ii_value['description'])?$ii_value['description']:'';
                                    $admisssion_ticket = isset($ii_value['admissionIncluded'])?$ii_value['admissionIncluded']:'';

                                    $itinerary_duration_mins = isset($ii_value['duration'])?$ii_value['duration']:'';

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
                        ?>
                        </div> <!-- outer div end -->
                    </div> <!-- timeline div end -->
                    <?php
                }
            }else if(isset($product_itinerary['itineraryType']) && $product_itinerary['itineraryType'] === 'MULTI_DAY_TOUR'){
                if(isset($product_itinerary['days']) && !empty($product_itinerary['days'])){
                    if(is_array($product_itinerary['days'])){
                        foreach ($product_itinerary['days'] as $ptd_key => $ptd_value) {
                            if(isset($ptd_value['items'])){
                                if(is_array($ptd_value['items'])){
                                    foreach ($ptd_value['items'] as $ptdi_key => $ptdi_value) {
                                        $formatted_location_resp = array();
                                        if(isset($ptdi_value['pointOfInterestLocation']) && isset($ptdi_value['pointOfInterestLocation']['location'])){
                                            if(isset($ptdi_value['pointOfInterestLocation']['location']['ref'])){
                                                $location_details = viatorwp_get_location_details(array($ptdi_value['pointOfInterestLocation']['location']['ref']));
                                                if(!empty($location_details)){
                                                    $location_details = json_decode($location_details,true);
                                                }
                                                if(!empty($location_details['locations']) && isset($location_details['locations'])){
                                                    if(is_array($location_details['locations'])){
                                                        foreach ($location_details['locations'] as $ldl_key => $ldl_value) {
                                                            $formatted_location_resp[] = viatorwp_format_location_details($ldl_value);
                                                        }
                                                    }
                                                }
                                                $pass_by = isset($ptdi_value['passByWithoutStopping'])?$ptdi_value['passByWithoutStopping']:'';
                                                if(empty($pass_by)){
                                                    $pass_by = '1 Stop';
                                                }
                                                $description = isset($ptdi_value['description'])?$ptdi_value['description']:'';
                                                $admisssion_ticket = isset($ptdi_value['admissionIncluded'])?$ptdi_value['admissionIncluded']:'';
                                                $itinerary_duration_mins = isset($ptdi_value['duration'])?$ptdi_value['duration']:'';
                                                ?>
                                                    <p><strong><?php echo esc_html__('Day ', 'viator-integration-for-wp'). esc_html($ptd_value['dayNumber']); ?></strong></p>
                                                    <h5><?php echo esc_html($ptd_value['title']); ?></h5>
                                                    <p><?php echo esc_html($pass_by); ?></p>
                                                    <div style="margin-left: 40px;">
                                                        <?php
                                                        if(isset($formatted_location_resp) && is_array($formatted_location_resp)){
                                                            foreach ($formatted_location_resp as $flr_key => $flr_value) {
                                                            ?>
                                                                <p><strong><?php echo esc_html($flr_value['name']); ?></strong></p>
                                                                <p><?php echo esc_html($flr_value['address']); ?></p>
                                                                <?php
                                                                if(isset($ptdi_value['description']) && !empty($ptdi_value['description'])){
                                                                ?>
                                                                    <p><?php echo esc_html($ptdi_value['description']); ?></p>
                                                                <?php   
                                                                }
                                                            }
                                                        }
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
                                                                echo '<span>'. esc_html($duration_in_text) .'</span>'; 
                                                            }
                                                        }
                                                        echo '<span> '. esc_html($admission_ticket_text) .'</span>'; 
                                                        ?>
                                                    </div>
                                                <?php
                                            }
                                        }
                                    } // ptd_value['items'] foreach end
                                } // ptd_value['items'] if end
                            } // ptd_value['items'] if end
                            if(isset($ptd_value['accommodations']) && !empty($ptd_value['accommodations'])){
                                if(is_array($ptd_value['accommodations'])){
                                    foreach ($ptd_value['accommodations'] as $pva_key => $pva_value) {
                                    ?>
                                        <p style="margin-left: 40px;"><strong><?php echo esc_html__('Accommodation: ', 'viator-integration-for-wp'); ?></strong><?php 
                                        if(isset($pva_value['description'])){
                                            echo esc_html($pva_value['description']);
                                        }
                                        ?></p>
                                    <?php   
                                    }
                                }
                            }
                            if(isset($ptd_value['foodAndDrinks']) && !empty($ptd_value['foodAndDrinks'])){
                                if(is_array($ptd_value['foodAndDrinks'])){
                                    foreach ($ptd_value['foodAndDrinks'] as $pvfd_key => $pvfd_value) {
                                    ?>
                                        <p style="margin-left: 40px;"><strong><?php echo esc_html__('Meals: ', 'viator-integration-for-wp'); ?></strong><?php 
                                        if(isset($pvfd_value['typeDescription'])){
                                            echo esc_html($pvfd_value['typeDescription']);
                                        }
                                        ?></p>
                                    <?php
                                    }
                                }
                            }
                        } // product_itinerary[0]['days'] foreach end
                    } // product_itinerary[0]['days'] foreach end
                } // product_itinerary[0]['days'] if end 
            } // else if end
        }
        $itinerary_html = ob_get_contents();
        ob_end_flush();
        add_post_meta( $product_id, '_viator_itinerary', wp_kses_post($itinerary_html));
    }
}

if(!function_exists('viatorwp_sanitize_associative_array')){
    function viatorwp_sanitize_associative_array($sanitize_array)
    {
        if(!empty($sanitize_array) && is_array($sanitize_array)){
            foreach ($sanitize_array as $sa_key => $sa_value) {
                $array_contents = array();
                foreach ($sa_value as $saa_key => $saa_value) {
                    $sa_value[$saa_key] = sanitize_text_field($saa_value);
                }
                $sanitize_array[$sa_key] = $sa_value;
            }
        }
        return $sanitize_array;
    }
}