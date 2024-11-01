<?php 
/** 
 * Function to add menus to wordpress dashboard
 * **/
require_once VIATORWP_PLUGIN_DIR.'admin/viator_admin_subpages.php';
require_once VIATORWP_PLUGIN_DIR.'admin/viator_admin_woocommerce.php';
require_once VIATORWP_PLUGIN_DIR.'admin/viator_admin_api.php';

add_action('admin_enqueue_scripts', 'viatorwp_admin_script_and_styles');

if(!function_exists('viatorwp_admin_script_and_styles')){
	function viatorwp_admin_script_and_styles()
	{
		wp_register_style('viatorwp-admin-css', VIATORWP_PLUGIN_URL.'assets/admin/css/viator-admin.css', array(), VIATORWP_VERSION);
		wp_enqueue_style('viatorwp-admin-css');
	}
}

add_action('admin_menu', 'viatorwp_option_menu', 1);

if(!function_exists('viatorwp_option_menu')){
	function viatorwp_option_menu() {
		add_menu_page('Viator Integration For WP', 'Viator For WP', 'manage_options', 'viator-integration-for-wp', 'viatorwp_options_page');
	}
}

if(!function_exists('viatorwp_options_page')){
	function viatorwp_options_page(){
		echo '<h1>'.esc_html__('Viator Integration For WP', 'viator-integration-for-wp').'</h1>';
		$vas_class_obj = new Viatorwp_Admin_Subpages();
		$vas_class_obj->viatorwp_add_subpage('Upload',  'viator_upload_settings', 'viatorwp_upload_options_setting');
		$vas_class_obj->viatorwp_add_subpage('API Credentials',  'viator_api_settings', 'viatorwp_api_options_setting');
		$vas_class_obj->viatorwp_add_subpage('Support',  'viator_plugin_support', 'viatorwp_support_options_setting');
		$vas_class_obj->viatorwp_display();
	}
}

add_action('admin_notices', 'viatorwp_display_admin_notice');
if(!function_exists('viatorwp_display_admin_notice')){
	function viatorwp_display_admin_notice(){
		if(!class_exists( 'WooCommerce' )){
			echo '<div class="notice notice-info is-dismissible">
	         <p>'.esc_html__('Viator Integration For WP requires Woocommerce plugin to be installed. Please install ','viator-integration-for-wp').'<a href="'.esc_url('https://wordpress.org/plugins/woocommerce/').'" target="_blank">'. esc_html__('Woocommerce', 'viator-integration-for-wp') .'</a></p>
	     	</div>';
		}

	 	if(!vaitorwp_validate_api_credentials()){
	 		echo '<div class="notice notice-info is-dismissible"><p>'.esc_html__('Please enter API key', 'viator-integration-for-wp').'</p></div>';
	 	}
	     
	}
}

if(!function_exists('viatorwp_upload_options_setting')){
	function viatorwp_upload_options_setting()
	{
		$sample_csv_file = VIATORWP_PLUGIN_URL.'assets/media/viator_sample_csv_format.csv';
		if (isset($_POST['update_options'])) {
			if ( ! isset( $_POST['viatorwp_setting_nonce'] ) || ! wp_verify_nonce( $_POST['viatorwp_setting_nonce'], 'viatorwp_setting_nonce' ) ){
				return;
			}
			if(current_user_can( 'manage_options' )){
				if(isset($_FILES['vas_csv_file'])){
					$uploadedfile = $_FILES['vas_csv_file'];
					$file_mime_type = wp_check_filetype($uploadedfile['name']);
					if(isset($file_mime_type['ext']) && !empty($file_mime_type['ext'])){
						if($file_mime_type['ext'] == 'csv'){
			    			$upload_overrides = array( 'test_form' => false );
							$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
						    if ( $movefile && ! isset( $movefile['error'] ) ) {
						    	$viator_data = array();
						    	if(get_option('viator_data')){
						    		$viator_data = get_option('viator_data');
						    	}
						    	$viator_data['vas_csv_path'] = sanitize_text_field($movefile['file']);
						    	$viator_data['vas_csv_url'] = esc_url($movefile['url']);
						    	$viator_data['vas_csv_type'] = sanitize_text_field($movefile['type']);
			       				update_option('viator_data', $viator_data);

								$csv_response = viatorwp_get_csv_file_data();
								if($csv_response['status']){
									echo '<div class="notice notice-success is-dismissible"><p><b>' . esc_html__('File uploaded successfully', 'viator-integration-for-wp') . '</b></p></div>';
								}else{
									echo '<div class="notice notice-error is-dismissible"><p><b>' . esc_html($csv_response['message']) . '</b></p></div>';
								}
						    }
						}else{
							echo '<div class="notice notice-error is-dismissible"><p><b>' . esc_html__('Kindly upload CSV file only.', 'viator-integration-for-wp') . '</b></p></div>';
						}
					}
				}
			}
		}
		
	?>
		<div class="wrap viatorwp-tab-content viatorwp-row">
			<div class="viatorwp-col">
				<form method="post" action="" enctype='multipart/form-data'>
					<div class="viatorwp_settings_div">
						<h3><?php echo esc_html__('Upload CSV', 'viator-integration-for-wp') ?></h3>
						<ul>
							<li>
								<div>
			                        <input type="file" name="vas_csv_file" >                     
			                        <p><a href="<?php echo esc_url($sample_csv_file); ?>"><?php echo esc_html__('Click here to download sample CSV Format', 'viator-integration-for-wp') ?></a></p>
			                    </div>
							</li>
						</ul>
					</div>
					<?php 
					if ( class_exists( 'WooCommerce' ) && vaitorwp_validate_api_credentials()) {
						$viator_data = get_option('viator_data');
					?>
						<div class="submit"><input type="submit" class="button button-primary" name="update_options" value="<?php echo esc_html__('Upload', 'viator-integration-for-wp') ?>" /></div>
					<?php } ?>
					<?php  wp_nonce_field( 'viatorwp_setting_nonce', 'viatorwp_setting_nonce' ); ?>
				</form>
			</div>

			<?php 
			global $wpdb;
			$total_product = 0;
			$table_name = $wpdb->prefix. "viatorwp_uploaded_products";
			$is_table_exist_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );
			$db_table_name = $wpdb->get_var($is_table_exist_query);
			if(!empty($db_table_name) && $db_table_name == $table_name){
				$query = "SELECT count(id) FROM $table_name";
				$total_product = $wpdb->get_var($query);
			}

			$total_imported_product = 0;
			$is_table_exist_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );
			$db_table_name = $wpdb->get_var($is_table_exist_query);
			if(!empty($db_table_name) && $db_table_name == $table_name) {
				$flag_val = 1;
				$query = $wpdb->prepare("SELECT count(id) FROM $table_name WHERE flag = %d", $flag_val);
				$total_imported_product = $wpdb->get_var($query);
			}

			$total_imported_failed = 0;
			$is_table_exist_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );
			$db_table_name = $wpdb->get_var($is_table_exist_query);
			if(!empty($db_table_name) && $db_table_name == $table_name) {
				$flag_val = 2;
				$query = $wpdb->prepare("SELECT count(id) FROM $table_name WHERE flag = %d", $flag_val);
				$total_imported_failed = $wpdb->get_var($query);
			}
			$pending_products = $total_product - $total_imported_product - $total_imported_failed;
			?>
			<?php 
			if($total_product > 0){
			?>
				<div class="viatorwp-col">
					<h3 style="text-align:center"><?php esc_html__('Import Progress', 'viator-integration-for-wp') ?></h3>
					<table style="margin: auto; border: solid;">
						<tr>
							<td class="viatorwp-td-style"><strong><?php echo esc_html__('Total Products', 'viator-integration-for-wp'); ?></strong></td>
							<td><?php echo esc_html($total_product); ?></td>
						</tr>
						<tr>
							<td class="viatorwp-td-style"><strong><?php echo esc_html__('Imported', 'viator-integration-for-wp'); ?></strong></td>
							<td><?php echo esc_html($total_imported_product) ?></td>
						</tr>
						<tr>
							<td class="viatorwp-td-style"><strong><?php echo esc_html__('In Progress', 'viator-integration-for-wp'); ?></strong></td>
							<td><?php echo esc_html($pending_products) ?></td>
						</tr>
						<tr>
							<td class="viatorwp-td-style"><strong><?php echo esc_html__('Failed', 'viator-integration-for-wp'); ?></strong></td>
							<td><?php echo esc_html($total_imported_failed) ?></td>
						</tr>
					</table>
					<?php 
					$import_status = esc_html__('Completed', 'viator-integration-for-wp');
					if($pending_products > 0){
						$import_status = esc_html__('In Progress', 'viator-integration-for-wp');
					}
					?>
					<h4 style="text-align:center"><?php echo esc_html__('Import Status: ', 'viator-integration-for-wp') . $import_status; ?></h4>
				</div>
			<?php
			}
			?>
		</div>	
	<?php
	}
}

if(!function_exists('viatorwp_api_options_setting')){
	function viatorwp_api_options_setting(){
		$viator_data = get_option('viator_data');
		if (isset($_POST['update_options'])) {
			if (!isset( $_POST['viatorwp_api_cred_nonce'] ) || ! wp_verify_nonce( $_POST['viatorwp_api_cred_nonce'], 'viatorwp_api_cred_nonce' ) ){
				return;
			} 
			if(current_user_can( 'manage_options' )){
				if(isset($_POST['api_key']) && !empty($_POST['api_key'])){
					$viator_data = array();
			    	if(get_option('viator_data')){
			    		$viator_data = get_option('viator_data');
			    	}
					$viator_data['api_key'] = sanitize_text_field($_POST['api_key']);
					$viator_data['api_end_point_url'] = VIATORWP_API_ENDPOINT;
					update_option('viator_data', $viator_data);
				}else{
					echo '<div class="notice notice-error is-dismissible"><p><b>' . esc_html__('Please enter valid API credentials','viator-integration-for-wp') . '</b></p></div>';
				}
			}
		} 
		?>
		<div class="wrap viatorwp-tab-content">
			<form method="post" action="">
				<div class="viatorwp_settings_div">
					<table>
						<tr>
							<td style="width: 200px;"><strong><?php echo esc_html__('API Key', 'viator-integration-for-wp'); ?></strong></td>
							<td><input type="text" placeholder="<?php echo esc_attr('Enter API Key'); ?>" name="api_key" value="<?php echo isset($viator_data['api_key'])?esc_attr($viator_data['api_key']):''; ?>"/></td>
						</tr>
					</table>
				</div>
				<div class="submit"><input type="submit" class="button button-primary" name="update_options" value="<?php echo esc_html__('Update Credentials', 'viator-integration-for-wp') ?>" /></div>
					<?php  wp_nonce_field( 'viatorwp_api_cred_nonce', 'viatorwp_api_cred_nonce' ); ?>
			</form>
		</div>
	<?php
	}
}

if(!function_exists('vaitorwp_validate_api_credentials')){
	function vaitorwp_validate_api_credentials(){
		$viator_data = get_option('viator_data');
	    if(isset($viator_data['api_key']) && !empty($viator_data['api_key'])){
	    	return true;
	    }else{
	    	return false;
	    }
	}
}

if(!function_exists('viatorwp_support_options_setting')){
	function viatorwp_support_options_setting()
	{
	?>
		<div class="wrap viatorwp-tab-content">
			<div class="viatorwp_settings_div">
	            <strong><?php echo esc_html__('If you have any query, please write the query in below box or email us at', 'viator-integration-for-wp') ?> <a href="<?php esc_attr('mailto:team@magazine3.in'); ?>"><?php echo esc_html__('team@magazine3.in', 'viator-integration-for-wp'); ?></a>. <?php echo esc_html__('We will reply to your email address shortly', 'viator-integration-for-wp') ?></strong>
	       
	            <ul>
	                <li>
	                   <input type="text" id="viatorwp_query_email" name="viatorwp_query_email" placeholder="email">
	                </li>
	                <li>                    
	                    <div><textarea rows="5" cols="60" id="viatorwp_query_message" name="viatorwp_query_message" placeholder="Write your query"></textarea></div>
	                    <span class="viatorwp-query-success viatorwp_hide"><?php echo esc_html__('Message sent successfully, Please wait we will get back to you shortly', 'viator-integration-for-wp'); ?></span>
	                    <span class="viatorwp-query-error viatorwp_hide"><?php echo esc_html__('Message not sent. please check your network connection', 'viator-integration-for-wp'); ?></span>
	                </li>
	                <li><button class="button viatorwp-send-query"><?php echo esc_html__('Send Message', 'viator-integration-for-wp'); ?></button></li>
	            </ul>                
	        </div>
	    </div>
	<?php
	}
}

add_action('wp_ajax_viatorwp_send_query_message', 'viatorwp_send_query_message');

/**
 * This is a ajax handler function for sending email from user admin panel to us. 
 * @return type json string
 */
	if(!function_exists('viatorwp_send_query_message')){
	function viatorwp_send_query_message(){   
	    if ( ! isset( $_POST['viatorwp_setting_security_nonce'] ) ){
	       return; 
	    }
	    if ( !wp_verify_nonce( $_POST['viatorwp_setting_security_nonce'], 'vas_ajax_check_nonce' ) ){
	       return;  
	    }
		if((isset($_POST['email']) && !empty($_POST['email'])) && (isset($_POST['message']) && !empty($_POST['message']))){   
		    $message        = sanitize_text_field($_POST['message']); 
		    $email          = sanitize_text_field($_POST['email']);   
		                          
		    if(function_exists('wp_get_current_user')){

		        $user           = wp_get_current_user();
		     
		        $message = '<p>'.esc_html($message).'</p><br><br><br><br> Query' . esc_html__(' from plugin support tab', 'viator-integration-for-wp');
		        
		        $user_data  = $user->data;        
		        $user_email = $user_data->user_email;     

		        if($email){
		            $user_email = $email;
		        }            
		        //php mailer variables        
		        $sendto    = 'team@magazine3.in';
		        $subject   = "Viator For WP Customer Query";
		        
		        $headers[] = 'Content-Type: text/html; charset=UTF-8';
		        $headers[] = 'From: '. esc_attr($user_email);            
		        $headers[] = 'Reply-To: ' . esc_attr($user_email);

		        // Load WP components, no themes.                      
		        $sent = wp_mail($sendto, $subject, $message, $headers); 

		        if($sent){
		             echo json_encode(array('status'=>'t'));  
		        }else{
		            echo json_encode(array('status'=>'f'));            
		        }
		    } 
		}                 
	    wp_die();           
	}
}
?>