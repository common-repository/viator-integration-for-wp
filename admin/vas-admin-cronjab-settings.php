<?php
add_filter( 'cron_schedules', 'viatorwp_isa_add_every_thirty_seconds' );

if(!function_exists('viatorwp_isa_add_every_thirty_seconds')){
	function viatorwp_isa_add_every_thirty_seconds( $schedules ) {
	    $schedules['every_thirty_seconds'] = array(
	            'interval'  => 30,
	            'display'   => esc_html__( 'Every 30 Seconds', 'viator-integration-for-wp' )
	    );
	    return $schedules;
	}
}

// Schedule an action if it's not already scheduled
if ( ! wp_next_scheduled( 'viatorwp_isa_add_every_thirty_seconds' ) ) {
    wp_schedule_event( time(), 'every_thirty_seconds', 'viatorwp_isa_add_every_thirty_seconds' );
}

// Hook into that action that'll fire every thirty seconds
add_action( 'viatorwp_isa_add_every_thirty_seconds', 'viatorwp_every_thirty_seconds_event_func' );

if(!function_exists('viatorwp_every_thirty_seconds_event_func')){
	function viatorwp_every_thirty_seconds_event_func() {
		global $wpdb;
		$table_name = $wpdb->prefix."viatorwp_uploaded_products";
		$is_table_exist_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );
			$db_table_name = $wpdb->get_var($is_table_exist_query);
		if(!empty($db_table_name) && $db_table_name == $table_name){
			$flag = 0;
			$query = $wpdb->prepare("SELECT * FROM $table_name WHERE flag = %d LIMIT 8", $flag);
			$product_details = $wpdb->get_results($query, ARRAY_A);
			if(!empty($product_details)){
				if(is_array($product_details)){
					foreach ($product_details as $pro_key => $pro_value) {
						if(isset($pro_value['product_code']) && !empty($pro_value['product_code'])){
							viatorwp_fetch_product_details($pro_value['product_code'], 'ADD_PRODUCT', '');
						}
					}
				}
			}
		}
	}
}